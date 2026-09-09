<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncOfficerStreamsJob;
use App\Models\ActiveStream;

class OfficerSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officer:sync';

    /**
     * The console command description.
     */
    protected $description = 'Manually trigger live stream status sync for all active IME Police officers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting officer live stream sync...');

        $job = new SyncOfficerStreamsJob();
        app()->call([$job, 'handle']);

        $liveCount = ActiveStream::where('status', 'LIVE')->count();

        $this->info("Officer stream sync completed successfully! Total 10-8 Live Units: {$liveCount}");
        return 0;
    }
}
