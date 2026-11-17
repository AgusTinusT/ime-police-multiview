<?php

namespace App\Console\Commands;

use App\Models\Officer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportOfficerDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officers:report-duplicates';

    /**
     * The console command description.
     */
    protected $description = 'Report duplicate callsigns and badge numbers among active officers (T-15)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Scanning active officers for duplicate callsigns and badge numbers...\n");

        // 1. Duplicate Callsigns (case-insensitive normalized)
        $duplicateCallsigns = Officer::where('is_active', true)
            ->whereNotNull('callsign')
            ->where('callsign', '!=', '')
            ->selectRaw('UPPER(TRIM(callsign)) as norm_callsign, count(*) as count')
            ->groupBy('norm_callsign')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateCallsigns->isEmpty()) {
            $this->info("✔ No duplicate callsigns found among active officers.");
        } else {
            $this->warn("⚠ Found {$duplicateCallsigns->count()} duplicate callsign(s):");
            $tableData = [];

            foreach ($duplicateCallsigns as $item) {
                $officers = Officer::where('is_active', true)
                    ->whereRaw('UPPER(TRIM(callsign)) = ?', [$item->norm_callsign])
                    ->get(['id', 'officer_name', 'callsign', 'department', 'user_id']);

                foreach ($officers as $officer) {
                    $tableData[] = [
                        'Normalized' => $item->norm_callsign,
                        'Officer ID' => $officer->id,
                        'Officer Name' => $officer->officer_name,
                        'Raw Callsign' => $officer->callsign,
                        'Dept' => $officer->department,
                        'Claimed' => $officer->user_id ? "User #{$officer->user_id}" : 'Unclaimed',
                    ];
                }
            }
            $this->table(['Normalized', 'Officer ID', 'Officer Name', 'Raw Callsign', 'Dept', 'Claimed'], $tableData);
        }

        $this->newLine();

        // 2. Duplicate Badge Numbers (excluding null or empty)
        $duplicateBadges = Officer::where('is_active', true)
            ->whereNotNull('badge_number')
            ->where('badge_number', '!=', '')
            ->selectRaw('UPPER(TRIM(badge_number)) as norm_badge, count(*) as count')
            ->groupBy('norm_badge')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateBadges->isEmpty()) {
            $this->info("✔ No duplicate badge numbers found among active officers.");
        } else {
            $this->warn("⚠ Found {$duplicateBadges->count()} duplicate badge number(s):");
            $badgeData = [];

            foreach ($duplicateBadges as $item) {
                $officers = Officer::where('is_active', true)
                    ->whereRaw('UPPER(TRIM(badge_number)) = ?', [$item->norm_badge])
                    ->get(['id', 'officer_name', 'badge_number', 'department', 'user_id']);

                foreach ($officers as $officer) {
                    $badgeData[] = [
                        'Normalized' => $item->norm_badge,
                        'Officer ID' => $officer->id,
                        'Officer Name' => $officer->officer_name,
                        'Raw Badge' => $officer->badge_number,
                        'Dept' => $officer->department,
                        'Claimed' => $officer->user_id ? "User #{$officer->user_id}" : 'Unclaimed',
                    ];
                }
            }
            $this->table(['Normalized', 'Officer ID', 'Officer Name', 'Raw Badge', 'Dept', 'Claimed'], $badgeData);
        }

        return Command::SUCCESS;
    }
}
