<?php

namespace Tests\Feature\User;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBlockedOfficerTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(string $name = 'John Doe', string $callsign = '101', string $dept = 'LSPD'): Officer
    {
        return Officer::create([
            'channel_id' => 'UC' . substr(md5($name), 0, 22),
            'officer_name' => $name,
            'callsign' => $callsign,
            'department' => $dept,
            'handle' => '@' . strtolower(str_replace(' ', '', $name)),
            'streamer_name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_blocked_officers_endpoints(): void
    {
        $officer = $this->createOfficer();

        $this->getJson('/api/v1/user/blocked-officers')->assertUnauthorized();
        $this->postJson('/api/v1/user/blocked-officers', ['officer_id' => $officer->id])->assertUnauthorized();
        $this->postJson('/api/v1/user/blocked-officers/sync', ['officer_ids' => [$officer->id]])->assertUnauthorized();
        $this->deleteJson("/api/v1/user/blocked-officers/{$officer->id}")->assertUnauthorized();
        $this->deleteJson('/api/v1/user/blocked-officers/all')->assertUnauthorized();
    }

    public function test_user_can_block_officer(): void
    {
        $user = User::factory()->create();
        $officer = $this->createOfficer('Officer Alpha', '201');

        $response = $this->actingAs($user)->postJson('/api/v1/user/blocked-officers', [
            'officer_id' => $officer->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Perwira berhasil disembunyikan dari feed.',
                'blocked_officer_ids' => [$officer->id],
            ]);

        $this->assertDatabaseHas('user_blocked_officers', [
            'user_id' => $user->id,
            'officer_id' => $officer->id,
        ]);
    }

    public function test_user_cannot_block_nonexistent_officer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/user/blocked-officers', [
            'officer_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['officer_id']);
    }

    public function test_user_can_unblock_officer(): void
    {
        $user = User::factory()->create();
        $officer = $this->createOfficer('Officer Bravo', '202');
        $user->blockedOfficers()->attach($officer->id);

        $response = $this->actingAs($user)->deleteJson("/api/v1/user/blocked-officers/{$officer->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Perwira berhasil ditampilkan kembali.',
                'blocked_officer_ids' => [],
            ]);

        $this->assertDatabaseMissing('user_blocked_officers', [
            'user_id' => $user->id,
            'officer_id' => $officer->id,
        ]);
    }

    public function test_user_can_sync_blocked_officers_from_client(): void
    {
        $user = User::factory()->create();
        $officer1 = $this->createOfficer('Officer 1', '301');
        $officer2 = $this->createOfficer('Officer 2', '302');

        $response = $this->actingAs($user)->postJson('/api/v1/user/blocked-officers/sync', [
            'officer_ids' => [$officer1->id, $officer2->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Daftar perwira berhasil disinkronkan.',
            ]);

        $this->assertEqualsCanonicalizing(
            [$officer1->id, $officer2->id],
            $user->fresh()->blockedOfficers()->pluck('officers.id')->all()
        );
    }

    public function test_user_can_unblock_all_officers(): void
    {
        $user = User::factory()->create();
        $officer1 = $this->createOfficer('Officer 1', '401');
        $officer2 = $this->createOfficer('Officer 2', '402');
        $user->blockedOfficers()->attach([$officer1->id, $officer2->id]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/user/blocked-officers/all');

        $response->assertOk()
            ->assertJson([
                'message' => 'Semua perwira berhasil ditampilkan kembali.',
                'blocked_officer_ids' => [],
            ]);

        $this->assertCount(0, $user->fresh()->blockedOfficers);
    }

    public function test_user_can_fetch_blocked_officers_list(): void
    {
        $user = User::factory()->create();
        $officer = $this->createOfficer('Officer X', '501', 'BCSO');
        $user->blockedOfficers()->attach($officer->id);

        $response = $this->actingAs($user)->getJson('/api/v1/user/blocked-officers');

        $response->assertOk()
            ->assertJson([
                'blocked_officer_ids' => [$officer->id],
            ]);

        $this->assertCount(1, $response->json('blocked_officers'));
        $this->assertEquals('Officer X', $response->json('blocked_officers.0.officer_name'));
    }

    public function test_inertia_shares_blocked_officer_ids_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $officer = $this->createOfficer('Officer Y', '601');
        $user->blockedOfficers()->attach($officer->id);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertContains($officer->id, $page['props']['auth']['user']['blocked_officer_ids']);
    }
}
