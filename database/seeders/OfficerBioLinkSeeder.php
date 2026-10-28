<?php

namespace Database\Seeders;

use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\OfficerClaim;
use App\Models\User;
use App\Services\BioProfileService;
use App\Services\NameSkeleton;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OfficerBioLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure all existing users have email verified and status active
        User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        User::whereNull('status')->update(['status' => 'active']);

        // 2. Admin Accounts
        $admin = User::updateOrCreate(
            ['email' => 'admin@ime-rp.com'],
            [
                'name' => 'Admin Dispatcher',
                'name_skeleton' => NameSkeleton::make('Admin Dispatcher'),
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'clipper_tier' => 'ultimate',
                'email_verified_at' => now(),
            ]
        );

        // 3. Officer Account 1: Ofc. E'ido Ertachta (LSPD - 1-ADAM-01)
        $officerUser1 = User::updateOrCreate(
            ['email' => 'officer.tester@ime-rp.com'],
            [
                'name' => 'Police Officer Tester',
                'name_skeleton' => NameSkeleton::make('Police Officer Tester'),
                'password' => Hash::make('password123'),
                'role' => 'officer',
                'status' => 'active',
                'clipper_tier' => 'ultimate',
                'email_verified_at' => now(),
            ]
        );

        $officer1 = Officer::where('callsign', '1-ADAM-01')->first() ?: Officer::first();
        if ($officer1) {
            $officer1->update(['user_id' => $officerUser1->id]);

            // Provision Bio Profile
            $profile1 = BioProfileService::ensureBioProfile($officer1);
            $profile1->update([
                'display_title' => $officer1->officer_name,
                'bio_tagline' => "Official SASP Tactical Channel • Unit {$officer1->callsign} • Streamer Patroli FiveM",
                'theme_preset' => 'lspd_metro',
                'button_style' => 'glass_tactical',
                'accent_color' => '#06b6d4',
                'show_live_stream_banner' => true,
                'is_active' => true,
                'is_suspended' => false,
            ]);

            // Seed Sample Bio Links
            $otherOfficerBabon = Officer::where('callsign', '1-LINCOLN-02')->first();
            $otherOfficerLyona = Officer::where('callsign', 'K9-03')->first();

            $sampleLinks = [
                [
                    'title' => 'Saweria Donasi Taktis',
                    'subtitle' => 'Min 10k TTS Nyala & Support Patroli',
                    'url' => 'https://saweria.co/eidoertachta',
                    'url_host' => 'saweria.co',
                    'link_type' => 'donation',
                    'badge_label' => 'ACTIVE TTS',
                    'is_highlighted' => true,
                    'is_active' => true,
                    'moderation_status' => 'approved',
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Discord Markas Kepolisian',
                    'subtitle' => 'Saluran Koordinasi Perwira & Warga',
                    'url' => 'https://discord.gg/ime-police',
                    'url_host' => 'discord.gg',
                    'link_type' => 'discord',
                    'badge_label' => 'OFFICIAL',
                    'is_highlighted' => false,
                    'is_active' => true,
                    'moderation_status' => 'approved',
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Instagram Dokumentasi Patroli',
                    'subtitle' => '@eidoertachta • Foto & Clip Pengejaran',
                    'url' => 'https://instagram.com/eidoertachta',
                    'url_host' => 'instagram.com',
                    'link_type' => 'social',
                    'badge_label' => '',
                    'is_highlighted' => false,
                    'is_active' => true,
                    'moderation_status' => 'approved',
                    'sort_order' => 5,
                ],
                [
                    'title' => 'YouTube Siaran & Arsip VOD',
                    'subtitle' => 'Tonton rekaman patroli sebelumnya',
                    'url' => 'https://youtube.com/@EidoErtachta',
                    'url_host' => 'youtube.com',
                    'link_type' => 'social',
                    'badge_label' => 'VOD',
                    'is_highlighted' => false,
                    'is_active' => true,
                    'moderation_status' => 'approved',
                    'sort_order' => 6,
                ],
            ];

            foreach ($sampleLinks as $linkData) {
                OfficerBioLink::updateOrCreate(
                    [
                        'officer_bio_profile_id' => $profile1->id,
                        'title' => $linkData['title'],
                    ],
                    $linkData
                );
            }

            // Seed Squad Partner Recommendations
            if ($otherOfficerBabon) {
                OfficerBioLink::updateOrCreate(
                    [
                        'officer_bio_profile_id' => $profile1->id,
                        'recommended_officer_id' => $otherOfficerBabon->id,
                    ],
                    [
                        'title' => 'Sgt. Babon King',
                        'link_type' => 'officer_recommendation',
                        'badge_label' => 'SQUAD',
                        'is_active' => true,
                        'moderation_status' => 'approved',
                        'sort_order' => 3,
                    ]
                );
            }

            if ($otherOfficerLyona) {
                OfficerBioLink::updateOrCreate(
                    [
                        'officer_bio_profile_id' => $profile1->id,
                        'recommended_officer_id' => $otherOfficerLyona->id,
                    ],
                    [
                        'title' => 'K9 Handler Lyona',
                        'link_type' => 'officer_recommendation',
                        'badge_label' => 'K-9 UNIT',
                        'is_active' => true,
                        'moderation_status' => 'approved',
                        'sort_order' => 4,
                    ]
                );
            }
        }

        // 4. Officer Account 2: Deputy Bal Pratama (BCSO - 2-BAKER-25)
        $officerUser2 = User::updateOrCreate(
            ['email' => 'deputy.tester@ime-rp.com'],
            [
                'name' => 'Deputy Bal Tester',
                'name_skeleton' => NameSkeleton::make('Deputy Bal Tester'),
                'password' => Hash::make('password123'),
                'role' => 'officer',
                'status' => 'active',
                'clipper_tier' => 'ultimate',
                'email_verified_at' => now(),
            ]
        );

        $officer2 = Officer::where('callsign', '2-BAKER-25')->first();
        if ($officer2) {
            $officer2->update(['user_id' => $officerUser2->id]);

            $profile2 = BioProfileService::ensureBioProfile($officer2);
            $profile2->update([
                'display_title' => $officer2->officer_name,
                'bio_tagline' => "BCSO Sheriff County Patrol • Sandy Shores & Grand Senora • Unit {$officer2->callsign}",
                'theme_preset' => 'bcso_sheriff',
                'button_style' => 'rounded_edge',
                'accent_color' => '#84cc16',
                'show_live_stream_banner' => true,
                'is_active' => true,
                'is_suspended' => false,
            ]);

            OfficerBioLink::updateOrCreate(
                [
                    'officer_bio_profile_id' => $profile2->id,
                    'title' => 'Trakteer Kopi Patroli Sheriff',
                ],
                [
                    'subtitle' => 'Dukungan operasional bensin & logistik',
                    'url' => 'https://trakteer.id/balgaming',
                    'url_host' => 'trakteer.id',
                    'link_type' => 'donation',
                    'badge_label' => 'TRAKTEER',
                    'is_highlighted' => true,
                    'is_active' => true,
                    'moderation_status' => 'approved',
                    'sort_order' => 1,
                ]
            );

            if ($officer1) {
                OfficerBioLink::updateOrCreate(
                    [
                        'officer_bio_profile_id' => $profile2->id,
                        'recommended_officer_id' => $officer1->id,
                    ],
                    [
                        'title' => $officer1->officer_name,
                        'link_type' => 'officer_recommendation',
                        'badge_label' => 'PATROL MATE',
                        'is_active' => true,
                        'moderation_status' => 'approved',
                        'sort_order' => 2,
                    ]
                );
            }
        }

        // 5. Member Account (For testing claims submission)
        $memberTester = User::updateOrCreate(
            ['email' => 'member.tester@ime-rp.com'],
            [
                'name' => 'Member Tester',
                'name_skeleton' => NameSkeleton::make('Member Tester'),
                'password' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
                'clipper_tier' => 'free',
                'email_verified_at' => now(),
            ]
        );

        // 6. Sample Pending Claim for Admin to Review
        $unclaimedOfficer = Officer::where('callsign', '3-VICTOR-18')->first(); // Kezan
        if ($unclaimedOfficer && $unclaimedOfficer->user_id === null) {
            OfficerClaim::firstOrCreate(
                [
                    'user_id' => $memberTester->id,
                    'officer_id' => $unclaimedOfficer->id,
                ],
                [
                    'verification_method' => 'manual_admin',
                    'proof_discord_tag' => 'itskzn#1234',
                    'notes' => 'Halo Admin, saya streamer Trooper Kezan. Mohon approve klaim profil dinas ini.',
                    'status' => 'pending',
                ]
            );
        }

        // 7. Ensure all other officers in the roster have their bio profiles provisioned
        foreach (Officer::where('is_active', true)->get() as $officer) {
            BioProfileService::ensureBioProfile($officer);
        }
    }
}
