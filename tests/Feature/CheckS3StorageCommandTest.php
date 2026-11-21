<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckS3StorageCommandTest extends TestCase
{
    public function test_it_lists_missing_credentials_without_touching_the_network(): void
    {
        config([
            'filesystems.disks.s3.key' => null,
            'filesystems.disks.s3.secret' => null,
            'filesystems.disks.s3.bucket' => 'ime-police-duty',
            'filesystems.disks.s3.endpoint' => 'https://is3.cloudhost.id',
        ]);

        $this->artisan('storage:check-s3')
            ->expectsOutputToContain('AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY')
            ->assertFailed();
    }

    public function test_it_runs_every_step_against_the_disk(): void
    {
        config([
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.bucket' => 'ime-police-duty',
            'filesystems.disks.s3.endpoint' => 'https://is3.cloudhost.id',
        ]);
        Storage::fake('s3');

        $this->artisan('storage:check-s3')
            ->expectsOutputToContain('S3 siap dipakai.')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('s3')->allFiles('_healthcheck'));
    }
}
