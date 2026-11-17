<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { Badge } from "@/Components/ui/badge";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/Components/ui/sheet";
import { cn } from "@/lib/utils";
import {
    ChevronRight,
    Ellipsis,
    FileText,
    HelpCircle,
    House,
    Info,
    LayoutGrid,
    Link2,
    MessageSquare,
    Scissors,
    Sparkles,
    Trophy,
    Users,
    Video,
} from "@lucide/vue";

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

const openBottomSheet = () => {
    isBottomSheetOpen.value = true;
};

const closeBottomSheet = () => {
    isBottomSheetOpen.value = false;
};

const handleResize = () => {
    if (window.innerWidth >= 768 && isBottomSheetOpen.value) {
        closeBottomSheet();
    }
};

onMounted(() => {
    window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
});
</script>

<template>
    <!-- MOBILE BOTTOM NAVIGATION BAR (FIXED BOTTOM, HIDDEN ON DESKTOP) -->
    <nav
        aria-label="Navigasi Bawah Mobile Taktis"
        class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-background/95 backdrop-blur-lg border-t border-border pb-safe select-none"
    >
        <div class="max-w-md mx-auto px-2">
            <div class="flex items-center justify-around min-h-14">
                <!-- TAB 1: HOME -->
                <Link
                    href="/"
                    :class="
                        cn(
                            'flex-1 min-h-12 flex flex-col items-center justify-center py-1.5 transition-colors',
                            isUrlActive('/')
                                ? 'text-primary font-bold'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                >
                    <div class="relative p-0.5">
                        <House class="size-5" />
                        <span
                            v-if="isUrlActive('/')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-primary"
                        ></span>
                    </div>
                    <span class="text-xs tracking-tight mt-0.5">{{ $t("nav.dashboard") }}</span>
                </Link>

                <!-- TAB 2: MULTIVIEW -->
                <Link
                    href="/multiview"
                    :class="
                        cn(
                            'flex-1 min-h-12 flex flex-col items-center justify-center py-1.5 transition-colors',
                            isUrlActive('/multiview')
                                ? 'text-primary font-bold'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                >
                    <div class="relative p-0.5">
                        <LayoutGrid class="size-5" />
                        <span
                            v-if="isUrlActive('/multiview')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-primary"
                        ></span>
                    </div>
                    <span class="text-xs tracking-tight mt-0.5">{{ $t("nav.multiview") }}</span>
                </Link>

                <!-- TAB 3: TACCLIPS -->
                <Link
                    href="/action-clips"
                    :class="
                        cn(
                            'flex-1 min-h-12 flex flex-col items-center justify-center py-1.5 transition-colors',
                            isUrlActive('/action-clips') || isUrlActive('/clips')
                                ? 'text-primary font-bold'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                >
                    <div class="relative p-0.5">
                        <Video class="size-5" />
                        <span
                            v-if="isUrlActive('/action-clips') || isUrlActive('/clips')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-primary"
                        ></span>
                    </div>
                    <span class="text-xs tracking-tight mt-0.5">{{ $t("nav.clips") }}</span>
                </Link>

                <!-- TAB 4: PERWIRA (OFFICERS & RANKING) -->
                <Link
                    href="/officers"
                    :class="
                        cn(
                            'flex-1 min-h-12 flex flex-col items-center justify-center py-1.5 transition-colors',
                            isUrlActive('/officers') || isUrlActive('/ranking')
                                ? 'text-primary font-bold'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                >
                    <div class="relative p-0.5">
                        <Users class="size-5" />
                        <span
                            v-if="isUrlActive('/officers') || isUrlActive('/ranking')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-primary"
                        ></span>
                    </div>
                    <span class="text-xs tracking-tight mt-0.5">{{ $t("nav.officers") }}</span>
                </Link>

                <!-- TAB 5: LAINNYA (MORE / BOTTOM SHEET TRIGGER) -->
                <button
                    type="button"
                    :aria-label="$t('nav.more')"
                    @click="openBottomSheet"
                    class="flex-1 min-h-12 flex flex-col items-center justify-center py-1.5 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                >
                    <div class="relative p-0.5">
                        <Ellipsis class="size-5" />
                    </div>
                    <span class="text-xs tracking-tight mt-0.5">{{ $t("nav.more") }}</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- BOTTOM SHEET MODAL (SLIDE UP FOR SECONDARY NAV & QUICK ACCESS) -->
    <Sheet v-model:open="isBottomSheetOpen">
        <SheetContent
            side="bottom"
            class="md:hidden rounded-t-2xl max-h-[85vh] overflow-y-auto p-0"
        >
            <SheetHeader class="px-5 pt-5 pb-3 border-b border-border text-left">
                <SheetTitle class="font-bold text-foreground text-sm tracking-wide uppercase font-mono">
                    Navigasi Taktis
                </SheetTitle>
                <SheetDescription class="text-xs text-muted-foreground">
                    Pilih menu atau informasi platform
                </SheetDescription>
            </SheetHeader>

            <div class="p-5 space-y-5 pb-8">
                <!-- Navigasi Tambahan List -->
                <div class="space-y-1">
                    <Link
                        href="/clipper"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/clipper')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Scissors class="size-4 text-primary" />
                            <div class="flex items-center gap-2">
                                <span>{{ $t("nav.clip_studio") }}</span>
                                <Badge variant="success" class="font-mono font-bold">
                                    BETA
                                </Badge>
                            </div>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>

                    <Link
                        href="/ranking"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/ranking')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Trophy class="size-4 text-primary" />
                            <span>{{ $t("nav.ranking") }}</span>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>

                    <!-- Bio-link saya (hanya jika officer) -->
                    <Link
                        v-if="$page.props.auth?.user?.is_officer"
                        href="/dashboard/bio-links"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/dashboard/bio-links')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Link2 class="size-4 text-primary" />
                            <span>{{ $t("nav.my_biolink") }}</span>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>
                </div>

                <!-- Separator -->
                <div class="h-px bg-border my-2" />

                <!-- Informasi & Bantuan List -->
                <div class="space-y-1">
                    <Link
                        href="/updates"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/updates')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground/80 hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Sparkles class="size-4 text-muted-foreground" />
                            <span>{{ $t("nav.updates") }}</span>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>

                    <Link
                        href="/about"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/about')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground/80 hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Info class="size-4 text-muted-foreground" />
                            <span>{{ $t("nav.about") }}</span>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>

                    <Link
                        href="/faq"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/faq')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground/80 hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <HelpCircle class="size-4 text-muted-foreground" />
                            <span>{{ $t("nav.faq") }}</span>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>

                    <Link
                        href="/feedback"
                        @click="closeBottomSheet"
                        :class="
                            cn(
                                'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                isUrlActive('/feedback')
                                    ? 'bg-primary/15 text-primary border border-primary/30'
                                    : 'bg-background/60 text-foreground/80 hover:bg-accent',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <MessageSquare class="size-4 text-muted-foreground" />
                            <span>{{ $t("nav.feedback") }}</span>
                        </div>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>
                </div>

                <!-- Platform Version Footer -->
                <div class="pt-2 text-center border-t border-border/80">
                    <p class="text-xs text-muted-foreground font-mono">
                        IME Roleplay Police Duty &bull; Mobile Hybrid Interface
                    </p>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
