<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupportPageTest extends TestCase
{
    public function test_support_page_renders_config_driven_sections(): void
    {
        config([
            'support.monthly_costs' => [
                ['label' => 'VPS', 'amount' => 150000],
                ['label' => 'Domain', 'amount' => 20000],
            ],
            'support.goals' => [
                ['key' => 'vps', 'title' => 'Upgrade VPS', 'target' => 1000000, 'collected' => 250000, 'status' => 'active'],
                ['key' => 'event', 'title' => 'Event Top 3', 'target' => null, 'collected' => 0, 'status' => 'weird'],
            ],
            'support.perks' => [['title' => 'Nama di dinding']],
            'support.supporters' => [['name' => 'Alpha']],
        ]);

        $this->get('/dukung')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Support')
            ->where('monthlyTotal', 170000)
            ->where('goals.0.percent', 25)
            ->where('goals.1.percent', null)
            ->where('goals.1.status', 'planned')
            ->where('perks.0.icon', 'heart')
            ->where('supporters.0.name', 'Alpha'));
    }

    public function test_total_is_hidden_while_any_cost_is_unknown(): void
    {
        config(['support.monthly_costs' => [
            ['label' => 'VPS', 'amount' => 150000],
            ['label' => 'Domain', 'amount' => null],
        ]]);

        $this->get('/dukung')->assertInertia(fn (Assert $page) => $page
            ->where('monthlyTotal', null)
            ->where('monthlyCosts.1.amount', null));
    }

    public function test_sponsored_cost_counts_as_zero_and_keeps_labels(): void
    {
        config([
            'support.monthly_costs' => [
                ['label' => 'VPS', 'amount' => 50000, 'covered_by' => 'Ditanggung pengembang'],
                ['label' => 'Domain', 'sponsor' => 'MotionLife'],
            ],
            'support.costs_note' => 'Donasi untuk event.',
        ]);

        $this->get('/dukung')->assertInertia(fn (Assert $page) => $page
            ->where('monthlyTotal', 50000)
            ->where('monthlyCosts.0.covered_by', 'Ditanggung pengembang')
            ->where('monthlyCosts.1.amount', 0)
            ->where('monthlyCosts.1.sponsor', 'MotionLife')
            ->where('costsNote', 'Donasi untuk event.'));
    }

    public function test_old_support_urls_redirect(): void
    {
        $this->get('/donate')->assertRedirect('/dukung');
        $this->get('/support')->assertRedirect('/dukung');
    }

    public function test_bagibagi_leaderboard_populates_supporters_and_active_goal(): void
    {
        Http::fake([
            'https://bagibagi.co/api/partnerintegration/top-donator/streamkey*' => Http::response([
                'data' => [
                    ['userName' => 'Seseorang', 'amount' => 80000, 'isVerified' => false, 'isAnonymous' => true],
                    ['userName' => 'SultanLSPD', 'amount' => 300000, 'isVerified' => true, 'isAnonymous' => false],
                    ['userName' => '   ', 'amount' => 20000, 'isVerified' => false, 'isAnonymous' => true],
                ],
                'success' => true,
                'message' => 'Success',
            ], 200),
        ]);

        config([
            'support.bagibagi.stream_key' => 'test_stream_key_123',
            'support.goals' => [
                ['key' => 'event-top-patrol', 'title' => 'Event Top 3', 'target' => 500000, 'collected' => 50000, 'status' => 'active'],
                ['key' => 'next-goal', 'title' => 'Second Active', 'target' => 500000, 'collected' => 0, 'status' => 'active'],
            ],
            'support.supporters' => [
                ['name' => 'ManualSponsor', 'note' => 'Sponsor awal'],
            ],
        ]);

        $this->get('/dukung')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Support')
            ->where('leaderboardSynced', true)
            ->where('goals.0.collected', 450000)
            ->where('goals.0.percent', 90)
            ->where('goals.1.collected', 0)
            ->has('supporters', 4)
            ->where('supporters.0.rank', 1)
            ->where('supporters.0.name', 'SultanLSPD')
            ->where('supporters.0.amount', 300000)
            ->where('supporters.0.is_verified', true)
            ->where('supporters.1.rank', 2)
            ->where('supporters.1.name', 'Seseorang')
            ->where('supporters.2.name', 'Seseorang')
            ->where('supporters.3.rank', null)
            ->where('supporters.3.name', 'ManualSponsor')
            ->where('supporters.3.note', 'Sponsor awal'));

        Http::assertSent(fn ($request) => $request->url() === 'https://bagibagi.co/api/partnerintegration/top-donator/streamkey?streamkey=test_stream_key_123');
    }

    public function test_bagibagi_accepts_full_copied_endpoint_url_in_stream_key_env(): void
    {
        $fullUrl = 'https://bagibagi.co/api/partnerintegration/top-donator/streamkey?streamkey=copied_full_url_key';

        Http::fake([
            $fullUrl => Http::response([
                'data' => [
                    ['userName' => 'KomandanBudi', 'amount' => 150000, 'isVerified' => true, 'isAnonymous' => false],
                ],
                'success' => true,
                'message' => 'Success',
            ], 200),
        ]);

        config(['support.bagibagi.stream_key' => $fullUrl]);

        $this->get('/dukung')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('leaderboardSynced', true)
            ->where('supporters.0.name', 'KomandanBudi')
            ->where('supporters.0.amount', 150000));

        Http::assertSentCount(1);
    }

    public function test_bagibagi_validation_failure_or_error_gracefully_falls_back_to_config(): void
    {
        Http::fake([
            'https://bagibagi.co/api/partnerintegration/top-donator/streamkey*' => Http::response([
                'data' => null,
                'success' => false,
                'message' => 'Validation Failed',
            ], 422),
        ]);

        config([
            'support.bagibagi.stream_key' => 'invalid_key',
            'support.updated_at' => '2026-11-21',
            'support.goals' => [
                ['key' => 'event-top-patrol', 'title' => 'Event Top 3', 'target' => 500000, 'collected' => 100000, 'status' => 'active'],
            ],
            'support.supporters' => [
                ['name' => 'FallbackDonor'],
            ],
        ]);

        $this->get('/dukung')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('leaderboardSynced', false)
            ->where('updatedAt', '2026-11-21')
            ->where('goals.0.collected', 100000)
            ->has('supporters', 1)
            ->where('supporters.0.name', 'FallbackDonor'));
    }

    public function test_bagibagi_serves_stale_cache_when_upstream_temporarily_fails(): void
    {
        Http::fakeSequence()
            ->push([
                'data' => [
                    ['userName' => 'CachedDonor', 'amount' => 200000, 'isVerified' => true, 'isAnonymous' => false],
                ],
                'success' => true,
                'message' => 'Success',
            ], 200)
            ->pushStatus(503);

        config([
            'support.bagibagi.stream_key' => 'stale_test_key',
            'support.bagibagi.cache_ttl' => 60,
        ]);

        $this->get('/dukung')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('leaderboardSynced', true)
            ->where('supporters.0.name', 'CachedDonor'));

        // Simulate fresh cache expiry while keeping 24h stale cache intact.
        $url = 'https://bagibagi.co/api/partnerintegration/top-donator/streamkey?streamkey=stale_test_key';
        $hash = substr(sha1($url), 0, 16);
        Cache::forget("bagibagi:leaderboard:fresh:{$hash}");

        $this->get('/dukung')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('leaderboardSynced', true)
            ->where('supporters.0.name', 'CachedDonor')
            ->where('supporters.0.amount', 200000));
    }

    public function test_support_highlight_api_returns_active_goal_and_top_three_supporters(): void
    {
        Http::fake([
            'https://bagibagi.co/api/partnerintegration/top-donator/streamkey*' => Http::response([
                'data' => [
                    ['userName' => 'DonorSatu', 'amount' => 150000, 'isVerified' => true, 'isAnonymous' => false],
                    ['userName' => 'DonorDua', 'amount' => 75000, 'isVerified' => false, 'isAnonymous' => false],
                    ['userName' => 'ucupmeletup', 'amount' => 25000, 'isVerified' => false, 'isAnonymous' => true],
                    ['userName' => 'DonorEmpat', 'amount' => 10000, 'isVerified' => false, 'isAnonymous' => true],
                ],
                'success' => true,
                'message' => 'Success',
            ], 200),
        ]);

        config([
            'support.bagibagi.stream_key' => 'highlight_key_123',
            'support.goals' => [
                ['key' => 'event-top-patrol', 'title' => 'Hadiah Top 1-3 Patroli', 'target' => 500000, 'collected' => 0, 'status' => 'active'],
            ],
            'support.supporters' => [
                ['name' => 'DonorLima'],
            ],
        ]);

        $this->getJson('/api/v1/support/highlight')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.visible', true)
            ->assertJsonPath('data.leaderboard_synced', true)
            ->assertJsonPath('data.goal.key', 'event-top-patrol')
            ->assertJsonPath('data.goal.collected', 260000)
            ->assertJsonPath('data.goal.percent', 52)
            ->assertJsonCount(3, 'data.top_supporters')
            ->assertJsonPath('data.top_supporters.0.name', 'DonorSatu')
            ->assertJsonPath('data.top_supporters.2.name', 'ucupmeletup')
            ->assertJsonPath('data.total_supporters', 5)
            ->assertJsonPath('data.extra_supporters_count', 2)
            ->assertJsonPath('data.support_url', '/dukung');
    }

    public function test_support_highlight_api_marks_invisible_when_no_goals_or_supporters_exist(): void
    {
        config([
            'support.bagibagi.stream_key' => null,
            'support.goals' => [],
            'support.supporters' => [],
        ]);

        $this->getJson('/api/v1/support/highlight')
            ->assertOk()
            ->assertJsonPath('data.visible', false)
            ->assertJsonPath('data.goal', null)
            ->assertJsonCount(0, 'data.top_supporters');
    }
}
