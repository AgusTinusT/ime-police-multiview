<?php

namespace App\Console\Commands;

use App\Models\VideoClip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneExpiredVideoClips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clips:prune {--hours=1 : Age of clips in hours to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune video clips and associated files created older than specified hours (default 1 hour)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $expiredClips = VideoClip::where('created_at', '<', $cutoff)->get();

        $count = 0;
        foreach ($expiredClips as $clip) {
            if ($clip->file_path && Storage::disk('public')->exists($clip->file_path)) {
                Storage::disk('public')->delete($clip->file_path);
            }
            $clip->delete();
            $count++;
        }

        $this->info("Successfully pruned {$count} video clip(s) older than {$hours} hour(s).");
        return 0;
    }
}
