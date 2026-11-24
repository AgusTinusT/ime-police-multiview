<?php

namespace Tests\Feature\Officer;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoredAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private User $admin;
    private User $owner;
    private Officer $officer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['avatars.enabled' => true, 'avatars.disk' => 's3']);
        Storage::fake('s3');

        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->owner = User::factory()->create(['role' => 'officer', 'email_verified_at' => now()]);

        $this->officer = Officer::create([
            'user_id' => $this->owner->id,
            'channel_id' => 'UC_TEST_STORED_01',
            'handle' => '@storedavatar',
            'streamer_name' => 'Stored Streamer',
            'officer_name' => 'Ofc. Stored',
            'callsign' => '1-ADAM-20',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'station' => 'Central',
            'avatar_url' => 'https://yt3.ggpht.com/youtube_auto.jpg',
            'is_active' => true,
        ]);
    }

    private function fakeImage(string $pattern = '*'): void
    {
        Http::fake([$pattern => Http::response(base64_decode(self::PNG_1X1), 200, ['Content-Type' => 'image/png'])]);
    }

    private function adminPayload(array $overrides = []): array
    {
        return array_merge([
            'handle' => '@storedavatar',
            'streamer_name' => 'Stored Streamer',
            'officer_name' => 'Ofc. Stored',
            'callsign' => '1-ADAM-20',
            'department' => 'LSPD',
        ], $overrides);
    }

    public function test_admin_custom_avatar_link_is_stored_on_s3(): void
    {
        $this->fakeImage();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
                'avatar_url' => 'https://img.example.test/photo.png',
            ]))
            ->assertOk();

        $officer = $this->officer->fresh();
        $this->assertTrue($officer->avatar_is_custom);
        $this->assertSame('s3', $officer->avatar_disk);
        $this->assertSame('https://img.example.test/photo.png', $officer->avatar_source_url);
        $this->assertStringEndsWith('/media/avatars/' . basename($officer->avatar_path), $officer->avatar_url);
        Storage::disk('s3')->assertExists($officer->avatar_path);
    }

    public function test_stored_avatar_is_served_with_cache_headers(): void
    {
        $this->fakeImage();
        $this->actingAs($this->admin)->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
            'avatar_url' => 'https://img.example.test/photo.png',
        ]))->assertOk();

        $path = parse_url($this->officer->fresh()->avatar_url, PHP_URL_PATH);
        auth()->logout();

        $response = $this->get($path);
        $response->assertOk();
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        $this->assertSame(base64_decode(self::PNG_1X1), $response->streamedContent());
    }

    public function test_hidden_stored_avatar_is_not_served_publicly(): void
    {
        $this->fakeImage();
        $this->actingAs($this->admin)->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
            'avatar_url' => 'https://img.example.test/photo.png',
        ]))->assertOk();

        $officer = $this->officer->fresh();
        $officer->update(['avatar_hidden' => true]);
        $path = parse_url($officer->avatar_url, PHP_URL_PATH);

        auth()->logout();
        $this->get($path)->assertNotFound();
        $this->actingAs($this->owner)->get($path)->assertOk();
    }

    public function test_broken_link_is_rejected_and_avatar_unchanged(): void
    {
        Http::fake(['*' => Http::response('Not found', 404)]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
                'avatar_url' => 'https://img.example.test/missing.png',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar_url']);

        $this->assertSame('https://yt3.ggpht.com/youtube_auto.jpg', $this->officer->fresh()->avatar_url);
    }

    public function test_non_image_link_is_rejected(): void
    {
        Http::fake(['*' => Http::response('<html>hello</html>', 200, ['Content-Type' => 'text/html'])]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
                'avatar_url' => 'https://img.example.test/page.html',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar_url']);
    }

    public function test_private_network_link_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
                'avatar_url' => 'http://127.0.0.1/secret.png',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar_url']);

        Http::assertNothingSent();
    }

    public function test_replacing_avatar_deletes_previous_file(): void
    {
        $this->fakeImage();
        $store = fn (string $url) => $this->actingAs($this->admin)
            ->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload(['avatar_url' => $url]))
            ->assertOk();

        $store('https://img.example.test/first.png');
        $firstPath = $this->officer->fresh()->avatar_path;

        $store('https://img.example.test/second.png');
        $second = $this->officer->fresh();

        $this->assertNotSame($firstPath, $second->avatar_path);
        Storage::disk('s3')->assertMissing($firstPath);
        Storage::disk('s3')->assertExists($second->avatar_path);
    }

    public function test_resubmitting_stored_url_does_not_download_again(): void
    {
        $this->fakeImage();
        $this->actingAs($this->admin)->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
            'avatar_url' => 'https://img.example.test/photo.png',
        ]))->assertOk();

        $officer = $this->officer->fresh();
        Http::fake();

        // Admin form resends the stored URL unchanged.
        $this->actingAs($this->admin)->putJson("/api/v1/officers/{$officer->id}", $this->adminPayload([
            'avatar_url' => $officer->avatar_url,
            'officer_name' => 'Ofc. Stored Renamed',
        ]))->assertOk();

        // Owner settings form resends it too (not a YouTube host, but unchanged).
        $this->actingAs($this->owner)->putJson('/api/v1/officer/profile', [
            'avatar_url' => $officer->avatar_url,
            'streamer_name' => 'Stored Streamer Two',
        ])->assertOk();

        Http::assertNothingSent();
        $this->assertSame($officer->avatar_path, $officer->fresh()->avatar_path);
    }

    public function test_switching_to_non_custom_link_detaches_stored_file(): void
    {
        $this->fakeImage();
        $this->actingAs($this->admin)->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
            'avatar_url' => 'https://img.example.test/photo.png',
        ]))->assertOk();
        $path = $this->officer->fresh()->avatar_path;

        // e.g. YouTube subscriber sync after the custom lock is turned off
        $this->officer->fresh()->update(['avatar_url' => 'https://yt3.ggpht.com/new_auto.jpg', 'avatar_is_custom' => false]);

        $officer = $this->officer->fresh();
        $this->assertNull($officer->avatar_path);
        $this->assertNull($officer->avatar_disk);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_deleting_officer_deletes_stored_file(): void
    {
        $this->fakeImage();
        $this->actingAs($this->admin)->putJson("/api/v1/officers/{$this->officer->id}", $this->adminPayload([
            'avatar_url' => 'https://img.example.test/photo.png',
        ]))->assertOk();
        $path = $this->officer->fresh()->avatar_path;

        $this->actingAs($this->admin)->deleteJson("/api/v1/officers/{$this->officer->id}")->assertOk();

        Storage::disk('s3')->assertMissing($path);
    }

    public function test_backfill_command_stores_existing_custom_links(): void
    {
        $this->fakeImage();
        $this->officer->update(['avatar_url' => 'https://img.example.test/legacy.png', 'avatar_is_custom' => true]);

        $this->artisan('officer:store-avatars')->assertSuccessful();

        $officer = $this->officer->fresh();
        $this->assertNotNull($officer->avatar_path);
        $this->assertSame('https://img.example.test/legacy.png', $officer->avatar_source_url);
        Storage::disk('s3')->assertExists($officer->avatar_path);
    }
}
