<?php

namespace App\Jobs;

use App\Models\TacClip;
use App\Models\VideoClip;
use App\Services\YouTubeClipperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cut one Momen Aksi at 720p (same yt-dlp/ffmpeg engine as Clip Studio) and
 * upload it to the archive disk. Runs on the "clipper" queue right after the
 * moment is created.
 *
 * Moments on a stream that is still live are cut from the live DVR buffer
 * immediately instead of waiting for the VOD: some officers switch the replay
 * to members-only once the stream ends, after which it can no longer be cut.
 */
class ArchiveMomenJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $tacClipId)
    {
        $this->onQueue('clipper');
    }

    public function handle(YouTubeClipperService $clipper): void
    {
        $momen = TacClip::find($this->tacClipId);
        if (! $momen || ! in_array($momen->archive_status, [TacClip::ARCHIVE_PENDING, TacClip::ARCHIVE_FAILED], true)) {
            return;
        }

        $url = "https://www.youtube.com/watch?v={$momen->video_id}";

        $momen->update([
            'archive_status' => TacClip::ARCHIVE_PROCESSING,
            'archive_attempts' => $momen->archive_attempts + 1,
            'archive_error' => null,
        ]);

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpPath = $tmpDir . "/momen_{$momen->id}_" . Str::random(8) . '.mp4';

        try {
            // The trimmer works on a VideoClip; this one is never saved.
            $source = new VideoClip([
                'youtube_url' => $url,
                'start_time' => $momen->start_seconds,
                'end_time' => $momen->end_seconds,
                'format' => config('momen.archive_format', 'MP4 720p'),
            ]);
            $source->id = "momen-{$momen->id}";

            // Same order as Clip Studio: live DVR first, yt-dlp sections as fallback.
            $cut = false;
            if ($clipper->isLiveStream($url)) {
                try {
                    $clipper->trimLiveStreamDvr($source, $tmpPath);
                    $cut = true;
                } catch (\Throwable $e) {
                    Log::warning("Momen #{$momen->id}: live DVR cut failed, falling back to yt-dlp: " . $e->getMessage());
                }
            }
            if (! $cut) {
                $clipper->trimStandardVod($source, $tmpPath);
            }

            if (! is_file($tmpPath) || filesize($tmpPath) === 0) {
                throw new \RuntimeException('Hasil potongan kosong atau tidak ditemukan.');
            }

            $disk = config('momen.archive_disk', 's3');
            $name = "{$momen->id}_" . Str::random(10) . '.mp4';
            $path = Storage::disk($disk)->putFileAs('momen/' . now()->format('Y/m'), new File($tmpPath), $name);

            if (! $path) {
                throw new \RuntimeException("Unggah ke disk '{$disk}' gagal.");
            }

            $momen->update([
                'archive_status' => TacClip::ARCHIVE_STORED,
                'archive_disk' => $disk,
                'archive_path' => $path,
                'archive_size' => filesize($tmpPath),
                'archived_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Momen #{$momen->id} archive failed: " . $e->getMessage());
            $momen->update([
                'archive_status' => TacClip::ARCHIVE_FAILED,
                'archive_error' => Str::limit($e->getMessage(), 490),
            ]);
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }
}
