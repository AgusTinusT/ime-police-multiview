<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ActiveStream;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ActiveStreamController extends Controller
{
    /**
     * Display the public API list of active streams.
     */
    public function index(Request $request)
    {
        $this->syncStreamsIfNeeded();

        $limit = (int) $request->query('limit', 12);
        $host = $request->getHost();

        // Create cache key based on parameters
        $cacheKey = "active_streams:all:lim_" . $limit . ":host_" . $host;

        $data = Cache::remember($cacheKey, 60, function () use ($limit, $host) {
            $streams = ActiveStream::with('channel')
                ->where('status', 'LIVE')
                ->limit($limit)
                ->get();

            $formatted = $streams->map(function ($stream) use ($host) {
                return [
                    'video_id' => $stream->video_id,
                    'channel_name' => $stream->channel->name,
                    'title' => $stream->title,
                    'thumbnail' => $stream->thumbnail_url,
                    'status' => $stream->status,
                    'live_chat_url' => "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}",
                ];
            });

            return [
                'status' => 'success',
                'data' => $formatted,
                'meta' => [
                    'total_active' => $formatted->count(),
                    'cached_until' => now()->addSeconds(60)->toIso8601String(),
                ]
            ];
        });

        return response()->json($data);
    }

    /**
     * Render the Inertia Dashboard.
     */
    public function dashboard(Request $request)
    {
        $this->syncStreamsIfNeeded();

        $host = $request->getHost();

        // Get Online Streams
        $activeStreams = ActiveStream::with('channel')
            ->where('status', 'LIVE')
            ->get()
            ->map(function ($stream) use ($host) {
                return [
                    'video_id' => $stream->video_id,
                    'channel_name' => $stream->channel->name,
                    'title' => $stream->title,
                    'thumbnail' => $stream->thumbnail_url,
                    'status' => $stream->status,
                    'live_chat_url' => "https://www.youtube.com/live_chat?v={$stream->video_id}&embed_domain={$host}",
                ];
            });

        // Get Offline Channels
        $liveChannelIds = ActiveStream::where('status', 'LIVE')->pluck('channel_id')->toArray();

        $offlineChannels = Channel::where('is_active', true)
            ->whereNotIn('channel_id', $liveChannelIds)
            ->get()
            ->map(function ($channel) {
                return [
                    'channel_id' => $channel->channel_id,
                    'name' => $channel->name,
                    'handle' => $channel->handle,
                ];
            });

        return Inertia::render('Dashboard', [
            'initialStreams' => $activeStreams,
            'initialOfflineChannels' => $offlineChannels,
        ]);
    }

    /**
     * Sync active stream statuses if the sync lock has expired.
     */
    protected function syncStreamsIfNeeded(): void
    {
        $lockKey = 'streams_sync_lock';

        if (!Cache::has($lockKey)) {
            // Put a lock to prevent concurrent sync executions (cooldown of 2 minutes)
            Cache::put($lockKey, true, 120);

            try {
                $job = new \App\Jobs\SyncActiveStreamsJob();
                app()->call([$job, 'handle']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Auto-sync failed: ' . $e->getMessage());
                // Forget lock on failure so it can retry
                Cache::forget($lockKey);
            }
        }
    }
}
