<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";
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
        class="bg-background/90 backdrop-blur-md border-b border-border px-3 sm:px-4 lg:px-8 py-2.5 sm:py-3 sticky top-0 z-40"
    >
        <div
            class="max-w-(--breakpoint-2xl) mx-auto w-full flex items-center justify-between gap-2"
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
                        class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-md bg-card border border-border group-hover:border-primary/50 p-1 overflow-hidden transition"
                    >
                        <img
                            :src="logoSaspColor"
                            class="w-full h-full object-contain rounded-sm"
                            alt="SASP Badge"
                        />
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-xs font-extrabold tracking-wider text-primary group-hover:text-primary/80 uppercase leading-tight transition"
                        >
                            IME ROLEPLAY
                        </span>
                        <span
                            class="text-xs font-bold tracking-wide text-foreground/80 group-hover:text-foreground uppercase leading-tight transition"
                        >
                            POLICE DUTY
                        </span>
                    </div>
                </Link>

                <!-- Vertical Divider (Visible on desktop) -->
                <div class="h-5 w-px bg-border hidden md:block"></div>

                <!-- Desktop Navigation Links (>= md) -->
                <nav class="hidden md:flex items-center space-x-1 shrink-0">
                    <Link
                        href="/"
                        :class="
                            cn(
                                'px-3 py-1.5 text-xs font-bold rounded-md transition shrink-0',
                                isUrlActive('/')
                                    ? 'text-primary-foreground bg-primary'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                            )
                        "
                        title="Home Discovery Hub"
                    >
                        <span>{{ $t("nav.dashboard") }}</span>
                    </Link>

                    <Link
                        href="/multiview"
                        :class="
                            cn(
                                'px-3 py-1.5 text-xs font-bold rounded-md transition shrink-0 flex items-center gap-1.5',
                                isUrlActive('/multiview')
                                    ? 'text-primary-foreground bg-primary'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                            )
                        "
                        title="Stand-alone Multiview Stage"
                    >
                        <span>{{ $t("nav.multiview") }}</span>
                    </Link>

                    <!-- Clip Studio Feature Link -->
                    <Link
                        href="/clipper"
                        :class="
                            cn(
                                'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0 flex items-center gap-1.5',
                                isUrlActive('/clipper')
                                    ? 'text-primary-foreground bg-primary font-bold'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                            )
                        "
                        title="Clip Studio"
                    >
                        <span>Clip Studio</span>
                        <Badge variant="success" class="px-1.5 py-0 font-mono font-extrabold">
                            BETA
                        </Badge>
                    </Link>

                    <Link
                        href="/action-clips"
                        :class="
                            cn(
                                'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                                isUrlActive('/action-clips') || isUrlActive('/clips')
                                    ? 'text-primary-foreground bg-primary font-bold'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                            )
                        "
                        title="TacClips — Kumpulan Klip Momen Aksi Kepolisian"
                    >
                        <span>{{ $t("nav.clips") }}</span>
                    </Link>

                    <Link
                        href="/officers"
                        :class="
                            cn(
                                'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                                isUrlActive('/officers')
                                    ? 'text-primary-foreground bg-primary font-bold'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                            )
                        "
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
