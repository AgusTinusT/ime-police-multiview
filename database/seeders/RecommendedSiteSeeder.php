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
                'background_image_url' => '/images/recommended/lspd-background.png',
                'cta_text' => 'Kunjungi website',
                'description' => 'Panduan lapangan untuk anggota Los Santos Police Department: rantai komando, 10-Codes, penal code, prosedur taktis, hingga pembuat laporan patroli dalam satu website.',
                'chips' => ['10-Codes', 'Penal Code', 'Patrol Report'],
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}

