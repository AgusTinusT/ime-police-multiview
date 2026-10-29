<script setup>
import { ChevronLeft, ChevronRight } from "@lucide/vue";
import { ref } from "vue";

import { Button } from "@/Components/ui/button";

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
});

const track = ref(null);
const scrollBy = (dir) =>
    track.value?.scrollBy({ left: dir * track.value.clientWidth * 0.85, behavior: "smooth" });
</script>

<template>
    <section class="group/row relative">
        <div class="mb-2 flex items-baseline gap-3 px-3 sm:px-6 lg:px-12">
            <h2 class="shrink-0 whitespace-nowrap text-lg font-bold tracking-tight text-foreground sm:text-xl">{{ title }}</h2>
            <span v-if="subtitle" class="min-w-0 truncate text-xs text-muted-foreground sm:text-sm">{{ subtitle }}</span>
            <slot name="action" />
        </div>
        <div class="relative">
            <div
                ref="track"
                class="no-scrollbar -my-6 flex snap-x gap-2 overflow-x-auto scroll-px-3 px-3 py-6 sm:gap-3 sm:scroll-px-6 sm:px-6 lg:scroll-px-12 lg:px-12"
            >
                <slot />
            </div>
            <Button
                variant="ghost"
                class="absolute inset-y-0 left-0 z-20 hidden h-auto w-10 rounded-none bg-background/70 opacity-0 transition-opacity group-hover/row:opacity-100 focus-visible:opacity-100 md:flex lg:w-12"
                aria-label="Geser ke kiri"
                @click="scrollBy(-1)"
            >
                <ChevronLeft class="size-6" />
            </Button>
            <Button
                variant="ghost"
                class="absolute inset-y-0 right-0 z-20 hidden h-auto w-10 rounded-none bg-background/70 opacity-0 transition-opacity group-hover/row:opacity-100 focus-visible:opacity-100 md:flex lg:w-12"
                aria-label="Geser ke kanan"
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
