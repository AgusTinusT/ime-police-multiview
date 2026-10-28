<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BioSlugRedirect;
use App\Models\ClipperDelegation;
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

    /**
     * T-10: Unbind officer revokes clipper delegations and preserves clipper/admin role.
     */
    public function test_unbind_officer_revokes_delegations_and_preserves_clipper_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $clipperUser = User::factory()->create([
            'role' => 'clipper',
            'clipper_tier' => 'tier_1',
        ]);
        $officer = Officer::create([
            'channel_id' => 'UC_UNBIND_CLIPPER',
            'handle' => '@unbindclipper',
            'streamer_name' => 'Clipper Officer',
            'officer_name' => 'Ofc. Clipper Role',
            'callsign' => '1-C-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $clipperUser->id,
        ]);
        BioProfileService::ensureBioProfile($officer);

        // Add a delegation granted by this officer user
        $trustedClipper = User::factory()->create(['role' => 'clipper']);
        ClipperDelegation::create([
            'granted_by_user_id' => $clipperUser->id,
            'assigned_user_id' => $trustedClipper->id,
            'tier' => 'tier_1',
        ]);

        $this->assertEquals(1, ClipperDelegation::where('granted_by_user_id', $clipperUser->id)->count());

        // Unbind officer
        $response = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/unbind-user", [
            'reason' => 'Resignation from police department',
        ]);

        $response->assertOk();
        $officer->refresh();
        $clipperUser->refresh();

        $this->assertNull($officer->user_id);
        // Crucial test: clipper role must NOT be demoted to 'member'
        $this->assertEquals('clipper', $clipperUser->role);
        // Delegations must be revoked
        $this->assertEquals(0, ClipperDelegation::where('granted_by_user_id', $clipperUser->id)->count());
    }

    /**
     * T-10: Banned user cannot log in and API requests return 403.
     */
    public function test_banned_user_login_is_rejected_and_api_returns_403(): void
    {
        $bannedUser = User::factory()->create([
            'email' => 'banned_trooper@policeduty.com',
            'password' => bcrypt('password123'),
            'status' => 'banned',
        ]);

        // Attempt login -> validation error on email
        $response = $this->post('/login', [
            'email' => 'banned_trooper@policeduty.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Direct actingAs API request blocked by not_banned middleware -> 403
        $this->actingAs($bannedUser)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => 1,
            ])
            ->assertStatus(403);
    }

    /**
     * T-10: Homoglyphs Cyrillic and zero-width characters are blocked.
     */
    public function test_homoglyphs_cyrillic_and_zero_width_rejected(): void
    {
        // Cyrillic small letter a (\u0430) replacing Latin 'A' in SASP
        $cyrillicA = "\u{0430}";
        $checkCyrillic = NameSkeleton::checkName("S{$cyrillicA}SP");
        $this->assertTrue($checkCyrillic['blocked']);

        // Zero-width space (\u200B) is stripped and visual homoglyphs normalized
        $zeroWidth = "\u{200B}";
        $this->assertEquals('offlcertrooper', NameSkeleton::make("Officer{$zeroWidth}Trooper"));

        // Registration with Cyrillic homoglyph on reserved police keyword -> blocked
        $response = $this->post('/register', [
            'name' => "S{$cyrillicA}SP Trooper",
            'email' => 'cyrillic@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors('name');
    }

    /**
     * T-10: Safe redirect (/go/{link}) rejects banned, inactive, or suspended profile links.
     */
    public function test_safe_redirect_rejects_banned_inactive_or_suspended_links(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_REDIRECT_TEST',
            'handle' => '@redirecttest',
            'streamer_name' => 'Redirect Streamer',
            'officer_name' => 'Ofc. Redirect Test',
            'callsign' => '1-R-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);

        // 1. Inactive link -> 404
        $inactiveLink = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Inactive Link',
            'url' => 'https://discord.gg/test',
            'link_type' => 'discord',
            'sort_order' => 1,
            'is_active' => false,
            'moderation_status' => 'approved',
        ]);
        $this->get("/go/{$inactiveLink->id}")->assertStatus(404);

        // 2. Banned moderation status link -> 404
        $bannedLink = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Banned Link',
            'url' => 'https://discord.gg/banned',
            'link_type' => 'discord',
            'sort_order' => 2,
            'is_active' => true,
            'moderation_status' => 'banned',
        ]);
        $this->get("/go/{$bannedLink->id}")->assertStatus(404);

        // 3. Profile suspended -> 404
        $profile->update(['is_suspended' => true]);
        $validLink = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Valid Link on Suspended Profile',
            'url' => 'https://discord.gg/valid',
            'link_type' => 'discord',
            'sort_order' => 3,
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);
        $this->get("/go/{$validLink->id}")->assertStatus(404);
    }

    /**
     * T-10: Second claim for the same officer or by the same user is rejected.
     */
    public function test_second_claim_for_same_officer_or_user_is_rejected(): void
    {
        $userA = User::factory()->create(['role' => 'member']);
        $userB = User::factory()->create(['role' => 'member']);
        $officer = Officer::create([
            'channel_id' => 'UC_CLAIM_DUP',
            'handle' => '@claimdup',
            'streamer_name' => 'Dup Streamer',
            'officer_name' => 'Trooper Duplicate',
            'callsign' => '2-D-01',
            'department' => 'BCSO',
            'rank' => 'Deputy',
            'is_active' => true,
        ]);

        // User A submits claim -> 201
        $this->actingAs($userA)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $officer->id,
                'proof_discord_tag' => 'userA#0001',
            ])
            ->assertStatus(201);

        // User A submits another claim while first is pending -> 422
        $this->actingAs($userA)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $officer->id,
                'proof_discord_tag' => 'userA#0001',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('officer_id');

        // User B tries to claim the same officer that already has pending claim -> 422
        $this->actingAs($userB)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $officer->id,
                'proof_discord_tag' => 'userB#0002',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('officer_id');
    }

    /**
     * B-07: Partner recommendations hide inactive or suspended officers.
     */
    public function test_b07_partner_recommendations_hide_inactive_or_suspended_officers(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $mainOfficer = Officer::create([
            'channel_id' => 'UC_MAIN_SQUAD',
            'handle' => '@mainsquad',
            'streamer_name' => 'Main Officer',
            'officer_name' => 'Ofc. Squad Lead',
            'callsign' => '1-M-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        $mainProfile = BioProfileService::ensureBioProfile($mainOfficer);
        $mainProfile->update(['slug' => 'squad-lead-main']);

        // Inactive partner officer
        $inactivePartner = Officer::create([
            'channel_id' => 'UC_PARTNER_INACTIVE',
            'handle' => '@inactivepartner',
            'streamer_name' => 'Inactive Partner',
            'officer_name' => 'Ofc. Inactive Partner',
            'callsign' => '1-I-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => false,
        ]);

        // Suspended profile partner officer
        $suspendedPartner = Officer::create([
            'channel_id' => 'UC_PARTNER_SUSPENDED',
            'handle' => '@suspendedpartner',
            'streamer_name' => 'Suspended Partner',
            'officer_name' => 'Ofc. Suspended Partner',
            'callsign' => '1-S-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $suspendedProfile = BioProfileService::ensureBioProfile($suspendedPartner);
        $suspendedProfile->update(['is_suspended' => true]);

        // Add both recommendation links to main officer
        OfficerBioLink::create([
            'officer_bio_profile_id' => $mainProfile->id,
            'title' => 'Rekan Inactive',
            'link_type' => 'officer_recommendation',
            'recommended_officer_id' => $inactivePartner->id,
            'sort_order' => 1,
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);
        OfficerBioLink::create([
            'officer_bio_profile_id' => $mainProfile->id,
            'title' => 'Rekan Suspended',
            'link_type' => 'officer_recommendation',
            'recommended_officer_id' => $suspendedPartner->id,
            'sort_order' => 2,
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);

        // Call public bio page
        $response = $this->get('/@squad-lead-main');
        $response->assertOk();

        // In Inertia props, links should be filtered out
        $links = $response->viewData('page')['props']['links'];
        $this->assertCount(0, $links);
    }

    /**
     * B-08: Reorder rejects duplicate IDs.
     */
    public function test_b08_reorder_rejects_duplicate_ids(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_REORDER_DUP',
            'handle' => '@reorderdup',
            'streamer_name' => 'Reorder Streamer',
            'officer_name' => 'Ofc. Reorder Dup',
            'callsign' => '1-O-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);

        $link = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Discord Link',
            'url' => 'https://discord.gg/lspd',
            'link_type' => 'discord',
            'sort_order' => 1,
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);

        // Submitting duplicate IDs [id, id] -> 422 validation error
        $response = $this->actingAs($user)->postJson('/api/v1/officer/bio-links/reorder', [
            'ordered_ids' => [$link->id, $link->id],
        ]);

        $response->assertStatus(422);
    }

    /**
     * T-09: Live status polling API returns telemetry.
     */
    public function test_t09_live_status_polling_endpoint(): void
    {
        $officer = Officer::create([
            'channel_id' => 'UC_LIVE_POLL_TEST',
            'handle' => '@livepolltest',
            'streamer_name' => 'Live Poll Streamer',
            'officer_name' => 'Ofc. Live Poll',
            'callsign' => '1-L-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);
        $profile->update(['slug' => 'live-poll-test']);

        $response = $this->getJson("/api/v1/bio-links/live-poll-test/live-status");
        $response->assertOk();
        $response->assertJsonStructure([
            'is_live',
            'live_stream',
            'recommended_streams',
        ]);
    }

    /**
     * B-05: Common Indonesian names are allowed, while police authority keywords are blocked.
     */
    public function test_b05_common_names_allowed_and_authority_keywords_blocked(): void
    {
        $allowedNames = [
            'Saprudin',
            'Ahmad Mintoro',
            'Badminton Lover',
            'Siti Sapriyah',
        ];

        foreach ($allowedNames as $name) {
            $check = NameSkeleton::checkName($name);
            $this->assertFalse($check['blocked'], "Expected '{$name}' NOT to be blocked, but it was blocked: " . ($check['reason'] ?? ''));
        }

        $blockedNames = [
            'Admin SASP',
            'SASP Official',
            'LSPD Dispatcher',
            'Chief Moderator',
        ];

        foreach ($blockedNames as $name) {
            $check = NameSkeleton::checkName($name);
            $this->assertTrue($check['blocked'], "Expected '{$name}' to be blocked, but it was allowed.");
        }
    }

    /**
     * B-11: Click telemetry records privacy-preserving visitor_hash and referer_host.
     */
    public function test_b11_click_telemetry_stores_visitor_hash_and_referer_host(): void
    {
        $officer = Officer::create([
            'channel_id' => 'UC_TELEMETRY_LINK',
            'handle' => '@telemetrylink',
            'streamer_name' => 'Telemetry Streamer',
            'officer_name' => 'Ofc. Telemetry',
            'callsign' => '1-T-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);

        $link = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Trakteer Kami',
            'url' => 'https://trakteer.id/policeduty',
            'url_host' => 'trakteer.id',
            'link_type' => 'donation',
            'sort_order' => 1,
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);

        $response = $this->withHeaders([
            'referer' => 'https://twitter.com/sasp_updates/status/12345',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ])->get("/go/{$link->id}");

        $response->assertStatus(302);
        $response->assertRedirect('https://trakteer.id/policeduty');

        $this->assertDatabaseHas('bio_link_clicks', [
            'officer_bio_link_id' => $link->id,
            'referer_host' => 'twitter.com',
        ]);

        $click = \App\Models\BioLinkClick::where('officer_bio_link_id', $link->id)->first();
        $this->assertNotNull($click->visitor_hash);
        $this->assertEquals(64, strlen($click->visitor_hash));
    }

    /**
     * B-12: Page view counter works with database cache store and flush command.
     */
    public function test_b12_view_counter_works_with_database_cache_store(): void
    {
        // Switch cache store to database
        config(['cache.default' => 'database']);

        $officer = Officer::create([
            'channel_id' => 'UC_DB_CACHE_VIEW',
            'handle' => '@dbcacheview',
            'streamer_name' => 'DB Cache Streamer',
            'officer_name' => 'Ofc. DB Cache',
            'callsign' => '1-D-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);
        $profile->update(['slug' => 'db-cache-view-test']);

        // Visit public page twice
        $this->get('/@db-cache-view-test')->assertOk();
        $this->get('/@db-cache-view-test')->assertOk();

        // Run flush command
        $exitCode = \Illuminate\Support\Facades\Artisan::call('biolinks:flush-views');
        $this->assertEquals(0, $exitCode);

        // Assert views are recorded in bio_daily_stats
        $stat = \App\Models\BioDailyStat::where('officer_bio_profile_id', $profile->id)
            ->whereNull('officer_bio_link_id')
            ->first();

        $this->assertNotNull($stat);
        $this->assertEquals(now()->toDateString(), \Illuminate\Support\Carbon::parse($stat->stat_date)->toDateString());
        $this->assertGreaterThanOrEqual(2, $stat->views);
    }

    /**
     * T-04: Password update invalidates other sessions.
     */
    public function test_t04_password_change_logs_out_other_sessions(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('old-password'),
        ]);

        // Insert fake second session for user in sessions table
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'session_other_device_123',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'OtherBrowser/1.0',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => 'session_other_device_123']);

        // User updates password on current session
        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();

        // Assert other session was deleted
        $this->assertDatabaseMissing('sessions', ['id' => 'session_other_device_123']);
    }

    /**
     * B-04: Clipper request endpoint rejects 'officer' type.
     */
    public function test_b04_clipper_request_rejects_officer_type(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->postJson('/api/v1/clipper-requests', [
            'request_type' => 'officer',
            'channel_url' => 'https://youtube.com/@officer',
            'reason' => 'Ingin clipper officer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('request_type');
    }

    /**
     * T-14 & T-20: YouTube claim token generation and 3 tokens / 24h rate limit.
     */
    public function test_v3_youtube_token_claim_and_rate_limit(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $officer = Officer::create([
            'channel_id' => 'UC_YT_TOKEN_TEST',
            'handle' => '@yttokentest',
            'streamer_name' => 'Token Streamer',
            'officer_name' => 'Ofc. Token',
            'callsign' => '1-T-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        // 1st request -> success
        $res1 = $this->actingAs($user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $officer->id,
        ]);
        $res1->assertStatus(201);
        $res1->assertJsonStructure(['status', 'token', 'claim_id', 'expires_at']);
        $token = $res1->json('token');
        $this->assertStringStartsWith('SASP-VERIFY-', $token);

        // Claim record created in DB with hashed token
        $this->assertDatabaseHas('officer_claims', [
            'officer_id' => $officer->id,
            'user_id' => $user->id,
            'verification_method' => 'youtube_token',
            'status' => 'pending',
        ]);

        // 2nd request -> success
        $res2 = $this->actingAs($user)->postJson('/api/v1/officer-claims/youtube', ['officer_id' => $officer->id]);
        $res2->assertStatus(201);

        // 3rd request -> success
        $res3 = $this->actingAs($user)->postJson('/api/v1/officer-claims/youtube', ['officer_id' => $officer->id]);
        $res3->assertStatus(201);

        // 4th request within 24h -> 429 Throttle
        $res4 = $this->actingAs($user)->postJson('/api/v1/officer-claims/youtube', ['officer_id' => $officer->id]);
        $res4->assertStatus(429);
    }

    /**
     * T-14 & T-20: YouTube verification automated approval & rejection.
     */
    public function test_v3_youtube_token_verification_success_and_failure(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'role' => 'member']);
        $officer = Officer::create([
            'channel_id' => 'UC_VERIFY_SUCCESS',
            'handle' => '@verifysuccess',
            'streamer_name' => 'Success Streamer',
            'officer_name' => 'Ofc. Success',
            'callsign' => '1-S-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        $res = $this->actingAs($user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $officer->id,
        ]);
        $claimId = $res->json('claim_id');
        $token = $res->json('token');

        $hasToken = false;
        \Illuminate\Support\Facades\Http::fake(function (\Illuminate\Http\Client\Request $req) use (&$hasToken, $token) {
            $desc = $hasToken ? "Official channel. {$token} verified officer." : "Just playing games.";

            if (str_contains($req->url(), 'googleapis.com')) {
                return \Illuminate\Support\Facades\Http::response([
                    'items' => [
                        [
                            'snippet' => [
                                'description' => $desc,
                            ],
                        ],
                    ],
                ], 200);
            }

            return \Illuminate\Support\Facades\Http::response("<html><meta name=\"description\" content=\"{$desc}\"></html>", 200);
        });

        // Test 1: Fake YouTube response without token -> 422
        $verifyFail = $this->actingAs($user)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube");
        $verifyFail->assertStatus(422);

        // Officer still unclaimed
        $this->assertNull($officer->fresh()->user_id);

        // Test 2: Token is added to channel description -> 200 Success
        $hasToken = true;

        $verifySuccess = $this->actingAs($user)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube", [
            'token' => $token,
        ]);
        $verifySuccess->assertStatus(200);

        // Officer bound & user role preserved (B-14)
        $freshOfficer = $officer->fresh();
        $this->assertEquals($user->id, $freshOfficer->user_id);
        $this->assertEquals('member', $user->fresh()->role);
        $this->assertTrue($user->fresh()->isOfficer());
    }

    /**
     * B-14: Binding and unbinding an officer does not overwrite user's role (e.g. clipper) with 'officer' or 'member'.
     */
    public function test_b14_clipper_user_role_preserved_on_bind_and_unbind(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $clipperUser = User::factory()->create([
            'role' => 'clipper',
            'clipper_tier' => 'creator',
            'email_verified_at' => now(),
        ]);

        $officer = Officer::create([
            'channel_id' => 'UC_B14_CLIPPER_OFFICER',
            'handle' => '@b14clipperofficer',
            'streamer_name' => 'B14 Streamer',
            'officer_name' => 'Ofc. B14 Clipper',
            'callsign' => '1-B-14',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        // 1. Admin binds clipper user to officer
        $resBind = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/bind-user", [
            'user_id' => $clipperUser->id,
            'reason' => 'Binding clipper officer',
        ]);
        $resBind->assertStatus(200);

        $boundUser = $clipperUser->fresh();
        $this->assertEquals('clipper', $boundUser->role, 'User role must remain clipper after bind');
        $this->assertEquals('creator', $boundUser->clipper_tier);
        $this->assertTrue($boundUser->isOfficer(), 'isOfficer() must return true via officer relation');

        // 2. Admin unbinds officer
        $resUnbind = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/unbind-user", [
            'reason' => 'Unbinding clipper officer',
        ]);
        $resUnbind->assertStatus(200);

        $unboundUser = $clipperUser->fresh();
        $this->assertEquals('clipper', $unboundUser->role, 'User role must remain clipper after unbind');
        $this->assertEquals('creator', $unboundUser->clipper_tier, 'User clipper_tier must be preserved');
        $this->assertFalse($unboundUser->isOfficer(), 'isOfficer() must return false after unbind');
    }

    /**
     * T-16 & T-20: Self-Service Profile update whitelist and locked fields.
     */
    public function test_v3_self_service_profile_mutability_and_locks(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_LOCKED_CHANNEL_1',
            'handle' => '@officerlock',
            'streamer_name' => 'Old Streamer',
            'officer_name' => 'Ofc. Old Name',
            'callsign' => '1-L-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        BioProfileService::ensureBioProfile($officer);

        // Update profile
        $res = $this->actingAs($user)->putJson('/api/v1/officer/profile', [
            'officer_name' => 'Ofc. New Name',
            'streamer_name' => 'New Streamer Name',
            'patrol_zone' => 'Sandy Shores',
            'avatar_url' => 'https://example.com/avatar.png',
            'use_custom_avatar' => true,
            // Attacker attempts to change locked fields
            'channel_id' => 'UC_HACKED_CHANNEL',
            'department' => 'SASP',
            'rank' => 'Chief of Police',
        ]);

        $res->assertStatus(200);
        $fresh = $officer->fresh();

        $this->assertEquals('Ofc. New Name', $fresh->officer_name);
        $this->assertEquals('New Streamer Name', $fresh->streamer_name);
        $this->assertEquals('Sandy Shores', $fresh->patrol_zone);
        $this->assertTrue($fresh->avatar_is_custom);

        // Locked fields remain unchanged
        $this->assertEquals('UC_LOCKED_CHANNEL_1', $fresh->channel_id);
        $this->assertEquals('LSPD', $fresh->department);
        $this->assertEquals('Officer', $fresh->rank);

        // Suspended profile check (PRD Section 4.3 -> 423 Locked)
        $profile = $fresh->bioProfile;
        $profile->is_suspended = true;
        $profile->save();

        $this->actingAs($user)
            ->putJson('/api/v1/officer/profile', [
                'officer_name' => 'Ofc. Another Name',
            ])
            ->assertStatus(423);
    }

    /**
     * T-16 & T-20: Callsign 7-day cooldown and uniqueness enforcement.
     */
    public function test_v3_callsign_cooldown_and_uniqueness(): void
    {
        $user1 = User::factory()->create(['role' => 'officer']);
        $officer1 = Officer::create([
            'channel_id' => 'UC_CALLSIGN_1',
            'handle' => '@unit1',
            'streamer_name' => 'Unit 1',
            'officer_name' => 'Trooper One',
            'callsign' => '1-A-10',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user1->id,
        ]);

        $user2 = User::factory()->create(['role' => 'officer']);
        $officer2 = Officer::create([
            'channel_id' => 'UC_CALLSIGN_2',
            'handle' => '@unit2',
            'streamer_name' => 'Unit 2',
            'officer_name' => 'Trooper Two',
            'callsign' => '2-B-20',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user2->id,
        ]);

        // Attempting to take officer 2's callsign -> 422
        $this->actingAs($user1)->putJson('/api/v1/officer/profile', [
            'callsign' => '2-B-20',
        ])->assertStatus(422);

        // Updating to a new unique callsign -> 200
        $this->actingAs($user1)->putJson('/api/v1/officer/profile', [
            'callsign' => '1-A-99',
        ])->assertStatus(200);

        $this->assertEquals('1-A-99', $officer1->fresh()->callsign);
        $this->assertNotNull($officer1->fresh()->callsign_changed_at);

        // Trying to change callsign again immediately within 7 days -> 422
        $resCooldown = $this->actingAs($user1)->putJson('/api/v1/officer/profile', [
            'callsign' => '1-A-100',
        ]);
        $resCooldown->assertStatus(422);
        $resCooldown->assertJsonValidationErrors('callsign');
    }

    /**
     * T-17 & T-20: Rank & Agency change request lifecycle (Submit, Lock, Cancel, Approve, Reject).
     */
    public function test_v3_rank_and_agency_change_requests_flow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $officerUser = User::factory()->create(['role' => 'officer', 'email_verified_at' => now()]);

        $agency = \App\Models\Agency::create([
            'agency_code' => 'SASP',
            'agency_name' => 'San Andreas State Police',
            'jurisdiction' => 'Statewide',
        ]);

        $currentRank = \App\Models\Rank::create([
            'agency_id' => $agency->id,
            'rank_title' => 'Trooper',
            'level' => 2,
        ]);

        $targetRank = \App\Models\Rank::create([
            'agency_id' => $agency->id,
            'rank_title' => 'Sergeant',
            'level' => 4,
        ]);

        $officer = Officer::create([
            'channel_id' => 'UC_CHANGE_REQ',
            'handle' => '@changereq',
            'streamer_name' => 'Req Streamer',
            'officer_name' => 'Trooper Request',
            'callsign' => '3-T-01',
            'department' => 'SASP',
            'rank' => 'Trooper',
            'agency_id' => $agency->id,
            'rank_id' => $currentRank->id,
            'is_active' => true,
            'user_id' => $officerUser->id,
        ]);

        // 1. Submit Rank Change Request
        $res = $this->actingAs($officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => 'Sergeant',
            'reason' => 'Lulus diklat komando tingkat madya',
        ]);
        $res->assertStatus(201);
        $reqId = $res->json('change_request.id');

        // 2. Duplicate submission for rank_id while pending -> 422
        $resDuplicate = $this->actingAs($officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => 'Sergeant',
            'reason' => 'Duplicate request',
        ]);
        $resDuplicate->assertStatus(422);

        // 3. User cancels the request
        $this->actingAs($officerUser)->deleteJson("/api/v1/officer/change-requests/{$reqId}")
            ->assertStatus(200);
        $this->assertDatabaseHas('officer_change_requests', ['id' => $reqId, 'status' => 'cancelled']);

        // 4. Submit fresh request and Admin approves
        $resFresh = $this->actingAs($officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => 'Sergeant',
            'reason' => 'Lulus diklat madya sprin 123',
        ]);
        $freshReqId = $resFresh->json('change_request.id');

        $this->actingAs($admin)->postJson("/api/v1/admin/officer-change-requests/{$freshReqId}/approve", [
            'reason' => 'Pangkat disahkan oleh Komando',
        ])->assertStatus(200);

        // Officer rank is updated automatically
        $freshOfficer = $officer->fresh();
        $this->assertEquals('Sergeant', $freshOfficer->rank);
        $this->assertEquals($targetRank->id, $freshOfficer->rank_id);

        $this->assertDatabaseHas('officer_change_requests', [
            'id' => $freshReqId,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    /**
     * T-19 & T-20: Overwrite protection for claimed officers and custom avatars.
     */
    public function test_v3_overwrite_protection_on_import_and_sync(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $claimedOfficer = Officer::create([
            'channel_id' => 'UC_CLAIMED_PROTECT',
            'handle' => '@claimedprotect',
            'streamer_name' => 'Custom Streamer Name',
            'officer_name' => 'Custom RP Name',
            'callsign' => '1-C-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'avatar_url' => 'https://custom-avatar.com/pic.png',
            'avatar_is_custom' => true,
            'is_active' => true,
            'user_id' => $user->id,
        ]);

        // Attempting to overwrite via manage command (action 'add' for existing channel)
        \Illuminate\Support\Facades\Artisan::call('officer:manage', [
            'action' => 'add',
            'channel_id' => 'UC_CLAIMED_PROTECT',
            'handle' => '@claimedprotect',
            'streamer_name' => 'Overwritten Streamer',
            'officer_name' => 'Overwritten Name',
            'callsign' => '1-X-99',
            'department' => 'LSPD',
            'rank' => 'Cadet',
        ]);

        $fresh = $claimedOfficer->fresh();
        // Protection preserved the owner's custom data
        $this->assertEquals('Custom RP Name', $fresh->officer_name);
        $this->assertEquals('Custom Streamer Name', $fresh->streamer_name);
        $this->assertEquals('https://custom-avatar.com/pic.png', $fresh->avatar_url);
    }
}


