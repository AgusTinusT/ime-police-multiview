<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Officer;

class OfficerManageCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officer:manage 
                            {action : add, list, toggle, delete} 
                            {channel_id? : YouTube Channel ID} 
                            {handle? : YouTube Handle (e.g. @Ncangpitung)} 
                            {streamer_name? : Streamer Name} 
                            {officer_name? : In-Game Character Name} 
                            {callsign? : Unit Callsign (e.g. 1-ADAM-12)} 
                            {department? : LSPD, BCSO, SASP, SWAT, AIR_SUPPORT, TRAFFIC, K9} 
                            {rank? : Rank (e.g. Officer, Sergeant, Lieutenant, Chief)} 
                            {badge_number? : Badge Number (e.g. #402)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage IME Roleplay police officers and monitored live channels';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listOfficers();
                break;

            case 'add':
                $this->addOfficer();
                break;

            case 'toggle':
                $this->toggleOfficer();
                break;

            case 'delete':
                $this->deleteOfficer();
                break;

            default:
                $this->error("Unknown action: {$action}. Available: list, add, toggle, delete");
                return 1;
        }

        return 0;
    }

    protected function listOfficers()
    {
        $officers = Officer::all(['id', 'channel_id', 'handle', 'officer_name', 'callsign', 'department', 'rank', 'badge_number', 'is_active']);

        if ($officers->isEmpty()) {
            $this->info('No officers found in database.');
            return;
        }

        $headers = ['ID', 'Channel ID', 'Handle', 'Officer Name', 'Callsign', 'Dept', 'Rank', 'Badge', 'Status'];
        $rows = $officers->map(function ($o) {
            return [
                $o->id,
                $o->channel_id,
                $o->handle,
                $o->officer_name,
                $o->callsign,
                $o->department,
                $o->rank,
                $o->badge_number,
                $o->is_active ? 'ACTIVE' : 'DISABLED',
            ];
        });

        $this->table($headers, $rows);
    }

    protected function addOfficer()
    {
        $handle = $this->argument('handle') ?? $this->ask('YouTube Handle (contoh: @WindahBasudara)');
        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }

        $streamer = $this->argument('streamer_name') ?? $this->ask('Streamer Name (Nama Streamer)');
        $officerName = $this->argument('officer_name') ?? $this->ask('Officer RP Character Name (Nama Karakter Polisi, contoh: Ofc. John Doe)');
        $callsign = $this->argument('callsign') ?? $this->ask('Unit Callsign (contoh: 1-ADAM-01, 2-BAKER-05, 3-VICTOR-03, SWAT-01)');
        $department = $this->argument('department') ?? $this->choice('Department', ['LSPD', 'BCSO', 'SASP', 'SWAT', 'AIR_SUPPORT', 'TRAFFIC', 'K9', 'DISPATCH'], 0);
        $rank = $this->argument('rank') ?? $this->ask('Rank (contoh: Officer, Sergeant, Lieutenant, Deputy, Trooper)', 'Officer');
        $badge = $this->argument('badge_number') ?? $this->ask('Badge Number (contoh: #101)', '#000');
        
        $channelId = $this->argument('channel_id') ?? $this->ask('YouTube Channel ID (Opsional, tekan Enter jika tidak tahu)', 'UC_' . \Illuminate\Support\Str::slug(str_replace('@', '', $handle), '_'));

        $officer = Officer::updateOrCreate(
            ['handle' => $handle],
            [
                'channel_id' => $channelId,
                'streamer_name' => $streamer,
                'officer_name' => $officerName,
                'callsign' => $callsign,
                'department' => $department,
                'rank' => $rank,
                'badge_number' => $badge,
                'is_active' => true,
            ]
        );

        $this->info("✓ Officer {$officer->officer_name} ({$officer->callsign}) berhasil ditambahkan/diperbarui!");
        
        $this->info("Memulai pengecekan status live YouTube...");
        $job = new \App\Jobs\SyncOfficerStreamsJob();
        app()->call([$job, 'handle']);
        $this->info("✓ Sinkronisasi selesai!");
    }

    protected function toggleOfficer()
    {
        $channelId = $this->argument('channel_id') ?? $this->ask('Enter Channel ID to toggle');
        $officer = Officer::where('channel_id', $channelId)->first();

        if (!$officer) {
            $this->error("Officer with Channel ID {$channelId} not found.");
            return;
        }

        $officer->is_active = !$officer->is_active;
        $officer->save();

        $status = $officer->is_active ? 'ACTIVE' : 'DISABLED';
        $this->info("Officer {$officer->officer_name} is now {$status}.");
    }

    protected function deleteOfficer()
    {
        $channelId = $this->argument('channel_id') ?? $this->ask('Enter Channel ID to delete');
        $officer = Officer::where('channel_id', $channelId)->first();

        if (!$officer) {
            $this->error("Officer with Channel ID {$channelId} not found.");
            return;
        }

        if ($this->confirm("Are you sure you want to delete {$officer->officer_name} ({$officer->callsign})?")) {
            $officer->delete();
            $this->info("Officer deleted successfully.");
        }
    }
}
