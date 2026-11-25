<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActiveBioLinksDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_bio_link_page_lists_only_active_claimed_non_suspended_officer_profiles(): void
    {
        $owner = User::factory()->create();
        $activeOfficer = Officer::create([
            'user_id' => $owner->id,
            'channel_id' => 'UC1111111111111111111111',
            'handle' => '@activebio',
            'streamer_name' => 'Active Bio',
            'officer_name' => 'Ofc. Active Bio',
            'callsign' => '1-A-10',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'is_listed' => true,
        ]);
        $activeProfile = OfficerBioProfile::create([
            'officer_id' => $activeOfficer->id,
            'slug' => 'active-bio',
            'bio_tagline' => 'Patroli LSPD setiap malam',
            'is_active' => true,
            'is_suspended' => false,
        ]);
        OfficerBioLink::create([
            'officer_bio_profile_id' => $activeProfile->id,
            'title' => 'Saweria',
            'url' => 'https://saweria.co/activebio',
            'link_type' => 'donation',
            'is_active' => true,
            'moderation_status' => 'approved',
            'sort_order' => 1,
        ]);

        // Unclaimed officer should NOT appear
        $unclaimedOfficer = Officer::create([
            'channel_id' => 'UC2222222222222222222222',
            'handle' => '@unclaimed',
            'streamer_name' => 'Unclaimed',
            'officer_name' => 'Ofc. Unclaimed',
            'callsign' => '1-A-11',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'is_listed' => true,
        ]);
        OfficerBioProfile::create([
            'officer_id' => $unclaimedOfficer->id,
            'slug' => 'unclaimed-bio',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        // Suspended profile should NOT appear
        $suspendedOwner = User::factory()->create();
        $suspendedOfficer = Officer::create([
            'user_id' => $suspendedOwner->id,
            'channel_id' => 'UC3333333333333333333333',
            'handle' => '@suspended',
            'streamer_name' => 'Suspended',
            'officer_name' => 'Ofc. Suspended',
            'callsign' => '1-A-12',
            'department' => 'BCSO',
            'rank' => 'Deputy',
            'is_active' => true,
            'is_listed' => true,
        ]);
        OfficerBioProfile::create([
            'officer_id' => $suspendedOfficer->id,
            'slug' => 'suspended-bio',
            'is_active' => true,
            'is_suspended' => true,
        ]);

        $this->get('/bio-link')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BioLinkPromo')
                ->has('activeBioLinks', 1)
                ->where('activeBioLinks.0.slug', 'active-bio')
                ->where('activeBioLinks.0.officer_name', 'Ofc. Active Bio')
                ->where('activeBioLinks.0.links_count', 1)
            );

        $this->get('/officers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OfficerDirectory')
                ->where('initialOfficers', function ($officers) use ($activeOfficer, $suspendedOfficer) {
                    $list = collect($officers);
                    $activeRow = $list->firstWhere('id', $activeOfficer->id);
                    $suspendedRow = $list->firstWhere('id', $suspendedOfficer->id);

                    return $activeRow['bio_slug'] === 'active-bio'
                        && $suspendedRow['bio_slug'] === null;
                })
            );
    }
}
