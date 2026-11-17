<?php

namespace Tests\Feature\Seo;

use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\Squad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoAndSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createOfficerWithProfile(string $slug = 'gusti_patrol'): OfficerBioProfile
    {
        $officer = Officer::create([
            'channel_id' => 'UC_TEST_' . uniqid(),
            'officer_name' => 'Gusti Patrol',
            'streamer_name' => 'Gusti Stream',
            'callsign' => '101',
            'badge_number' => '9901',
            'department' => 'SASP',
            'handle' => '@gusti_patrol',
            'is_active' => true,
        ]);

        return OfficerBioProfile::create([
            'officer_id' => $officer->id,
            'slug' => $slug,
            'bio_tagline' => 'Unit Reaksi Cepat SASP',
            'theme' => 'ranger_forest',
            'is_suspended' => false,
        ]);
    }

    public function test_sitemap_xml_returns_valid_xml_with_core_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('/multiview</loc>', $content);
        $this->assertStringContainsString('/officers</loc>', $content);
        $this->assertStringContainsString('/ranking</loc>', $content);
        $this->assertStringContainsString('/action-clips</loc>', $content);
        $this->assertStringContainsString('/bio-link</loc>', $content);
        $this->assertStringContainsString('/faq</loc>', $content);
        $this->assertStringContainsString('/about</loc>', $content);
        $this->assertStringContainsString('/updates</loc>', $content);
        $this->assertStringContainsString('/feedback</loc>', $content);
    }

    public function test_sitemap_xml_includes_active_officer_bio_profiles(): void
    {
        $profile = $this->createOfficerWithProfile('patrol_alpha_1');

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString("/@patrol_alpha_1</loc>", $response->getContent());
    }

    public function test_sitemap_xml_includes_public_squads(): void
    {
        $user = User::factory()->create();
        Squad::create([
            'user_id' => $user->id,
            'name' => 'High Speed Interceptor',
            'slug' => 'squad-hsi-unit',
            'visibility' => 'public',
            'is_hidden' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('squad=squad-hsi-unit</loc>', $response->getContent());
    }

    public function test_sitemap_redirects_to_sitemap_xml(): void
    {
        $response = $this->get('/sitemap');

        $response->assertRedirect('/sitemap.xml');
        $response->assertStatus(301);
    }

    public function test_robots_txt_contains_sitemap_and_disallowed_paths(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $robotsContent);
        $this->assertStringContainsString('sitemap.xml', $robotsContent);
        $this->assertStringContainsString('Disallow: /admin/', $robotsContent);
        $this->assertStringContainsString('Disallow: /api/', $robotsContent);
        $this->assertStringContainsString('Allow: /@*', $robotsContent);
    }

    public function test_public_pages_render_meta_tags_and_canonical_urls(): void
    {
        // 1. Home Dashboard
        $homeRes = $this->get('/');
        $homeRes->assertOk();
        $this->assertStringContainsString('<link rel="canonical"', $homeRes->getContent());
        $this->assertStringContainsString('og:site_name', $homeRes->getContent());

        // 2. Officer Directory
        $dirRes = $this->get('/officers');
        $dirRes->assertOk();
        $this->assertStringContainsString('Direktori Perwira', $dirRes->getContent());

        // 3. Ranking Page
        $rankRes = $this->get('/ranking');
        $rankRes->assertOk();
        $this->assertStringContainsString('Peringkat', $rankRes->getContent());

        // 4. Multiview Theater
        $mvRes = $this->get('/multiview');
        $mvRes->assertOk();
        $this->assertStringContainsString('Tactical Multiview', $mvRes->getContent());
    }

    public function test_officer_bio_profile_renders_rich_jsonld_and_profile_meta(): void
    {
        $profile = $this->createOfficerWithProfile('bio_seo_officer');

        $response = $this->get('/@bio_seo_officer');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('ProfilePage', $content);
        $this->assertStringContainsString('Gusti Patrol', $content);
        $this->assertStringContainsString('/@bio_seo_officer', $content);
    }
}

