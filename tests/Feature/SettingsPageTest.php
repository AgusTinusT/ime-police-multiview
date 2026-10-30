<?php

namespace Tests\Feature;

use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/settings');

        $response->assertRedirect('/login');
    }

    public function test_regular_user_can_access_settings_without_officer_props(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Index')
            ->where('currentTab', 'account')
            ->missing('officer')
            ->missing('bioSlug')
        );
    }

    public function test_officer_can_access_settings_with_officer_props_and_bioslug(): void
    {
        $user = User::factory()->create([
            'role' => 'officer',
        ]);

        $officer = Officer::create([
            'user_id' => $user->id,
            'officer_name' => 'John Officer',
            'streamer_name' => 'JohnStreamer',
            'handle' => 'johnofficer',
            'channel_id' => 'UC_TEST_OFFICER_01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-ADAM-99',
            'badge_number' => '999',
            'is_active' => true,
        ]);

        OfficerBioProfile::create([
            'officer_id' => $officer->id,
            'slug' => 'john-officer',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Index')
            ->has('officer')
            ->where('bioSlug', 'john-officer')
        );
    }

    public function test_legacy_officer_profile_redirects_to_settings_officer_tab(): void
    {
        $user = User::factory()->create([
            'role' => 'officer',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard/officer-profile');

        $response->assertRedirect('/settings?tab=officer');
    }

    public function test_legacy_profile_redirects_to_settings_account_tab(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertRedirect('/settings?tab=account');
    }
}

