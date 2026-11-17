<?php

namespace Tests\Feature\BioLink;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\OfficerChangeRequest;
use App\Models\Rank;
use App\Models\User;
use App\Services\OfficerBindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officerUser;
    private Officer $officer;
    private Agency $lspd;
    private Agency $bcso;
    private Rank $sergeantLspd;
    private Rank $deputyBcso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->officerUser = User::factory()->create([
            'role' => 'member',
            'email_verified_at' => now(),
        ]);

        $this->lspd = Agency::create([
            'agency_name' => 'Los Santos Police Department',
            'agency_code' => 'LSPD',
            'badge_logo_url' => 'https://example.com/lspd.png',
            'is_active' => true,
        ]);

        $this->bcso = Agency::create([
            'agency_name' => 'Blaine County Sheriff Office',
            'agency_code' => 'BCSO',
            'badge_logo_url' => 'https://example.com/bcso.png',
            'is_active' => true,
        ]);

        $this->sergeantLspd = Rank::create([
            'agency_id' => $this->lspd->id,
            'rank_title' => 'Sergeant',
            'level' => 3,
        ]);

        $this->deputyBcso = Rank::create([
            'agency_id' => $this->bcso->id,
            'rank_title' => 'Deputy',
            'level' => 2,
        ]);

        $this->officer = Officer::create([
            'user_id' => $this->officerUser->id,
            'officer_name' => 'John Rookie',
            'streamer_name' => 'Streamer John',
            'handle' => 'johnrookie',
            'channel_id' => 'UC_john_rookie',
            'agency_id' => $this->lspd->id,
            'department' => 'LSPD',
            'rank' => 'Cadet',
            'callsign' => '101',
            'badge_number' => '101',
            'is_active' => true,
        ]);

        OfficerBioProfile::create([
            'officer_id' => $this->officer->id,
            'slug' => 'johnrookie',
            'is_active' => true,
        ]);
    }

    public function test_owner_submits_rank_request_and_admin_approves(): void
    {
        $response = $this->actingAs($this->officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'reason' => 'Promotion passed examination',
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('change_request.id');

        $this->assertDatabaseHas('officer_change_requests', [
            'id' => $requestId,
            'officer_id' => $this->officer->id,
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'status' => 'pending',
            'pending_lock' => "{$this->officer->id}:rank_id",
        ]);

        // Admin approves
        $approveResponse = $this->actingAs($this->admin)->postJson("/api/v1/admin/officer-change-requests/{$requestId}/approve", [
            'reason' => 'Congratulations on the promotion',
        ]);

        $approveResponse->assertStatus(200);

        $this->officer->refresh();
        $this->assertEquals($this->sergeantLspd->id, $this->officer->rank_id);
        $this->assertEquals('Sergeant', $this->officer->rank);

        $this->assertDatabaseHas('officer_change_requests', [
            'id' => $requestId,
            'status' => 'approved',
            'pending_lock' => null,
            'reviewed_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.change_approved',
            'subject_id' => $requestId,
        ]);
    }

    public function test_rank_from_different_agency_is_rejected_at_submission(): void
    {
        $response = $this->actingAs($this->officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $this->deputyBcso->id,
            'reason' => 'Transferring across agencies',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_value']);
    }

    public function test_duplicate_pending_request_for_same_field_is_rejected(): void
    {
        OfficerChangeRequest::create([
            'officer_id' => $this->officer->id,
            'requested_by' => $this->officerUser->id,
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'reason' => 'Submitting duplicate',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['field']);
    }

    public function test_admin_approving_stale_request_is_rejected(): void
    {
        $changeRequest = OfficerChangeRequest::create([
            'officer_id' => $this->officer->id,
            'requested_by' => $this->officerUser->id,
            'field' => 'rank_id',
            'old_value' => '999', // Simulate old rank ID was 999
            'new_value' => (string) $this->sergeantLspd->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/officer-change-requests/{$changeRequest->id}/approve");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Pengajuan ditolak otomatis karena data perwira sudah diubah sebelumnya (Stale).']);

        $changeRequest->refresh();
        $this->assertEquals('rejected', $changeRequest->status);
        $this->assertNull($changeRequest->pending_lock);
    }

    public function test_agency_approval_resets_mismatched_rank(): void
    {
        // Officer initially has Sergeant rank in LSPD
        $this->officer->update([
            'agency_id' => $this->lspd->id,
            'department' => 'LSPD',
            'rank_id' => $this->sergeantLspd->id,
            'rank' => 'Sergeant',
        ]);

        $changeRequest = OfficerChangeRequest::create([
            'officer_id' => $this->officer->id,
            'requested_by' => $this->officerUser->id,
            'field' => 'agency_id',
            'old_value' => (string) $this->lspd->id,
            'new_value' => (string) $this->bcso->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/officer-change-requests/{$changeRequest->id}/approve");

        $response->assertStatus(200);

        $this->officer->refresh();
        $this->assertEquals($this->bcso->id, $this->officer->agency_id);
        $this->assertEquals('BCSO', $this->officer->department);
        $this->assertNull($this->officer->rank_id, 'Rank ID should be null because Sergeant belongs to LSPD not BCSO');
        $this->assertEquals('Officer', $this->officer->rank);
    }

    public function test_reject_without_reason_or_short_reason_returns_422(): void
    {
        $changeRequest = OfficerChangeRequest::create([
            'officer_id' => $this->officer->id,
            'requested_by' => $this->officerUser->id,
            'field' => 'rank_id',
            'old_value' => null,
            'new_value' => (string) $this->sergeantLspd->id,
            'status' => 'pending',
        ]);

        // Empty reason
        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/officer-change-requests/{$changeRequest->id}/reject", [
            'reason' => '',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['reason']);

        // Less than 5 characters
        $responseShort = $this->actingAs($this->admin)->postJson("/api/v1/admin/officer-change-requests/{$changeRequest->id}/reject", [
            'reason' => 'bad',
        ]);
        $responseShort->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    public function test_non_owner_cannot_cancel_change_request(): void
    {
        $changeRequest = OfficerChangeRequest::create([
            'officer_id' => $this->officer->id,
            'requested_by' => $this->officerUser->id,
            'field' => 'rank_id',
            'old_value' => null,
            'new_value' => (string) $this->sergeantLspd->id,
            'status' => 'pending',
        ]);

        $stranger = User::factory()->create(['email_verified_at' => now(), 'role' => 'member']);

        $response = $this->actingAs($stranger)->deleteJson("/api/v1/officer/change-requests/{$changeRequest->id}");
        $response->assertStatus(403);
    }

    public function test_legacy_officer_without_rank_id_is_not_rejected_as_stale(): void
    {
        // Legacy imported officer has department & rank string, but null agency_id and null rank_id
        $this->officer->update([
            'agency_id' => null,
            'rank_id' => null,
            'department' => 'LSPD',
            'rank' => 'Officer',
        ]);

        $response = $this->actingAs($this->officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'reason' => 'Legacy officer applying for rank',
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('change_request.id');

        // Approve should succeed without being falsely marked as stale
        $approveResponse = $this->actingAs($this->admin)->postJson("/api/v1/admin/officer-change-requests/{$requestId}/approve");
        $approveResponse->assertStatus(200);

        $this->officer->refresh();
        $this->assertEquals($this->sergeantLspd->id, $this->officer->rank_id);
    }

    public function test_unbind_and_rebind_allows_new_request_without_500_error(): void
    {
        // Submit a rank request
        $this->actingAs($this->officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'reason' => 'First attempt',
        ])->assertStatus(201);

        // Admin unbinds the officer
        $unbindResponse = $this->actingAs($this->admin)->postJson("/api/v1/admin/officers/{$this->officer->id}/unbind-user", [
            'reason' => 'Officer taking administrative leave',
        ]);
        $unbindResponse->assertStatus(200);

        // Pending request must now be cancelled and pending_lock must be null
        $this->assertDatabaseHas('officer_change_requests', [
            'officer_id' => $this->officer->id,
            'status' => 'cancelled',
            'pending_lock' => null,
        ]);

        // Re-bind officer to the user
        $bindResponse = $this->actingAs($this->admin)->postJson("/api/v1/admin/officers/{$this->officer->id}/bind-user", [
            'user_id' => $this->officerUser->id,
            'reason' => 'Officer reinstatement',
        ]);
        $bindResponse->assertStatus(200);

        // Officer user submits the same request again -> must NOT fail with 500 duplicate key error
        $secondAttempt = $this->actingAs($this->officerUser)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $this->sergeantLspd->id,
            'reason' => 'Second attempt after re-bind',
        ]);

        $secondAttempt->assertStatus(201);
        $this->assertDatabaseHas('officer_change_requests', [
            'officer_id' => $this->officer->id,
            'field' => 'rank_id',
            'status' => 'pending',
            'pending_lock' => "{$this->officer->id}:rank_id",
        ]);
    }
}
