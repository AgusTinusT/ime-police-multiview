<?php

namespace Tests\Feature\BioLink;

use App\Models\Agency;
use App\Models\BioDailyStat;
use App\Models\BioSlugRedirect;
use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\Rank;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_public_page_uses_relational_rank_agency_and_badge_logo(): void
    {
        $agency = Agency::create([
            'agency_name' => 'San Andreas State Police',
            'agency_code' => 'SASP',
            'badge_logo_url' => 'https://example.com/sasp_badge.png',
            'is_active' => true,
        ]);

        $rank = Rank::create([
            'agency_id' => $agency->id,
            'rank_title' => 'Captain',
            'level' => 4,
        ]);

        $user = $this->createVerifiedUser(['role' => 'member']);

        $officer = Officer::create([
            'user_id' => $user->id,
            'officer_name' => 'John Miller',
            'streamer_name' => 'MillerLive',
            'handle' => 'johnmiller',
            'channel_id' => 'UC_MILLER_123',
            'agency_id' => $agency->id,
            'rank_id' => $rank->id,
            'department' => 'Old Department',
            'rank' => 'Old Rank',
            'callsign' => '100',
            'is_active' => true,
        ]);

        $profile = OfficerBioProfile::create([
            'officer_id' => $officer->id,
            'slug' => 'johnmiller',
            'is_active' => true,
        ]);

        $response = $this->get('/@johnmiller');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink')
            ->where('officer.rank', 'Captain')
            ->where('officer.agency', 'San Andreas State Police')
            ->where('officer.agency_badge_url', 'https://example.com/sasp_badge.png')
            ->where('officer.department', 'SASP')
        );
    }

    public function test_public_page_falls_back_to_text_columns_when_relations_are_null(): void
    {
        $user = $this->createVerifiedUser(['role' => 'member']);

        $officer = Officer::create([
            'user_id' => $user->id,
            'officer_name' => 'Legacy Officer',
            'streamer_name' => 'LegacyStreamer',
            'handle' => 'legacyofficer',
            'channel_id' => 'UC_LEGACY_123',
            'agency_id' => null,
            'rank_id' => null,
            'department' => 'LSPD',
            'rank' => 'Senior Officer',
            'callsign' => '102',
            'is_active' => true,
        ]);

        $profile = OfficerBioProfile::create([
            'officer_id' => $officer->id,
            'slug' => 'legacyofficer',
            'is_active' => true,
        ]);

        $response = $this->get('/@legacyofficer');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink')
            ->where('officer.rank', 'Senior Officer')
            ->where('officer.agency', 'LSPD')
            ->where('officer.department', 'LSPD')
            ->where('officer.agency_badge_url', null)
        );
    }

    public function test_public_bio_link_page_displays_officer_info(): void
    {
        $user = $this->createVerifiedUser();
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

    public function test_slug_update_creates_301_permanent_redirect(): void
    {
        $admin = $this->createAdminUser();
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

        $response = $this->get('/@handle301');
        $response->assertStatus(301);
        $response->assertRedirect('/@customofficerslug301');

        $this->get('/@nonexistenthandle')->assertStatus(404);
    }

    public function test_b07_partner_recommendations_hide_inactive_or_suspended_officers(): void
    {
        $user = $this->createVerifiedUser(['role' => 'officer']);
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

        $response = $this->get('/@squad-lead-main');
        $response->assertOk();

        $links = $response->viewData('page')['props']['links'];
        $this->assertCount(0, $links);
    }

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

    public function test_b12_view_counter_works_with_database_cache_store(): void
    {
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

        $this->get('/@db-cache-view-test')->assertOk();
        $this->get('/@db-cache-view-test')->assertOk();

        $exitCode = Artisan::call('biolinks:flush-views');
        $this->assertEquals(0, $exitCode);

        $stat = BioDailyStat::where('officer_bio_profile_id', $profile->id)
            ->whereNull('officer_bio_link_id')
            ->first();

        $this->assertNotNull($stat);
        $this->assertEquals(now()->toDateString(), Carbon::parse($stat->stat_date)->toDateString());
        $this->assertGreaterThanOrEqual(2, $stat->views);
    }

    public function test_public_page_and_live_status_include_viewers_count(): void
    {
        $officer = Officer::create([
            'channel_id' => 'UC_LIVE_VIEWERS',
            'handle' => '@liveviewers',
            'streamer_name' => 'Live Streamer',
            'officer_name' => 'Ofc. Live Viewers',
            'callsign' => '1-L-123',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);
        $profile->update(['slug' => 'live-viewers-test']);

        \App\Models\ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => 'UC_LIVE_VIEWERS',
            'video_id' => 'video_123',
            'title' => 'Patrol Live Stream',
            'status' => 'LIVE',
            'viewers_count' => 123,
        ]);

        $pageResponse = $this->get('/@live-viewers-test');
        $pageResponse->assertOk();
        $pageResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink')
            ->where('officer.is_live', true)
            ->where('officer.live_stream.viewer_count', 123)
            ->where('officer.live_stream.video_id', 'video_123')
            ->missing('officer.live_stream.duty_minutes')
        );

        $apiResponse = $this->getJson('/api/v1/bio-links/live-viewers-test/live-status');
        $apiResponse->assertOk();
        $apiResponse->assertJson([
            'is_live' => true,
            'live_stream' => [
                'video_id' => 'video_123',
                'title' => 'Patrol Live Stream',
                'viewer_count' => 123,
            ],
        ]);
        $this->assertArrayNotHasKey('duty_minutes', $apiResponse->json('live_stream'));
    }
}
