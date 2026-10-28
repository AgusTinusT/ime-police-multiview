<?php

namespace App\Console\Commands;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Console\Command;

class AuditOfficerBindings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officers:audit-bindings';

    /**
     * The console command description.
     */
    protected $description = 'Audit officer-to-user 1-to-1 bindings and report legacy role=officer discrepancies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== SASP Tactical Officer Binding Audit ===');
        $this->newLine();

        // 1. Bound Officers
        $boundOfficers = Officer::with('user')->whereNotNull('user_id')->get();
        $this->info("1. Active Bound Officers ({$boundOfficers->count()} total):");
        if ($boundOfficers->isEmpty()) {
            $this->line('   (No officers currently bound to user accounts)');
        } else {
            $rows = $boundOfficers->map(fn($o) => [
                'Officer ID' => $o->id,
                'Callsign' => $o->callsign,
                'Officer Name' => $o->officer_name,
                'Streamer' => $o->streamer_name,
                'Bound User' => "{$o->user?->name} ({$o->user?->email}) [ID: {$o->user_id}]",
            ]);
            $this->table(['Officer ID', 'Callsign', 'Officer Name', 'Streamer', 'Bound User'], $rows);
        }
        $this->newLine();

        // 2. Unclaimed Officers
        $unclaimedCount = Officer::whereNull('user_id')->count();
        $this->info("2. Unclaimed Officers ({$unclaimedCount} total in roster)");
        $this->newLine();

        // 3. Legacy Discrepancies: Users with role='officer' who have NO bound officer record
        $unboundLegacyUsers = User::where('role', 'officer')
            ->whereDoesntHave('officer')
            ->get(['id', 'name', 'email', 'created_at']);

        $this->warn("3. Legacy Users with role='officer' without 1:1 Officer Binding ({$unboundLegacyUsers->count()} found):");
        if ($unboundLegacyUsers->isEmpty()) {
            $this->info('   ✓ All role=officer users are cleanly bound or migrated!');
        } else {
            $rows = $unboundLegacyUsers->map(fn($u) => [
                'User ID' => $u->id,
                'Name' => $u->name,
                'Email' => $u->email,
                'Registered At' => $u->created_at?->format('Y-m-d H:i') ?? 'N/A',
            ]);
            $this->table(['User ID', 'Name', 'Email', 'Registered At'], $rows);
            $this->comment('   Recommendation: Use the Admin Panel or tinker to bind these users to their respective Officer records.');
        }

        return Command::SUCCESS;
    }
}
