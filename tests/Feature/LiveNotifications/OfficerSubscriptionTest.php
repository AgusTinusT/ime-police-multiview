<?php

namespace Tests\Feature\LiveNotifications;

use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(array $overrides = []): Officer
    {
        return Officer::create(array_merge([
            'channel_id' => 'UC1234567890123456789012',
            'handle' => '@testofficer',
            'streamer_name' => 'Streamer Test',
            'officer_name' => 'John Doe',
            'callsign' => '1-A-10',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'is_listed' => true,
        ], $overrides));
    }

    public function test_guest_cannot_list_or_toggle_subscriptions(): void
    {
        $officer = $this->createOfficer();

        $this->getJson('/api/v1/subscriptions')->assertStatus(401);
        $this->postJson("/api/v1/officers/{$officer->id}/subscribe")->assertStatus(401);
        $this->deleteJson("/api/v1/officers/{$officer->id}/subscribe")->assertStatus(401);
    }

    public function test_authenticated_user_can_subscribe_list_and_unsubscribe(): void
    {
        $user = User::factory()->create();
        $officer = $this->createOfficer();

        $this->actingAs($user)
            ->postJson("/api/v1/officers/{$officer->id}/subscribe", [
                'notify_in_app' => true,
                'notify_web_push' => false,
            ])
            ->assertStatus(201)
            ->assertJson([
                'subscribed' => true,
                'subscribers_count' => 1,
                'subscription' => [
                    'officer_id' => $officer->id,
                    'notify_in_app' => true,
                    'notify_web_push' => false,
                ],
            ]);

        // Re-subscribing updates preferences idempotently without duplicate rows
        $this->actingAs($user)
            ->postJson("/api/v1/officers/{$officer->id}/subscribe", [
                'notify_in_app' => true,
                'notify_web_push' => true,
            ])
            ->assertStatus(200)
            ->assertJson([
                'subscribed' => true,
                'subscribers_count' => 1,
                'subscription' => [
                    'notify_web_push' => true,
                ],
            ]);

        $this->assertDatabaseCount('officer_subscriptions', 1);

        $this->actingAs($user)
            ->getJson('/api/v1/subscriptions')
            ->assertOk()
            ->assertJson([
                'officer_ids' => [$officer->id],
            ])
            ->assertJsonCount(1, 'subscriptions');

        $this->actingAs($user)
            ->deleteJson("/api/v1/officers/{$officer->id}/subscribe")
            ->assertOk()
            ->assertJson([
                'subscribed' => false,
                'subscribers_count' => 0,
            ]);

        $this->assertDatabaseMissing('officer_subscriptions', [
            'user_id' => $user->id,
            'officer_id' => $officer->id,
        ]);
    }

    public function test_cannot_subscribe_to_inactive_officer_or_with_invalid_payload(): void
    {
        $user = User::factory()->create();
        $inactive = $this->createOfficer([
            'channel_id' => 'UC9999999999999999999999',
            'callsign' => '1-A-99',
            'is_active' => false,
        ]);
        $active = $this->createOfficer();

        $this->actingAs($user)
            ->postJson("/api/v1/officers/{$inactive->id}/subscribe")
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson("/api/v1/officers/{$active->id}/subscribe", [
                'notify_in_app' => 'not-a-bool',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['notify_in_app']);

        $this->assertSame(0, OfficerSubscription::count());
    }
}
