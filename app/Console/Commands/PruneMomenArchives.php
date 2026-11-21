<?php

namespace App\Console\Commands;

use App\Models\TacClip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Delete archived Momen Aksi files older than MOMEN_ARCHIVE_DAYS. Only the file
 * goes: the moment row (title, start/end seconds, officer, likes) stays and keeps
 * playing from YouTube.
 */
class PruneMomenArchives extends Command
{
    protected $signature = 'momen:prune-archives {--days= : Override MOMEN_ARCHIVE_DAYS}';

    protected $description = 'Delete archived Momen Aksi files past the retention window, keeping the moment data';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('momen.archive_days', 30));

        $expired = TacClip::where('archive_status', TacClip::ARCHIVE_STORED)
            ->where('archived_at', '<', now()->subDays($days))
            ->get();

        $freed = 0;
        foreach ($expired as $momen) {
            if ($momen->archive_disk && $momen->archive_path) {
                Storage::disk($momen->archive_disk)->delete($momen->archive_path);
            }
            $freed += (int) $momen->archive_size;

            $momen->update([
                'archive_status' => TacClip::ARCHIVE_EXPIRED,
                'archive_path' => null,
                'archive_size' => null,
            ]);
        }

        $this->info(sprintf(
            '%d arsip lebih dari %d hari dihapus (%.1f MB). Data momen tetap disimpan.',
            $expired->count(),
            $days,
            $freed / 1048576,
        ));

        return self::SUCCESS;
    }
}
