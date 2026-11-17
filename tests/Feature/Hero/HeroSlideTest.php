<?php

namespace Tests\Feature\Hero;

use App\Models\ActiveStream;
use App\Models\AuditLog;
use App\Models\HeroSlide;
use App\Models\HeroSlideStat;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\User;
use App\Services\HeroSlideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSlideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_crud_toggle_and_reorder_hero_slides_with_audit_logs_and_non_admin_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $viewer = User::factory()->create(['role' => 'viewer']);

        // Non-admin forbidden
        $this->actingAs($viewer)
            ->getJson('/api/v1/admin/hero-slides')
            ->assertForbidden();

        // Create slide 1
        $res1 = $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'feature',
            'title' => 'Peluncuran Multiview v4',
            'subtitle' => 'Pantau seluruh unit SASP secara real-time.',
            'badge_label' => 'FITUR BARU',
            'accent' => 'primary',
            'audience' => 'all',
            'image_alt' => 'Banner Multiview',
            'image_desktop' => UploadedFile::fake()->image('desktop.jpg', 1600, 600),
            'cta_primary_label' => 'Buka Multiview',
            'cta_primary_url' => '/multiview',
            'is_active' => true,
        ])->assertCreated();

        $slide1Id = $res1->json('slide.id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hero_slide.created',
            'subject_type' => 'hero_slide',
            'subject_id' => $slide1Id,
        ]);

        // Create slide 2
        $res2 = $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'event',
            'title' => 'Operasi Patroli Gabungan',
            'accent' => 'info',
            'audience' => 'all',
            'image_alt' => 'Banner Operasi',
            'image_desktop' => UploadedFile::fake()->image('event.png', 1600, 600),
            'is_active' => true,
        ])->assertCreated();

        $slide2Id = $res2->json('slide.id');

        // Update slide 1
        $this->actingAs($admin)->postJson("/api/v1/admin/hero-slides/{$slide1Id}", [
            '_method' => 'PUT',
            'slide_type' => 'feature',
            'title' => 'Peluncuran Multiview v4.1',
            'accent' => 'success',
            'audience' => 'all',
            'image_alt' => 'Banner Multiview Baru',
        ])->assertOk();

        $this->assertEquals('Peluncuran Multiview v4.1', HeroSlide::find($slide1Id)->title);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hero_slide.updated',
            'subject_type' => 'hero_slide',
            'subject_id' => $slide1Id,
        ]);

        // Toggle slide 1
        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/hero-slides/{$slide1Id}/toggle")
            ->assertOk();

        $this->assertFalse(HeroSlide::find($slide1Id)->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hero_slide.toggled',
            'subject_type' => 'hero_slide',
            'subject_id' => $slide1Id,
        ]);

        // Reorder slides
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides/reorder', [
            'ordered_ids' => [$slide2Id, $slide1Id],
        ])->assertOk();

        $this->assertEquals(1, HeroSlide::find($slide2Id)->sort_order);
        $this->assertEquals(2, HeroSlide::find($slide1Id)->sort_order);
        $this->assertTrue(AuditLog::where('action', 'hero_slide.reordered')->exists());

        // Delete slide 2
        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/hero-slides/{$slide2Id}")
            ->assertOk();

        $this->assertDatabaseMissing('hero_slides', ['id' => $slide2Id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hero_slide.deleted',
            'subject_type' => 'hero_slide',
            'subject_id' => $slide2Id,
        ]);
    }

    public function test_image_upload_reencodes_to_webp_and_rejects_undersized_dimensions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Undersized desktop image -> 422
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'feature',
            'title' => 'Gambar Kecil',
            'accent' => 'primary',
            'audience' => 'all',
            'image_alt' => 'Alt',
            'image_desktop' => UploadedFile::fake()->image('small.jpg', 1200, 500),
        ])->assertStatus(422)->assertJsonValidationErrors(['image_desktop']);

        // Valid desktop + mobile images -> stored as .webp
        $res = $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'feature',
            'title' => 'Gambar Valid',
            'accent' => 'primary',
            'audience' => 'all',
            'image_alt' => 'Alt Valid',
            'image_desktop' => UploadedFile::fake()->image('a.jpg', 1600, 600),
            'image_mobile' => UploadedFile::fake()->image('m.png', 1080, 1080),
        ])->assertCreated();

        $desktopPath = $res->json('slide.image_desktop_path');
        $mobilePath = $res->json('slide.image_mobile_path');

        $this->assertStringEndsWith('.webp', $desktopPath);
        $this->assertStringEndsWith('.webp', $mobilePath);
        Storage::disk('public')->assertExists($desktopPath);
        Storage::disk('public')->assertExists($mobilePath);
    }

    public function test_invalid_cta_urls_and_duplicate_active_live_spotlight_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['javascript:alert(1)', '//evil.com', 'http://insecure.example.com'] as $badUrl) {
            $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
                'slide_type' => 'cta',
                'title' => 'Test URL',
                'accent' => 'primary',
                'audience' => 'all',
                'image_alt' => 'Alt',
                'image_desktop' => UploadedFile::fake()->image('a.jpg', 1600, 600),
                'cta_primary_label' => 'Klik',
                'cta_primary_url' => $badUrl,
            ])->assertStatus(422)->assertJsonValidationErrors(['cta_primary_url']);
        }

        // First active live_spotlight succeeds
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'live_spotlight',
            'accent' => 'primary',
            'audience' => 'all',
            'is_active' => true,
        ])->assertCreated();

        // Second active live_spotlight fails with 422
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'live_spotlight',
            'accent' => 'info',
            'audience' => 'all',
            'is_active' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['slide_type']);
    }

    public function test_schedule_audience_max_five_and_cache_invalidation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $officerUser = User::factory()->create(['role' => 'viewer']);
        Officer::create([
            'user_id' => $officerUser->id,
            'channel_id' => 'UC_OFFICER_HERO_1',
            'handle' => '@OfficerHero1',
            'streamer_name' => 'Officer Hero',
            'officer_name' => 'Sgt. Hero',
            'callsign' => '101',
            'badge_number' => '#101',
            'department' => 'SASP',
            'rank' => 'Sergeant',
            'is_active' => true,
        ]);

        // Future slide (starts tomorrow) -> excluded
        HeroSlide::create([
            'slide_type' => 'event',
            'title' => 'Future Slide',
            'accent' => 'primary',
            'audience' => 'all',
            'image_desktop_path' => 'hero/future.webp',
            'image_alt' => 'Future',
            'starts_at' => now()->addDay(),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Expired slide (ended yesterday) -> excluded
        HeroSlide::create([
            'slide_type' => 'event',
            'title' => 'Expired Slide',
            'accent' => 'primary',
            'audience' => 'all',
            'image_desktop_path' => 'hero/expired.webp',
            'image_alt' => 'Expired',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Officer-only slide
        HeroSlide::create([
            'slide_type' => 'cta',
            'title' => 'Khusus Perwira',
            'accent' => 'dept-sasp',
            'audience' => 'officer',
            'image_desktop_path' => 'hero/officer.webp',
            'image_alt' => 'Officer',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create 6 'all' slides to test max 5 limit
        for ($i = 1; $i <= 6; $i++) {
            HeroSlide::create([
                'slide_type' => 'feature',
                'title' => "Public Slide {$i}",
                'accent' => 'primary',
                'audience' => 'all',
                'image_desktop_path' => "hero/pub{$i}.webp",
                'image_alt' => "Pub {$i}",
                'sort_order' => 10 + $i,
                'is_active' => true,
            ]);
        }

        // Guest only sees 'all' slides, max 5, no future/expired/officer slides
        $guestSlides = HeroSlideService::forAudience(null);
        $this->assertCount(5, $guestSlides);
        $guestTitles = array_column($guestSlides, 'title');
        $this->assertNotContains('Future Slide', $guestTitles);
        $this->assertNotContains('Expired Slide', $guestTitles);
        $this->assertNotContains('Khusus Perwira', $guestTitles);

        // Officer sees 'Khusus Perwira' first (sort_order 3), total capped at 5
        $officerSlides = HeroSlideService::forAudience($officerUser);
        $this->assertCount(5, $officerSlides);
        $this->assertEquals('Khusus Perwira', $officerSlides[0]['title']);

        // Cache invalidation on admin toggle
        $this->assertTrue(Cache::has('hero_slides:guest'));
        $firstSlide = HeroSlide::where('title', 'Public Slide 1')->first();
        $this->actingAs($admin)->patchJson("/api/v1/admin/hero-slides/{$firstSlide->id}/toggle")->assertOk();
        $this->assertFalse(Cache::has('hero_slides:guest'));
    }

    public function test_live_spotlight_uses_highest_viewers_count_stream_and_omits_when_none_live(): void
    {
        HeroSlide::create([
            'slide_type' => 'live_spotlight',
            'badge_label' => 'SOROTAN LIVE',
            'accent' => 'primary',
            'audience' => 'all',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Without any live stream -> omitted
        $this->assertCount(0, HeroSlideService::forAudience(null));
        HeroSlideService::forget();

        $off1 = Officer::create([
            'channel_id' => 'UC_SPOT_1',
            'handle' => '@Spot1',
            'streamer_name' => 'Streamer 1',
            'officer_name' => 'Officer One',
            'callsign' => '201',
            'badge_number' => '#201',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        $off2 = Officer::create([
            'channel_id' => 'UC_SPOT_2',
            'handle' => '@Spot2',
            'streamer_name' => 'Streamer 2',
            'officer_name' => 'Captain Two',
            'callsign' => '100',
            'badge_number' => '#100',
            'department' => 'SASP',
            'rank' => 'Captain',
            'is_active' => true,
        ]);

        OfficerBioProfile::create([
            'officer_id' => $off2->id,
            'slug' => 'captain-two',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        ActiveStream::create([
            'officer_id' => $off1->id,
            'channel_id' => $off1->channel_id,
            'video_id' => 'vid_1',
            'title' => 'Patroli Sore',
            'thumbnail_url' => 'https://i.ytimg.com/vi/vid_1/maxresdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 350,
        ]);

        ActiveStream::create([
            'officer_id' => $off2->id,
            'channel_id' => $off2->channel_id,
            'video_id' => 'vid_2',
            'title' => 'Pengejaran Kode 3 Highway',
            'thumbnail_url' => 'https://i.ytimg.com/vi/vid_2/maxresdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 1850,
        ]);

        $slides = HeroSlideService::forAudience(null);
        $this->assertCount(1, $slides);
        $this->assertEquals('live_spotlight', $slides[0]['type']);
        $this->assertEquals('Captain Two', $slides[0]['title']);
        $this->assertEquals('Pengejaran Kode 3 Highway', $slides[0]['subtitle']);
        $this->assertEquals(1850, $slides[0]['live']['viewers_count']);
        $this->assertEquals('/multiview', $slides[0]['cta_primary']['url']);
        $this->assertEquals('/@captain-two', $slides[0]['cta_secondary']['url']);
    }

    public function test_telemetry_event_increments_daily_stats_and_returns_404_for_inactive_slide(): void
    {
        $activeSlide = HeroSlide::create([
            'slide_type' => 'feature',
            'title' => 'Active Slide',
            'accent' => 'primary',
            'audience' => 'all',
            'image_desktop_path' => 'hero/active.webp',
            'image_alt' => 'Active',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $inactiveSlide = HeroSlide::create([
            'slide_type' => 'feature',
            'title' => 'Inactive Slide',
            'accent' => 'primary',
            'audience' => 'all',
            'image_desktop_path' => 'hero/inactive.webp',
            'image_alt' => 'Inactive',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $this->postJson("/api/v1/hero-slides/{$activeSlide->id}/event", ['type' => 'impression'])
            ->assertNoContent();
        $this->postJson("/api/v1/hero-slides/{$activeSlide->id}/event", ['type' => 'impression'])
            ->assertNoContent();
        $this->postJson("/api/v1/hero-slides/{$activeSlide->id}/event", ['type' => 'click'])
            ->assertNoContent();

        $stat = HeroSlideStat::where('hero_slide_id', $activeSlide->id)->first();
        $this->assertNotNull($stat);
        $this->assertEquals(2, $stat->impressions);
        $this->assertEquals(1, $stat->clicks);

        // Inactive slide returns 404
        $this->postJson("/api/v1/hero-slides/{$inactiveSlide->id}/event", ['type' => 'impression'])
            ->assertNotFound();
    }

    public function test_end_to_end_dashboard_inertia_props_and_admin_7d_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'viewer']);
        $officerUser = User::factory()->create(['role' => 'viewer']);

        $officer = Officer::create([
            'user_id' => $officerUser->id,
            'channel_id' => 'UC_E2E_OFFICER',
            'handle' => '@E2EOfficer',
            'streamer_name' => 'E2E Streamer',
            'officer_name' => 'Lt. E2E',
            'callsign' => '105',
            'badge_number' => '#105',
            'department' => 'SASP',
            'rank' => 'Lieutenant',
            'is_active' => true,
        ]);

        ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => 'vid_e2e',
            'title' => 'Patroli Malam E2E',
            'thumbnail_url' => 'https://i.ytimg.com/vi/vid_e2e/maxresdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 920,
        ]);

        // 1. Scheduled feature slide (active today, audience: all)
        $resFeature = $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'feature',
            'title' => 'Fitur Baru Hari Ini',
            'accent' => 'primary',
            'audience' => 'all',
            'image_alt' => 'Fitur',
            'image_desktop' => UploadedFile::fake()->image('f.jpg', 1600, 600),
            'starts_at' => now()->subHour()->toIso8601String(),
            'ends_at' => now()->addDay()->toIso8601String(),
            'is_active' => true,
        ])->assertCreated();
        $featureId = $resFeature->json('slide.id');

        // 2. Officer-only event slide
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'event',
            'title' => 'Briefing Khusus Perwira',
            'accent' => 'dept-sasp',
            'audience' => 'officer',
            'image_alt' => 'Briefing',
            'image_desktop' => UploadedFile::fake()->image('e.jpg', 1600, 600),
            'is_active' => true,
        ])->assertCreated();

        // 3. CTA slide for guest
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'cta',
            'title' => 'Gabung Komunitas',
            'accent' => 'info',
            'audience' => 'guest',
            'image_alt' => 'CTA',
            'image_desktop' => UploadedFile::fake()->image('c.jpg', 1600, 600),
            'cta_primary_label' => 'Daftar',
            'cta_primary_url' => '/register',
            'is_active' => true,
        ])->assertCreated();

        // 4. Live spotlight slide
        $this->actingAs($admin)->postJson('/api/v1/admin/hero-slides', [
            'slide_type' => 'live_spotlight',
            'accent' => 'primary',
            'audience' => 'all',
            'is_active' => true,
        ])->assertCreated();

        // Check dashboard prop for Guest (sees: feature, cta, live_spotlight = 3 slides)
        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('heroSlides', 3)
            );

        // Check dashboard prop for Member (sees: feature, live_spotlight = 2 slides)
        $this->actingAs($member)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('heroSlides', 2)
            );

        // Check dashboard prop for Officer (sees: feature, officer event, live_spotlight = 3 slides)
        $this->actingAs($officerUser)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('heroSlides', 3)
            );

        // Record impression & click on feature slide and verify admin index 7-day stats
        $this->postJson("/api/v1/hero-slides/{$featureId}/event", ['type' => 'impression'])->assertNoContent();
        $this->postJson("/api/v1/hero-slides/{$featureId}/event", ['type' => 'click'])->assertNoContent();

        $indexRes = $this->actingAs($admin)->getJson('/api/v1/admin/hero-slides')->assertOk();
        $slidesList = collect($indexRes->json('slides'));
        $featureRow = $slidesList->firstWhere('id', $featureId);
        $this->assertEquals(1, $featureRow['impressions_7d']);
        $this->assertEquals(1, $featureRow['clicks_7d']);
    }
}
