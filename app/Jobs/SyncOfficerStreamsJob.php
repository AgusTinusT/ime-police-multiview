<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Officer;
use App\Models\ActiveStream;
use App\Services\YouTubeScraperService;

class SyncOfficerStreamsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(YouTubeScraperService $scraper): void
    {
        $officers = Officer::where('is_active', true)->get();
        if ($officers->isEmpty()) {
            return;
        }

        // Run hybrid multi-phase fast sync
        $liveMap = $scraper->syncAllActiveOfficers($officers);

        foreach ($officers as $officer) {
            $liveData = $liveMap[$officer->id] ?? null;

            if ($liveData && !empty($liveData['video_id'])) {
                // If officer missing channel_id, auto-save detected YouTube channel ID
                if ((empty($officer->channel_id) || str_starts_with($officer->channel_id, 'custom-')) && !empty($liveData['channel_id'])) {
                    $officer->update(['channel_id' => $liveData['channel_id']]);
                }

                $channelId = $officer->channel_id ?: ($liveData['channel_id'] ?: 'ch-' . $officer->id);

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
                        'description' => $liveData['description'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );

                // Mark other previous streams of this officer as ended
                ActiveStream::where('channel_id', $channelId)
                    ->where('video_id', '!=', $liveData['video_id'])
                    ->where('status', 'LIVE')
                    ->update([
                        'status' => 'ENDED',
                        'last_synced_at' => now(),
                    ]);
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
    }
}
