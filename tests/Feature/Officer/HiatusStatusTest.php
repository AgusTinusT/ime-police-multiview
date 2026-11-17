<?php

namespace Tests\Feature\Officer;

use App\Models\ActiveStream;
use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HiatusStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Officer tanpa last_duty_at tapi created_at 1 hari lalu -> is_hiatus = false.
     */
    public function test_new_officer_without_duty_is_not_hiatus(): void
    {
        $officer = Officer::create([
            'officer_name' => 'Ofc. Rookie',
            'streamer_name' => 'Rookie',
            'handle' => '@rookie',
            'channel_id' => 'UC_ROOKIE',
            'department' => 'LSPD',
            'rank' => 'Cadet',
            'callsign' => '1-R-01',
            'is_active' => true,
            'last_duty_at' => null,
            'created_at' => now()->subDay(),
        ]);

        $this->assertFalse($officer->is_hiatus);
    }

    /**
     * 2. Officer last_duty_at 20 hari lalu & tidak live -> is_hiatus = true.
     */
    public function test_officer_not_on_duty_for_20_days_is_hiatus(): void
    {
        $officer = Officer::create([
            'officer_name' => 'Ofc. Inactive',
            'streamer_name' => 'Inactive',
            'handle' => '@inactive',
            'channel_id' => 'UC_INACTIVE',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-A-10',
            'is_active' => true,
            'last_duty_at' => now()->subDays(20),
        ]);

        $this->assertTrue($officer->is_hiatus);
    }

    /**
     * 3. Officer last_duty_at 20 hari lalu tapi sedang live -> is_hiatus = false.
     */
    public function test_officer_not_on_duty_for_20_days_but_live_is_not_hiatus(): void
    {
        $officer = Officer::create([
            'officer_name' => 'Ofc. Returning',
            'streamer_name' => 'Returning',
            'handle' => '@returning',
            'channel_id' => 'UC_RETURNING',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-A-11',
            'is_active' => true,
            'last_duty_at' => now()->subDays(20),
        ]);

        ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => 'VID_RETURNING_01',
            'title' => 'SASP 10-8 Live Shift',
            'status' => 'LIVE',
        ]);

        $officer->load('activeStream');
        $this->assertFalse($officer->is_hiatus);
    }

    /**
     * 4. Officer last_duty_at 3 hari lalu -> is_hiatus = false.
     */
    public function test_officer_recent_duty_is_not_hiatus(): void
    {
        $officer = Officer::create([
            'officer_name' => 'Ofc. Active',
            'streamer_name' => 'Active',
            'handle' => '@active',
            'channel_id' => 'UC_ACTIVE',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-A-12',
            'is_active' => true,
            'last_duty_at' => now()->subDays(3),
        ]);

        $this->assertFalse($officer->is_hiatus);
    }

    /**
     * 5. GET / (Dashboard) -> monthlyLeaderboard.all_top tidak memuat officer hiatus; GET /ranking -> tetap memuat, dengan is_hiatus=true.
     */
    public function test_dashboard_filters_out_hiatus_and_ranking_keeps_hiatus(): void
    {
        // Ranking bulan ini hanya menghitung menit dinas bila last_duty_at di bulan berjalan.
        // Kunci waktu di akhir bulan supaya "25 hari lalu" tetap di bulan yang sama, apa pun tanggal test dijalankan.
        $this->travelTo(now()->endOfMonth()->setTime(12, 0));

        $activeOfficer = Officer::create([
            'officer_name' => 'Ofc. Active Guy',
            'streamer_name' => 'Active Guy',
            'handle' => '@activeguy',
            'channel_id' => 'UC_ACTIVE_GUY',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-A-01',
            'is_active' => true,
            'monthly_duty_minutes' => 600,
            'last_duty_at' => now()->subDays(2),
        ]);

        $hiatusOfficer = Officer::create([
            'officer_name' => 'Ofc. Hiatus Veteran',
            'streamer_name' => 'Hiatus Veteran',
            'handle' => '@hiatusvet',
            'channel_id' => 'UC_HIATUS_VET',
            'department' => 'LSPD',
            'rank' => 'Sergeant',
            'callsign' => '1-A-02',
            'is_active' => true,
            'monthly_duty_minutes' => 1200,
            'last_duty_at' => now()->subDays(25),
        ]);

        // Dashboard check
        $dashboardResponse = $this->get('/');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('monthlyLeaderboard.all_top', function ($allTop) use ($activeOfficer, $hiatusOfficer) {
                $ids = collect($allTop)->pluck('id')->all();
                return in_array($activeOfficer->id, $ids) && !in_array($hiatusOfficer->id, $ids);
            })
        );

        // Ranking check
        $rankingResponse = $this->get('/ranking');
        $rankingResponse->assertOk();
        $rankingResponse->assertInertia(fn (Assert $page) => $page
            ->component('Ranking')
            ->where('ranking.duty_month', function ($dutyMonth) use ($hiatusOfficer) {
                $officerItem = collect($dutyMonth)->firstWhere('id', $hiatusOfficer->id);
                return $officerItem && $officerItem['is_hiatus'] === true;
            })
        );
    }

    /**
     * 6. Ubah config(['police.hiatus_days' => 7]) -> test ulang #2 dengan last_duty_at 10 hari lalu -> true.
     */
    public function test_configurable_hiatus_days_threshold(): void
    {
        config(['police.hiatus_days' => 7]);

        $officer = Officer::create([
            'officer_name' => 'Ofc. SevenDays',
            'streamer_name' => 'SevenDays',
            'handle' => '@sevendays',
            'channel_id' => 'UC_SEVENDAYS',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-A-20',
            'is_active' => true,
            'last_duty_at' => now()->subDays(10),
        ]);

        $this->assertTrue($officer->is_hiatus);
    }
}

