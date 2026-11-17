<?php

namespace Tests\Feature\BioLink;

use App\Models\Agency;
use App\Models\Officer;
use App\Models\OfficerChangeRequest;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class OwnershipFlowTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_complete_eido_ownership_lifecycle(): void
    {
        // 0. Setup Master Data (Agency & Ranks)
        $lspd = Agency::create([
            'agency_name' => 'Los Santos Police Department',
            'agency_code' => 'LSPD',
            'badge_logo_url' => 'https://example.com/lspd.png',
            'is_active' => true,
        ]);

        $cadetRank = Rank::create([
            'agency_id' => $lspd->id,
            'rank_title' => 'Cadet',
            'level' => 1,
        ]);

        $seniorRank = Rank::create([
            'agency_id' => $lspd->id,
            'rank_title' => 'Senior Officer',
            'level' => 3,
        ]);

        $admin = $this->createAdminUser();

        // 1. Admin creates officer "e'ido" via POST /api/v1/officers (unclaimed)
        $storeResponse = $this->actingAs($admin)->postJson('/api/v1/officers', [
            'handle' => '@eidoertachta',
            'streamer_name' => "E'ido Ertachta",
            'officer_name' => "Ofc. E'ido Ertachta",
            'callsign' => '1-ADAM-01',
            'department' => 'LSPD',
            'rank' => 'Cadet',
            'channel_id' => 'UC_EIDO_OFFICIAL_CHANNEL',
            'agency_id' => $lspd->id,
            'rank_id' => $cadetRank->id,
            'badge_number' => '101',
            'station' => 'Central',
        ]);

        $storeResponse->assertStatus(201);
        $officerId = $storeResponse->json('officer.id') ?? Officer::where('handle', '@eidoertachta')->first()->id;
        $officer = Officer::findOrFail($officerId);
        $this->assertNull($officer->user_id, 'Officer must be unclaimed initially');

        // 2. New user registers + email is verified
        $user = User::factory()->create([
            'name' => 'Eido Streamer',
            'email' => 'eido@example.com',
            'role' => 'member',
            'email_verified_at' => now(),
        ]);

        // 3. POST /api/v1/officer-claims/youtube -> retrieve token from response
        $claimResponse = $this->actingAs($user)->postJson('/api/v1/officer-claims/youtube', [
            'officer_id' => $officer->id,
        ]);

        $claimResponse->assertStatus(201);
        $token = $claimResponse->json('token');
        $claimId = $claimResponse->json('claim_id');
        $this->assertNotEmpty($token);
        $this->assertStringStartsWith('SASP-VERIFY-', $token);

        // 4. Fake YouTube channel page containing token -> verify-youtube -> 200 & officer bound
        $channelId = $officer->channel_id;
        Http::fake([
            "https://www.youtube.com/channel/{$channelId}/about*" => Http::response(
                "<html><body>Channel Official Eido Ertachta. Token Verifikasi: {$token} - SASP Official</body></html>",
                200
            ),
            "https://www.youtube.com/channel/{$channelId}/live*" => Http::response(
                "<html><body>Live stream offline</body></html>",
                200
            ),
        ]);

        $verifyResponse = $this->actingAs($user)->postJson("/api/v1/officer-claims/{$claimId}/verify-youtube");
        $verifyResponse->assertStatus(200);

        $officer->refresh();
        $user->refresh();
        $this->assertEquals($user->id, $officer->user_id);
        $this->assertNotNull($officer->bioProfile);
        $bioSlug = $officer->bioProfile->slug;

        // 5. PUT /api/v1/officer/profile changes callsign -> GET /@{slug} reflects new callsign
        $updateProfileResponse = $this->actingAs($user)->putJson('/api/v1/officer/profile', [
            'callsign' => '1-ADAM-02',
        ]);
        $updateProfileResponse->assertStatus(200);

        $officer->refresh();
        $this->assertEquals('1-ADAM-02', $officer->callsign);

        $publicPageResponse = $this->get("/@{$bioSlug}");
        $publicPageResponse->assertStatus(200);
        $publicPageResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink')
            ->where('officer.callsign', '1-ADAM-02')
        );

        // 6. POST /api/v1/officer/change-requests rank -> admin approves -> rank changes
        $requestResponse = $this->actingAs($user)->postJson('/api/v1/officer/change-requests', [
            'field' => 'rank_id',
            'new_value' => (string) $seniorRank->id,
            'reason' => 'Promotion exam passed',
        ]);
        $requestResponse->assertStatus(201);
        $requestId = $requestResponse->json('change_request.id');

        $approveResponse = $this->actingAs($admin)->postJson("/api/v1/admin/officer-change-requests/{$requestId}/approve", [
            'reason' => 'Approved promotion',
        ]);
        $approveResponse->assertStatus(200);

        $officer->refresh();
        $this->assertEquals($seniorRank->id, $officer->rank_id);
        $this->assertEquals('Senior Officer', $officer->rank);

        // 7. Owner adds Discord link in /api/v1/officer/bio-links -> visible on /@{slug}
        $linkResponse = $this->actingAs($user)->postJson('/api/v1/officer/bio-links', [
            'title' => 'Komunitas Discord Eido',
            'link_type' => 'discord',
            'url' => 'https://discord.gg/eido',
        ]);
        $linkResponse->assertStatus(201);

        $publicPageWithLink = $this->get("/@{$bioSlug}");
        $publicPageWithLink->assertStatus(200);
        $publicPageWithLink->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink')
            ->has('links', 1)
            ->where('links.0.title', 'Komunitas Discord Eido')
        );

        // 8. Admin unbinds -> /@{slug} 404; pending change requests cancelled; data dinas preserved
        $unbindResponse = $this->actingAs($admin)->postJson("/api/v1/admin/officers/{$officer->id}/unbind-user", [
            'reason' => 'Officer honorable discharge',
        ]);
        $unbindResponse->assertStatus(200);

        $officer->refresh();
        $this->assertNull($officer->user_id);
        $this->assertEquals('1-ADAM-02', $officer->callsign);
        $this->assertEquals('Senior Officer', $officer->rank);
        $this->assertEquals($seniorRank->id, $officer->rank_id);

        // Profile is inactive, so /@{slug} returns 404
        $this->get("/@{$bioSlug}")->assertStatus(404);
    }
}
