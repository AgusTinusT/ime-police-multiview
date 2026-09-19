<?php

namespace App\Jobs;

use App\Models\VideoClip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ProcessVideoClipJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600; // 10 minutes timeout for execution

    /**
     * Create a new job instance.
     */
    public function __construct(public VideoClip $videoClip)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->videoClip->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            // Ensure public storage clips directory exists
            Storage::disk('public')->makeDirectory('clips');

            $filename = 'clip_' . $this->videoClip->id . '_' . Str::random(8) . '.mp4';
            $relativeFilePath = 'clips/' . $filename;
            $absoluteOutputPath = Storage::disk('public')->path($relativeFilePath);

            $startTimeFormatted = gmdate("H:i:s", $this->videoClip->start_time);
            $endTimeFormatted = gmdate("H:i:s", $this->videoClip->end_time);
            $sectionSpec = "*{$startTimeFormatted}-{$endTimeFormatted}";

            // Resolve binary paths (check local project storage/app/bin first)
            $binDir = storage_path('app/bin');
            $ytDlpBin = file_exists($binDir . '/yt-dlp.exe') 
                ? $binDir . '/yt-dlp.exe' 
                : (file_exists($binDir . '/yt-dlp') ? $binDir . '/yt-dlp' : 'yt-dlp');
            $ffmpegBin = file_exists($binDir . '/ffmpeg.exe') 
                ? $binDir . '/ffmpeg.exe' 
                : (file_exists($binDir . '/ffmpeg') ? $binDir . '/ffmpeg' : 'ffmpeg');

            // yt-dlp command using Direct Stream Copy (--download-sections)
            // --force-keyframes-at-cuts ensures accurate segment length without dropping frames
            // --hls-use-mpegts prevents active live streams from hanging in .part files
            // --js-runtimes node uses Node.js engine for YouTube JS challenge solving
            $command = [
                $ytDlpBin,
                '--user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                '--js-runtimes', 'node',
                '--download-sections', $sectionSpec,
                '-f', 'bv*[height<=1080]+ba/b[height<=1080]/best',
                '--merge-output-format', 'mp4',
                '--force-keyframes-at-cuts',
                '--hls-use-mpegts',
                '--concurrent-fragments', '4',
                '--fragment-retries', '10',
            ];

            if ($ffmpegBin) {
                $command[] = '--ffmpeg-location';
                $command[] = $ffmpegBin;
            }

            $command[] = '-o';
            $command[] = $absoluteOutputPath;
            $command[] = '--no-playlist';
            $command[] = '--force-overwrites';
            $command[] = $this->videoClip->youtube_url;

            Log::info("Starting Video Trimming Job #{$this->videoClip->id}", [
                'cmd' => implode(' ', $command),
                'section' => $sectionSpec,
            ]);

            $process = new Process($command);
            $process->setTimeout($this->timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                // If yt-dlp failed, log error and update status
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                Log::error("Video Trimming Job #{$this->videoClip->id} failed", ['error' => $errorOutput]);

                $this->videoClip->update([
                    'status' => 'failed',
                    'error_message' => Str::limit($errorOutput, 500),
                ]);
                return;
            }

            // Success
            $this->videoClip->update([
                'file_path' => $relativeFilePath,
                'status' => 'completed',
                'error_message' => null,
            ]);

            Log::info("Video Trimming Job #{$this->videoClip->id} completed successfully!", [
                'file_path' => $relativeFilePath
            ]);

        } catch (\Throwable $e) {
            Log::error("Video Trimming Exception #{$this->videoClip->id}", ['exception' => $e->getMessage()]);

            $this->videoClip->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
