<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';

// Icons & Branding
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import iconLspdColor from '@/Components/Icons/LSPD_256.jpg';
import iconBcsoColor from '@/Components/Icons/LSCSD_256.jpg';
import iconSaprColor from '@/Components/Icons/SAPR_256.png';
import iconImeColor from '@/Components/Icons/IME_RP_256.jpg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconAllUnits from '@/Components/Icons/category-svgrepo-com.svg';
import iconChat from '@/Components/Icons/chat-line-svgrepo-com.svg';
import iconSaver from '@/Components/Icons/gauge-low-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconTarget from '@/Components/Icons/target-svgrepo-com.svg';

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
    <Head title="Tentang Platform — IME RP SASP Police Duty Multiview" />

    <TacticalLayout activeTab="about">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            
            <!-- Hero Card -->
            <div class="bg-gradient-to-br from-blue-950/70 via-slate-900 to-slate-950 border border-blue-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col sm:flex-row items-center gap-6">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-slate-900/90 border border-blue-400/40 p-2 shrink-0 flex items-center justify-center shadow-inner">
                    <img :src="logoSaspColor" class="w-full h-full object-contain rounded-xl" alt="SASP" />
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <div class="flex items-center justify-center sm:justify-start gap-2 mb-2">
                        <span class="text-xs font-black tracking-wider text-blue-400 uppercase font-mono">
                            IME ROLEPLAY POLICE DIVISION
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight leading-tight">
                        Community Bodycam & Patrol Multiview
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                        Tempat kumpul penonton buat mantau aksi patroli seluruh unit kepolisian di server GTA V IME Roleplay secara langsung. Nonton bodycam petugas bareng-bareng dari berbagai sudut pandang tanpa ribet, dan lancar.
                    </p>
                </div>
            </div>

            <!-- Core Features Section -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-200 font-mono">Fitur Utama Platform</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-slate-800/90 border border-slate-700/80 text-white shrink-0">
                            <img :src="iconFocus" class="w-5 h-5 brightness-0 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Tactical CCTV Grid & Focus Mode</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Pantau siaran live banyak petugas secara bersamaan dengan layout fleksibel (Auto, 2x2, 3x3, 4x4) atau Focus Mode layar utama dengan audio follow otomatis.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-slate-800/90 border border-slate-700/80 text-white shrink-0">
                            <img :src="iconAllUnits" class="w-5 h-5 brightness-0 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Cinema Hub & Patrol Swimlanes</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Tampilan ala visual Netflix dengan Top Spotlight Billboard dan swimlane per kesatuan untuk siaran patroli live dan rekaman VOD terbaru.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-slate-800/90 border border-slate-700/80 text-white shrink-0">
                            <img :src="iconChat" class="w-5 h-5 brightness-0 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Live Chat & Interaksi Komentar</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Membaca dan berinteraksi di live chat YouTube perwira secara real-time. Akun YouTube yang terhubung otomatis mengikuti sesi akun yang sedang aktif di browser Anda (youtube.com).
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-slate-800/90 border border-slate-700/80 text-white shrink-0">
                            <img :src="iconRadio" class="w-5 h-5 brightness-0 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Radio Taktis TAC 1–5</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Kelompokkan petugas ke dalam frekuensi darurat (TAC 1 s.d. TAC 5) saat merespon perampokan bank, pengejaran 10-80, atau operasi SWAT dengan timer situasi otomatis.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-slate-800/90 border border-slate-700/80 text-white shrink-0">
                            <img :src="iconTarget" class="w-5 h-5 brightness-0 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Support 1K Subs Community Milestone</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Apresiasi komunitas untuk mendukung petugas patroli mencapai target 1.000 subscriber YouTube pertama mereka dengan tombol 1-klik subscribe.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-slate-800/90 border border-slate-700/80 text-white shrink-0">
                            <img :src="iconSaver" class="w-5 h-5 brightness-0 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Data Saver & Standby Manual Play</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Mode penghemat kuota yang mematikan auto-play otomatis dan menampilkan thumbnail standby. Video diputar secara manual (play-on-demand) pada unit yang dipilih untuk menghemat penggunaan memori (RAM) dan kuota hingga 70%.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supported Police Agencies -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-200 font-mono">Kesatuan Kepolisian Terdaftar</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- LSPD -->
                    <div class="bg-slate-900/80 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 text-center flex flex-col items-center transition group shadow-lg">
                        <div class="w-16 h-16 rounded-2xl bg-slate-950/70 border border-slate-800/80 p-2.5 flex items-center justify-center mb-3 group-hover:scale-105 transition shadow-inner">
                            <img :src="iconLspdColor" class="w-full h-full object-contain opacity-90 group-hover:opacity-100 transition rounded-lg" alt="LSPD Logo" />
                        </div>
                        <span class="text-xs font-mono font-black text-slate-200 tracking-wider">LSPD</span>
                        <h4 class="text-sm font-bold text-slate-100 mt-0.5">Los Santos Police Dept</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Markas Pusat Mission Row, membawahi patroli dan penegakan hukum perkotaan Los Santos.</p>
                    </div>

                    <!-- BCSO -->
                    <div class="bg-slate-900/80 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 text-center flex flex-col items-center transition group shadow-lg">
                        <div class="w-16 h-16 rounded-2xl bg-slate-950/70 border border-slate-800/80 p-2.5 flex items-center justify-center mb-3 group-hover:scale-105 transition shadow-inner">
                            <img :src="iconBcsoColor" class="w-full h-full object-contain opacity-90 group-hover:opacity-100 transition rounded-lg" alt="BCSO Logo" />
                        </div>
                        <span class="text-xs font-mono font-black text-slate-200 tracking-wider">BCSO</span>
                        <h4 class="text-sm font-bold text-slate-100 mt-0.5">Blaine County Sheriff</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Markas Paleto Bay & Sandy Shores, menjaga keamanan wilayah pedesaan dan gurun.</p>
                    </div>

                    <!-- SASP -->
                    <div class="bg-slate-900/80 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 text-center flex flex-col items-center transition group shadow-lg">
                        <div class="w-16 h-16 rounded-2xl bg-slate-950/70 border border-slate-800/80 p-2.5 flex items-center justify-center mb-3 group-hover:scale-105 transition shadow-inner">
                            <img :src="iconSaspColor" class="w-full h-full object-contain opacity-90 group-hover:opacity-100 transition rounded-lg" alt="SASP Logo" />
                        </div>
                        <span class="text-xs font-mono font-black text-slate-200 tracking-wider">SASP</span>
                        <h4 class="text-sm font-bold text-slate-100 mt-0.5">San Andreas State Police</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Divisi kepolisian negara bagian, pengawasan jalan tol dan operasi taktis antar-wilayah.</p>
                    </div>

                    <!-- SAPR -->
                    <div class="bg-slate-900/80 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 text-center flex flex-col items-center transition group shadow-lg">
                        <div class="w-16 h-16 rounded-2xl bg-slate-950/70 border border-slate-800/80 p-2.5 flex items-center justify-center mb-3 group-hover:scale-105 transition shadow-inner">
                            <img :src="iconSaprColor" class="w-full h-full object-contain opacity-90 group-hover:opacity-100 transition rounded-lg" alt="SAPR Logo" />
                        </div>
                        <span class="text-xs font-mono font-black text-slate-200 tracking-wider">SAPR</span>
                        <h4 class="text-sm font-bold text-slate-100 mt-0.5">San Andreas Park Ranger</h4>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Pengawasan cagar alam, cagar hutan Gunung Chiliad, dan perairan San Andreas.</p>
                    </div>
                </div>
            </div>

            <!-- Official IME Roleplay Community Card -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-200 font-mono">Komunitas & Server IME Roleplay</h2>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-blue-950/60 via-slate-900/90 to-slate-950 border border-blue-900/50 hover:border-blue-500/40 rounded-3xl p-6 sm:p-7 shadow-xl transition">
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
                            <div class="w-14 h-14 rounded-2xl bg-blue-950/80 border border-blue-500/30 p-2 shrink-0 flex items-center justify-center shadow-inner">
                                <img :src="iconImeColor" class="w-full h-full object-contain rounded-xl" alt="IME Logo" />
                            </div>
                            <div>
                                <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/30 uppercase">IME Roleplay Community</span>
                                    <span class="text-[10px] font-mono text-slate-400 font-bold hidden sm:inline">FiveM GTA V Indonesia</span>
                                </div>
                                <h3 class="text-base sm:text-lg font-black text-white">IME Roleplay</h3>
                                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                    Server FiveM dari komunitas IME Roleplay yang menghadirkan pengalaman roleplay realistis, terstruktur, dan seru. Dengan fitur lengkap dan komunitas yang aktif, kamu bisa membangun cerita serta karakter dengan bebas dan profesional.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 shrink-0">
                            <!-- Official Website -->
                            <a 
                                href="https://imeroleplay.com" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700/90 border border-slate-700 text-slate-200 font-bold text-xs shadow transition flex items-center gap-1.5"
                                title="Kunjungi Website Resmi imeroleplay.com"
                            >
                                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                <span>imeroleplay.com</span>
                            </a>

                            <!-- Discord Invite -->
                            <a 
                                href="https://discord.gg/imeroleplay" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="px-4 py-2.5 rounded-xl bg-[#5865F2] hover:bg-[#4752C4] text-white font-bold text-xs shadow-lg shadow-[#5865F2]/25 transition transform hover:-translate-y-0.5 flex items-center gap-1.5"
                                title="Gabung Server Discord IME Roleplay"
                            >
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.893.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                                <span>Discord IME</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer Profile & BagiBagi Support Card -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-200 font-mono">Pengembang & Kontributor</h2>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-slate-900/95 via-slate-900/80 to-blue-950/40 border border-slate-700/60 hover:border-blue-500/50 rounded-3xl p-6 sm:p-8 shadow-2xl transition duration-300">
                    <!-- Glow background accent -->
                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-6">
                        <!-- Avatar / Tactical Badge -->
                        <div class="relative shrink-0">
                            <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-emerald-600 p-[2px] shadow-lg shadow-blue-500/20">
                                <div class="w-full h-full bg-[#0b1320] rounded-[14px] flex flex-col items-center justify-center text-center p-2">
                                    <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400">DEV</span>
                                    <span class="text-[9px] font-mono text-slate-400 font-bold uppercase tracking-wider mt-0.5">IME FAN</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Content -->
                        <div class="flex-1 text-center md:text-left space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <div class="flex items-center justify-center md:justify-start gap-2">
                                        <h3 class="text-xl font-black text-white tracking-tight">Gusti Aidan</h3>
                                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                            @AgusTinusT
                                        </span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-400 mt-0.5">
                                        Fullstack Developer • IME Roleplay Police Enthusiast
                                    </p>
                                </div>

                                <div class="flex items-center justify-center sm:justify-end gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-800/80 border border-slate-700 text-slate-300 font-mono text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                        Fan-Made Project
                                    </span>
                                </div>
                            </div>

                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-3xl">
                                Platform ini murni proyek nirlaba buat support teman-teman di komunitas IME Roleplay. Untuk sekarang, biaya operasional server masih ditanggung mandiri dengan estimasi bertahan sampai bulan November.

                                Buat kalian yang merasa terbantu dan ingin ikut kontribusi secara sukarela agar platform ini bisa terus berlanjut serta terus berkembang, kalian bisa kirim dukungan. Terima kasih banyak atas dukungannya!
                            </p>

                            <!-- Links & Action Buttons -->
                            <div class="pt-2 flex flex-wrap items-center justify-center md:justify-start gap-3">
                                <!-- BagiBagi Support Button -->
                                <a 
                                    href="https://bagibagi.co/Gusti-Aidan" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-bold text-xs shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 transition transform hover:-translate-y-0.5"
                                    title="Dukung developer lewat BagiBagi"
                                >
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    <span>Traktir Kopi</span>
                                </a>

                                <!-- Discord Button -->
                                <a 
                                    href="https://discord.com/users/572766938275643402" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#5865F2] hover:bg-[#4752C4] text-white font-bold text-xs shadow-lg shadow-[#5865F2]/25 hover:shadow-[#5865F2]/40 transition transform hover:-translate-y-0.5"
                                    title="Hubungi Developer di Discord"
                                >
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.893.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                                    </svg>
                                    <span>Discord</span>
                                </a>

                                <!-- Feedback Link -->
                                <Link 
                                    href="/feedback" 
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 text-slate-300 hover:text-white font-medium text-xs transition"
                                >
                                    <span>Kritik & Masukan</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Architecture & Credits -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 space-y-3 font-mono text-xs text-slate-400">
                <h3 class="text-sm font-bold text-slate-200 font-mono uppercase mb-2">Informasi Platform & Profil Pengembang</h3>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Komunitas Server:</span>
                    <span class="text-blue-400 font-bold">IME Roleplay Indonesia</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Domain Support:</span>
                    <span class="text-amber-400 font-bold">policeduty.sasp-motionliferp.com</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">System Developer:</span>
                    <span class="text-emerald-400 font-bold">Agus Tinus Turnip (Gusti Aidan)</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Status Project:</span>
                    <span class="text-slate-300">Community Fan-Made</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Teknologi Web:</span>
                    <span class="text-slate-300">Laravel 12 + Inertia Vue 3 + PWA Engine</span>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-slate-500">Lisensi & Hak Cipta:</span>
                    <span class="text-slate-400">Fan-made Community Portal (Fair Use)</span>
                </div>
            </div>

            <!-- Disclaimer Mengenai Iklan YouTube -->
            <div class="bg-amber-950/30 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-3.5 text-xs text-amber-200/90 shadow-md">
                <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h4 class="font-bold text-amber-300">Informasi Penting Mengenai Iklan Video:</h4>
                    <p class="leading-relaxed text-slate-300">
                        Iklan yang muncul saat memutar video di platform ini diputar langsung oleh <strong>YouTube / platform penyedia siaran</strong> (sesuai aturan monetisasi channel streamer perwira). Situs IME Police Multiview ini <strong>100% bebas dari iklan komersial pihak ketiga</strong> dan tidak mengambil keuntungan dari iklan tersebut.
                    </p>
                </div>
            </div>

        </div>
    </TacticalLayout>
</template>

