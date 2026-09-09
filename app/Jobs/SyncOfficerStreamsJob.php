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
        $handles = $officers->pluck('handle')->toArray();

        // Get status for all handles concurrently
        $liveStreamsData = $scraper->checkLiveStatusMany($handles);

        foreach ($officers as $officer) {
            $liveData = $liveStreamsData[$officer->handle] ?? null;

            if ($liveData) {
                // Update or create active stream
                ActiveStream::updateOrCreate(
                    [
                        'channel_id' => $officer->channel_id,
                        'video_id' => $liveData['video_id'],
                    ],
                    [
                        'title' => $liveData['title'],
                        'thumbnail_url' => $liveData['thumbnail_url'],
                        'status' => 'LIVE',
                        'last_synced_at' => now(),
                    ]
                );

                // Mark other streams of this officer as ended
                ActiveStream::where('channel_id', $officer->channel_id)
                    ->where('video_id', '!=', $liveData['video_id'])
                    ->update([
                        'status' => 'ENDED',
                        'last_synced_at' => now(),
                    ]);
            } else {
                // Officer is not live, update existing streams to ended
                ActiveStream::where('channel_id', $officer->channel_id)
                    ->where('status', '!=', 'ENDED')
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
