<?php

namespace Tests\Feature\Officer;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

/**
 * Non-personnel officers (is_listed = false) keep a working bio-link but are
 * left out of every public roster.
 */
class UnlistedOfficerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBioLinkFixtures;

    private Officer $listed;

    private Officer $unlisted;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->listed = $this->createOfficer(['officer_name' => 'Listed Officer']);
        $this->unlisted = $this->createOfficer(['officer_name' => 'Bio Only Account', 'is_listed' => false]);

        OfficerBioProfile::create(['officer_id' => $this->unlisted->id, 'slug' => 'bio-only']);
    }

    public function test_new_officers_default_to_listed(): void
    {
        $this->assertTrue($this->listed->fresh()->is_listed);
        $this->assertSame([$this->listed->id], Officer::roster()->pluck('id')->all());
    }

    public function test_unlisted_officer_bio_link_still_works(): void
    {
        $this->get('/@bio-only')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/BioLink'));
    }

    public function test_unlisted_officer_is_hidden_from_public_rosters(): void
    {
        $names = fn (array $rows) => array_column($rows, 'officer_name');

        $this->get('/officers')->assertInertia(fn (Assert $page) => $page
            ->component('OfficerDirectory')
            ->where('initialOfficers', fn ($rows) => $names($rows->toArray()) === ['Listed Officer']));

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('initialOfflineOfficers', fn ($rows) => $names($rows->toArray()) === ['Listed Officer'])
            ->where('newOfficers', fn ($rows) => $names($rows->toArray()) === ['Listed Officer']));

        $squadOfficers = $this->getJson('/api/v1/squads/officers')->assertOk()->json('data');
        $this->assertSame(['Listed Officer'], $names($squadOfficers));
    }

    public function test_unlisted_officer_live_stream_is_not_shown_as_live(): void
    {
        ActiveStream::create([
            'officer_id' => $this->unlisted->id,
            'channel_id' => $this->unlisted->channel_id,
            'video_id' => 'UNLISTED001',
            'title' => 'Not a patrol',
            'status' => 'LIVE',
        ]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('initialStreams', 0));
    }

    public function test_admin_can_toggle_listed_and_it_ends_live_streams(): void
    {
        $admin = $this->createAdminUser();
        ActiveStream::create([
            'officer_id' => $this->listed->id,
            'channel_id' => $this->listed->channel_id,
            'video_id' => 'LISTEDLIVE1',
            'title' => 'Patrol',
            'status' => 'LIVE',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/officers/{$this->listed->id}/toggle-listed")
            ->assertOk()
            ->assertJson(['status' => 'success', 'is_listed' => false]);

        $this->assertFalse($this->listed->fresh()->is_listed);
        $this->assertSame(0, ActiveStream::where('officer_id', $this->listed->id)->count());

        $this->actingAs($admin)
            ->patchJson("/api/v1/officers/{$this->listed->id}/toggle-listed", ['is_listed' => true])
            ->assertOk()
            ->assertJson(['is_listed' => true]);
    }

    public function test_non_admin_cannot_toggle_listed(): void
    {
        $this->actingAs($this->createVerifiedUser())
            ->patchJson("/api/v1/officers/{$this->listed->id}/toggle-listed")
            ->assertForbidden();
    }
}
