<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Officer;
use App\Models\ActiveStream;
use App\Models\TacChannel;
use App\Models\TacClip;
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
        $host = $request->getHost();

        // 1. Get Online 10-8 Live Streams with Officer info (Deduplicated strictly by video_id)
        $activeStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->where(function ($q) {
                $q->whereHas('officer', fn($o) => $o->where('is_active', true))
                  ->orWhereDoesntHave('officer');
            })
            ->get()
            ->unique('video_id')
            ->map(function ($stream) use ($host) {
                $officer = $stream->officer;
                return [
                    'id' => $stream->id,
                    'video_id' => $stream->video_id,
                    'title' => $stream->title ?? 'Patrol Stream',
                    'thumbnail' => $stream->thumbnail_url,
                    'status' => 'LIVE',
                    'incident_code' => $stream->incident_code ?? '10-8 Routine Patrol',
                    'description' => $stream->description ?? '',
                    'viewers_count' => $stream->viewers_count ?? 0,
                    'live_chat_url' => "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}&dark_theme=1",
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
                        'subscriber_count' => $officer->subscriber_count,
                        'subscriber_count_text' => $officer->subscriber_count_text,
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
                    'subscriber_count' => $officer->subscriber_count,
                    'subscriber_count_text' => $officer->subscriber_count_text,
                    'status' => '10-7 OFFLINE',
                ];
            });

        // 3. Get Recent Offline Patrol Videos / VODs for Cinema Hub (Served instantly from background cache)
        $recentReplays = Cache::get('cinema_hub_replays_cache', []);

        // 4. Department Unit Breakdown Stats
        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => $activeStreams->count(),
            'total_offline' => $offlineOfficers->count(),
            'lspd_live' => $activeStreams->where('officer.department', 'LSPD')->count(),
            'bcso_live' => $activeStreams->where('officer.department', 'BCSO')->count(),
            'sasp_live' => $activeStreams->where('officer.department', 'SASP')->count(),
            'sapr_live' => $activeStreams->filter(fn($s) => in_array($s['officer']['department'] ?? '', ['SAPR', 'PARK RANGER']))->count(),
        ];

        // 5. Tactical Radio Channels (TAC 1 to TAC 5)
        TacChannel::ensureChannelsExist();
        $tacChannels = TacChannel::orderBy('id')->get()->map(function ($ch) {
            $ch->checkAndResetIfExpired();
            return [
                'id' => $ch->id,
                'code' => $ch->code,
                'name' => $ch->name,
                'video_ids' => $ch->video_ids ?? [],
                'expires_at' => $ch->expires_at ? $ch->expires_at->toIso8601String() : null,
                'remaining_seconds' => $ch->remaining_seconds,
                'is_active' => $ch->is_active,
                'unit_count' => $ch->unit_count,
            ];
        });

        return Inertia::render('Dashboard', [
            'initialStreams' => $activeStreams->values(),
            'initialOfflineOfficers' => $offlineOfficers->values(),
            'initialTacChannels' => $tacChannels->values(),
            'initialReplays' => $recentReplays,
            'deptStats' => $deptStats,
            'monthlyLeaderboard' => $this->getMonthlyLeaderboard(),
            'supportOfficers' => $this->getSupportOfficers(),
            'lastSyncedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Render the Dedicated Police Tactical Command Center Dashboard.
     */
    public function policePage(Request $request)
    {
        return $this->multiview($request);
    }

    /**
     * Render the Standalone Full Viewport Multiview Theater Stage (PoliceDashboard reference page).
     */
    public function multiview(Request $request)
    {
        $host = $request->getHost();

        // Get Online 10-8 Live Streams
        $activeStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->where(function ($q) {
                $q->whereHas('officer', fn($o) => $o->where('is_active', true))
                  ->orWhereDoesntHave('officer');
            })
            ->get()
            ->unique('video_id')
            ->map(function ($stream) use ($host) {
                $officer = $stream->officer;
                return [
                    'id' => $stream->id,
                    'video_id' => $stream->video_id,
                    'title' => $stream->title ?? 'Patrol Stream',
                    'thumbnail' => $stream->thumbnail_url,
                    'status' => 'LIVE',
                    'incident_code' => $stream->incident_code ?? '10-8 Routine Patrol',
                    'description' => $stream->description ?? '',
                    'viewers_count' => $stream->viewers_count ?? 0,
                    'live_chat_url' => "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}&dark_theme=1",
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
                        'subscriber_count' => $officer->subscriber_count,
                        'subscriber_count_text' => $officer->subscriber_count_text,
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
                    'subscriber_count' => $officer->subscriber_count,
                    'subscriber_count_text' => $officer->subscriber_count_text,
                    'status' => '10-7 OFFLINE',
                ];
            });

        $recentReplays = Cache::get('cinema_hub_replays_cache', []);

        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => $activeStreams->count(),
            'total_offline' => $offlineOfficers->count(),
            'lspd_live' => $activeStreams->where('officer.department', 'LSPD')->count(),
            'bcso_live' => $activeStreams->where('officer.department', 'BCSO')->count(),
            'sasp_live' => $activeStreams->where('officer.department', 'SASP')->count(),
            'sapr_live' => $activeStreams->filter(fn($s) => in_array($s['officer']['department'] ?? '', ['SAPR', 'PARK RANGER']))->count(),
        ];

        TacChannel::ensureChannelsExist();
        $tacChannels = TacChannel::orderBy('id')->get()->map(function ($ch) {
            $ch->checkAndResetIfExpired();
            return [
                'id' => $ch->id,
                'code' => $ch->code,
                'name' => $ch->name,
                'video_ids' => $ch->video_ids ?? [],
                'expires_at' => $ch->expires_at ? $ch->expires_at->toIso8601String() : null,
                'remaining_seconds' => $ch->remaining_seconds,
                'is_active' => $ch->is_active,
                'unit_count' => $ch->unit_count,
            ];
        });

        return Inertia::render('Multiview', [
            'initialStreams' => $activeStreams->values(),
            'initialOfflineOfficers' => $offlineOfficers->values(),
            'initialTacChannels' => $tacChannels->values(),
            'initialReplays' => $recentReplays,
            'deptStats' => $deptStats,
            'lastSyncedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Get Active Streams & Telemetry
     */
    public function apiStreams(Request $request)
    {
        $host = $request->getHost();

        // 1. Online 10-8 Live Streams with Officer info (Deduplicated strictly by video_id)
        $activeStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->where(function ($q) {
                $q->whereHas('officer', fn($o) => $o->where('is_active', true))
                  ->orWhereDoesntHave('officer');
            })
            ->get()
            ->unique('video_id')
            ->map(function ($stream) use ($host) {
                $officer = $stream->officer;
                return [
                    'id' => $stream->id,
                    'video_id' => $stream->video_id,
                    'title' => $stream->title ?? 'Patrol Stream',
                    'thumbnail' => $stream->thumbnail_url,
                    'status' => 'LIVE',
                    'incident_code' => $stream->incident_code ?? '10-8 Routine Patrol',
                    'description' => $stream->description ?? '',
                    'viewers_count' => $stream->viewers_count ?? 0,
                    'live_chat_url' => "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}&dark_theme=1",
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
                        'subscriber_count' => $officer->subscriber_count,
                        'subscriber_count_text' => $officer->subscriber_count_text,
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

        // 2. Offline 10-7 Officer Roster
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
                    'subscriber_count' => $officer->subscriber_count,
                    'subscriber_count_text' => $officer->subscriber_count_text,
                    'status' => '10-7 OFFLINE',
                ];
            });

        // 3. Offline Officer VODs / Patrol Replays (Served instantly from background cache)
        $recentReplays = Cache::get('cinema_hub_replays_cache', []);

        // 4. Department Unit Breakdown Stats
        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => $activeStreams->count(),
            'total_offline' => $offlineOfficers->count(),
            'lspd_live' => $activeStreams->where('officer.department', 'LSPD')->count(),
            'bcso_live' => $activeStreams->where('officer.department', 'BCSO')->count(),
            'sasp_live' => $activeStreams->where('officer.department', 'SASP')->count(),
            'sapr_live' => $activeStreams->filter(fn($s) => in_array($s['officer']['department'] ?? '', ['SAPR', 'PARK RANGER']))->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $activeStreams->values(),
            'replays' => $recentReplays,
            'offline_officers' => $offlineOfficers->values(),
            'dept_stats' => $deptStats,
            'monthly_leaderboard' => $this->getMonthlyLeaderboard(),
            'support_officers' => $this->getSupportOfficers(),
            'count' => $activeStreams->count(),
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
        } catch (\Throwable $e) {
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
     * API: Get full stream details (title, full description, viewers) by video ID.
     */
    public function apiStreamDetails(Request $request, \App\Services\YouTubeScraperService $scraperService)
    {
        $videoId = $request->query('video_id') ?? $request->input('video_id') ?? '';
        $videoId = trim($videoId);

        if (strlen($videoId) !== 11) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video ID tidak valid.',
            ], 422);
        }

        $details = $scraperService->scrapeVideoDetails($videoId);

        if (!$details) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail video YouTube.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $details,
        ]);
    }

    /**
     * API: Batch Live Telemetry (viewers count & live status) for active stream video IDs.
     */
    public function apiTelemetry(Request $request, \App\Services\YouTubeScraperService $scraperService)
    {
        $videoIds = $request->input('video_ids') ?? $request->query('video_ids') ?? [];
        if (is_string($videoIds)) {
            $videoIds = explode(',', $videoIds);
        }

        if (!is_array($videoIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'video_ids must be an array or comma-separated string.',
            ], 422);
        }

        $telemetry = $scraperService->getBatchStreamsTelemetry($videoIds);

        return response()->json([
            'status' => 'success',
            'count' => count($telemetry),
            'data' => $telemetry,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Dedicated Page: Officer Directory & Streamer Roster.
     */
    public function officers(Request $request)
    {
        $activeStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->get();
        $liveChannelIds = $activeStreams->pluck('channel_id')->filter()->toArray();

        $allOfficers = Officer::where('is_active', true)
            ->orderBy('department')
            ->orderBy('rank')
            ->get()
            ->map(function ($officer) use ($liveChannelIds, $activeStreams) {
                $isLive = in_array($officer->channel_id, $liveChannelIds);
                $liveStream = $isLive ? $activeStreams->firstWhere('channel_id', $officer->channel_id) : null;

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
                    'subscriber_count' => $officer->subscriber_count ?? 0,
                    'subscriber_count_text' => $officer->subscriber_count_text ?: ($officer->subscriber_count ? number_format($officer->subscriber_count) . " subs" : "< 1k subs"),
                    'is_online' => $isLive,
                    'live_video_id' => $liveStream ? $liveStream->video_id : null,
                ];
            });

        $deptStats = [
            'total_officers' => $allOfficers->count(),
            'total_live' => $allOfficers->where('is_online', true)->count(),
            'total_offline' => $allOfficers->where('is_online', false)->count(),
            'lspd_total' => $allOfficers->where('department', 'LSPD')->count(),
            'bcso_total' => $allOfficers->where('department', 'BCSO')->count(),
            'sasp_total' => $allOfficers->where('department', 'SASP')->count(),
            'sapr_total' => $allOfficers->filter(fn($o) => in_array($o['department'], ['SAPR', 'PARK RANGER']))->count(),
        ];

        return Inertia::render('OfficerDirectory', [
            'initialOfficers' => $allOfficers->values(),
            'deptStats' => $deptStats,
            'lastSyncedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Dedicated Standalone Page: Community Action Clips Gallery (`/clips`).
     */
    public function clipsPage(Request $request)
    {
        $allOfficers = Officer::where('is_active', true)
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
                    'avatar_url' => $officer->avatar_url,
                ];
            });

        $dbClips = TacClip::with('officer:id,officer_name,streamer_name,handle,avatar_url,department,callsign')
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($clip) {
                $officer = $clip->officer;
                $avatarUrl = $officer ? $officer->avatar_url : null;
                return [
                    'id' => $clip->id,
                    'video_id' => $clip->video_id,
                    'title' => $clip->title,
                    'start_seconds' => (int) $clip->start_seconds,
                    'end_seconds' => (int) $clip->end_seconds,
                    'officer_id' => $clip->officer_id,
                    'officer_name' => $clip->officer_name ?: ($officer ? $officer->officer_name : 'Patrol Unit'),
                    'officer_handle' => $clip->officer_handle ?: ($officer ? $officer->handle : '@PatrolUnit'),
                    'creator_name' => $clip->creator_name ?: 'Guest',
                    'likes_count' => (int) $clip->likes_count,
                    'views_count' => (int) $clip->views_count,
                    'avatar_url' => $avatarUrl,
                    'user_id' => $clip->user_id,
                    'created_at' => $clip->created_at ? $clip->created_at->toIso8601String() : null,
                ];
            });

        return Inertia::render('Clips', [
            'officers' => $allOfficers->values(),
            'initialClips' => $dbClips->values(),
            'lastSyncedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Get all active community TacClips.
     */
    public function getTacClips(Request $request)
    {
        $dbClips = TacClip::with('officer:id,officer_name,streamer_name,handle,avatar_url,department,callsign')
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($clip) {
                $officer = $clip->officer;
                return [
                    'id' => $clip->id,
                    'video_id' => $clip->video_id,
                    'title' => $clip->title,
                    'start_seconds' => (int) $clip->start_seconds,
                    'end_seconds' => (int) $clip->end_seconds,
                    'officer_id' => $clip->officer_id,
                    'officer_name' => $clip->officer_name ?: ($officer ? $officer->officer_name : 'Patrol Unit'),
                    'officer_handle' => $clip->officer_handle ?: ($officer ? $officer->handle : '@PatrolUnit'),
                    'creator_name' => $clip->creator_name ?: 'Guest',
                    'likes_count' => (int) $clip->likes_count,
                    'views_count' => (int) $clip->views_count,
                    'avatar_url' => $officer ? $officer->avatar_url : null,
                    'user_id' => $clip->user_id,
                    'created_at' => $clip->created_at ? $clip->created_at->toIso8601String() : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $dbClips->values(),
        ]);
    }

    /**
     * API: Store a new community TacClip into MySQL.
     */
    public function storeTacClip(Request $request)
    {
        $validated = $request->validate([
            'youtube_url' => 'nullable|string',
            'video_id' => 'nullable|string|max:11',
            'title' => 'required|string|max:255',
            'start_seconds' => 'required|integer|min:0',
            'end_seconds' => 'required|integer|gt:start_seconds',
            'officer_id' => 'nullable|integer',
            'officer_name' => 'nullable|string|max:255',
            'officer_handle' => 'nullable|string|max:255',
            'creator_name' => 'nullable|string|max:255',
        ]);

        $videoId = $validated['video_id'] ?? null;
        if (!$videoId && !empty($validated['youtube_url'])) {
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|live|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $validated['youtube_url'], $matches)) {
                $videoId = $matches[1];
            }
        }

        if (!$videoId || strlen($videoId) !== 11) {
            return response()->json([
                'status' => 'error',
                'message' => 'URL atau Video ID YouTube tidak valid.',
            ], 422);
        }

        $duration = $validated['end_seconds'] - $validated['start_seconds'];
        if ($duration > 600) {
            return response()->json([
                'status' => 'error',
                'message' => 'Durasi klip maksimal 10 menit (600 detik).',
            ], 422);
        }

        $officer = null;
        if (!empty($validated['officer_id'])) {
            $officer = Officer::find($validated['officer_id']);
        } elseif (!empty($validated['officer_name']) || !empty($validated['officer_handle'])) {
            $officer = Officer::where('officer_name', $validated['officer_name'])
                ->orWhere('handle', $validated['officer_handle'])
                ->first();
        }

        $user = $request->user();
        $creatorName = $user ? $user->name : (!empty($validated['creator_name']) ? trim($validated['creator_name']) : 'Guest');

        $clip = TacClip::create([
            'user_id' => $user ? $user->id : null,
            'officer_id' => $officer ? $officer->id : null,
            'video_id' => $videoId,
            'title' => trim($validated['title']),
            'start_seconds' => (int) $validated['start_seconds'],
            'end_seconds' => (int) $validated['end_seconds'],
            'officer_name' => $officer ? $officer->officer_name : ($validated['officer_name'] ?? 'Patrol Unit'),
            'officer_handle' => $officer ? $officer->handle : ($validated['officer_handle'] ?? '@PatrolUnit'),
            'creator_name' => $creatorName,
            'likes_count' => 0,
            'views_count' => 0,
            'is_approved' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'TacClip momen aksi berhasil ditambahkan dan dapat dilihat oleh seluruh komunitas!',
            'clip' => [
                'id' => $clip->id,
                'video_id' => $clip->video_id,
                'title' => $clip->title,
                'start_seconds' => (int) $clip->start_seconds,
                'end_seconds' => (int) $clip->end_seconds,
                'officer_id' => $clip->officer_id,
                'officer_name' => $clip->officer_name,
                'officer_handle' => $clip->officer_handle,
                'creator_name' => $clip->creator_name,
                'likes_count' => 0,
                'views_count' => 0,
                'avatar_url' => $officer ? $officer->avatar_url : null,
                'user_id' => $clip->user_id,
                'created_at' => $clip->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * API: Like/Unlike a TacClip.
     */
    public function likeTacClip(Request $request, $id)
    {
        $clip = TacClip::findOrFail($id);
        $clip->increment('likes_count');
        return response()->json([
            'status' => 'success',
            'likes_count' => (int) $clip->likes_count,
        ]);
    }

    /**
     * API: Delete a TacClip.
     */
    public function destroyTacClip(Request $request, $id)
    {
        $clip = TacClip::findOrFail($id);
        $user = $request->user();

        if ($user && ($user->isAdmin() || $clip->user_id === $user->id)) {
            $clip->delete();
            return response()->json(['status' => 'success', 'message' => 'Klip berhasil dihapus.']);
        }

        $clip->delete();
        return response()->json(['status' => 'success', 'message' => 'Klip berhasil dihapus.']);
    }

    /**
     * Dedicated Page: 10-Codes and Radio Operational Protocols Guide.
     */
    public function radioCodes(Request $request)
    {
        TacChannel::ensureChannelsExist();
        $tacChannels = TacChannel::orderBy('id')->get()->map(function ($ch) {
            $ch->checkAndResetIfExpired();
            return [
                'id' => $ch->id,
                'code' => $ch->code,
                'name' => $ch->name,
                'video_ids' => $ch->video_ids ?? [],
                'remaining_seconds' => $ch->remaining_seconds,
                'is_active' => $ch->is_active,
                'unit_count' => $ch->unit_count,
            ];
        });

        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => ActiveStream::where('status', 'LIVE')->count(),
        ];

        return Inertia::render('RadioCodes', [
            'tacChannels' => $tacChannels->values(),
            'deptStats' => $deptStats,
            'lastSyncedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Dedicated Page: About Police Command Center.
     */
    public function about(Request $request)
    {
        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => ActiveStream::where('status', 'LIVE')->count(),
            'lspd_count' => Officer::where('department', 'LSPD')->where('is_active', true)->count(),
            'bcso_count' => Officer::where('department', 'BCSO')->where('is_active', true)->count(),
            'sasp_count' => Officer::where('department', 'SASP')->where('is_active', true)->count(),
        ];

        return Inertia::render('About', [
            'deptStats' => $deptStats,
            'appVersion' => '2.4.0-Pro',
        ]);
    }

    /**
     * Dedicated Page: Citizen & Dispatcher Reporting Portal.
     */
    public function feedbackPage(Request $request)
    {
        return Inertia::render('Feedback', [
            'prefillType' => $request->query('type', 'CHANNEL_REQUEST'),
        ]);
    }

    /**
     * Dedicated Page: Tactical FAQ & Help Guide.
     */
    public function faqPage(Request $request)
    {
        return Inertia::render('Faq', [
            'appVersion' => '2.4.0-Pro',
        ]);
    }

    public function qnaPage(Request $request)
    {
        return $this->faqPage($request);
    }

    /**
     * Dedicated Page: System Updates & Release Changelog.
     */
    public function updatesPage(Request $request)
    {
        return Inertia::render('Updates', [
            'appVersion' => '2.4.0-Pro',
        ]);
    }

    /**
     * Generate monthly leaderboard metrics for officers based on DB data & live status.
     */
    public function getMonthlyLeaderboard(): array
    {
        $now = now();
        $daysInMonth = $now->daysInMonth;
        $daysLeft = max(1, $daysInMonth - $now->day);
        $monthName = strtoupper($now->locale('id')->translatedFormat('F Y'));
        $periodText = $monthName;
        $resetCycleText = "Siklus Reset: {$daysLeft} Hari Lagi";

        $liveStreams = ActiveStream::where('status', 'LIVE')->get()->keyBy('channel_id');
        $officers = Officer::where('is_active', true)->get();

        if ($officers->isEmpty()) {
            return [
                'period_text' => $periodText,
                'reset_cycle_text' => $resetCycleText,
                'top_streamers' => [],
                'ranks_four_to_six' => [],
                'all_top' => [],
            ];
        }

        $mapped = $officers->map(function ($officer) use ($liveStreams, $now) {
            $isLive = $liveStreams->has($officer->channel_id);
            $liveStream = $isLive ? $liveStreams->get($officer->channel_id) : null;

            $totalMinutesRaw = (int) ($officer->monthly_duty_minutes ?? 0);

            $totalHoursDecimal = round($totalMinutesRaw / 60, 1);
            $hoursPart = floor($totalMinutesRaw / 60);
            $minsPart = $totalMinutesRaw % 60;
            $totalHoursStr = "{$hoursPart} Jam {$minsPart}m";

            $avgDaily = round($totalHoursDecimal / max(1, $now->day), 1);
            $avgShiftStr = "{$avgDaily}j / hari";

            $handle = trim($officer->handle ?? '');
            if (!empty($handle)) {
                $cleanHandle = str_starts_with($handle, '@') ? $handle : '@' . $handle;
                $youtubeUrl = "https://www.youtube.com/{$cleanHandle}";
            } elseif (!empty($officer->channel_id) && !str_starts_with($officer->channel_id, 'UC_')) {
                $youtubeUrl = "https://www.youtube.com/channel/{$officer->channel_id}";
            } else {
                $youtubeUrl = "https://youtube.com";
            }

            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'streamer_name' => $officer->streamer_name,
                'callsign' => $officer->department ? "{$officer->department} • {$officer->callsign}" : $officer->callsign,
                'department' => $officer->department,
                'avatar_url' => $officer->avatar_url ?: "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($officer->handle ?: 'officer'),
                'total_hours' => $totalHoursStr,
                'total_hours_num' => $totalHoursDecimal,
                'avg_shift' => $avgShiftStr,
                'is_live' => $isLive,
                'live_viewers' => $liveStream ? ($liveStream->viewers_count ?? 0) : 0,
                'youtube_url' => $youtubeUrl,
            ];
        })->sortByDesc('total_hours_num')->values();

        $topHours = $mapped->first()['total_hours_num'] ?? 184;

        $leaderboard = $mapped->take(6)->map(function ($item, $index) use ($topHours) {
            $rank = $index + 1;
            $percent = $topHours > 0 ? min(100, round(($item['total_hours_num'] / $topHours) * 100)) : 100;

            $badge = match ($rank) {
                1 => 'EMAS',
                2 => 'SILVER',
                3 => 'BRONZE',
                default => 'RUNNER_UP',
            };

            return array_merge($item, [
                'rank' => $rank,
                'badge' => $badge,
                'percent' => $percent,
                'role' => "{$item['department']} • {$item['total_hours']}",
            ]);
        });

        return [
            'period_text' => $periodText,
            'reset_cycle_text' => $resetCycleText,
            'top_streamers' => $leaderboard->slice(0, 3)->values()->all(),
            'ranks_four_to_six' => $leaderboard->slice(3, 3)->values()->all(),
            'all_top' => $leaderboard->values()->all(),
        ];
    }

    /**
     * Get active officers with under 1,000 YouTube subscribers for the Road to 1K Support Grid.
     */
    public function getSupportOfficers(): array
    {
        $liveStreams = ActiveStream::where('status', 'LIVE')->get()->keyBy('channel_id');

        // Query active officers with subscriber_count < 1000 (or null)
        $officers = Officer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('subscriber_count')
                  ->orWhere('subscriber_count', '<', 1000);
            })
            ->get();

        if ($officers->isEmpty()) {
            $officers = Officer::where('is_active', true)
                ->orderBy('subscriber_count', 'asc')
                ->take(9)
                ->get();
        }

        return $officers->map(function ($officer) use ($liveStreams) {
            $isLive = $liveStreams->has($officer->channel_id);
            $handle = trim($officer->handle ?? '');
            if (!empty($handle)) {
                $cleanHandle = str_starts_with($handle, '@') ? $handle : '@' . $handle;
                $youtubeUrl = "https://www.youtube.com/{$cleanHandle}";
            } elseif (!empty($officer->channel_id) && !str_starts_with($officer->channel_id, 'UC_')) {
                $youtubeUrl = "https://www.youtube.com/channel/{$officer->channel_id}";
            } else {
                $youtubeUrl = "https://youtube.com";
            }

            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'streamer_name' => ltrim($officer->handle ?: $officer->streamer_name, '@'),
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'avatar_url' => $officer->avatar_url ?: "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($officer->handle ?: 'officer'),
                'subscriber_count' => $officer->subscriber_count ?? 0,
                'subscriber_count_text' => $officer->subscriber_count_text ?: ($officer->subscriber_count ? number_format($officer->subscriber_count) . " subs" : "< 1k subs"),
                'is_live_now' => $isLive,
                'youtube_url' => $youtubeUrl,
            ];
        })->sortByDesc('is_live_now')->values()->take(12)->all();
    }

    /**
     * Sync active stream statuses with a fast 25-second cooldown lock.
     */
    protected function syncStreamsIfNeeded(): void
    {
        $lockKey = 'police_streams_sync_lock';

        if (!Cache::has($lockKey)) {
            Cache::put($lockKey, true, 25);

            try {
                $job = new SyncOfficerStreamsJob();
                app()->call([$job, 'handle']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Police auto-sync failed: ' . $e->getMessage());
                Cache::forget($lockKey);
            }
        }
    }
}


