<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Officer;
use App\Models\ActiveStream;
use App\Services\LiveNotificationTriggerService;
use App\Services\YouTubeScraperService;
use App\Services\YouTubeWebSubService;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncOfficerStreamsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180; // 3 minutes timeout for stream syncing

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(
        YouTubeScraperService $scraper,
        LiveNotificationTriggerService $notificationTrigger,
        ?YouTubeWebSubService $webSub = null,
    ): void {
        $webSub ??= app(YouTubeWebSubService::class);
        // Purge any active stream records belonging to disabled or unlisted
        // (non-personnel, bio-link only) officers.
        ActiveStream::whereHas('officer', fn($q) => $q->where('is_active', false)->orWhere('is_listed', false))->delete();

        $officers = Officer::roster()->get();
        if ($officers->isEmpty()) {
            return;
        }

        // Run hybrid multi-phase fast sync
        $liveMap = $scraper->syncAllActiveOfficers($officers);

        foreach ($officers as $officer) {
            $liveData = $liveMap[$officer->id] ?? null;

            if ($liveData && !empty($liveData['video_id'])) {
                // Auto-save valid UC... YouTube channel_id when officer is missing one (enables WebSub real-time push)
                $detectedChannelId = trim((string) ($liveData['channel_id'] ?? ''));
                $needsChannelId = empty($officer->channel_id)
                    || str_starts_with($officer->channel_id, 'custom-')
                    || str_starts_with($officer->channel_id, 'UC_');

                if ($needsChannelId && $webSub->isEligibleChannel($detectedChannelId)) {
                    $officer->update(['channel_id' => $detectedChannelId]);
                    $officer->refresh();

                    try {
                        $webSub->subscribe($officer);
                    } catch (\Throwable $e) {
                        Log::warning('Auto WebSub subscribe after channel_id discovery failed', [
                            'officer_id' => $officer->id,
                            'channel_id' => $detectedChannelId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $channelId = $officer->channel_id ?: ($liveData['channel_id'] ?: 'ch-' . $officer->id);

                // Clean up any other active stream record having this exact video_id for a different officer
                ActiveStream::where('video_id', $liveData['video_id'])
                    ->where('officer_id', '!=', $officer->id)
                    ->delete();

                $streamDescription = $liveData['description'] ?? null;
                $youtubeStartedAt = $liveData['started_at'] ?? null;
                $needsDescription = empty($streamDescription) || strlen($streamDescription) < 150;
                $needsStartedAt = empty($youtubeStartedAt)
                    && ! ActiveStream::where('officer_id', $officer->id)
                        ->where('video_id', $liveData['video_id'])
                        ->whereNotNull('live_notified_at')
                        ->exists();

                if (!empty($liveData['video_id']) && ($needsDescription || $needsStartedAt)) {
                    $details = $scraper->scrapeVideoDetails($liveData['video_id']);
                    if ($details) {
                        if (!empty($details['description']) && $needsDescription) {
                            $streamDescription = $details['description'];
                        }
                        if (empty($youtubeStartedAt) && !empty($details['started_at'])) {
                            $youtubeStartedAt = $details['started_at'];
                        }
                    }
                }

                // Check if stream is valid Police Duty 10-8 vs Badside/Civilian
                $isPolicePatrol = $scraper->isPolicePatrolStream($liveData['title'] ?? '', $streamDescription ?? '', $officer);

                if ($isPolicePatrol) {
                    // Update or create active stream with officer_id
                    $activeStream = ActiveStream::updateOrCreate(
                        [
                            'officer_id' => $officer->id,
                            'video_id' => $liveData['video_id'],
                        ],
                        [
                            'channel_id' => $channelId,
                            'title' => $liveData['title'],
                            'thumbnail_url' => $liveData['thumbnail_url'],
                            'status' => 'LIVE',
                            'viewers_count' => $liveData['viewers_count'] ?? 0,
                            'description' => $streamDescription,
                            'last_synced_at' => now(),
                        ]
                    );

                    $method = strtoupper((string) ($liveData['detection_method'] ?? ''));
                    $detectionSource = match ($method) {
                        'HASHTAG_SCAN' => 'sync_hashtag',
                        'RSS_FEED' => 'sync_rss',
                        'HANDLE_LIVE' => 'sync_handle',
                        default => 'sync',
                    };

                    $notificationTrigger->triggerIfNotNotified($officer, $activeStream, $detectionSource, $youtubeStartedAt);

                    $lastDuty = $officer->last_duty_at;
                    $currentTime = now();

                    if (!$lastDuty || $lastDuty->diffInMinutes($currentTime) > 15) {
                        // Sesi patroli live baru atau kembali live setelah jeda > 15 menit
                        $officer->update(['last_duty_at' => $currentTime]);
                    } else {
                        // Perwira sedang live terus-menerus. Akumulasi hanya menit presisi yang benar-benar berlalu
                        $diffMins = intval($lastDuty->diffInMinutes($currentTime));
                        if ($diffMins >= 1) {
                            $officer->increment('monthly_duty_minutes', $diffMins);
                            $officer->update(['last_duty_at' => $lastDuty->copy()->addMinutes($diffMins)]);
                        }
                    }

                    // Mark other previous streams of this officer as ended
                    ActiveStream::where('officer_id', $officer->id)
                        ->where('video_id', '!=', $liveData['video_id'])
                        ->where('status', 'LIVE')
                        ->update([
                            'status' => 'ENDED',
                            'last_synced_at' => now(),
                        ]);
                } else {
                    // Stream is not police patrol (e.g. badside / missing hashtag). Mark any existing active stream as ENDED.
                    ActiveStream::where('officer_id', $officer->id)
                        ->where('status', 'LIVE')
                        ->update([
                            'status' => 'ENDED',
                            'last_synced_at' => now(),
                        ]);
                }
            } else {
                // Officer is not live in this cycle. Mark any existing active stream as ENDED immediately.
                ActiveStream::where('officer_id', $officer->id)
                    ->where('status', 'LIVE')
                    ->update([
                        'status' => 'ENDED',
                        'last_synced_at' => now(),
                    ]);
            }
        }

        // Cleanup old ended streams older than 3 days
        ActiveStream::where('status', 'ENDED')
            ->where('last_synced_at', '<', now()->subDays(3))
            ->delete();

        // Warm up Cinema Hub replays cache only when expired (keeps 1-minute sync fast & lightweight)
        if (! Cache::has('cinema_hub_replays_cache')) {
            try {
                $liveChannelIds = ActiveStream::where('status', 'LIVE')->pluck('channel_id')->toArray();
                $unmatchedOfficers = Officer::roster()
                    ->whereNotIn('channel_id', $liveChannelIds)
                    ->get();

                if ($unmatchedOfficers->isNotEmpty()) {
                    $recentReplays = $scraper->fetchLatestOfficerVideos($unmatchedOfficers, 100);
                    if (!empty($recentReplays)) {
                        Cache::put('cinema_hub_replays_cache', $recentReplays, 900);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Cinema Hub background cache warmup failed: ' . $e->getMessage());
            }
        }
    }
}
