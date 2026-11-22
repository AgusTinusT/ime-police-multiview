export const releasesId = [
    {
        version: "v2.7.0",
        date: "22 November 2026",
        badge: "RILIS TERBARU",
        isLatest: true,
        summary:
            "Halaman tonton ala YouTube, Momen Aksi dengan arsip otomatis, nama Clip Studio yang seragam, header melayang baru, halaman Dukung yang transparan, serta perbaikan pemotongan siaran live.",
        changes: [
            {
                type: "FEATURE",
                tag: "Halaman Tonton",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Halaman Tonton ala YouTube (/watch)",
                description:
                    "Tonton satu perwira dengan player besar, live chat, daftar live lainnya, dan rekaman terbaru. Kartu di dashboard dan tile multiview kini membuka halaman ini.",
            },
            {
                type: "FEATURE",
                tag: "Momen Aksi",
                tagColor: "bg-purple-950/70 text-purple-300 border-purple-500/40",
                title: "TacClips Kini Bernama Momen Aksi (/momen)",
                description:
                    "Tandai momen langsung dari halaman tonton dengan tombol Tandai Momen. Setiap momen diarsipkan otomatis dalam 720p selama 30 hari, termasuk saat siaran masih live.",
            },
            {
                type: "FEATURE",
                tag: "Clip Studio",
                tagColor: "bg-purple-950/70 text-purple-300 border-purple-500/40",
                title: "Satu Nama: Clip Studio (/clip-studio)",
                description:
                    "Studio potong & unduh video kini memakai satu nama dan alamat. Tautan lama /clipper dan /action-clips tetap berfungsi.",
            },
            {
                type: "FEATURE",
                tag: "Tampilan",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Header Melayang & Menu Akun Baru",
                description:
                    "Header berubah menjadi kapsul melayang saat halaman digulir, dilengkapi menu Lainnya (Kirim masukan, Pembaruan, FAQ) dan menu akun yang lebih rapi.",
            },
            {
                type: "FEATURE",
                tag: "Komunitas",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Halaman Dukung (/dukung)",
                description:
                    "Rincian biaya operasional yang transparan, target berikutnya (hadiah event Top 1–3 patroli), dan cara mendukung lewat donasi, GitHub, atau masukan.",
            },
            {
                type: "FEATURE",
                tag: "Halaman Utama",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Dashboard Lebih Padat & Rapi",
                description:
                    "Officer baru bergabung ditandai selama 14 hari, Top 10 patroli dipindah ke atas, squad milik sendiri dan komunitas digabung tanpa duplikat, dan panah geser hanya muncul saat dibutuhkan.",
            },
            {
                type: "FEATURE",
                tag: "Perwira",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Buka Channel & Akun Bio-Link Non-Personel",
                description:
                    "Profil perwira kini punya tombol Buka Channel. Akun yang hanya memakai bio-link bisa disembunyikan dari daftar perwira tanpa mematikan bio-link-nya.",
            },
            {
                type: "BUGFIX",
                tag: "Clip Studio",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Potong Siaran Live Tepat di Menit yang Dipilih",
                description:
                    "Memotong siaran yang masih live tidak lagi menghasilkan cuplikan sesudah menit yang dipilih. Potongan diambil dari buffer siaran, termasuk momen beberapa jam ke belakang.",
            },
            {
                type: "BUGFIX",
                tag: "Siaran Live",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Siaran #imepolice Selalu Tampil di Sedang Live",
                description:
                    "Judul dengan #imepolice kini tampil di Sedang live walau mengandung kata seperti \"rampok\", dan siaran yang masih live tidak lagi muncul sebagai rekaman.",
            },
            {
                type: "BUGFIX",
                tag: "Halaman Tonton",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Deskripsi Stream Tampil Penuh",
                description:
                    "Deskripsi yang pendek tapi banyak baris tidak lagi terpotong, dan tautan di deskripsi (misalnya donasi) bisa diklik.",
            },
            {
                type: "BUGFIX",
                tag: "Sistem",
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Perbaikan Navigasi, Admin & Keamanan",
                description:
                    "Header dashboard tetap menempel saat digulir, menu admin Rekomendasi Website bisa dibuka kembali, dan dependensi server diperbarui untuk menutup 11 celah keamanan.",
            },
        ],
    },
    {
        version: "v2.6.0",
        date: "31 Oktober 2026",
        summary:
            "Pembaruan arsitektur: Penghentian fitur Watchlist personal dan TAC Channel radio yang digantikan sepenuhnya oleh sistem Squad kustom dan preset komunitas yang lebih fleksibel.",
        changes: [
            {
                type: "REMOVED",
                tag: "Sistem Squad",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Fitur Watchlist Dihentikan — Gunakan Squad",
                description:
                    "Fitur Watchlist personal telah dihentikan. Pengguna kini dapat menggunakan fitur Squad untuk mengelompokkan dan menyimpan perwira favorit secara multi-device.",
            },
            {
                type: "REMOVED",
                tag: "Sistem Squad",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Fitur TAC Channel Dihentikan — Squad Menggantikan",
                description:
                    "Kanal radio taktis TAC 1 - 10 dihentikan dan digantikan oleh squad komunitas dan preset watch party yang dapat dibagikan langsung antar pengguna.",
            },
        ],
    },
    {
        version: "v2.5.0",
        date: "27 September 2026",
        summary:
            "Pembaruan rilis utama: Halaman Dashboard Utama, Fitur Dukungan Streamer 1K Subs, Leaderboard Streamer Bulanan, Panel Visibilitas & Prioritas Perwira, serta Penyederhanaan Multiview Stage 10-8.",
        changes: [
            {
                type: "FEATURE",
                tag: "Halaman Utama",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Halaman Dashboard Utama (Police Duty Command)",
                description:
                    "Pemberharuan struktur rute utama dimana pengguna kini disambut dengan halaman Dashboard interaktif sebelum masuk ke stage Multiview.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Komunitas",
                tagColor:
                    "bg-purple-950/70 text-purple-300 border-purple-500/40",
                title: "Dukungan Streamer Road to 1K Subscriber",
                description:
                    "Seksi khusus di Dashboard untuk mendukung perwira/streamer yang sedang mengejar target 1.000 subscriber (1K Subs) dengan fitur shortcut subscribe langsung.",
            },
            {
                type: "FEATURE",
                tag: "Peringkat Patroli",
                tagColor: "bg-amber-950/70 text-amber-300 border-amber-500/40",
                title: "Leaderboard Streamer & Jam Patroli Bulanan",
                description:
                    "Papan peringkat perwira teraktif bulanan berdasarkan akumulasi durasi jam patroli 10-8, total pemirsa, dan konsistensi siaran bulanan.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Multiview",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Panel Visibilitas & Kelola Perwira (BottomSheet)",
                description:
                    "Pengaturan visibilitas siaran langsung perwira dengan filter status Tampil, Disembunyikan, dan Prioritas, dilengkapi indikator warna konsisten dan pencarian instan.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Multiview",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Pengaturan Hirarki Urutan Grid & Pin Prioritas",
                description:
                    "Perwira dapat diprioritaskan tampil pada posisi teratas grid (#1, #2, #3...) menggunakan fitur Pin (📌) dan tombol navigasi Atas/Bawah (▲/▼) berbasis SVG.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Penyederhanaan Tampilan & Removal 10-7 Offline Roster",
                description:
                    "Pembersihan fitur Roster 10-7 Offline dan menu toolbar sub-header untuk mengoptimalkan performa halaman dan memfokuskan antarmuka 100% pada siaran langsung 10-8.",
            },
            {
                type: "BUGFIX",
                tag: "Perbaikan Bug",
                tagColor:
                    "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
                title: "Perbaikan Pencarian Multiview Control & ReferenceError",
                description:
                    "Memperbaiki pencarian perwira realtime di Multiview Control serta menangani masalah `getOfficerKey` untuk stabilitas aksi prioritas perwira.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Standardisasi UI Sesuai Spesifikasi panduan.html",
                description:
                    "Penerapan standar desain panduan.html: penyelarasan sistem warna status (Emerald untuk Tampil, Rose untuk Disembunyikan, Amber untuk Prioritas), penggunaan aksi single-language 'Tampil' vs 'Sembunyi', serta penggantian seluruh emoticon dengan vektor SVG native.",
            },
        ],
    },
    {
        version: "v2.4.0",
        date: "17 September 2026",
        badge: "STABLE",
        isLatest: false,
        summary:
            "Pembaruan rilis minggu ini: Master Instansi & Pangkat Kepolisian, Sertifikasi Taktis, Pengelolaan Akun Member & RBAC, Obrolan Member Real-time, Tampilan HP & Dock Navigasi, Kualitas Video 1080p HD, dan Keamanan Server.",
        changes: [
            {
                type: "FEATURE",
                tag: "Fitur Admin",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Master Instansi Kepolisian & Hierarki Pangkat",
                description:
                    "Admin kini dapat mengelola struktur instansi kepolisian (SASP, LSPD, BCSO, SAPR), tingkatan pangkat perwira, level komando, dan sub-divisi taktis secara terpusat.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Admin",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Manajemen Sertifikasi & Kualifikasi Taktis",
                description:
                    "Pengelolaan lisensi dan sertifikasi khusus perwira (seperti Air Support, K9, SWAT/SRT, Pursuit Unit) melalui panel admin.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Admin",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Pengelolaan Akun Member & Akses Kontrol (RBAC)",
                description:
                    "Manajemen akun member terdaftar, pengaturan peran hak akses (Admin & Member), serta perlindungan rute administratif.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Member",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Ruang Obrolan Komunitas (Tactical Member Chat)",
                description:
                    "Fitur baru minggu ini: Ruang obrolan langsung khusus member terdaftar dan perwira dengan penanda pangkat/role resmi serta proteksi ketertiban pesan.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Baru",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Penyaringan Judul Wajib Hashtag #imepolice",
                description:
                    "Sistem kini memverifikasi keberadaan hashtag #imepolice pada judul siaran live untuk memastikan hanya siaran patroli resmi yang masuk katalog.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Baru",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Dukungan Simbol & Font Judul Unik",
                description:
                    "Judul siaran YouTube yang menggunakan gaya font khusus (seperti #𝕀𝕄𝔼ℝ𝕆𝕃𝔼ℙ𝕃𝔸𝕐) kini tetap terbaca otomatis oleh sistem agar deteksi siaran berjalan 100% akurat.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Baru",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Akses Transaksi & Tombol 'Buka di YouTube'",
                description:
                    "Menyediakan tombol resmi 'Buka di YouTube' pada kartu siaran agar penonton dapat memberikan Super Chat, Gift Membership, atau Join Member langsung di YouTube.com.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Tampilan Pemutar Khusus HP & Dock Navigasi Bawah",
                description:
                    "Menyediakan susunan pemutar video khusus smartphone (1x1 Focus Mode, 1x2, dan 1x3 Stack) serta menu navigasi bawah jempol untuk kemudahan penggunaan di HP.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Desain Kartu Siaran Minimalis & Floating Controls",
                description:
                    "Menyederhanakan tampilan pemutar video dengan menyembunyikan tombol sekunder dan menampilkannya secara halus hanya saat kursor diarahkan ke kartu siaran.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Penyetaraan Tampilan Header, Footer & Container",
                description:
                    "Menyatukan tata letak halaman (TacticalLayout), menambahkan catatan kaki resmi (TacticalFooter), serta menyelaraskan lebar kontainer di seluruh halaman website.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Dukungan Icon PWA Android (Maskable Icon)",
                description:
                    "Menyesuaikan icon aplikasi PWA Android agar tampil pas dan tidak terpotong saat dipasang di layar utama (Home Screen) smartphone.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Reposisi Posisi Notifikasi Pop-up",
                description:
                    "Menggeser posisi pop-up notifikasi di atas tombol Obrolan Komunitas agar pesan pemberitahuan selalu terlihat jelas dan tidak tertutup.",
            },
            {
                type: "PERFORMANCE",
                tag: "Performa",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Kualitas Video Jernih 1080p HD Saat Fokus",
                description:
                    "Video yang diputar atau difokuskan secara otomatis beralih ke kualitas jernih 1080p HD tanpa mengurangi efisiensi kuota pada Mode Hemat.",
            },
            {
                type: "PERFORMANCE",
                tag: "Keamanan & API",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Peningkatan Keamanan Server & Dynamic Chat Domain",
                description:
                    "Memperketat proteksi keamanan server (CSRF & Rate Limiting) serta memastikan domain embed live chat beradaptasi secara dinamis.",
            },
            {
                type: "BUGFIX",
                tag: "Perbaikan Bug",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Pemberdayaan Tombol Langganan (Subscribe)",
                description:
                    "Tombol berlangganan YouTube kini dipastikan selalu berfungsi dengan mengarahkan langsung ke saluran perwira tanpa kendala link rusak.",
            },
            {
                type: "BUGFIX",
                tag: "Perbaikan Bug",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Pembersihan Siaran Ulang dari Personal Watchlist",
                description:
                    "Memperbaiki masalah di mana rekaman siaran ulang (VOD) tidak lagi otomatis masuk ke daftar Personal Watchlist pengguna saat memutar video.",
            },
            {
                type: "BUGFIX",
                tag: "Perbaikan Bug",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Dukungan Link Kanal Perwira Pendukung",
                description:
                    "Mengarahkan klik kartu perwira pendukung (offline) langsung ke halaman kanal YouTube resmi mereka untuk mencegah timbulnya kendala pemutaran.",
            },
        ],
    },
    {
        version: "v2.3.5",
        date: "15 September 2026",
        badge: "VERSI STABIL",
        isLatest: false,
        summary:
            "Mode Hemat Kuota (Saver Mode), Obrolan Komunitas Real-time, dan Peningkatan Pemindaian Siaran.",
        changes: [
            {
                type: "FEATURE",
                tag: "Fitur Baru",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Mode Hemat Kuota (Saver Mode)",
                description:
                    "Pengguna dapat mengaktifkan Mode Saver untuk menghemat kuota internet dan beban perangkat saat memutar banyak siaran sekaligus.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Baru",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Menu Obrolan Komunitas (Member Chat)",
                description:
                    "Ruang obrolan langsung untuk seluruh anggota komunitas & perwira dengan dukungan penyematan pesan penting oleh Dispatcher.",
            },
            {
                type: "BUGFIX",
                tag: "Perbaikan Bug",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Peningkatan Sistem Pemindaian Link YouTube",
                description:
                    "Memperbaiki sistem pemindaian otomatis agar link siaran live YouTube terdeteksi lebih cepat dan stabil.",
            },
        ],
    },
    {
        version: "v2.2.0",
        date: "10 September 2026",
        badge: "VERSI STABIL",
        isLatest: false,
        summary:
            "Saluran Radio Taktis TAC 1 - TAC 10, Tampilan Kategori Departemen, dan Peningkatan Kecepatan Halaman.",
        changes: [
            {
                type: "FEATURE",
                tag: "Fitur Baru",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Saluran Situasi Darurat Radio Taktis (TAC 1 - TAC 10)",
                description:
                    "Dispatcher & perwira dapat mengalokasikan siaran ke saluran taktis khusus dengan batas waktu otomatis.",
            },
            {
                type: "UIUX",
                tag: "UI & Pengalaman",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Pengelompokan Siaran Berdasarkan Divisi",
                description:
                    "Penyusunan baris siaran berdasarkan divisi departemen (LSPD, BCSO, SASP, SAPR, High Command, Undercover).",
            },
            {
                type: "PERFORMANCE",
                tag: "Performa",
                tagColor: "bg-slate-800 text-slate-300 border-slate-700",
                title: "Penyimpanan Cache & Kecepatan Muat",
                description:
                    "Sistem menyimpan status siaran secara efisien sehingga halaman terbuka jauh lebih cepat dan responsif.",
            },
        ],
    },
    {
        version: "v2.0.0",
        date: "1 September 2026",
        badge: "VERSI UTAMA",
        isLatest: false,
        summary:
            "Peluncuran Perdana Command Center Multiview untuk Komunitas IME Roleplay.",
        changes: [
            {
                type: "FEATURE",
                tag: "Fitur Utama",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Layar Pemutar Multiview",
                description:
                    "Dukungan pemutaran banyak siaran sekaligus (tampilan 2x2, 3x3, 4x4, & Mode Fokus) untuk memantau siaran perwira patroli secara bersamaan.",
            },
            {
                type: "FEATURE",
                tag: "Fitur Utama",
                tagColor: "bg-blue-950/70 text-blue-300 border-blue-500/40",
                title: "Direktori Perwira & Pusat Pengelolaan",
                description:
                    "Manajemen data perwira 10-7, pengelolaan pangkat, callsign, dan penambahan kanal siaran baru.",
            },
        ],
    },
];
