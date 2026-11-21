<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Officer;
use App\Models\ActiveStream;
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
                        'station' => $officer->station,
                        'avatar_url' => $officer->public_avatar_url,
                        'is_hiatus' => false,
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
                        'station' => 'Central',
                        'avatar_url' => null,
                        'is_hiatus' => false,
                    ],
                ];
            });

        // 2. Get Offline 10-7 Officer Roster
        $liveOfficerIds = ActiveStream::where('status', 'LIVE')->pluck('officer_id')->filter()->toArray();

        $offlineOfficers = Officer::with('activeStream')
            ->where('is_active', true)
            ->whereNotIn('id', $liveOfficerIds)
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
                    'station' => $officer->station,
                    'avatar_url' => $officer->public_avatar_url,
                    'is_hiatus' => (bool) $officer->is_hiatus,
                    'subscriber_count' => $officer->subscriber_count,
                    'subscriber_count_text' => $officer->subscriber_count_text,
                    'status' => '10-7 OFFLINE',
                ];
            });

        // 3. Get Recent Offline Patrol Videos / VODs for Cinema Hub (Served instantly from background cache, sorted newest first)
        $recentReplays = $this->getSortedRecentReplays();

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

        return Inertia::render('Dashboard', [
            'heroSlides' => \App\Services\HeroSlideService::forAudience($request->user()),
            'initialStreams' => $activeStreams->values(),
            'initialOfflineOfficers' => $offlineOfficers->values(),
            'initialReplays' => $recentReplays,
            'deptStats' => $deptStats,
            'monthlyLeaderboard' => $this->getMonthlyLeaderboard(10, true),
            'supportOfficers' => $this->getSupportOfficers(),
            'newOfficers' => $this->getNewOfficers(),
            // Ship recommended sites with the initial payload so the row paints
            // on first render instead of after an extra round trip.
            'recommendedSites' => \App\Models\RecommendedSite::where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(),
            'lastSyncedAt' => now()->toIso8601String(),
            'meta' => [
                'title' => 'Pusat Komando Multiview & Bodycam Perwira Polisi — IME Roleplay',
                'description' => 'Pantau live stream bodycam perwira kepolisian IME Roleplay (LSPD, BCSO, SASP, SAPR) secara serentak dalam mode multiview interaktif.',
                'image' => asset('images/cars-police-twopov.jpg'),
                'url' => url('/'),
                'canonical' => url('/'),
                'type' => 'website',
            ],
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
                        'station' => $officer->station,
                        'avatar_url' => $officer->public_avatar_url,
                        'is_hiatus' => false,
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
                        'station' => 'Central',
                        'avatar_url' => null,
                        'is_hiatus' => false,
                    ],
                ];
            });

        $liveOfficerIds = ActiveStream::where('status', 'LIVE')->pluck('officer_id')->filter()->toArray();

        $offlineOfficers = Officer::with('activeStream')
            ->where('is_active', true)
            ->whereNotIn('id', $liveOfficerIds)
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
                    'station' => $officer->station,
                    'avatar_url' => $officer->public_avatar_url,
                    'is_hiatus' => (bool) $officer->is_hiatus,
                    'subscriber_count' => $officer->subscriber_count,
                    'subscriber_count_text' => $officer->subscriber_count_text,
                    'status' => '10-7 OFFLINE',
                ];
            });

        $recentReplays = $this->getSortedRecentReplays();

        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => $activeStreams->count(),
            'total_offline' => $offlineOfficers->count(),
            'lspd_live' => $activeStreams->where('officer.department', 'LSPD')->count(),
            'bcso_live' => $activeStreams->where('officer.department', 'BCSO')->count(),
            'sasp_live' => $activeStreams->where('officer.department', 'SASP')->count(),
            'sapr_live' => $activeStreams->filter(fn($s) => in_array($s['officer']['department'] ?? '', ['SAPR', 'PARK RANGER']))->count(),
        ];

        return Inertia::render('Multiview', [
            'initialStreams' => $activeStreams->values(),
            'initialOfflineOfficers' => $offlineOfficers->values(),
            'initialReplays' => $recentReplays,
            'deptStats' => $deptStats,
            'lastSyncedAt' => now()->toIso8601String(),
            'meta' => [
                'title' => 'Tactical Multiview Theater — Pantau Banyak POV Siaran Polisi Sekaligus',
                'description' => 'Panggung pemantauan multiview hingga 12 live stream bodycam perwira polisi bersamaan dengan audio switcher, squad selector, dan live chat terintegrasi.',
                'image' => asset('images/cars-police-twopov.jpg'),
                'url' => url('/multiview'),
                'canonical' => url('/multiview'),
                'type' => 'website',
            ],
        ]);
    }

    /**
     * Halaman tonton satu POV dengan layout ala YouTube.
     * Video dicari di live stream dulu, lalu cache replay, lalu riwayat stream.
     */
    public function watch(Request $request, string $videoId)
    {
        $host = $request->getHost();

        $liveStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->where(function ($q) {
                $q->whereHas('officer', fn ($o) => $o->where('is_active', true))
                  ->orWhereDoesntHave('officer');
            })
            ->orderByDesc('viewers_count')
            ->get()
            ->unique('video_id')
            ->map(fn ($s) => $this->mapWatchStream($s, $host, true))
            ->values();

        $replays = collect($this->getSortedRecentReplays());

        $current = $liveStreams->firstWhere('video_id', $videoId);

        if (! $current) {
            $replay = $replays->firstWhere('video_id', $videoId);
            if ($replay) {
                $current = array_merge($replay, ['is_live' => false, 'live_chat_url' => null]);
            }
        }

        if (! $current) {
            $past = ActiveStream::with('officer')->where('video_id', $videoId)->latest()->first();
            $current = $past
                ? $this->mapWatchStream($past, $host, false)
                : [
                    // Video tidak dikenal: tetap putar, tanpa info officer.
                    'video_id' => $videoId,
                    'title' => 'Siaran Patroli',
                    'thumbnail' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                    'description' => '',
                    'viewers_count' => 0,
                    'is_live' => false,
                    'live_chat_url' => null,
                    'officer' => null,
                ];
        }

        $officerName = $current['officer']['officer_name'] ?? null;
        $title = $current['title'] ?? 'Siaran Patroli';
        $officerId = $current['officer']['id'] ?? null;

        // TacClips: momen dari video ini, plus momen lain dari officer yang sama.
        $clips = TacClip::where('is_approved', true)
            ->where('video_id', $videoId)
            ->orderBy('start_seconds')
            ->take(50)
            ->get()
            ->map(fn ($c) => $this->mapWatchClip($c))
            ->values();

        $officerClips = $officerId
            ? TacClip::where('is_approved', true)
                ->where('officer_id', $officerId)
                ->where('video_id', '!=', $videoId)
                ->latest()
                ->take(12)
                ->get()
                ->map(fn ($c) => $this->mapWatchClip($c))
                ->values()
            : collect();

        return Inertia::render('Watch', [
            'stream' => $current,
            'clips' => $clips,
            'officerClips' => $officerClips,
            // ?t=95 starts the player at 95s; ?clip=ID highlights that TacClip.
            'startAt' => max(0, (int) $request->query('t', 0)),
            'highlightClipId' => $request->filled('clip') ? (int) $request->query('clip') : null,
            'liveStreams' => $liveStreams->reject(fn ($s) => $s['video_id'] === $videoId)->values(),
            'replays' => $replays->reject(fn ($r) => ($r['video_id'] ?? null) === $videoId)->take(12)->values(),
            'meta' => [
                'title' => $officerName ? "{$officerName} — {$title}" : $title,
                'description' => 'Tonton POV bodycam perwira IME Roleplay dengan live chat terintegrasi.',
                'image' => $current['thumbnail'] ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                'url' => url("/watch/{$videoId}"),
                'canonical' => url("/watch/{$videoId}"),
                'type' => 'video.other',
            ],
        ]);
    }

    private function mapWatchClip(TacClip $clip): array
    {
        return [
            'id' => $clip->id,
            'video_id' => $clip->video_id,
            'title' => $clip->title,
            'start_seconds' => (int) $clip->start_seconds,
            'end_seconds' => (int) $clip->end_seconds,
            'officer_name' => $clip->officer_name,
            'creator_name' => $clip->creator_name ?: 'Guest',
            'likes_count' => (int) $clip->likes_count,
        ];
    }

    private function mapWatchStream(ActiveStream $stream, string $host, bool $isLive): array
    {
        $officer = $stream->officer;

        return [
            'video_id' => $stream->video_id,
            'title' => $stream->title ?? 'Patrol Stream',
            'thumbnail' => $stream->thumbnail_url ?: "https://i.ytimg.com/vi/{$stream->video_id}/hqdefault.jpg",
            'description' => $stream->description ?? '',
            'viewers_count' => $stream->viewers_count ?? 0,
            'is_live' => $isLive,
            'live_chat_url' => $isLive
                ? "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}&dark_theme=1"
                : null,
            'officer' => $officer ? [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'streamer_name' => $officer->streamer_name,
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'rank' => $officer->rank,
                'avatar_url' => $officer->public_avatar_url,
                'subscriber_count_text' => $officer->subscriber_count_text,
                'youtube_url' => $officer->youtube_url,
            ] : null,
        ];
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
                        'station' => $officer->station,
                        'avatar_url' => $officer->public_avatar_url,
                        'is_hiatus' => false,
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
                        'station' => 'Central',
                        'avatar_url' => null,
                        'is_hiatus' => false,
                    ],
                ];
            });

        // 2. Offline 10-7 Officer Roster
        $liveOfficerIds = ActiveStream::where('status', 'LIVE')->pluck('officer_id')->filter()->toArray();
        $offlineOfficers = Officer::with('activeStream')
            ->where('is_active', true)
            ->whereNotIn('id', $liveOfficerIds)
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
                    'station' => $officer->station,
                    'avatar_url' => $officer->public_avatar_url,
                    'is_hiatus' => (bool) $officer->is_hiatus,
                    'subscriber_count' => $officer->subscriber_count,
                    'subscriber_count_text' => $officer->subscriber_count_text,
                    'status' => '10-7 OFFLINE',
                ];
            });

        // 3. Offline Officer VODs / Patrol Replays (Served instantly from background cache, sorted newest first)
        $recentReplays = $this->getSortedRecentReplays();

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
            'monthly_leaderboard' => $this->getMonthlyLeaderboard(10, true),
            'support_officers' => $this->getSupportOfficers(),
            'count' => $activeStreams->count(),
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get sorted recent replays from cache (newest first).
     */
    private function getSortedRecentReplays(): array
    {
        $recentReplays = Cache::get('cinema_hub_replays_cache', []);
        if (is_array($recentReplays) && !empty($recentReplays)) {
            usort($recentReplays, function ($a, $b) {
                $timeA = \App\Services\YouTubeScraperService::parseRelativeTimeToSeconds($a['streamed_at'] ?? $a['incident_code'] ?? '');
                $timeB = \App\Services\YouTubeScraperService::parseRelativeTimeToSeconds($b['streamed_at'] ?? $b['incident_code'] ?? '');
                return $timeA <=> $timeB;
            });
            return array_values($recentReplays);
        }
        return [];
    }

    /**
     * API: Trigger manual sync
     */
    public function apiSync(Request $request)
    {
        try {
            $job = new SyncOfficerStreamsJob();
            app()->call([$job, 'handle']);

            $liveCount = ActiveStream::where('status', 'LIVE')->count();
            $streamsData = $this->apiStreams($request)->getData(true);

            return response()->json(array_merge([
                'status' => 'success',
                'message' => 'Sync completed successfully',
                'active_units' => $liveCount,
            ], $streamsData));
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
        $liveOfficerIds = $activeStreams->pluck('officer_id')->filter()->toArray();

        $allOfficers = Officer::with(['rankRelation:id,rank_title,level', 'activeStream'])
            ->where('is_active', true)
            ->orderBy('department')
            ->orderBy('rank')
            ->get()
            ->map(function ($officer) use ($liveOfficerIds, $activeStreams) {
                $isLive = in_array($officer->id, $liveOfficerIds);
                $liveStream = $isLive ? $activeStreams->firstWhere('officer_id', $officer->id) : null;

                return [
                    'id' => $officer->id,
                    'channel_id' => $officer->channel_id,
                    'handle' => $officer->handle,
                    'streamer_name' => $officer->streamer_name,
                    'officer_name' => $officer->officer_name,
                    'callsign' => $officer->callsign,
                    'badge_number' => $officer->badge_number,
                    'department' => $officer->department,
                    'rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                    // Hierarchy level from the ranks table (10 = Chief); 0 when not linked yet.
                    'rank_level' => (int) ($officer->rankRelation?->level ?? 0),
                    'station' => $officer->station,
                    'avatar_url' => $officer->public_avatar_url,
                    'subscriber_count' => $officer->subscriber_count ?? 0,
                    'subscriber_count_text' => $officer->subscriber_count_text ?: ($officer->subscriber_count ? number_format($officer->subscriber_count) . " subs" : "< 1k subs"),
                    'is_online' => $isLive,
                    'is_hiatus' => (bool) $officer->is_hiatus,
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
            'meta' => [
                'title' => 'Direktori Perwira Kepolisian — Roster LSPD, BCSO, SASP & SAPR',
                'description' => 'Daftar lengkap perwira kepolisian aktif IME Roleplay. Cari perwira berdasarkan nama, badge, callsign, dan instansi kedinasan.',
                'image' => asset('images/car-police-officer.jpg'),
                'url' => url('/officers'),
                'canonical' => url('/officers'),
                'type' => 'website',
            ],
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
                    'avatar_url' => $officer->public_avatar_url,
                ];
            });

        $dbClips = TacClip::with('officer:id,officer_name,streamer_name,handle,avatar_url,avatar_hidden,department,callsign')
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($clip) {
                $officer = $clip->officer;
                $avatarUrl = $officer ? $officer->public_avatar_url : null;
                return [
                    'id' => $clip->id,
                    'video_id' => $clip->video_id,
                    'title' => $clip->title,
                    'start_seconds' => (int) $clip->start_seconds,
                    'end_seconds' => (int) $clip->end_seconds,
                    'officer_id' => $clip->officer_id ? (int) $clip->officer_id : ($officer ? (int) $officer->id : null),
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
            'meta' => [
                'title' => 'TacClips — Klip Video Aksi & Patroli Taktis Kepolisian IME RP',
                'description' => 'Koleksi klip video aksi, pengejaran (pursuit), baku tembak (10-13), dan momen taktis terbaik perwira kepolisian IME Roleplay.',
                'image' => asset('images/car-police-crash.jpg'),
                'url' => url('/action-clips'),
                'canonical' => url('/action-clips'),
                'type' => 'website',
            ],
        ]);
    }

    /**
     * API: Get all active community TacClips.
     */
    public function getTacClips(Request $request)
    {
        $dbClips = TacClip::with('officer:id,officer_name,streamer_name,handle,avatar_url,avatar_hidden,department,callsign')
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
                    'officer_id' => $clip->officer_id ? (int) $clip->officer_id : ($officer ? (int) $officer->id : null),
                    'officer_name' => $clip->officer_name ?: ($officer ? $officer->officer_name : 'Patrol Unit'),
                    'officer_handle' => $clip->officer_handle ?: ($officer ? $officer->handle : '@PatrolUnit'),
                    'creator_name' => $clip->creator_name ?: 'Guest',
                    'likes_count' => (int) $clip->likes_count,
                    'views_count' => (int) $clip->views_count,
                    'avatar_url' => $officer ? $officer->public_avatar_url : null,
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
                'avatar_url' => $officer ? $officer->public_avatar_url : null,
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
        $deptStats = [
            'total_officers' => Officer::where('is_active', true)->count(),
            'total_live' => ActiveStream::where('status', 'LIVE')->count(),
        ];

        return Inertia::render('RadioCodes', [
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
            'meta' => [
                'title' => 'Tentang Sistem Police Tactical Multiview — IME Roleplay',
                'description' => 'Informasi platform agregator live stream dan pemantauan terpadu perwira kepolisian IME Roleplay.',
                'url' => url('/about'),
                'canonical' => url('/about'),
                'type' => 'website',
            ],
        ]);
    }

    /**
     * Dedicated Page: Citizen & Dispatcher Reporting Portal.
     */
    public function feedbackPage(Request $request)
    {
        return Inertia::render('Feedback', [
            'prefillType' => $request->query('type', 'CHANNEL_REQUEST'),
            'meta' => [
                'title' => 'Kirim Masukan & Saran — Tactical Police Multiview',
                'description' => 'Bantu kami meningkatkan platform dengan memberikan masukan, laporan masalah, atau permintaan kanal dinas.',
                'url' => url('/feedback'),
                'canonical' => url('/feedback'),
                'type' => 'website',
            ],
        ]);
    }

    /**
     * Dedicated Page: Tactical FAQ & Help Guide.
     */
    public function faqPage(Request $request)
    {
        return Inertia::render('Faq', [
            'appVersion' => '2.4.0-Pro',
            'meta' => [
                'title' => 'FAQ & Panduan Penggunaan — Police Tactical Multiview',
                'description' => 'Pertanyaan yang sering diajukan seputar fitur multiview, bio-link perwira, pemotongan video (clipper), dan pusat komando.',
                'url' => url('/faq'),
                'canonical' => url('/faq'),
                'type' => 'website',
            ],
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
            'meta' => [
                'title' => 'Catatan Pembaruan & Changelog — Tactical Command Center',
                'description' => 'Riwayat rilis fitur baru, optimasi performa, dan pembaruan sistem Police Tactical Multiview.',
                'url' => url('/updates'),
                'canonical' => url('/updates'),
                'type' => 'website',
            ],
        ]);
    }

    /**
     * Generate monthly leaderboard metrics for officers based on DB data & live status.
     * Falls back to previous month if no officers have hours recorded in the current month.
     */
    public function getMonthlyLeaderboard(?int $limit = 10, bool $filterHiatus = false): array
    {
        $now = now();
        $liveStreams = ActiveStream::where('status', 'LIVE')->get()->keyBy('channel_id');
        $officers = Officer::with(['rankRelation', 'activeStream'])->where('is_active', true)->get();

        $buildForMonth = function (\Carbon\CarbonInterface $date, bool $isCurrent) use ($officers, $liveStreams, $limit, $filterHiatus) {
            $daysInMonth = $date->daysInMonth;
            $daysLeft = max(1, $daysInMonth - $date->day);
            $monthName = strtoupper($date->locale('id')->translatedFormat('F Y'));
            $periodText = $monthName;
            $resetCycleText = "Siklus Reset: {$daysLeft} Hari Lagi";

            if ($officers->isEmpty()) {
                return [
                    'period_text' => $periodText,
                    'reset_cycle_text' => $resetCycleText,
                    'top_streamers' => [],
                    'ranks_four_to_six' => [],
                    'all_top' => [],
                    'has_hours' => false,
                ];
            }

            $mapped = $officers->map(function ($officer) use ($liveStreams, $date, $isCurrent) {
                $isLive = $isCurrent && ($officer->activeStream !== null || $liveStreams->has($officer->channel_id));
                $liveStream = $isLive ? ($officer->activeStream ?? $liveStreams->get($officer->channel_id)) : null;

                $totalMinutesRaw = 0;
                if ($isCurrent) {
                    // For current month: count duty minutes if last_duty_at is in current month or null
                    if (!$officer->last_duty_at || $officer->last_duty_at->isSameMonth($date)) {
                        $totalMinutesRaw = (int) ($officer->monthly_duty_minutes ?? 0);
                    }
                } else {
                    // For previous month: use previous_monthly_duty_minutes column saved during reset
                    $totalMinutesRaw = (int) ($officer->previous_monthly_duty_minutes ?? 0);
                }

                $totalHoursDecimal = round($totalMinutesRaw / 60, 1);
                $hoursPart = floor($totalMinutesRaw / 60);
                $minsPart = $totalMinutesRaw % 60;
                $totalHoursStr = "{$hoursPart} Jam {$minsPart}m";

                $divisor = $isCurrent ? max(1, $date->day) : $date->daysInMonth;
                $avgDaily = round($totalHoursDecimal / $divisor, 1);
                $avgShiftStr = "{$avgDaily}j / hari";

                $youtubeUrl = $this->buildOfficerYoutubeUrl($officer);

                return [
                    'id' => $officer->id,
                    'officer_name' => $officer->officer_name,
                    'streamer_name' => $officer->streamer_name,
                    'callsign' => $officer->department ? "{$officer->department} • {$officer->callsign}" : $officer->callsign,
                    'raw_callsign' => $officer->callsign,
                    'department' => $officer->department,
                    'officer_rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                    'avatar_url' => $officer->public_avatar_url,
                    'total_hours' => $totalHoursStr,
                    'total_hours_num' => $totalHoursDecimal,
                    'total_minutes' => $totalMinutesRaw,
                    'avg_shift' => $avgShiftStr,
                    'is_live' => $isLive,
                    'is_hiatus' => (bool) $officer->is_hiatus,
                    'live_viewers' => $liveStream ? ($liveStream->viewers_count ?? 0) : 0,
                    'youtube_url' => $youtubeUrl,
                ];
            })->sortByDesc('total_hours_num')->values();

            $hasHours = $mapped->contains(fn($item) => $item['total_hours_num'] > 0);
            $topHours = $mapped->first()['total_hours_num'] ?? 184;

            $query = $mapped;
            if ($filterHiatus) {
                $query = $query->filter(fn($item) => empty($item['is_hiatus']))->values();
            }
            if ($limit !== null) {
                $query = $query->take($limit);
            }

            $leaderboard = $query->map(function ($item, $index) use ($topHours) {
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
                'has_hours' => $hasHours,
            ];
        };

        // 1. Try current month
        $currentData = $buildForMonth($now, true);

        if ($currentData['has_hours']) {
            unset($currentData['has_hours']);
            $currentData['is_previous_period'] = false;
            return $currentData;
        }

        // 2. Fallback to previous month if any officer has hours recorded for that period
        $previousMonth = $now->copy()->subMonth();
        $previousData = $buildForMonth($previousMonth, false);

        if ($previousData['has_hours']) {
            unset($previousData['has_hours']);
            $previousData['is_previous_period'] = true;
            return $previousData;
        }

        // 3. Fallback when neither period has hours: keep current month with is_previous_period = false
        unset($currentData['has_hours']);
        $currentData['is_previous_period'] = false;
        return $currentData;
    }

    /**
     * Dedicated Page: Officer Performance & Community Rankings (/ranking).
     */
    public function rankingPage(Request $request)
    {
        $now = now();
        $previousMonth = $now->copy()->subMonth();

        // 1. duty_month: from getMonthlyLeaderboard(50) (including UX-06 fallback), only minutes > 0
        $monthlyLeaderboard = $this->getMonthlyLeaderboard(50);
        $dutyMonth = collect($monthlyLeaderboard['all_top'] ?? [])
            ->filter(fn($item) => ($item['total_minutes'] ?? $item['total_hours_num']) > 0)
            ->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'officer_name' => $item['officer_name'],
                    'callsign' => $item['raw_callsign'] ?? $item['callsign'],
                    'department' => $item['department'],
                    'rank' => $item['officer_rank'] ?? '',
                    'avatar_url' => $item['avatar_url'],
                    'is_live' => (bool) ($item['is_live'] ?? false),
                    'is_hiatus' => (bool) ($item['is_hiatus'] ?? false),
                    'youtube_url' => $item['youtube_url'],
                    'value' => (int) ($item['total_minutes'] ?? round(($item['total_hours_num'] ?? 0) * 60)),
                    'value_label' => $item['total_hours'],
                    'avg_shift' => $item['avg_shift'] ?? null,
                    'percent' => $item['percent'] ?? 100,
                    'badge' => $item['badge'] ?? 'RUNNER_UP',
                ];
            })
            ->values()
            ->all();

        // 2. duty_last_month: previous_monthly_duty_minutes desc (> 0 only, max 50)
        $lastMonthDays = $previousMonth->daysInMonth;
        $dutyLastMonthOfficers = Officer::with(['rankRelation', 'activeStream'])
            ->where('is_active', true)
            ->where('previous_monthly_duty_minutes', '>', 0)
            ->orderByDesc('previous_monthly_duty_minutes')
            ->take(50)
            ->get();

        $topLastMonthMinutes = (int) ($dutyLastMonthOfficers->first()?->previous_monthly_duty_minutes ?? 0);
        $dutyLastMonth = $dutyLastMonthOfficers->map(function ($officer, $index) use ($topLastMonthMinutes, $lastMonthDays) {
            $mins = (int) $officer->previous_monthly_duty_minutes;
            $hoursPart = floor($mins / 60);
            $minsPart = $mins % 60;
            $totalHoursStr = "{$hoursPart} Jam {$minsPart}m";

            $totalHoursDecimal = round($mins / 60, 1);
            $avgDaily = round($totalHoursDecimal / $lastMonthDays, 1);
            $avgShiftStr = "{$avgDaily}j / hari";

            $rank = $index + 1;
            $percent = $topLastMonthMinutes > 0 ? min(100, round(($mins / $topLastMonthMinutes) * 100)) : 100;
            $badge = match ($rank) {
                1 => 'EMAS',
                2 => 'SILVER',
                3 => 'BRONZE',
                default => 'RUNNER_UP',
            };

            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                'avatar_url' => $officer->public_avatar_url,
                'is_live' => $officer->activeStream !== null && $officer->activeStream->status === 'LIVE',
                'is_hiatus' => (bool) $officer->is_hiatus,
                'youtube_url' => $this->buildOfficerYoutubeUrl($officer),
                'value' => $mins,
                'value_label' => $totalHoursStr,
                'avg_shift' => $avgShiftStr,
                'percent' => $percent,
                'badge' => $badge,
            ];
        })->values()->all();

        // 3. subscribers: subscriber_count desc (max 50)
        $subscriberOfficers = Officer::with(['rankRelation', 'activeStream'])
            ->where('is_active', true)
            ->orderByDesc('subscriber_count')
            ->take(50)
            ->get();

        $subscribers = $subscriberOfficers->map(function ($officer) {
            $count = (int) ($officer->subscriber_count ?? 0);
            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                'avatar_url' => $officer->public_avatar_url,
                'is_live' => $officer->activeStream !== null && $officer->activeStream->status === 'LIVE',
                'is_hiatus' => (bool) $officer->is_hiatus,
                'youtube_url' => $this->buildOfficerYoutubeUrl($officer),
                'value' => $count,
                'value_label' => $this->formatSubscriberCount($count),
            ];
        })->values()->all();

        // 4. road_to_1k: subscriber_count < 1000, desc (closest to 1K first, max 50)
        $roadTo1kOfficers = Officer::with(['rankRelation', 'activeStream'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('subscriber_count')
                  ->orWhere('subscriber_count', '<', 1000);
            })
            ->orderByDesc('subscriber_count')
            ->take(50)
            ->get();

        $roadTo1k = $roadTo1kOfficers->map(function ($officer) {
            $count = (int) ($officer->subscriber_count ?? 0);
            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                'avatar_url' => $officer->public_avatar_url,
                'is_live' => $officer->activeStream !== null && $officer->activeStream->status === 'LIVE',
                'is_hiatus' => (bool) $officer->is_hiatus,
                'youtube_url' => $this->buildOfficerYoutubeUrl($officer),
                'value' => $count,
                'value_label' => number_format($count, 0, ',', '.') . '/1.000',
            ];
        })->values()->all();

        // 5. recently_on_duty: last_duty_at desc (not null, max 50)
        $recentlyOnDutyOfficers = Officer::with(['rankRelation', 'activeStream'])
            ->where('is_active', true)
            ->whereNotNull('last_duty_at')
            ->orderByDesc('last_duty_at')
            ->take(50)
            ->get();

        $recentlyOnDuty = $recentlyOnDutyOfficers->map(function ($officer) {
            return [
                'id' => $officer->id,
                'officer_name' => $officer->officer_name,
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                'avatar_url' => $officer->public_avatar_url,
                'is_live' => $officer->activeStream !== null && $officer->activeStream->status === 'LIVE',
                'is_hiatus' => (bool) $officer->is_hiatus,
                'youtube_url' => $this->buildOfficerYoutubeUrl($officer),
                'value' => $officer->last_duty_at->toIso8601String(),
                'value_label' => $officer->last_duty_at->locale('id')->diffForHumans(),
            ];
        })->values()->all();

        $currentPeriodText = strtoupper($now->locale('id')->translatedFormat('F Y'));
        $previousPeriodText = strtoupper($previousMonth->locale('id')->translatedFormat('F Y'));

        return Inertia::render('Ranking', [
            'ranking' => [
                'duty_month' => $dutyMonth,
                'duty_last_month' => $dutyLastMonth,
                'subscribers' => $subscribers,
                'road_to_1k' => $roadTo1k,
                'recently_on_duty' => $recentlyOnDuty,
            ],
            'period_text' => [
                'current' => $currentPeriodText,
                'previous' => $previousPeriodText,
            ],
            'currentPeriodText' => $currentPeriodText,
            'previousPeriodText' => $previousPeriodText,
            'meta' => [
                'title' => 'Peringkat & Jam Dinas Perwira — Statistik Patroli Bulanan IME RP',
                'description' => 'Peringkat keaktifan patroli dan rekapitulasi jam dinas bulanan perwira polisi IME Roleplay. Statistik transparan dan objektif.',
                'image' => asset('images/cars-police-twopov.jpg'),
                'url' => url('/ranking'),
                'canonical' => url('/ranking'),
                'type' => 'website',
            ],
        ]);
    }

    /**
     * Build canonical YouTube URL for an officer.
     */
    protected function buildOfficerYoutubeUrl(Officer $officer): string
    {
        $handle = trim($officer->handle ?? '');
        if (!empty($handle)) {
            $cleanHandle = str_starts_with($handle, '@') ? $handle : '@' . $handle;
            return "https://www.youtube.com/{$cleanHandle}";
        } elseif (!empty($officer->channel_id) && !str_starts_with($officer->channel_id, 'UC_')) {
            return "https://www.youtube.com/channel/{$officer->channel_id}";
        }
        return "https://youtube.com";
    }

    /**
     * Format subscriber count to Indonesian shorthand (e.g. 12,3 rb, 1,2 jt).
     */
    protected function formatSubscriberCount(int $count): string
    {
        if ($count >= 1_000_000) {
            $val = $count / 1_000_000;
            return str_replace('.', ',', (string) round($val, 1)) . ' jt';
        }
        if ($count >= 1_000) {
            $val = $count / 1_000;
            return str_replace('.', ',', (string) round($val, 1)) . ' rb';
        }
        return (string) $count;
    }

    /**
     * Get active officers with under 1,000 YouTube subscribers for the Road to 1K Support Grid.
     */
    /**
     * Officer yang baru ditambahkan ke database dalam config('police.new_officer_days')
     * hari terakhir (default 14). Setelah lewat batas itu, otomatis hilang dari daftar.
     */
    public function getNewOfficers(): array
    {
        $days = config('police.new_officer_days', 14);
        $liveChannelIds = ActiveStream::where('status', 'LIVE')->pluck('channel_id')->flip();

        return Officer::where('is_active', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->map(function ($officer) use ($liveChannelIds, $days) {
                return [
                    'id' => $officer->id,
                    'officer_name' => $officer->officer_name,
                    'streamer_name' => ltrim($officer->handle ?: $officer->streamer_name, '@'),
                    'callsign' => $officer->callsign,
                    'department' => $officer->department,
                    'rank' => $officer->rank,
                    'avatar_url' => $officer->public_avatar_url,
                    'is_live_now' => $liveChannelIds->has($officer->channel_id),
                    'youtube_url' => $officer->youtube_url,
                    'created_at' => $officer->created_at->toIso8601String(),
                    'days_since_added' => (int) $officer->created_at->diffInDays(now()),
                    'days_remaining' => max(0, $days - (int) $officer->created_at->diffInDays(now())),
                ];
            })
            ->values()
            ->all();
    }

    public function getSupportOfficers(): array
    {
        $liveStreams = ActiveStream::where('status', 'LIVE')->get()->keyBy('channel_id');

        // Query active officers with subscriber_count < 1000 (or null).
        // Eager-load activeStream so is_hiatus doesn't fire per-officer queries.
        $officers = Officer::with('activeStream')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('subscriber_count')
                  ->orWhere('subscriber_count', '<', 1000);
            })
            ->get();

        if ($officers->isEmpty()) {
            $officers = Officer::with('activeStream')
                ->where('is_active', true)
                ->orderBy('subscriber_count', 'asc')
                ->take(9)
                ->get();
        }

        // Dukungan hanya untuk perwira yang masih rutin live — officer hiatus
        // (lebih dari config('police.hiatus_days') hari sejak terakhir live)
        // tidak ditampilkan.
        $officers = $officers->filter(fn ($officer) => ! $officer->is_hiatus);

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
                'avatar_url' => $officer->public_avatar_url,
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


