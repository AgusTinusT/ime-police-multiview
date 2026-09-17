<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Officer;
use App\Services\YouTubeScraperService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncOfficerSubscribersCommand extends Command
{
    protected $signature = 'officer:sync-subscribers';
    protected $description = 'Fetch and update subscriber counts for all active officers from YouTube';

    public function handle(YouTubeScraperService $scraper)
    {
        $this->info('Starting subscriber count & channel metadata sync for active officers...');

        $officers = Officer::where('is_active', true)->get();
        if ($officers->isEmpty()) {
            $this->warn('No active officers found.');
            return 0;
        }

        $chunks = $officers->chunk(8);
        $totalUpdated = 0;

        foreach ($chunks as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk) {
                foreach ($chunk as $officer) {
                    $handle = trim($officer->handle ?? '');
                    if (!empty($handle)) {
                        $cleanHandle = str_starts_with($handle, '@') ? $handle : '@' . $handle;
                        $url = "https://www.youtube.com/{$cleanHandle}";
                    } elseif (!empty($officer->channel_id) && !str_starts_with($officer->channel_id, 'UC_')) {
                        $url = "https://www.youtube.com/channel/{$officer->channel_id}";
                    } else {
                        continue;
                    }
                    $pool->as($officer->id)->withHeaders(YouTubeScraperService::getBrowserHeaders())->timeout(10)->get($url);
                }
            });

            foreach ($chunk as $officer) {
                $res = $responses[$officer->id] ?? null;
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful()) {
                    $html = $res->body();
                    $extracted = YouTubeScraperService::extractSubscriberCountFromHtml($html);

                    $updateData = [];

                    if ($extracted['count'] !== null) {
                        $updateData['subscriber_count'] = $extracted['count'];
                        $updateData['subscriber_count_text'] = $extracted['text'];
                    }

                    // Extract real YouTube channel_id from HTML
                    $realChannelId = null;
                    if (preg_match('/"externalId"\s*:\s*"(UC[a-zA-Z0-9_-]{22})"/i', $html, $m)) {
                        $realChannelId = $m[1];
                    } elseif (preg_match('/"channelId"\s*:\s*"(UC[a-zA-Z0-9_-]{22})"/i', $html, $m)) {
                        $realChannelId = $m[1];
                    } elseif (preg_match('/itemprop="channelId"\s+content="(UC[a-zA-Z0-9_-]{22})"/i', $html, $m)) {
                        $realChannelId = $m[1];
                    } elseif (preg_match('/<link rel="alternate" type="application\/rss\+xml" title="RSS" href="https:\/\/www\.youtube\.com\/feeds\/videos\.xml\?channel_id=(UC[a-zA-Z0-9_-]{22})"/i', $html, $m)) {
                        $realChannelId = $m[1];
                    }

                    if ($realChannelId && (str_starts_with($officer->channel_id, 'UC_') || $officer->channel_id !== $realChannelId)) {
                        $oldChannelId = $officer->channel_id;
                        $updateData['channel_id'] = $realChannelId;
                        
                        if (!empty($oldChannelId)) {
                            \Illuminate\Support\Facades\DB::table('active_streams')->where('channel_id', $oldChannelId)->update(['channel_id' => $realChannelId]);
                        }
                    }

                    // Extract avatar URL if currently dicebear placeholder or empty
                    if (str_contains($officer->avatar_url ?? '', 'dicebear') || empty($officer->avatar_url)) {
                        if (preg_match('/"avatar"\s*:\s*\{\s*"thumbnails"\s*:\s*\[\s*\{\s*"url"\s*:\s*"([^"]+)"/i', $html, $am)) {
                            $updateData['avatar_url'] = str_replace('\u0026', '&', $am[1]);
                        } elseif (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $am)) {
                            $updateData['avatar_url'] = $am[1];
                        }
                    }

                    if (!empty($updateData)) {
                        $officer->update($updateData);
                        $totalUpdated++;
                        $this->line("Updated {$officer->officer_name} ({$officer->handle}): " . json_encode($updateData));
                    }
                }
            }
            usleep(500000); // 500ms delay between chunks to prevent aggressive rate-limiting
        }

        // Flush Cinema Hub Cache so VODs refresh immediately
        \Illuminate\Support\Facades\Cache::forget('cinema_hub_replays_cache');

        $this->info("Successfully updated subscriber counts and channel metadata for {$totalUpdated} officers.");
        return 0;
    }
}
