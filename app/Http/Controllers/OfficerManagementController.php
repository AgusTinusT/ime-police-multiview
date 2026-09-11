<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Models\ActiveStream;
use App\Jobs\SyncOfficerStreamsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfficerManagementController extends Controller
{
    /**
     * List all officers from database with search & department filters.
     */
    public function index(Request $request)
    {
        $query = Officer::query();

        if ($request->filled('dept') && $request->dept !== 'ALL') {
            $query->where('department', $request->dept);
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(officer_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(streamer_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(callsign) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(handle) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(badge_number) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(patrol_zone) LIKE ?', ["%{$search}%"]);
            });
        }

        $officers = $query->orderBy('department')->orderBy('rank')->get();

        return response()->json([
            'status' => 'success',
            'data' => $officers,
            'count' => $officers->count(),
        ]);
    }

    /**
     * Store a newly created officer in MySQL database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'handle' => 'required|string|max:100',
            'streamer_name' => 'required|string|max:150',
            'officer_name' => 'required|string|max:150',
            'callsign' => 'required|string|max:50',
            'department' => 'required|string|max:50',
            'rank' => 'nullable|string|max:100',
            'badge_number' => 'nullable|string|max:50',
            'patrol_zone' => 'nullable|string|max:150',
            'channel_id' => 'nullable|string|max:64',
            'avatar_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $handle = trim($validated['handle']);
        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }
        $validated['handle'] = $handle;

        // Auto-generate unique channel_id if not provided
        if (empty($validated['channel_id'])) {
            $validated['channel_id'] = 'UC_' . Str::slug(str_replace('@', '', $handle), '_') . '_' . substr(md5($handle . time()), 0, 8);
        }

        // Auto avatar if empty
        if (empty($validated['avatar_url'])) {
            $seed = Str::slug($handle);
            $validated['avatar_url'] = "https://api.dicebear.com/7.x/bottts/svg?seed={$seed}";
        }

        $validated['rank'] = $validated['rank'] ?? 'Officer';
        $validated['badge_number'] = $validated['badge_number'] ?? '#000';
        $validated['patrol_zone'] = $validated['patrol_zone'] ?? 'Los Santos Metropolitan';
        $validated['is_active'] = $request->boolean('is_active', true);

        // Check if handle or channel_id already exists
        $existing = Officer::where('handle', $validated['handle'])
            ->orWhere('channel_id', $validated['channel_id'])
            ->first();

        if ($existing) {
            $existing->update($validated);
            $officer = $existing;
        } else {
            $officer = Officer::create($validated);
        }

        // Trigger background live stream sync
        try {
            $job = new SyncOfficerStreamsJob();
            app()->call([$job, 'handle']);
        } catch (\Exception $e) {
            // Log without failing response
        }

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$officer->officer_name} ({$officer->callsign}) successfully added to database.",
            'data' => $officer,
        ], 201);
    }

    /**
     * Update an existing officer in MySQL database.
     */
    public function update(Request $request, $id)
    {
        $officer = Officer::findOrFail($id);

        $validated = $request->validate([
            'handle' => 'required|string|max:100',
            'streamer_name' => 'required|string|max:150',
            'officer_name' => 'required|string|max:150',
            'callsign' => 'required|string|max:50',
            'department' => 'required|string|max:50',
            'rank' => 'nullable|string|max:100',
            'badge_number' => 'nullable|string|max:50',
            'patrol_zone' => 'nullable|string|max:150',
            'channel_id' => 'nullable|string|max:64',
            'avatar_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $handle = trim($validated['handle']);
        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }
        $validated['handle'] = $handle;

        $officer->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$officer->officer_name} updated successfully.",
            'data' => $officer,
        ]);
    }

    /**
     * Toggle the active status of an officer.
     */
    public function toggle($id)
    {
        $officer = Officer::findOrFail($id);
        $officer->is_active = !$officer->is_active;
        $officer->save();

        // If deactivated, mark active streams as ended
        if (!$officer->is_active) {
            ActiveStream::where('channel_id', $officer->channel_id)
                ->update(['status' => 'ENDED']);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$officer->officer_name} is now " . ($officer->is_active ? 'ACTIVE' : 'DISABLED'),
            'is_active' => $officer->is_active,
            'data' => $officer,
        ]);
    }

    /**
     * Delete an officer permanently from database.
     */
    public function destroy($id)
    {
        $officer = Officer::findOrFail($id);
        
        // Remove any associated active streams
        ActiveStream::where('channel_id', $officer->channel_id)->delete();

        $name = $officer->officer_name;
        $callsign = $officer->callsign;
        
        $officer->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$name} ({$callsign}) deleted successfully.",
        ]);
    }

    /**
     * Render the dedicated standalone Inertia Admin Hub page.
     */
    public function adminPage(Request $request)
    {
        $officersCount = Officer::count();
        $activeOfficersCount = Officer::where('is_active', true)->count();
        $lspdCount = Officer::where('is_active', true)->where('department', 'LSPD')->count();
        $bcsoCount = Officer::where('is_active', true)->where('department', 'BCSO')->count();
        $saspCount = Officer::where('is_active', true)->where('department', 'SASP')->count();
        $saprCount = Officer::where('is_active', true)->whereIn('department', ['SAPR', 'PARK RANGER'])->count();
        $liveCount = ActiveStream::where('status', 'LIVE')->count();

        return \Inertia\Inertia::render('Admin/OfficerManagement', [
            'stats' => [
                'total_officers' => $officersCount,
                'active_officers' => $activeOfficersCount,
                'live_streams' => $liveCount,
                'lspd' => $lspdCount,
                'bcso' => $bcsoCount,
                'sasp' => $saspCount,
                'sapr' => $saprCount,
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],
        ]);
    }

    /**
     * Check and verify a YouTube channel / handle in real-time.
     */
    public function checkChannel(Request $request, \App\Services\YouTubeScraperService $scraper)
    {
        $request->validate([
            'handle' => 'required|string|max:255',
        ]);

        $info = $scraper->fetchChannelInfo($request->handle);

        if (!$info) {
            return response()->json([
                'status' => 'error',
                'message' => 'YouTube channel not found or inaccessible. Please verify the handle or URL.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $info,
        ]);
    }

    /**
     * Trigger live stream crawling on-demand.
     */
    public function syncStreams(\App\Services\YouTubeScraperService $scraper)
    {
        try {
            $job = new SyncOfficerStreamsJob();
            app()->call([$job, 'handle']);

            $liveCount = ActiveStream::where('status', 'LIVE')->count();

            return response()->json([
                'status' => 'success',
                'message' => "Live stream synchronization complete. Found {$liveCount} units currently LIVE on patrol.",
                'live_count' => $liveCount,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Manual stream sync failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync live streams: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger subscriber count update on-demand.
     */
    public function syncSubscribers()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('officer:sync-subscribers');
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'YouTube subscriber count synchronization completed successfully.',
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Manual subscriber sync failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update subscriber counts: ' . $e->getMessage(),
            ], 500);
        }
    }
}
