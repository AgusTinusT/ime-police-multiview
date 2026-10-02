<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import iconSend from "@/Components/Icons/send-svgrepo-com.svg";

const props = defineProps({
    appVersion: {
        type: String,
        default: "2.4.0-Pro",
    },
});

// Category & Search State
const activeCategory = ref("ALL");
const searchQuery = ref("");

// Accordion State (Array of open FAQ IDs)
const openFaqIds = ref([1, 5]);

// Copy Link Toast State
const copiedFaqCode = ref(null);

const toggleFaq = (id) => {
    if (openFaqIds.value.includes(id)) {
        openFaqIds.value = openFaqIds.value.filter((item) => item !== id);
    } else {
        openFaqIds.value.push(id);
    }
};

const isFaqOpen = (id) => openFaqIds.value.includes(id);

// Toggle All Accordions State
const isAllOpen = computed(() => {
    return (
        filteredFaqs.value.length > 0 &&
        filteredFaqs.value.every((f) => openFaqIds.value.includes(f.id))
    );
});

const toggleAllAccordions = () => {
    if (isAllOpen.value) {
        openFaqIds.value = [];
    } else {
        openFaqIds.value = filteredFaqs.value.map((f) => f.id);
    }
};

// Set Search Keyword from Quick Chips
const setSearchKeyword = (keyword) => {
    searchQuery.value = keyword;
};

const clearSearch = () => {
    searchQuery.value = "";
};

// Copy Direct Deep Link URL
const copyDirectLink = (faq) => {
    const url = `${window.location.origin}${window.location.pathname}#${faq.code}`;
    navigator.clipboard.writeText(url);
    copiedFaqCode.value = faq.code;
    window.history.replaceState(null, "", `#${faq.code}`);
    setTimeout(() => {
        copiedFaqCode.value = null;
    }, 2000);
};

// Check Hash on Mount for Direct Deep Linking
onMounted(() => {
    if (window.location.hash) {
        const hash = window.location.hash.replace("#", "").toUpperCase();
        const found = faqs.find((f) => f.code.toUpperCase() === hash);
        if (found) {
            openFaqIds.value = [found.id];
            activeCategory.value = found.category;
            setTimeout(() => {
                const el = document.getElementById(found.code);
                if (el)
                    el.scrollIntoView({ behavior: "smooth", block: "center" });
            }, 300);
        }
    }
});

// Comprehensive IME Police SOP & FAQ Data
const faqs = [
    // Category: MULTIVIEW & FITUR
    {
        id: 1,
        code: "SOP-MV-01",
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        question: "Bagaimana cara kerja platform IME Police Multiview ini?",
        answer: 'Platform ini dirancang sebagai pusat pemantauan patroli kepolisian IME Roleplay. Sistem menampilkan tayangan langsung (live bodycam/stream) dari perwira aktif secara bersamaan agar komando dan penonton dapat memantau situasi operasi secara real-time.<br/><br/><div class="p-3.5 rounded-md bg-slate-950 border border-slate-800 text-slate-300 text-xs font-mono space-y-1.5"><div class="flex items-center justify-between"><strong class="uppercase text-blue-400 font-tactical tracking-wider text-xs">Aturan Judul Live Stream:</strong><span class="text-[10px] text-blue-400 font-mono">HASHTAG RULE</span></div><p class="leading-relaxed">Judul siaran perwira <strong>wajib menyertakan hashtag <code class="bg-slate-900 px-1.5 py-0.5 rounded-md text-blue-400 border border-slate-800 font-mono">#imepolice</code></strong> agar siaran patroli terdeteksi dan tampil otomatis di layar pemantauan.</p></div>',
    },
    {
        id: 2,
        code: "SOP-MV-02",
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        question: "Mengapa video awal diputar tanpa suara (Mute)?",
        answer: 'Secara bawaan, aturan keamanan browser (Chrome, Edge, Safari) otomatis mematikan suara awal pada halaman yang memutar beberapa video sekaligus.<br/><br/>Untuk mendengarkan suara perwira tertentu, silakan klik tombol <strong class="text-blue-400 font-mono font-semibold">Unmute</strong> atau aktifkan <strong class="text-blue-400 font-mono font-semibold">Focus Mode</strong> pada jendela video perwira tersebut.',
    },
    {
        id: 3,
        code: "SOP-MV-03",
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        question: "Apa perbedaan antara Mode Saver dan Play All?",
        answer: '<strong class="text-blue-400 font-mono uppercase">Mode Saver (Hemat Kuota):</strong> Menahan pemutaran otomatis dan menampilkan gambar sampul video yang ringan untuk menghemat kuota internet serta kinerja perangkat. Video akan langsung diputar saat diklik.<br/><br/><strong class="text-blue-400 font-mono uppercase">Mode Play All:</strong> Memutar seluruh siaran video secara bersamaan untuk pemantauan komando menyeluruh.',
    },
    {
        id: 4,
        code: "SOP-MV-04",
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        question:
            "Bagaimana cara menyimpan video ke daftar Personal Watchlist?",
        answer: 'Anda dapat menekan tombol <strong class="text-blue-400 font-mono font-semibold">Pin (Bintang)</strong> pada kartu video perwira. Video tersebut akan tersimpan otomatis di browser Anda dan terkumpul di tab <span class="font-mono text-blue-400 font-semibold">Personal Watchlist</span> (maksimal 6 video aktif).',
    },

    // Category: RADIO TAKTIS (TAC)
    {
        id: 5,
        code: "SOP-TAC-01",
        category: "TAC",
        categoryLabel: "Radio Taktis (TAC)",
        question: "Apa fungsi dari Saluran Radio Taktis (TAC 1 - 10)?",
        answer: "Saluran TAC (Radio Taktis) digunakan oleh perwira dan komando untuk mengelompokkan siaran unit yang sedang menangani situasi atau operasi khusus (seperti High Speed Pursuit atau Code 3). Memilih tab TAC akan menyaring dan menampilkan unit yang terhubung ke saluran tersebut.",
    },
    {
        id: 6,
        code: "SOP-TAC-02",
        category: "TAC",
        categoryLabel: "Radio Taktis (TAC)",
        question: "Berapa lama batas waktu alokasi situasi TAC Radio?",
        answer: 'Setiap alokasi saluran TAC memiliki batas waktu otomatis selama <strong class="text-blue-400 font-mono font-bold">30 Menit</strong>. Ketika waktu tersisa kurang dari 60 detik, sistem akan menampilkan pemberitahuan. Petugas dapat memperpanjang waktu situasi (+20 menit) atau menutup saluran jika operasi selesai.',
    },

    // Category: STREAMER & PENDAFTARAN
    {
        id: 7,
        code: "SOP-REG-01",
        category: "OFFICERS",
        categoryLabel: "Streamer & Pendaftaran",
        question: "Bagaimana cara mendaftarkan channel siaran perwira baru?",
        answer: 'Anda dapat mengajukan pendaftaran channel siaran langsung melalui menu <a href="/feedback" class="text-blue-400 font-mono underline font-semibold hover:text-blue-300 transition">Feedback & Reports</a>. Cantumkan Nama Perwira, Callsign, Kesatuan (LSPD/BCSO/SASP/SAPR), dan Link Channel YouTube Anda. Pastikan judul streaming menyertakan hashtag <code class="bg-slate-950 px-1.5 py-0.5 rounded text-blue-400 border border-blue-800/60 font-mono font-semibold">#imepolice</code>.',
    },
    {
        id: 8,
        code: "SOP-REG-02",
        category: "OFFICERS",
        categoryLabel: "Streamer & Pendaftaran",
        question: "Apakah sistem ini mendukung pemantauan streamer non-polisi?",
        answer: 'Ya. Anda dapat menggunakan fitur <strong class="text-blue-400 font-mono">Pencarian Live</strong> di bagian atas untuk mencari siaran berdasarkan hashtag (seperti <code class="bg-slate-950 px-1.5 py-0.5 rounded text-blue-400 border border-blue-800/60 font-mono">#imeroleplay</code>, <code class="bg-slate-950 px-1.5 py-0.5 rounded text-blue-400 border border-blue-800/60 font-mono">#DOJ</code>) atau memasukkan link YouTube secara manual ke layar pemantauan.',
    },

    // Category: KENDALA TEKNIS
    {
        id: 9,
        code: "SOP-SYS-01",
        category: "TROUBLESHOOTING",
        categoryLabel: "Kendala Teknis",
        question:
            'Mengapa pemutar video berwarna hitam atau muncul "Playback Error"?',
        answer: 'Penyebab umum meliputi:<br/>1. Perwira telah mengakhiri siaran live.<br/>2. Pemilik channel membatasi pemutaran video di luar YouTube.<br/>3. Pemblokir iklan (Adblocker) memblokir pemutar YouTube.<br/><br/>Coba tekan tombol <strong class="text-blue-400 font-mono">Sync (Segarkan Siaran)</strong> atau buka video langsung di situs YouTube.',
    },
    {
        id: 10,
        code: "SOP-SYS-02",
        category: "TROUBLESHOOTING",
        categoryLabel: "Kendala Teknis",
        question: "Apakah situs ini dapat ditambahkan sebagai aplikasi di HP?",
        answer: 'Tentu saja. Buka situs ini di Chrome (Android) atau Safari (iOS), lalu pilih menu browser <strong class="text-slate-200 font-mono">"Add to Home Screen"</strong> agar situs terpasang dan dapat diakses cepat seperti aplikasi HP.',
    },
    {
        id: 11,
        code: "SOP-SYS-03",
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        question:
            "Mengapa Super Chat atau Membership tidak muncul di Live Chat?",
        answer: 'Aturan resmi Google & YouTube melarang transaksi keuangan (Super Chat, Gift Membership) di pemutar situs luar untuk menjaga keamanan pembayaran.<br/><br/>Untuk mengirimkan Super Chat atau bergabung menjadi Member, silakan klik tombol <strong class="text-slate-200 font-mono font-semibold">"Buka di YouTube"</strong> pada kartu siaran.',
    },
    // Category: TACCLIPS & STUDIO CLIPPER
    {
        id: 12,
        code: "SOP-CLIP-01",
        category: "CLIPS",
        categoryLabel: "TacClips & Studio Pro",
        question: "Apa itu fitur TacClips dan bagaimana cara menikmatinya?",
        answer: '<strong>TacClips</strong> adalah galeri pemutar video sinematik 16:9 yang menampilkan momen-momen aksi terbaik kepolisian (seperti pengejaran kendaraan Code 3, negosiasi, taktis penggerebekan, dan momen unik perwira).<br/><br/>Anda dapat melakukan navigasi antar klip secara mudah menggunakan tombol panah atas/bawah, scroll mouse, menyukai klip, memutar ulang (replay), atau menyalin tautan untuk dibagikan ke teman.',
    },
    {
        id: 13,
        code: "SOP-CLIP-02",
        category: "CLIPS",
        categoryLabel: "TacClips & Studio Pro",
        question: "Mengapa video klip muncul pesan Error atau Video Tidak Ditemukan (Unavailable)?",
        answer: 'Pesan <strong class="text-amber-400 font-mono">"Video Tidak Ditemukan / Unavailable"</strong> biasanya disebabkan oleh beberapa faktor bawaan kebijakan YouTube:<br/><br/>' +
            '<ul class="list-disc list-inside space-y-1.5 text-slate-300 font-sans">' +
            '<li><strong class="text-slate-100">Video Khusus Member (YouTube Membership):</strong> Video YouTube disetel oleh pemilik channel khusus untuk Member berlangganan. Pemutar luar (embed) tidak diizinkan memutar video berbayar/khusus member.</li>' +
            '<li><strong class="text-slate-100">Video Dihapus atau Diset Private/Unlisted:</strong> Pemilik channel atau YouTube telah menghapus video asli atau mengubah aksesnya menjadi Pribadi (Private).</li>' +
            '<li><strong class="text-slate-100">Pembatasan Penanaman (Embedding Disabled):</strong> Pemilik channel mematikan opsi <em>"Allow embedding"</em> pada pengaturan YouTube Studio.</li>' +
            '<li><strong class="text-slate-100">Pembatasan Usia (Age-Restricted Video):</strong> Video mengandung pembatasan usia yang mewajibkan penonton melakukan verifikasi usia langsung di situs resmi YouTube.</li>' +
            '<li><strong class="text-slate-100">Siaran Live Belum Selesai Diproses:</strong> Klip yang diambil dari siaran langsung (YouTube Live) yang baru saja berakhir membutuhkan waktu beberapa menit bagi server YouTube untuk memproses arsip video resmi (VOD).</li>' +
            '</ul><br/>' +
            '<em>Solusi: Klik tombol <strong class="text-blue-400 font-mono">"Full (Buka di YouTube)"</strong> di panel samping untuk menonton video langsung di situs YouTube resmi.</em>',
    },
    {
        id: 14,
        code: "SOP-CLIP-03",
        category: "CLIPS",
        categoryLabel: "TacClips & Studio Pro",
        question: "Apa itu Studio Clipper Pro dan apa kegunaannya?",
        answer: '<strong class="text-blue-400 font-mono">Studio Clipper Pro</strong> adalah studio khusus yang dirancang bagi perwira dan editor resmi untuk memotong, mengedit, serta memproses cuplikan video siaran YouTube secara presisi.<br/><br/>Melalui studio ini, tim media dapat mengolah cuplikan momen patroli menjadi konten berkualitas tinggi untuk publikasi komunitas.',
    },
    {
        id: 15,
        code: "SOP-CLIP-04",
        category: "CLIPS",
        categoryLabel: "TacClips & Studio Pro",
        question: "Mengapa tombol Akses Studio Clipper bertanda gembok (🔒) dan terkunci?",
        answer: 'Untuk menjaga kualitas dan keamanan operasional, akses penuh ke fitur Studio Clipper Pro **dibatasi khusus untuk akun berizin (Role Clipper / Admin)**.<br/><br/>Jika Anda belum memiliki role yang diizinkan, tombol akan menampilkan tanda gembok <code class="bg-slate-950 px-1.5 py-0.5 rounded text-amber-400 border border-amber-900/60 font-mono">🔒</code>. Jika Anda adalah editor konten atau perwira yang berminat menjadi Clipper resmi IME Police, Anda dapat mengajukan permohonan role melalui halaman <a href="/feedback" class="text-blue-400 font-mono underline font-semibold hover:text-blue-300 transition">Feedback & Support</a>.',
    },
];

// Categories configuration with dedicated SVG icons
const categories = [
    { id: "ALL", name: "Semua Kategori", code: "ALL", icon: "grid" },
    { id: "MULTIVIEW", name: "Multiview & Fitur", code: "MV", icon: "video" },
    { id: "CLIPS", name: "TacClips & Studio Pro", code: "CLIP", icon: "cut" },
    { id: "TAC", name: "Radio Taktis (TAC)", code: "TAC", icon: "radio" },
    {
        id: "OFFICERS",
        name: "Streamer & Registrasi",
        code: "REG",
        icon: "user",
    },
    {
        id: "TROUBLESHOOTING",
        name: "Kendala Teknis",
        code: "SYS",
        icon: "wrench",
    },
];

// Filtered FAQs based on Category and Search Query
const filteredFaqs = computed(() => {
    let list = faqs;

    if (activeCategory.value !== "ALL") {
        list = list.filter((item) => item.category === activeCategory.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (item) =>
                item.question.toLowerCase().includes(q) ||
                item.answer.toLowerCase().includes(q) ||
                item.code.toLowerCase().includes(q) ||
                item.categoryLabel.toLowerCase().includes(q),
        );
    }

    return list;
});

// Dynamic Keyword Highlight Function (Brand Blue Accent)
const highlightKeyword = (text) => {
    if (!searchQuery.value || !searchQuery.value.trim()) return text;
    const q = searchQuery.value.trim().replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${q})`, "gi");
    return text.replace(
        regex,
        '<mark class="bg-blue-500/25 text-blue-200 font-bold px-1 py-0.5 rounded border-b border-blue-400">$1</mark>',
    );
};
</script>

<template>
    <TacticalLayout>
        <Head title="SOP & FAQ — IME Police Terminal" />

        <main
            class="relative z-10 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-12"
        >
            <!-- Hero Header Section -->
            <section
                class="text-center max-w-3xl mx-auto py-6 sm:py-10 space-y-6 sm:space-y-8"
            >
                <div
                    class="inline-flex items-center space-x-2 px-3 py-1 rounded-md bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300"
                >
                    <span
                        class="w-2 h-2 rounded-full bg-blue-400"
                    ></span>
                    <span
                        >DISPATCH MANUAL v{{ appVersion }} •
                        {{ faqs.length }} DOKUMEN SOP</span
                    >
                </div>

                <h1
                    class="text-3xl sm:text-4xl md:text-5xl font-tactical font-extrabold uppercase text-slate-100 tracking-wider leading-tight sm:leading-tight"
                >
                    PANDUAN OPERASIONAL &
                    <span class="text-blue-400">FAQS</span>
                </h1>
                <p
                    class="text-xs sm:text-sm text-slate-300 max-w-2xl mx-auto leading-relaxed font-sans"
                >
                    Temukan panduan taktis, alokasi frekuensi radio, solusi
                    kendala teknis bodycam, dan regulasi pendaftaran perwira.
                </p>

                <!-- Search Field -->
                <div class="relative max-w-2xl mx-auto pt-4 sm:pt-6">
                    <div
                        class="relative flex items-center bg-slate-900 border border-slate-800 focus-within:border-blue-500 rounded-md p-1.5 transition duration-200"
                    >
                        <div class="pl-3.5 text-slate-400 shrink-0">
                            <svg
                                class="w-5 h-5 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Ketik kata kunci (contoh: #imepolice, Focus Mode, TAC Radio)..."
                            class="w-full bg-transparent border-0 border-none ring-0 focus:ring-0 focus:outline-none focus:border-none shadow-none px-3.5 py-2 text-xs sm:text-sm font-sans text-slate-100 placeholder:text-slate-500"
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="p-1.5 text-slate-400 hover:text-white rounded-md hover:bg-slate-800 transition text-xs font-mono shrink-0"
                            title="Bersihkan pencarian"
                        >
                            ✕
                        </button>
                        <div
                            class="hidden sm:flex items-center pr-3 pointer-events-none shrink-0"
                        >
                            <kbd
                                class="px-2 py-0.5 text-[10px] font-mono font-semibold text-slate-400 bg-slate-950 border border-slate-800 rounded-md"
                                >ESC</kbd
                            >
                        </div>
                    </div>

                    <!-- Quick Search Chips -->
                    <div
                        class="mt-5 flex flex-wrap items-center justify-center gap-2 text-xs font-mono text-slate-400"
                    >
                        <span class="text-slate-500">Kata Kunci Cepat:</span>
                        <button
                            @click="setSearchKeyword('#imepolice')"
                            class="px-2.5 py-1 rounded-md bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 transition"
                        >
                            # #imepolice
                        </button>
                        <button
                            @click="setSearchKeyword('Focus Mode')"
                            class="px-2.5 py-1 rounded-md bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-slate-100 transition"
                        >
                            # Focus Mode
                        </button>
                        <button
                            @click="setSearchKeyword('Klip')"
                            class="px-2.5 py-1 rounded-md bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-slate-100 transition"
                        >
                            # Klip Aksi
                        </button>
                        <button
                            @click="setSearchKeyword('Studio Clipper')"
                            class="px-2.5 py-1 rounded-md bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-slate-100 transition"
                        >
                            # Studio Clipper
                        </button>
                        <button
                            @click="setSearchKeyword('TAC')"
                            class="px-2.5 py-1 rounded-md bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-slate-100 transition"
                        >
                            # TAC Radio
                        </button>
                    </div>
                </div>
            </section>

            <!-- 2-Column Responsive Layout: Content (Left) + Sticky Sidebar Navigation (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Main Content Area: Search Meta Bar + Accordion List (Left Column - 8 cols) -->
                <section class="lg:col-span-8 space-y-4">
                    <!-- Search Meta Indicator & Expand All Toolbar -->
                    <div
                        class="flex items-center justify-between px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono"
                    >
                        <div class="flex items-center gap-2 text-slate-300">
                            <svg
                                class="w-4 h-4 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                />
                            </svg>
                            <span v-if="searchQuery">
                                Hasil pencarian:
                                <strong class="text-blue-400">{{
                                    searchQuery
                                }}</strong>
                                ({{ filteredFaqs.length }} dokumen)
                            </span>
                            <span v-else class="text-slate-400">
                                Menampilkan {{ filteredFaqs.length }} dokumen
                                SOP
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                v-if="searchQuery"
                                @click="clearSearch"
                                class="text-blue-400 hover:underline font-semibold"
                            >
                                Reset Filter
                            </button>
                            <button
                                @click="toggleAllAccordions"
                                class="px-3 py-1 rounded-md bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-300 transition text-[11px] font-mono font-semibold flex items-center gap-1.5"
                            >
                                <svg
                                    class="w-3.5 h-3.5 text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 13l-7 7-7-7m14-8l-7 7-7-7"
                                    />
                                </svg>
                                <span>{{
                                    isAllOpen ? "TUTUP SEMUA" : "BUKA SEMUA"
                                }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Empty Search State -->
                    <div
                        v-if="filteredFaqs.length === 0"
                        class="bg-slate-900 border border-slate-800 rounded-xl p-10 text-center space-y-4"
                    >
                        <div
                            class="w-12 h-12 rounded-md bg-slate-950 border border-slate-800 flex items-center justify-center mx-auto text-blue-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h3
                                class="font-tactical text-lg font-bold uppercase text-slate-100 tracking-wider"
                            >
                                TIDAK DITEMUKAN SOP YANG COCOK
                            </h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                Kami tidak menemukan dokumen SOP dengan kata
                                kunci tersebut. Coba kata kunci lain atau reset
                                filter.
                            </p>
                        </div>
                        <button
                            @click="
                                clearSearch();
                                activeCategory = 'ALL';
                            "
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-md text-xs font-mono font-bold uppercase transition"
                        >
                            RESET FILTER PENCARIAN
                        </button>
                    </div>

                    <!-- FAQ Accordion List -->
                    <div class="space-y-3">
                        <div
                            v-for="faq in filteredFaqs"
                            :key="faq.id"
                            :id="faq.code"
                            class="bg-slate-900 border rounded-xl transition overflow-hidden"
                            :class="
                                isFaqOpen(faq.id)
                                    ? 'border-slate-700'
                                    : 'border-slate-800 hover:border-slate-700'
                            "
                        >
                            <!-- Accordion Header Button -->
                            <button
                                @click="toggleFaq(faq.id)"
                                class="w-full px-5 py-4 flex items-start justify-between text-left gap-4 hover:bg-slate-800/40 transition group"
                            >
                                <div class="space-y-2 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2 py-0.5 text-[11px] font-mono font-semibold rounded-md bg-slate-950 text-slate-300 border border-slate-800 uppercase"
                                        >
                                            {{ faq.code }}
                                        </span>
                                        <span
                                            class="text-xs font-mono text-slate-500"
                                        >
                                            {{ faq.categoryLabel }}
                                        </span>
                                    </div>
                                    <h2
                                        class="font-tactical text-base sm:text-[18px] font-bold uppercase text-slate-100 tracking-wide group-hover:text-blue-300 transition leading-snug"
                                        v-html="highlightKeyword(faq.question)"
                                    ></h2>
                                </div>

                                <!-- Chevron Icon -->
                                <div
                                    class="w-7 h-7 rounded-md bg-slate-950 border border-slate-800 flex items-center justify-center text-slate-400 shrink-0 transition duration-200 mt-1"
                                >
                                    <svg
                                        class="w-4 h-4 transform transition-transform duration-200"
                                        :class="
                                            isFaqOpen(faq.id)
                                                ? 'rotate-180 text-blue-400'
                                                : ''
                                        "
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </div>
                            </button>

                            <!-- Accordion Body Answer -->
                            <div
                                v-show="isFaqOpen(faq.id)"
                                class="px-5 pb-5 pt-2 border-t border-slate-800 bg-slate-950/60 text-slate-300 font-sans text-sm leading-relaxed space-y-4"
                            >
                                <div
                                    class="prose prose-invert max-w-2xl text-slate-300 text-sm leading-relaxed"
                                    v-html="highlightKeyword(faq.answer)"
                                ></div>

                                <!-- Shareable Direct Link & Action Footer -->
                                <div
                                    class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono text-slate-400"
                                >
                                    <span
                                        class="text-slate-500 flex items-center gap-1.5"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-blue-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        IME Police Dispatch Document
                                    </span>
                                    <button
                                        @click.stop="copyDirectLink(faq)"
                                        class="text-slate-400 hover:text-blue-400 transition flex items-center gap-1.5"
                                        title="Copy Direct Link URL"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-blue-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                            />
                                        </svg>
                                        <span>{{
                                            copiedFaqCode === faq.code
                                                ? "TAUTAN TERSALIN! ✓"
                                                : "BAGIKAN TAUTAN SOP"
                                        }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Right Column: Sticky Navigation Sidebar -->
                <aside class="lg:col-span-4 space-y-5 lg:sticky lg:top-20">
                    <!-- Category Navigation Pills Card -->
                    <div
                        class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3"
                    >
                        <div
                            class="flex items-center justify-between pb-3 border-b border-slate-800"
                        >
                            <span
                                class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2"
                            >
                                <svg
                                    class="w-4 h-4 text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                    />
                                </svg>
                                KATEGORI PROSEDUR
                            </span>
                            <span
                                class="text-[11px] px-2.5 py-0.5 rounded-md bg-slate-950 text-blue-400 font-mono border border-slate-800 font-semibold"
                            >
                                {{
                                    activeCategory === "ALL"
                                        ? "Semua Topik"
                                        : categories.find(
                                              (c) => c.id === activeCategory,
                                          )?.name
                                }}
                            </span>
                        </div>

                        <nav class="space-y-1.5">
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                @click="activeCategory = cat.id"
                                :class="[
                                    'w-full px-3.5 py-2.5 rounded-md text-xs font-sans font-medium transition flex items-center justify-between text-left border',
                                    activeCategory === cat.id
                                        ? 'bg-blue-600 text-white font-semibold border-blue-600'
                                        : 'bg-slate-950 text-slate-400 hover:text-slate-100 hover:bg-slate-800 border-slate-800',
                                ]"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <svg
                                        v-if="cat.icon === 'grid'"
                                        class="w-4 h-4 shrink-0"
                                        :class="activeCategory === cat.id ? 'text-white' : 'text-blue-400'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="cat.icon === 'video'"
                                        class="w-4 h-4 shrink-0"
                                        :class="activeCategory === cat.id ? 'text-white' : 'text-blue-400'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="cat.icon === 'cut'"
                                        class="w-4 h-4 shrink-0"
                                        :class="activeCategory === cat.id ? 'text-white' : 'text-blue-400'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 0L4 4m5.121 5.121L4 14"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="cat.icon === 'radio'"
                                        class="w-4 h-4 shrink-0"
                                        :class="activeCategory === cat.id ? 'text-white' : 'text-blue-400'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="cat.icon === 'user'"
                                        class="w-4 h-4 shrink-0"
                                        :class="activeCategory === cat.id ? 'text-white' : 'text-blue-400'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="cat.icon === 'wrench'"
                                        class="w-4 h-4 shrink-0"
                                        :class="activeCategory === cat.id ? 'text-white' : 'text-blue-400'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    <span class="truncate">{{ cat.name }}</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 text-[10px] font-mono rounded-md shrink-0"
                                    :class="
                                        activeCategory === cat.id
                                            ? 'bg-blue-700 text-white'
                                            : 'bg-slate-900 text-slate-500'
                                    "
                                >
                                    {{
                                        cat.id === "ALL"
                                            ? faqs.length
                                            : faqs.filter(
                                                  (f) => f.category === cat.id,
                                              ).length
                                    }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Dispatch Support CTA Box -->
                    <div
                        class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-3.5"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="p-2 rounded-md bg-slate-950 text-blue-400 border border-slate-800 shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <h3
                                    class="font-tactical font-bold text-sm uppercase text-slate-100 tracking-wider"
                                >
                                    BUTUH BANTUAN DISPATCHER?
                                </h3>
                                <p
                                    class="text-xs text-slate-400 leading-relaxed"
                                >
                                    Ajukan pendaftaran streamer perwira baru
                                    atau laporkan kendala operasional ke
                                    komando.
                                </p>
                            </div>
                        </div>
                        <Link
                            href="/feedback"
                            class="w-full py-2.5 px-4 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-xs font-mono font-bold uppercase tracking-wider transition flex items-center justify-center gap-2"
                        >
                            <span>LAPOR DISPATCH / FEEDBACK</span>
                            <img
                                :src="iconSend"
                                class="w-3.5 h-3.5 invert opacity-80"
                                alt="Send"
                            />
                        </Link>
                    </div>
                </aside>
            </div>

            <!-- Bottom Operational Support Card (#hubungi-kami) -->
            <section id="hubungi-kami" class="pt-8 border-t border-slate-800">
                <div
                    class="rounded-xl bg-slate-900 border border-slate-800 p-6 sm:p-10 space-y-6 text-center max-w-4xl mx-auto"
                >
                    <div
                        class="w-12 h-12 rounded-md bg-slate-950 text-blue-400 border border-slate-800 flex items-center justify-center mx-auto"
                    >
                        <svg
                            class="w-6 h-6 text-blue-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"
                            />
                        </svg>
                    </div>

                    <div class="space-y-2">
                        <h2
                            class="text-xl sm:text-2xl font-tactical font-bold text-slate-100 uppercase tracking-wider"
                        >
                            BELUM MENEMUKAN SOLUSI OPERASIONAL YANG ANDA
                            BUTUHKAN?
                        </h2>
                        <p
                            class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto leading-relaxed font-sans"
                        >
                            Tim Komando Dispatch dan Admin Sistem siap membantu
                            perwira 24/7 dalam penanganan alokasi frekuensi,
                            verifikasi channel live, atau troubleshooting
                            bodycam.
                        </p>
                    </div>

                    <div
                        class="flex flex-wrap items-center justify-center gap-3"
                    >
                        <Link
                            href="/feedback"
                            class="px-6 py-3 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-xs font-mono font-bold uppercase tracking-wider transition flex items-center gap-2"
                        >
                            <span>KIRIM LAPORAN / TIKET DISPATCH</span>
                            <img
                                :src="iconSend"
                                class="w-3.5 h-3.5 invert opacity-80"
                                alt="Send"
                            />
                        </Link>
                        <button
                            @click="
                                clearSearch();
                                activeCategory = 'ALL';
                            "
                            class="px-6 py-3 rounded-md bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-mono font-semibold uppercase transition"
                        >
                            LIHAT SEMUA DOKUMEN SOP
                        </button>
                    </div>

                    <div
                        class="pt-5 border-t border-slate-800 flex flex-wrap items-center justify-center gap-6 text-xs font-mono text-slate-400"
                    >
                        <span class="flex items-center gap-2">
                            <svg
                                class="w-4 h-4 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            Dukungan Laporan Best Effort
                        </span>
                        <span class="flex items-center gap-2">
                            <svg
                                class="w-4 h-4 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                            Project Komunitas Independent
                        </span>
                        <span class="flex items-center gap-2">
                            <svg
                                class="w-4 h-4 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Sinkronisasi Live Otomatis
                        </span>
                    </div>
                </div>
            </section>
        </main>
    </TacticalLayout>
</template>
