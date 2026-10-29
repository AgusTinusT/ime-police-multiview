<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import Autoplay from "embla-carousel-autoplay";
import {
    Carousel,
    CarouselContent,
    CarouselItem,
} from "@/Components/ui/carousel";
import { Button } from "@/Components/ui/button";
import { PageContainer } from "@/Components/tactical";
import { cn } from "@/lib/utils";
import { ChevronLeft, ChevronRight, Pause, Play } from "@lucide/vue";
import HeroSlide from "./HeroSlide.vue";

const props = defineProps({
    slides: {
        type: Array,
        default: () => [],
    },
    interval: {
        type: Number,
        default: 7000,
    },
    preview: {
        type: Boolean,
        default: false,
    },
});

const wrapperRef = ref(null);
const emblaApi = ref(null);
const currentIndex = ref(0);
const isHovered = ref(false);
const isFocusedWithin = ref(false);
const isTabHidden = ref(false);
const userPaused = ref(false);
const prefersReducedMotion = ref(false);
const isIntersecting = ref(true);

let impressionTimer = null;
let intersectionObserver = null;
let mediaQuery = null;

const hasMultiple = computed(() => (props.slides?.length || 0) > 1);

const autoplayPlugin = computed(() => {
    if (props.preview || !hasMultiple.value || prefersReducedMotion.value) {
        return [];
    }
    return [
        Autoplay({
            delay: props.interval,
            stopOnInteraction: false,
            stopOnMouseEnter: false,
        }),
    ];
});

const isAutoplayPaused = computed(
    () =>
        props.preview ||
        !hasMultiple.value ||
        prefersReducedMotion.value ||
        userPaused.value ||
        isHovered.value ||
        isFocusedWithin.value ||
        isTabHidden.value,
);

function syncAutoplayState() {
    const api = emblaApi.value;
    if (!api) return;
    const plugin = api.plugins?.()?.autoplay;
    if (!plugin) return;

    if (isAutoplayPaused.value) {
        plugin.stop();
    } else {
        plugin.play();
    }
}

function onInitApi(api) {
    emblaApi.value = api;
    currentIndex.value = api.selectedScrollSnap() || 0;

    api.on("select", () => {
        currentIndex.value = api.selectedScrollSnap() || 0;
        scheduleImpressionCheck();
    });

    syncAutoplayState();
    scheduleImpressionCheck();
}

function goToSlide(idx) {
    emblaApi.value?.scrollTo(idx);
}

function goPrev() {
    emblaApi.value?.scrollPrev();
}

function goNext() {
    emblaApi.value?.scrollNext();
}

function toggleUserPause() {
    userPaused.value = !userPaused.value;
    syncAutoplayState();
}

function handleMouseEnter() {
    isHovered.value = true;
    syncAutoplayState();
}

function handleMouseLeave() {
    isHovered.value = false;
    syncAutoplayState();
}

function handleFocusIn() {
    isFocusedWithin.value = true;
    syncAutoplayState();
}

function handleFocusOut(e) {
    if (wrapperRef.value && !wrapperRef.value.contains(e.relatedTarget)) {
        isFocusedWithin.value = false;
        syncAutoplayState();
    }
}

function handleVisibilityChange() {
    isTabHidden.value = document.visibilityState !== "visible";
    syncAutoplayState();
}

function handleReducedMotionChange(e) {
    prefersReducedMotion.value = !!e.matches;
    syncAutoplayState();
}

function sendTelemetry(slide, eventType) {
    if (props.preview || !slide || !slide.id) return;
    if (slide.type === "live_spotlight" || slide.slide_type === "live_spotlight") return;

    const url = `/api/v1/hero-slides/${slide.id}/event`;
    const payload = JSON.stringify({ type: eventType });

    try {
        if (typeof navigator !== "undefined" && typeof navigator.sendBeacon === "function") {
            const blob = new Blob([payload], { type: "application/json" });
            navigator.sendBeacon(url, blob);
        } else if (typeof fetch === "function") {
            fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: payload,
                keepalive: true,
            }).catch(() => {});
        }
    } catch {
        // Ignore telemetry error
    }
}

function scheduleImpressionCheck() {
    if (impressionTimer) {
        clearTimeout(impressionTimer);
        impressionTimer = null;
    }

    if (props.preview || !isIntersecting.value) return;
    const activeSlide = props.slides?.[currentIndex.value];
    if (!activeSlide || !activeSlide.id) return;
    if (activeSlide.type === "live_spotlight" || activeSlide.slide_type === "live_spotlight") return;

    const storageKey = `hero_imp_${activeSlide.id}`;
    try {
        if (sessionStorage.getItem(storageKey)) return;
    } catch {
        // Ignore sessionStorage error
    }

    impressionTimer = setTimeout(() => {
        if (!isIntersecting.value || document.visibilityState !== "visible") return;
        try {
            sessionStorage.setItem(storageKey, "1");
        } catch {
            // Ignore sessionStorage error
        }
        sendTelemetry(activeSlide, "impression");
    }, 1000);
}

function handleCtaClick(slide) {
    sendTelemetry(slide, "click");
}

watch(
    () => props.slides,
    () => {
        currentIndex.value = 0;
        scheduleImpressionCheck();
    },
);

onMounted(() => {
    if (typeof window !== "undefined" && typeof window.matchMedia === "function") {
        mediaQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
        prefersReducedMotion.value = mediaQuery.matches;
        mediaQuery.addEventListener?.("change", handleReducedMotionChange);
    }

    document.addEventListener("visibilitychange", handleVisibilityChange);

    if (typeof IntersectionObserver !== "undefined" && wrapperRef.value) {
        intersectionObserver = new IntersectionObserver(
            (entries) => {
                const entry = entries[0];
                isIntersecting.value = !!entry && entry.intersectionRatio >= 0.5;
                if (isIntersecting.value) {
                    scheduleImpressionCheck();
                } else if (impressionTimer) {
                    clearTimeout(impressionTimer);
                    impressionTimer = null;
                }
            },
            { threshold: [0, 0.5, 1] },
        );
        intersectionObserver.observe(wrapperRef.value);
    }
});

onUnmounted(() => {
    if (impressionTimer) clearTimeout(impressionTimer);
    if (intersectionObserver) intersectionObserver.disconnect();
    document.removeEventListener("visibilitychange", handleVisibilityChange);
    mediaQuery?.removeEventListener?.("change", handleReducedMotionChange);
});
</script>

<template>
    <Head v-if="!preview && slides?.[0]?.image_desktop_url">
        <link rel="preload" as="image" :href="slides[0].image_desktop_url" />
    </Head>

    <section
        v-if="slides && slides.length > 0"
        ref="wrapperRef"
        role="region"
        aria-roledescription="carousel"
        aria-label="Sorotan"
        class="relative w-full bg-background py-4 sm:py-6"
        @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave"
        @focusin="handleFocusIn"
        @focusout="handleFocusOut"
    >
        <PageContainer>
            <Carousel
                :opts="{
                    loop: hasMultiple,
                    duration: prefersReducedMotion ? 0 : 20,
                }"
                :plugins="autoplayPlugin"
                class="w-full"
                aria-label="Sorotan"
                @init-api="onInitApi"
            >
                <CarouselContent>
                    <CarouselItem
                        v-for="(slide, idx) in slides"
                        :key="slide.id || idx"
                        role="group"
                        aria-roledescription="slide"
                        :aria-label="`${idx + 1} dari ${slides.length}`"
                        :aria-hidden="idx !== currentIndex ? 'true' : undefined"
                        :inert="idx !== currentIndex ? true : undefined"
                        class="motion-safe:transition-opacity motion-safe:duration-200"
                    >
                        <HeroSlide
                            :slide="slide"
                            :index="idx"
                            @cta-click="handleCtaClick(slide)"
                        />
                    </CarouselItem>
                </CarouselContent>

                <!-- Carousel Controls & Progress Bar (Only when > 1 slide) -->
                <div
                    v-if="hasMultiple"
                    class="mt-3 flex flex-wrap items-center justify-between gap-4 px-1"
                >
                    <!-- Indicator Dots & Progress Bar -->
                    <div class="flex items-center gap-2">
                        <button
                            v-for="(slide, idx) in slides"
                            :key="`dot-${slide.id || idx}`"
                            type="button"
                            :aria-label="`Slide ${idx + 1} dari ${slides.length}`"
                            :aria-current="idx === currentIndex ? 'true' : undefined"
                            :class="
                                cn(
                                    'relative h-2.5 overflow-hidden rounded-full transition-all cursor-pointer',
                                    idx === currentIndex
                                        ? 'w-10 bg-primary/25 border border-primary/50'
                                        : 'w-2.5 bg-muted-foreground/30 hover:bg-muted-foreground/60',
                                )
                            "
                            @click="goToSlide(idx)"
                        >
                            <span
                                v-if="idx === currentIndex"
                                :class="
                                    cn(
                                        'block h-full w-full origin-left bg-primary rounded-full',
                                        !isAutoplayPaused && 'motion-safe:animate-pulse',
                                    )
                                "
                            />
                        </button>
                    </div>

                    <!-- Play/Pause & Prev/Next Buttons -->
                    <div class="flex items-center gap-1.5">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-foreground"
                            :aria-label="userPaused ? 'Putar otomatis' : 'Jeda putar otomatis'"
                            @click="toggleUserPause"
                        >
                            <Play v-if="userPaused" class="size-4" />
                            <Pause v-else class="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-foreground"
                            aria-label="Slide sebelumnya"
                            @click="goPrev"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-foreground"
                            aria-label="Slide berikutnya"
                            @click="goNext"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </Carousel>
        </PageContainer>
    </section>
</template>

