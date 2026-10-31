<?php

namespace Database\Seeders;

use App\Models\RecommendedSite;
use Illuminate\Database\Seeder;

class RecommendedSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RecommendedSite::updateOrCreate(
            ['url' => 'https://ucup-meletup.site/'],
            [
                'name' => 'LSPD HANDBOOK',
                'badge' => 'HANDBOOK',
                'poster_name' => 'LOS SANTOS POLICE DEPARTMENT',
                'poster_subtitle' => 'Handbook Internal',
                'poster_image_url' => '/images/recommended/lspd-sprite.webp',
                'background_image_url' => '/images/recommended/lspd-background.webp',
                'cta_text' => 'Kunjungi website',
                'description' => 'Panduan lapangan untuk anggota Los Santos Police Department: rantai komando, 10-Codes, penal code, prosedur taktis, hingga pembuat laporan patroli dalam satu website.',
                'chips' => ['10-Codes', 'Penal Code', 'Patrol Report'],
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        RecommendedSite::updateOrCreate(
            ['url' => 'https://sasp-motionliferp.com/'],
            [
                'name' => 'SASP FORUM',
                'badge' => 'FORUM',
                'poster_name' => 'LAW ENFORCEMENT',
                'poster_subtitle' => 'To Protect and To Serve',
                'poster_image_url' => '/images/recommended/sasp-sprite.webp',
                'background_image_url' => '/images/recommended/sasp-background.webp',
                'cta_text' => 'Kunjungi website',
                'description' => 'Website internal IME Police. Semua informasi dan pengajuan, mulai dari pengumuman, rekrutmen, reinstatement, hingga lisensi senjata, tersedia di satu forum.',
                'chips' => ['Informasi', 'Pengajuan', 'Employment'],
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}

