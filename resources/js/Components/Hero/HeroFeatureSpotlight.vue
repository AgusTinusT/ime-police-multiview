<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { cn } from "@/lib/utils";
import {
    ChevronLeft,
    ChevronRight,
    Film,
    LayoutGrid,
    Scissors,
    ShieldCheck,
    Sparkles,
    Video,
} from "@lucide/vue";

defineEmits(["open-clipper-claim", "open-officer-claim"]);

const activeSlide = ref(0);
const isPaused = ref(false);
let timer = null;

const slides = [
    {
        id: "clips",
        tag: "CLIPS STUDIO • AKSES BETA GRATIS",
        tagVariant: "info",
        icon: Scissors,
        title: "Klip Video Patroli Resolusi Tinggi Bebas Watermark",
        desc: "Potong aksi pengejaran dan situasi tembak-menembak berkecepatan tinggi langsung dari siaran perwira. Tandai momen penting dengan multi-cut, ekspor video 1080p, dan bagikan ke media sosial.",
        chips: ["⚡ 1080p 60FPS Cutter", "🎯 Multi-Marker Trim", "🆓 Bebas Watermark"],
        primaryAction: {
            label: "Buka Clips Studio",
            href: "/clips",
            icon: Scissors,
        },
        secondaryAction: {
            label: "Akses Beta Tester",
            emit: "open-clipper-claim",
        },
    },
    {
        id: "multiview",
        tag: "THEATER MONITOR • HINGGA 9 LAYAR",
        tagVariant: "default",
        icon: LayoutGrid,
        title: "Pantau Multi-Bodycam Perwira Secara Bersamaan",
        desc: "Sistem multiview grid taktis untuk memantau 1 hingga 9 siaran langsung petugas lapangan dalam satu layar sinkron dengan mixer audio mandiri dan live chat terpadu.",
        chips: ["🎛️ Audio Matrix Mixer", "💬 Integrated SASP Live Chat", "📡 Fullscreen Mode"],
        primaryAction: {
            label: "Buka Multi-Bodycam",
            href: "/multiview",
            icon: LayoutGrid,
        },
        secondaryAction: {
            label: "Panduan Fitur",
            href: "/faq",
        },
    },
    {
        id: "biolink",
        tag: "SASP VERIFIED ID • BIO-LINK HUB",
        tagVariant: "warning",
        icon: ShieldCheck,
        title: "Klaim Profil Perwira & Dapatkan URL /@callsign",
        desc: "Verifikasi kepemilikan channel YouTube perwira secara otomatis tanpa menunggu admin. Tampilkan status dinas 10-8, lencana kesatuan resmi, dan terima tactical donation.",
        chips: ["⚡ Verifikasi YouTube Otomatis", "💳 Dukungan Donasi Langsung", "🏅 Lencana Departemen Resmi"],
        primaryAction: {
            label: "Klaim Profil Perwira",
            emit: "open-officer-claim",
            icon: ShieldCheck,
        },
        secondaryAction: {
            label: "Direktori Perwira",
            href: "/officers",
        },
    },
];

const nextSlide = () => {
    activeSlide.value = (activeSlide.value + 1) % slides.length;
};

const prevSlide = () => {
    activeSlide.value = (activeSlide.value - 1 + slides.length) % slides.length;
};

const setSlide = (idx) => {
    activeSlide.value = idx;
};

onMounted(() => {
    timer = setInterval(() => {
        if (!isPaused.value) {
            nextSlide();
        }
    }, 6500);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <div
        class="rounded-xl bg-card/85 border border-border backdrop-blur-md p-5 sm:p-6 shadow-xl relative overflow-hidden flex flex-col justify-between gap-5 transition-all duration-300"
        @mouseenter="isPaused = true"
        @mouseleave="isPaused = false"
    >
        <!-- Ambient decorative corner glow -->
        <div
            class="absolute -top-24 -right-24 size-56 rounded-full bg-primary/10 blur-3xl pointer-events-none"
        />

        <!-- Top Header & Tabs Bar -->
        <div class="space-y-3 relative z-10">
            <div class="flex items-center justify-between pb-2.5 border-b border-border">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-primary motion-safe:animate-pulse" />
                    <span class="text-xs font-mono font-semibold tracking-wider text-foreground uppercase">
                        SASP DISPATCH OS // FITUR UTAMA
                    </span>
                </div>
                <span class="text-xs font-mono text-muted-foreground">
                    0{{ activeSlide + 1 }} / 0{{ slides.length }}
                </span>
            </div>

            <!-- Mini Feature Pill Selectors -->
            <div class="flex items-center gap-1.5 p-1 rounded-lg bg-background/80 border border-border/80">
                <button
                    v-for="(s, idx) in slides"
                    :key="s.id"
                    type="button"
                    :class="
                        cn(
                            'flex-1 flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-md text-xs font-mono font-medium transition-colors cursor-pointer',
                            activeSlide === idx
                                ? 'bg-primary/15 text-primary border border-primary/30 font-bold shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/40 border border-transparent'
                        )
                    "
                    @click="setSlide(idx)"
                >
                    <component :is="s.icon" class="size-3.5 shrink-0" />
                    <span class="truncate">{{ s.id === 'clips' ? 'Clips Studio' : s.id === 'multiview' ? 'Multiview' : 'Bio-Link' }}</span>
                    <span
                        v-if="s.id === 'clips'"
                        class="hidden sm:inline-block size-1.5 rounded-full bg-info motion-safe:animate-ping"
                    />
                </button>
            </div>
        </div>

        <!-- Slide Body (Active Feature Spotlight) -->
        <div class="space-y-3 relative z-10 min-h-[160px] flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="font-mono text-xs border-primary/30 bg-primary/10 text-primary">
                        {{ slides[activeSlide].tag }}
                    </Badge>
                </div>

                <h3 class="text-base sm:text-lg font-bold text-foreground leading-snug">
                    {{ slides[activeSlide].title }}
                </h3>

                <p class="text-xs text-muted-foreground leading-relaxed line-clamp-3">
                    {{ slides[activeSlide].desc }}
                </p>
            </div>

            <!-- Feature Chips -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                <span
                    v-for="chip in slides[activeSlide].chips"
                    :key="chip"
                    class="px-2 py-0.5 rounded-md bg-muted/70 text-muted-foreground text-xs font-mono border border-border"
                >
                    {{ chip }}
                </span>
            </div>
        </div>

        <!-- Action Buttons & Navigation Footer -->
        <div class="flex items-center justify-between gap-3 pt-3 border-t border-border relative z-10">
            <div class="flex items-center gap-2">
                <!-- Primary Action -->
                <Button
                    v-if="slides[activeSlide].primaryAction.href"
                    as-child
                    size="sm"
                    class="text-xs font-mono font-bold gap-1.5"
                >
                    <Link :href="slides[activeSlide].primaryAction.href">
                        <component :is="slides[activeSlide].primaryAction.icon" class="size-3.5" />
                        <span>{{ slides[activeSlide].primaryAction.label }}</span>
                    </Link>
                </Button>
                <Button
                    v-else
                    type="button"
                    size="sm"
                    class="text-xs font-mono font-bold gap-1.5"
                    @click="$emit(slides[activeSlide].primaryAction.emit)"
                >
                    <component :is="slides[activeSlide].primaryAction.icon" class="size-3.5" />
                    <span>{{ slides[activeSlide].primaryAction.label }}</span>
                </Button>

                <!-- Secondary Action -->
                <Button
                    v-if="slides[activeSlide].secondaryAction.href"
                    as-child
                    variant="outline"
                    size="sm"
                    class="text-xs font-mono"
                >
                    <Link :href="slides[activeSlide].secondaryAction.href">
                        {{ slides[activeSlide].secondaryAction.label }}
                    </Link>
                </Button>
                <Button
                    v-else-if="slides[activeSlide].secondaryAction.emit"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="text-xs font-mono"
                    @click="$emit(slides[activeSlide].secondaryAction.emit)"
                >
                    {{ slides[activeSlide].secondaryAction.label }}
                </Button>
            </div>

            <!-- Arrow Navigation Buttons -->
            <div class="flex items-center gap-1 shrink-0">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    aria-label="Slide Sebelumnya"
                    @click="prevSlide"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    aria-label="Slide Berikutnya"
                    @click="nextSlide"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
