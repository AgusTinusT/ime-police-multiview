<script setup>
import { Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconSaver from "@/Components/Icons/gauge-low-svgrepo-com.svg";
import iconPlayAll from "@/Components/Icons/play-full-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";
import iconFullscreen from "@/Components/Icons/full-screen-svgrepo-com.svg";
import iconExitFullscreen from "@/Components/Icons/minimize-svgrepo-com.svg";

// Sub-components
import UserAccountMenu from "@/Components/UserAccountMenu.vue";

defineProps({
    isDataSaverEnabled: {
        type: Boolean,
        default: false,
    },
    isSyncingFeeds: {
        type: Boolean,
        default: false,
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

defineEmits([
    "enable-data-saver",
    "disable-data-saver",
    "trigger-sync",
    "open-quick-add",
    "toggle-fullscreen",
]);
</script>

<template>
    <!-- Tactical Header Bar -->
    <header
        class="bg-[#0b1320] border-b border-blue-900/40 px-3 sm:px-4 py-2 flex items-center justify-between gap-2 max-w-full sticky top-0 z-40 shadow-xl backdrop-blur-md"
    >
        <!-- Left Area: Branding & Standalone Page Navigation Links -->
        <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 shrink-0">
            <!-- Branding: IME Roleplay Police Division -->
            <div class="flex items-center space-x-2 shrink-0">
                <div
                    class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 shadow-inner p-1 overflow-hidden"
                >
                    <img
                        :src="logoSaspColor"
                        class="w-full h-full object-contain rounded"
                        alt="SASP Badge"
                    />
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-[11px] sm:text-xs font-black tracking-wider text-blue-400 uppercase leading-tight"
                        >IME ROLEPLAY</span
                    >
                    <span
                        class="text-[9px] sm:text-[10px] font-bold tracking-wide text-slate-300 uppercase leading-tight"
                        >POLICE DUTY</span
                    >
                </div>
            </div>

            <!-- Vertical Divider (Visible on tablet & desktop) -->
            <div class="h-5 w-px bg-slate-800/80 hidden md:block"></div>

            <!-- Page Navigation Links (Clean Minimalist Text Tabs - Visible on md: / iPad Air 4 & desktop) -->
            <nav
                class="hidden md:flex items-center space-x-0.5 lg:space-x-1 shrink-0"
            >
                <Link
                    href="/officers"
                    class="px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition shrink-0"
                    title="Officer Directory (LSPD, BCSO, SASP)"
                >
                    <span class="hidden xl:inline">Officer </span>Directory
                </Link>

                <Link
                    href="/about"
                    class="px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition shrink-0"
                    title="About Police Command Center"
                >
                    <span class="hidden xl:inline">About </span>Platform
                </Link>

                <Link
                    href="/faq"
                    class="px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition shrink-0"
                    title="Tactical FAQ Guide"
                >
                    FAQ
                </Link>

                <Link
                    href="/feedback"
                    class="px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-blue-300 hover:bg-blue-950/40 transition shrink-0"
                    title="Channel Requests & System Feedback"
                >
                    Feedback<span class="hidden xl:inline"> & Reports</span>
                </Link>
            </nav>
        </div>

        <!-- Right Area: In-Page Player & Stream Actions (Desktop / Tablet Toolbar) -->
        <div
            class="hidden md:flex items-center space-x-1 sm:space-x-1.5 shrink-0"
        >
            <!-- Label Badge for Actions Group -->
            <span
                class="hidden 2xl:inline-block text-[10px] font-mono text-slate-500 uppercase tracking-wider font-semibold mr-1"
                >Player Actions:</span
            >

            <!-- 1. Mode Switcher (Saver vs Play All) -->
            <div
                class="flex items-center h-8 bg-slate-950/90 rounded-lg p-0.5 border border-slate-800 shrink-0"
            >
                <button
                    @click="$emit('enable-data-saver')"
                    :class="
                        isDataSaverEnabled
                            ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="h-full px-2 sm:px-2.5 text-xs rounded transition flex items-center gap-1"
                    title="Mode Saver: Hold video playback to save bandwidth"
                >
                    <img
                        :src="iconSaver"
                        class="w-3.5 h-3.5 invert shrink-0"
                        alt="Saver"
                    />
                    <span class="hidden 2xl:inline">Saver</span>
                </button>
                <button
                    @click="$emit('disable-data-saver')"
                    :class="
                        !isDataSaverEnabled
                            ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="h-full px-2 sm:px-2.5 text-xs rounded transition flex items-center gap-1"
                    title="Play All: Play all video feeds simultaneously"
                >
                    <img
                        :src="iconPlayAll"
                        class="w-3 h-3 invert shrink-0"
                        alt="Play All"
                    />
                    <span class="hidden 2xl:inline">Play All</span>
                </button>
            </div>

            <!-- 2. Manual Sync Feeds Button -->
            <button
                @click="$emit('trigger-sync')"
                :disabled="isSyncingFeeds"
                class="h-8 px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700/80 transition flex items-center gap-1.5 disabled:opacity-50 shrink-0"
                title="Resynchronize live feeds from YouTube"
            >
                <img
                    :src="iconRefresh"
                    class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                    :class="{ 'animate-spin': isSyncingFeeds }"
                    alt="Sync"
                />
                <span class="hidden 2xl:inline">{{
                    isSyncingFeeds ? "Syncing..." : "Sync"
                }}</span>
            </button>

            <!-- 3. Quick Feed Button (In-Page Drawer Action with Emerald Accent) -->
            <button
                @click="$emit('open-quick-add')"
                class="h-8 px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-950/80 hover:bg-emerald-900/90 text-emerald-300 border border-emerald-500/40 transition flex items-center gap-1.5 shadow-sm shadow-emerald-950/50 shrink-0"
                title="Add External YouTube URL to CCTV Wall (In-Page Modal)"
            >
                <img
                    :src="iconQuickAdd"
                    class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                    alt="Quick Feed"
                />
                <span class="hidden 2xl:inline">Quick Feed</span>
            </button>

            <!-- 4. Fullscreen Button -->
            <button
                @click="$emit('toggle-fullscreen')"
                :class="
                    isFullscreen
                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/40 border-blue-400'
                        : 'bg-slate-900 text-slate-300 hover:bg-slate-800 border-slate-700/80'
                "
                class="h-8 px-2.5 py-1 text-xs font-bold rounded-lg border transition flex items-center gap-1.5 shrink-0"
                title="Toggle Mode Fullscreen CCTV Wall"
            >
                <img
                    :src="
                        isFullscreen ? iconExitFullscreen : iconFullscreen
                    "
                    class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                    alt="Fullscreen"
                />
                <span class="hidden 2xl:inline">{{
                    isFullscreen ? "Exit" : "Fullscreen"
                }}</span>
            </button>

            <!-- 5. User Account Sign In / Profile / Logout (YouTube-Style Dropdown) -->
            <UserAccountMenu />
        </div>

        <!-- Right Area Mobile Quick Action (< md) -->
        <div class="flex md:hidden items-center space-x-2 shrink-0">
            <button
                @click="$emit('open-quick-add')"
                class="p-2 rounded-lg bg-emerald-950/90 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/40 transition flex items-center justify-center shadow-md"
                title="Quick Add Feed"
            >
                <img
                    :src="iconQuickAdd"
                    class="w-4 h-4 invert opacity-95"
                    alt="Quick Add"
                />
            </button>

            <UserAccountMenu />
        </div>
    </header>
</template>
