<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiKitRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_ui_kit_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/dev/ui-kit');

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_ui_kit_catalog(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($user)->get('/dev/ui-kit');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_ui_kit_catalog(): void
    {
        $response = $this->get('/dev/ui-kit');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_bio_themes_preview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/dev/bio-themes');

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_bio_themes_preview(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($user)->get('/dev/bio-themes');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_bio_themes_preview(): void
    {
        $response = $this->get('/dev/bio-themes');

        $response->assertRedirect('/login');
    }
}

