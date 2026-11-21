<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "Momen Aksi" (/momen) and "Clip Studio" (/clip-studio) replaced the old
 * /action-clips and /clipper URLs; old links must keep working.
 */
class MomenAndClipStudioRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_urls_render_renamed_pages(): void
    {
        $this->get('/momen')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('MomenAksi'));

        $this->get('/clip-studio')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ClipStudio'));
    }

    public function test_old_momen_urls_redirect_permanently(): void
    {
        foreach (['/action-clips', '/clips', '/tacclips'] as $old) {
            $this->get($old)->assertStatus(301)->assertRedirect('/momen');
        }
    }

    public function test_old_clip_studio_urls_redirect_and_keep_the_query(): void
    {
        foreach (['/clipper', '/clipper/pricing', '/clipper/police-guide', '/clipper-pricing', '/police-clipper', '/pricing'] as $old) {
            $this->get($old)->assertStatus(301)->assertRedirect('/clip-studio');
        }

        // Prefill links from the watch page and Momen Aksi must survive the redirect
        // (Laravel normalises the parameter order; the values are what matter).
        $location = $this->get('/clipper?url=https%3A%2F%2Fyoutu.be%2Fabc&start=10&end=40')
            ->assertStatus(301)
            ->headers->get('Location');

        $this->assertSame('/clip-studio', parse_url($location, PHP_URL_PATH));
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(['end' => '40', 'start' => '10', 'url' => 'https://youtu.be/abc'], $query);
    }
}
