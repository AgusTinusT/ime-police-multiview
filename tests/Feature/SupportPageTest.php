<?php

namespace Tests\Feature;

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

    public function test_old_support_urls_redirect(): void
    {
        $this->get('/donate')->assertRedirect('/dukung');
        $this->get('/support')->assertRedirect('/dukung');
    }
}
