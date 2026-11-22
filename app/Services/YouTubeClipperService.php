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
            // Same flags as the cutters: without the JS runtime / cookies, or with a
            // tight timeout, this check failed on the VPS and the clip silently took
            // the VOD path, which on a live stream records from the live edge.
            $command = [
                $ytDlpBin,
                '--dump-json',
                '--no-warnings',
                '--skip-download',
                '--no-playlist',
                '--no-cache-dir',
                '--force-ipv4',
                '--js-runtimes', $this->resolveJsRuntimeSpec(),
            ];
            $cookiesFile = storage_path('app/cookies.txt');
            if (file_exists($cookiesFile)) {
                array_push($command, '--cookies', $cookiesFile);
            }
            $command[] = $youtubeUrl;

            $process = $this->createProcess($command, 60);

            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning('YouTubeClipperService::getVideoStatus yt-dlp failed: ' . Str::limit($process->getErrorOutput() ?: $process->getOutput(), 300));
            }

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

        $this->trimToFile($clip, $absoluteOutputPath);

        return $relativeFilePath;
    }

    /**
     * Cut $clip->start_time..end_time (seconds from the start of the broadcast)
     * into $outputPath. Shared by Clip Studio and the Momen Aksi archive.
     *
     * While the stream is live the cut must come from the DVR segments. The old
     * fallback to `yt-dlp --download-sections` is NOT used then: on a live stream
     * yt-dlp ignores the section and records from the live edge onwards, so the
     * user got footage from after the moment they picked. If DVR fails and the
     * stream has meanwhile ended, the finished VOD is cut instead.
     */
    public function trimToFile(VideoClip $clip, string $outputPath): void
    {
        $status = $this->getVideoStatus($clip->youtube_url);
        $isLive = (bool) $status['is_live'];
        $unknown = ($status['live_status'] ?? '') === 'unknown';

        Log::info("YouTubeClipperService processing clip #{$clip->id}", [
            'url' => $clip->youtube_url,
            'start' => $clip->start_time,
            'end' => $clip->end_time,
            'is_live' => $isLive,
            'live_status' => $status['live_status'] ?? null,
        ]);

        // Status check failed (timeout, bot check): try DVR first. A VOD has no live
        // manifest so DVR fails fast, and the VOD cutter refuses live streams anyway.
        if ($unknown) {
            try {
                $this->trimLiveStreamDvr($clip, $outputPath);

                return;
            } catch (\Throwable $e) {
                Log::info("Clip #{$clip->id}: live status unknown and DVR cut failed, trying the VOD cutter.", ['error' => $e->getMessage()]);
                $this->trimStandardVod($clip, $outputPath);

                return;
            }
        }

        if (! $isLive) {
            $this->trimStandardVod($clip, $outputPath);

            return;
        }

        try {
            $this->trimLiveStreamDvr($clip, $outputPath);
        } catch (\Throwable $e) {
            if ($this->isLiveStream($clip->youtube_url)) {
                Log::warning("Live DVR cut failed for clip #{$clip->id}; not falling back to a live-edge recording.", ['error' => $e->getMessage()]);
                throw new \RuntimeException('Potong live (DVR) gagal: ' . $e->getMessage(), 0, $e);
            }

            Log::info("Clip #{$clip->id}: stream ended during the DVR cut, cutting the finished VOD instead.");
            $this->trimStandardVod($clip, $outputPath);
        }
    }

    /**
     * Map a clip range (seconds from broadcast start) to DVR segment numbers.
     * YouTube numbers live segments from 0 at the start of the broadcast (sq * chunk
     * equals the time since start), so sq = floor(seconds / chunk).
     *
     * @return array{startSq:int, endSq:int, seekOffset:float, duration:float}
     */
    public static function planDvrRange(float $start, float $end, float $chunk): array
    {
        if ($chunk <= 0) {
            $chunk = 5.0;
        }
        if ($start < 0 || $end <= $start) {
            throw new \InvalidArgumentException('Rentang waktu klip tidak valid.');
        }

        $startSq = (int) floor($start / $chunk);

        return [
            'startSq' => $startSq,
            'endSq' => (int) ceil($end / $chunk),
            'seekOffset' => max(0.0, $start - ($startSq * $chunk)),
            'duration' => max(1.0, $end - $start),
        ];
    }

    /**
     * Errors that will not go away on retry (members-only, private, removed).
     */
    public static function isPermanentVideoError(string $message): bool
    {
        return (bool) preg_match(
            '/members-only|join this channel|private video|video is private|video unavailable|has been removed|account associated with this video has been terminated|copyright claim/i',
            $message,
        );
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
        //    (same JS runtime as the VOD path; yt-dlp may need it for YouTube's challenges)
        $cmd = [
            $ytDlpBin,
            '-g',
            '-f', $formatSpec,
            '--no-playlist',
            '--no-cache-dir',
            '--force-ipv4',
            '--js-runtimes', $this->resolveJsRuntimeSpec(),
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
        $videoM3u8Content = $this->fetchPlaylist($videoStreamUrl);

        // Parse chunk duration (#EXTINF:2.000 or #EXT-X-TARGETDURATION:2)
        $chunkDuration = 5.0; // default fallback
        if (preg_match('/#EXTINF:([\d\.]+)/', $videoM3u8Content, $m)) {
            $chunkDuration = (float) $m[1];
        } elseif (preg_match('/#EXT-X-TARGETDURATION:(\d+)/', $videoM3u8Content, $m)) {
            $chunkDuration = (float) $m[1];
        }

        // 3. Calculate sequence range
        $plan = self::planDvrRange((float) $clip->start_time, (float) $clip->end_time, $chunkDuration);
        $startSq = $plan['startSq'];
        $endSq = $plan['endSq'];
        $seekOffset = $plan['seekOffset'];
        $clipDuration = $plan['duration'];

        // The end of the clip may still be ahead of the live edge (e.g. "next 30 s");
        // those segments 404 until they are produced, so wait for them instead of
        // failing. Give up if they are further away than the job can wait.
        $lastSq = self::lastSequenceNumber($videoM3u8Content);
        if ($lastSq !== null && $endSq > $lastSq) {
            $waitSeconds = ($endSq - $lastSq) * $chunkDuration;
            if ($waitSeconds > 300) {
                throw new \RuntimeException(sprintf(
                    'Akhir klip %.0f detik di depan siaran live saat ini; tunggu sampai momennya lewat lalu coba lagi.',
                    $waitSeconds,
                ));
            }
            $deadline = microtime(true) + $waitSeconds + 30;
            while ($lastSq < $endSq && microtime(true) < $deadline) {
                sleep((int) max(2, min(10, ceil(($endSq - $lastSq) * $chunkDuration))));
                $videoM3u8Content = $this->fetchPlaylist($videoStreamUrl);
                $lastSq = self::lastSequenceNumber($videoM3u8Content) ?? $lastSq;
            }
            if ($lastSq < $endSq) {
                throw new \RuntimeException('Segmen akhir klip belum tersedia di siaran live.');
            }
        }

        // 4. Construct custom .m3u8 playlist files
        $scratchDir = storage_path('app/scratch_playlists');
        if (!file_exists($scratchDir)) {
            @mkdir($scratchDir, 0755, true);
        }

        $videoPlaylistPath = $this->buildCustomM3u8($videoM3u8Content, $videoStreamUrl, $startSq, $endSq, $chunkDuration, $scratchDir, "clip_{$clip->id}_v.m3u8");
        $audioPlaylistPath = null;

        if ($audioStreamUrl) {
            try {
                $audioM3u8Content = $this->fetchPlaylist($audioStreamUrl);
            } catch (\Throwable) {
                $audioM3u8Content = null;
            }
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
     * Download an HLS playlist with a timeout (independent of allow_url_fopen).
     */
    protected function fetchPlaylist(string $url): string
    {
        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'])
            ->get($url);

        if (! $response->successful() || trim($response->body()) === '') {
            throw new \RuntimeException("Failed to download M3U8 manifest (HTTP {$response->status()}).");
        }

        return $response->body();
    }

    /**
     * Highest /sq/N segment number listed in a live playlist (the live edge).
     */
    public static function lastSequenceNumber(string $m3u8Content): ?int
    {
        if (preg_match_all('#/sq/(\d+)#', $m3u8Content, $m)) {
            return max(array_map('intval', $m[1]));
        }
        if (preg_match('/#EXT-X-MEDIA-SEQUENCE:(\d+)/', $m3u8Content, $seq)) {
            $count = preg_match_all('/^#EXTINF:/m', $m3u8Content);

            return (int) $seq[1] + max(0, $count - 1);
        }

        return null;
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
        // Safety net: on a live stream yt-dlp ignores --download-sections and records
        // from the live edge (footage after the chosen minute). Refuse live videos here;
        // live cuts belong to trimLiveStreamDvr.
        $command[] = '--match-filter';
        $command[] = '!is_live';
        $command[] = $clip->youtube_url;

        $process = $this->createProcess($command, 600);
        $process->run();

        $rejectedAsLive = str_contains($process->getOutput() . $process->getErrorOutput(), 'does not pass filter');
        if ($rejectedAsLive || ($process->isSuccessful() && (! file_exists($outputPath) || filesize($outputPath) === 0))) {
            throw new \RuntimeException('Video ini sedang live; potongan tidak diambil dari rekaman agar tidak merekam dari ujung live.');
        }

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
