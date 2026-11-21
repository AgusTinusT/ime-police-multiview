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

    /**
     * Real YouTubeClipperService::trimToFile policy with the network calls faked.
     *
     * @param  array<bool>  $liveChecks  successive isLiveStream() answers
     * @param  string|null  $dvrError  make the DVR cut throw this
     * @param  string|null  $vodError  make the VOD cut throw this
     */
    private function fakeClipper(array $liveChecks, ?string $dvrError = null, ?string $vodError = null, bool $expectDvr = false, bool $expectVod = false): void
    {
        $mock = Mockery::mock(YouTubeClipperService::class)->makePartial();
        $mock->shouldReceive('isLiveStream')->andReturnValues($liveChecks);

        $cut = fn (?string $error) => function ($clip, string $path) use ($error) {
            if ($error) {
                throw new \RuntimeException($error);
            }
            file_put_contents($path, 'fake-mp4-bytes');
        };

        $expectDvr
            ? $mock->shouldReceive('trimLiveStreamDvr')->once()->andReturnUsing($cut($dvrError))
            : $mock->shouldNotReceive('trimLiveStreamDvr');
        $expectVod
            ? $mock->shouldReceive('trimStandardVod')->once()->andReturnUsing($cut($vodError))
            : $mock->shouldNotReceive('trimStandardVod');

        $this->app->instance(YouTubeClipperService::class, $mock);
    }

    private function runJob(TacClip $momen): TacClip
    {
        (new ArchiveMomenJob($momen->id))->handle(app(YouTubeClipperService::class));

        return $momen->fresh();
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
        $this->fakeClipper([true], expectDvr: true);
        $momen = $this->runJob($this->momen());

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
        $this->fakeClipper([false], expectVod: true);

        $this->assertSame(TacClip::ARCHIVE_STORED, $this->runJob($this->momen())->archive_status);
    }

    public function test_live_dvr_failure_never_falls_back_to_a_live_edge_recording(): void
    {
        // Still live after the DVR failure: yt-dlp would record from the live edge
        // (footage after the moment), so the cut must fail and be retried instead.
        $this->fakeClipper([true, true], dvrError: 'HTTP 404 segment', expectDvr: true, expectVod: false);
        $momen = $this->runJob($this->momen());

        $this->assertSame(TacClip::ARCHIVE_FAILED, $momen->archive_status);
        $this->assertStringContainsString('DVR', $momen->archive_error);
        $this->assertNull($momen->archive_path);
    }

    public function test_stream_that_ended_during_the_dvr_cut_is_cut_from_the_vod(): void
    {
        $this->fakeClipper([true, false], dvrError: 'stream ended', expectDvr: true, expectVod: true);

        $this->assertSame(TacClip::ARCHIVE_STORED, $this->runJob($this->momen())->archive_status);
    }

    public function test_members_only_video_is_marked_unavailable_and_not_retried(): void
    {
        $this->fakeClipper([false], vodError: 'ERROR: [youtube] OmP29OQcXR0: Join this channel to get access to members-only content', expectVod: true);
        $momen = $this->runJob($this->momen());

        $this->assertSame(TacClip::ARCHIVE_UNAVAILABLE, $momen->archive_status);
        $this->assertSame(1, $momen->archive_attempts);

        Queue::fake();
        TacClip::whereKey($momen->id)->update(['updated_at' => now()->subHour()]);
        $this->artisan('momen:archive')->assertSuccessful();
        Queue::assertNothingPushed();
    }

    public function test_transient_failure_is_recorded_for_a_retry(): void
    {
        $this->fakeClipper([false], vodError: 'Connection reset by peer', expectVod: true);
        $momen = $this->runJob($this->momen());

        $this->assertSame(TacClip::ARCHIVE_FAILED, $momen->archive_status);
        $this->assertSame(1, $momen->archive_attempts);
    }

    public function test_redo_deletes_the_wrong_file_and_requeues(): void
    {
        Queue::fake();
        Storage::disk('s3')->put('momen/2026/11/wrong.mp4', 'wrong-part');
        $momen = $this->momen([
            'archive_status' => TacClip::ARCHIVE_STORED, 'archive_disk' => 's3',
            'archive_path' => 'momen/2026/11/wrong.mp4', 'archived_at' => now(), 'archive_attempts' => 1,
        ]);

        $this->artisan("momen:archive --redo={$momen->id}")->assertSuccessful();

        Storage::disk('s3')->assertMissing('momen/2026/11/wrong.mp4');
        $momen->refresh();
        $this->assertSame(TacClip::ARCHIVE_PENDING, $momen->archive_status);
        $this->assertNull($momen->archive_path);
        $this->assertSame(0, $momen->archive_attempts);
        Queue::assertPushed(ArchiveMomenJob::class, fn ($job) => $job->tacClipId === $momen->id);
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
