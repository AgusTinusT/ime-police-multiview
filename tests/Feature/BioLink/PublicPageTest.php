<?php

namespace Tests\Feature\BioLink;

use App\Models\Agency;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

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

        $user = User::factory()->create(['email_verified_at' => now(), 'role' => 'member']);

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
        $user = User::factory()->create(['email_verified_at' => now(), 'role' => 'member']);

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
}
