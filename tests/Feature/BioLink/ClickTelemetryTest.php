<?php

namespace Tests\Feature\BioLink;

use App\Models\BioLinkClick;
use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class ClickTelemetryTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_safe_redirect_records_telemetry_and_redirects(): void
    {
        $user = $this->createVerifiedUser(['role' => 'member']);
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

    public function test_safe_redirect_rejects_banned_inactive_or_suspended_links(): void
    {
        $user = $this->createVerifiedUser(['role' => 'officer']);
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

        $click = BioLinkClick::where('officer_bio_link_id', $link->id)->first();
        $this->assertNotNull($click->visitor_hash);
        $this->assertEquals(64, strlen($click->visitor_hash));
    }
}
