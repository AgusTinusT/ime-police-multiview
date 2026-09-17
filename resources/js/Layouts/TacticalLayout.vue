<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import iconUser from '@/Components/Icons/user-svgrepo-com.svg';
import iconRadio from '@/Components/Icons/radio-signal-svgrepo-com.svg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconRoster from '@/Components/Icons/doc-svgrepo-com.svg';
import iconUrl from '@/Components/Icons/url-checker-svgrepo-com.svg';
import UserAccountMenu from '@/Components/UserAccountMenu.vue';
import TacticalFooter from '@/Components/TacticalFooter.vue';
import TacticalChatDrawer from '@/Components/TacticalChatDrawer.vue';

const isFullscreen = ref(false);
const isMobileMenuOpen = ref(false);

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
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-3 sm:px-4 py-2 flex items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Area: Branding & Standalone Page Navigation Links -->
            <div class="flex items-center space-x-3 shrink-0">
                <!-- Branding: IME Roleplay Police Division -->
                <Link href="/" class="flex items-center space-x-2.5 shrink-0 group">
                    <div class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 shadow-inner p-1 overflow-hidden group-hover:border-blue-400 transition">
                        <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[11px] sm:text-xs font-black tracking-wider text-blue-400 uppercase leading-tight group-hover:text-blue-300 transition">IME ROLEPLAY</span>
                        <span class="text-[9px] sm:text-[10px] font-bold tracking-wide text-slate-300 uppercase leading-tight">POLICE DIVISION</span>
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
                        href="/faq"
                        :class="[
                            'px-3 py-1.5 text-xs transition rounded-lg font-medium',
                            $page.url.startsWith('/faq') ? 'bg-blue-600/30 text-blue-300 font-bold border border-blue-500/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/70'
                        ]"
                        title="Pusat Bantuan & FAQ Taktis"
                    >
                        FAQ
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

            <!-- Right Controls: Return to Multiview & User Dropdown -->
            <div class="flex items-center space-x-2">
                <Link 
                    href="/" 
                    :class="[
                        'px-2.5 py-1.5 sm:px-3 sm:py-1.5 text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-md',
                        $page.url === '/' || $page.url.startsWith('/dashboard') ? 'bg-blue-600 text-white shadow-blue-600/30 border border-blue-400' : 'bg-slate-900 hover:bg-blue-600 text-slate-200 hover:text-white border border-slate-700 hover:border-blue-500'
                    ]"
                    title="Buka Halaman Utama CCTV Multiview"
                >
                    <img :src="iconFocus" class="w-3.5 h-3.5 invert" alt="" />
                    <span class="hidden sm:inline">CCTV Multiview</span>
                </Link>

                <!-- Mobile Menu Button (< md) -->
                <button 
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    class="md:hidden p-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white text-xs"
                    title="Navigasi Menu"
                >
                    <span class="text-sm">☰</span>
                </button>

                <!-- YouTube-Style User Account Dropdown -->
                <UserAccountMenu />
            </div>
        </header>

        <!-- Mobile Navigation Drawer (< md) -->
        <div v-if="isMobileMenuOpen" class="md:hidden bg-[#090f1a] border-b border-slate-800 px-4 py-3 space-y-2 animate-in slide-in-from-top-2">
            <div class="flex items-center justify-between pb-1 border-b border-slate-800/80 text-[11px] font-mono text-blue-400 font-bold">
                <span>NAVIGASI TAKTIS</span>
                <button @click="isMobileMenuOpen = false" class="text-slate-400">✕</button>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <Link 
                    href="/" 
                    @click="isMobileMenuOpen = false"
                    class="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 flex items-center gap-2"
                >
                    <img :src="iconFocus" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>Multiview</span>
                </Link>
                <Link 
                    href="/officers" 
                    @click="isMobileMenuOpen = false"
                    class="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 flex items-center gap-2"
                >
                    <img :src="iconRoster" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>Directory</span>
                </Link>
                <Link 
                    href="/faq" 
                    @click="isMobileMenuOpen = false"
                    class="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 flex items-center gap-2"
                >
                    <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>FAQ</span>
                </Link>
                <Link 
                    href="/about" 
                    @click="isMobileMenuOpen = false"
                    class="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 flex items-center gap-2"
                >
                    <img :src="iconUrl" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>About</span>
                </Link>
                <Link 
                    href="/feedback" 
                    @click="isMobileMenuOpen = false"
                    class="col-span-2 p-2 rounded-lg bg-sky-950/60 border border-sky-500/40 text-sky-300 flex items-center gap-2 font-bold"
                >
                    <img :src="iconFeedback" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>Feedback & Channel Requests</span>
                </Link>
            </div>
        </div>

        <!-- Dynamic Content Slot -->
        <div class="flex-1 flex flex-col">
            <slot />
        </div>

        <!-- Reusable Tactical Footer -->
        <TacticalFooter />

        <!-- Floating Tactical Community Chat -->
        <TacticalChatDrawer />

    </div>
</template>
