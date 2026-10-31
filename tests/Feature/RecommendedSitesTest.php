<?php

namespace Tests\Feature;

use App\Models\RecommendedSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendedSitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_returns_only_active_sites_ordered_by_sort_order_then_id(): void
    {
        $siteInactive = RecommendedSite::create([
            'name' => 'Inactive Site',
            'poster_name' => 'Inactive Poster',
            'poster_image_url' => '/images/recommended/inactive.webp',
            'url' => 'https://inactive.example.com',
            'description' => 'Inactive description',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $siteOrder2 = RecommendedSite::create([
            'name' => 'Order 2 Site',
            'poster_name' => 'Poster 2',
            'poster_image_url' => '/images/recommended/order2.webp',
            'url' => 'https://order2.example.com',
            'description' => 'Order 2 description',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $siteOrder0First = RecommendedSite::create([
            'name' => 'Order 0 First Site',
            'poster_name' => 'Poster 0 First',
            'poster_image_url' => '/images/recommended/order0-1.webp',
            'url' => 'https://order0-1.example.com',
            'description' => 'Order 0 first description',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $siteOrder0Second = RecommendedSite::create([
            'name' => 'Order 0 Second Site',
            'poster_name' => 'Poster 0 Second',
            'poster_image_url' => '/images/recommended/order0-2.webp',
            'url' => 'https://order0-2.example.com',
            'description' => 'Order 0 second description',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->getJson('/api/v1/recommended-sites');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'badge',
                        'poster_name',
                        'poster_subtitle',
                        'poster_image_url',
                        'background_image_url',
                        'url',
                        'cta_text',
                        'description',
                        'chips',
                        'is_featured',
                        'is_active',
                        'sort_order',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        // Ensure inactive site is not returned
        $returnedIds = collect($data)->pluck('id')->all();
        $this->assertNotContains($siteInactive->id, $returnedIds);

        // Verify order: sort_order asc, then id asc
        $this->assertEquals($siteOrder0First->id, $data[0]['id']);
        $this->assertEquals($siteOrder0Second->id, $data[1]['id']);
        $this->assertEquals($siteOrder2->id, $data[2]['id']);
    }

    public function test_admin_can_post_new_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'name' => 'LSPD HANDBOOK',
            'badge' => 'HANDBOOK',
            'poster_name' => 'LOS SANTOS POLICE DEPARTMENT',
            'poster_subtitle' => 'Handbook Internal',
            'poster_image_url' => '/images/recommended/lspd-sprite.webp',
            'background_image_url' => '/images/recommended/lspd-background.png',
            'url' => 'https://ucup-meletup.site/',
            'cta_text' => 'Kunjungi website',
            'description' => 'Panduan lapangan untuk anggota Los Santos Police Department.',
            'chips' => ['10-Codes', 'Penal Code', 'Patrol Report'],
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/recommended-sites', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'LSPD HANDBOOK')
            ->assertJsonPath('data.badge', 'HANDBOOK')
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.chips', ['10-Codes', 'Penal Code', 'Patrol Report']);

        $this->assertDatabaseHas('recommended_sites', [
            'name' => 'LSPD HANDBOOK',
            'url' => 'https://ucup-meletup.site/',
        ]);
    }

    public function test_regular_user_cannot_post_new_entry(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $payload = [
            'name' => 'BCSO PORTAL',
            'poster_name' => 'Blaine County Sheriff Office',
            'poster_image_url' => '/images/recommended/bcso.webp',
            'url' => 'https://bcso.example.com',
            'description' => 'Portal resmi BCSO.',
        ];

        $this->actingAs($viewer)
            ->postJson('/api/v1/admin/recommended-sites', $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('recommended_sites', [
            'name' => 'BCSO PORTAL',
        ]);
    }

    public function test_admin_can_toggle_active_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $site = RecommendedSite::create([
            'name' => 'Toggle Site',
            'poster_name' => 'Toggle Poster',
            'poster_image_url' => '/images/recommended/toggle.webp',
            'url' => 'https://toggle.example.com',
            'description' => 'Toggle description',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/recommended-sites/{$site->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertFalse($site->fresh()->is_active);

        // Toggle back to true
        $this->actingAs($admin)->patchJson("/api/v1/admin/recommended-sites/{$site->id}/toggle")
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->assertTrue($site->fresh()->is_active);
    }

    public function test_admin_can_delete_site(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $site = RecommendedSite::create([
            'name' => 'Delete Me Site',
            'poster_name' => 'Delete Me Poster',
            'poster_image_url' => '/images/recommended/delete.webp',
            'url' => 'https://delete.example.com',
            'description' => 'Delete me description',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/admin/recommended-sites/{$site->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('recommended_sites', [
            'id' => $site->id,
        ]);
    }

    public function test_validation_fails_for_invalid_url_and_non_array_chips(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'name' => 'Invalid Site',
            'poster_name' => 'Invalid Poster',
            'poster_image_url' => '/images/recommended/test.webp',
            'url' => 'not-a-valid-url',
            'description' => 'Validation test description',
            'chips' => 'invalid-chips-string',
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/recommended-sites', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'chips']);
    }
}

