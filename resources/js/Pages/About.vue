<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';

// Icons & Branding
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import iconLspdColor from '@/Components/Icons/LSPD_256.jpg';
import iconBcsoColor from '@/Components/Icons/LSCSD_256.jpg';
import iconSaprColor from '@/Components/Icons/SAPR_256.png';
import iconImeColor from '@/Components/Icons/IME_RP_256.jpg';
import iconSend from '@/Components/Icons/send-svgrepo-com.svg';

const props = defineProps({
    deptStats: {
        type: Object,
        default: () => ({}),
    },
    appVersion: {
        type: String,
        default: '2.4.0-Pro',
    },
});
</script>

<template>
    <TacticalLayout activeTab="about">
        <Head title="Tentang Platform — IME Police Multiview" />

        <!-- Ambient background glow spots -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="absolute -top-32 left-1/4 -translate-x-1/2 w-[700px] h-[400px] bg-blue-600/10 blur-[140px] rounded-full"></div>
            <div class="absolute top-[35%] -right-28 w-[500px] h-[500px] bg-blue-800/15 blur-[130px] rounded-full"></div>
            <div class="absolute bottom-10 left-1/3 w-[600px] h-[350px] bg-slate-900/40 blur-[150px] rounded-full"></div>
        </div>

        <main class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-20">
            
            <!-- 1. SPLIT HERO: STATEMENT VS QUICK FACTS -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Left Statement Column -->
                <div class="lg:col-span-7 space-y-5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-950/60 border border-blue-800/60 text-xs font-mono text-blue-400 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                        <span class="uppercase tracking-wider font-semibold">TENTANG PLATFORM</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-tactical font-extrabold uppercase text-slate-100 tracking-wider leading-[1.15]">
                        Membangun pusat pemantauan patroli yang <span class="text-blue-400">tidak membuang waktu Anda.</span>
                    </h1>

                    <p class="text-sm sm:text-base text-slate-300 font-sans leading-relaxed max-w-xl">
                        Platform ini lahir dari kebutuhan sederhana: menyediakan pusat pemantauan bodycam terintegrasi yang menghargai fokus penonton dan komando, memangkas friksi navigasi, dan menyajikan tayangan patroli IME Roleplay secara real-time dengan akurasi tinggi.
                    </p>

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <a
                            href="https://imeroleplay.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-950/80 hover:bg-blue-900 border border-blue-800/80 text-blue-400 text-xs font-mono font-bold uppercase tracking-wider transition shadow-sm"
                        >
                            <span>Website Resmi imeroleplay.com</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <a
                            href="https://discord.gg/imeroleplay"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 text-xs font-mono font-semibold uppercase tracking-wider transition"
                        >
                            <span>Discord IME RP</span>
                        </a>
                    </div>
                </div>

                <!-- Right Quick Glance Board -->
                <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            RINGKASAN SISTEM MULTIVIEW
                        </h3>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800/60 font-semibold">
                            v{{ appVersion }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-lg bg-blue-950 text-blue-400 border border-blue-800/60 shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="space-y-0.5">
                                <div class="text-xs font-tactical font-bold uppercase text-slate-100 tracking-wider">
                                    KECEPATAN TANPA BLOATWARE
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Dirancang ringan tanpa pelacak komersial yang membebani memori browser Anda saat memutar banyak stream.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-lg bg-blue-950 text-blue-400 border border-blue-800/60 shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div class="space-y-0.5">
                                <div class="text-xs font-tactical font-bold uppercase text-slate-100 tracking-wider">
                                    SINGLE AUDIO POLICY & FOCUS MODE
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Menghindari tumpang-tindih suara antar unit dengan pengelolaan audio otomatis 1-stream aktif.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-lg bg-blue-950 text-blue-400 border border-blue-800/60 shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
                            </div>
                            <div class="space-y-0.5">
                                <div class="text-xs font-tactical font-bold uppercase text-slate-100 tracking-wider">
                                    RADIO TAC & AUTO DETECT #IMEPOLICE
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Sistem mendeteksi siaran live hashtag <code class="text-blue-400 font-mono font-bold">#imepolice</code> otomatis dan mengelompokkannya ke TAC 1 - 10.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <!-- 2. KEPOLISIAN TERDAFTAR (HORIZONTAL TILES) -->
            <section class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-800 pb-5">
                    <div>
                        <span class="text-xs font-mono font-bold uppercase tracking-wider text-blue-400">
                            KESATUAN OPERASIONAL
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-tactical font-bold text-slate-100 uppercase tracking-wider mt-0.5">
                            4 KESATUAN KEPOLISIAN TERDAFTAR
                        </h2>
                    </div>
                    <p class="text-xs text-slate-400 max-w-md leading-relaxed">
                        Seluruh unit patroli dari berbagai divisi kepolisian IME Roleplay terintegrasi dalam satu layar komando.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- LSPD Tile -->
                    <div class="bg-slate-900 border border-slate-800 hover:border-blue-800/80 rounded-2xl p-5 transition group shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono font-bold text-blue-400 bg-blue-950/60 px-2 py-0.5 rounded border border-blue-800/60 uppercase">01. DEPT</span>
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 p-2 shrink-0 flex items-center justify-center">
                                <img :src="iconLspdColor" class="w-full h-full object-contain rounded-lg" alt="LSPD" />
                            </div>
                            <div>
                                <h3 class="font-tactical font-bold text-base uppercase text-slate-100 group-hover:text-blue-300 transition">
                                    LOS SANTOS POLICE DEPT
                                </h3>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    Markas Mission Row, membawahi patroli & penegakan hukum perkotaan Los Santos.
                                </p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500">
                            <span>KODE: LSPD</span>
                            <span class="text-slate-400 font-semibold">City Patrol</span>
                        </div>
                    </div>

                    <!-- BCSO Tile -->
                    <div class="bg-slate-900 border border-slate-800 hover:border-blue-800/80 rounded-2xl p-5 transition group shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono font-bold text-blue-400 bg-blue-950/60 px-2 py-0.5 rounded border border-blue-800/60 uppercase">02. DEPT</span>
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 p-2 shrink-0 flex items-center justify-center">
                                <img :src="iconBcsoColor" class="w-full h-full object-contain rounded-lg" alt="BCSO" />
                            </div>
                            <div>
                                <h3 class="font-tactical font-bold text-base uppercase text-slate-100 group-hover:text-blue-300 transition">
                                    BLAINE COUNTY SHERIFF
                                </h3>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    Markas Paleto Bay & Sandy Shores, menjaga wilayah pedesaan & gurun.
                                </p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500">
                            <span>KODE: BCSO</span>
                            <span class="text-slate-400 font-semibold">County Patrol</span>
                        </div>
                    </div>

                    <!-- SASP Tile -->
                    <div class="bg-slate-900 border border-slate-800 hover:border-blue-800/80 rounded-2xl p-5 transition group shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono font-bold text-blue-400 bg-blue-950/60 px-2 py-0.5 rounded border border-blue-800/60 uppercase">03. DEPT</span>
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 p-2 shrink-0 flex items-center justify-center">
                                <img :src="logoSaspColor" class="w-full h-full object-contain rounded-lg" alt="SASP" />
                            </div>
                            <div>
                                <h3 class="font-tactical font-bold text-base uppercase text-slate-100 group-hover:text-blue-300 transition">
                                    SAN ANDREAS STATE POLICE
                                </h3>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    Divisi negara bagian, pengawasan jalan tol & operasi taktis antar-wilayah.
                                </p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500">
                            <span>KODE: SASP</span>
                            <span class="text-slate-400 font-semibold">State Troopers</span>
                        </div>
                    </div>

                    <!-- SAPR Tile -->
                    <div class="bg-slate-900 border border-slate-800 hover:border-blue-800/80 rounded-2xl p-5 transition group shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono font-bold text-blue-400 bg-blue-950/60 px-2 py-0.5 rounded border border-blue-800/60 uppercase">04. DEPT</span>
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 p-2 shrink-0 flex items-center justify-center">
                                <img :src="iconSaprColor" class="w-full h-full object-contain rounded-lg" alt="SAPR" />
                            </div>
                            <div>
                                <h3 class="font-tactical font-bold text-base uppercase text-slate-100 group-hover:text-blue-300 transition">
                                    SAN ANDREAS PARK RANGER
                                </h3>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                    Pengawasan cagar alam, cagar hutan Mt. Chiliad, & perairan San Andreas.
                                </p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500">
                            <span>KODE: SAPR</span>
                            <span class="text-slate-400 font-semibold">Park Rangers</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. WORKFLOW STORY (STEPPER TIMELINE) -->
            <section class="space-y-8">
                <div class="max-w-xl">
                    <span class="text-xs font-mono font-bold uppercase tracking-wider text-blue-400">
                        WORKFLOW STORY
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-tactical font-bold text-slate-100 uppercase tracking-wider mt-0.5">
                        ALUR PEMANTAUAN TAKTIS MULTIVIEW
                    </h2>
                </div>

                <div class="space-y-6 relative before:absolute before:inset-0 before:left-6 sm:before:left-8 before:w-0.5 before:bg-slate-800">
                    
                    <!-- Step 1 -->
                    <div class="relative flex items-start gap-5 sm:gap-7 group">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-slate-900 border border-blue-800/60 flex items-center justify-center text-blue-400 shrink-0 z-10 shadow-sm group-hover:border-blue-500 transition-all">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1 bg-slate-900 border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <h3 class="font-tactical font-bold text-base sm:text-lg uppercase text-slate-100 tracking-wider">
                                    01. AUTO DETECT HASHTAG #IMEPOLICE
                                </h3>
                                <span class="text-xs font-mono font-semibold px-2.5 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800/60 w-fit">
                                    MODUL INTI
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed font-sans">
                                Setiap perwira yang sedang siaran live di YouTube dengan hashtag <code class="bg-slate-950 px-1.5 py-0.5 rounded text-blue-400 border border-slate-800 font-mono">#imepolice</code> akan otomatis terdeteksi dan masuk ke layar pemantauan tanpa perlu input manual.
                            </p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative flex items-start gap-5 sm:gap-7 group">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-slate-900 border border-blue-800/60 flex items-center justify-center text-blue-400 shrink-0 z-10 shadow-sm group-hover:border-blue-500 transition-all">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </div>
                        <div class="flex-1 bg-slate-900 border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <h3 class="font-tactical font-bold text-base sm:text-lg uppercase text-slate-100 tracking-wider">
                                    02. MULTI-GRID CCTV & SINGLE AUDIO FOCUS
                                </h3>
                                <span class="text-xs font-mono font-semibold px-2.5 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800/60 w-fit">
                                    UI PRESISE
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed font-sans">
                                Komando dan penonton dapat beralih grid layout (2x2, 3x3, 4x4) atau menggunakan Focus Mode layar utama dengan pengelolaan 1 audio aktif agar tidak terjadi tabrakan suara antar unit.
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative flex items-start gap-5 sm:gap-7 group">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-slate-900 border border-blue-800/60 flex items-center justify-center text-blue-400 shrink-0 z-10 shadow-sm group-hover:border-blue-500 transition-all">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        </div>
                        <div class="flex-1 bg-slate-900 border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <h3 class="font-tactical font-bold text-base sm:text-lg uppercase text-slate-100 tracking-wider">
                                    03. ALOKASI RADIO TAC & PERSONAL WATCHLIST
                                </h3>
                                <span class="text-xs font-mono font-semibold px-2.5 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800/60 w-fit">
                                    INTEGRASI DISPATCH
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed font-sans">
                                Unit yang sedang merespon operasi khusus dikelompokkan ke saluran radio TAC 1 - 10 dengan timer alokasi 30 menit. Penonton juga dapat menyematkan stream favorit ke Personal Watchlist.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. DEVELOPER & TECH STACK (SPLIT DEVELOPER SHOWCASE) -->
            <section class="p-6 sm:p-10 rounded-3xl bg-slate-900 border border-slate-800 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    
                    <!-- Left Developer Profile Column -->
                    <div class="lg:col-span-6 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-950/80 border border-blue-800/60 text-blue-400 text-xs font-mono font-semibold uppercase">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            SYSTEM ARCHITECT & DEVELOPER
                        </div>

                        <div class="space-y-1">
                            <h2 class="text-2xl sm:text-3xl font-tactical font-bold uppercase text-slate-100 tracking-wide">
                                GUSTI AIDAN <span class="text-blue-400 font-mono text-sm font-semibold">(@AgusTinusT)</span>
                            </h2>
                            <p class="text-xs font-mono text-slate-400">
                                Fullstack Developer • IME Roleplay Police Enthusiast
                            </p>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-sans">
                            Platform ini murni proyek nirlaba komunitas buat mendampingi teman-teman perwira & penonton di IME Roleplay. Setiap milidetik waktu respons dan setiap piksel tata letak diperhitungkan agar pengguna dapat memantau patroli dengan efisien tanpa gangguan visual.
                        </p>

                        <!-- Action Links -->
                        <div class="pt-2 flex flex-wrap items-center gap-3">
                            <a 
                                href="https://bagibagi.co/Gusti-Aidan" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-950/80 hover:bg-amber-900 border border-amber-800/80 text-amber-400 font-mono font-bold text-xs transition shadow-sm"
                                title="Dukung developer via BagiBagi"
                            >
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                <span>TRAKTIR KOPI (BAGIBAGI)</span>
                            </a>

                            <a 
                                href="https://discord.com/users/572766938275643402" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#5865F2]/20 hover:bg-[#5865F2]/40 text-blue-300 border border-[#5865F2]/40 font-mono font-bold text-xs transition"
                            >
                                <span>DISCORD DEV</span>
                            </a>

                            <Link 
                                href="/feedback" 
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-xs font-mono font-semibold uppercase transition"
                            >
                                <span>KRITIK & MASUKAN</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Right Tech Philosophy & Platform Info -->
                    <div class="lg:col-span-6 bg-slate-950 border border-slate-800 rounded-2xl p-6 space-y-4 font-mono text-xs text-slate-400">
                        <div class="text-xs font-bold uppercase tracking-wider text-blue-400 border-b border-slate-800 pb-2">
                            TEKNOLOGI & FONDASI PLATFORM
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-800/60 text-blue-300">Laravel 11 / PHP 8.2</span>
                            <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-800/60 text-blue-300">Vue 3 Composition API</span>
                            <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-800/60 text-blue-300">Inertia.js v2</span>
                            <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-800/60 text-blue-300">Tailwind Engine</span>
                            <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-800/60 text-blue-300">Vite Bundler</span>
                        </div>

                        <div class="space-y-1.5 pt-2 border-t border-slate-800/80">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Domain Resmi:</span>
                                <span class="text-slate-200 font-bold">policeduty.sasp-motionliferp.com</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Komunitas Server:</span>
                                <span class="text-blue-400 font-bold">IME Roleplay FiveM Indonesia</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Status Lisensi:</span>
                                <span class="text-slate-300">Community Fan-Made (Fair Use)</span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Important Disclaimer regarding YouTube monetization ads -->
            <div class="bg-amber-950/30 border border-amber-800/50 rounded-2xl p-4 flex items-start gap-3.5 text-xs text-amber-300/90 shadow-sm">
                <div class="p-2 rounded-xl bg-amber-950 text-amber-400 border border-amber-800/60 shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="space-y-1 font-sans">
                    <h4 class="font-tactical font-bold text-amber-400 uppercase text-sm tracking-wider">Catatan Mengenai Iklan YouTube:</h4>
                    <p class="leading-relaxed text-slate-300">
                        Iklan yang mungkin muncul saat memutar video di platform ini diputar langsung oleh <strong>YouTube / platform siaran resmi</strong> (sesuai aturan monetisasi channel perwira). Situs IME Police Multiview ini <strong>100% bebas dari iklan komersial pihak ketiga</strong>.
                    </p>
                </div>
            </div>

        </main>
    </TacticalLayout>
</template>
