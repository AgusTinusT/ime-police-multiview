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
        $this->info('Starting subscriber count sync for active officers...');

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
                    } elseif (!empty($officer->channel_id)) {
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

                    if ($extracted['count'] !== null) {
                        $officer->update([
                            'subscriber_count' => $extracted['count'],
                            'subscriber_count_text' => $extracted['text'],
                        ]);
                        $totalUpdated++;
                        $this->line("Updated {$officer->officer_name} ({$officer->handle}): {$extracted['text']} ({$extracted['count']})");
                    }
                }
            }
            usleep(500000); // 500ms delay between chunks to prevent aggressive rate-limiting
        }

        $this->info("Successfully updated subscriber counts for {$totalUpdated} officers.");
        return 0;
    }
}
