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
        $resNonHttps = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/officer/bio-links', [
                'title' => 'Saweria Donasi',
                'link_type' => 'donation',
                'url' => 'http://saweria.co/tetew',
            ]);
        $resNonHttps->assertStatus(422);

        // 2. Rejected: unallowed phishing domain
        $resPhishing = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/officer/bio-links', [
                'title' => 'Saweria Scam',
                'link_type' => 'donation',
                'url' => 'https://saweria-fake-phishing.com/tetew',
            ]);
        $resPhishing->assertStatus(422);

        // 3. Accepted: official saweria.co
        $resValid = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/officer/bio-links', [
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

    /**
     * B-01: Admin can approve claim, which binds officer and logs audit.
     */
    public function test_claim_approval_binds_officer_and_records_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'channel_id' => 'UC_CLAIM_TEST',
            'handle' => '@claimtest',
            'streamer_name' => 'Claim Streamer',
            'officer_name' => 'Trooper Claim',
            'callsign' => '3-V-99',
            'department' => 'SASP',
            'rank' => 'Trooper',
            'is_active' => true,
        ]);

        $claim = OfficerClaim::create([
            'user_id' => $member->id,
            'officer_id' => $officer->id,
            'verification_method' => 'manual_admin',
            'proof_discord_tag' => 'claim#1234',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/officer-claims/{$claim->id}/approve", [
            'reason' => 'Identity verified via Discord voice',
        ]);

        $response->assertOk();
        $officer->refresh();
        $claim->refresh();

        $this->assertEquals($member->id, $officer->user_id);
        $this->assertEquals('approved', $claim->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'claim.approved',
            'subject_id' => $claim->id,
        ]);
    }

    /**
     * B-01: Rejecting claim requires valid reason.
     */
    public function test_claim_rejection_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'channel_id' => 'UC_REJECT_TEST',
            'handle' => '@rejecttest',
            'streamer_name' => 'Reject Streamer',
            'officer_name' => 'Ofc. Reject',
            'callsign' => '1-R-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        $claim = OfficerClaim::create([
            'user_id' => $member->id,
            'officer_id' => $officer->id,
            'verification_method' => 'manual_admin',
            'proof_discord_tag' => 'reject#1234',
            'status' => 'pending',
        ]);

        // Attempt rejection without reason -> 422
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/officer-claims/{$claim->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        // With reason -> 200
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/officer-claims/{$claim->id}/reject", ['reason' => 'Discord tag tidak cocok'])
            ->assertOk();

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
    }

    /**
     * B-01 & B-02: Approving claim or binding officer fails if officer already bound.
     */
    public function test_claim_approval_and_bind_fails_if_officer_already_bound(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $boundUser = User::factory()->create(['role' => 'officer']);
        $secondUser = User::factory()->create(['role' => 'member']);

        $officer = Officer::create([
            'channel_id' => 'UC_ALREADY_BOUND',
            'handle' => '@alreadybound',
            'streamer_name' => 'Already Bound',
            'officer_name' => 'Sgt. Already Bound',
            'callsign' => '1-L-55',
            'department' => 'LSPD',
            'rank' => 'Sergeant',
            'is_active' => true,
            'user_id' => $boundUser->id,
        ]);

        // Direct bind attempt -> 422
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/officers/{$officer->id}/bind-user", ['user_id' => $secondUser->id])
            ->assertStatus(422);

        // Claim approve attempt -> 422
        $claim = OfficerClaim::create([
            'user_id' => $secondUser->id,
            'officer_id' => $officer->id,
            'verification_method' => 'manual_admin',
            'proof_discord_tag' => 'second#1234',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/officer-claims/{$claim->id}/approve")
            ->assertStatus(422);
    }

    /**
     * B-03: Fallback handle redirects 301 to profile slug, or 404 if no profile.
     */
    public function test_fallback_handle_redirects_301_to_profile_slug(): void
    {
        $officerWithProfile = Officer::create([
            'channel_id' => 'UC_HANDLE_301',
            'handle' => '@handle301',
            'streamer_name' => 'Handle 301 Streamer',
            'officer_name' => 'Ofc. Handle 301',
            'callsign' => '1-H-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officerWithProfile);
        $profile->update(['slug' => 'customofficerslug301']);

        // Requesting via handle -> 301 to /@{slug}
        $response = $this->get('/@handle301');
        $response->assertStatus(301);
        $response->assertRedirect('/@customofficerslug301');

        // Requesting unknown handle -> 404
        $this->get('/@nonexistenthandle')->assertStatus(404);
    }

    /**
     * B-04: Approving clipper request does not overwrite role of an officer.
     */
    public function test_clipper_request_approval_does_not_overwrite_officer_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $officerUser = User::factory()->create(['role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_CLIPPER_OFFICER',
            'handle' => '@clipperofficer',
            'streamer_name' => 'Clipper Officer',
            'officer_name' => 'Ofc. Clipper',
            'callsign' => '1-C-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $officerUser->id,
        ]);

        $clipperReq = \App\Models\ClipperRequest::create([
            'user_id' => $officerUser->id,
            'request_type' => 'creator',
            'channel_url' => 'https://youtube.com/@clipperofficer',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/clipper-requests/admin/{$clipperReq->id}/approve")
            ->assertOk();

        $officerUser->refresh();
        $this->assertTrue($officerUser->isOfficer());
        $this->assertEquals('officer', $officerUser->role);
        $this->assertEquals('creator', $officerUser->clipper_tier);
    }

    /**
     * B-05: Substring matching blocks reserved keywords in NameSkeleton.
     */
    public function test_reserved_keyword_substring_matching_blocks_impersonation(): void
    {
        $check1 = NameSkeleton::checkName('SASP Official');
        $this->assertTrue($check1['blocked']);

        $check2 = NameSkeleton::checkName('Admin SASP');
        $this->assertTrue($check2['blocked']);

        $check3 = NameSkeleton::checkName('0fflclal Patrol');
        $this->assertTrue($check3['blocked']);
    }

    /**
     * T-02: Unverified users cannot access officer dashboard or submit claims.
     */
    public function test_unverified_email_cannot_access_officer_dashboard_or_submit_claim(): void
    {
        $unverifiedOfficerUser = User::factory()->unverified()->create(['role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_UNVERIFIED_OFC',
            'handle' => '@unverifiedofc',
            'streamer_name' => 'Unverified Streamer',
            'officer_name' => 'Ofc. Unverified',
            'callsign' => '1-U-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $unverifiedOfficerUser->id,
        ]);
        BioProfileService::ensureBioProfile($officer);

        // Accessing dashboard -> redirected to verification notice
        $this->actingAs($unverifiedOfficerUser)
            ->get('/dashboard/bio-links')
            ->assertRedirect(route('verification.notice'));

        // Submitting claim with unverified account -> 403
        $unverifiedMember = User::factory()->unverified()->create(['role' => 'member']);
        $unclaimedOfficer = Officer::create([
            'channel_id' => 'UC_UNCLAIMED_TEST',
            'handle' => '@unclaimedtest',
            'streamer_name' => 'Unclaimed Streamer',
            'officer_name' => 'Trooper Unclaimed',
            'callsign' => '3-U-99',
            'department' => 'SASP',
            'rank' => 'Trooper',
            'is_active' => true,
        ]);

        $this->actingAs($unverifiedMember)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $unclaimedOfficer->id,
                'proof_discord_tag' => 'unverified#0001',
            ])
            ->assertStatus(403);
    }

    /**
     * T-03: Donation link requires confirmed password within 15 minutes.
     */
    public function test_donation_link_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'role' => 'officer',
        ]);
        $officer = Officer::create([
            'channel_id' => 'UC_PASSWORD_CONFIRM',
            'handle' => '@pwconfirm',
            'streamer_name' => 'PW Streamer',
            'officer_name' => 'Ofc. PW Confirm',
            'callsign' => '1-P-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        BioProfileService::ensureBioProfile($officer);

        // Attempting to add donation link without confirmation -> 423
        $this->actingAs($user)
            ->postJson('/api/v1/officer/bio-links', [
                'link_type' => 'donation',
                'title' => 'Saweria Donasi',
                'url' => 'https://saweria.co/pwconfirm',
            ])
            ->assertStatus(423)
            ->assertJson(['error' => 'password_confirmation_required']);

        // With inline password confirmation -> 201
        $this->actingAs($user)
            ->postJson('/api/v1/officer/bio-links', [
                'link_type' => 'donation',
                'title' => 'Saweria Donasi',
                'url' => 'https://saweria.co/pwconfirm',
                'password_confirmation_inline' => 'secret123',
            ])
            ->assertStatus(201);
    }

    /**
     * SEC-01: Public chat messages query does not leak user emails.
     */
    public function test_public_chat_messages_do_not_leak_user_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Secret Officer',
            'email' => 'very_secret_officer_email@policeduty.com',
        ]);

        \App\Models\ChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Laporan 10-8 patroli aktif.',
        ]);

        $response = $this->getJson('/api/v1/chat/messages');
        $response->assertOk();
        $response->assertDontSee('very_secret_officer_email@policeduty.com');
        $response->assertSee('Secret Officer');
    }
}
