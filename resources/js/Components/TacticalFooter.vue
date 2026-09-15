<script setup>
import { Link } from '@inertiajs/vue3';
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconRoster from '@/Components/Icons/doc-svgrepo-com.svg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconUrl from '@/Components/Icons/url-checker-svgrepo-com.svg';
</script>

<template>
    <footer class="mt-auto bg-[#080d16] border-t border-blue-900/40 text-slate-300 font-sans pt-8 pb-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Top Footer Grid: Branding, Quick Navigation & System Status -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pb-6 border-b border-slate-800/80">
                
                <!-- Branding & Community Info (Cols 1-5) -->
                <div class="md:col-span-5 space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-950/60 via-slate-900 to-slate-950 border border-blue-500/40 p-1 shadow-inner overflow-hidden shrink-0">
                            <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                        </div>
                        <div>
                            <span class="text-sm font-black tracking-wider text-blue-400 uppercase block leading-tight">IME ROLEPLAY</span>
                            <span class="text-xs font-bold tracking-wide text-slate-300 uppercase block leading-tight">POLICE DUTY MULTIVIEW</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-md font-mono">
                        Portal resmi pemantauan CCTV bodycam petugas kepolisian (LSPD, BCSO, SASP, SAPR) untuk komunitas IME Roleplay Indonesia.
                    </p>
                    <div class="flex items-center space-x-2 pt-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>🟢 SASP Satellite Systems Nominal</span>
                        </span>
                    </div>
                </div>

                <!-- Navigation Quick Links (Cols 6-8) -->
                <div class="md:col-span-4 space-y-2">
                    <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-blue-400">Navigasi Platform</h4>
                    <ul class="grid grid-cols-2 gap-2 text-xs font-medium">
                        <li>
                            <Link href="/" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 py-1">
                                <img :src="iconFocus" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <span>CCTV Multiview</span>
                            </Link>
                        </li>
                        <li>
                            <Link href="/officers" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 py-1">
                                <img :src="iconRoster" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <span>Directory Officer</span>
                            </Link>
                        </li>
                        <li>
                            <Link href="/qna" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 py-1">
                                <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <span>QnA & FAQ</span>
                            </Link>
                        </li>
                        <li>
                            <Link href="/about" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 py-1">
                                <img :src="iconUrl" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <span>About Platform</span>
                            </Link>
                        </li>
                        <li class="col-span-2">
                            <Link href="/feedback" class="text-sky-400 hover:text-sky-300 transition flex items-center gap-1.5 py-1">
                                <img :src="iconFeedback" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                <span>Channel Requests & Feedback</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Legal / Roleplay Disclaimer (Cols 9-12) -->
                <div class="md:col-span-3 space-y-2">
                    <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">Disklaimer Komunitas</h4>
                    <p class="text-[11px] text-slate-500 leading-normal">
                        Platform fan-made independen non-komersial. Seluruh konten siaran langsung dan rekaman merupakan hak cipta masing-masing streamer GTA V Roleplay.
                    </p>
                </div>
            </div>

            <!-- Bottom Copyright & Developer Credits Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 pt-2">
                <p class="font-medium text-slate-300">
                    © {{ new Date().getFullYear() }} IME Roleplay Community — SASP Police Duty Multiview
                </p>
                <p class="text-[11px] text-slate-400 font-mono">
                    Developed with ❤️ by <span class="text-blue-400 font-bold">Gusti Aidan (Agus Tinus Turnip)</span>
                </p>
            </div>
        </div>
    </footer>
</template>
