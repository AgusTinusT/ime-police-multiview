<script setup>
import { ChevronLeft, ChevronRight } from "@lucide/vue";
import { onMounted, onUnmounted, ref } from "vue";

import { Button } from "@/Components/ui/button";
import { cn } from "@/lib/utils";

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    // Few items: lay the cards out as a grid that fills the row on md+ instead
    // of a horizontal track. Children should size themselves with w-full at md+.
    fillCols: { type: Number, default: 0 },
});

const track = ref(null);
const canLeft = ref(false);
const canRight = ref(false);

// Arrows only when there is something to scroll to in that direction.
const updateArrows = () => {
    const el = track.value;
    if (!el) return;
    canLeft.value = el.scrollLeft > 4;
    canRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
};

const scrollBy = (dir) =>
    track.value?.scrollBy({ left: dir * track.value.clientWidth * 0.85, behavior: "smooth" });

let resizeObserver = null;
let mutationObserver = null;

onMounted(() => {
    updateArrows();
    resizeObserver = new ResizeObserver(updateArrows);
    resizeObserver.observe(track.value);
    // Rows fill in after mount (fetched clips, squads), so watch the children too.
    mutationObserver = new MutationObserver(() => requestAnimationFrame(updateArrows));
    mutationObserver.observe(track.value, { childList: true });
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    mutationObserver?.disconnect();
});

const gridCols = {
    1: "md:grid-cols-[minmax(0,48rem)]",
    2: "md:grid-cols-2",
    3: "md:grid-cols-3",
};
</script>

<template>
    <section class="group/row relative">
        <div class="mb-2 flex items-baseline gap-3 px-3 sm:px-6 lg:px-12">
            <h2 class="shrink-0 whitespace-nowrap text-lg font-bold tracking-tight text-foreground sm:text-xl">{{ title }}</h2>
            <span v-if="subtitle" class="min-w-0 truncate text-xs text-muted-foreground sm:text-sm">{{ subtitle }}</span>
            <slot name="action" />
        </div>
        <div v-if="$slots.filters" class="mb-1 px-3 sm:px-6 lg:px-12">
            <slot name="filters" />
        </div>
        <div class="relative">
            <div
                ref="track"
                :class="cn(
                    'no-scrollbar -my-6 flex snap-x gap-2 overflow-x-auto scroll-px-3 px-3 py-6 sm:gap-3 sm:scroll-px-6 sm:px-6 lg:scroll-px-12 lg:px-12',
                    fillCols && ['md:grid md:overflow-visible md:gap-4', gridCols[fillCols]],
                )"
                @scroll.passive="updateArrows"
            >
                <slot />
            </div>
            <Button
                v-if="canLeft"
                variant="ghost"
                class="absolute inset-y-0 left-0 z-20 hidden h-auto w-10 rounded-none bg-background/70 opacity-0 transition-opacity group-hover/row:opacity-100 focus-visible:opacity-100 md:flex lg:w-12"
                :aria-label="$t('catalog.scroll_left')"
                @click="scrollBy(-1)"
            >
                <ChevronLeft class="size-6" />
            </Button>
            <Button
                v-if="canRight"
                variant="ghost"
                class="absolute inset-y-0 right-0 z-20 hidden h-auto w-10 rounded-none bg-background/70 opacity-0 transition-opacity group-hover/row:opacity-100 focus-visible:opacity-100 md:flex lg:w-12"
                :aria-label="$t('catalog.scroll_right')"
                @click="scrollBy(1)"
            >
                <ChevronRight class="size-6" />
            </Button>
        </div>
    </section>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>
