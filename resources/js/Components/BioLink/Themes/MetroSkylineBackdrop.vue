<script setup>
// Background layer for the "LSPD Metropolitan" bio-link theme: the Los Santos
// skyline at night with lit windows, and a blue/white lightbar glow above it.
import { rng, skyline } from "./shapes";

defineProps({
    live: { type: Boolean, default: false },
});

const HEIGHT = 260;
const blocks = skyline({ width: 1200, height: HEIGHT, seed: 7 });

// A few lit windows per tower (deterministic)
const rand = rng(41);
const windows = blocks.flatMap((b) => {
    const lit = [];
    for (let y = HEIGHT - b.h + 10; y < HEIGHT - 12; y += 14) {
        for (let x = b.x + 6; x < b.x + b.w - 6; x += 10) {
            if (rand() < 0.14) lit.push({ x, y });
        }
    }
    return lit;
});
</script>

<template>
    <div class="metro-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <!-- Lightbar: blue left, white right -->
        <div class="metro-bar metro-bar-blue absolute -left-40 -top-40 size-[34rem] rounded-full" />
        <div class="metro-bar metro-bar-white absolute -right-40 -top-32 size-[30rem] rounded-full" />

        <!-- Skyline -->
        <svg class="metro-skyline absolute inset-x-0 top-6 h-[17rem] w-full" :viewBox="`0 0 1200 ${HEIGHT}`" preserveAspectRatio="xMidYMax slice">
            <g class="metro-towers">
                <rect v-for="(b, i) in blocks" :key="`t${i}`" :x="b.x" :y="HEIGHT - b.h" :width="b.w" :height="b.h" />
                <template v-for="(b, i) in blocks" :key="`m${i}`">
                    <rect v-if="b.mast" :x="b.x + b.w / 2 - 1" :y="HEIGHT - b.h - 22" width="2" height="22" />
                </template>
            </g>
            <g class="metro-windows">
                <rect v-for="(w, i) in windows" :key="`w${i}`" :x="w.x" :y="w.y" width="4" height="5" />
            </g>
        </svg>

        <div class="absolute inset-0 bg-linear-to-b from-transparent from-40% via-background/75 via-65% to-background" />
    </div>
</template>

<style scoped>
.metro-bar {
    filter: blur(80px);
    opacity: 0.16;
}
.metro-bar-blue {
    background: var(--primary);
}
.metro-bar-white {
    background: var(--foreground);
    opacity: 0.07;
}

/* The city fades out before the profile text starts. */
.metro-skyline {
    mask-image: linear-gradient(to bottom, black 45%, transparent 92%);
}
.metro-towers rect {
    fill: color-mix(in oklab, var(--primary) 10%, var(--background));
}
.metro-windows rect {
    fill: var(--highlight);
    opacity: 0.28;
}

@media (prefers-reduced-motion: no-preference) {
    .metro-bar-blue {
        animation: metro-flash 4s ease-in-out infinite;
    }
    .metro-bar-white {
        animation: metro-flash-white 4s ease-in-out 2s infinite;
    }
    .is-live .metro-bar-blue,
    .is-live .metro-bar-white {
        animation-duration: 1.4s;
    }
}
@keyframes metro-flash {
    0%,
    100% {
        opacity: 0.1;
    }
    50% {
        opacity: 0.26;
    }
}
@keyframes metro-flash-white {
    0%,
    100% {
        opacity: 0.04;
    }
    50% {
        opacity: 0.12;
    }
}
</style>
