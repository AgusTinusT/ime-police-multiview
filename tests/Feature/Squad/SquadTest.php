<?php

namespace Tests\Feature\Squad;

use App\Models\ActiveStream;
use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\Squad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SquadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createOfficer(string $name, string $callsign, string $dept = 'LSPD'): Officer
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

    public function test_user_can_create_squad_with_valid_data(): void
    {
        $user = User::factory()->create();
        $officer1 = $this->createOfficer('John Doe', '101');
        $officer2 = $this->createOfficer('Jane Smith', '102');

        $response = $this->actingAs($user)->postJson('/api/v1/squads', [
            'name' => 'SWAT Team 5',
            'description' => 'Unit taktis SWAT',
            'visibility' => 'public',
            'officer_ids' => [$officer1->id, $officer2->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.name', 'SWAT Team 5');
        $response->assertJsonPath('data.visibility', 'public');
        $response->assertJsonPath('data.officers.0.id', $officer1->id);
        $response->assertJsonPath('data.officers.1.id', $officer2->id);

        $this->assertDatabaseHas('squads', [
            'user_id' => $user->id,
            'name' => 'SWAT Team 5',
            'visibility' => 'public',
        ]);

        $squadId = $response->json('data.id');
        $this->assertDatabaseHas('squad_officer', [
            'squad_id' => $squadId,
            'officer_id' => $officer1->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('squad_officer', [
            'squad_id' => $squadId,
            'officer_id' => $officer2->id,
            'position' => 1,
        ]);
    }

    public function test_mine_endpoint_returns_user_squads_ordered_by_updated_at(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $officer = $this->createOfficer('Officer One', '101');

        $squad1 = Squad::create([
            'user_id' => $userA->id,
            'name' => 'Squad Lama',
            'slug' => 'squad-lama-111111',
        ]);
        $squad1->timestamps = false;
        $squad1->updated_at = now()->subDay();
        $squad1->save();
        $squad1->officers()->attach([$officer->id => ['position' => 0]]);

        $squad2 = Squad::create([
            'user_id' => $userA->id,
            'name' => 'Squad Baru',
            'slug' => 'squad-baru-222222',
        ]);
        $squad2->timestamps = false;
        $squad2->updated_at = now();
        $squad2->save();
        $squad2->officers()->attach([$officer->id => ['position' => 0]]);

        $squadB = Squad::create([
            'user_id' => $userB->id,
            'name' => 'Squad User B',
            'slug' => 'squad-b-333333',
        ]);
        $squadB->officers()->attach([$officer->id => ['position' => 0]]);

        // Unauthenticated -> 401 or redirect to login
        auth()->logout();
        $this->getJson('/api/v1/squads/mine')->assertUnauthorized();

        // User A gets their squads in desc order
        $res = $this->actingAs($userA)->getJson('/api/v1/squads/mine');
        $res->assertOk();
        $data = $res->json('data');

        $this->assertCount(2, $data);
        $this->assertSame('Squad Baru', $data[0]['name']);
        $this->assertSame('Squad Lama', $data[1]['name']);
    }

    public function test_cannot_create_squad_with_invalid_or_exceeded_officers(): void
    {
        $user = User::factory()->create();

        // Empty officers
        $this->actingAs($user)->postJson('/api/v1/squads', [
            'name' => 'Empty Squad',
            'officer_ids' => [],
        ])->assertStatus(422);

        // Exceeding max officers (config is 12)
        $officerIds = [];
        for ($i = 1; $i <= 13; $i++) {
            $o = $this->createOfficer("Officer {$i}", "C{$i}");
            $officerIds[] = $o->id;
        }

        $this->actingAs($user)->postJson('/api/v1/squads', [
            'name' => 'Too Many Squad',
            'officer_ids' => $officerIds,
        ])->assertStatus(422);

        // Non-existent officer
        $this->actingAs($user)->postJson('/api/v1/squads', [
            'name' => 'Ghost Squad',
            'officer_ids' => [999999],
        ])->assertStatus(422);
    }

    public function test_cannot_exceed_max_per_user_quota(): void
    {
        config(['squads.max_per_user' => 2]);
        $user = User::factory()->create();
        $officer = $this->createOfficer('Officer One', '101');

        // Create 2 squads
        for ($i = 1; $i <= 2; $i++) {
            $this->actingAs($user)->postJson('/api/v1/squads', [
                'name' => "Squad {$i}",
                'officer_ids' => [$officer->id],
            ])->assertCreated();
        }

        // 3rd squad must be rejected
        $res = $this->actingAs($user)->postJson('/api/v1/squads', [
            'name' => 'Squad 3',
            'officer_ids' => [$officer->id],
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'Anda telah mencapai batas maksimal 2 squad.']);
    }

    public function test_blocked_words_in_name_are_rejected(): void
    {
        config(['squads.blocked_words' => ['kasar', 'toxicword']]);
        $user = User::factory()->create();
        $officer = $this->createOfficer('Officer One', '101');

        $res = $this->actingAs($user)->postJson('/api/v1/squads', [
            'name' => 'Squad KASAR 123',
            'officer_ids' => [$officer->id],
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'Nama squad mengandung kata yang tidak diperbolehkan.']);
    }

    public function test_update_and_delete_restricted_to_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $officer1 = $this->createOfficer('Officer One', '101');
        $officer2 = $this->createOfficer('Officer Two', '102');

        $squad = Squad::create([
            'user_id' => $owner->id,
            'name' => 'Original Name',
            'slug' => 'original-name-123456',
            'visibility' => 'private',
        ]);
        $squad->officers()->attach([$officer1->id => ['position' => 0]]);

        // Other user attempts update -> 403
        $this->actingAs($otherUser)->putJson('/api/v1/squads/' . $squad->id, [
            'name' => 'Hacked Name',
            'officer_ids' => [$officer1->id],
        ])->assertForbidden();

        // Other user attempts delete -> 403
        $this->actingAs($otherUser)->deleteJson('/api/v1/squads/' . $squad->id)
            ->assertForbidden();

        // Owner updates -> 200
        $this->actingAs($owner)->putJson('/api/v1/squads/' . $squad->id, [
            'name' => 'Updated Name',
            'visibility' => 'public',
            'officer_ids' => [$officer2->id],
        ])->assertOk();

        $this->assertDatabaseHas('squads', [
            'id' => $squad->id,
            'name' => 'Updated Name',
            'visibility' => 'public',
        ]);

        // Owner deletes -> 200
        $this->actingAs($owner)->deleteJson('/api/v1/squads/' . $squad->id)
            ->assertOk();

        $this->assertDatabaseMissing('squads', ['id' => $squad->id]);
    }

    public function test_slug_show_visibility_rules(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $officer = $this->createOfficer('Officer One', '101');

        $publicSquad = Squad::create([
            'user_id' => $owner->id,
            'name' => 'Public Unit',
            'slug' => 'public-unit-111111',
            'visibility' => 'public',
        ]);
        $publicSquad->officers()->attach([$officer->id => ['position' => 0]]);

        $privateSquad = Squad::create([
            'user_id' => $owner->id,
            'name' => 'Private Unit',
            'slug' => 'private-unit-222222',
            'visibility' => 'private',
        ]);
        $privateSquad->officers()->attach([$officer->id => ['position' => 0]]);

        $hiddenSquad = Squad::create([
            'user_id' => $owner->id,
            'name' => 'Hidden Unit',
            'slug' => 'hidden-unit-333333',
            'visibility' => 'public',
            'is_hidden' => true,
        ]);
        $hiddenSquad->officers()->attach([$officer->id => ['position' => 0]]);

        // Public squad accessible by anyone
        auth()->logout();
        $this->getJson('/api/v1/squads/' . $publicSquad->slug)->assertOk();

        // Private squad: 404 for unauth or other user, 200 for owner
        auth()->logout();
        $this->getJson('/api/v1/squads/' . $privateSquad->slug)->assertNotFound();
        $this->actingAs($otherUser)->getJson('/api/v1/squads/' . $privateSquad->slug)->assertNotFound();
        $this->actingAs($owner)->getJson('/api/v1/squads/' . $privateSquad->slug)->assertOk();

        // Hidden squad: 404 for unauth or other user, 200 for owner
        auth()->logout();
        $this->getJson('/api/v1/squads/' . $hiddenSquad->slug)->assertNotFound();
        $this->actingAs($otherUser)->getJson('/api/v1/squads/' . $hiddenSquad->slug)->assertNotFound();
        $this->actingAs($owner)->getJson('/api/v1/squads/' . $hiddenSquad->slug)->assertOk();
    }

    public function test_fork_creates_private_copy_and_increments_forks_count(): void
    {
        $owner = User::factory()->create();
        $forker = User::factory()->create();
        $officer1 = $this->createOfficer('Officer One', '101');
        $officer2 = $this->createOfficer('Officer Two', '102');

        $squad = Squad::create([
            'user_id' => $owner->id,
            'name' => 'SWAT Bravo',
            'slug' => 'swat-bravo-123456',
            'description' => 'Original description',
            'visibility' => 'public',
            'forks_count' => 0,
        ]);
        $squad->officers()->attach([
            $officer1->id => ['position' => 0],
            $officer2->id => ['position' => 1],
        ]);

        $res = $this->actingAs($forker)->postJson('/api/v1/squads/' . $squad->id . '/fork');
        $res->assertCreated();
        $res->assertJsonPath('data.name', 'SWAT Bravo (fork)');
        $res->assertJsonPath('data.visibility', 'private');
        $res->assertJsonPath('data.forked_from_id', $squad->id);

        // Original squad forks_count is incremented
        $squad->refresh();
        $this->assertSame(1, $squad->forks_count);
        // Original officers remain untouched
        $this->assertCount(2, $squad->officers);

        // Forked squad in DB
        $this->assertDatabaseHas('squads', [
            'user_id' => $forker->id,
            'name' => 'SWAT Bravo (fork)',
            'visibility' => 'private',
            'forked_from_id' => $squad->id,
        ]);

        // Hidden squad cannot be forked
        $squad->update(['is_hidden' => true]);
        $this->actingAs($forker)->postJson('/api/v1/squads/' . $squad->id . '/fork')
            ->assertForbidden();

        // Private squad cannot be forked by other users
        $privateSquad = Squad::create([
            'user_id' => $owner->id,
            'name' => 'Secret Squad',
            'slug' => 'secret-squad-123456',
            'visibility' => 'private',
        ]);
        $privateSquad->officers()->attach([$officer1->id => ['position' => 0]]);

        $this->actingAs($forker)->postJson('/api/v1/squads/' . $privateSquad->id . '/fork')
            ->assertForbidden();
    }

    public function test_public_endpoint_lists_public_squads_sorted_by_live_and_forks(): void
    {
        $user = User::factory()->create();

        $officerLive1 = $this->createOfficer('Live Officer 1', 'L1');
        ActiveStream::create([
            'officer_id' => $officerLive1->id,
            'channel_id' => $officerLive1->channel_id,
            'video_id' => 'vid1',
            'title' => 'Patrol Live 1',
            'status' => 'LIVE',
            'started_at' => now(),
        ]);

        $officerLive2 = $this->createOfficer('Live Officer 2', 'L2');
        ActiveStream::create([
            'officer_id' => $officerLive2->id,
            'channel_id' => $officerLive2->channel_id,
            'video_id' => 'vid2',
            'title' => 'Patrol Live 2',
            'status' => 'LIVE',
            'started_at' => now(),
        ]);

        $officerOffline = $this->createOfficer('Off Officer', 'OFF');

        // Squad A: 1 live, 0 forks
        $squadA = Squad::create(['user_id' => $user->id, 'name' => 'Squad A', 'slug' => 'squad-a-111111', 'visibility' => 'public', 'forks_count' => 0]);
        $squadA->officers()->attach([$officerLive1->id => ['position' => 0]]);

        // Squad B: 2 live, 0 forks
        $squadB = Squad::create(['user_id' => $user->id, 'name' => 'Squad B', 'slug' => 'squad-b-222222', 'visibility' => 'public', 'forks_count' => 0]);
        $squadB->officers()->attach([
            $officerLive1->id => ['position' => 0],
            $officerLive2->id => ['position' => 1],
        ]);

        // Squad C: 0 live, 10 forks
        $squadC = Squad::create(['user_id' => $user->id, 'name' => 'Squad C', 'slug' => 'squad-c-333333', 'visibility' => 'public', 'forks_count' => 10]);
        $squadC->officers()->attach([$officerOffline->id => ['position' => 0]]);

        // Hidden squad: should not appear
        $hidden = Squad::create(['user_id' => $user->id, 'name' => 'Hidden Squad', 'slug' => 'hidden-444444', 'visibility' => 'public', 'is_hidden' => true]);
        $hidden->officers()->attach([$officerLive1->id => ['position' => 0]]);

        // Private squad: should not appear
        $private = Squad::create(['user_id' => $user->id, 'name' => 'Private Squad', 'slug' => 'private-555555', 'visibility' => 'private']);
        $private->officers()->attach([$officerLive1->id => ['position' => 0]]);

        $res = $this->getJson('/api/v1/squads/public');
        $res->assertOk();

        $data = $res->json('data');
        $this->assertCount(3, $data);

        // Order: Squad B (2 live) > Squad A (1 live) > Squad C (0 live, 10 forks)
        $this->assertSame('Squad B', $data[0]['name']);
        $this->assertSame(2, $data[0]['live_count']);

        $this->assertSame('Squad A', $data[1]['name']);
        $this->assertSame(1, $data[1]['live_count']);

        $this->assertSame('Squad C', $data[2]['name']);
        $this->assertSame(0, $data[2]['live_count']);
        $this->assertSame(10, $data[2]['forks_count']);
    }

    public function test_admin_can_toggle_hide_with_audit_log_and_non_admin_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'viewer']);
        $officer = $this->createOfficer('Officer One', '101');

        $squad = Squad::create([
            'user_id' => $user->id,
            'name' => 'Target Squad',
            'slug' => 'target-squad-123456',
            'visibility' => 'public',
            'is_hidden' => false,
        ]);
        $squad->officers()->attach([$officer->id => ['position' => 0]]);

        // Non-admin forbidden
        $this->actingAs($user)->patchJson('/api/v1/admin/squads/' . $squad->id . '/hide')
            ->assertForbidden();

        // Admin can toggle hide
        $res = $this->actingAs($admin)->patchJson('/api/v1/admin/squads/' . $squad->id . '/hide', [
            'reason' => 'Spam content',
        ]);
        $res->assertOk();
        $res->assertJsonPath('is_hidden', true);

        $squad->refresh();
        $this->assertTrue($squad->is_hidden);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'squad.hide',
            'subject_type' => 'Squad',
            'subject_id' => $squad->id,
        ]);

        // Toggle back
        $this->actingAs($admin)->patchJson('/api/v1/admin/squads/' . $squad->id . '/hide')->assertOk();
        $squad->refresh();
        $this->assertFalse($squad->is_hidden);
    }

    public function test_public_list_is_owner_is_per_viewer_not_cached(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $officer = $this->createOfficer("Cache Viewer", "1-ADAM-90");

        $this->actingAs($owner)->postJson("/api/v1/squads", [
            "name" => "Squad Cache",
            "visibility" => "public",
            "officer_ids" => [$officer->id],
        ])->assertCreated();

        // Owner warms the shared cache first.
        $this->actingAs($owner)->getJson("/api/v1/squads/public")
            ->assertOk()->assertJsonPath("data.0.is_owner", true);

        $this->actingAs($other)->getJson("/api/v1/squads/public")
            ->assertOk()->assertJsonPath("data.0.is_owner", false)
            ->assertJsonMissingPath("data.0._owner_id");
    }

    public function test_admin_can_list_squads_with_search_and_pagination_and_non_admin_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $viewer = User::factory()->create(['role' => 'viewer']);
        $officer = $this->createOfficer('Officer Squad', 'SQ-1');

        $publicSquad1 = Squad::create([
            'user_id' => $admin->id,
            'name' => 'SWAT Public',
            'slug' => 'swat-public-1',
            'visibility' => 'public',
        ]);
        $publicSquad1->officers()->attach([$officer->id => ['position' => 0]]);

        $publicSquad2 = Squad::create([
            'user_id' => $admin->id,
            'name' => 'Traffic Control',
            'slug' => 'traffic-control-2',
            'visibility' => 'public',
            'is_hidden' => true,
        ]);
        $publicSquad2->officers()->attach([$officer->id => ['position' => 0]]);

        $privateSquad = Squad::create([
            'user_id' => $admin->id,
            'name' => 'Private Secret',
            'slug' => 'private-secret-3',
            'visibility' => 'private',
        ]);
        $privateSquad->officers()->attach([$officer->id => ['position' => 0]]);

        // Non-admin forbidden
        $this->actingAs($viewer)->getJson('/api/v1/admin/squads')
            ->assertForbidden();

        // Admin success: lists public squads including hidden, but excludes private
        $res = $this->actingAs($admin)->getJson('/api/v1/admin/squads');
        $res->assertOk();
        $res->assertJsonPath('status', 'success');
        $this->assertCount(2, $res->json('data'));

        // Search filtering
        $searchRes = $this->actingAs($admin)->getJson('/api/v1/admin/squads?search=traffic');
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertSame('Traffic Control', $searchRes->json('data.0.name'));
    }

    public function test_available_officers_returns_active_roster_with_online_flags(): void
    {
        $officer1 = $this->createOfficer('Officer Alpha', '101');
        $officer2 = $this->createOfficer('Officer Beta', '102');

        // Create active live stream for officer1
        \App\Models\ActiveStream::create([
            'officer_id' => $officer1->id,
            'video_id' => 'abc123live',
            'channel_id' => $officer1->channel_id,
            'title' => 'Patrol Stream',
            'status' => 'LIVE',
            'last_synced_at' => now(),
        ]);

        $res = $this->getJson('/api/v1/squads/officers');
        $res->assertOk();
        $res->assertJsonPath('status', 'success');

        $data = collect($res->json('data'));
        $found1 = $data->firstWhere('id', $officer1->id);
        $found2 = $data->firstWhere('id', $officer2->id);

        $this->assertNotNull($found1);
        $this->assertTrue($found1['is_online']);

        $this->assertNotNull($found2);
        $this->assertFalse($found2['is_online']);
    }
}
