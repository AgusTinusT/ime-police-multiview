<?php

namespace Tests\Feature\BioLink\Concerns;

use App\Models\Agency;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\Rank;
use App\Models\User;
use App\Services\OfficerBindingService;

trait CreatesBioLinkFixtures
{
    protected function createVerifiedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role' => 'member',
        ], $attributes));
    }

    protected function createAdminUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role' => 'admin',
        ], $attributes));
    }

    protected function createOfficer(array $attributes = []): Officer
    {
        static $counter = 1;
        $idx = $counter++;

        return Officer::create(array_merge([
            'channel_id' => "UC_TEST_{$idx}",
            'handle' => "@officer_{$idx}",
            'streamer_name' => "Streamer {$idx}",
            'officer_name' => "Officer Name {$idx}",
            'callsign' => "10-{$idx}",
            'badge_number' => "{$idx}",
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ], $attributes));
    }

    protected function bindOfficer(Officer $officer, User $user, ?string $reason = null): array
    {
        return OfficerBindingService::bind($officer, $user, $reason ?? 'Test fixture binding');
    }
}
