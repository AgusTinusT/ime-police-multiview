<?php

namespace App\Console\Commands;

use App\Models\Officer;
use App\Services\OfficerAvatarStore;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Backfill: copy custom officer avatars that are still hotlinked to object
 * storage. Links that no longer load are reported and left unchanged.
 */
class StoreOfficerAvatars extends Command
{
    protected $signature = 'officer:store-avatars {--dry-run : Hanya tampilkan perwira yang akan diproses}';

    protected $description = 'Simpan foto profil custom perwira yang masih berupa tautan ke object storage (S3)';

    public function handle(OfficerAvatarStore $store): int
    {
        if (! OfficerAvatarStore::enabled()) {
            $this->error('Penyimpanan avatar nonaktif (AVATAR_STORE_ENABLED=false).');

            return self::FAILURE;
        }

        $officers = Officer::where('avatar_is_custom', true)
            ->whereNull('avatar_path')
            ->whereNotNull('avatar_url')
            ->get(['id', 'officer_name', 'callsign', 'avatar_url', 'avatar_path', 'avatar_disk']);

        $todo = $officers->filter(fn (Officer $o) => OfficerAvatarStore::shouldStore($o->avatar_url));

        $this->line("{$todo->count()} foto custom masih berupa tautan.");
        if ($this->option('dry-run')) {
            foreach ($todo as $officer) {
                $this->line(" - #{$officer->id} {$officer->officer_name}: {$officer->avatar_url}");
            }

            return self::SUCCESS;
        }

        $stored = 0;
        $failed = 0;
        foreach ($todo as $officer) {
            try {
                $officer->update($store->store($officer->avatar_url));
                $stored++;
                $this->info("✓ #{$officer->id} {$officer->officer_name}");
            } catch (ValidationException $e) {
                $failed++;
                $this->warn("✗ #{$officer->id} {$officer->officer_name}: " . collect($e->errors())->flatten()->first());
            }
        }

        $this->info("Selesai: {$stored} disimpan, {$failed} gagal (tautan dibiarkan apa adanya).");

        return self::SUCCESS;
    }
}
