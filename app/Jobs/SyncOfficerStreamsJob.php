<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Officer;
use App\Models\ActiveStream;
use App\Services\YouTubeScraperService;

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
    public function handle(YouTubeScraperService $scraper): void
    {
        // Purge any active stream records belonging to disabled officers
        ActiveStream::whereHas('officer', fn($q) => $q->where('is_active', false))->delete();

        $officers = Officer::where('is_active', true)->get();
        if ($officers->isEmpty()) {
            return;
        }

        // Run hybrid multi-phase fast sync
        $liveMap = $scraper->syncAllActiveOfficers($officers);

        foreach ($officers as $officer) {
            $liveData = $liveMap[$officer->id] ?? null;

            if ($liveData && !empty($liveData['video_id'])) {
                // If officer missing channel_id, auto-save detected YouTube channel ID ONLY for direct handle/RSS detection
                if ((empty($officer->channel_id) || str_starts_with($officer->channel_id, 'custom-')) && !empty($liveData['channel_id']) && in_array($liveData['detection_method'] ?? '', ['DIRECT_HANDLE', 'RSS_FEED'])) {
                    $officer->update(['channel_id' => $liveData['channel_id']]);
                }

                $channelId = $officer->channel_id ?: ($liveData['channel_id'] ?: 'ch-' . $officer->id);

                // Clean up any other active stream record having this exact video_id under a different channel_id
                ActiveStream::where('video_id', $liveData['video_id'])
                    ->where('channel_id', '!=', $channelId)
                    ->delete();

                $streamDescription = $liveData['description'] ?? null;
                if (!empty($liveData['video_id']) && (empty($streamDescription) || strlen($streamDescription) < 150)) {
                    $details = $scraper->scrapeVideoDetails($liveData['video_id']);
                    if ($details && !empty($details['description'])) {
                        $streamDescription = $details['description'];
                    }
                }

                // Check if stream is valid Police Duty 10-8 vs Badside/Civilian
                $isPolicePatrol = $scraper->isPolicePatrolStream($liveData['title'] ?? '', $streamDescription ?? '', $officer);

                if ($isPolicePatrol) {
                    // Update or create active stream
                    ActiveStream::updateOrCreate(
                        [
                            'channel_id' => $channelId,
                            'video_id' => $liveData['video_id'],
                        ],
                        [
                            'title' => $liveData['title'],
                            'thumbnail_url' => $liveData['thumbnail_url'],
                            'status' => 'LIVE',
                            'viewers_count' => $liveData['viewers_count'] ?? 0,
                            'description' => $streamDescription,
                            'last_synced_at' => now(),
                        ]
                    );

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
                    ActiveStream::where('channel_id', $channelId)
                        ->where('video_id', '!=', $liveData['video_id'])
                        ->where('status', 'LIVE')
                        ->update([
                            'status' => 'ENDED',
                            'last_synced_at' => now(),
                        ]);
                } else {
                    // Stream is not police patrol (e.g. badside / missing hashtag). Mark any existing active stream as ENDED.
                    ActiveStream::where('channel_id', $channelId)
                        ->where('status', 'LIVE')
                        ->update([
                            'status' => 'ENDED',
                            'last_synced_at' => now(),
                        ]);
                }
            } else {
                // Officer is not live in this cycle. Mark any existing active stream as ENDED immediately.
                $channelId = $officer->channel_id ?: 'ch-' . $officer->id;
                ActiveStream::where('channel_id', $channelId)
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

        // Warm up Cinema Hub replays cache in the background
        try {
            $liveChannelIds = ActiveStream::where('status', 'LIVE')->pluck('channel_id')->toArray();
            $unmatchedOfficers = Officer::where('is_active', true)
                ->whereNotIn('channel_id', $liveChannelIds)
                ->get();

            if ($unmatchedOfficers->isNotEmpty()) {
                $recentReplays = $scraper->fetchLatestOfficerVideos($unmatchedOfficers, 100);
                if (!empty($recentReplays)) {
                    Cache::put('cinema_hub_replays_cache', $recentReplays, 1800);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Cinema Hub background cache warmup failed: ' . $e->getMessage());
        }
    }
}
