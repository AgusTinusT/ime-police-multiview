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

            // Resolve binary paths
            $binDir = storage_path('app/bin');
            $ytDlpBin = file_exists($binDir . '/yt-dlp.exe') 
                ? $binDir . '/yt-dlp.exe' 
                : (file_exists($binDir . '/yt-dlp') ? $binDir . '/yt-dlp' : 'yt-dlp');

            // Resolve ffmpeg location
            $ffmpegLocation = null;
            if (file_exists($binDir . '/ffmpeg.exe')) {
                $ffmpegLocation = $binDir . '/ffmpeg.exe';
            } elseif (file_exists($binDir . '/ffmpeg')) {
                $ffmpegLocation = $binDir . '/ffmpeg';
            } elseif (file_exists('/usr/bin/ffmpeg')) {
                $ffmpegLocation = '/usr/bin/ffmpeg';
            } elseif (file_exists('/usr/local/bin/ffmpeg')) {
                $ffmpegLocation = '/usr/local/bin/ffmpeg';
            }

            // Resolve JS runtime (node/deno) path for YouTube JS challenge solving
            $nodePath = trim(shell_exec('which node 2>/dev/null') ?? '');
            $denoPath = trim(shell_exec('which deno 2>/dev/null') ?? '');

            $jsRuntimeSpec = 'node';
            if ($nodePath && file_exists($nodePath)) {
                $jsRuntimeSpec = 'node:' . $nodePath;
            } elseif ($denoPath && file_exists($denoPath)) {
                $jsRuntimeSpec = 'deno:' . $denoPath;
            } elseif (file_exists('/usr/bin/node')) {
                $jsRuntimeSpec = 'node:/usr/bin/node';
            } elseif (file_exists('/usr/local/bin/node')) {
                $jsRuntimeSpec = 'node:/usr/local/bin/node';
            }

            // yt-dlp command using Direct Stream Copy (--download-sections)
            // --force-ipv4 bypasses datacenter IPv6 rate limiting (HTTP 429) on VPS
            // --concurrent-fragments 8 speeds up clips > 1 minute
            $command = [
                $ytDlpBin,
                '--force-ipv4',
                '--sleep-requests', '1.5',
                '--user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                '--js-runtimes', $jsRuntimeSpec,
                '--extractor-args', 'youtube:player_client=tv_embedded,android_embedded',
                '--download-sections', $sectionSpec,
                '-f', 'bv*[vcodec^=avc1][height<=1080]+ba[ext=m4a]/bv*[vcodec^=avc1][height<=1080]+ba/b[height<=1080]/best',
                '--merge-output-format', 'mp4',
                '--hls-use-mpegts',
                '--concurrent-fragments', '8',
                '--fragment-retries', '10',
                '--postprocessor-args', 'ffmpeg:-c:v copy -c:a copy -movflags +faststart -avoid_negative_ts make_zero',
            ];

            if ($ffmpegLocation) {
                $command[] = '--ffmpeg-location';
                $command[] = $ffmpegLocation;
            }

            // Optional: Support YouTube cookies.txt file to completely bypass HTTP 429 rate limits
            $cookiesFile = storage_path('app/cookies.txt');
            if (file_exists($cookiesFile)) {
                $command[] = '--cookies';
                $command[] = $cookiesFile;
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
