<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Models\Squad;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SquadController extends Controller
{
    /**
     * Public visible squads list.
     */
    public function public(): JsonResponse
    {
        $squads = Cache::remember('squads.public', 60, function () {
            $list = Squad::publicVisible()
                ->with(['user', 'officers.activeStream', 'officers.agency'])
                ->get();

            // Keep the owner id out of the cached payload's public fields; is_owner is
            // per-viewer and must be computed after reading from the shared cache.
            return $list->map(fn (Squad $squad) => [...$this->formatSquad($squad), '_owner_id' => $squad->user_id])
                ->sort(function ($a, $b) {
                    if ($b['live_count'] === $a['live_count']) {
                        return $b['forks_count'] <=> $a['forks_count'];
                    }
                    return $b['live_count'] <=> $a['live_count'];
                })
                ->take(24)
                ->values()
                ->all();
        });

        $viewerId = Auth::id();
        $squads = array_map(function (array $squad) use ($viewerId) {
            $squad['is_owner'] = $viewerId !== null && $squad['_owner_id'] === $viewerId;
            unset($squad['_owner_id']);

            return $squad;
        }, $squads);

        return response()->json([
            'status' => 'success',
            'data' => $squads,
        ]);
    }

    /**
     * Authenticated user's squads.
     */
    public function mine(Request $request): JsonResponse
    {
        $squads = $request->user()->squads()
            ->with(['user', 'officers.activeStream', 'officers.agency'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Squad $squad) => $this->formatSquad($squad))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $squads,
        ]);
    }

    /**
     * Show squad by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $squad = Squad::where('slug', $slug)
            ->with(['user', 'officers.activeStream', 'officers.agency'])
            ->first();

        if (! $squad) {
            return response()->json(['message' => 'Squad tidak ditemukan.'], 404);
        }

        $isOwner = Auth::check() && Auth::id() === $squad->user_id;

        // Squad harus public & tidak is_hidden, atau milik user yang sedang login
        if (! $isOwner) {
            if ($squad->is_hidden || $squad->visibility !== 'public') {
                return response()->json(['message' => 'Squad tidak ditemukan.'], 404);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatSquad($squad),
        ]);
    }

    /**
     * Store new squad.
     */
    public function store(Request $request): JsonResponse
    {
        $maxPerUser = (int) config('squads.max_per_user', 20);
        if ($request->user()->squads()->count() >= $maxPerUser) {
            return response()->json([
                'message' => "Anda telah mencapai batas maksimal {$maxPerUser} squad.",
            ], 422);
        }

        $maxOfficers = (int) config('squads.max_officers', 12);
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:40',
            'description' => 'nullable|string|max:160',
            'visibility' => 'nullable|in:private,public',
            'officer_ids' => "required|array|min:1|max:{$maxOfficers}",
            'officer_ids.*' => 'required|integer|exists:officers,id',
        ]);

        if ($this->containsBlockedWord($validated['name'])) {
            return response()->json([
                'message' => 'Nama squad mengandung kata yang tidak diperbolehkan.',
            ], 422);
        }

        $slug = $this->generateUniqueSlug($validated['name']);

        $squad = Squad::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'] ?? 'private',
            'forked_from_id' => null,
            'forks_count' => 0,
            'is_hidden' => false,
        ]);

        $attachData = [];
        foreach ($validated['officer_ids'] as $index => $officerId) {
            $attachData[$officerId] = ['position' => $index];
        }
        $squad->officers()->sync($attachData);

        Cache::forget('squads.public');

        $squad->load(['user', 'officers.activeStream', 'officers.agency']);

        return response()->json([
            'status' => 'success',
            'message' => 'Squad berhasil dibuat.',
            'data' => $this->formatSquad($squad),
        ], 201);
    }

    /**
     * Update existing squad.
     */
    public function update(Request $request, Squad $squad): JsonResponse
    {
        if ($squad->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah squad ini.',
            ], 403);
        }

        $maxOfficers = (int) config('squads.max_officers', 12);
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:40',
            'description' => 'nullable|string|max:160',
            'visibility' => 'nullable|in:private,public',
            'officer_ids' => "required|array|min:1|max:{$maxOfficers}",
            'officer_ids.*' => 'required|integer|exists:officers,id',
        ]);

        if ($this->containsBlockedWord($validated['name'])) {
            return response()->json([
                'message' => 'Nama squad mengandung kata yang tidak diperbolehkan.',
            ], 422);
        }

        $squad->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'] ?? $squad->visibility,
        ]);

        $attachData = [];
        foreach ($validated['officer_ids'] as $index => $officerId) {
            $attachData[$officerId] = ['position' => $index];
        }
        $squad->officers()->sync($attachData);

        Cache::forget('squads.public');

        $squad->load(['user', 'officers.activeStream', 'officers.agency']);

        return response()->json([
            'status' => 'success',
            'message' => 'Squad berhasil diperbarui.',
            'data' => $this->formatSquad($squad),
        ]);
    }

    /**
     * Delete squad.
     */
    public function destroy(Request $request, Squad $squad): JsonResponse
    {
        if ($squad->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menghapus squad ini.',
            ], 403);
        }

        $squad->delete();

        Cache::forget('squads.public');

        return response()->json([
            'status' => 'success',
            'message' => 'Squad berhasil dihapus.',
        ]);
    }

    /**
     * Fork a public squad into user's private copy.
     */
    public function fork(Request $request, Squad $squad): JsonResponse
    {
        $isOwner = $squad->user_id === $request->user()->id;

        if ($squad->is_hidden) {
            return response()->json([
                'message' => 'Squad tersembunyi tidak dapat di-fork.',
                'code' => 'SQUAD_HIDDEN',
            ], 403);
        }

        if ($squad->visibility !== 'public' && ! $isOwner) {
            return response()->json([
                'message' => 'Squad privat tidak dapat di-fork.',
                'code' => 'SQUAD_PRIVATE',
            ], 403);
        }

        $maxPerUser = (int) config('squads.max_per_user', 20);
        if ($request->user()->squads()->count() >= $maxPerUser) {
            return response()->json([
                'message' => "Anda telah mencapai batas maksimal {$maxPerUser} squad.",
            ], 422);
        }

        $forkName = mb_substr($squad->name . ' (fork)', 0, 40);
        $slug = $this->generateUniqueSlug($forkName);

        $newSquad = Squad::create([
            'user_id' => $request->user()->id,
            'name' => $forkName,
            'slug' => $slug,
            'description' => $squad->description,
            'visibility' => 'private',
            'forked_from_id' => $squad->id,
            'forks_count' => 0,
            'is_hidden' => false,
        ]);

        $officersWithPositions = $squad->officers()->get()->mapWithKeys(function ($o) {
            return [$o->id => ['position' => $o->pivot->position]];
        })->toArray();

        $newSquad->officers()->sync($officersWithPositions);

        // Squad asal forks_count bertambah 1, data anggota squad asal tidak berubah
        $squad->increment('forks_count');

        Cache::forget('squads.public');

        $newSquad->load(['user', 'officers.activeStream', 'officers.agency']);

        return response()->json([
            'status' => 'success',
            'message' => 'Squad berhasil di-fork.',
            'data' => $this->formatSquad($newSquad),
        ], 201);
    }

    /**
     * Admin public squads listing (with search & pagination).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Squad::where('visibility', 'public')
            ->with(['user', 'officers']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $squads = $query->orderBy('created_at', 'desc')->paginate(20);

        $items = collect($squads->items())->map(function (Squad $squad) {
            return [
                'id' => $squad->id,
                'name' => $squad->name,
                'slug' => $squad->slug,
                'description' => $squad->description,
                'visibility' => $squad->visibility,
                'owner_name' => $squad->user?->name ?? 'Anonim',
                'forks_count' => $squad->forks_count,
                'is_hidden' => $squad->is_hidden,
                'officers_count' => $squad->officers->count(),
                'created_at' => $squad->created_at?->toISOString(),
                'updated_at' => $squad->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'pagination' => [
                'current_page' => $squads->currentPage(),
                'last_page' => $squads->lastPage(),
                'per_page' => $squads->perPage(),
                'total' => $squads->total(),
            ],
        ]);
    }

    /**
     * Admin toggle hide for public squad.
     */
    public function toggleHide(Request $request, Squad $squad): JsonResponse
    {
        $before = $squad->is_hidden;
        $squad->is_hidden = ! $before;
        $squad->save();

        AuditLogger::log(
            'squad.hide',
            'Squad',
            $squad->id,
            ['is_hidden' => $before],
            ['is_hidden' => $squad->is_hidden],
            $request->input('reason', 'Admin toggle hide squad')
        );

        Cache::forget('squads.public');

        return response()->json([
            'status' => 'success',
            'is_hidden' => $squad->is_hidden,
            'message' => $squad->is_hidden
                ? 'Squad berhasil disembunyikan.'
                : 'Squad ditampilkan kembali.',
        ]);
    }

    /**
     * Helper to format squad array.
     */
    protected function formatSquad(Squad $squad): array
    {
        $officers = $squad->officers->map(function (Officer $officer) {
            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'department' => $officer->agency?->agency_code ?? $officer->department,
                'avatar_url' => $officer->avatar_url,
                'channel_id' => $officer->channel_id,
                'is_live' => $officer->activeStream !== null,
            ];
        });

        $liveCount = $officers->where('is_live', true)->count();

        return [
            'id' => $squad->id,
            'name' => $squad->name,
            'slug' => $squad->slug,
            'description' => $squad->description,
            'visibility' => $squad->visibility,
            'owner_name' => $squad->user?->name ?? 'Anonim',
            'is_owner' => Auth::check() && Auth::id() === $squad->user_id,
            'forked_from_id' => $squad->forked_from_id,
            'forks_count' => $squad->forks_count,
            'is_hidden' => $squad->is_hidden,
            'officers' => $officers->values()->all(),
            'live_count' => $liveCount,
            'created_at' => $squad->created_at?->toISOString(),
            'updated_at' => $squad->updated_at?->toISOString(),
        ];
    }

    /**
     * Helper to generate unique slug.
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'squad';
        }
        do {
            $slug = $baseSlug . '-' . Str::lower(Str::random(6));
        } while (Squad::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Helper to check blocked words.
     */
    protected function containsBlockedWord(string $name): bool
    {
        $blockedWords = config('squads.blocked_words', []);
        if (empty($blockedWords)) {
            return false;
        }

        $lowerName = strtolower($name);
        foreach ($blockedWords as $word) {
            $trimmed = trim(strtolower($word));
            if ($trimmed !== '' && str_contains($lowerName, $trimmed)) {
                return true;
            }
        }

        return false;
    }
}

