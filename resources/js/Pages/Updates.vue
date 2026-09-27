<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";

// Branding & Icons
import iconFeedback from "@/Components/Icons/report-svgrepo-com.svg";

const props = defineProps({
    appVersion: {
        type: String,
        default: "2.5.0-Pro",
    },
});

// Category Filter State
const selectedCategory = ref("ALL");

const categories = [
    { id: "ALL", label: "Semua Pembaruan" },
    { id: "FEATURE", label: "Fitur Baru" },
    { id: "BUGFIX", label: "Perbaikan Bug" },
    { id: "PERFORMANCE", label: "Performa" },
    { id: "UIUX", label: "UI & Pengalaman" },
];

// Release History Dataset (Clean Neutral Tactical Theme)
const releases = ref([
    {
        version: "v2.5.0-Pro",
        date: "27 September 2026",
        badge: "RILIS TERBARU",
        isLatest: true,
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
                tagColor: "bg-purple-950/70 text-purple-300 border-purple-500/40",
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
                tagColor: "bg-emerald-950/70 text-emerald-300 border-emerald-500/40",
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
        version: "v2.4.0-Pro",
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
]);

// Filtered Releases computed
const filteredReleases = computed(() => {
    if (selectedCategory.value === "ALL") {
        return releases.value;
    }
    return releases.value
        .map((rel) => {
            const filtered = rel.changes.filter(
                (c) => c.type === selectedCategory.value,
            );
            if (filtered.length === 0) return null;
            return {
                ...rel,
                changes: filtered,
            };
        })
        .filter(Boolean);
});
</script>

<template>
    <Head title="System Updates & Release Notes — IME RP Police Multiview" />

    <TacticalLayout activeTab="updates">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- HERO HEADER BANNER -->
            <div
                class="bg-slate-900 border border-slate-800 rounded-xl p-6 sm:p-8"
            >
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-6"
                >
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2.5 py-0.5 rounded-md bg-slate-950 text-slate-300 border border-slate-800 text-[10px] font-mono font-bold uppercase tracking-wider"
                            >
                                CATATAN PEMBARUAN SISTEM
                            </span>
                        </div>

                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight leading-tight"
                        >
                            Catatan Pembaruan Sistem
                        </h1>

                        <p
                            class="text-xs sm:text-sm text-slate-400 leading-relaxed"
                        >
                            Riwayat rilis, fitur baru, dan perbaikan performa
                            platform Multiview IME Roleplay Police Command
                            Center.
                        </p>
                    </div>

                    <!-- Platform Version Card -->
                    <div
                        class="bg-slate-950 border border-slate-800 rounded-xl p-4 shrink-0 flex flex-col items-center justify-center text-center gap-1 md:w-48"
                    >
                        <div
                            class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest"
                        >
                            VERSI SAAT INI
                        </div>
                        <div
                            class="text-2xl font-black font-mono text-blue-400 tracking-wider"
                        >
                            {{ appVersion }}
                        </div>
                        <div
                            class="text-[10px] text-slate-500 font-mono mt-0.5"
                        >
                            Aktif Seluruh Unit
                        </div>
                    </div>
                </div>
            </div>

            <!-- METRICS SUMMARY ROW -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >TOTAL RILIS</span
                    >
                    <span
                        class="text-lg font-bold font-mono text-slate-100 mt-1"
                        >4 Versi</span
                    >
                </div>
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >VERSI UTAMA</span
                    >
                    <span class="text-lg font-bold font-mono text-blue-400 mt-1"
                        >v2.4.0-Pro</span
                    >
                </div>
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >HASHTAG RULE</span
                    >
                    <span
                        class="text-lg font-bold font-mono text-slate-200 mt-1"
                        >Wajib #imepolice</span
                    >
                </div>
                <div
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between"
                >
                    <span
                        class="text-[11px] font-mono text-slate-400 font-semibold uppercase"
                        >UPDATE TERAKHIR</span
                    >
                    <span
                        class="text-lg font-bold font-mono text-slate-300 mt-1"
                        >17 Sep 2026</span
                    >
                </div>
            </div>

            <!-- CATEGORY FILTER TABS -->
            <div
                class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none"
            >
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = cat.id"
                    :class="[
                        'px-3 py-1.5 rounded-md text-xs font-bold font-mono transition shrink-0 border',
                        selectedCategory === cat.id
                            ? 'bg-blue-600 text-white border-blue-500'
                            : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800 hover:bg-slate-800',
                    ]"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- RELEASE TIMELINE SECTION -->
            <div class="space-y-6">
                <div
                    v-for="rel in filteredReleases"
                    :key="rel.version"
                    class="relative pl-6 sm:pl-8 border-l border-slate-800 space-y-3 group"
                >
                    <!-- Timeline Marker Dot -->
                    <div
                        class="absolute -left-[7px] top-2 w-3.5 h-3.5 rounded-full border transition duration-300"
                        :class="
                            rel.isLatest
                                ? 'bg-blue-500 border-blue-400'
                                : 'bg-slate-800 border-slate-700 group-hover:border-slate-500'
                        "
                    ></div>

                    <!-- Release Header Card -->
                    <div
                        class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-3.5"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-800"
                        >
                            <div class="flex items-center space-x-2.5">
                                <span
                                    class="text-lg font-bold font-mono text-slate-100 tracking-tight"
                                >
                                    {{ rel.version }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold uppercase border"
                                    :class="
                                        rel.isLatest
                                            ? 'bg-blue-950 text-blue-400 border-blue-900/60'
                                            : 'bg-slate-950 text-slate-400 border-slate-800'
                                    "
                                >
                                    {{ rel.badge }}
                                </span>
                            </div>
                            <span
                                class="text-xs font-mono text-slate-400 font-medium"
                            >
                                {{ rel.date }}
                            </span>
                        </div>

                        <!-- Summary Paragraph -->
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed font-sans"
                        >
                            {{ rel.summary }}
                        </p>

                        <!-- Changes List -->
                        <div class="grid grid-cols-1 gap-2.5 pt-1">
                            <div
                                v-for="(change, cIdx) in rel.changes"
                                :key="cIdx"
                                class="bg-slate-950 border border-slate-800 rounded-md p-3 space-y-1"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-200"
                                    >
                                        {{ change.title }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold border shrink-0',
                                            change.tagColor,
                                        ]"
                                    >
                                        {{ change.tag }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs text-slate-400 leading-relaxed"
                                >
                                    {{ change.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredReleases.length === 0"
                    class="text-center py-10 bg-slate-900 rounded-xl border border-slate-800 p-6 space-y-2"
                >
                    <div class="text-xs font-mono text-slate-400">
                        Tidak ada catatan untuk kategori ini.
                    </div>
                </div>
            </div>

            <!-- FOOTER CALLOUT BANNER -->
            <div
                class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="space-y-0.5">
                    <h4 class="text-xs font-bold text-slate-200">
                        Memiliki Masakan, Masukan, atau Laporan Bug?
                    </h4>
                    <p class="text-xs text-slate-400">
                        Kirim umpan balik Anda untuk membantu pengembangan
                        platform Multiview.
                    </p>
                </div>
                <Link
                    href="/feedback"
                    class="px-3.5 py-2 rounded-md bg-slate-950 hover:bg-slate-800 text-slate-200 text-xs font-bold transition shrink-0 border border-slate-800 flex items-center justify-center gap-2"
                >
                    <img
                        :src="iconFeedback"
                        class="w-3.5 h-3.5 invert opacity-80"
                        alt=""
                    />
                    <span>Kirim Feedback →</span>
                </Link>
            </div>
        </div>
    </TacticalLayout>
</template>
