<?php

namespace Tests\Feature;

use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class BioLinkPromoTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_guest_can_access_bio_link_promo_page_with_expected_props(): void
    {
        $response = $this->get('/bio-link');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('BioLinkPromo')
            ->where('isOfficer', false)
            ->where('isAuthenticated', false)
            ->has('themes')
        );
    }

    public function test_officer_accesses_promo_page_with_is_officer_true(): void
    {
        $user = $this->createVerifiedUser();
        $officer = $this->createOfficer(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/bio-link');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('BioLinkPromo')
            ->where('isOfficer', true)
            ->where('isAuthenticated', true)
        );
    }

    public function test_regular_user_accesses_promo_page_with_is_officer_false(): void
    {
        $user = $this->createVerifiedUser(['role' => 'viewer']);

        $response = $this->actingAs($user)->get('/bio-link');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('BioLinkPromo')
            ->where('isOfficer', false)
            ->where('isAuthenticated', true)
        );
    }

    public function test_example_slug_returns_first_bound_officer_slug_or_null(): void
    {
        // When no bound officer exists
        $responseWithoutBound = $this->get('/bio-link');
        $responseWithoutBound->assertInertia(fn (Assert $page) => $page
            ->where('exampleSlug', null)
        );

        // When a bound officer with bio profile exists
        $user = $this->createVerifiedUser();
        $officer = $this->createOfficer(['user_id' => $user->id]);
        OfficerBioProfile::create([
            'officer_id' => $officer->id,
            'slug' => 'boundofficer',
        ]);

        $responseWithBound = $this->get('/bio-link');
        $responseWithBound->assertInertia(fn (Assert $page) => $page
            ->where('exampleSlug', 'boundofficer')
        );
    }
}
