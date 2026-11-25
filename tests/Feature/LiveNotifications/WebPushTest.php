<?php

namespace Tests\Feature\LiveNotifications;

use App\Jobs\NotifyOfficerLiveJob;
use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_check_config_and_delete_push_subscription(): void
    {
        config([
            'services.webpush.public_key' => 'BPtestPublicKey123',
            'services.webpush.private_key' => 'testPrivateKey123',
        ]);

        $this->postJson('/api/v1/push-subscriptions', [])->assertStatus(401);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/push-subscriptions/config')
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'public_key' => 'BPtestPublicKey123',
                'subscriptions_count' => 0,
            ]);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/sample-endpoint-123';

        $this->actingAs($user)
            ->postJson('/api/v1/push-subscriptions', [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'BOr7fakePublicKeyData',
                    'auth' => 'fakeAuthSecret123',
                ],
                'content_encoding' => 'aes128gcm',
            ])
            ->assertStatus(201)
            ->assertJson(['subscriptions_count' => 1]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/push-subscriptions', [
                'endpoint' => $endpoint,
            ])
            ->assertOk()
            ->assertJson(['subscriptions_count' => 0]);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_web_push_service_cleans_up_expired_410_subscriptions(): void
    {
        config([
            'services.webpush.public_key' => 'BPtestPublicKey123',
            'services.webpush.private_key' => 'testPrivateKey123',
        ]);

        $user = User::factory()->create();
        $activeSub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/active',
            'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example.com/active'),
            'public_key' => 'pub1',
            'auth_token' => 'auth1',
            'content_encoding' => 'aes128gcm',
        ]);
        $expiredSub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/expired',
            'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example.com/expired'),
            'public_key' => 'pub2',
            'auth_token' => 'auth2',
            'content_encoding' => 'aes128gcm',
        ]);

        $successReport = Mockery::mock(MessageSentReport::class);
        $successReport->shouldReceive('getEndpoint')->andReturn($activeSub->endpoint);
        $successReport->shouldReceive('isSuccess')->andReturn(true);

        $goneResponse = Mockery::mock(ResponseInterface::class);
        $goneResponse->shouldReceive('getStatusCode')->andReturn(410);

        $expiredReport = Mockery::mock(MessageSentReport::class);
        $expiredReport->shouldReceive('getEndpoint')->andReturn($expiredSub->endpoint);
        $expiredReport->shouldReceive('isSuccess')->andReturn(false);
        $expiredReport->shouldReceive('isSubscriptionExpired')->andReturn(true);
        $expiredReport->shouldReceive('getResponse')->andReturn($goneResponse);

        $mockClient = Mockery::mock(WebPush::class);
        $mockClient->shouldReceive('queueNotification')->twice();
        $mockClient->shouldReceive('flush')->once()->andReturn((function () use ($successReport, $expiredReport) {
            yield $successReport;
            yield $expiredReport;
        })());

        $service = new class($mockClient) extends WebPushService
        {
            public function __construct(private readonly WebPush $client) {}

            protected function makeWebPushClient(): WebPush
            {
                return $this->client;
            }
        };

        $result = $service->sendToSubscriptions([$activeSub, $expiredSub], ['title' => 'Live!']);

        $this->assertSame(['sent' => 1, 'expired' => 1, 'failed' => 0], $result);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $activeSub->id]);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $expiredSub->id]);
    }

    public function test_notify_officer_live_job_dispatches_web_push_when_enabled(): void
    {
        $officer = Officer::create([
            'channel_id' => 'UC1234567890123456789012',
            'handle' => '@pushofficer',
            'streamer_name' => 'Push Streamer',
            'officer_name' => 'Sgt. Foley',
            'callsign' => '3-C-12',
            'department' => 'BCSO',
            'rank' => 'Sergeant',
            'is_active' => true,
            'is_listed' => true,
        ]);
        $stream = ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => 'dQw4w9WgXcQ',
            'title' => 'Patroli BCSO #imepolice',
            'thumbnail_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 42,
            'last_synced_at' => now(),
        ]);
        $user = User::factory()->create();
        OfficerSubscription::create([
            'user_id' => $user->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => true,
        ]);
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/device-1',
            'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example.com/device-1'),
            'public_key' => 'pub1',
            'auth_token' => 'auth1',
            'content_encoding' => 'aes128gcm',
        ]);

        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldReceive('isConfigured')->once()->andReturn(true);
        $webPush->shouldReceive('sendOfficerLiveNotification')->once()->andReturn([
            'sent' => 1,
            'expired' => 0,
            'failed' => 0,
        ]);

        (new NotifyOfficerLiveJob($officer->id, $stream->id))->handle($webPush);

        $this->assertSame(1, $user->notifications()->count());
    }
}
