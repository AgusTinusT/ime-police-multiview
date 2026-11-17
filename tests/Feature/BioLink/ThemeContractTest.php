<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ThemeContractTest extends TestCase
{
    use RefreshDatabase;

    private const THEME_ONLY_BUTTON_STYLES = ['pursuit_stripe', 'plate_carrier'];

    // Must match BACKDROPS / HEADINGS in resources/js/Components/BioLink/BioLinkView.vue.
    private const BACKDROPS = [
        'grid', 'glass_radar', 'trooper_highway', 'metro_skyline', 'county_star',
        'ranger_forest', 'stealth_carbon', 'srt_pursuit', 'swat_nightops',
    ];
    private const HEADINGS = ['default', 'pursuit', 'stencil'];

    public function test_every_configured_backdrop_is_registered_on_the_bio_page(): void
    {
        $page = file_get_contents(resource_path('js/Components/BioLink/BioLinkView.vue'));

        foreach (config('biolinks.theme_presets') as $key => $theme) {
            if ($theme['backdrop'] === 'grid') {
                continue;
            }
            $this->assertStringContainsString("{$theme['backdrop']}: lazy(", $page, "{$key}: backdrop '{$theme['backdrop']}' is not registered in BioLinkView.vue");
        }
    }

    public function test_every_theme_preset_follows_the_contract(): void
    {
        $buttonStyles = array_merge(config('biolinks.button_styles'), self::THEME_ONLY_BUTTON_STYLES);

        foreach (config('biolinks.theme_presets') as $key => $theme) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $theme['accent'], "{$key}.accent");
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $theme['accent_2'], "{$key}.accent_2");
            $this->assertNotSame(strtolower($theme['accent']), strtolower($theme['accent_2']), "{$key}: accent_2 must differ from accent");
            $this->assertTrue($theme['surface'] === null || preg_match('/^#[0-9a-f]{6}$/i', $theme['surface']) === 1, "{$key}.surface");
            $this->assertContains($theme['backdrop'], self::BACKDROPS, "{$key}.backdrop");
            $this->assertContains($theme['heading'], self::HEADINGS, "{$key}.heading");
            $this->assertTrue($theme['button_style'] === null || in_array($theme['button_style'], $buttonStyles, true), "{$key}.button_style");
        }
    }

    public function test_public_bio_page_receives_theme_contract(): void
    {
        $officer = Officer::create([
            'channel_id' => 'UC_THEME_CONTRACT',
            'handle' => '@themecontract',
            'streamer_name' => 'Theme Streamer',
            'officer_name' => 'Ofc. Theme',
            'callsign' => '1-T-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);
        $profile->update(['slug' => 'theme-contract', 'theme_preset' => 'srt_pursuit']);

        $this->get('/@theme-contract')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/BioLink')
                ->where('profile.theme_preset', 'srt_pursuit')
                ->where('themes.srt_pursuit.backdrop', 'srt_pursuit')
                ->where('themes.srt_pursuit.button_style', 'pursuit_stripe')
                ->has('themes.default_glass.accent_2')
            );
    }

    public function test_admin_can_open_theme_preview_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dev/bio-themes?slug=sample')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dev/BioThemes')
                ->where('sampleSlug', 'sample')
                ->has('themes.srt_pursuit')
            );
    }

    public function test_non_admin_and_guest_cannot_open_theme_preview_page(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)->get('/dev/bio-themes')->assertForbidden();
        auth()->logout();
        $this->get('/dev/bio-themes')->assertRedirect('/login');
    }
}
