<?php

/*
|--------------------------------------------------------------------------
| Halaman Dukungan (/dukung)
|--------------------------------------------------------------------------
|
| Semua angka di halaman dukungan berasal dari file ini. Nominal dalam
| Rupiah (integer). Biarkan `null` bila belum pasti: halaman menampilkan
| "Segera diumumkan" alih-alih angka karangan.
|
| Perbarui `collected` + `updated_at` setiap kali ada donasi masuk, lalu
| deploy (atau jalankan `php artisan config:clear` di server).
|
| status goal: 'active' (sedang dikumpulkan), 'planned' (berikutnya),
|              'funded' (tercapai)
|
*/

return [
    'donate_url' => env('SUPPORT_DONATE_URL', 'https://bagibagi.co/Gusti-Aidan'),
    'github_url' => 'https://github.com/AgusTinusT/ime-police-multiview',
    'discord_url' => 'https://discord.gg/gkaXxWpwhA',

    // Tanggal terakhir angka di bawah diperbarui (YYYY-MM-DD).
    'updated_at' => null,

    // Biaya rutin per bulan.
    'monthly_costs' => [
        [
            'label' => 'VPS server aplikasi & database',
            'note' => 'Menjalankan situs, sinkronisasi live stream tiap menit, dan penyimpanan data perwira.',
            'amount' => null,
        ],
        [
            'label' => 'Domain & SSL',
            'note' => 'Alamat situs dan sertifikat keamanan (dihitung per bulan).',
            'amount' => null,
        ],
        [
            'label' => 'Backup & penyimpanan klip',
            'note' => 'Cadangan database harian dan ruang penyimpanan hasil potongan Clip Studio.',
            'amount' => null,
        ],
    ],

    // Apa yang didapat pendukung. Contoh awal: sesuaikan dengan yang benar-benar
    // bisa diberikan, karena halaman menampilkannya sebagai janji ke donatur.
    // 'who' = siapa yang berhak (teks bebas, mis. "Semua donatur").
    'perks' => [
        [
            'icon' => 'heart',
            'title' => 'Nama di Dinding Supporter',
            'description' => 'Namamu (atau alias) tercantum di halaman ini sebagai pendukung.',
            'who' => 'Semua donatur',
        ],
        [
            'icon' => 'badge',
            'title' => 'Badge Supporter',
            'description' => 'Tanda Supporter di akun dan profil bio-link selama masa dukungan.',
            'who' => 'Donatur bulanan',
        ],
        [
            'icon' => 'discord',
            'title' => 'Role Supporter di Discord',
            'description' => 'Role khusus dan kanal diskusi pengembangan di server Discord.',
            'who' => 'Donatur bulanan',
        ],
        [
            'icon' => 'sparkles',
            'title' => 'Akses awal fitur baru',
            'description' => 'Mencoba fitur beta (mis. Clip Studio) lebih dulu dan ikut memberi masukan.',
            'who' => 'Donatur & kontributor GitHub',
        ],
    ],

    // Dinding Supporter: isi manual, terbaru di atas. Kosongkan bila belum ada.
    // Contoh: ['name' => 'Gusti Aidan', 'note' => 'Donatur bulanan'],
    'supporters' => [],

    // Target pendanaan berikutnya, tampil berurutan.
    'goals' => [
        [
            'key' => 'vps-upgrade-tacclips',
            'title' => 'Upgrade VPS: simpan seluruh TacClips',
            'description' => 'Saat ini TacClips hanya menandai detik di video YouTube, sehingga klip hilang bila video asli dihapus atau diprivat. Dengan VPS dan penyimpanan lebih besar, setiap momen aksi bisa diarsipkan permanen di server sendiri.',
            'target' => null,
            'collected' => 0,
            'status' => 'active',
        ],
        [
            'key' => 'event-top-patrol',
            'title' => 'Event komunitas: hadiah Top 1–3 patroli',
            'description' => 'Hadiah bulanan untuk perwira peringkat 1–3 di "Top 10 patroli bulan ini", sebagai apresiasi bagi yang paling rajin bertugas dan siaran.',
            'target' => null,
            'collected' => 0,
            'status' => 'planned',
        ],
    ],
];
