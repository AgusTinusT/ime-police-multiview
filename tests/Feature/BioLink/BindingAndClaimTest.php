<?php

namespace Tests\Feature\BioLink;

use App\Models\ClipperDelegation;
use App\Models\ClipperRequest;
use App\Models\Officer;
use App\Models\OfficerClaim;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class BindingAndClaimTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_admin_can_bind_user_to_officer_and_derive_is_officer(): void
    {
        $admin = $this->createAdminUser();
        $member = $this->createVerifiedUser(['role' => 'member']);
        $officer = $this->createOfficer();

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

    public function test_claim_approval_binds_officer_and_records_audit_log(): void
    {
        $admin = $this->createAdminUser();
        $member = $this->createVerifiedUser(['role' => 'member']);
        $officer = $this->createOfficer();

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

    public function test_claim_rejection_requires_reason(): void
    {
        $admin = $this->createAdminUser();
        $member = $this->createVerifiedUser(['role' => 'member']);
        $officer = $this->createOfficer();

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

    public function test_claim_approval_and_bind_fails_if_officer_already_bound(): void
    {
        $admin = $this->createAdminUser();
        $boundUser = $this->createVerifiedUser(['role' => 'officer']);
        $secondUser = $this->createVerifiedUser(['role' => 'member']);
        $officer = $this->createOfficer(['user_id' => $boundUser->id]);

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

    public function test_clipper_request_approval_does_not_overwrite_officer_role(): void
    {
        $admin = $this->createAdminUser();
        $officerUser = $this->createVerifiedUser(['role' => 'officer']);
        $officer = $this->createOfficer(['user_id' => $officerUser->id]);

        $clipperReq = ClipperRequest::create([
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

    public function test_unbind_officer_revokes_delegations_and_preserves_clipper_role(): void
    {
        $admin = $this->createAdminUser();
        $clipperUser = $this->createVerifiedUser([
            'role' => 'clipper',
            'clipper_tier' => 'tier_1',
        ]);
        $officer = $this->createOfficer(['user_id' => $clipperUser->id]);
        BioProfileService::ensureBioProfile($officer);

        $trustedClipper = $this->createVerifiedUser(['role' => 'clipper']);
        ClipperDelegation::create([
            'granted_by_user_id' => $clipperUser->id,
            'assigned_user_id' => $trustedClipper->id,
            'tier' => 'tier_1',
        ]);

        $this->assertEquals(1, ClipperDelegation::where('granted_by_user_id', $clipperUser->id)->count());

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/unbind-user", [
            'reason' => 'Resignation from police department',
        ]);

        $response->assertOk();
        $officer->refresh();
        $clipperUser->refresh();

        $this->assertNull($officer->user_id);
        $this->assertEquals('clipper', $clipperUser->role);
        $this->assertEquals(0, ClipperDelegation::where('granted_by_user_id', $clipperUser->id)->count());
    }

    public function test_second_claim_for_same_officer_or_user_is_rejected(): void
    {
        $userA = $this->createVerifiedUser();
        $userB = $this->createVerifiedUser();
        $officer = $this->createOfficer();

        $this->actingAs($userA)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $officer->id,
                'proof_discord_tag' => 'userA#0001',
            ])
            ->assertStatus(201);

        $this->actingAs($userA)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $officer->id,
                'proof_discord_tag' => 'userA#0001',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('officer_id');

        $this->actingAs($userB)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => $officer->id,
                'proof_discord_tag' => 'userB#0002',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('officer_id');
    }

    public function test_b04_clipper_request_rejects_officer_type(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->postJson('/api/v1/clipper-requests', [
            'request_type' => 'officer',
            'channel_url' => 'https://youtube.com/@officer',
            'reason' => 'Ingin clipper officer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('request_type');
    }

    public function test_b14_clipper_user_role_preserved_on_bind_and_unbind(): void
    {
        $admin = $this->createAdminUser();
        $clipperUser = $this->createVerifiedUser([
            'role' => 'clipper',
            'clipper_tier' => 'creator',
        ]);
        $officer = $this->createOfficer();

        // 1. Admin binds clipper user to officer
        $resBind = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/bind-user", [
            'user_id' => $clipperUser->id,
            'reason' => 'Binding clipper officer',
        ]);
        $resBind->assertStatus(200);

        $boundUser = $clipperUser->fresh();
        $this->assertEquals('clipper', $boundUser->role);
        $this->assertEquals('creator', $boundUser->clipper_tier);
        $this->assertTrue($boundUser->isOfficer());

        // 2. Admin unbinds officer
        $resUnbind = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/unbind-user", [
            'reason' => 'Unbinding clipper officer',
        ]);
        $resUnbind->assertStatus(200);

        $unboundUser = $clipperUser->fresh();
        $this->assertEquals('clipper', $unboundUser->role);
        $this->assertEquals('creator', $unboundUser->clipper_tier);
        $this->assertFalse($unboundUser->isOfficer());
    }
}
