<?php

namespace Tests\Feature\BioLink;

use App\Models\ChatMessage;
use App\Models\Officer;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

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

        $this->actingAs($unverifiedOfficerUser)
            ->get('/dashboard/bio-links')
            ->assertRedirect(route('verification.notice'));

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

    public function test_public_chat_messages_do_not_leak_user_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Secret Officer',
            'email' => 'very_secret_officer_email@policeduty.com',
        ]);

        ChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Laporan 10-8 patroli aktif.',
        ]);

        $response = $this->getJson('/api/v1/chat/messages');
        $response->assertOk();
        $response->assertDontSee('very_secret_officer_email@policeduty.com');
        $response->assertSee('Secret Officer');
    }

    public function test_banned_user_login_is_rejected_and_api_returns_403(): void
    {
        $bannedUser = User::factory()->create([
            'email' => 'banned_trooper@policeduty.com',
            'password' => bcrypt('password123'),
            'status' => 'banned',
        ]);

        $response = $this->post('/login', [
            'email' => 'banned_trooper@policeduty.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($bannedUser)
            ->postJson('/api/v1/officer-claims', [
                'officer_id' => 1,
            ])
            ->assertStatus(403);
    }

    public function test_t04_password_change_logs_out_other_sessions(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        DB::table('sessions')->insert([
            'id' => 'session_other_device_123',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'OtherBrowser/1.0',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => 'session_other_device_123']);

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'session_other_device_123']);
    }
}
