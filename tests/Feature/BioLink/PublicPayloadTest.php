<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPayloadTest extends TestCase
{
    use RefreshDatabase;

    private Officer $officer;
    private string $slug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officer = Officer::create([
            'channel_id' => 'UC_PAYLOAD_TEST',
            'handle' => '@payloadtest',
            'streamer_name' => 'Payload Streamer',
            'officer_name' => 'Ofc. Payload',
            'callsign' => '1-P-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        $profile = BioProfileService::ensureBioProfile($this->officer);
        $this->slug = $profile->slug;
    }

    public function test_guest_visiting_bio_page_does_not_contain_prefetch_script(): void
    {
        if (!file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Vite manifest.json not found in public/build');
        }

        $this->withoutHotFile(function () {
            $response = $this->get("/@{$this->slug}");
            $response->assertOk();

            $content = $response->getContent();
            $this->assertStringNotContainsString('rel="prefetch"', $content);
            $this->assertStringNotContainsString('makeLink = (asset)', $content);
        });
    }

    public function test_authenticated_user_visiting_bio_page_does_not_contain_prefetch_script(): void
    {
        if (!file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Vite manifest.json not found in public/build');
        }

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->withoutHotFile(function () use ($user) {
            $response = $this->actingAs($user)->get("/@{$this->slug}");
            $response->assertOk();

            $content = $response->getContent();
            $this->assertStringNotContainsString('rel="prefetch"', $content);
            $this->assertStringNotContainsString('makeLink = (asset)', $content);
        });
    }

    public function test_authenticated_user_visiting_dashboard_triggers_prefetch(): void
    {
        if (!file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Vite manifest.json not found in public/build');
        }

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->withoutHotFile(function () use ($user) {
            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertOk();

            $content = $response->getContent();
            $hasPrefetch = str_contains($content, 'rel="prefetch"') ||
                str_contains($content, 'makeLink = (asset)');

            $this->assertTrue($hasPrefetch, 'Dashboard for authenticated user should include Vite prefetch');
        });
    }

    private function withoutHotFile(callable $callback): void
    {
        $hotFile = public_path('hot');
        $hotExisted = file_exists($hotFile);
        $hotContent = null;
        if ($hotExisted) {
            $hotContent = file_get_contents($hotFile);
            @unlink($hotFile);
        }

        try {
            $callback();
        } finally {
            if ($hotExisted && $hotContent !== null) {
                file_put_contents($hotFile, $hotContent);
            }
        }
    }
}
