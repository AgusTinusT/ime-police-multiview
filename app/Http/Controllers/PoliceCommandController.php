<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Officer;
use App\Models\ActiveStream;
use App\Jobs\SyncOfficerStreamsJob;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PoliceCommandController extends Controller
{
    /**
     * Render the Tactical Police Command Center Dashboard.
     */
    public function dashboard(Request $request)
    {
        $this->syncStreamsIfNeeded();

        $host = $request->getHost();

        // 1. Get Online 10-8 Live Streams with Officer info
        $activeStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->get()
            ->map(function ($stream) use ($host) {
                $officer = $stream->officer;
                return [
                    'id' => $stream->id,
                    'video_id' => $stream->video_id,
                    'title' => $stream->title ?? 'Patrol Stream',
                    'thumbnail' => $stream->thumbnail_url,
                    'status' => 'LIVE',
                    'incident_code' => $stream->incident_code ?? '10-8 Routine Patrol',
                    'viewers_count' => $stream->viewers_count ?? 0,
                    'live_chat_url' => "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}",
                    'officer' => $officer ? [
                        'id' => $officer->id,
                        'channel_id' => $officer->channel_id,
                        'handle' => $officer->handle,
                        'streamer_name' => $officer->streamer_name,
                        'officer_name' => $officer->officer_name,
                        'callsign' => $officer->callsign,
                        'badge_number' => $officer->badge_number,
                        'department' => $officer->department,
                        'rank' => $officer->rank,
                        'patrol_zone' => $officer->patrol_zone,
                        'avatar_url' => $officer->avatar_url,
                    ] : [
                        'id' => 0,
                        'channel_id' => $stream->channel_id,
                        'handle' => '@Unit',
                        'streamer_name' => 'Officer',
                        'officer_name' => 'Patrol Unit',
                        'callsign' => '1-ADAM-00',
                        'badge_number' => '#000',
                        'department' => 'LSPD',
                        'rank' => 'Officer',
                        'patrol_zone' => 'Los Santos',
                        'avatar_url' => null,
                    ],
                ];
            });

        // 2. Get Offline 10-7 Officer Roster
        $liveChannelIds = ActiveStream::where('status', 'LIVE')->pluck('channel_id')->toArray();

        $offlineOfficers = Officer::where('is_active', true)
            ->whereNotIn('channel_id', $liveChannelIds)
            ->orderBy('department')
            ->orderBy('rank')
            ->get()
            ->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'channel_id' => $officer->channel_id,
                    'handle' => $officer->handle,
                    'streamer_name' => $officer->streamer_name,
                    'officer_name' => $officer->officer_name,
                    'callsign' => $officer->callsign,
                    'badge_number' => $officer->badge_number,
                    'department' => $officer->department,
                    'rank' => $officer->rank,
                    'patrol_zone' => $officer->patrol_zone,
                    'avatar_url' => $officer->avatar_url,
                    'status' => '10-7 OFFLINE',
                ];
            });

        // 3. Department Unit Breakdown Stats
        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => $activeStreams->count(),
            'total_offline' => $offlineOfficers->count(),
            'lspd_live' => $activeStreams->where('officer.department', 'LSPD')->count(),
            'bcso_live' => $activeStreams->where('officer.department', 'BCSO')->count(),
            'sasp_live' => $activeStreams->where('officer.department', 'SASP')->count(),
        ];

        return Inertia::render('PoliceDashboard', [
            'initialStreams' => $activeStreams->values(),
            'initialOfflineOfficers' => $offlineOfficers->values(),
            'deptStats' => $deptStats,
            'lastSyncedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Get Active Streams & Telemetry
     */
    public function apiStreams(Request $request)
    {
        $this->syncStreamsIfNeeded();

        $dept = $request->query('dept');
        $query = ActiveStream::with('officer')->where('status', 'LIVE');

        if ($dept && $dept !== 'ALL') {
            $query->whereHas('officer', function ($q) use ($dept) {
                $q->where('department', $dept);
            });
        }

        $streams = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $streams,
            'count' => $streams->count(),
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Trigger manual sync
     */
    public function apiSync()
    {
        try {
            $job = new SyncOfficerStreamsJob();
            app()->call([$job, 'handle']);

            $liveCount = ActiveStream::where('status', 'LIVE')->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Sync completed successfully',
                'active_units' => $liveCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Live YouTube search by hashtag or keyword
     */
    public function apiSearchLive(Request $request, \App\Services\YouTubeScraperService $scraperService)
    {
        $query = $request->input('q') ?? $request->query('q') ?? '';
        $query = trim($query);

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kata kunci pencarian minimal 2 karakter.',
                'data' => [],
            ], 422);
        }

        $results = $scraperService->searchLiveStreams($query, 20);

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'count' => count($results),
            'data' => $results,
        ]);
    }

    /**
     * Sync active stream statuses with a 2-minute cooldown lock.
     */
    protected function syncStreamsIfNeeded(): void
    {
        $lockKey = 'police_streams_sync_lock';

        if (!Cache::has($lockKey)) {
            Cache::put($lockKey, true, 120);

            try {
                $job = new SyncOfficerStreamsJob();
                app()->call([$job, 'handle']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Police auto-sync failed: ' . $e->getMessage());
                Cache::forget($lockKey);
            }
        }
    }
}
