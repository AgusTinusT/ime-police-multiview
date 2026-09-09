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
        $channelId = $this->argument('channel_id') ?? $this->ask('YouTube Channel ID');
        $handle = $this->argument('handle') ?? $this->ask('YouTube Handle (e.g. @example)');
        $streamer = $this->argument('streamer_name') ?? $this->ask('Streamer Name');
        $officerName = $this->argument('officer_name') ?? $this->ask('Officer RP Character Name');
        $callsign = $this->argument('callsign') ?? $this->ask('Unit Callsign (e.g. 1-ADAM-12)');
        $department = $this->argument('department') ?? $this->choice('Department', ['LSPD', 'BCSO', 'SASP', 'SWAT', 'AIR_SUPPORT', 'TRAFFIC', 'K9', 'DISPATCH'], 0);
        $rank = $this->argument('rank') ?? $this->ask('Rank (e.g. Officer II, Sergeant)', 'Officer');
        $badge = $this->argument('badge_number') ?? $this->ask('Badge Number (e.g. #108)', '#000');

        $officer = Officer::updateOrCreate(
            ['channel_id' => $channelId],
            [
                'handle' => $handle,
                'streamer_name' => $streamer,
                'officer_name' => $officerName,
                'callsign' => $callsign,
                'department' => $department,
                'rank' => $rank,
                'badge_number' => $badge,
                'is_active' => true,
            ]
        );

        $this->info("Officer {$officer->officer_name} ({$officer->callsign}) successfully added / updated!");
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
