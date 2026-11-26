<?php

namespace Tests\Feature\LiveNotifications;

use App\Jobs\NotifyOfficerLiveJob;
use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Models\User;
use App\Services\DiscordNotificationService;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class DiscordNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficerAndStream(): array
    {
        $officer = Officer::create([
            'channel_id' => 'UC1111222233334444555566',
            'handle' => '@discordtest',
            'streamer_name' => 'Discord Streamer',
            'officer_name' => 'Gegey Alvarez',
            'callsign' => '71319',
            'department' => 'SASP',
            'rank' => 'Trooper',
            'is_active' => true,
            'is_listed' => true,
        ]);

        $stream = ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => 'xyz987ABC12',
            'title' => 'Patroli SASP Malam #imepolice',
            'thumbnail_url' => 'https://i.ytimg.com/vi/xyz987ABC12/hqdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 88,
            'last_synced_at' => now(),
        ]);

        return [$officer, $stream];
    }

    public function test_discord_channel_broadcast_and_personal_dm_run_from_live_job(): void
    {
        config([
            'app.url' => 'https://sasp-motionliferp.com',
            'services.discord.bot_token' => 'test-bot-token',
            'services.discord.live_channel_id' => '123456789012345678',
            'services.discord.dept_role_ids.SASP' => '999888777666555444',
        ]);

        Http::fake([
            'https://discord.com/api/v10/channels/123456789012345678/messages' => Http::response(['id' => 'msg-channel-1'], 200),
            'https://discord.com/api/v10/users/@me/channels' => Http::response(['id' => 'dm-chan-777'], 200),
            'https://discord.com/api/v10/channels/dm-chan-777/messages' => Http::response(['id' => 'msg-dm-1'], 200),
        ]);

        [$officer, $stream] = $this->createOfficerAndStream();

        $user = User::factory()->create([
            'discord_id' => '572766938275643402',
            'discord_username' => 'agustinus_rp',
            'discord_connected_at' => now(),
        ]);

        OfficerSubscription::create([
            'user_id' => $user->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => false,
            'notify_discord_dm' => true,
        ]);

        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldReceive('isConfigured')->once()->andReturn(false);

        (new NotifyOfficerLiveJob($officer->id, $stream->id))->handle($webPush, app(DiscordNotificationService::class));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/api/v10/channels/123456789012345678/messages'
                && str_contains((string) ($request['content'] ?? ''), '<@&999888777666555444>')
                && ($request['embeds'][0]['image']['url'] ?? null) === 'https://i.ytimg.com/vi/xyz987ABC12/hqdefault.jpg'
                && ($request['components'][0]['components'][0]['label'] ?? null) === 'Tonton Live';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/api/v10/channels/dm-chan-777/messages'
                && ($request['components'][0]['components'][0]['label'] ?? null) === 'Tonton Sekarang';
        });

        $user->refresh();
        $this->assertSame('dm-chan-777', $user->discord_dm_channel_id);
        $this->assertNull($user->discord_dm_failed_reason);
    }

    public function test_discord_dm_50007_error_records_failure_reason(): void
    {
        config([
            'services.discord.bot_token' => 'test-bot-token',
        ]);

        Http::fake([
            'https://discord.com/api/v10/users/@me/channels' => Http::response(['id' => 'dm-chan-blocked'], 200),
            'https://discord.com/api/v10/channels/dm-chan-blocked/messages' => Http::response([
                'code' => 50007,
                'message' => 'Cannot send messages to this user',
            ], 403),
        ]);

        $user = User::factory()->create([
            'discord_id' => '555444333222111000',
            'discord_username' => 'blocked_dm_user',
            'discord_connected_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/discord/test-dm')
            ->assertStatus(422)
            ->assertJson(['error_code' => 50007]);

        $user->refresh();
        $this->assertNotNull($user->discord_dm_failed_reason);
        $this->assertStringContainsString('50007', $user->discord_dm_failed_reason);
    }

    public function test_discord_dm_50278_no_mutual_guilds_records_failure_reason(): void
    {
        config([
            'services.discord.bot_token' => 'test-bot-token',
        ]);

        Http::fake([
            'https://discord.com/api/v10/users/@me/channels' => Http::response(['id' => 'dm-chan-noguild'], 200),
            'https://discord.com/api/v10/channels/dm-chan-noguild/messages' => Http::response([
                'code' => 50278,
                'message' => 'Cannot send messages to this user due to having no mutual guilds',
            ], 403),
        ]);

        $user = User::factory()->create([
            'discord_id' => '555444333222111999',
            'discord_username' => 'no_guild_user',
            'discord_connected_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/discord/test-dm')
            ->assertStatus(422)
            ->assertJson(['error_code' => 50278]);

        $user->refresh();
        $this->assertNotNull($user->discord_dm_failed_reason);
        $this->assertStringContainsString('50278', $user->discord_dm_failed_reason);
    }

    public function test_discord_oauth_callback_connects_account_and_rejects_duplicates(): void
    {
        config([
            'services.discord.client_id' => 'dc-client-id',
            'services.discord.client_secret' => 'dc-client-secret',
            'services.discord.redirect_uri' => 'https://sasp-motionliferp.com/auth/discord/callback',
        ]);

        Http::fake([
            'https://discord.com/api/oauth2/token' => Http::response(['access_token' => 'oauth-token-123'], 200),
            'https://discord.com/api/v10/users/@me' => Http::response([
                'id' => '888777666555444333',
                'username' => 'trooper_one',
                'global_name' => 'Trooper One',
                'avatar' => 'abc123avatar',
            ], 200),
        ]);

        $user = User::factory()->create();

        // Invalid state is rejected
        $this->actingAs($user)
            ->get('/auth/discord/callback?code=abc&state=wrong-state')
            ->assertRedirect('/settings?tab=notifications&discord=invalid_state');

        // Valid state connects user
        $this->actingAs($user)
            ->withSession(['discord_oauth_state' => 'valid-state-xyz'])
            ->get('/auth/discord/callback?code=valid-code&state=valid-state-xyz')
            ->assertRedirect('/settings?tab=notifications&discord=connected');

        $user->refresh();
        $this->assertSame('888777666555444333', $user->discord_id);
        $this->assertSame('Trooper One', $user->discord_username);

        // Second user attempting to bind the same discord_id gets conflict
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)
            ->withSession(['discord_oauth_state' => 'valid-state-2'])
            ->get('/auth/discord/callback?code=valid-code&state=valid-state-2')
            ->assertRedirect('/settings?tab=notifications&discord=conflict');

        // Disconnect clears fields
        $this->actingAs($user)
            ->deleteJson('/api/v1/discord/disconnect')
            ->assertOk();

        $user->refresh();
        $this->assertNull($user->discord_id);
        $this->assertNull($user->discord_dm_channel_id);
    }

    public function test_subscription_endpoint_persists_notify_discord_dm_preference(): void
    {
        [$officer] = $this->createOfficerAndStream();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/officers/{$officer->id}/subscribe", [
                'notify_in_app' => true,
                'notify_web_push' => true,
                'notify_discord_dm' => false,
            ])
            ->assertStatus(201)
            ->assertJsonPath('subscription.notify_discord_dm', false);

        $this->assertDatabaseHas('officer_subscriptions', [
            'user_id' => $user->id,
            'officer_id' => $officer->id,
            'notify_discord_dm' => false,
        ]);
    }
}
