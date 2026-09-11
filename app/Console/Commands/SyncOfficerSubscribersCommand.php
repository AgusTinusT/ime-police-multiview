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

        $officers = Officer::where('is_active', true)->whereNotNull('handle')->get();
        if ($officers->isEmpty()) {
            $this->warn('No active officers with handles found.');
            return 0;
        }

        $chunks = $officers->chunk(10);
        $totalUpdated = 0;

        foreach ($chunks as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk) {
                foreach ($chunk as $officer) {
                    $handle = str_starts_with($officer->handle, '@') ? $officer->handle : '@' . $officer->handle;
                    $url = "https://www.youtube.com/{$handle}";
                    $pool->as($officer->id)->withHeaders(YouTubeScraperService::getBrowserHeaders())->timeout(7)->get($url);
                }
            });

            foreach ($chunk as $officer) {
                $res = $responses[$officer->id] ?? null;
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful()) {
                    $html = $res->body();
                    $subscriberText = null;

                    if (preg_match('/"subscriberCountText":\{"accessibility":\{"accessibilityData":\{"label":"([^"]+)"\}\}/', $html, $matches)) {
                        $subscriberText = $matches[1];
                    } elseif (preg_match('/"subscriberCountText":\{"simpleText":"([^"]+)"\}/', $html, $matches)) {
                        $subscriberText = $matches[1];
                    }

                    if ($subscriberText) {
                        $count = YouTubeScraperService::parseSubscriberCount($subscriberText);
                        if ($count !== null) {
                            $officer->update([
                                'subscriber_count' => $count,
                                'subscriber_count_text' => $subscriberText,
                            ]);
                            $totalUpdated++;
                            $this->line("Updated {$officer->officer_name} ({$officer->handle}): {$subscriberText} ({$count})");
                        }
                    }
                }
            }
            sleep(1);
        }

        $this->info("Successfully updated subscriber counts for {$totalUpdated} officers.");
        return 0;
    }
}
