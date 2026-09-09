<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Channel;
use App\Models\ActiveStream;
use App\Services\YouTubeScraperService;

class SyncActiveStreamsJob implements ShouldQueue
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
        $channels = Channel::where('is_active', true)->get();
        $handles = $channels->pluck('handle')->toArray();

        // Get status for all handles concurrently
        $liveStreamsData = $scraper->checkLiveStatusMany($handles);

        foreach ($channels as $channel) {
            $liveData = $liveStreamsData[$channel->handle] ?? null;

            if ($liveData) {
                // Update or create the active stream
                ActiveStream::updateOrCreate(
                    [
                        'channel_id' => $channel->channel_id,
                        'video_id' => $liveData['video_id'],
                    ],
                    [
                        'title' => $liveData['title'],
                        'thumbnail_url' => $liveData['thumbnail_url'],
                        'status' => 'LIVE',
                        'last_synced_at' => now(),
                    ]
                );

                // Mark other streams of this channel as ended
                ActiveStream::where('channel_id', $channel->channel_id)
                    ->where('video_id', '!=', $liveData['video_id'])
                    ->update([
                        'status' => 'ENDED',
                        'last_synced_at' => now(),
                    ]);
            } else {
                // Channel is not live, update existing streams for this channel to ended
                ActiveStream::where('channel_id', $channel->channel_id)
                    ->where('status', '!=', 'ENDED')
                    ->update([
                        'status' => 'ENDED',
                        'last_synced_at' => now(),
                    ]);
            }
        }

        // Cleanup old ended streams to keep the table neat (older than 3 days)
        ActiveStream::where('status', 'ENDED')
            ->where('last_synced_at', '<', now()->subDays(3))
            ->delete();
    }
}
