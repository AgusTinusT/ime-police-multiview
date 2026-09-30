<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoClipJob;
use App\Models\VideoClip;
use App\Services\YouTubeScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VideoClipController extends Controller
{
    /**
     * Render the dedicated Tactical Video Clipper workspace page.
     */
    public function clipperPage(Request $request)
    {
        return Inertia::render('Clipper', [
            'initialUrl' => $request->query('url', ''),
        ]);
    }

    /**
     * Get list of video clips.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = VideoClip::with('user:id,name,email')
            ->latest();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $clips = $query->paginate(15)->through(function ($clip) {
            $clipArray = $clip->toArray();
            $fileExists = $clip->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($clip->file_path);
            $clipArray['file_exists'] = $fileExists;
            $clipArray['download_url'] = $fileExists && $clip->status === 'completed'
                ? asset('storage/' . $clip->file_path)
                : null;
            $clipArray['direct_download_url'] = $fileExists && $clip->status === 'completed'
                ? url('/api/v1/clips/' . $clip->id . '/download')
                : null;
            return $clipArray;
        });

        return response()->json($clips);
    }

    /**
     * Store and trigger new video trimming process.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'youtube_url' => 'required|url',
            'start_time' => 'required',
            'end_time' => 'required',
            'title' => 'nullable|string|max:255',
            'format' => 'nullable|string|in:MP4 1080p,MP4 720p,MP4 360p,MP3 Audio',
        ]);

        $startSeconds = $this->parseTimestampToSeconds($validated['start_time']);
        $endSeconds = $this->parseTimestampToSeconds($validated['end_time']);

        if ($startSeconds === null || $endSeconds === null) {
            throw ValidationException::withMessages([
                'start_time' => 'Format waktu tidak valid. Gunakan format Jam:Menit:Detik (contoh 00:01:30 atau 90).'
            ]);
        }

        if ($endSeconds <= $startSeconds) {
            throw ValidationException::withMessages([
                'end_time' => 'Waktu selesai (End Time) harus lebih besar dari waktu mulai (Start Time).'
            ]);
        }

        $durationSeconds = $endSeconds - $startSeconds;

        // Hard Limit: Maximum 10 Minutes (600 Seconds)
        if ($durationSeconds > 600) {
            throw ValidationException::withMessages([
                'end_time' => 'Durasi potongan maksimal adalah 10 menit (600 detik). Durasi Anda: ' . ceil($durationSeconds / 60) . ' menit.'
            ]);
        }

        // Clean YouTube URL & Block active live streams
        $cleanUrl = $this->cleanYoutubeUrl($validated['youtube_url']);
        $videoId = $this->extractVideoId($cleanUrl);

        if ($videoId) {
            $scraper = app(YouTubeScraperService::class);
            $telemetryMap = $scraper->getBatchStreamsTelemetry([$videoId]);
            $telemetry = $telemetryMap[$videoId] ?? null;
            if ($telemetry && ($telemetry['status'] ?? '') === 'LIVE') {
                throw ValidationException::withMessages([
                    'youtube_url' => 'Fitur Clipper saat ini belum mendukung pemotongan Siaran Langsung (LIVE Stream) yang sedang berlangsung. Silakan gunakan Video VOD / Rekaman YouTube.'
                ]);
            }
        }

        $clip = VideoClip::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?: 'Klip Video - ' . date('Y-m-d H:i'),
            'youtube_url' => $cleanUrl,
            'start_time' => $startSeconds,
            'end_time' => $endSeconds,
            'duration_seconds' => $durationSeconds,
            'format' => $validated['format'] ?? 'MP4 1080p',
            'status' => 'pending',
        ]);

        // Dispatch processing job asynchronously to Supervisor Queue Worker
        ProcessVideoClipJob::dispatch($clip)->onQueue('clipper');

        return response()->json([
            'message' => 'Proses pemotongan video telah dimasukkan ke dalam antrean.',
            'clip' => $clip,
        ], 201);
    }

    /**
     * Delete a video clip.
     */
    public function destroy(Request $request, $id)
    {
        $clip = VideoClip::findOrFail($id);

        if (!$request->user()->isAdmin() && $clip->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki izin untuk menghapus klip ini.'], 403);
        }

        if ($clip->file_path && Storage::disk('public')->exists($clip->file_path)) {
            Storage::disk('public')->delete($clip->file_path);
        }

        $clip->delete();

        return response()->json(['message' => 'Klip video berhasil dihapus.']);
    }

    /**
     * Force download the video clip file.
     */
    public function download(Request $request, $id)
    {
        $clip = VideoClip::findOrFail($id);

        if (!$request->user()->isAdmin() && $clip->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengunduh klip ini.'], 403);
        }

        if (!$clip->file_path || !Storage::disk('public')->exists($clip->file_path)) {
            return response()->json(['message' => 'File video tidak ditemukan.'], 404);
        }

        $absolutePath = Storage::disk('public')->path($clip->file_path);
        $ext = pathinfo($clip->file_path, PATHINFO_EXTENSION) ?: 'mp4';
        $downloadFileName = \Illuminate\Support\Str::slug($clip->title) . '.' . $ext;

        $contentType = match ($ext) {
            'mp3' => 'audio/mpeg',
            'gif' => 'image/gif',
            default => 'video/mp4',
        };

        return response()->download($absolutePath, $downloadFileName, [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * Helper to parse timestamp string (HH:MM:SS, MM:SS, or seconds) into integer seconds.
     */
    private function parseTimestampToSeconds($input): ?int
    {
        if (is_numeric($input)) {
            return (int) $input;
        }

        if (!is_string($input)) {
            return null;
        }

        $parts = array_map('intval', explode(':', trim($input)));
        $count = count($parts);

        if ($count === 3) { // HH:MM:SS
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } elseif ($count === 2) { // MM:SS
            return ($parts[0] * 60) + $parts[1];
        } elseif ($count === 1) { // SS
            return $parts[0];
        }

        return null;
    }

    /**
     * Validate YouTube URL and detect whether it is a VOD or Live Stream.
     */
    public function checkUrl(Request $request, YouTubeScraperService $scraper)
    {
        $validated = $request->validate([
            'url' => 'required|string',
        ]);

        $rawUrl = trim($validated['url']);
        $videoId = $this->extractVideoId($rawUrl);

        if (!$videoId) {
            return response()->json([
                'valid' => false,
                'message' => 'URL YouTube tidak valid. Mohon masukkan URL video atau live stream yang sah.',
            ], 422);
        }

        $cleanUrl = 'https://www.youtube.com/watch?v=' . $videoId;

        // Fetch live telemetry status via YouTubeScraperService
        $telemetryMap = $scraper->getBatchStreamsTelemetry([$videoId]);
        $telemetry = $telemetryMap[$videoId] ?? null;

        $isLive = ($telemetry && ($telemetry['status'] ?? '') === 'LIVE');
        $videoType = $isLive ? 'LIVE' : 'VOD';
        $typeLabel = $isLive ? '🔴 Siaran Langsung (LIVE Stream)' : '🎬 Video Rekaman (VOD)';

        $title = $telemetry['title'] ?? null;
        $author = null;
        $thumbnailUrl = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";

        // Fetch oEmbed metadata for accurate title and author name
        try {
            $oembedRes = Http::timeout(4)->get('https://www.youtube.com/oembed', [
                'url' => $cleanUrl,
                'format' => 'json',
            ]);
            if ($oembedRes->successful()) {
                $oembedData = $oembedRes->json();
                if (empty($title)) {
                    $title = $oembedData['title'] ?? null;
                }
                $author = $oembedData['author_name'] ?? null;
                $thumbnailUrl = $oembedData['thumbnail_url'] ?? $thumbnailUrl;
            }
        } catch (\Throwable $e) {
            // Silently fallback if oEmbed fails
        }

        return response()->json([
            'valid' => true,
            'can_trim' => !$isLive,
            'video_id' => $videoId,
            'clean_url' => $cleanUrl,
            'is_live' => $isLive,
            'video_type' => $videoType,
            'type_label' => $typeLabel,
            'title' => $title ?: "YouTube Video Stream [{$videoId}]",
            'channel_name' => $author ?: 'YouTube Channel',
            'thumbnail_url' => $thumbnailUrl,
            'viewers_count' => $telemetry['viewers_count'] ?? 0,
            'message' => $isLive
                ? '⚠️ Siaran Langsung (LIVE Stream) belum didukung. Fitur Clipper saat ini khusus untuk Video VOD / Rekaman YouTube. Silakan tunggu hingga siaran selesai.'
                : 'Terseteksi: Link ini adalah Video Rekaman (VOD). Pemotongan presisi siap diproses.',
        ]);
    }

    /**
     * Helper to extract 11-character YouTube Video ID from any format.
     */
    private function extractVideoId(string $url): ?string
    {
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|live|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Helper to clean YouTube URL.
     */
    private function cleanYoutubeUrl(string $url): string
    {
        $id = $this->extractVideoId($url);
        return $id ? 'https://www.youtube.com/watch?v=' . $id : $url;
    }
}
