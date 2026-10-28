<?php

namespace Tests\Feature\BioLink;

use App\Models\Officer;
use App\Services\NameSkeleton;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NameSkeletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_names_are_rejected(): void
    {
        $blockedNames = [
            'Admin SASP',
            'SASP Official',
            '0fficial',
            'AdminSASP',
            'lspd',
            'Sheriff Budi',
        ];

        foreach ($blockedNames as $name) {
            $check = NameSkeleton::checkName($name);
            $this->assertTrue(
                $check['blocked'],
                "Nama '{$name}' harusnya diblokir karena mengandung reserved keyword/role."
            );
        }
    }

    public function test_legitimate_names_are_allowed(): void
    {
        $allowedNames = [
            'Saprudin',
            'Siti Sapriyah',
            'Ahmad Mintoro',
            'Badminton Lover',
            'Budi Santoso',
        ];

        foreach ($allowedNames as $name) {
            $check = NameSkeleton::checkName($name);
            $this->assertFalse(
                $check['blocked'],
                "Nama '{$name}' harusnya diizinkan dan tidak terkena false-positive reserved keyword."
            );
        }
    }

    public function test_registration_with_legitimate_name_succeeds(): void
    {
        $response = $this->post('/register', [
            'name' => 'Saprudin',
            'email' => 'saprudin@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'name' => 'Saprudin',
            'email' => 'saprudin@example.com',
        ]);
    }
}
