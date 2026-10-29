<?php

namespace Tests\Feature;

use App\Http\Controllers\PoliceCommandController;
use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_uses_current_month_when_current_month_has_hours(): void
    {
        Officer::create([
            'channel_id' => 'UC_OFFICER_CURRENT',
            'handle' => 'officer_cur',
            'streamer_name' => 'Current Streamer',
            'officer_name' => 'Ofc. Current',
            'callsign' => '1-ADAM-01',
            'department' => 'LSPD',
            'monthly_duty_minutes' => 120,
            'last_duty_at' => now(),
            'is_active' => true,
        ]);

        $controller = new PoliceCommandController();
        $leaderboard = $controller->getMonthlyLeaderboard();

        $this->assertFalse($leaderboard['is_previous_period']);
        $this->assertEquals(strtoupper(now()->locale('id')->translatedFormat('F Y')), $leaderboard['period_text']);
        $this->assertCount(1, $leaderboard['all_top']);
        $this->assertEquals('Ofc. Current', $leaderboard['all_top'][0]['officer_name']);
        $this->assertEquals(2.0, $leaderboard['all_top'][0]['total_hours_num']);
    }

    public function test_leaderboard_falls_back_to_previous_month_when_current_month_is_empty(): void
    {
        // Officer active last month with accumulated duty minutes
        Officer::create([
            'channel_id' => 'UC_OFFICER_PREV',
            'handle' => 'officer_prev',
            'streamer_name' => 'Previous Streamer',
            'officer_name' => 'Ofc. Previous',
            'callsign' => '1-ADAM-02',
            'department' => 'BCSO',
            'monthly_duty_minutes' => 300,
            'last_duty_at' => now()->subMonth(),
            'is_active' => true,
        ]);

        // Officer with 0 minutes this month
        Officer::create([
            'channel_id' => 'UC_OFFICER_ZERO',
            'handle' => 'officer_zero',
            'streamer_name' => 'Zero Streamer',
            'officer_name' => 'Ofc. Zero',
            'callsign' => '1-ADAM-03',
            'department' => 'LSPD',
            'monthly_duty_minutes' => 0,
            'last_duty_at' => now(),
            'is_active' => true,
        ]);

        $controller = new PoliceCommandController();
        $leaderboard = $controller->getMonthlyLeaderboard();

        $previousMonth = now()->subMonth();
        $expectedPeriod = strtoupper($previousMonth->locale('id')->translatedFormat('F Y'));

        $this->assertTrue($leaderboard['is_previous_period']);
        $this->assertEquals($expectedPeriod, $leaderboard['period_text']);
        $this->assertCount(2, $leaderboard['all_top']);
        $this->assertEquals('Ofc. Previous', $leaderboard['all_top'][0]['officer_name']);
        $this->assertEquals(5.0, $leaderboard['all_top'][0]['total_hours_num']);
    }

    public function test_dashboard_inertia_receives_previous_period_flag_on_fallback(): void
    {
        Officer::create([
            'channel_id' => 'UC_OFFICER_PREV_2',
            'handle' => 'officer_prev_2',
            'streamer_name' => 'Previous Streamer 2',
            'officer_name' => 'Ofc. Previous Two',
            'callsign' => '1-ADAM-04',
            'department' => 'SASP',
            'monthly_duty_minutes' => 180,
            'last_duty_at' => now()->subMonth(),
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Dashboard')
                ->where('monthlyLeaderboard.is_previous_period', true)
        );
    }
}
