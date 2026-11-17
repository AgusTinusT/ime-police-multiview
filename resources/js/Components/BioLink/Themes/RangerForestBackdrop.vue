<script setup>
// Background layer for the "SAPR Park Ranger" bio-link theme: topographic map
// contours, two pine ridges in morning mist and a low dawn glow.
import { contours, pinesOn, ridge } from "./shapes";

defineProps({
    live: { type: Boolean, default: false },
});

const topoA = contours({ cx: 280, cy: 170, rings: 9, gap: 30, seed: 11 });
const topoB = contours({ cx: 960, cy: 110, rings: 7, gap: 34, seed: 29 });

const back = ridge({ width: 1200, base: 210, amplitude: 70, seed: 3, bottom: 320, step: 30 });
const front = ridge({ width: 1200, base: 262, amplitude: 46, seed: 8, bottom: 320, step: 26 });
const backPines = pinesOn(back.points, { seed: 5, every: 1, min: 20, max: 40 });
const frontPines = pinesOn(front.points, { seed: 9, every: 1, min: 30, max: 62 });
</script>

<template>
    <div class="ranger-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <!-- Dawn glow: accent above, warm highlight low on the left -->
        <div class="ranger-glow ranger-glow-top absolute -top-48 left-1/2 size-[40rem] -translate-x-1/2 rounded-full" />
        <div class="ranger-glow ranger-glow-dawn absolute -left-40 top-40 size-[30rem] rounded-full" />

        <!-- Topographic contours -->
        <svg class="ranger-topo absolute inset-x-0 top-0 h-[46rem] w-full" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMin slice">
            <path v-for="(d, i) in topoA" :key="`a${i}`" :d="d" />
            <path v-for="(d, i) in topoB" :key="`b${i}`" :d="d" />
        </svg>

        <!-- Pine ridges -->
        <svg class="absolute inset-x-0 top-16 h-[20rem] w-full" viewBox="0 0 1200 320" preserveAspectRatio="xMidYMax slice">
            <path class="ranger-ridge-back" :d="back.d" />
            <path class="ranger-ridge-back" :d="backPines" />
            <path class="ranger-ridge-front" :d="front.d" />
            <path class="ranger-ridge-front" :d="frontPines" />
        </svg>

        <!-- Morning mist + fade into the page -->
        <div class="absolute inset-0 bg-linear-to-b from-transparent from-35% via-background/75 via-60% to-background" />
    </div>
</template>

<style scoped>
.ranger-glow {
    filter: blur(90px);
}
.ranger-glow-top {
    background: var(--primary);
    opacity: 0.12;
}
.ranger-glow-dawn {
    background: var(--highlight);
    opacity: 0.07;
}

.ranger-topo path {
    fill: none;
    stroke: var(--primary);
    stroke-width: 1.2;
    opacity: 0.13;
    vector-effect: non-scaling-stroke;
}

.ranger-ridge-back {
    fill: color-mix(in oklab, var(--primary) 9%, var(--background));
}
.ranger-ridge-front {
    fill: color-mix(in oklab, var(--primary) 16%, var(--background));
}

@media (prefers-reduced-motion: no-preference) {
    .is-live .ranger-glow-top {
        animation: ranger-breathe 3s ease-in-out infinite;
    }
}
@keyframes ranger-breathe {
    0%,
    100% {
        opacity: 0.1;
    }
    50% {
        opacity: 0.22;
    }
}
</style>
