<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\User;
use App\Services\BioProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class OfficerLinksTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_donation_link_requires_allowed_domain_and_https(): void
    {
        $user = $this->createVerifiedUser();
        $officer = Officer::create([
            'user_id' => $user->id,
            'channel_id' => 'UC_TETEW_14',
            'handle' => '@tetewwwwwww',
            'streamer_name' => 'tetewww',
            'officer_name' => 'Ofc. Tetewww',
            'callsign' => '1-ADAM-14',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        BioProfileService::ensureBioProfile($officer);

        // 1. Rejected: non-https
        $resNonHttps = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/officer/bio-links', [
                'title' => 'Saweria Donasi',
                'link_type' => 'donation',
                'url' => 'http://saweria.co/tetew',
            ]);
        $resNonHttps->assertStatus(422);

        // 2. Rejected: unallowed phishing domain
        $resPhishing = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/officer/bio-links', [
                'title' => 'Saweria Scam',
                'link_type' => 'donation',
                'url' => 'https://saweria-fake-phishing.com/tetew',
            ]);
        $resPhishing->assertStatus(422);

        // 3. Accepted: official saweria.co
        $resValid = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/officer/bio-links', [
                'title' => 'Saweria Donasi',
                'link_type' => 'donation',
                'url' => 'https://saweria.co/tetew',
            ]);
        $resValid->assertStatus(201);
    }

    public function test_anti_idor_officer_cannot_modify_other_officer_links(): void
    {
        $userA = $this->createVerifiedUser();
        $officerA = Officer::create([
            'user_id' => $userA->id,
            'channel_id' => 'UC_A',
            'handle' => '@officerA',
            'streamer_name' => 'Officer A',
            'officer_name' => 'Ofc. Alpha',
            'callsign' => '1-A-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);
        $profileA = BioProfileService::ensureBioProfile($officerA);
        $linkA = OfficerBioLink::create([
            'officer_bio_profile_id' => $profileA->id,
            'title' => 'Alpha Discord',
            'link_type' => 'discord',
            'url' => 'https://discord.gg/alpha',
        ]);

        $userB = $this->createVerifiedUser();
        $officerB = Officer::create([
            'user_id' => $userB->id,
            'channel_id' => 'UC_B',
            'handle' => '@officerB',
            'streamer_name' => 'Officer B',
            'officer_name' => 'Ofc. Bravo',
            'callsign' => '1-B-01',
            'department' => 'BCSO',
            'rank' => 'Deputy',
            'is_active' => true,
        ]);
        BioProfileService::ensureBioProfile($officerB);

        // User B attempts to delete User A's link -> 403
        $response = $this->actingAs($userB)->deleteJson("/api/v1/officer/bio-links/{$linkA->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('officer_bio_links', ['id' => $linkA->id]);
    }

    public function test_donation_link_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'role' => 'officer',
            'email_verified_at' => now(),
        ]);
        $officer = Officer::create([
            'channel_id' => 'UC_PASSWORD_CONFIRM',
            'handle' => '@pwconfirm',
            'streamer_name' => 'PW Streamer',
            'officer_name' => 'Ofc. PW Confirm',
            'callsign' => '1-P-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        BioProfileService::ensureBioProfile($officer);

        // Attempting to add donation link without confirmation -> 423
        $this->actingAs($user)
            ->postJson('/api/v1/officer/bio-links', [
                'link_type' => 'donation',
                'title' => 'Saweria Donasi',
                'url' => 'https://saweria.co/pwconfirm',
            ])
            ->assertStatus(423)
            ->assertJson(['error' => 'password_confirmation_required']);

        // With inline password confirmation -> 201
        $this->actingAs($user)
            ->postJson('/api/v1/officer/bio-links', [
                'link_type' => 'donation',
                'title' => 'Saweria Donasi',
                'url' => 'https://saweria.co/pwconfirm',
                'password_confirmation_inline' => 'secret123',
            ])
            ->assertStatus(201);
    }

    public function test_b08_reorder_rejects_duplicate_ids(): void
    {
        $user = $this->createVerifiedUser(['role' => 'officer']);
        $officer = Officer::create([
            'channel_id' => 'UC_REORDER_DUP',
            'handle' => '@reorderdup',
            'streamer_name' => 'Reorder Streamer',
            'officer_name' => 'Ofc. Reorder Dup',
            'callsign' => '1-O-01',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        $profile = BioProfileService::ensureBioProfile($officer);

        $link = OfficerBioLink::create([
            'officer_bio_profile_id' => $profile->id,
            'title' => 'Discord Link',
            'url' => 'https://discord.gg/lspd',
            'link_type' => 'discord',
            'sort_order' => 1,
            'is_active' => true,
            'moderation_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/officer/bio-links/reorder', [
            'ordered_ids' => [$link->id, $link->id],
        ]);

        $response->assertStatus(422);
    }
}
