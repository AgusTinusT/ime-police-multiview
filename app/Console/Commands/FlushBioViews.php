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
            $pendingKey = "bio_views_pending:{$statDate}";
            $profileIds = Cache::get($pendingKey, []);

            if (empty($profileIds)) {
                continue;
            }

            foreach ($profileIds as $profileId) {
                $cacheKey = "bio_views:{$profileId}:{$statDate}";
                $views = (int) Cache::pull($cacheKey, 0);

                if ($views > 0) {
                    $stat = BioDailyStat::firstOrCreate(
                        [
                            'officer_bio_profile_id' => $profileId,
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

            // Reset pending list for this date
            Cache::forget($pendingKey);
        }

        $this->info("Successfully flushed {$totalViews} page views across {$totalFlushedCount} profile-dates to database.");
        return Command::SUCCESS;
    }
}
