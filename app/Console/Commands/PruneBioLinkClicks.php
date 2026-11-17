<?php

namespace App\Console\Commands;

use App\Models\BioLinkClick;
use Illuminate\Console\Command;

class PruneBioLinkClicks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'biolinks:prune-clicks {--days=90 : Number of days of click history to retain}';

    /**
     * The console command description.
     */
    protected $description = 'Prune old raw telemetry clicks from bio_link_clicks table older than retention period (default 90 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 7) {
            $days = 90;
        }

        $cutoff = now()->subDays($days);
        $deleted = BioLinkClick::where('created_at', '<', $cutoff)->delete();

        $this->info("✓ Successfully pruned {$deleted} raw bio link clicks older than {$days} days (before {$cutoff->toDateTimeString()}).");
        return Command::SUCCESS;
    }
}
