<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use App\Models\HeroSlideStat;
use App\Services\AuditLogger;
use App\Services\HeroImageService;
use App\Services\HeroSlideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HeroSlideController extends Controller
{
    public function index(): JsonResponse
    {
        $since = now()->subDays(6)->toDateString();

        $slides = HeroSlide::query()
            ->withSum(['stats as impressions_7d' => fn ($q) => $q->where('stat_date', '>=', $since)], 'impressions')
            ->withSum(['stats as clicks_7d' => fn ($q) => $q->where('stat_date', '>=', $since)], 'clicks')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (HeroSlide $slide) {
                $data = $slide->toArray();
                $data['impressions_7d'] = (int) ($slide->impressions_7d ?? 0);
                $data['clicks_7d'] = (int) ($slide->clicks_7d ?? 0);

                return $data;
            });

        return response()->json([
            'slides' => $slides,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSlide($request);

        $slideType = $validated['slide_type'];
        $isActive = (bool) ($validated['is_active'] ?? true);

        if ($slideType === 'live_spotlight' && $isActive) {
            $this->ensureSingleActiveSpotlight();
        }

        $desktopPath = null;
        $mobilePath = null;

        if ($request->hasFile('image_desktop')) {
            $desktopPath = HeroImageService::store($request->file('image_desktop'), 'desktop');
        } elseif ($slideType !== 'live_spotlight') {
            throw ValidationException::withMessages([
                'image_desktop' => ['Gambar desktop wajib diunggah untuk tipe slide ini.'],
            ]);
        }

        if ($request->hasFile('image_mobile')) {
            $mobilePath = HeroImageService::store($request->file('image_mobile'), 'mobile');
        }

        if (($desktopPath || $mobilePath) && empty($validated['image_alt'])) {
            HeroImageService::delete($desktopPath);
            HeroImageService::delete($mobilePath);

            throw ValidationException::withMessages([
                'image_alt' => ['Teks alternatif (alt) wajib diisi bila slide memiliki gambar.'],
            ]);
        }

        $maxOrder = (int) HeroSlide::max('sort_order');

        $slide = HeroSlide::create([
            'slide_type' => $slideType,
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'badge_label' => $validated['badge_label'] ?? null,
            'accent' => $validated['accent'] ?? 'primary',
            'image_desktop_path' => $desktopPath,
            'image_mobile_path' => $mobilePath,
            'image_alt' => $validated['image_alt'] ?? null,
            'cta_primary_label' => $validated['cta_primary_label'] ?? null,
            'cta_primary_url' => $validated['cta_primary_url'] ?? null,
            'cta_secondary_label' => $validated['cta_secondary_label'] ?? null,
            'cta_secondary_url' => $validated['cta_secondary_url'] ?? null,
            'audience' => $validated['audience'] ?? 'all',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'sort_order' => $validated['sort_order'] ?? ($maxOrder + 1),
            'is_active' => $isActive,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        AuditLogger::log('hero_slide.created', 'hero_slide', $slide->id, null, $slide->toArray());
        HeroSlideService::forget();

        return response()->json([
            'message' => 'Slide hero berhasil ditambahkan.',
            'slide' => $slide->fresh(),
        ], 201);
    }

    public function update(Request $request, HeroSlide $slide): JsonResponse
    {
        $validated = $this->validateSlide($request, $slide);

        $slideType = $validated['slide_type'] ?? $slide->slide_type;
        $isActive = array_key_exists('is_active', $validated)
            ? (bool) $validated['is_active']
            : (bool) $slide->is_active;

        if ($slideType === 'live_spotlight' && $isActive) {
            $this->ensureSingleActiveSpotlight($slide->id);
        }

        $before = $slide->toArray();

        $desktopPath = $slide->image_desktop_path;
        $mobilePath = $slide->image_mobile_path;

        if ($request->hasFile('image_desktop')) {
            $newDesktop = HeroImageService::store($request->file('image_desktop'), 'desktop');
            HeroImageService::delete($desktopPath);
            $desktopPath = $newDesktop;
        } elseif ($slideType !== 'live_spotlight' && !$desktopPath) {
            throw ValidationException::withMessages([
                'image_desktop' => ['Gambar desktop wajib diunggah untuk tipe slide ini.'],
            ]);
        }

        if ($request->hasFile('image_mobile')) {
            $newMobile = HeroImageService::store($request->file('image_mobile'), 'mobile');
            HeroImageService::delete($mobilePath);
            $mobilePath = $newMobile;
        }

        $imageAlt = array_key_exists('image_alt', $validated) ? $validated['image_alt'] : $slide->image_alt;
        if (($desktopPath || $mobilePath) && empty($imageAlt)) {
            throw ValidationException::withMessages([
                'image_alt' => ['Teks alternatif (alt) wajib diisi bila slide memiliki gambar.'],
            ]);
        }

        $slide->update([
            'slide_type' => $slideType,
            'title' => array_key_exists('title', $validated) ? $validated['title'] : $slide->title,
            'subtitle' => array_key_exists('subtitle', $validated) ? $validated['subtitle'] : $slide->subtitle,
            'badge_label' => array_key_exists('badge_label', $validated) ? $validated['badge_label'] : $slide->badge_label,
            'accent' => $validated['accent'] ?? $slide->accent,
            'image_desktop_path' => $desktopPath,
            'image_mobile_path' => $mobilePath,
            'image_alt' => $imageAlt,
            'cta_primary_label' => array_key_exists('cta_primary_label', $validated) ? $validated['cta_primary_label'] : $slide->cta_primary_label,
            'cta_primary_url' => array_key_exists('cta_primary_url', $validated) ? $validated['cta_primary_url'] : $slide->cta_primary_url,
            'cta_secondary_label' => array_key_exists('cta_secondary_label', $validated) ? $validated['cta_secondary_label'] : $slide->cta_secondary_label,
            'cta_secondary_url' => array_key_exists('cta_secondary_url', $validated) ? $validated['cta_secondary_url'] : $slide->cta_secondary_url,
            'audience' => $validated['audience'] ?? $slide->audience,
            'starts_at' => array_key_exists('starts_at', $validated) ? $validated['starts_at'] : $slide->starts_at,
            'ends_at' => array_key_exists('ends_at', $validated) ? $validated['ends_at'] : $slide->ends_at,
            'sort_order' => $validated['sort_order'] ?? $slide->sort_order,
            'is_active' => $isActive,
            'updated_by' => $request->user()?->id,
        ]);

        AuditLogger::log('hero_slide.updated', 'hero_slide', $slide->id, $before, $slide->fresh()->toArray());
        HeroSlideService::forget();

        return response()->json([
            'message' => 'Slide hero berhasil diperbarui.',
            'slide' => $slide->fresh(),
        ]);
    }

    public function destroy(HeroSlide $slide): JsonResponse
    {
        $before = $slide->toArray();
        $slideId = $slide->id;

        HeroImageService::delete($slide->image_desktop_path);
        HeroImageService::delete($slide->image_mobile_path);

        $slide->delete();

        AuditLogger::log('hero_slide.deleted', 'hero_slide', $slideId, $before, null);
        HeroSlideService::forget();

        return response()->json([
            'message' => 'Slide hero berhasil dihapus.',
        ]);
    }

    public function toggle(Request $request, HeroSlide $slide): JsonResponse
    {
        $before = $slide->toArray();
        $nextState = !$slide->is_active;

        if ($slide->slide_type === 'live_spotlight' && $nextState) {
            $this->ensureSingleActiveSpotlight($slide->id);
        }

        $slide->update([
            'is_active' => $nextState,
            'updated_by' => $request->user()?->id,
        ]);

        AuditLogger::log('hero_slide.toggled', 'hero_slide', $slide->id, $before, $slide->fresh()->toArray());
        HeroSlideService::forget();

        return response()->json([
            'message' => $nextState ? 'Slide hero diaktifkan.' : 'Slide hero dinonaktifkan.',
            'slide' => $slide->fresh(),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $orderedIds = array_map('intval', $validated['ordered_ids']);
        $existingIds = HeroSlide::pluck('id')->map(fn ($id) => (int) $id)->all();

        sort($existingIds);
        $sortedInput = $orderedIds;
        sort($sortedInput);

        if ($existingIds !== $sortedInput) {
            throw ValidationException::withMessages([
                'ordered_ids' => ['Daftar ID slide harus mencakup seluruh slide secara lengkap dan unik.'],
            ]);
        }

        $before = HeroSlide::orderBy('sort_order')->pluck('id')->all();

        DB::transaction(function () use ($orderedIds, $request) {
            foreach ($orderedIds as $index => $id) {
                HeroSlide::where('id', $id)->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $request->user()?->id,
                ]);
            }
        });

        AuditLogger::log('hero_slide.reordered', 'hero_slide', 0, ['ordered_ids' => $before], ['ordered_ids' => $orderedIds]);
        HeroSlideService::forget();

        return response()->json([
            'message' => 'Urutan slide hero berhasil diperbarui.',
        ]);
    }

    public function recordEvent(Request $request, HeroSlide $slide): Response
    {
        if (!$slide->is_active) {
            abort(404);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:impression,click'],
        ]);

        $today = now()->toDateString();

        $stat = HeroSlideStat::firstOrCreate(
            [
                'hero_slide_id' => $slide->id,
                'stat_date' => $today,
            ],
            [
                'impressions' => 0,
                'clicks' => 0,
            ]
        );

        if ($validated['type'] === 'impression') {
            $stat->increment('impressions');
        } else {
            $stat->increment('clicks');
        }

        return response()->noContent();
    }

    protected function validateSlide(Request $request, ?HeroSlide $existing = null): array
    {
        $urlRule = function (string $attribute, mixed $value, \Closure $fail) {
            if ($value === null || $value === '') {
                return;
            }
            if (!is_string($value)) {
                $fail('URL CTA tidak valid.');
                return;
            }
            $isRelativePath = str_starts_with($value, '/') && !str_starts_with($value, '//');
            $isHttps = str_starts_with($value, 'https://');
            if (!$isRelativePath && !$isHttps) {
                $fail('URL CTA harus diawali "/" (path internal) atau "https://".');
            }
        };

        return $request->validate([
            'slide_type' => ['required', 'in:feature,event,cta,live_spotlight'],
            'title' => ['required_unless:slide_type,live_spotlight', 'nullable', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'badge_label' => ['nullable', 'string', 'max:24'],
            'accent' => ['required', 'in:primary,info,success,warning,danger,dept-lspd,dept-bcso,dept-sasp,dept-sapr'],
            'image_alt' => ['nullable', 'string', 'max:150'],
            'cta_primary_label' => ['nullable', 'string', 'max:30'],
            'cta_primary_url' => ['nullable', 'string', 'max:500', $urlRule],
            'cta_secondary_label' => ['nullable', 'string', 'max:30'],
            'cta_secondary_url' => ['nullable', 'string', 'max:500', $urlRule],
            'audience' => ['required', 'in:all,guest,member,officer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    protected function ensureSingleActiveSpotlight(?int $ignoreId = null): void
    {
        $query = HeroSlide::where('slide_type', 'live_spotlight')->where('is_active', true);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slide_type' => ['Maksimal satu slide Live Spotlight yang dapat aktif pada saat bersamaan.'],
            ]);
        }
    }
}

