<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import iconSearch from "@/Components/Icons/search-svgrepo-com.svg";
import iconCategory from "@/Components/Icons/category-svgrepo-com.svg";
import iconMultiview from "@/Components/Icons/play-full-svgrepo-com.svg";
import iconUser from "@/Components/Icons/user-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import iconBug from "@/Components/Icons/bug-svgrepo-com.svg";
import iconSend from "@/Components/Icons/send-svgrepo-com.svg";
import iconHelp from "@/Components/Icons/chat-svgrepo-com.svg";

const props = defineProps({
    appVersion: {
        type: String,
        default: "2.4.0-Pro",
    },
});

// Category Filter State
const activeCategory = ref("ALL");
const searchQuery = ref("");

// Accordion Expanded State (Array of active FAQ IDs)
const openFaqIds = ref([1, 5]); // Default open 1st & 5th FAQ for immediate visual engagement

const toggleFaq = (id) => {
    if (openFaqIds.value.includes(id)) {
        openFaqIds.value = openFaqIds.value.filter((item) => item !== id);
    } else {
        openFaqIds.value.push(id);
    }
};

const isFaqOpen = (id) => openFaqIds.value.includes(id);

// Comprehensive FAQ List
const faqs = [
    // Category: MULTIVIEW & FITUR
    {
        id: 1,
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        badgeColor: "bg-blue-500/20 text-blue-300 border-blue-500/40",
        question: "Bagaimana cara kerja platform IME Police Multiview ini?",
        answer: "Platform ini dirancang sebagai pusat pemantauan patroli kepolisian IME Roleplay. Sistem menampilkan tayangan langsung (live bodycam/stream) dari perwira aktif (LSPD, BCSO, SASP, SAPR) secara bersamaan sehingga komando dan penonton dapat memantau berbagai sudut operasi secara langsung.<br/><br/><strong>Ketentuan Judul Live Stream:</strong> Judul siaran perwira <strong>wajib menyertakan hashtag <code>#imepolice</code></strong> agar siaran patroli dapat terdeteksi dan tampil otomatis di website ini.",
    },
    {
        id: 2,
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        badgeColor: "bg-blue-500/20 text-blue-300 border-blue-500/40",
        question:
            "Mengapa video awal diputar tanpa suara (Mute)?",
        answer: "Secara bawaan, aturan keamanan browser (seperti Chrome, Edge, Safari) otomatis mematikan suara awal pada halaman web yang memutar beberapa video sekaligus. Untuk mendengarkan suara perwira tertentu, silakan klik tombol <strong>Unmute</strong> atau gunakan fitur <strong>Focus Mode</strong> pada jendela video perwira tersebut.",
    },
    {
        id: 3,
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        badgeColor: "bg-blue-500/20 text-blue-300 border-blue-500/40",
        question: "Apa perbedaan antara Mode Saver dan Play All?",
        answer: "<strong>Mode Saver (Hemat Kuota)</strong> menahan pemutaran otomatis dan menampilkan gambar sampul video yang ringan untuk menghemat kuota internet serta kinerja perangkat Anda. Saat video diklik atau difokuskan (Focus Mode), video akan langsung diputar dengan kualitas jernih.<br/><br/><strong>Mode Play All</strong> memutar seluruh siaran video secara bersamaan untuk pemantauan menyeluruh.",
    },
    {
        id: 4,
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        badgeColor: "bg-blue-500/20 text-blue-300 border-blue-500/40",
        question:
            "Bagaimana cara menyimpan video ke daftar Personal Watchlist?",
        answer: "Anda dapat menekan tombol <strong>Pin (jarum/bintang)</strong> pada kartu video perwira. Video tersebut akan tersimpan otomatis di browser Anda dan terkumpul di tab 📌 <strong>Personal</strong> (maksimal 6 video aktif).",
    },

    // Category: RADIO TAKTIS (TAC)
    {
        id: 5,
        category: "TAC",
        categoryLabel: "Radio Taktis (TAC)",
        badgeColor: "bg-amber-500/20 text-amber-300 border-amber-500/40",
        question: "Apa fungsi dari Saluran Radio Taktis (TAC 1 - 10)?",
        answer: "Saluran TAC (Radio Taktis) digunakan oleh perwira dan komando untuk mengelompokkan siaran unit yang sedang menangani situasi atau operasi tertentu (seperti Pengejaran Kecepatan Tinggi, Perampokan, atau Operasi Khusus). Memilih tab TAC akan menyaring dan menampilkan unit yang terhubung ke saluran tersebut.",
    },
    {
        id: 6,
        category: "TAC",
        categoryLabel: "Radio Taktis (TAC)",
        badgeColor: "bg-amber-500/20 text-amber-300 border-amber-500/40",
        question: "Berapa lama batas waktu alokasi situasi TAC Radio?",
        answer: "Setiap alokasi saluran TAC memiliki batas waktu otomatis selama <strong>30 Menit</strong>. Ketika waktu tersisa kurang dari 60 detik, sistem akan menampilkan pemberitahuan. Petugas dapat memperpanjang waktu situasi (+20 menit) atau menutup saluran jika situasi telah selesai.",
    },

    // Category: STREAMER & PENDAFTARAN
    {
        id: 7,
        category: "OFFICERS",
        categoryLabel: "Streamer & Pendaftaran",
        badgeColor: "bg-emerald-500/20 text-emerald-300 border-emerald-500/40",
        question:
            "Saya perwira polisi baru di IME Roleplay, bagaimana cara mendaftarkan channel siaran saya?",
        answer: 'Anda dapat mengajukan pendaftaran channel siaran langsung melalui menu <a href="/feedback" class="text-sky-400 underline font-bold">Feedback & Reports</a>. Cantumkan Nama Perwira, Callsign, Kesatuan (LSPD/BCSO/SASP/SAPR), dan Link Channel YouTube Anda. Pastikan judul streaming Anda selalu menyertakan hashtag wajib <code>#imepolice</code>.',
    },
    {
        id: 8,
        category: "OFFICERS",
        categoryLabel: "Streamer & Pendaftaran",
        badgeColor: "bg-emerald-500/20 text-emerald-300 border-emerald-500/40",
        question:
            "Apakah sistem ini mendukung pemantauan streamer non-polisi (Target/Gang)?",
        answer: "Ya! Anda dapat menggunakan fitur <strong>Pencarian Live</strong> di bagian atas untuk mencari siaran berdasarkan hashtag (seperti <code>#imeroleplay</code>, <code>#DOJ</code>, <code>#Vagabond</code>) atau memasukkan link YouTube secara manual untuk ditambahkan ke layar pemantauan Anda.",
    },

    // Category: KENDALA TEKNIS
    {
        id: 9,
        category: "TROUBLESHOOTING",
        categoryLabel: "Kendala Teknis",
        badgeColor: "bg-red-500/20 text-red-300 border-red-500/40",
        question:
            'Mengapa pemutar video berwarna hitam atau muncul tulisan "Playback Error"?',
        answer: "Beberapa penyebab umum meliputi: (1) Perwira telah mengakhiri siaran live, (2) Pemilik channel membatasi pemutaran video di luar YouTube, atau (3) Pemblokir iklan (adblocker) di browser Anda memblokir pemutar YouTube. Coba tekan tombol <strong>Sync (Segarkan Siaran)</strong> atau buka video langsung di situs YouTube.",
    },
    {
        id: 10,
        category: "TROUBLESHOOTING",
        categoryLabel: "Kendala Teknis",
        badgeColor: "bg-red-500/20 text-red-300 border-red-500/40",
        question:
            "Apakah situs ini dapat ditambahkan sebagai aplikasi di HP (Android / iPhone)?",
        answer: 'Tentu saja! Anda bisa membuka situs ini di browser Google Chrome (Android) atau Safari (iOS), lalu pilih menu browser <strong>"Add to Home Screen" (Tambahkan ke Layar Utama)</strong> agar situs terpasang dan dapat diakses dengan cepat seperti aplikasi HP.',
    },
    {
        id: 11,
        category: "MULTIVIEW",
        categoryLabel: "Multiview & Fitur",
        badgeColor: "bg-blue-500/20 text-blue-300 border-blue-500/40",
        question:
            "Mengapa saya tidak bisa mengirim Super Chat, Gift Membership, atau Join Member pada Live Chat di website ini?",
        answer: 'Berdasarkan aturan resmi Google & YouTube, fitur transaksi keuangan (seperti <strong>Super Chat, Super Stickers, Gift Membership, dan Join Member</strong>) tidak diizinkan di dalam pemutar video di situs luar untuk menjaga keamanan pembayaran.<br/><br/>Jika Anda ingin mengirimkan Super Chat, Gift, atau bergabung menjadi Member channel perwira, silakan klik tombol <strong>"Buka di YouTube" (ikon panah keluar)</strong> pada kartu siaran untuk bertransaksi langsung di situs resmi <strong>YouTube.com</strong>.',
    },
];

// Categories array for filter buttons
const categories = [
    { id: "ALL", name: "Semua Topik FAQ" },
    { id: "MULTIVIEW", name: "Multiview & Fitur" },
    { id: "TAC", name: "Radio Taktis (TAC)" },
    { id: "OFFICERS", name: "Streamer & Pendaftaran" },
    { id: "TROUBLESHOOTING", name: "Kendala Teknis" },
];

// Computed Filtered FAQs
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
                item.categoryLabel.toLowerCase().includes(q),
        );
    }

    return list;
});
</script>

<template>
    <TacticalLayout>
        <Head title="Panduan & FAQ — IME RP SASP Police Duty" />

        <!-- Main Content Area -->
        <main
            class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
        >
            <!-- Hero Header Banner -->
            <div
                class="bg-gradient-to-br from-blue-950/70 via-slate-900 to-slate-950 border border-blue-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden"
            >
                <div
                    class="absolute -right-12 -bottom-12 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"
                ></div>

                <div
                    class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6"
                >
                    <div class="space-y-2 max-w-2xl">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider"
                        >
                            <img
                                :src="iconHelp"
                                class="w-3.5 h-3.5 invert opacity-80"
                                alt="Help"
                            />
                            <span>Pusat Bantuan & FAQ</span>
                        </div>
                        <h1
                            class="text-2xl sm:text-3xl font-black text-white tracking-tight"
                        >
                            Pertanyaan Umum (FAQ) & Panduan Operasional
                        </h1>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Temukan jawaban cepat mengenai penggunaan Multiview,
                            alokasi TAC Radio taktis, pendaftaran channel
                            perwira, serta solusi masalah teknis secara mandiri.
                        </p>
                    </div>

                    <!-- Search Box -->
                    <div class="w-full md:w-80 shrink-0">
                        <div class="relative flex items-center">
                            <img
                                :src="iconSearch"
                                class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 invert opacity-40 pointer-events-none"
                                alt="Search"
                            />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari pertanyaan / kendala..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-900/90 border border-blue-500/30 focus:border-blue-400 focus:ring-1 focus:ring-blue-400 rounded-xl text-xs text-slate-200 placeholder-slate-500 transition shadow-inner"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs font-bold"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Filter Tabs -->
            <div
                class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-slate-800/80"
            >
                <button
                    @click="activeCategory = 'ALL'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'ALL'
                            ? 'bg-blue-600 text-white border-blue-500 shadow-md shadow-blue-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60',
                    ]"
                >
                    <img
                        :src="iconCategory"
                        class="w-4 h-4 invert"
                        :class="activeCategory !== 'ALL' ? 'opacity-60' : ''"
                        alt=""
                    />
                    <span>Semua Pertanyaan ({{ faqs.length }})</span>
                </button>

                <button
                    @click="activeCategory = 'MULTIVIEW'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'MULTIVIEW'
                            ? 'bg-blue-600 text-white border-blue-500 shadow-md shadow-blue-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60',
                    ]"
                >
                    <img
                        :src="iconMultiview"
                        class="w-4 h-4 invert"
                        :class="
                            activeCategory !== 'MULTIVIEW' ? 'opacity-60' : ''
                        "
                        alt=""
                    />
                    <span>Multiview & Fitur</span>
                </button>

                <button
                    @click="activeCategory = 'OFFICERS'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'OFFICERS'
                            ? 'bg-teal-600 text-white border-teal-500 shadow-md shadow-teal-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60',
                    ]"
                >
                    <img
                        :src="iconUser"
                        class="w-4 h-4 invert"
                        :class="
                            activeCategory !== 'OFFICERS' ? 'opacity-60' : ''
                        "
                        alt=""
                    />
                    <span>Police Streamer & Perwira</span>
                </button>

                <button
                    @click="activeCategory = 'TAC'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'TAC'
                            ? 'bg-amber-600 text-white border-amber-500 shadow-md shadow-amber-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60',
                    ]"
                >
                    <img
                        :src="iconRadio"
                        class="w-4 h-4 invert"
                        :class="activeCategory !== 'TAC' ? 'opacity-60' : ''"
                        alt=""
                    />
                    <span>TAC Radio Channel</span>
                </button>

                <button
                    @click="activeCategory = 'TROUBLESHOOTING'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'TROUBLESHOOTING'
                            ? 'bg-rose-600 text-white border-rose-500 shadow-md shadow-rose-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60',
                    ]"
                >
                    <img
                        :src="iconBug"
                        class="w-4 h-4 invert"
                        :class="
                            activeCategory !== 'TROUBLESHOOTING'
                                ? 'opacity-60'
                                : ''
                        "
                        alt=""
                    />
                    <span>Troubleshooting</span>
                </button>
            </div>

            <!-- FAQ Accordion List -->
            <div class="space-y-4">
                <div
                    v-if="filteredFaqs.length === 0"
                    class="text-center py-16 bg-slate-900/40 border border-slate-800 rounded-2xl space-y-3"
                >
                    <img
                        :src="iconSearch"
                        class="w-12 h-12 invert opacity-20 mx-auto"
                        alt="Not found"
                    />
                    <p class="text-base font-semibold text-slate-300">
                        Tidak ada pertanyaan yang sesuai
                    </p>
                    <p class="text-xs text-slate-500">
                        Coba kata kunci lain atau pilih kategori lain di atas.
                    </p>
                </div>

                <div
                    v-for="faq in filteredFaqs"
                    :key="faq.id"
                    class="bg-slate-900/70 border border-slate-800/90 hover:border-blue-500/40 rounded-2xl overflow-hidden transition shadow-lg"
                >
                    <!-- Accordion Trigger Button -->
                    <button
                        @click="toggleFaq(faq.id)"
                        class="w-full px-6 py-4 flex items-center justify-between text-left gap-4 hover:bg-slate-800/40 transition group"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border shrink-0',
                                    faq.badgeColor,
                                ]"
                            >
                                {{ faq.categoryLabel }}
                            </span>
                            <h2
                                class="text-sm font-bold text-slate-200 group-hover:text-blue-300 transition"
                            >
                                {{ faq.question }}
                            </h2>
                        </div>

                        <div
                            class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700/80 flex items-center justify-center shrink-0 group-hover:border-blue-500/50 transition"
                        >
                            <svg
                                class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-transform duration-200"
                                :class="{ 'rotate-180': isFaqOpen(faq.id) }"
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

                    <!-- Accordion Expanded Content -->
                    <div
                        v-show="isFaqOpen(faq.id)"
                        class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/50 bg-slate-950/40"
                    >
                        <div
                            class="prose prose-invert max-w-none text-slate-300"
                            v-html="faq.answer"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Bottom CTA Banner: Need More Assistance? -->
            <div
                class="bg-gradient-to-r from-blue-950/80 via-slate-900 to-slate-900 border border-blue-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl"
            >
                <div class="space-y-1 text-center sm:text-left">
                    <h3
                        class="text-base font-bold text-white flex items-center justify-center sm:justify-start gap-2"
                    >
                        <img :src="iconHelp" class="w-5 h-5 invert" alt="" />
                        <span>Masih Memiliki Pertanyaan Lain?</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Jika pertanyaan Anda belum terjawab atau Anda ingin
                        mendaftarkan channel perwira baru, kirimkan masukan
                        kepada tim Dispatcher.
                    </p>
                </div>

                <Link
                    href="/feedback"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition shrink-0 flex items-center gap-2"
                >
                    <span>Kirim Laporan / Feedback</span>
                    <img :src="iconSend" class="w-4 h-4 invert" alt="Send" />
                </Link>
            </div>
        </main>
    </TacticalLayout>
</template>
