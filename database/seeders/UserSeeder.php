<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserWatchlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed initial admin and member user accounts.
     */
    public function run(): void
    {
        // 1. Admin Accounts
        $admins = [
            [
                'name' => 'Admin Dispatcher',
                'email' => 'admin@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Gusti Aidan (Agus Tinus Turnip)',
                'email' => 'gustiaidan@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
            [
                'name' => 'SASP Command Center',
                'email' => 'dispatch@sasp.ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
        ];

        foreach ($admins as $adminData) {
            User::updateOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );
        }

        // 2. Member Accounts (Pengguna Umum)
        $members = [
            [
                'name' => 'John Doe',
                'email' => 'johndoe@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'watchlist' => [
                    ['video_id' => 'v2u59yW8rT4', 'officer_name' => "Ofc. E'ido Ertachta"],
                    ['video_id' => 'x891AkmK91s', 'officer_name' => 'Sgt. Babon King'],
                ],
            ],
            [
                'name' => 'Officer Eido Member',
                'email' => 'eido@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'watchlist' => [
                    ['video_id' => 'v2u59yW8rT4', 'officer_name' => "Ofc. E'ido Ertachta"],
                ],
            ],
            [
                'name' => 'Member User',
                'email' => 'user@ime-rp.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'watchlist' => [
                    ['video_id' => 'dQw4w9WgXcQ', 'officer_name' => 'Custom Patrol Unit'],
                ],
            ],
        ];

        foreach ($members as $memberData) {
            $user = User::updateOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'password' => $memberData['password'],
                    'role' => $memberData['role'],
                ]
            );

            // Seed sample Cloud Watchlist entries for members
            if (isset($memberData['watchlist']) && is_array($memberData['watchlist'])) {
                foreach ($memberData['watchlist'] as $w) {
                    UserWatchlist::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'video_id' => $w['video_id'],
                        ],
                        [
                            'officer_name' => $w['officer_name'],
                        ]
                    );
                }
            }
        }
    }
}
