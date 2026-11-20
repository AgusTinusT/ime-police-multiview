<?php

namespace Tests\Feature\Officer;

use App\Http\Controllers\PoliceCommandController;
use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewOfficersHighlightTest extends TestCase
{
    use RefreshDatabase;

    private function makeOfficer(string $name, $createdAt, bool $active = true): Officer
    {
        $officer = Officer::create([
            'officer_name' => $name,
            'streamer_name' => $name,
            'handle' => '@' . strtolower(str_replace(' ', '', $name)),
            'channel_id' => 'UC_' . strtoupper(str_replace(' ', '', $name)),
            'department' => 'LSPD',
            'rank' => 'Cadet',
            'callsign' => '1-N-' . substr(md5($name), 0, 4),
            'is_active' => $active,
        ]);
        $officer->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $officer;
    }

    public function test_only_officers_added_within_14_days_are_listed_newest_first(): void
    {
        $this->makeOfficer('Fresh', now()->subDay());
        $this->makeOfficer('Almost Expired', now()->subDays(13));
        $this->makeOfficer('Expired', now()->subDays(15));
        $this->makeOfficer('Inactive New', now()->subDay(), false);

        $list = app(PoliceCommandController::class)->getNewOfficers();

        $this->assertSame(['Fresh', 'Almost Expired'], array_column($list, 'officer_name'));
        $this->assertSame(1, $list[0]['days_since_added']);
        $this->assertSame(13, $list[0]['days_remaining']);
    }

    public function test_dashboard_page_receives_new_officers_prop(): void
    {
        $this->makeOfficer('Fresh', now()->subDays(2));

        $this->get('/')->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('newOfficers', 1)
            ->where('newOfficers.0.officer_name', 'Fresh'));
    }
}
