<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import { Badge } from "@/Components/ui/badge";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    ChevronDown,
    CircleQuestionMark,
    Clapperboard,
    Ellipsis,
    Heart,
    House,
    Info,
    LayoutGrid,
    Link2,
    Megaphone,
    MessageSquareWarning,
    Scissors,
    Users,
} from "@lucide/vue";
import { cn } from "@/lib/utils";
import UserAccountMenu from "@/Components/UserAccountMenu.vue";
import TacticalMobileBottomNav from "@/Components/TacticalMobileBottomNav.vue";

const props = defineProps({
    // Dashboard billboard: blend the header into the hero until the user scrolls.
    transparentAtTop: {
        type: Boolean,
        default: false,
    },
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

// "More" menu: secondary pages. /feedback is pinned on top as the report entry.
const moreLinks = [
    { href: "/updates", label: "nav.updates", icon: Megaphone },
    { href: "/faq", label: "nav.faq", icon: CircleQuestionMark },
    { href: "/about", label: "nav.about", icon: Info },
    { href: "/bio-link", label: "nav.bio_link", icon: Link2 },
];
const moreActive = computed(() =>
    ["/feedback", ...moreLinks.map((l) => l.href)].some((href) => isUrlActive(href)),
);

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

const isScrolled = ref(false);
const handleScroll = () => {
    // Low threshold: the flat header is transparent, so morph before content slides under it.
    isScrolled.value = window.scrollY > 8;
};

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("resize", handleResize);
    // Every page: flat header at the top, floating pill once the page scrolls.
    handleScroll();
    window.addEventListener("scroll", handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyDown);
    window.removeEventListener("resize", handleResize);
    window.removeEventListener("scroll", handleScroll);
    document.body.style.overflow = "";
});
</script>

<template>
    <!-- 1. TOP HEADER: flat and full-width at the top of the page, turns into a
         floating pill inside a page-width container once the page scrolls.
         Total height is fixed (h-16 / sm:h-[76px]) in both states so pages can
         offset by it and nothing jumps when it morphs. -->
    <header
        :class="cn(
            'pointer-events-none sticky top-0 z-40 px-2 pt-2 transition-colors duration-300 sm:px-4 sm:pt-3 lg:px-8',
            // Keep nav legible over the dashboard hero image while flat.
            transparentAtTop && !isScrolled && 'bg-linear-to-b from-background/80 to-transparent',
        )"
    >
        <div
            :class="cn(
                'pointer-events-auto mx-auto flex h-14 w-full max-w-(--breakpoint-2xl) items-center justify-between gap-2 border pl-2 pr-2 transition-all duration-300 sm:h-16 sm:pl-3 sm:pr-3',
                isScrolled
                    ? 'rounded-full border-border bg-background/80 shadow-lg shadow-black/30 backdrop-blur-xl'
                    : 'rounded-none border-transparent bg-transparent shadow-none',
            )"
        >
            <!-- Left Area: Branding & Logo -->
            <div
                class="flex items-center space-x-2 sm:space-x-3 min-w-0 shrink-0"
            >
                <Link
                    href="/"
                    class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                    :title="$t('nav.dashboard')"
                    :aria-label="$t('nav.dashboard')"
                >
                    <div
                        class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-card border border-border group-hover:border-primary/50 p-1 overflow-hidden transition"
                    >
                        <img
                            :src="logoSaspColor"
                            class="w-full h-full object-contain rounded-full"
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
                <nav class="hidden md:flex items-center gap-1 shrink-0">
                    <Link
                        href="/"
                        :class="
                            cn(
                                'hidden xl:flex px-3.5 py-2 text-sm font-medium rounded-full transition shrink-0 items-center gap-1.5',
                                isUrlActive('/')
                                    ? 'text-primary bg-primary/15 ring-1 ring-primary/60'
                                    : 'text-foreground/80 hover:text-foreground hover:bg-foreground/10',
                            )
                        "
                        :title="$t('nav.dashboard')"
                    >
                        <House class="size-4" />
                        <span>{{ $t("nav.dashboard") }}</span>
                    </Link>

                    <Link
                        href="/multiview"
                        :class="
                            cn(
                                'px-2.5 py-1.5 text-xs lg:px-3.5 lg:py-2 lg:text-sm font-medium rounded-full transition shrink-0 flex items-center gap-1.5',
                                isUrlActive('/multiview')
                                    ? 'text-primary bg-primary/15 ring-1 ring-primary/60'
                                    : 'text-foreground/80 hover:text-foreground hover:bg-foreground/10',
                            )
                        "
                        :title="$t('nav.multiview')"
                    >
                        <LayoutGrid class="hidden size-4 lg:block" />
                        <span>{{ $t("nav.multiview") }}</span>
                    </Link>

                    <Link
                        href="/action-clips"
                        :class="
                            cn(
                                'px-2.5 py-1.5 text-xs lg:px-3.5 lg:py-2 lg:text-sm font-medium rounded-full transition shrink-0 flex items-center gap-1.5',
                                isUrlActive('/action-clips') || isUrlActive('/clips')
                                    ? 'text-primary bg-primary/15 ring-1 ring-primary/60 font-bold'
                                    : 'text-foreground/80 hover:text-foreground hover:bg-foreground/10',
                            )
                        "
                        :title="$t('nav.clips')"
                    >
                        <Clapperboard class="hidden size-4 lg:block" />
                        <span>{{ $t("nav.clips") }}</span>
                    </Link>

                    <!-- Dropdown Menu Perwira (Direktori + Ranking) -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                :class="
                                    cn(
                                        'px-2.5 py-1.5 text-xs lg:px-3.5 lg:py-2 lg:text-sm font-medium rounded-full transition shrink-0 flex items-center gap-1 cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                                        isUrlActive('/officers') || isUrlActive('/ranking')
                                            ? 'text-primary bg-primary/15 ring-1 ring-primary/60 font-bold'
                                            : 'text-foreground/80 hover:text-foreground hover:bg-foreground/10',
                                    )
                                "
                                :title="$t('nav.officers')"
                            >
                                <Users class="hidden size-4 lg:block" />
                                <span>{{ $t("nav.officers") }}</span>
                                <ChevronDown class="size-3.5 opacity-70" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="w-48 font-sans">
                            <DropdownMenuItem as-child>
                                <Link
                                    href="/officers"
                                    class="w-full cursor-pointer flex items-center justify-between"
                                >
                                    <span>{{ $t("nav.directory") }}</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link
                                    href="/ranking"
                                    class="w-full cursor-pointer flex items-center justify-between"
                                >
                                    <span>{{ $t("nav.ranking") }}</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <!-- Clip Studio Feature Link -->
                    <Link
                        href="/clipper"
                        :class="
                            cn(
                                'px-2.5 py-1.5 text-xs lg:px-3.5 lg:py-2 lg:text-sm font-medium rounded-full transition shrink-0 flex items-center gap-1.5',
                                isUrlActive('/clipper')
                                    ? 'text-primary bg-primary/15 ring-1 ring-primary/60 font-bold'
                                    : 'text-foreground/80 hover:text-foreground hover:bg-foreground/10',
                            )
                        "
                        :title="$t('nav.clip_studio')"
                    >
                        <Scissors class="hidden size-4 lg:block" />
                        <span>{{ $t("nav.clip_studio") }}</span>
                        <Badge variant="success" class="px-1.5 py-0 font-mono font-extrabold">
                            BETA
                        </Badge>
                    </Link>

                    <!-- More: secondary pages (report/feedback, updates, help) -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                :class="
                                    cn(
                                        'px-2.5 py-1.5 text-xs lg:px-3.5 lg:py-2 lg:text-sm font-medium rounded-full transition shrink-0 flex items-center gap-1 cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                                        moreActive
                                            ? 'text-primary bg-primary/15 ring-1 ring-primary/60 font-bold'
                                            : 'text-foreground/80 hover:text-foreground hover:bg-foreground/10',
                                    )
                                "
                                :title="$t('nav.more')"
                            >
                                <Ellipsis class="hidden size-4 lg:block" />
                                <span>{{ $t("nav.more") }}</span>
                                <ChevronDown class="size-3.5 opacity-70" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            :side-offset="10"
                            class="w-64 rounded-2xl border-foreground/10 bg-popover/95 p-2 font-sans backdrop-blur-xl"
                        >
                            <DropdownMenuItem as-child class="p-0">
                                <Link
                                    href="/feedback"
                                    class="mb-1 flex w-full cursor-pointer items-center gap-3 rounded-xl border border-primary/40 bg-primary/10 px-3 py-2.5 text-primary focus:bg-primary/20"
                                >
                                    <MessageSquareWarning class="size-4 shrink-0" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold">{{ $t("nav.feedback") }}</p>
                                        <p class="truncate text-xs font-normal text-primary/80">{{ $t("nav.feedback_hint") }}</p>
                                    </div>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem v-for="item in moreLinks" :key="item.href" as-child class="p-0">
                                <Link
                                    :href="item.href"
                                    :class="cn(
                                        'flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium',
                                        isUrlActive(item.href) ? 'text-primary' : 'text-foreground/90',
                                    )"
                                >
                                    <component :is="item.icon" class="size-4 shrink-0 text-muted-foreground" />
                                    <span>{{ $t(item.label) }}</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </nav>
            </div>

            <!-- Right Area: Donate + User Account Menu -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Donate: icon-only on tablets, labelled from lg; phones get it in the account menu -->
                <Link
                    href="/dukung"
                    class="hidden h-10 shrink-0 items-center gap-1.5 rounded-full border border-live/40 bg-live/15 px-3 text-sm font-semibold text-live transition-colors hover:bg-live/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:flex"
                    :title="$t('donate.title')"
                    :aria-label="$t('donate.title')"
                >
                    <Heart class="size-4 shrink-0 fill-current" />
                    <span class="hidden lg:inline">{{ $t("donate.short") }}</span>
                </Link>
                <UserAccountMenu />
            </div>
        </div>
    </header>

    <!-- 2. MOBILE BOTTOM NAVIGATION BAR & BOTTOM SHEET DRAWER -->
    <TacticalMobileBottomNav />
</template>
