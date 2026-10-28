<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_admin_kill_switch_suspends_profile(): void
    {
        $admin = $this->createAdminUser();
        $officer = Officer::create([
            'channel_id' => 'UC_SUSPEND_TEST',
            'handle' => '@suspectofficer',
            'streamer_name' => 'Suspect Streamer',
            'officer_name' => 'Ofc. Suspect',
            'callsign' => '1-S-99',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);
        $link = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Suspicious Link',
            'link_type' => 'custom',
            'url' => 'https://example.com/test',
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);

        // Admin triggers kill switch
        $this->actingAs($admin)->patchJson("/api/v1/admin/bio-profiles/{$profile->id}/suspend", [
            'is_suspended' => true,
            'reason' => 'Phishing investigation',
        ])->assertOk();

        $profile->refresh();
        $this->assertTrue($profile->is_suspended);

        // Click redirect must now return 404
        $this->get("/go/{$link->id}")->assertStatus(404);

        // Public page renders quarantine card with noindex
        $pageResponse = $this->get("/@{$profile->slug}");
        $pageResponse->assertOk();
        $pageResponse->assertSee('DINONAKTIFKAN');
        $pageResponse->assertSee('noindex');
    }
}
