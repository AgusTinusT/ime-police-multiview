<?php

namespace Tests\Feature\BioLink;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Officer $officer;
    private Agency $lspd;
    private Agency $bcso;
    private Division $swatLspd;
    private Division $k9Bcso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'member',
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

        $this->swatLspd = Division::create([
            'agency_id' => $this->lspd->id,
            'division_name' => 'Special Weapons and Tactics',
            'division_code' => 'SWAT',
        ]);

        $this->k9Bcso = Division::create([
            'agency_id' => $this->bcso->id,
            'division_name' => 'Canine Unit',
            'division_code' => 'K9',
        ]);

        $this->officer = Officer::create([
            'user_id' => $this->user->id,
            'officer_name' => 'James Gordon',
            'streamer_name' => 'GordonGamer',
            'handle' => 'jamesgordon',
            'channel_id' => 'UC_GORDON_123',
            'agency_id' => $this->lspd->id,
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '101',
            'badge_number' => '101',
            'is_active' => true,
        ]);

        OfficerBioProfile::create([
            'officer_id' => $this->officer->id,
            'slug' => 'jamesgordon',
            'is_active' => true,
        ]);
    }

    public function test_owner_updates_profile_fields_successfully(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'officer_name' => 'James W Gordon',
            'streamer_name' => 'GordonOfficial',
            'callsign' => '102',
            'badge_number' => '102',
            'patrol_zone' => 'Vinewood Hills',
            'avatar_url' => 'https://yt3.ggpht.com/avatar_valid.jpg',
            'division_id' => $this->swatLspd->id,
        ]);

        $response->assertStatus(200);

        $this->officer->refresh();
        $this->assertEquals('James W Gordon', $this->officer->officer_name);
        $this->assertEquals('GordonOfficial', $this->officer->streamer_name);
        $this->assertEquals('102', $this->officer->callsign);
        $this->assertEquals('102', $this->officer->badge_number);
        $this->assertEquals('Vinewood Hills', $this->officer->patrol_zone);
        $this->assertEquals('https://yt3.ggpht.com/avatar_valid.jpg', $this->officer->avatar_url);
        $this->assertTrue((bool) $this->officer->avatar_is_custom);
        $this->assertEquals($this->swatLspd->id, $this->officer->division_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.self_updated',
            'subject_id' => $this->officer->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.name_changed',
            'subject_id' => $this->officer->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.callsign_changed',
            'subject_id' => $this->officer->id,
        ]);
    }

    public function test_sending_locked_field_returns_422(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'channel_id' => 'UC_CHANGED_HACK',
            'is_active' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel_id']);
    }

    public function test_duplicate_callsign_is_rejected(): void
    {
        Officer::create([
            'officer_name' => 'Another Officer',
            'streamer_name' => 'Another Streamer',
            'handle' => 'another',
            'channel_id' => 'UC_ANOTHER',
            'callsign' => '202',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'callsign' => '202', // duplicate
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['callsign']);
    }

    public function test_changing_callsign_twice_within_7_days_is_rejected(): void
    {
        // First change
        $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'callsign' => '105',
        ])->assertStatus(200);

        // Immediate second change
        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'callsign' => '106',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['callsign']);

        // Travel 8 days forward
        $this->travel(8)->days();

        $allowedResponse = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'callsign' => '106',
        ]);
        $allowedResponse->assertStatus(200);
    }

    public function test_avatar_from_unauthorized_host_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'avatar_url' => 'https://malicious-cdn.com/avatar.png',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar_url']);
    }

    public function test_division_from_different_agency_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'division_id' => $this->k9Bcso->id, // belongs to BCSO while officer is in LSPD
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['division_id']);
    }

    public function test_editing_patrol_zone_multiple_times_does_not_consume_name_rate_limit(): void
    {
        // Change patrol zone 5 times
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
                'patrol_zone' => "Zone {$i}",
            ])->assertStatus(200);
        }

        // Officer name change must still be allowed!
        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'officer_name' => 'James Fresh Gordon',
        ]);

        $response->assertStatus(200);
    }

    public function test_suspended_officer_bio_returns_423(): void
    {
        $this->officer->bioProfile->update(['is_suspended' => true]);

        $response = $this->actingAs($this->user)->putJson('/api/v1/officer/profile', [
            'patrol_zone' => 'Downtown',
        ]);

        $response->assertStatus(423);
    }

    public function test_user_without_officer_returns_403(): void
    {
        $unboundUser = User::factory()->create(['email_verified_at' => now(), 'role' => 'member']);

        $response = $this->actingAs($unboundUser)->putJson('/api/v1/officer/profile', [
            'patrol_zone' => 'Downtown',
        ]);

        $response->assertStatus(403);
    }
}
