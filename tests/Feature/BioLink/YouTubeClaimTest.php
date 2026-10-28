<?php

namespace Tests\Feature\BioLink;

use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\OfficerClaim;
use App\Models\User;
use App\Services\YouTubeChannelVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeClaimTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Officer $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'member',
        ]);

        $this->officer = Officer::create([
            'officer_name' => 'Officer Eido',
            'streamer_name' => 'Eido Stream',
            'handle' => 'officereido',
            'channel_id' => 'UC_EIDO_CHANNEL_123',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '999',
            'badge_number' => '999',
            'is_active' => true,
        ]);
    }

    public function test_token_in_officer_channel_page_auto_approves_and_binds(): void
    {
        $createResponse = $this->actingAs($this->user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $this->officer->id,
        ]);

        $createResponse->assertStatus(201);
        $rawToken = $createResponse->json('token');
        $claimId = $createResponse->json('claim_id');

        $this->assertNotEmpty($rawToken);
        $this->assertMatchesRegularExpression('/^SASP-VERIFY-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $rawToken);

        // Verify database only stores hash, not the raw token
        $this->assertDatabaseHas('officer_claims', [
            'id' => $claimId,
            'verification_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('officer_claims', [
            'id' => $claimId,
            'verification_token_hash' => $rawToken,
        ]);

        // Fake YouTube HTTP response containing the token in HTML body
        Http::fake([
            'https://www.youtube.com/channel/UC_EIDO_CHANNEL_123/about*' => Http::response(
                "<html><body><div id='description'>Halo semua, ini channel resmi saya. {$rawToken} - Verifikasi SASP</div></body></html>",
                200
            ),
            'https://www.youtube.com/channel/UC_EIDO_CHANNEL_123/live*' => Http::response(
                "<html><body>Live stream offline</body></html>",
                200
            ),
        ]);

        $verifyResponse = $this->actingAs($this->user)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube");
        $verifyResponse->assertStatus(200);

        // Claim should now be approved
        $this->assertDatabaseHas('officer_claims', [
            'id' => $claimId,
            'status' => 'approved',
            'review_reason' => 'Verifikasi otomatis YouTube',
        ]);

        // Officer should be bound to user
        $this->officer->refresh();
        $this->assertEquals($this->user->id, $this->officer->user_id);
        $this->assertNotNull($this->officer->bioProfile);
        $this->assertTrue((bool) $this->officer->bioProfile->is_active);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'claim.auto_approved',
            'subject_id' => $claimId,
        ]);
    }

    public function test_token_only_in_different_channel_remains_pending_and_returns_422(): void
    {
        $createResponse = $this->actingAs($this->user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $this->officer->id,
        ]);

        $rawToken = $createResponse->json('token');
        $claimId = $createResponse->json('claim_id');

        // Token is placed in someone else's channel (e.g. UC_DIFFERENT_CHANNEL), not UC_EIDO_CHANNEL_123
        Http::fake([
            'https://www.youtube.com/channel/UC_EIDO_CHANNEL_123/about*' => Http::response(
                "<html><body>Channel description without any token</body></html>",
                200
            ),
            'https://www.youtube.com/channel/UC_EIDO_CHANNEL_123/live*' => Http::response(
                "<html><body>Live stream without token</body></html>",
                200
            ),
        ]);

        $verifyResponse = $this->actingAs($this->user)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube");
        $verifyResponse->assertStatus(422)
            ->assertJsonFragment(['message' => 'Kode belum ditemukan di deskripsi channel. Pastikan sudah disimpan, lalu coba lagi dalam beberapa menit.']);

        $this->assertDatabaseHas('officer_claims', [
            'id' => $claimId,
            'status' => 'pending',
            'check_attempts' => 1,
        ]);

        $this->officer->refresh();
        $this->assertNull($this->officer->user_id);
    }

    public function test_expired_token_sets_status_expired_and_returns_422(): void
    {
        $createResponse = $this->actingAs($this->user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $this->officer->id,
        ]);

        $claimId = $createResponse->json('claim_id');

        // Travel 25 hours into the future
        $this->travel(25)->hours();

        $verifyResponse = $this->actingAs($this->user)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube");
        $verifyResponse->assertStatus(422)
            ->assertJsonFragment(['message' => 'Token verifikasi telah kedaluwarsa (berlaku 24 jam). Silakan buat token baru.']);

        $this->assertDatabaseHas('officer_claims', [
            'id' => $claimId,
            'status' => 'expired',
        ]);
    }

    public function test_other_user_cannot_call_verify_on_claim(): void
    {
        $createResponse = $this->actingAs($this->user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $this->officer->id,
        ]);

        $claimId = $createResponse->json('claim_id');

        $stranger = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'member',
        ]);

        $verifyResponse = $this->actingAs($stranger)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube");
        $verifyResponse->assertStatus(403);
    }

    public function test_cannot_claim_already_bound_officer(): void
    {
        $otherUser = User::factory()->create(['email_verified_at' => now(), 'role' => 'member']);
        $this->officer->update(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $this->officer->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['officer_id']);
    }

    public function test_unverified_email_user_is_denied(): void
    {
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'member',
        ]);

        $response = $this->actingAs($unverifiedUser)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $this->officer->id,
        ]);

        // Route middleware 'verified' redirects or returns 409/403
        $this->assertTrue(in_array($response->status(), [403, 409, 302], true));
    }

    public function test_cannot_claim_inactive_officer(): void
    {
        $inactiveOfficer = Officer::create([
            'officer_name' => 'Inactive Officer',
            'streamer_name' => 'Inactive Stream',
            'handle' => 'inactiveofficer',
            'channel_id' => 'UC_INACTIVE_123',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '000',
            'badge_number' => '000',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $inactiveOfficer->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['officer_id']);
    }
}
