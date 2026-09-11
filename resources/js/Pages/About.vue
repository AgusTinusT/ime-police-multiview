<script setup>
import { Head, Link } from '@inertiajs/vue3';

// Icons & Branding
import logoSaspColor from '@/Components/Icons/SASP256.jpg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconAllUnits from '@/Components/Icons/category-svgrepo-com.svg';
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
    <Head title="Tentang Command Center - IME Police Multiview" />

    <div class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased">
        
        <!-- Tactical Header Bar -->
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-4 py-2 flex items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Area: Branding & Standalone Page Navigation Links -->
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

                <!-- Page Navigation Links (Clean Minimalist Text Tabs) -->
                <nav class="hidden md:flex items-center space-x-1">
                    <Link 
                        href="/officers"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="Officer Directory (LSPD, BCSO, SASP)"
                    >
                        Officer Directory
                    </Link>

                    <Link 
                        href="/radio-codes"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="10-Codes & Tactical Radio Channels (TAC 1-10)"
                    >
                        10-Codes & Radio
                    </Link>

                    <Link 
                        href="/about"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-indigo-600/30 text-indigo-300 border border-indigo-500/50 shadow-sm transition"
                        title="Active Page: About Police Command Center"
                    >
                        About Platform
                    </Link>

                    <Link 
                        href="/feedback"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-sky-300 hover:bg-sky-950/40 transition"
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
                    <img :src="iconFocus" class="w-3.5 h-3.5 invert" alt="" />
                    <span>CCTV Multiview</span>
                </Link>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
            
            <!-- Hero Card -->
            <div class="bg-gradient-to-br from-blue-950/70 via-slate-900 to-slate-950 border border-blue-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col sm:flex-row items-center gap-6">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-slate-900/90 border border-blue-400/40 p-2 shrink-0 flex items-center justify-center shadow-inner">
                    <img :src="logoSaspColor" class="w-full h-full object-contain rounded-xl" alt="SASP" />
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <div class="flex items-center justify-center sm:justify-start gap-2 mb-2">
                        <span class="text-xs font-black tracking-wider text-blue-400 uppercase font-mono">IME ROLEPLAY INDONESIA</span>
                        <span class="text-[10px] bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded-full font-mono font-bold border border-blue-500/40">v{{ appVersion }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight leading-tight">
                        Police Command Center & Tactical Multiview
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                        Pusat pengawasan siaran langsung terpadu bagi seluruh unit kepolisian di server GTA V IME Roleplay. Memungkinkan penonton dan warga memantau kamera bodycam petugas secara simultan tanpa lag dan hemat kuota.
                    </p>
                </div>
            </div>

            <!-- Core Features Section -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-200 font-mono">Fitur Utama Platform</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-blue-950/80 border border-blue-500/30 text-blue-400 shrink-0">
                            <img :src="iconFocus" class="w-5 h-5 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Tactical CCTV Grid & Focus Mode</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Pantau siaran live banyak petugas secara bersamaan dengan layout fleksibel (Auto, 2x2, 3x3, 4x4) atau Focus Mode layar utama dengan audio follow otomatis.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-indigo-950/80 border border-indigo-500/30 text-indigo-400 shrink-0">
                            <img :src="iconAllUnits" class="w-5 h-5 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Cinema Hub & Patrol Swimlanes</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Tampilan ala visual Netflix dengan Top Spotlight Billboard dan swimlane per kesatuan untuk siaran patroli live dan rekaman VOD terbaru.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-amber-950/80 border border-amber-500/30 text-amber-400 shrink-0">
                            <img :src="iconRadio" class="w-5 h-5 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Radio Taktis TAC 1–5</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Kelompokkan petugas ke dalam frekuensi darurat (TAC 1 s.d. TAC 5) saat merespon perampokan bank, pengejaran 10-80, atau operasi SWAT dengan timer situasi otomatis.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start">
                        <div class="p-3 rounded-xl bg-red-950/80 border border-red-500/30 text-red-400 shrink-0">
                            <img :src="iconTarget" class="w-5 h-5 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Support 1K Subs Community Milestone</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Apresiasi komunitas untuk mendukung petugas patroli mencapai target 1.000 subscriber YouTube pertama mereka dengan tombol 1-klik subscribe.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-3.5 items-start md:col-span-2">
                        <div class="p-3 rounded-xl bg-emerald-950/80 border border-emerald-500/30 text-emerald-400 shrink-0">
                            <img :src="iconSaver" class="w-5 h-5 invert" alt="" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">Data Saver & Smart Bandwidth</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Penghematan kuota cerdas dengan thumbnail standby ringan sebelum memutar player video, mencegah lag browser dan buffering berlebih.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supported Police Agencies -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <h2 class="text-base font-bold uppercase tracking-wider text-slate-200 font-mono">Kesatuan Kepolisian Terdaftar</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-slate-900/80 border border-blue-500/30 rounded-2xl p-5 text-center flex flex-col items-center">
                        <span class="px-3 py-1 text-xs font-black rounded-lg bg-blue-600 text-white mb-2 shadow">LSPD</span>
                        <h4 class="text-sm font-bold text-slate-200">Los Santos Police Department</h4>
                        <p class="text-xs text-slate-400 mt-1">Markas Pusat Mission Row, membawahi patroli perkotaan Los Santos.</p>
                    </div>

                    <div class="bg-slate-900/80 border border-amber-500/30 rounded-2xl p-5 text-center flex flex-col items-center">
                        <span class="px-3 py-1 text-xs font-black rounded-lg bg-amber-600 text-white mb-2 shadow">BCSO</span>
                        <h4 class="text-sm font-bold text-slate-200">Blaine County Sheriff's Office</h4>
                        <p class="text-xs text-slate-400 mt-1">Markas Paleto Bay & Sandy Shores, menjaga keamanan wilayah pedesaan dan gurun.</p>
                    </div>

                    <div class="bg-slate-900/80 border border-indigo-500/30 rounded-2xl p-5 text-center flex flex-col items-center">
                        <span class="px-3 py-1 text-xs font-black rounded-lg bg-indigo-600 text-white mb-2 shadow">SASP</span>
                        <h4 class="text-sm font-bold text-slate-200">San Andreas State Police</h4>
                        <p class="text-xs text-slate-400 mt-1">Divisi penegak hukum negara bagian, pengawasan jalan tol dan operasi taktis khusus.</p>
                    </div>
                </div>
            </div>

            <!-- Platform Architecture & Credits -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 space-y-3 font-mono text-xs text-slate-400">
                <h3 class="text-sm font-bold text-slate-200 font-mono uppercase mb-2">Informasi Teknis & Kredit</h3>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Komunitas Roleplay:</span>
                    <span class="text-blue-400 font-bold">IME Roleplay Indonesia</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Teknologi:</span>
                    <span class="text-slate-300">Laravel 12 + Inertia.js Vue 3 + Tailwind CSS</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-800 py-1.5">
                    <span class="text-slate-500">Video Integration:</span>
                    <span class="text-slate-300">YouTube IFrame API & Hybrid Scraper Engine</span>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-slate-500">Lisensi & Hak Cipta:</span>
                    <span class="text-slate-400">Fan-made Community Dashboard (Fair Use)</span>
                </div>
            </div>

        </main>

        <!-- TACTICAL COMMAND CENTER FOOTER -->
        <footer class="mt-20 bg-[#080d18]/95 border-t border-blue-900/40 text-slate-400 text-xs backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                    <div>
                        <h4 class="text-sm font-black text-blue-400 uppercase font-mono">IME ROLEPLAY POLICE DIVISION</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tactical Command Center & Multiview System</p>
                    </div>
                    <div class="flex items-center space-x-3 text-xs">
                        <Link href="/" class="text-slate-300 hover:text-white">Multiview</Link>
                        <span>•</span>
                        <Link href="/officers" class="text-slate-300 hover:text-white">Direktori</Link>
                        <span>•</span>
                        <Link href="/radio-codes" class="text-slate-300 hover:text-white">Kode 10</Link>
                        <span>•</span>
                        <Link href="/feedback" class="text-sky-400 hover:text-sky-300">Lapor / Usul</Link>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
