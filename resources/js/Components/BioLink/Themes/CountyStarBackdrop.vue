<script setup>
// Background layer for the "BCSO Sheriff County" bio-link theme: the six-point
// sheriff star with ball tips, low desert hills and a warm county sunset glow.
import { ridge, starPath, starTips } from "./shapes";

defineProps({
    live: { type: Boolean, default: false },
});

const star = starPath(100, 100, 6, 84, 44);
const tips = starTips(100, 100, 6, 84);
const far = ridge({ width: 1200, base: 230, amplitude: 34, seed: 17, bottom: 300, step: 60 });
const near = ridge({ width: 1200, base: 270, amplitude: 22, seed: 23, bottom: 300, step: 50 });
</script>

<template>
    <div class="county-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <!-- Sunset: warm highlight low, accent above -->
        <div class="county-glow county-glow-sun absolute left-1/2 top-24 size-[30rem] -translate-x-1/2 rounded-full" />
        <div class="county-glow county-glow-sky absolute -top-56 left-1/2 size-[42rem] -translate-x-1/2 rounded-full" />

        <!-- Sheriff star behind the profile -->
        <svg class="county-star absolute left-1/2 top-8 w-[22rem] -translate-x-1/2 sm:w-[28rem]" viewBox="0 0 200 200">
            <path :d="star" />
            <circle v-for="(t, i) in tips" :key="i" :cx="t.x" :cy="t.y" r="7" />
            <circle cx="100" cy="100" r="30" class="county-star-seal" />
        </svg>

        <!-- Desert hills -->
        <svg class="absolute inset-x-0 top-20 h-[19rem] w-full" viewBox="0 0 1200 300" preserveAspectRatio="xMidYMax slice">
            <path class="county-hill-far" :d="far.d" />
            <path class="county-hill-near" :d="near.d" />
        </svg>

        <div class="absolute inset-0 bg-linear-to-b from-transparent from-35% via-background/75 via-60% to-background" />
    </div>
</template>

<style scoped>
.county-glow {
    filter: blur(90px);
}
.county-glow-sun {
    background: var(--highlight);
    opacity: 0.1;
}
.county-glow-sky {
    background: var(--primary);
    opacity: 0.08;
}

.county-star path,
.county-star circle {
    fill: color-mix(in oklab, var(--highlight) 5%, transparent);
    stroke: var(--highlight);
    stroke-width: 1.5;
    opacity: 0.18;
    vector-effect: non-scaling-stroke;
}
.county-star .county-star-seal {
    fill: none;
}

.county-hill-far {
    fill: color-mix(in oklab, var(--primary) 8%, var(--background));
}
.county-hill-near {
    fill: color-mix(in oklab, var(--highlight) 7%, var(--background));
}

@media (prefers-reduced-motion: no-preference) {
    .is-live .county-glow-sun {
        animation: county-breathe 3s ease-in-out infinite;
    }
}
@keyframes county-breathe {
    0%,
    100% {
        opacity: 0.08;
    }
    50% {
        opacity: 0.2;
    }
}
</style>
