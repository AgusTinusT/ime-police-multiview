<?php

namespace App\Services;

use App\Models\VideoClip;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class YouTubeClipperService
{
    /**
     * Helper to create Symfony Process with custom TEMP/TMP and PATH environment variables.
     * Prevents [PYI-29568:ERROR] Could not create temporary directory! on web servers.
     */
    protected function createProcess(array $command, ?int $timeout = 600): Process
    {
        $tmpDir = storage_path('app/tmp');
        if (!file_exists($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $process = new Process($command);
        if ($timeout !== null) {
            $process->setTimeout($timeout);
        }

        $process->setEnv([
            'TEMP' => $tmpDir,
            'TMP' => $tmpDir,
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ]);

        return $process;
    }

    /**
     * Resolve yt-dlp binary location.
     */
    public function resolveYtDlpBinary(): string
    {
        $binDir = storage_path('app/bin');
        if (file_exists($binDir . '/yt-dlp.exe')) {
            return $binDir . '/yt-dlp.exe';
        }
        if (file_exists($binDir . '/yt-dlp') && is_executable($binDir . '/yt-dlp')) {
            return $binDir . '/yt-dlp';
        }
        if (file_exists('/usr/local/bin/yt-dlp')) {
            return '/usr/local/bin/yt-dlp';
        }
        if (file_exists('/usr/bin/yt-dlp')) {
            return '/usr/bin/yt-dlp';
        }
        return 'yt-dlp';
    }

    /**
     * Resolve ffmpeg location.
     */
    public function resolveFfmpegBinary(): ?string
    {
        $binDir = storage_path('app/bin');
        if (file_exists($binDir . '/ffmpeg.exe')) {
            return $binDir . '/ffmpeg.exe';
        }
        if (file_exists($binDir . '/ffmpeg')) {
            return $binDir . '/ffmpeg';
        }
        if (file_exists('/usr/bin/ffmpeg')) {
            return '/usr/bin/ffmpeg';
        }
        if (file_exists('/usr/local/bin/ffmpeg')) {
            return '/usr/local/bin/ffmpeg';
        }
        return 'ffmpeg';
    }

    /**
     * Detect JS runtime (Node / Deno) for yt-dlp challenge solver.
     */
    public function resolveJsRuntimeSpec(): string
    {
        $nodePath = trim(shell_exec('which node 2>/dev/null') ?? '');
        $denoPath = trim(shell_exec('which deno 2>/dev/null') ?? '');

        if ($nodePath && file_exists($nodePath)) {
            return 'node:' . $nodePath;
        }
        if ($denoPath && file_exists($denoPath)) {
            return 'deno:' . $denoPath;
        }
        if (file_exists('/usr/bin/node')) {
            return 'node:/usr/bin/node';
        }
        if (file_exists('/usr/local/bin/node')) {
            return 'node:/usr/local/bin/node';
        }
        return 'node';
    }

    /**
     * Get detailed stream status (live, post_live, vod) from YouTube metadata.
     */
    public function getVideoStatus(string $youtubeUrl): array
    {
        try {
            $ytDlpBin = $this->resolveYtDlpBinary();
            $process = $this->createProcess([
                $ytDlpBin,
                '--dump-json',
                '--no-warnings',
                '--skip-download',
                $youtubeUrl
            ], 15);

            $process->run();

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput(), true);
                if ($data) {
                    $liveStatus = $data['live_status'] ?? '';
                    $isLive = !empty($data['is_live']) 
                        || $liveStatus === 'is_live'
                        || $liveStatus === 'is_upcoming';
                    $isPostLive = $liveStatus === 'post_live' || (!empty($data['was_live']) && !$isLive);

                    return [
                        'is_live' => $isLive,
                        'is_post_live' => $isPostLive,
                        'live_status' => $liveStatus,
                        'duration' => $data['duration'] ?? null,
                        'title' => $data['title'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("YouTubeClipperService::getVideoStatus check failed: " . $e->getMessage());
        }

        return [
            'is_live' => false,
            'is_post_live' => false,
            'live_status' => 'unknown',
            'duration' => null,
            'title' => null,
        ];
    }

    /**
     * Check if a YouTube video is an active live stream via yt-dlp dump-json.
     */
    public function isLiveStream(string $youtubeUrl): bool
    {
        $status = $this->getVideoStatus($youtubeUrl);
        return $status['is_live'];
    }

    /**
     * Main Entry: Process video clipping for a VideoClip model.
     * Supports both YouTube VOD and active YouTube Live (HLS DVR Sequence Manifest Extraction).
     */
    public function trimClip(VideoClip $clip): string
    {
        Storage::disk('public')->makeDirectory('clips');

        $formatOption = $clip->format ?? 'MP4 1080p';
        $ext = match ($formatOption) {
            'MP3 Audio' => 'mp3',
            default => 'mp4',
        };

        $filename = 'clip_' . $clip->id . '_' . Str::random(8) . '.' . $ext;
        $relativeFilePath = 'clips/' . $filename;
        $absoluteOutputPath = Storage::disk('public')->path($relativeFilePath);

        $isLive = $this->isLiveStream($clip->youtube_url);

        Log::info("YouTubeClipperService processing clip #{$clip->id}", [
            'url' => $clip->youtube_url,
            'start' => $clip->start_time,
            'end' => $clip->end_time,
            'is_live' => $isLive,
        ]);

        if ($isLive) {
            try {
                $this->trimLiveStreamDvr($clip, $absoluteOutputPath);
                return $relativeFilePath;
            } catch (\Throwable $e) {
                Log::warning("HLS DVR Sequence extraction failed for live clip #{$clip->id}, falling back to yt-dlp: " . $e->getMessage());
            }
        }

        // Standard VOD or Fallback trimming
        $this->trimStandardVod($clip, $absoluteOutputPath);
        return $relativeFilePath;
    }

    /**
     * HLS DVR Sequence Manifest Extraction for Active YouTube Live Streams.
     */
    public function trimLiveStreamDvr(VideoClip $clip, string $outputPath): void
    {
        $ytDlpBin = $this->resolveYtDlpBinary();
        $ffmpegBin = $this->resolveFfmpegBinary();

        $formatOption = $clip->format ?? 'MP4 1080p';
        $formatSpec = match ($formatOption) {
            'MP3 Audio' => 'ba/bestaudio',
            'MP4 480p' => 'bv*[height<=480]+ba/b[height<=480]/best',
            'MP4 720p' => 'bv*[height<=720]+ba/b[height<=720]/best',
            default => 'bv*[height<=1080]+ba/b[height<=1080]/best',
        };

        // 1. Extract M3U8 Stream URLs via yt-dlp -g with requested format specification
        $cmd = [
            $ytDlpBin,
            '-g',
            '-f', $formatSpec,
            '--no-playlist',
            '--no-cache-dir',
            '--force-ipv4',
            '--user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            $clip->youtube_url,
        ];

        $cookiesFile = storage_path('app/cookies.txt');
        if (file_exists($cookiesFile)) {
            array_splice($cmd, 1, 0, ['--cookies', $cookiesFile]);
        }

        $process = $this->createProcess($cmd, 30);
        $process->run();

        // Fallback: If format-specific yt-dlp -g failed, try without -f flag
        if (!$process->isSuccessful() || empty(trim($process->getOutput()))) {
            Log::warning("Format-specific yt-dlp -g failed for clip #{$clip->id} ({$formatSpec}), falling back to default stream.");
            $cmdFallback = array_values(array_filter($cmd, fn($arg) => $arg !== '-f' && $arg !== $formatSpec));
            $process = $this->createProcess($cmdFallback, 30);
            $process->run();
        }

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to get stream URLs from yt-dlp: " . $process->getErrorOutput());
        }

        $streamUrls = array_values(array_filter(explode("\n", trim($process->getOutput()))));
        if (empty($streamUrls)) {
            throw new \RuntimeException("No HLS stream URLs found for live stream.");
        }

        $videoStreamUrl = $streamUrls[0];
        $audioStreamUrl = count($streamUrls) > 1 ? $streamUrls[1] : null;

        // 2. Fetch M3U8 content to determine chunk duration & sequence URLs
        $videoM3u8Content = @file_get_contents($videoStreamUrl);
        if (!$videoM3u8Content) {
            throw new \RuntimeException("Failed to download video M3U8 manifest.");
        }

        // Parse chunk duration (#EXTINF:5.000 or #EXT-X-TARGETDURATION:5)
        $chunkDuration = 5.0; // default fallback
        if (preg_match('/#EXTINF:([\d\.]+)/', $videoM3u8Content, $m)) {
            $chunkDuration = (float) $m[1];
        } elseif (preg_match('/#EXT-X-TARGETDURATION:(\d+)/', $videoM3u8Content, $m)) {
            $chunkDuration = (float) $m[1];
        }

        if ($chunkDuration <= 0) {
            $chunkDuration = 5.0;
        }

        $startTime = (float) $clip->start_time;
        $endTime = (float) $clip->end_time;
        $clipDuration = max(1.0, $endTime - $startTime);

        // 3. Calculate sequence range
        $startSq = (int) floor($startTime / $chunkDuration);
        $endSq = (int) ceil($endTime / $chunkDuration);
        $seekOffset = max(0.0, $startTime - ($startSq * $chunkDuration));

        // 4. Construct custom .m3u8 playlist files
        $scratchDir = storage_path('app/scratch_playlists');
        if (!file_exists($scratchDir)) {
            @mkdir($scratchDir, 0755, true);
        }

        $videoPlaylistPath = $this->buildCustomM3u8($videoM3u8Content, $videoStreamUrl, $startSq, $endSq, $chunkDuration, $scratchDir, "clip_{$clip->id}_v.m3u8");
        $audioPlaylistPath = null;

        if ($audioStreamUrl) {
            $audioM3u8Content = @file_get_contents($audioStreamUrl);
            if ($audioM3u8Content) {
                $audioPlaylistPath = $this->buildCustomM3u8($audioM3u8Content, $audioStreamUrl, $startSq, $endSq, $chunkDuration, $scratchDir, "clip_{$clip->id}_a.m3u8");
            }
        }

        // 5. Execute FFmpeg with protocol whitelist
        $ffmpegCmd = [
            $ffmpegBin,
            '-y',
            '-protocol_whitelist', 'file,http,https,tcp,tls,crypto,data',
            '-ss', sprintf('%.3f', $seekOffset),
            '-i', $videoPlaylistPath,
        ];

        if ($audioPlaylistPath) {
            array_push(
                $ffmpegCmd,
                '-protocol_whitelist', 'file,http,https,tcp,tls,crypto,data',
                '-ss', sprintf('%.3f', $seekOffset),
                '-i', $audioPlaylistPath
            );
        }

        $formatOption = $clip->format ?? 'MP4 1080p';
        if ($formatOption === 'MP3 Audio') {
            array_push($ffmpegCmd, '-t', sprintf('%.3f', $clipDuration), '-c:a', 'libmp3lame', '-q:a', '2', $outputPath);
        } else {
            array_push($ffmpegCmd, '-t', sprintf('%.3f', $clipDuration), '-c:v', 'copy', '-c:a', 'copy', '-movflags', '+faststart', $outputPath);
        }

        Log::info("Executing FFmpeg DVR clip process #{$clip->id}", ['cmd' => implode(' ', $ffmpegCmd)]);

        $ffmpegProcess = $this->createProcess($ffmpegCmd, 600);
        $ffmpegProcess->run();

        // Cleanup temporary scratch playlists
        @unlink($videoPlaylistPath);
        if ($audioPlaylistPath) {
            @unlink($audioPlaylistPath);
        }

        if (!$ffmpegProcess->isSuccessful()) {
            throw new \RuntimeException("FFmpeg DVR clipping failed: " . $ffmpegProcess->getErrorOutput());
        }
    }

    /**
     * Build custom M3U8 file containing sequence URLs for target time range.
     */
    protected function buildCustomM3u8(string $m3u8Content, string $originalStreamUrl, int $startSq, int $endSq, float $chunkDuration, string $scratchDir, string $fileName): string
    {
        // Find segment URL line template in original m3u8
        $lines = explode("\n", $m3u8Content);
        $sampleSegmentUrl = '';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed) && !str_starts_with($trimmed, '#')) {
                $sampleSegmentUrl = $trimmed;
                break;
            }
        }

        if (empty($sampleSegmentUrl)) {
            $sampleSegmentUrl = $originalStreamUrl;
        }

        $playlistLines = [];
        $playlistLines[] = '#EXTM3U';
        $playlistLines[] = '#EXT-X-VERSION:3';
        $playlistLines[] = '#EXT-X-TARGETDURATION:' . ceil($chunkDuration);
        $playlistLines[] = '#EXT-X-MEDIA-SEQUENCE:' . $startSq;

        for ($sq = $startSq; $sq <= $endSq; $sq++) {
            $playlistLines[] = sprintf('#EXTINF:%.3f,', $chunkDuration);
            if (preg_match('/\/sq\/\d+/', $sampleSegmentUrl)) {
                $segUrl = preg_replace('/\/sq\/\d+/', '/sq/' . $sq, $sampleSegmentUrl);
            } else {
                $separator = str_contains($sampleSegmentUrl, '?') ? '&' : '?';
                $segUrl = $sampleSegmentUrl . $separator . 'sq=' . $sq;
            }
            $playlistLines[] = $segUrl;
        }

        $playlistLines[] = '#EXT-X-ENDLIST';

        $filePath = $scratchDir . '/' . $fileName;
        file_put_contents($filePath, implode("\n", $playlistLines));

        return $filePath;
    }

    /**
     * Standard VOD Trimming via yt-dlp --download-sections.
     */
    public function trimStandardVod(VideoClip $clip, string $outputPath): void
    {
        $ytDlpBin = $this->resolveYtDlpBinary();
        $ffmpegBin = $this->resolveFfmpegBinary();
        $jsRuntimeSpec = $this->resolveJsRuntimeSpec();

        $startTimeFormatted = gmdate("H:i:s", $clip->start_time);
        $endTimeFormatted = gmdate("H:i:s", $clip->end_time);
        $sectionSpec = "*{$startTimeFormatted}-{$endTimeFormatted}";

        $formatOption = $clip->format ?? 'MP4 1080p';

        $command = [
            $ytDlpBin,
            '--no-cache-dir',
            '--force-ipv4',
            '--user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            '--js-runtimes', $jsRuntimeSpec,
            '--download-sections', $sectionSpec,
        ];

        if ($formatOption === 'MP3 Audio') {
            array_push(
                $command,
                '-f', 'bestaudio/ba',
                '-x',
                '--audio-format', 'mp3',
                '--audio-quality', '0'
            );
        } elseif ($formatOption === 'MP4 480p') {
            array_push(
                $command,
                '-f', 'bv*[height<=480]+ba/b[height<=480]/best',
                '--merge-output-format', 'mp4',
                '--downloader-args', 'ffmpeg:-c copy -movflags +faststart'
            );
        } elseif ($formatOption === 'MP4 720p') {
            array_push(
                $command,
                '-f', 'bv*[height<=720]+ba/b[height<=720]/best',
                '--merge-output-format', 'mp4',
                '--downloader-args', 'ffmpeg:-c copy -movflags +faststart'
            );
        } else { // Default MP4 1080p
            array_push(
                $command,
                '-f', 'bv*[height<=1080]+ba/b[height<=1080]/best',
                '--merge-output-format', 'mp4',
                '--downloader-args', 'ffmpeg:-c copy -movflags +faststart'
            );
        }

        array_push(
            $command,
            '--force-keyframes-at-cuts',
            '--hls-use-mpegts',
            '--concurrent-fragments', '8',
            '--fragment-retries', '10'
        );

        if ($ffmpegBin) {
            $command[] = '--ffmpeg-location';
            $command[] = $ffmpegBin;
        }

        $cookiesFile = storage_path('app/cookies.txt');
        if (file_exists($cookiesFile)) {
            $command[] = '--cookies';
            $command[] = $cookiesFile;
        }

        $command[] = '-o';
        $command[] = $outputPath;
        $command[] = '--no-playlist';
        $command[] = '--force-overwrites';
        $command[] = $clip->youtube_url;

        $process = $this->createProcess($command, 600);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::warning("trimStandardVod initial attempt failed for clip #{$clip->id}, attempting fallback retry: " . Str::limit($errorOutput, 250));

            // Fallback attempt: remove custom ffmpeg downloader args (which can fail on fragmented streams) and retry
            $fallbackCommand = [];
            $skipNext = false;
            foreach ($command as $i => $arg) {
                if ($skipNext) {
                    $skipNext = false;
                    continue;
                }
                if ($arg === '--downloader-args') {
                    $skipNext = true;
                    continue;
                }
                $fallbackCommand[] = $arg;
            }

            @unlink($outputPath . '.part');
            $fallbackProcess = $this->createProcess($fallbackCommand, 600);
            $fallbackProcess->run();

            if ($fallbackProcess->isSuccessful() && file_exists($outputPath) && filesize($outputPath) > 1000) {
                Log::info("trimStandardVod fallback succeeded for clip #{$clip->id}");
                return;
            }

            $finalError = $fallbackProcess->getErrorOutput() ?: $fallbackProcess->getOutput() ?: $errorOutput;

            // Detect if failure is due to post_live state (stream ended but VOD archive still rendering)
            if (
                str_contains($finalError, 'moov atom not found') ||
                str_contains($finalError, 'Invalid data found when processing input') ||
                str_contains($finalError, 'source=yt_live_broadcast') ||
                str_contains($finalError, 'This live event has ended')
            ) {
                throw new \RuntimeException(
                    "Siaran YouTube ini baru saja berakhir dan saat ini masih dalam proses rendering arsip oleh server YouTube (Status: Post-Live). Server Google Video belum menyediakan file rekaman VOD utuh (moov atom belum tersedia). Silakan tunggu sekitar 15-30 menit hingga YouTube menyelesaikan proses arsip VOD, lalu coba potong kembali."
                );
            }

            throw new \RuntimeException(Str::limit($finalError, 500));
        }
    }
}
