<script setup>
import { ref, computed, watch, onUnmounted } from "vue";
import { Button } from "@/Components/ui/button";
import { cn } from "@/lib/utils";
import { ArrowRight, ChevronLeft, ChevronRight, Megaphone, X } from "@lucide/vue";

// Admin-managed announcement banner on the home dashboard. Reads the model
// fields that the admin form writes (title, message, icon, image_url).
const props = defineProps({
    announcements: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close"]);

const isVisible = ref(true);
const activeIndex = ref(0);
let timer = null;

const currentAnnouncement = computed(() => {
    if (!props.announcements || props.announcements.length === 0) return null;
    return props.announcements[activeIndex.value % props.announcements.length];
});

// Admin picks "info" / "warning" / "poll" (or leaves it as default).
// Each type gets its own border tint so the banner reads at a glance.
const toneClasses = computed(() => {
    const type = (currentAnnouncement.value?.type || "").toLowerCase();
    if (type === "warning" || type === "poll") {
        return {
            frame: "border-warning/50 bg-warning/5",
            label: "text-warning",
            icon: "bg-warning/15 text-warning",
        };
    }
    if (type === "info") {
        return {
            frame: "border-info/40 bg-info/5",
            label: "text-info",
            icon: "bg-info/15 text-info",
        };
    }
    return {
        frame: "border-primary/40 bg-primary/5",
        label: "text-primary",
        icon: "bg-primary/15 text-primary",
    };
});

// The admin field accepts an emoji glyph (🚨, 📢, ⚠️); fall back to the icon.
const iconIsEmoji = computed(() => {
    const raw = (currentAnnouncement.value?.icon || "").trim();
    // very loose: 1–4 chars that aren't just whitespace/alphanumerics
    return !!raw && raw.length <= 4 && !/^[a-z0-9_-]+$/i.test(raw);
});

const startTimer = () => {
    if (timer) clearInterval(timer);
    if (props.announcements && props.announcements.length > 1) {
        timer = setInterval(() => nextSlide(), 6000);
    }
};

const stopTimer = () => {
    if (timer) clearInterval(timer);
};

const nextSlide = () => {
    if (!props.announcements || props.announcements.length === 0) return;
    activeIndex.value = (activeIndex.value + 1) % props.announcements.length;
};

const prevSlide = () => {
    if (!props.announcements || props.announcements.length === 0) return;
    activeIndex.value =
        (activeIndex.value - 1 + props.announcements.length) % props.announcements.length;
};

const setSlide = (idx) => {
    activeIndex.value = idx;
    startTimer();
};

const dismissBanner = () => {
    isVisible.value = false;
    emit("close");
};

watch(
    () => props.announcements,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            activeIndex.value = 0;
            startTimer();
        } else {
            stopTimer();
        }
    },
    { immediate: true, deep: true }
);

onUnmounted(() => stopTimer());
</script>

<template>
    <section
        v-if="isVisible && currentAnnouncement"
        :aria-label="currentAnnouncement.title"
        :class="cn('relative overflow-hidden rounded-xl border bg-card/70 backdrop-blur-sm', toneClasses.frame)"
        @mouseenter="stopTimer"
        @mouseleave="startTimer"
    >
        <!-- Optional banner image fades in from the right. Decorative, hidden to AT. -->
        <div
            v-if="currentAnnouncement.image_url"
            aria-hidden="true"
            class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 md:block"
            :style="{
                backgroundImage: `linear-gradient(to right, var(--color-card) 10%, transparent 90%), url('${currentAnnouncement.image_url}')`,
                backgroundSize: 'cover',
                backgroundPosition: 'center right',
            }"
        />

        <!-- Close button floats so it doesn't eat width on mobile -->
        <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            aria-label="Tutup pengumuman"
            title="Tutup"
            class="absolute right-1.5 top-1.5 z-10 text-muted-foreground hover:text-foreground"
            @click="dismissBanner"
        >
            <X class="size-4" />
        </Button>

        <div class="relative flex flex-col gap-3 p-3 sm:flex-row sm:items-start sm:gap-4 sm:p-4">
            <div class="flex items-start gap-3 min-w-0 flex-1 sm:gap-4">
                <!-- Icon / emoji -->
                <div
                    :class="cn('flex size-10 shrink-0 items-center justify-center rounded-lg border border-current/20 text-lg', toneClasses.icon)"
                    aria-hidden="true"
                >
                    <span v-if="iconIsEmoji">{{ currentAnnouncement.icon }}</span>
                    <Megaphone v-else class="size-5" />
                </div>

                <!-- Text -->
                <div class="min-w-0 flex-1 space-y-1 pr-8 sm:pr-0">
                    <div class="flex items-center gap-2">
                        <span
                            :class="cn('text-xs font-mono font-bold uppercase tracking-wider', toneClasses.label)"
                        >
                            {{ currentAnnouncement.badge_text || "Pengumuman" }}
                        </span>
                        <span
                            v-if="props.announcements.length > 1"
                            class="text-xs text-muted-foreground"
                        >{{ activeIndex + 1 }}/{{ props.announcements.length }}</span>
                    </div>

                    <h3 class="text-sm font-semibold text-foreground sm:text-base">
                        {{ currentAnnouncement.title }}
                    </h3>

                    <p
                        v-if="currentAnnouncement.message"
                        class="whitespace-pre-line text-sm text-muted-foreground"
                    >{{ currentAnnouncement.message }}</p>
                </div>
            </div>

            <!-- CTA row: full-width button on mobile, inline arrows on desktop.
                 The close button floats top-right, so leave it room (pr-10). -->
            <div
                v-if="currentAnnouncement.action_url && currentAnnouncement.action_text || props.announcements.length > 1"
                class="flex shrink-0 items-center gap-1 sm:ml-auto sm:items-start sm:pt-0.5 sm:pr-10"
            >
                <Button
                    v-if="currentAnnouncement.action_url && currentAnnouncement.action_text"
                    as-child
                    size="sm"
                    class="w-full gap-1.5 sm:w-auto"
                >
                    <a :href="currentAnnouncement.action_url" target="_blank" rel="noopener noreferrer">
                        <span>{{ currentAnnouncement.action_text }}</span>
                        <ArrowRight class="size-3.5" />
                    </a>
                </Button>

                <Button
                    v-if="props.announcements.length > 1"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Pengumuman sebelumnya"
                    title="Sebelumnya"
                    class="hidden sm:inline-flex"
                    @click="prevSlide"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <Button
                    v-if="props.announcements.length > 1"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Pengumuman selanjutnya"
                    title="Selanjutnya"
                    class="hidden sm:inline-flex"
                    @click="nextSlide"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>

        <!-- Dots at bottom when more than one -->
        <div
            v-if="props.announcements.length > 1"
            class="relative flex items-center justify-center gap-1.5 pb-2"
        >
            <button
                v-for="(_, idx) in props.announcements"
                :key="idx"
                type="button"
                :aria-label="`Pengumuman ${idx + 1}`"
                @click="setSlide(idx)"
                :class="cn('h-1 rounded-full transition-all', idx === activeIndex ? 'w-5 bg-primary' : 'w-1.5 bg-muted hover:bg-muted-foreground/50')"
            ></button>
        </div>
    </section>
</template>
