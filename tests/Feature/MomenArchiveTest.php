<?php

namespace Tests\Feature;

use App\Jobs\ArchiveMomenJob;
use App\Models\TacClip;
use App\Services\YouTubeClipperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MomenArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['momen.archive_enabled' => true, 'momen.archive_disk' => 's3', 'momen.archive_days' => 30]);
        Storage::fake('s3');
    }

    private function momen(array $attrs = []): TacClip
    {
        return TacClip::create(array_merge([
            'video_id' => 'ABCDEFGHIJK',
            'title' => 'Pengejaran',
            'start_seconds' => 100,
            'end_seconds' => 130,
            'creator_name' => 'Tester',
            'is_approved' => true,
            'archive_status' => TacClip::ARCHIVE_PENDING,
        ], $attrs));
    }

    /** Fake trimmer: writes a few bytes to the output path like yt-dlp would. */
    private function fakeClipper(bool $live, ?string $failWith = null): void
    {
        $mock = Mockery::mock(YouTubeClipperService::class);
        $mock->shouldReceive('isLiveStream')->andReturn($live);
        $write = function ($clip, string $path) use ($failWith) {
            if ($failWith) {
                throw new \RuntimeException($failWith);
            }
            file_put_contents($path, 'fake-mp4-bytes');
        };
        if ($live) {
            $mock->shouldReceive('trimLiveStreamDvr')->once()->andReturnUsing($write);
            $mock->shouldNotReceive('trimStandardVod');
        } else {
            $mock->shouldNotReceive('trimLiveStreamDvr');
            $mock->shouldReceive('trimStandardVod')->once()->andReturnUsing($write);
        }
        $this->app->instance(YouTubeClipperService::class, $mock);
    }

    public function test_creating_a_moment_queues_its_archive_immediately(): void
    {
        Queue::fake();

        $id = $this->postJson('/api/v1/tac-clips', [
            'video_id' => 'ABCDEFGHIJK',
            'title' => 'Momen live',
            'start_seconds' => 10,
            'end_seconds' => 40,
        ])->assertCreated()->json('clip.id');

        $this->assertSame(TacClip::ARCHIVE_PENDING, TacClip::find($id)->archive_status);
        Queue::assertPushedOn('clipper', ArchiveMomenJob::class, fn ($job) => $job->tacClipId === $id);
    }

    public function test_nothing_is_queued_while_archiving_is_disabled(): void
    {
        config(['momen.archive_enabled' => false]);
        Queue::fake();

        $id = $this->postJson('/api/v1/tac-clips', [
            'video_id' => 'ABCDEFGHIJK', 'title' => 'X', 'start_seconds' => 0, 'end_seconds' => 5,
        ])->json('clip.id');

        $this->assertNull(TacClip::find($id)->archive_status);
        Queue::assertNothingPushed();
    }

    public function test_live_moment_is_cut_from_the_dvr_buffer_and_stored(): void
    {
        $this->fakeClipper(live: true);
        $momen = $this->momen();

        (new ArchiveMomenJob($momen->id))->handle(app(YouTubeClipperService::class));

        $momen->refresh();
        $this->assertSame(TacClip::ARCHIVE_STORED, $momen->archive_status);
        $this->assertSame('s3', $momen->archive_disk);
        Storage::disk('s3')->assertExists($momen->archive_path);
        $this->assertStringStartsWith('momen/', $momen->archive_path);
        $this->assertSame(strlen('fake-mp4-bytes'), $momen->archive_size);
        $this->assertNotNull($momen->archived_at);
        $this->assertSame([], glob(storage_path("app/tmp/momen_{$momen->id}_*")));
    }

    public function test_vod_moment_uses_the_section_downloader(): void
    {
        $this->fakeClipper(live: false);
        $momen = $this->momen();

        (new ArchiveMomenJob($momen->id))->handle(app(YouTubeClipperService::class));

        $this->assertSame(TacClip::ARCHIVE_STORED, $momen->fresh()->archive_status);
    }

    public function test_failure_is_recorded_for_a_retry(): void
    {
        $this->fakeClipper(live: false, failWith: 'Video members-only');
        $momen = $this->momen();

        (new ArchiveMomenJob($momen->id))->handle(app(YouTubeClipperService::class));

        $momen->refresh();
        $this->assertSame(TacClip::ARCHIVE_FAILED, $momen->archive_status);
        $this->assertSame(1, $momen->archive_attempts);
        $this->assertStringContainsString('members-only', $momen->archive_error);
    }

    public function test_scheduler_queues_pending_and_retryable_moments_only(): void
    {
        Queue::fake();
        $pending = $this->momen();
        $retry = $this->momen(['archive_status' => TacClip::ARCHIVE_FAILED, 'archive_attempts' => 1]);
        $exhausted = $this->momen(['archive_status' => TacClip::ARCHIVE_FAILED, 'archive_attempts' => 3]);
        $stored = $this->momen(['archive_status' => TacClip::ARCHIVE_STORED]);
        TacClip::whereKey([$retry->id, $exhausted->id])->update(['updated_at' => now()->subMinutes(10)]);

        $this->artisan('momen:archive')->assertSuccessful();

        $queued = [];
        Queue::assertPushed(ArchiveMomenJob::class, function ($job) use (&$queued) {
            $queued[] = $job->tacClipId;

            return true;
        });
        sort($queued);
        $this->assertSame([$pending->id, $retry->id], $queued);
        $this->assertNotContains($exhausted->id, $queued);
        $this->assertNotContains($stored->id, $queued);
    }

    public function test_backfill_marks_recent_unarchived_moments(): void
    {
        Queue::fake();
        $recent = $this->momen(['archive_status' => null]);
        $old = $this->momen(['archive_status' => null]);
        TacClip::whereKey($old->id)->update(['created_at' => now()->subDays(40)]);

        $this->artisan('momen:archive --backfill')->assertSuccessful();

        $this->assertSame(TacClip::ARCHIVE_PENDING, $recent->fresh()->archive_status);
        $this->assertNull($old->fresh()->archive_status);
        Queue::assertPushed(ArchiveMomenJob::class, 1);
    }

    public function test_archives_older_than_30_days_are_deleted_but_the_moment_stays(): void
    {
        Storage::disk('s3')->put('momen/2026/10/old.mp4', 'x');
        Storage::disk('s3')->put('momen/2026/11/new.mp4', 'y');
        $old = $this->momen(['archive_status' => TacClip::ARCHIVE_STORED, 'archive_disk' => 's3', 'archive_path' => 'momen/2026/10/old.mp4', 'archived_at' => now()->subDays(31)]);
        $new = $this->momen(['archive_status' => TacClip::ARCHIVE_STORED, 'archive_disk' => 's3', 'archive_path' => 'momen/2026/11/new.mp4', 'archived_at' => now()->subDays(2)]);

        $this->artisan('momen:prune-archives')->assertSuccessful();

        Storage::disk('s3')->assertMissing('momen/2026/10/old.mp4');
        Storage::disk('s3')->assertExists('momen/2026/11/new.mp4');
        $old->refresh();
        $this->assertSame(TacClip::ARCHIVE_EXPIRED, $old->archive_status);
        $this->assertNull($old->archive_path);
        $this->assertSame(100, $old->start_seconds);
        $this->assertSame(TacClip::ARCHIVE_STORED, $new->fresh()->archive_status);
    }
}
