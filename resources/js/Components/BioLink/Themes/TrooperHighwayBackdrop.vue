<script setup>
// Background layer for the "SASP State Trooper" bio-link theme: a highway
// running to the horizon in gold lane lines, and the trooper star.
import { starPath } from "./shapes";

defineProps({
    live: { type: Boolean, default: false },
});

const star = starPath(100, 100, 7, 96, 44);
const starInner = starPath(100, 100, 7, 78, 36);
</script>

<template>
    <div class="trooper-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <div class="trooper-glow absolute -top-56 left-1/2 size-[42rem] -translate-x-1/2 rounded-full" />

        <!-- Highway to the horizon -->
        <svg class="absolute inset-x-0 top-0 h-[44rem] w-full" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMin slice">
            <polygon class="trooper-road" points="500,70 -260,700 1260,700" />
            <line class="trooper-edge" x1="500" y1="70" x2="-260" y2="700" />
            <line class="trooper-edge" x1="500" y1="70" x2="1260" y2="700" />
            <line class="trooper-center" x1="496" y1="70" x2="470" y2="700" />
            <line class="trooper-center" x1="504" y1="70" x2="530" y2="700" />
            <line class="trooper-lane" x1="500" y1="70" x2="150" y2="700" />
            <line class="trooper-lane" x1="500" y1="70" x2="850" y2="700" />
        </svg>

        <!-- Trooper star, faint, behind the profile -->
        <svg class="trooper-star absolute left-1/2 top-10 w-[24rem] -translate-x-1/2 sm:w-[30rem]" viewBox="0 0 200 200">
            <path :d="star" />
            <path :d="starInner" />
        </svg>

        <div class="absolute inset-0 bg-linear-to-b from-transparent from-35% via-background/75 via-60% to-background" />
    </div>
</template>

<style scoped>
.trooper-glow {
    background: var(--primary);
    filter: blur(90px);
    opacity: 0.1;
}

.trooper-road {
    fill: color-mix(in oklab, var(--foreground) 3%, transparent);
}
.trooper-edge {
    stroke: var(--primary);
    stroke-width: 3;
    opacity: 0.35;
    vector-effect: non-scaling-stroke;
}
.trooper-center {
    stroke: var(--primary);
    stroke-width: 2;
    opacity: 0.28;
    vector-effect: non-scaling-stroke;
}
.trooper-lane {
    stroke: var(--foreground);
    stroke-width: 2;
    stroke-dasharray: 26 34;
    opacity: 0.12;
    vector-effect: non-scaling-stroke;
}

.trooper-star path {
    fill: none;
    stroke: var(--primary);
    stroke-width: 1.5;
    stroke-linejoin: round;
    opacity: 0.16;
    vector-effect: non-scaling-stroke;
}
.trooper-star path:first-child {
    fill: color-mix(in oklab, var(--primary) 5%, transparent);
}

@media (prefers-reduced-motion: no-preference) {
    .trooper-lane {
        animation: trooper-drive 1.6s linear infinite;
    }
    .is-live .trooper-glow {
        animation: trooper-breathe 2.4s ease-in-out infinite;
    }
}
@keyframes trooper-drive {
    to {
        stroke-dashoffset: -60;
    }
}
@keyframes trooper-breathe {
    0%,
    100% {
        opacity: 0.08;
    }
    50% {
        opacity: 0.2;
    }
}
</style>
