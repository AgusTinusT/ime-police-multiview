<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";

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
        class="bg-slate-950 border-b border-slate-800 px-3 sm:px-4 lg:px-8 py-3 sticky top-0 z-40"
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
                        class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-md bg-slate-900 border border-slate-800 group-hover:border-slate-700 p-1 overflow-hidden transition"
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
                <div class="h-5 w-px bg-slate-800 hidden md:block"></div>

                <!-- Page Navigation Links with Dynamic Active State -->
                <nav class="hidden md:flex items-center space-x-1 shrink-0">
                    <Link
                        href="/"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-md transition shrink-0',
                            isUrlActive('/')
                                ? 'text-white bg-blue-600'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Home Discovery Hub"
                    >
                        <span>Home</span>
                    </Link>

                    <Link
                        href="/multiview"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-md transition shrink-0 flex items-center gap-1.5',
                            isUrlActive('/multiview')
                                ? 'text-white bg-blue-600'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Stand-alone Multiview Stage"
                    >
                        <span>Multiview</span>
                    </Link>

                    <Link
                        href="/action-clips"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/action-clips') ||
                            isUrlActive('/clips')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="TacClips — Kumpulan Klip Momen Aksi Kepolisian"
                    >
                        <span>TacClips</span>
                    </Link>

                    <Link
                        href="/officers"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/officers')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Officers"
                    >
                        <span>Officers</span>
                    </Link>

                    <Link
                        href="/about"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/about')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="About"
                    >
                        <span>About</span>
                    </Link>

                    <Link
                        href="/faq"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/faq')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="FAQ"
                    >
                        FAQ
                    </Link>

                    <Link
                        href="/feedback"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/feedback')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Feedback"
                    >
                        Feedback
                    </Link>
                </nav>
            </div>

            <!-- Right Area: Stream Controls & Account Menu (Desktop / Tablet Toolbar) -->
            <div class="hidden md:flex items-center space-x-2 shrink-0">
                <!-- User Account Sign In / Profile / Logout -->
                <UserAccountMenu />
            </div>

            <!-- Right Area Mobile Quick Action (< md) -->
            <div class="flex md:hidden items-center space-x-2 shrink-0">
                <UserAccountMenu />
            </div>
        </div>
    </header>
</template>
