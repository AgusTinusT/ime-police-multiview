<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NameSkeleton;
use Illuminate\Console\Command;

class ScanImpersonation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:scan-impersonation';

    /**
     * The console command description.
     */
    protected $description = 'Scan existing user accounts for homoglyphs, impersonation, or reserved keyword violations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== SASP Anti-Impersonation & Homoglyph Registry Scanner ===');
        $this->newLine();

        $users = User::all();
        $violations = [];
        $flagged = [];

        foreach ($users as $user) {
            // Update skeleton if missing
            $skeleton = NameSkeleton::make($user->name);
            if ($user->name_skeleton !== $skeleton) {
                $user->name_skeleton = $skeleton;
                $user->saveQuietly();
            }

            $check = NameSkeleton::checkName($user->name, $user->id);

            if ($check['blocked']) {
                $violations[] = [
                    'User ID' => $user->id,
                    'Name' => $user->name,
                    'Skeleton' => $skeleton,
                    'Role' => $user->role,
                    'Reason' => $check['reason'],
                ];
            } elseif ($check['flagged']) {
                $flagged[] = [
                    'User ID' => $user->id,
                    'Name' => $user->name,
                    'Skeleton' => $skeleton,
                    'Role' => $user->role,
                    'Warning' => $check['reason'],
                ];
            }
        }

        if (empty($violations) && empty($flagged)) {
            $this->info("✓ Clean! Scanned {$users->count()} users. No impersonations or suspicious homoglyphs found.");
            return Command::SUCCESS;
        }

        if (!empty($violations)) {
            $this->error("Found " . count($violations) . " critical impersonation conflicts:");
            $this->table(['User ID', 'Name', 'Skeleton', 'Role', 'Reason'], $violations);
        }

        if (!empty($flagged)) {
            $this->warn("Found " . count($flagged) . " suspicious fuzzy matches flagged for moderation review:");
            $this->table(['User ID', 'Name', 'Skeleton', 'Role', 'Warning'], $flagged);
        }

        return Command::SUCCESS;
    }
}
