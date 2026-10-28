<?php

namespace App\Console\Commands;

use App\Models\BioDailyStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FlushBioViews extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'biolinks:flush-views';

    /**
     * The console command description.
     */
    protected $description = 'Flush cached public bio-link page views to bio_daily_stats table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dates = [
            now()->subDay()->toDateString(),
            now()->toDateString(),
        ];

        $totalFlushedCount = 0;
        $totalViews = 0;

        foreach ($dates as $statDate) {
            \App\Models\OfficerBioProfile::select('id')->chunk(200, function ($profiles) use ($statDate, &$totalFlushedCount, &$totalViews) {
                foreach ($profiles as $profile) {
                    $cacheKey = "bio_views:{$profile->id}:{$statDate}";
                    $views = (int) Cache::get($cacheKey, 0);

                    if ($views > 0) {
                        Cache::decrement($cacheKey, $views);

                        $stat = BioDailyStat::firstOrCreate(
                            [
                                'officer_bio_profile_id' => $profile->id,
                                'officer_bio_link_id' => null,
                                'stat_date' => $statDate,
                            ],
                            [
                                'clicks' => 0,
                                'views' => 0,
                                'unique_visitors' => 0,
                            ]
                        );

                        $stat->increment('views', $views);
                        $totalFlushedCount++;
                        $totalViews += $views;
                    }
                }
            });

            // Also clean up any legacy pending array if present
            Cache::forget("bio_views_pending:{$statDate}");
        }

        $this->info("Successfully flushed {$totalViews} page views across {$totalFlushedCount} profile-dates to database.");
        return Command::SUCCESS;
    }
}
