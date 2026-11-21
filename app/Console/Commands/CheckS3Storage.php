<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Smoke-test the S3 disk after filling AWS_* in .env: write, read, sign and
 * delete a tiny object, reporting which step fails.
 */
class CheckS3Storage extends Command
{
    protected $signature = 'storage:check-s3';

    protected $description = 'Verify the S3 disk (AWS_* in .env) can write, read, sign URLs and delete';

    public function handle(): int
    {
        $missing = collect(['AWS_ACCESS_KEY_ID' => 'key', 'AWS_SECRET_ACCESS_KEY' => 'secret', 'AWS_BUCKET' => 'bucket', 'AWS_ENDPOINT' => 'endpoint'])
            ->filter(fn ($configKey) => blank(config("filesystems.disks.s3.{$configKey}")))
            ->keys();

        if ($missing->isNotEmpty()) {
            $this->error('Belum diisi di .env: ' . $missing->implode(', '));
            $this->line('Lihat .env.production.example bagian "Object Storage S3", lalu jalankan php artisan config:clear.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Endpoint %s · bucket %s · region %s',
            config('filesystems.disks.s3.endpoint'),
            config('filesystems.disks.s3.bucket'),
            config('filesystems.disks.s3.region'),
        ));

        // Surface the real S3 error instead of a silent false (the disk is first
        // resolved below, so the flag applies).
        config(['filesystems.disks.s3.throw' => true]);
        $disk = Storage::disk('s3');

        $path = '_healthcheck/' . now()->format('Ymd-His') . '-' . Str::random(6) . '.txt';
        $body = 'ime-police-duty s3 check ' . now()->toIso8601String();

        $steps = [
            'Tulis objek uji' => fn () => $disk->put($path, $body),
            'Baca kembali' => function () use ($disk, $path, $body) {
                if ($disk->get($path) !== $body) {
                    throw new \RuntimeException('Isi file tidak sama dengan yang ditulis.');
                }
            },
            'Buat signed URL (5 menit)' => fn () => $this->line('    ' . $disk->temporaryUrl($path, now()->addMinutes(5))),
            'Hapus objek uji' => fn () => $disk->delete($path),
        ];

        foreach ($steps as $label => $step) {
            try {
                $step();
                $this->info("✓ {$label}");
            } catch (Throwable $e) {
                $this->error("✗ {$label}: " . Str::limit($e->getMessage(), 300));
                $this->line('Periksa key/secret, nama bucket, endpoint, dan region di .env.');

                return self::FAILURE;
            }
        }

        $this->info('S3 siap dipakai.');

        return self::SUCCESS;
    }
}
