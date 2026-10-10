<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";

// Sub-components
import UserAccountMenu from "@/Components/UserAccountMenu.vue";
import TacticalMobileBottomNav from "@/Components/TacticalMobileBottomNav.vue";


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

const emit = defineEmits([
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

// Bottom Sheet Mobile Drawer State
const isBottomSheetOpen = ref(false);

const closeBottomSheet = () => {
    isBottomSheetOpen.value = false;
    document.body.style.overflow = "";
};

const handleKeyDown = (e) => {
    if (e.key === "Escape" && isBottomSheetOpen.value) {
        closeBottomSheet();
    }
};

const handleResize = () => {
    if (window.innerWidth >= 768 && isBottomSheetOpen.value) {
        closeBottomSheet();
    }
};

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyDown);
    window.removeEventListener("resize", handleResize);
    document.body.style.overflow = "";
});
</script>

<template>
    <!-- 1. TOP HEADER (DESKTOP FULL & MOBILE COMPACT) -->
    <header
        class="bg-tactical-base/90 backdrop-blur-md border-b border-tactical-border px-3 sm:px-4 lg:px-8 py-2.5 sm:py-3 sticky top-0 z-40"
    >
        <div
            class="max-w-screen-2xl mx-auto w-full flex items-center justify-between gap-2"
        >
            <!-- Left Area: Branding & Logo -->
            <div
                class="flex items-center space-x-2 sm:space-x-3 min-w-0 shrink-0"
            >
                <Link
                    href="/"
                    class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                    title="Halaman Beranda Dashboard"
                >
                    <div
                        class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-md bg-tactical-panel border border-tactical-border group-hover:border-tactical-border-light p-1 overflow-hidden transition"
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
                        >
                            IME ROLEPLAY
                        </span>
                        <span
                            class="text-[9px] sm:text-[10px] font-bold tracking-wide text-slate-300 group-hover:text-white uppercase leading-tight transition"
                        >
                            POLICE DUTY
                        </span>
                    </div>
                </Link>

                <!-- Vertical Divider (Visible on desktop) -->
                <div class="h-5 w-px bg-tactical-border hidden md:block"></div>

                <!-- Desktop Navigation Links (>= md) -->
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
                        <span>{{ $t("nav.dashboard") }}</span>
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
                        <span>{{ $t("nav.multiview") }}</span>
                    </Link>

                    <!-- Clip Studio Feature Link -->
                    <Link
                        href="/clipper"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0 flex items-center gap-1.5',
                            isUrlActive('/clipper')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Clip Studio"
                    >
                        <span>Clip Studio</span>
                        <span class="px-1.5 py-0.2 text-[9px] font-mono font-extrabold rounded bg-emerald-950 text-emerald-400 border border-emerald-800/80 shrink-0">BETA</span>
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
                        <span>{{ $t("nav.clips") }}</span>
                    </Link>

                    <Link
                        href="/officers"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/officers')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Direktori Perwira Kepolisian"
                    >
                        <span>{{ $t("nav.directory") }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Right Area: User Account Menu -->
            <div class="flex items-center space-x-2 shrink-0">
                <UserAccountMenu />
            </div>
        </div>
    </header>

    <!-- 2. MOBILE BOTTOM NAVIGATION BAR & BOTTOM SHEET DRAWER -->
    <TacticalMobileBottomNav />
</template>
