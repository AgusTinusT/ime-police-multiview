<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncOfficerStreamsJob;
use App\Models\ActiveStream;
use App\Models\Officer;

class OfficerSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officer:sync';

    /**
     * The console command description.
     */
    protected $description = 'Trigger multi-tier live stream sync for all active IME Police officers with diagnostic output';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('======================================================');
        $this->info('🚔  IME POLICE MULTIVIEW - LIVE STREAM SYNCHRONIZER  ');
        $this->info('======================================================');

        $activeOfficers = Officer::where('is_active', true)->count();
        $this->comment("Scanning live status for {$activeOfficers} active officers...");

        $start = microtime(true);
        $job = new SyncOfficerStreamsJob();
        app()->call([$job, 'handle']);
        $elapsed = round(microtime(true) - $start, 2);

        $liveStreams = ActiveStream::with('officer')
            ->where('status', 'LIVE')
            ->orderBy('viewers_count', 'desc')
            ->get();

        $rows = [];
        foreach ($liveStreams as $stream) {
            $officer = $stream->officer;
            $callsign = $officer ? ($officer->callsign ?: 'N/A') : 'N/A';
            $officerName = $officer ? $officer->officer_name : 'Unknown';
            $handle = $officer ? $officer->handle : 'N/A';
            $title = mb_strimwidth($stream->title, 0, 45, '...');

            $rows[] = [
                $callsign,
                $officerName,
                $handle,
                $stream->video_id,
                $stream->viewers_count,
                $title,
                $stream->last_synced_at?->diffForHumans() ?? 'Just now',
            ];
        }

        if (!empty($rows)) {
            $this->table(
                ['Callsign', 'Officer Name', 'Handle', 'Video ID', 'Viewers', 'Stream Title', 'Last Sync'],
                $rows
            );
        } else {
            $this->warn('No active live streams currently detected.');
        }

        $this->info("------------------------------------------------------");
        $this->info("✅ Sync completed in {$elapsed}s | Total 10-8 Live Units: " . count($rows));
        $this->info("======================================================\n");

        return 0;
    }
}

