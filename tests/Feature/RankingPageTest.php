<?php

namespace Tests\Feature;

use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RankingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_ranking_page_and_receives_five_ranking_keys(): void
    {
        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ranking')
            ->has('ranking.duty_month')
            ->has('ranking.duty_last_month')
            ->has('ranking.subscribers')
            ->has('ranking.road_to_1k')
            ->has('ranking.recently_on_duty')
        );
    }

    public function test_inactive_officers_are_omitted_from_all_rankings(): void
    {
        Officer::create([
            'channel_id' => 'UC_ACTIVE',
            'handle' => 'ofc_active',
            'streamer_name' => 'Active Streamer',
            'officer_name' => 'Active Officer',
            'callsign' => '1-ADAM-01',
            'department' => 'LSPD',
            'monthly_duty_minutes' => 120,
            'previous_monthly_duty_minutes' => 60,
            'subscriber_count' => 500,
            'last_duty_at' => now(),
            'is_active' => true,
        ]);

        Officer::create([
            'channel_id' => 'UC_INACTIVE',
            'handle' => 'ofc_inactive',
            'streamer_name' => 'Inactive Streamer',
            'officer_name' => 'Inactive Officer',
            'callsign' => '1-ADAM-02',
            'department' => 'BCSO',
            'monthly_duty_minutes' => 300,
            'previous_monthly_duty_minutes' => 180,
            'subscriber_count' => 200,
            'last_duty_at' => now(),
            'is_active' => false,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertInertia(function (Assert $page) {
            $ranking = $page->toArray()['props']['ranking'];

            foreach (['duty_month', 'duty_last_month', 'subscribers', 'road_to_1k', 'recently_on_duty'] as $key) {
                $names = collect($ranking[$key])->pluck('officer_name')->all();
                $this->assertNotContains('Inactive Officer', $names, "Failed for ranking key: {$key}");
            }
        });
    }

    public function test_duty_month_only_contains_officers_with_duty_minutes_greater_than_zero_and_is_sorted(): void
    {
        Officer::create([
            'channel_id' => 'UC_ZERO',
            'handle' => 'ofc_zero',
            'streamer_name' => 'Zero Streamer',
            'officer_name' => 'Zero Officer',
            'callsign' => '1-ADAM-03',
            'department' => 'LSPD',
            'monthly_duty_minutes' => 0,
            'last_duty_at' => now(),
            'is_active' => true,
        ]);

        Officer::create([
            'channel_id' => 'UC_FIRST',
            'handle' => 'ofc_first',
            'streamer_name' => 'First Streamer',
            'officer_name' => 'First Officer',
            'callsign' => '1-ADAM-04',
            'department' => 'SASP',
            'monthly_duty_minutes' => 240,
            'last_duty_at' => now(),
            'is_active' => true,
        ]);

        Officer::create([
            'channel_id' => 'UC_SECOND',
            'handle' => 'ofc_second',
            'streamer_name' => 'Second Streamer',
            'officer_name' => 'Second Officer',
            'callsign' => '1-ADAM-05',
            'department' => 'BCSO',
            'monthly_duty_minutes' => 120,
            'last_duty_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertInertia(function (Assert $page) {
            $dutyMonth = $page->toArray()['props']['ranking']['duty_month'];

            $this->assertCount(2, $dutyMonth);
            $this->assertEquals('First Officer', $dutyMonth[0]['officer_name']);
            $this->assertEquals(240, $dutyMonth[0]['value']);
            $this->assertEquals('Second Officer', $dutyMonth[1]['officer_name']);
            $this->assertEquals(120, $dutyMonth[1]['value']);

            $names = collect($dutyMonth)->pluck('officer_name')->all();
            $this->assertNotContains('Zero Officer', $names);
        });
    }

    public function test_road_to_1k_does_not_contain_officers_with_1000_or_more_subscribers(): void
    {
        Officer::create([
            'channel_id' => 'UC_UNDER_1',
            'handle' => 'ofc_700',
            'streamer_name' => '700 Streamer',
            'officer_name' => 'Ofc. Seven Hundred',
            'callsign' => '1-ADAM-06',
            'department' => 'LSPD',
            'subscriber_count' => 700,
            'is_active' => true,
        ]);

        Officer::create([
            'channel_id' => 'UC_UNDER_2',
            'handle' => 'ofc_950',
            'streamer_name' => '950 Streamer',
            'officer_name' => 'Ofc. Nine Fifty',
            'callsign' => '1-ADAM-07',
            'department' => 'BCSO',
            'subscriber_count' => 950,
            'is_active' => true,
        ]);

        Officer::create([
            'channel_id' => 'UC_EXACT_1K',
            'handle' => 'ofc_1000',
            'streamer_name' => '1000 Streamer',
            'officer_name' => 'Ofc. One Thousand',
            'callsign' => '1-ADAM-08',
            'department' => 'SASP',
            'subscriber_count' => 1000,
            'is_active' => true,
        ]);

        Officer::create([
            'channel_id' => 'UC_OVER_1K',
            'handle' => 'ofc_5000',
            'streamer_name' => '5000 Streamer',
            'officer_name' => 'Ofc. Five Thousand',
            'callsign' => '1-ADAM-09',
            'department' => 'LSPD',
            'subscriber_count' => 5000,
            'is_active' => true,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertInertia(function (Assert $page) {
            $roadTo1k = $page->toArray()['props']['ranking']['road_to_1k'];

            $this->assertCount(2, $roadTo1k);
            // Closest to 1K first (descending)
            $this->assertEquals('Ofc. Nine Fifty', $roadTo1k[0]['officer_name']);
            $this->assertEquals(950, $roadTo1k[0]['value']);
            $this->assertEquals('Ofc. Seven Hundred', $roadTo1k[1]['officer_name']);
            $this->assertEquals(700, $roadTo1k[1]['value']);

            $names = collect($roadTo1k)->pluck('officer_name')->all();
            $this->assertNotContains('Ofc. One Thousand', $names);
            $this->assertNotContains('Ofc. Five Thousand', $names);
        });
    }
}
