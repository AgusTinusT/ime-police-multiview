<?php

namespace App\Console\Commands;

use App\Services\YouTubeWebSubService;
use Illuminate\Console\Command;

class SyncYouTubeWebSubCommand extends Command
{
    protected $signature = 'websub:sync';

    protected $description = 'Subscribe eligible officer YouTube channels to WebSub and renew expiring leases';

    public function handle(YouTubeWebSubService $webSub): int
    {
        $result = $webSub->syncDueSubscriptions();

        $this->info(sprintf(
            'WebSub sync selesai: %d diproses (%d diminta, %d gagal).',
            $result['total'],
            $result['requested'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
