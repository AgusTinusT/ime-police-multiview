<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Officer;
use App\Jobs\SyncOfficerStreamsJob;
use Illuminate\Support\Str;

class OfficerImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officer:import 
                            {source? : File path (JSON/CSV/TXT) or raw JSON string}
                            {--sync : Automatically trigger live stream sync after import}';

    /**
     * The console command description.
     */
    protected $description = 'Import multiple police officers and YouTube channels in bulk (JSON, CSV, or Pipe Delimited)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = $this->argument('source');

        if (!$source) {
            $this->info("Masukkan data officer (format JSON Array atau Baris per Baris: Handle | Streamer | Karakter | Callsign | Dept):");
            $this->comment("Contoh Baris:");
            $this->comment("@WindahBasudara | Windah Basudara | Sgt. Brando | 1-LINCOLN-10 | LSPD | Sergeant | #110");
            $this->comment("@MiawAug | MiawAug | Deputy Reggie | 2-KING-07 | BCSO | Deputy | #205");
            $this->line("");

            $source = $this->ask("Ketik path file JSON/TXT atau tempelkan JSON string");
        }

        if (empty($source)) {
            $this->error("Sumber data kosong.");
            return 1;
        }

        $items = [];

        // 1. Check if source is a file path
        if (file_exists($source)) {
            $content = file_get_contents($source);
            $items = $this->parseContent($content);
        } else {
            // 2. Parse direct string
            $items = $this->parseContent($source);
        }

        if (empty($items)) {
            $this->error("Tidak ada data valid yang dapat diproses.");
            return 1;
        }

        $this->info("Ditemukan " . count($items) . " data officer untuk diimpor...");

        $successCount = 0;
        foreach ($items as $item) {
            $handle = trim($item['handle'] ?? '');
            if (empty($handle)) continue;

            if (!str_starts_with($handle, '@')) {
                $handle = '@' . $handle;
            }

            $channelId = $item['channel_id'] ?? ('UC_' . Str::slug(str_replace('@', '', $handle), '_') . '_' . substr(md5($handle), 0, 6));
            $streamerName = $item['streamer_name'] ?? str_replace('@', '', $handle);
            $officerName = $item['officer_name'] ?? ('Ofc. ' . $streamerName);
            $callsign = $item['callsign'] ?? '1-ADAM-00';
            $department = strtoupper($item['department'] ?? 'LSPD');
            $rank = $item['rank'] ?? 'Officer';
            $badgeNumber = $item['badge_number'] ?? '#000';
            $patrolZone = $item['patrol_zone'] ?? 'Mission Row / Downtown';

            Officer::updateOrCreate(
                ['handle' => $handle],
                [
                    'channel_id' => $channelId,
                    'streamer_name' => $streamerName,
                    'officer_name' => $officerName,
                    'callsign' => $callsign,
                    'department' => $department,
                    'rank' => $rank,
                    'badge_number' => $badgeNumber,
                    'patrol_zone' => $patrolZone,
                    'is_active' => true,
                ]
            );

            $this->line(" <info>✓</info> Berhasil menyimpan: <comment>{$officerName}</comment> ({$handle} - {$callsign} [{$department}])");
            $successCount++;
        }

        $this->info("\n=== Total {$successCount} officer berhasil diimpor ke MySQL ===");

        // Trigger sync
        $this->info("Memulai sinkronisasi status live YouTube untuk seluruh unit...");
        $job = new SyncOfficerStreamsJob();
        app()->call([$job, 'handle']);
        $this->info("✓ Pengecekan live stream selesai!");

        return 0;
    }

    /**
     * Parse content from JSON, CSV, or Pipe Delimited lines.
     */
    protected function parseContent(string $content): array
    {
        $content = trim($content);

        // Try JSON
        if (str_starts_with($content, '[') || str_starts_with($content, '{')) {
            $json = json_decode($content, true);
            if (is_array($json)) {
                return isset($json[0]) ? $json : [$json];
            }
        }

        // Try line-by-line (Pipe '|' or Comma ',')
        $lines = explode("\n", $content);
        $results = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }

            // Detect delimiter
            $delimiter = str_contains($line, '|') ? '|' : ',';
            $parts = array_map('trim', explode($delimiter, $line));

            if (count($parts) >= 1 && !empty($parts[0])) {
                $results[] = [
                    'handle' => $parts[0] ?? '',
                    'streamer_name' => $parts[1] ?? '',
                    'officer_name' => $parts[2] ?? '',
                    'callsign' => $parts[3] ?? '',
                    'department' => $parts[4] ?? 'LSPD',
                    'rank' => $parts[5] ?? 'Officer',
                    'badge_number' => $parts[6] ?? '#000',
                ];
            }
        }

        return $results;
    }
}
