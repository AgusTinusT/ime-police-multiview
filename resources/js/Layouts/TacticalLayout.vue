<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

import logoSaspColor from '@/Components/Icons/SASP256.jpg';
import iconUser from '@/Components/Icons/user-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';

const isFullscreen = ref(false);

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => console.warn(err));
        isFullscreen.value = true;
    } else {
        document.exitFullscreen().catch(err => console.warn(err));
        isFullscreen.value = false;
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <div class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased">
        
        <!-- Persistent Tactical Header Bar -->
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
                        :class="[
                            'px-3 py-1.5 text-xs transition rounded-lg font-medium',
                            $page.url.startsWith('/officers') ? 'bg-blue-600/30 text-blue-300 font-bold border border-blue-500/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/70'
                        ]"
                        title="Officer Directory (LSPD, BCSO, SASP)"
                    >
                        Officer Directory
                    </Link>

                    <Link 
                        href="/radio-codes"
                        :class="[
                            'px-3 py-1.5 text-xs transition rounded-lg font-medium',
                            $page.url.startsWith('/radio-codes') ? 'bg-blue-600/30 text-blue-300 font-bold border border-blue-500/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/70'
                        ]"
                        title="10-Codes & Tactical Radio Channels (TAC 1-10)"
                    >
                        10-Codes & Radio
                    </Link>

                    <Link 
                        href="/about"
                        :class="[
                            'px-3 py-1.5 text-xs transition rounded-lg font-medium',
                            $page.url.startsWith('/about') ? 'bg-indigo-600/30 text-indigo-300 font-bold border border-indigo-500/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/70'
                        ]"
                        title="About Police Command Center"
                    >
                        About Platform
                    </Link>

                    <Link 
                        href="/feedback"
                        :class="[
                            'px-3 py-1.5 text-xs transition rounded-lg font-medium',
                            $page.url.startsWith('/feedback') ? 'bg-sky-600/30 text-sky-300 font-bold border border-sky-500/50' : 'text-slate-300 hover:text-sky-300 hover:bg-sky-950/40'
                        ]"
                        title="Channel Requests & System Feedback"
                    >
                        Feedback & Reports
                    </Link>
                </nav>
            </div>

            <!-- Right Controls: Return to Multiview -->
            <div class="flex items-center space-x-2">
                <Link 
                    href="/" 
                    :class="[
                        'px-3 py-1.5 text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-md',
                        $page.url === '/' || $page.url.startsWith('/dashboard') ? 'bg-blue-600 text-white shadow-blue-600/30 border border-blue-400' : 'bg-slate-900 hover:bg-blue-600 text-slate-200 hover:text-white border border-slate-700 hover:border-blue-500'
                    ]"
                    title="Buka Halaman Utama CCTV Multiview"
                >
                    <img :src="iconFocus" class="w-3.5 h-3.5 invert" alt="" />
                    <span>CCTV Multiview</span>
                </Link>
            </div>
        </header>

        <!-- Dynamic Content Slot -->
        <slot />

    </div>
</template>
