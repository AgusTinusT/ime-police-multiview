<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveMomenJob;
use App\Models\TacClip;
use Illuminate\Console\Command;

/**
 * Queue Momen Aksi archives: pending moments (e.g. the queue was down when they
 * were created) and failed ones that still have attempts left. --backfill also
 * queues older moments that were never archived but are still inside the
 * retention window.
 */
class ArchiveMomenAksi extends Command
{
    protected $signature = 'momen:archive {--backfill : Queue never-archived moments from the last MOMEN_ARCHIVE_DAYS days} {--limit=20 : Max moments queued per run}';

    protected $description = 'Queue Momen Aksi 720p archives to object storage (pending, retries, optional backfill)';

    public function handle(): int
    {
        if (! config('momen.archive_enabled')) {
            $this->warn('Arsip Momen Aksi nonaktif (MOMEN_ARCHIVE_ENABLED=false).');

            return self::SUCCESS;
        }

        $days = (int) config('momen.archive_days', 30);

        if ($this->option('backfill')) {
            $marked = TacClip::whereNull('archive_status')
                ->where('created_at', '>=', now()->subDays($days))
                ->update(['archive_status' => TacClip::ARCHIVE_PENDING]);
            $this->info("Backfill: {$marked} momen ditandai untuk diarsipkan.");
        }

        $ids = TacClip::where(function ($q) {
            $q->where('archive_status', TacClip::ARCHIVE_PENDING)
                ->orWhere(fn ($f) => $f->where('archive_status', TacClip::ARCHIVE_FAILED)
                    ->where('archive_attempts', '<', (int) config('momen.archive_max_attempts', 3))
                    // Give a just-ended live stream a moment before retrying.
                    ->where('updated_at', '<=', now()->subMinutes(5)));
        })
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->pluck('id');

        foreach ($ids as $id) {
            ArchiveMomenJob::dispatch($id);
        }

        $this->info("{$ids->count()} momen masuk antrean arsip.");

        return self::SUCCESS;
    }
}
