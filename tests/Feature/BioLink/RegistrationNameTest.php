<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Models\User;
use App\Services\NameSkeleton;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\BioLink\Concerns\CreatesBioLinkFixtures;
use Tests\TestCase;

class RegistrationNameTest extends TestCase
{
    use RefreshDatabase, CreatesBioLinkFixtures;

    public function test_registration_with_homoglyph_impersonation_is_blocked(): void
    {
        Officer::create([
            'channel_id' => 'UC_TEST_101',
            'handle' => '@Rangeritemlucu',
            'streamer_name' => 'Arinnn',
            'officer_name' => 'Ranger Arin Luciana',
            'callsign' => '4-RANGER-02',
            'department' => 'SAPR',
            'rank' => 'Park Ranger',
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'RangeritemIucu',
            'email' => 'fake@impersonator.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('users', ['email' => 'fake@impersonator.com']);
    }

    public function test_reserved_keyword_substring_matching_blocks_impersonation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Admin SASP Official',
            'email' => 'admin_fake@sasp.gov',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('users', ['email' => 'admin_fake@sasp.gov']);
    }

    public function test_homoglyphs_cyrillic_and_zero_width_rejected(): void
    {
        Officer::create([
            'channel_id' => 'UC_CYRILLIC_TEST',
            'handle' => '@BudiSantoso',
            'streamer_name' => 'Budi Gamer',
            'officer_name' => 'Budi Santoso',
            'callsign' => '1-ADAM-99',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
        ]);

        // Cyrillic 'а' (U+0430) and 'о' (U+043E)
        $response = $this->post('/register', [
            'name' => "Budi S\u{0430}nt\u{043E}s\u{043E}",
            'email' => 'cyrillic_attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('users', ['email' => 'cyrillic_attacker@example.com']);
    }

    public function test_b05_common_names_allowed_and_authority_keywords_blocked(): void
    {
        // 1. Common Indonesian / ordinary names with substrings like 'sapr' or 'admin'
        $allowed = ['Saprudin', 'Siti Sapriyah', 'Ahmad Mintoro', 'Badminton Lover', 'Budi Santoso'];
        foreach ($allowed as $name) {
            $check = NameSkeleton::checkName($name);
            $this->assertFalse($check['blocked'], "Name '{$name}' should be allowed but was blocked.");
        }

        // 2. Full authority keyword impersonation must still be blocked
        $blocked = ['Admin SASP', 'SASP Official', '0fficial', 'AdminSASP', 'lspd', 'Sheriff Budi'];
        foreach ($blocked as $name) {
            $check = NameSkeleton::checkName($name);
            $this->assertTrue($check['blocked'], "Name '{$name}' must be blocked.");
        }
    }
}
