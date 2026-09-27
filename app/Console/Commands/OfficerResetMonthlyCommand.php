<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Officer;
use Illuminate\Support\Facades\Log;

class OfficerResetMonthlyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officer:reset-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly accumulated duty minutes for all officers at the start of a new month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Officer::count();
        Officer::query()->update([
            'monthly_duty_minutes' => 0,
            'last_duty_at' => null,
        ]);

        $monthName = strtoupper(now()->locale('id')->translatedFormat('F Y'));
        $msg = "Monthly duty hours reset successfully for {$count} officers for cycle {$monthName}.";
        
        Log::info($msg);
        $this->info("✅ " . $msg);

        return Command::SUCCESS;
    }
}
