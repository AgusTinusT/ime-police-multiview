<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed initial admin and member user accounts.
     */
    public function run(): void
    {
        // Akun demo memakai password publik (password123, tertulis di repo). Jangan pernah
        // dibuat di server yang bisa diakses orang lain.
        if (app()->environment('production', 'staging')) {
            $this->command?->warn('UserSeeder dilewati: akun demo tidak dibuat di '.app()->environment().'.');

            return;
        }

        // 1. Admin Accounts
        $admins = [
            [
                'name' => 'Admin Dispatcher',
                'email' => 'admin@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'clipper_tier' => 'ultimate',
            ],
            [
                'name' => 'Gusti Aidan (Agus Tinus Turnip)',
                'email' => 'gustiaidan@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'clipper_tier' => 'ultimate',
            ],
            [
                'name' => 'SASP Command Center',
                'email' => 'dispatch@sasp.ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'clipper_tier' => 'ultimate',
            ],
        ];

        foreach ($admins as $adminData) {
            User::updateOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );
        }

        // 2. Specialized Clipper Tier Testing Accounts
        $clipperTesters = [
            [
                'name' => 'Free Member Tester',
                'email' => 'free.tester@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'clipper_tier' => 'free',
            ],
            [
                'name' => 'Creator Clipper Tester (Komunitas)',
                'email' => 'clipper.creator@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'clipper',
                'clipper_tier' => 'creator',
            ],
            [
                'name' => 'VIP Supporter Tester',
                'email' => 'vip.ultimate@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'clipper_tier' => 'ultimate',
            ],
            [
                'name' => 'Police Officer Tester',
                'email' => 'officer.tester@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'officer',
                'clipper_tier' => 'ultimate',
            ],
        ];

        foreach ($clipperTesters as $tester) {
            User::updateOrCreate(
                ['email' => $tester['email']],
                $tester
            );
        }

        // 3. General Member Accounts (Pengguna Umum)
        $members = [
            [
                'name' => 'John Doe',
                'email' => 'johndoe@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'clipper_tier' => 'free',
            ],
            [
                'name' => 'Officer Eido Member',
                'email' => 'eido@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'clipper_tier' => 'free',
            ],
            [
                'name' => 'Member User',
                'email' => 'user@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'clipper_tier' => 'free',
            ],
        ];

        foreach ($members as $memberData) {
            User::updateOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'password' => $memberData['password'],
                    'role' => $memberData['role'],
                    'clipper_tier' => $memberData['clipper_tier'],
                ]
            );
        }
    }
}
