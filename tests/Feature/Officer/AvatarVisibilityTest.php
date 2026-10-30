<?php

namespace Tests\Feature\Officer;

use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AvatarVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officerUser;
    private Officer $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->officerUser = User::factory()->create([
            'role' => 'officer',
            'email_verified_at' => now(),
        ]);

        $this->officer = Officer::create([
            'user_id' => $this->officerUser->id,
            'channel_id' => 'UC_TEST_AVATAR_01',
            'handle' => '@officerjohn',
            'streamer_name' => 'John RealFace',
            'officer_name' => 'Ofc. John Doe',
            'callsign' => '1-ADAM-10',
            'badge_number' => '#110',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'patrol_zone' => 'Mission Row',
            'avatar_url' => 'https://yt3.ggpht.com/real_photo.jpg',
            'avatar_hidden' => false,
            'is_active' => true,
            'monthly_duty_minutes' => 120,
        ]);
    }

    /**
     * 1. Officer PATCH endpoint -> sukses, avatar_hidden tersimpan.
     */
    public function test_officer_can_patch_avatar_visibility(): void
    {
        $response = $this->actingAs($this->officerUser)
            ->patchJson('/api/v1/officer/avatar-visibility', [
                'avatar_hidden' => true,
            ]);

        $response->assertOk()
            ->assertJson([
                'avatar_hidden' => true,
            ]);

        $this->assertDatabaseHas('officers', [
            'id' => $this->officer->id,
            'avatar_hidden' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.avatar_hidden_changed',
            'subject_type' => 'officer',
            'subject_id' => $this->officer->id,
        ]);
    }

    /**
     * 2. User non-officer PATCH -> 403.
     */
    public function test_non_officer_cannot_patch_avatar_visibility(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($member)
            ->patchJson('/api/v1/officer/avatar-visibility', [
                'avatar_hidden' => true,
            ]);

        $response->assertForbidden();
    }

    /**
     * 3. Setelah avatar_hidden=true, GET /@slug (bio-link publik) -> officer.avatar_url adalah null.
     */
    public function test_when_avatar_hidden_public_biolink_returns_null_avatar(): void
    {
        $this->officer->update(['avatar_hidden' => true]);

        OfficerBioProfile::create([
            'officer_id' => $this->officer->id,
            'slug' => 'john-doe',
            'is_active' => true,
        ]);

        $response = $this->get('/@john-doe');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink')
            ->where('officer.avatar_url', null)
        );
    }

    /**
     * 4. Setelah avatar_hidden=true, direktori perwira (API/response) -> avatar null.
     */
    public function test_when_avatar_hidden_officers_directory_returns_null_avatar(): void
    {
        $this->officer->update(['avatar_hidden' => true]);

        $response = $this->get('/officers');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('OfficerDirectory')
            ->where('initialOfficers.0.avatar_url', null)
        );
    }

    /**
     * 5. GET /dashboard -> leaderboard officer tersebut avatar_url null.
     */
    public function test_when_avatar_hidden_dashboard_leaderboard_returns_null_avatar(): void
    {
        $this->officer->update(['avatar_hidden' => true]);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('monthlyLeaderboard.all_top.0.avatar_url', null)
        );
    }

    /**
     * 6. Admin melihat officer di /admin/officers -> tetap melihat avatar_url asli.
     */
    public function test_admin_sees_original_avatar_url(): void
    {
        $this->officer->update(['avatar_hidden' => true]);

        $this->assertEquals('https://yt3.ggpht.com/real_photo.jpg', $this->officer->avatar_url);
        $this->assertNull($this->officer->public_avatar_url);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/officers');
        $response->assertOk();
        $officerItem = collect($response->json('data'))->firstWhere('id', $this->officer->id);
        $this->assertNotNull($officerItem);
        $this->assertEquals('https://yt3.ggpht.com/real_photo.jpg', $officerItem['avatar_url']);
    }

    /**
     * 7. Admin PUT officer dengan avatar_hidden=true -> sukses, kolom tersimpan, audit log terbuat.
     */
    public function test_admin_can_put_officer_avatar_hidden_with_audit_log(): void
    {
        $payload = [
            'handle' => '@officerjohn',
            'streamer_name' => 'John RealFace',
            'officer_name' => 'Ofc. John Doe',
            'callsign' => '1-ADAM-10',
            'department' => 'LSPD',
            'avatar_url' => 'https://yt3.ggpht.com/real_photo.jpg',
            'avatar_hidden' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/officers/{$this->officer->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('officers', [
            'id' => $this->officer->id,
            'avatar_hidden' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.avatar_hidden_changed',
            'subject_type' => 'officer',
            'subject_id' => $this->officer->id,
        ]);
    }

    /**
     * 8. Admin non-admin (user biasa) PUT officer -> 403.
     */
    public function test_regular_user_cannot_put_officer_to_change_avatar_hidden(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'member',
            'email_verified_at' => now(),
        ]);

        $payload = [
            'handle' => '@officerjohn',
            'streamer_name' => 'John RealFace',
            'officer_name' => 'Ofc. John Doe',
            'callsign' => '1-ADAM-10',
            'department' => 'LSPD',
            'avatar_hidden' => true,
        ];

        $response = $this->actingAs($regularUser)
            ->putJson("/api/v1/officers/{$this->officer->id}", $payload);

        $response->assertForbidden();
    }
}
