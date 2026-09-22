<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoClipJob;
use App\Models\VideoClip;
use Illuminate\Http\Request;
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
            $clipArray['download_url'] = $clip->file_path && $clip->status === 'completed'
                ? asset('storage/' . $clip->file_path)
                : null;
            $clipArray['direct_download_url'] = $clip->file_path && $clip->status === 'completed'
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

        // Clean YouTube URL
        $cleanUrl = $this->cleanYoutubeUrl($validated['youtube_url']);

        $clip = VideoClip::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?: 'Klip Video - ' . date('Y-m-d H:i'),
            'youtube_url' => $cleanUrl,
            'start_time' => $startSeconds,
            'end_time' => $endSeconds,
            'duration_seconds' => $durationSeconds,
            'status' => 'pending',
        ]);

        // Dispatch processing job asynchronously to Supervisor Queue Worker
        ProcessVideoClipJob::dispatch($clip);

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
        $downloadFileName = \Illuminate\Support\Str::slug($clip->title) . '.mp4';

        return response()->download($absolutePath, $downloadFileName, [
            'Content-Type' => 'video/mp4',
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
     * Helper to clean YouTube URL.
     */
    private function cleanYoutubeUrl(string $url): string
    {
        // Simple extraction for standard YouTube links
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            return 'https://www.youtube.com/watch?v=' . $matches[1];
        }
        return $url;
    }
}
