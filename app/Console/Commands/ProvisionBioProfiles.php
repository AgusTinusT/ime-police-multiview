<?php

namespace App\Console\Commands;

use App\Models\Officer;
use App\Services\BioProfileService;
use Illuminate\Console\Command;

class ProvisionBioProfiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'officers:provision-bio-profiles';

    /**
     * The console command description.
     */
    protected $description = 'Provision initial tactical bio profiles and unique slugs for all active officers in the roster';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Provisioning Tactical Bio Profiles ===');
        $this->newLine();

        $officers = Officer::where('is_active', true)->get();
        $bar = $this->output->createProgressBar($officers->count());

        $created = 0;
        $existing = 0;

        foreach ($officers as $officer) {
            $isNew = !$officer->bioProfile()->exists();
            $profile = BioProfileService::ensureBioProfile($officer);

            if ($isNew) {
                $created++;
            } else {
                $existing++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully provisioned: {$created} new profiles created, {$existing} existing verified. Total: {$officers->count()} officers.");
        return Command::SUCCESS;
    }
}
