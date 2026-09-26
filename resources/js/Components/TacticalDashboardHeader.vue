<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";

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

const page = usePage();
const isUrlActive = (path) => {
    const url = page.url || "";
    if (path === "/") {
        return (
            url === "/" || url === "/dashboard" || url.startsWith("/dashboard?")
        );
    }
    return url.startsWith(path);
};
</script>

<template>
    <!-- Tactical Header Bar -->
    <header
        class="bg-[#0b1320] border-b border-blue-900/40 px-3 sm:px-4 lg:px-8 py-3.5 sm:py-4 sticky top-0 z-40 shadow-xl backdrop-blur-md"
    >
        <div
            class="max-w-screen-2xl mx-auto w-full flex items-center justify-between gap-2"
        >
            <!-- Left Area: Branding & Standalone Page Navigation Links -->
            <div
                class="flex items-center space-x-2 sm:space-x-3 min-w-0 shrink-0"
            >
                <!-- Branding: IME Roleplay Police Division (Navigates to Dashboard) -->
                <Link
                    href="/"
                    class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                    title="Halaman Beranda Dashboard"
                >
                    <div
                        class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 group-hover:border-blue-400 shadow-inner p-1 overflow-hidden transition"
                    >
                        <img
                            :src="logoSaspColor"
                            class="w-full h-full object-contain rounded"
                            alt="SASP Badge"
                        />
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-[11px] sm:text-xs font-black tracking-wider text-blue-400 group-hover:text-blue-300 uppercase leading-tight transition"
                            >IME ROLEPLAY</span
                        >
                        <span
                            class="text-[9px] sm:text-[10px] font-bold tracking-wide text-slate-300 group-hover:text-white uppercase leading-tight transition"
                            >POLICE DUTY</span
                        >
                    </div>
                </Link>

                <!-- Vertical Divider (Visible on tablet & desktop) -->
                <div class="h-5 w-px bg-slate-800/80 hidden md:block"></div>

                <!-- Page Navigation Links with Dynamic Active State -->
                <nav
                    class="hidden md:flex items-center space-x-0.5 lg:space-x-1 shrink-0"
                >
                    <Link
                        href="/"
                        :class="[
                            'px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-bold rounded-lg transition shrink-0',
                            isUrlActive('/')
                                ? 'text-blue-400 bg-blue-950/60 border border-blue-500/40 shadow'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800/70',
                        ]"
                        title="Home Discovery Hub"
                    >
                        <span>Home</span>
                    </Link>

                    <Link
                        href="/multiview"
                        :class="[
                            'px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-bold rounded-lg transition shrink-0 flex items-center gap-1.5',
                            isUrlActive('/multiview')
                                ? 'text-blue-400 bg-blue-950/60 border border-blue-500/40 shadow'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800/70',
                        ]"
                        title="Stand-alone Multiview Stage"
                    >
                        <span>Multiview</span>
                    </Link>

                    <Link
                        href="/officers"
                        :class="[
                            'px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg transition shrink-0',
                            isUrlActive('/officers')
                                ? 'text-blue-400 bg-blue-950/60 border border-blue-500/40 font-bold shadow'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800/70',
                        ]"
                        title="Officers"
                    >
                        <span class="hidden xl:inline">Officers </span>
                    </Link>

                    <Link
                        href="/about"
                        :class="[
                            'px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg transition shrink-0',
                            isUrlActive('/about')
                                ? 'text-blue-400 bg-blue-950/60 border border-blue-500/40 font-bold shadow'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800/70',
                        ]"
                        title="About"
                    >
                        <span class="hidden xl:inline">About </span>
                    </Link>

                    <Link
                        href="/faq"
                        :class="[
                            'px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg transition shrink-0',
                            isUrlActive('/faq')
                                ? 'text-blue-400 bg-blue-950/60 border border-blue-500/40 font-bold shadow'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800/70',
                        ]"
                        title="FAQ"
                    >
                        FAQ
                    </Link>

                    <Link
                        href="/feedback"
                        :class="[
                            'px-2 py-1 lg:px-3 lg:py-1.5 text-xs font-medium rounded-lg transition shrink-0',
                            isUrlActive('/feedback')
                                ? 'text-blue-400 bg-blue-950/60 border border-blue-500/40 font-bold shadow'
                                : 'text-slate-300 hover:text-blue-300 hover:bg-slate-800/70',
                        ]"
                        title="Feedback"
                    >
                        Feedback
                    </Link>
                </nav>
            </div>

            <!-- Right Area: Stream Controls & Account Menu (Desktop / Tablet Toolbar) -->
            <div class="hidden md:flex items-center space-x-2 shrink-0">
                <!-- Quick Feed Button -->
                <button
                    @click="$emit('open-quick-add')"
                    class="h-8 px-2.5 text-xs font-bold rounded-lg bg-emerald-950/80 hover:bg-emerald-900/90 text-emerald-300 border border-emerald-500/40 transition flex items-center gap-1.5 shadow-sm shadow-emerald-950/50 shrink-0 cursor-pointer"
                    title="Add External YouTube URL to CCTV Wall"
                >
                    <img
                        :src="iconQuickAdd"
                        class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                        alt="Quick Feed"
                    />
                    <span class="hidden sm:inline leading-none">Quick Feed</span>
                </button>

                <!-- User Account Sign In / Profile / Logout -->
                <UserAccountMenu />
            </div>

            <!-- Right Area Mobile Quick Action (< md) -->
            <div class="flex md:hidden items-center space-x-2 shrink-0">
                <button
                    @click="$emit('open-quick-add')"
                    class="h-8 w-8 rounded-lg bg-emerald-950/90 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/40 transition flex items-center justify-center shadow-md cursor-pointer shrink-0"
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
        </div>
    </header>
</template>
