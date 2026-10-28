<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BioSlugRedirect;
use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\OfficerClaim;
use App\Models\User;
use App\Services\BioProfileService;
use App\Services\NameSkeleton;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerTacticalBioLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup default config for tests if needed
    }

    /**
     * Test homoglyph and impersonation detection during registration.
     */
    public function test_registration_with_homoglyph_impersonation_is_blocked(): void
    {
        // Create an active officer
        Officer::create([
            'channel_id' => 'UC_TEST_101',
            'handle' => '@Rangeritemlucu',
            'streamer_name' => 'Arinnn',
            'officer_name' => 'Ranger Arin Luciana',
            'callsign' => '4-RANGER-02',
            'department' => 'SAPR',
            'rank' => 'Park Ranger',
            'is_active' => true,
        ]);

        // Attempt to register with visual lookalike name using 'I' instead of 'l'
        $response = $this->post('/register', [
            'name' => 'RangeritemIucu',
            'email' => 'fake@impersonator.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('users', ['email' => 'fake@impersonator.com']);
    }

    /**
     * Test admin direct binding of a user to an officer.
     */
    public function test_admin_can_bind_user_to_officer_and_derive_is_officer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'channel_id' => 'UC_EIDO_01',
            'handle' => '@EidoErtachta',
            'streamer_name' => "E'ido Ertachta",
            'officer_name' => "Ofc. E'ido Ertachta",
            'callsign' => '1-ADAM-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        $this->assertFalse($member->isOfficer());

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/bind-user", [
            'user_id' => $member->id,
            'reason' => 'Official command binding',
        ]);

        $response->assertOk();
        $officer->refresh();
        $member->refresh();

        $this->assertEquals($member->id, $officer->user_id);
        $this->assertTrue($member->isOfficer());
        $this->assertNotNull($officer->bioProfile);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.bind',
            'subject_id' => $officer->id,
        ]);
    }

    /**
     * Test public bio link page renders correctly with status and verified seal.
     */
    public function test_public_bio_link_page_displays_officer_info(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'user_id' => $user->id,
            'channel_id' => 'UC_LYONA_03',
            'handle' => '@Lyonablln',
            'streamer_name' => 'Lyona',
            'officer_name' => 'K9 Handler Lyona',
            'callsign' => 'K9-03',
            'department' => 'BCSO',
            'rank' => 'K9 Unit Handler',
            'is_active' => true,
        ]);

        $profile = BioProfileService::ensureBioProfile($officer);

        $response = $this->get("/@{$profile->slug}");

        $response->assertOk();
        $response->assertSee('K9 Handler Lyona');
    }

    /**
     * Test old slug redirects with 301 permanent redirect when slug is updated.
     */
    public function test_slug_update_creates_301_permanent_redirect(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $officer = Officer::create([
            'channel_id' => 'UC_BABON_02',
            'handle' => '@babonkingrp',
            'streamer_name' => 'BABON KING',
            'officer_name' => 'Sgt. Babon King',
            'callsign' => '1-LINCOLN-02',
            'department' => 'LSPD',
            'rank' => 'Sergeant',
            'is_active' => true,
        ]);

        $profile = BioProfileService::ensureBioProfile($officer);
        $oldSlug = $profile->slug;
        $newSlug = 'babon-patrol-hq';

        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/bio-profiles/{$profile->id}/slug", [
            'slug' => $newSlug,
            'reason' => 'Official rebrand',
        ]);

        $response->assertOk();

        // Visiting old slug must yield 301 redirect
        $redirectResponse = $this->get("/@{$oldSlug}");
        $redirectResponse->assertStatus(301);
        $redirectResponse->assertRedirect("/@{$newSlug}");
    }

    /**
     * Test allowlist enforcement on donation links.
     */
    public function test_donation_link_requires_allowed_domain_and_https(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'user_id' => $user->id,
            'channel_id' => 'UC_TETEW_14',
            'handle' => '@tetewwwwwww',
            'streamer_name' => 'tetewww',
            'officer_name' => 'Ofc. Tetewww',
            'callsign' => '1-ADAM-14',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        BioProfileService::ensureBioProfile($officer);

        // 1. Rejected: non-https
        $resNonHttps = $this->actingAs($user)->postJson('/api/v1/officer/bio-links', [
            'title' => 'Saweria Donasi',
            'link_type' => 'donation',
            'url' => 'http://saweria.co/tetew',
        ]);
        $resNonHttps->assertStatus(422);

        // 2. Rejected: unallowed phishing domain
        $resPhishing = $this->actingAs($user)->postJson('/api/v1/officer/bio-links', [
            'title' => 'Saweria Scam',
            'link_type' => 'donation',
            'url' => 'https://saweria-fake-phishing.com/tetew',
        ]);
        $resPhishing->assertStatus(422);

        // 3. Accepted: official saweria.co
        $resValid = $this->actingAs($user)->postJson('/api/v1/officer/bio-links', [
            'title' => 'Saweria Donasi',
            'link_type' => 'donation',
            'url' => 'https://saweria.co/tetew',
        ]);
        $resValid->assertStatus(201);
    }

    /**
     * Test anti-IDOR: Officer A cannot update or delete Officer B's link.
     */
    public function test_anti_idor_officer_cannot_modify_other_officer_links(): void
    {
        $userA = User::factory()->create(['role' => 'member']);
        $officerA = Officer::create([
            'user_id' => $userA->id,
            'channel_id' => 'UC_A',
            'handle' => '@officerA',
            'streamer_name' => 'Officer A',
            'officer_name' => 'Ofc. Alpha',
            'callsign' => '1-A-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profileA = BioProfileService::ensureBioProfile($officerA);
        $linkA = OfficerBioLink::create([
            'officer_bio_profile_id' => $profileA->id,
            'title' => 'Alpha Discord',
            'link_type' => 'discord',
            'url' => 'https://discord.gg/alpha',
        ]);

        $userB = User::factory()->create(['role' => 'member']);
        $officerB = Officer::create([
            'user_id' => $userB->id,
            'channel_id' => 'UC_B',
            'handle' => '@officerB',
            'streamer_name' => 'Officer B',
            'officer_name' => 'Ofc. Bravo',
            'callsign' => '1-B-01',
            'department' => 'BCSO',
            'rank' => 'Deputy',
            'is_active' => true,
        ]);
        BioProfileService::ensureBioProfile($officerB);

        // User B attempts to delete User A's link
        $response = $this->actingAs($userB)->deleteJson("/api/v1/officer/bio-links/{$linkA->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('officer_bio_links', ['id' => $linkA->id]);
    }

    /**
     * Test safe click redirect (/go/{link}) and telemetry recording.
     */
    public function test_safe_redirect_records_telemetry_and_redirects(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'user_id' => $user->id,
            'channel_id' => 'UC_BAL',
            'handle' => '@BALGAMING',
            'streamer_name' => 'BAL GAMING',
            'officer_name' => 'Deputy Bal Pratama',
            'callsign' => '2-BAKER-25',
            'department' => 'BCSO',
            'rank' => 'Deputy',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);
        $link = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Saweria Bal',
            'link_type' => 'donation',
            'url' => 'https://saweria.co/balgaming',
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);

        $response = $this->get("/go/{$link->id}");

        $response->assertStatus(302);
        $response->assertRedirect('https://saweria.co/balgaming');
        $this->assertDatabaseHas('bio_link_clicks', ['officer_bio_link_id' => $link->id]);
    }

    /**
     * Test admin kill switch (suspend profile) halts redirects and shows quarantine card.
     */
    public function test_admin_kill_switch_suspends_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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
