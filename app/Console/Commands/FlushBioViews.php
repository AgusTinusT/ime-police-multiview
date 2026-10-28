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
        $today = now()->toDateString();
        $pendingKey = "bio_views_pending:{$today}";
        $profileIds = Cache::get($pendingKey, []);

        if (empty($profileIds)) {
            $this->info("No pending bio page views to flush for date {$today}.");
            return Command::SUCCESS;
        }

        $flushedCount = 0;
        $totalViews = 0;

        foreach ($profileIds as $profileId) {
            $cacheKey = "bio_views:{$profileId}:{$today}";
            $views = (int) Cache::pull($cacheKey, 0);

            if ($views > 0) {
                $stat = BioDailyStat::firstOrCreate(
                    [
                        'officer_bio_profile_id' => $profileId,
                        'officer_bio_link_id' => null,
                        'stat_date' => $today,
                    ],
                    [
                        'clicks' => 0,
                        'views' => 0,
                        'unique_visitors' => 0,
                    ]
                );

                $stat->increment('views', $views);
                $flushedCount++;
                $totalViews += $views;
            }
        }

        // Reset pending list
        Cache::forget($pendingKey);

        $this->info("Successfully flushed {$totalViews} page views across {$flushedCount} profiles to database.");
        return Command::SUCCESS;
    }
}
