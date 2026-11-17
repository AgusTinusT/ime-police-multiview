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
        $this->onQueue('clipper');
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\YouTubeClipperService $clipperService): void
    {
        $this->videoClip->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $relativeFilePath = $clipperService->trimClip($this->videoClip);

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
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }
}
