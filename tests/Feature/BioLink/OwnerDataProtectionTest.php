<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OwnerDataProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T-19: OfficerImportCommand creates new officers without crash (station defined).
     */
    public function test_import_command_successfully_creates_new_officer(): void
    {
        $json = json_encode([
            [
                'handle' => '@newrookie',
                'streamer_name' => 'Rookie Streamer',
                'officer_name' => 'Ofc. Rookie',
                'callsign' => '1-R-01',
                'department' => 'LSPD',
                'rank' => 'Cadet',
                'badge_number' => '#101',
                'station' => 'Central',
            ]
        ]);

        $exitCode = Artisan::call('officer:import', ['source' => $json]);
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('officers', [
            'handle' => '@newrookie',
            'officer_name' => 'Ofc. Rookie',
            'callsign' => '1-R-01',
            'station' => 'Central',
        ]);
    }

    /**
     * T-19: OfficerImportCommand protects owner-managed fields on claimed officers.
     */
    public function test_import_command_skips_owner_managed_fields_on_claimed_officer(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_CLAIMED_IMPORT',
            'handle' => '@claimedimport',
            'streamer_name' => 'Owner Streamer',
            'officer_name' => 'Owner RP Name',
            'callsign' => '1-O-99',
            'badge_number' => '#999',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'station' => 'Central',
            'avatar_url' => 'https://custom-avatar.com/owner.png',
            'avatar_is_custom' => true,
            'is_active' => true,
            'user_id' => $owner->id,
        ]);

        $json = json_encode([
            [
                'handle' => '@claimedimport',
                'streamer_name' => 'Overwritten Streamer',
                'officer_name' => 'Overwritten RP Name',
                'callsign' => '2-HACK-01',
                'badge_number' => '#001',
                'department' => 'SASP',
                'rank' => 'Trooper',
                'station' => '71',
            ]
        ]);

        Artisan::call('officer:import', ['source' => $json]);

        $fresh = $officer->fresh();
        // Owner managed fields must remain intact
        $this->assertEquals('Owner RP Name', $fresh->officer_name);
        $this->assertEquals('Owner Streamer', $fresh->streamer_name);
        $this->assertEquals('1-O-99', $fresh->callsign);
        $this->assertEquals('#999', $fresh->badge_number);
        $this->assertEquals('Central', $fresh->station);
        $this->assertEquals('https://custom-avatar.com/owner.png', $fresh->avatar_url);

        // Non-owner fields (e.g. department) can update
        $this->assertEquals('SASP', $fresh->department);
    }

    /**
     * T-19: SyncOfficerSubscribersCommand preserves custom avatar.
     */
    public function test_sync_subscribers_preserves_custom_avatar_and_updates_dicebear(): void
    {
        $customOfficer = Officer::create([
            'channel_id' => 'UC_CUSTOM_AVATAR',
            'handle' => '@customavatar',
            'streamer_name' => 'Custom Avatar Streamer',
            'officer_name' => 'Ofc. Custom Avatar',
            'callsign' => '1-C-10',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'avatar_url' => 'https://i.imgur.com/mycustom.png',
            'avatar_is_custom' => true,
            'is_active' => true,
        ]);

        $dicebearOfficer = Officer::create([
            'channel_id' => 'UC_DICEBEAR_AVATAR',
            'handle' => '@dicebearavatar',
            'streamer_name' => 'Dicebear Streamer',
            'officer_name' => 'Ofc. Dicebear',
            'callsign' => '1-D-20',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'avatar_url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=placeholder',
            'avatar_is_custom' => false,
            'is_active' => true,
        ]);

        Http::fake([
            '*youtube.com*' => Http::response('<html><meta property="og:image" content="https://yt3.ggpht.com/new_avatar.jpg"><meta name="description" content="10K subscribers"></html>', 200),
        ]);

        Artisan::call('officer:sync-subscribers');

        // Custom avatar is NOT overwritten
        $this->assertEquals('https://i.imgur.com/mycustom.png', $customOfficer->fresh()->avatar_url);

        // Dicebear placeholder IS overwritten with real YouTube avatar
        $this->assertEquals('https://yt3.ggpht.com/new_avatar.jpg', $dicebearOfficer->fresh()->avatar_url);
    }

    /**
     * Non-custom officer avatar updates when YouTube channel avatar changes.
     */
    public function test_sync_subscribers_updates_avatar_when_youtube_avatar_changes(): void
    {
        $officer = Officer::create([
            'channel_id' => 'UC_YOUTUBE_CHANGE',
            'handle' => '@youtubechange',
            'streamer_name' => 'Changing Streamer',
            'officer_name' => 'Ofc. Changing',
            'callsign' => '1-Y-30',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'avatar_url' => 'https://yt3.ggpht.com/old_avatar_v1.jpg',
            'avatar_is_custom' => false,
            'is_active' => true,
        ]);

        Http::fake([
            '*youtube.com*' => Http::response('<html><meta property="og:image" content="https://yt3.ggpht.com/new_avatar_v2.jpg"><meta name="description" content="15K subscribers"></html>', 200),
        ]);

        Artisan::call('officer:sync-subscribers');

        $this->assertEquals('https://yt3.ggpht.com/new_avatar_v2.jpg', $officer->fresh()->avatar_url);
    }

    /**
     * T-19: Admin updating a claimed officer logs officer.admin_override audit event.
     */
    public function test_admin_updating_claimed_officer_logs_admin_override(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['email_verified_at' => now(), 'role' => 'officer']);

        $officer = Officer::create([
            'channel_id' => 'UC_ADMIN_OVERRIDE',
            'handle' => '@adminoverride',
            'streamer_name' => 'Original Streamer',
            'officer_name' => 'Ofc. Original',
            'callsign' => '1-A-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $owner->id,
        ]);

        $this->actingAs($admin)->putJson("/api/v1/officers/{$officer->id}", [
            'officer_name' => 'Ofc. Admin Modified',
            'streamer_name' => 'Original Streamer',
            'callsign' => '1-A-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'handle' => '@adminoverride',
            'channel_id' => 'UC_ADMIN_OVERRIDE',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'officer.admin_override',
            'subject_id' => $officer->id,
        ]);
    }
}
