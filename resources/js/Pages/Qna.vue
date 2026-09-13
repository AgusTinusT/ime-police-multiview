<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import iconSearch from '@/Components/Icons/search-svgrepo-com.svg';
import iconCategory from '@/Components/Icons/category-svgrepo-com.svg';
import iconMultiview from '@/Components/Icons/play-full-svgrepo-com.svg';
import iconUser from '@/Components/Icons/user-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconBug from '@/Components/Icons/bug-svgrepo-com.svg';
import iconSend from '@/Components/Icons/send-svgrepo-com.svg';
import iconHelp from '@/Components/Icons/chat-svgrepo-com.svg';

const props = defineProps({
    appVersion: {
        type: String,
        default: '2.4.0-Pro',
    },
});

// Category Filter State
const activeCategory = ref('ALL');
const searchQuery = ref('');

// Mobile Nav Toggle
const mobileMenuOpen = ref(false);

// Accordion Expanded State (Array of active FAQ IDs)
const openFaqIds = ref([1, 5]); // Default open 1st & 5th FAQ for immediate visual engagement

const toggleFaq = (id) => {
    if (openFaqIds.value.includes(id)) {
        openFaqIds.value = openFaqIds.value.filter(item => item !== id);
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
        category: 'MULTIVIEW',
        categoryLabel: 'Multiview & Fitur',
        badgeColor: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        question: 'Bagaimana cara kerja platform IME Police Multiview ini?',
        answer: 'Platform ini dirancang khusus sebagai Tactical Command Center untuk jajaran kepolisian IME Roleplay. Sistem secara otomatis mengagregasi dan menampilkan live bodycam/stream dari perwira aktif (LSPD, BCSO, SASP, SAPR) secara real-time dari platform YouTube, Twitch, dan Kick sehingga komando dan penonton dapat memantau berbagai sudut operasi secara bersamaan.'
    },
    {
        id: 2,
        category: 'MULTIVIEW',
        categoryLabel: 'Multiview & Fitur',
        badgeColor: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        question: 'Mengapa video tidak memutar suara secara otomatis (Autoplay Muted)?',
        answer: 'Secara default, kebijakan keamanan browser modern (seperti Chrome, Edge, Safari) memblokir pemutaran suara otomatis pada halaman web yang menampilkan beberapa media sekaligus. Untuk mendengarkan suara dari perwira tertentu, klik tombol <strong>Unmute</strong> atau gunakan fitur <strong>Focus Mode</strong> pada jendela stream perwira tersebut.'
    },
    {
        id: 3,
        category: 'MULTIVIEW',
        categoryLabel: 'Multiview & Fitur',
        badgeColor: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        question: 'Bagaimana cara kerja fitur Bandwidth Saver / Standby Play Mode?',
        answer: 'Fitur <strong>Bandwidth Saver</strong> bekerja dengan cara menghentikan pemutaran otomatis (auto-play) dan menampilkan thumbnail standby yang ringan. Video baru akan diputar secara manual saat Anda memilih unit yang ingin ditonton, sehingga penggunaan kuota internet dan konsumsi memori (RAM) browser dapat ditekan hingga 70%.'
    },
    {
        id: 4,
        category: 'MULTIVIEW',
        categoryLabel: 'Multiview & Fitur',
        badgeColor: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        question: 'Bagaimana cara mengatur kualitas / resolusi video (1080p, 720p, 480p)?',
        answer: 'Kualitas video diatur langsung melalui ikon gerigi (Settings) pada pemutar video <strong>YouTube</strong> itu sendiri. Perlu diperhatikan bahwa pemutar embed YouTube menerapkan resolusi secara global pada browser Anda: <strong>jika Anda mengubah 1 video ke resolusi 1080p (atau 480p/720p), maka secara otomatis seluruh pemutar video perwira lainnya akan ikut berubah ke resolusi tersebut</strong>.'
    },
    {
        id: 5,
        category: 'MULTIVIEW',
        categoryLabel: 'Multiview & Fitur',
        badgeColor: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        question: 'Akun YouTube mana yang terhubung saat berinteraksi di Live Chat atau Subscribe?',
        answer: 'Interaksi pada player video, fitur 1-Klik Subscribe, maupun Live Chat secara otomatis <strong>mengikuti akun YouTube yang sedang aktif / digunakan pada browser Anda (youtube.com)</strong>. Situs ini tidak pernah meminta, mengakses, atau menyimpan kredensial/kata sandi akun Anda.'
    },
    {
        id: 6,
        category: 'MULTIVIEW',
        categoryLabel: 'Multiview & Fitur',
        badgeColor: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        question: 'Apa perbedaan antara Grid View, Focus Mode, dan Cinema Hub?',
        answer: '<strong>Grid View</strong> menampilkan seluruh perwira aktif dalam tata letak kisi berukuran sama. <strong>Focus Mode</strong> memperbesar 1 perwira utama di tengah dengan thumbnail perwira lainnya di samping. <strong>Cinema Hub</strong> menampilkan rekaman patroli/VOD terbaru saat tidak ada perwira yang sedang 10-8 (Live).'
    },

    // Category: STREAMER & PERWIRA
    {
        id: 7,
        category: 'OFFICER',
        categoryLabel: 'Streamer & Perwira',
        badgeColor: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
        question: 'Bagaimana cara mendaftarkan channel stream perwira baru?',
        answer: 'Perwira yang baru bergabung atau berpindah channel dapat mengajukan penambahan melalui halaman <a href="/feedback" class="text-blue-400 font-bold underline hover:text-blue-300">Feedback & Reports</a>. Pilih kategori <strong>Usulan Channel Streamer Baru</strong>, lalu masukkan Nama Perwira, Callsign, Departemen (LSPD/BCSO/SASP/SAPR), serta Link Channel YouTube/Twitch Anda.'
    },
    {
        id: 8,
        category: 'OFFICER',
        categoryLabel: 'Streamer & Perwira',
        badgeColor: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
        question: 'Mengapa status live stream saya terdeteksi OFFLINE padahal saya sedang Live?',
        answer: 'Sistem auto-telemetry mengenali stream berdasarkan kata kunci di judul live stream Anda. Pastikan judul live stream menyertakan setidaknya salah satu kata kunci seperti <code>IME</code>, <code>IME ROLEPLAY</code>, <code>POLICE</code>, <code>LSPD</code>, <code>BCSO</code>, atau <code>SASP</code>. Jika masih tidak deteksi, periksa apakah link channel di direktori sudah tepat.'
    },
    {
        id: 9,
        category: 'OFFICER',
        categoryLabel: 'Streamer & Perwira',
        badgeColor: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
        question: 'Apakah unit non-kepolisian (seperti EMS, Mekanik, atau Warga) bisa didaftarkan?',
        answer: 'Saat ini platform ini secara khusus dikhususkan untuk <strong>Police Tactical Command Center</strong> (LSPD, BCSO, SASP, SAPR) demi menjamin ruang pantau operasi taktis kepolisian. Namun, usulan fitur integrasi unit darurat lain (seperti EMS) sedang dalam pertimbangan pengembang.'
    },

    // Category: TAC RADIO
    {
        id: 8,
        category: 'TAC_RADIO',
        categoryLabel: 'TAC Radio Channel',
        badgeColor: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
        question: 'Apa itu fitur TAC Channel pada header dashboard?',
        answer: 'Fitur TAC (Tactical Radio Channel) menampilkan alokasi frekuensi radio taktis (seperti TAC-1, TAC-2, TAC-3) yang digunakan jajaran perwira saat melakukan operasi khusus, penyergapan (Code 3), atau perbantuan lintas unit (Pursuit).'
    },
    {
        id: 9,
        category: 'TAC_RADIO',
        categoryLabel: 'TAC Radio Channel',
        badgeColor: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
        question: 'Siapa yang berwenang menetapkan atau mereset saluran TAC Radio?',
        answer: 'Pengaturan dan alokasi TAC Radio dilakukan secara real-time oleh Command Staff / Dispatcher terdaftar melalui panel Admin. Setiap alokasi TAC memiliki timer otomatis yang akan kembali netral jika operasi taktis telah selesai.'
    },

    // Category: TROUBLESHOOTING
    {
        id: 10,
        category: 'TROUBLESHOOTING',
        categoryLabel: 'Troubleshooting',
        badgeColor: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
        question: 'Video mengalami buffering berulang atau layar hitam (Black Screen)?',
        answer: 'Masalah ini biasanya disebabkan oleh salah satu dari hal berikut: (1) Ad-Blocker pihak ketiga memblokir embed player, (2) Batasan koneksi internet lokal, atau (3) YouTube/Twitch membatasi akses embed. Coba muat ulang halaman (F5) atau matikan ekstensi pemblokir iklan di browser Anda.'
    },
    {
        id: 11,
        category: 'TROUBLESHOOTING',
        categoryLabel: 'Troubleshooting',
        badgeColor: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
        question: 'Mengapa sering muncul iklan pada pemutar video (stream)?',
        answer: 'Iklan yang muncul saat memutar video sepenuhnya disajikan dan dikontrol langsung oleh <strong>YouTube / platform penyedia siaran</strong> (sesuai status monetisasi channel perwira yang bersangkutan). <strong>Website IME Police Multiview ini 100% bersih dari iklan komersial bawaan</strong> dan tidak memasang ad-banner pihak ketiga sama sekali.'
    },
    {
        id: 12,
        category: 'TROUBLESHOOTING',
        categoryLabel: 'Troubleshooting',
        badgeColor: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
        question: 'Apakah platform ini aman dan bebas dari pelacakan?',
        answer: 'Ya, platform IME Police Multiview dibuat murni sebagai alat bantu komunitas tanpa iklan berbayar (No Ads), tanpa tracker komersial, serta mematuhi kebijakan privasi embed resmi dari platform penyedia konten.'
    },
];

// Computed Filtered FAQs
const filteredFaqs = computed(() => {
    let list = faqs;

    if (activeCategory.value !== 'ALL') {
        list = list.filter(item => item.category === activeCategory.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(item => 
            item.question.toLowerCase().includes(q) ||
            item.answer.toLowerCase().includes(q) ||
            item.categoryLabel.toLowerCase().includes(q)
        );
    }

    return list;
});
</script>

<template>
    <Head title="Tanya Jawab & Panduan Taktis (QnA) - IME Police Multiview" />

    <div class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased">
        
        <!-- Tactical Header Bar -->
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-4 py-2 flex items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Area: Branding & Navigation -->
            <div class="flex items-center space-x-3 shrink-0">
                <!-- Branding: IME Roleplay Police Division -->
                <Link href="/" class="flex items-center space-x-2.5 shrink-0 group">
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 shadow-inner p-1 overflow-hidden group-hover:border-blue-400 transition">
                        <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-black tracking-wider text-blue-400 uppercase leading-tight group-hover:text-blue-300 transition">IME ROLEPLAY</span>
                        <span class="text-[10px] font-bold tracking-wide text-slate-300 uppercase leading-tight">POLICE DIVISION</span>
                    </div>
                </Link>

                <!-- Vertical Divider -->
                <div class="h-6 w-px bg-slate-800/80 hidden md:block"></div>

                <!-- Page Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1">
                    <Link 
                        href="/officers"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="Officer Directory (LSPD, BCSO, SASP)"
                    >
                        Officer Directory
                    </Link>

                    <Link 
                        href="/about"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="About Police Command Center"
                    >
                        About Platform
                    </Link>

                    <Link 
                        href="/qna"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600/30 text-blue-300 border border-blue-500/50 shadow-sm transition"
                        title="Active Page: QnA & Tactical FAQ Guide"
                    >
                        QnA & FAQ
                    </Link>

                    <Link 
                        href="/feedback"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="Channel Requests & System Feedback"
                    >
                        Feedback & Reports
                    </Link>
                </nav>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center space-x-2">
                <Link 
                    href="/" 
                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-600/30 transition flex items-center gap-1.5"
                    title="Kembali ke Halaman Utama CCTV Multiview"
                >
                    <span>Multiview</span>
                </Link>

                <!-- Mobile Menu Button -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-1.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:text-white"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Mobile Navigation Menu Modal -->
        <div v-if="mobileMenuOpen" class="md:hidden bg-[#0b1320] border-b border-blue-900/40 px-4 py-3 space-y-2">
            <Link href="/officers" class="block px-3 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800">Officer Directory</Link>
            <Link href="/about" class="block px-3 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800">About Platform</Link>
            <Link href="/qna" class="block px-3 py-2 rounded-lg text-sm font-bold text-blue-400 bg-blue-950/50">QnA & FAQ</Link>
            <Link href="/feedback" class="block px-3 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800">Feedback & Reports</Link>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
            
            <!-- Hero Header Banner -->
            <div class="bg-gradient-to-br from-blue-950/70 via-slate-900 to-slate-950 border border-blue-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider">
                            <img :src="iconHelp" class="w-3.5 h-3.5 invert opacity-80" alt="Help" />
                            <span>Pusat Bantuan & Tanya Jawab</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                            Pertanyaan Umum (QnA) & Panduan Operasional
                        </h1>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Temukan jawaban cepat mengenai penggunaan Multiview, alokasi TAC Radio taktis, pendaftaran channel perwira, serta solusi masalah teknis secara mandiri.
                        </p>
                    </div>

                    <!-- Search Box -->
                    <div class="w-full md:w-80 shrink-0">
                        <div class="relative flex items-center">
                            <img :src="iconSearch" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 invert opacity-40 pointer-events-none" alt="Search" />
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
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-slate-800/80">
                <button 
                    @click="activeCategory = 'ALL'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'ALL'
                            ? 'bg-blue-600 text-white border-blue-500 shadow-md shadow-blue-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconCategory" class="w-4 h-4 invert" :class="activeCategory !== 'ALL' ? 'opacity-60' : ''" alt="" />
                    <span>Semua Pertanyaan ({{ faqs.length }})</span>
                </button>

                <button 
                    @click="activeCategory = 'MULTIVIEW'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'MULTIVIEW'
                            ? 'bg-blue-600 text-white border-blue-500 shadow-md shadow-blue-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconMultiview" class="w-4 h-4 invert" :class="activeCategory !== 'MULTIVIEW' ? 'opacity-60' : ''" alt="" />
                    <span>Multiview & Fitur</span>
                </button>

                <button 
                    @click="activeCategory = 'OFFICER'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'OFFICER'
                            ? 'bg-teal-600 text-white border-teal-500 shadow-md shadow-teal-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconUser" class="w-4 h-4 invert" :class="activeCategory !== 'OFFICER' ? 'opacity-60' : ''" alt="" />
                    <span>Police Streamer & Perwira</span>
                </button>

                <button 
                    @click="activeCategory = 'TAC_RADIO'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'TAC_RADIO'
                            ? 'bg-amber-600 text-white border-amber-500 shadow-md shadow-amber-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconRadio" class="w-4 h-4 invert" :class="activeCategory !== 'TAC_RADIO' ? 'opacity-60' : ''" alt="" />
                    <span>TAC Radio Channel</span>
                </button>

                <button 
                    @click="activeCategory = 'TROUBLESHOOTING'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border flex items-center gap-2',
                        activeCategory === 'TROUBLESHOOTING'
                            ? 'bg-rose-600 text-white border-rose-500 shadow-md shadow-rose-600/30'
                            : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200 hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconBug" class="w-4 h-4 invert" :class="activeCategory !== 'TROUBLESHOOTING' ? 'opacity-60' : ''" alt="" />
                    <span>Troubleshooting</span>
                </button>
            </div>

            <!-- FAQ Accordion List -->
            <div class="space-y-4">
                <div v-if="filteredFaqs.length === 0" class="text-center py-16 bg-slate-900/40 border border-slate-800 rounded-2xl space-y-3">
                    <img :src="iconSearch" class="w-12 h-12 invert opacity-20 mx-auto" alt="Not found" />
                    <p class="text-base font-semibold text-slate-300">Tidak ada pertanyaan yang sesuai</p>
                    <p class="text-xs text-slate-500">Coba kata kunci lain atau pilih kategori lain di atas.</p>
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
                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border shrink-0', faq.badgeColor]">
                                {{ faq.categoryLabel }}
                            </span>
                            <h2 class="text-sm font-bold text-slate-200 group-hover:text-blue-300 transition">
                                {{ faq.question }}
                            </h2>
                        </div>

                        <div class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700/80 flex items-center justify-center shrink-0 group-hover:border-blue-500/50 transition">
                            <svg 
                                class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-transform duration-200"
                                :class="{ 'rotate-180': isFaqOpen(faq.id) }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Accordion Expanded Content -->
                    <div 
                        v-show="isFaqOpen(faq.id)"
                        class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/50 bg-slate-950/40"
                    >
                        <div class="prose prose-invert max-w-none text-slate-300" v-html="faq.answer"></div>
                    </div>
                </div>
            </div>

            <!-- Bottom CTA Banner: Need More Assistance? -->
            <div class="bg-gradient-to-r from-blue-950/80 via-slate-900 to-slate-900 border border-blue-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
                <div class="space-y-1 text-center sm:text-left">
                    <h3 class="text-base font-bold text-white flex items-center justify-center sm:justify-start gap-2">
                        <img :src="iconHelp" class="w-5 h-5 invert" alt="" />
                        <span>Masih Memiliki Pertanyaan Lain?</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Jika pertanyaan Anda belum terjawab atau Anda ingin mendaftarkan channel perwira baru, kirimkan masukan kepada tim Dispatcher.
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

        <!-- Tactical Footer -->
        <footer class="mt-auto border-t border-slate-800/80 bg-[#070b12] py-6 px-4 text-center text-xs text-slate-500">
            <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-400">IME POLICE MULTIVIEW</span>
                    <span>•</span>
                    <span>v{{ appVersion }}</span>
                </div>
                <div>
                    Platform Pendukung Komunitas IME Roleplay Police Division
                </div>
            </div>
        </footer>
    </div>
</template>
