<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoClip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoClipTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_url_returns_valid_response(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/clips/check-url', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'can_trim' => true,
                'video_id' => 'dQw4w9WgXcQ',
            ]);
    }
}
