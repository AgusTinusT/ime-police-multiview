<script setup>
// Background layer for the "SRT Pursuit" bio-link theme, after the SRT stream
// thumbnails: an oversized white SRT wordmark, a red slash, red/blue siren
// glow that keeps alternating, rising embers and diagonal speed lines.
// Purely decorative, CSS-only motion that stops for prefers-reduced-motion.
import { rng } from "./shapes";
import SrtWordmark from "./SrtWordmark.vue";

defineProps({
    // While the officer is live the sirens flash faster and brighter.
    live: { type: Boolean, default: false },
});

// Embers drifting up through the top of the page (deterministic per render).
const rand = rng(23);
const embers = Array.from({ length: 22 }, () => ({
    left: `${(rand() * 100).toFixed(1)}%`,
    top: `${(18 + rand() * 50).toFixed(1)}vh`,
    size: `${(2 + rand() * 3).toFixed(1)}px`,
    delay: `${(-rand() * 7).toFixed(2)}s`,
    duration: `${(5 + rand() * 4).toFixed(2)}s`,
    drift: `${Math.round((rand() - 0.5) * 80)}px`,
    hot: rand() < 0.35,
}));
</script>

<template>
    <div class="srt-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <!-- Speed lines: the pursuit feel -->
        <div class="srt-speed absolute inset-0" />

        <!-- Siren glow: red left, blue right, alternating -->
        <div class="srt-siren srt-siren-red absolute -left-40 -top-40 size-[34rem] rounded-full" />
        <div class="srt-siren srt-siren-blue absolute -right-40 -top-24 size-[34rem] rounded-full" />

        <!-- Red slash across the top corner, like the thumbnails (kept clear of the profile text) -->
        <div class="srt-slash absolute -right-40 top-6 h-5 w-[30rem]" />
        <div class="srt-slash srt-slash-thin absolute -right-32 top-16 h-1 w-[22rem]" />

        <!-- Oversized white unit wordmark bleeding off the edges -->
        <SrtWordmark class="srt-bg-wordmark absolute left-1/2 top-24 w-[135vw] max-w-none -translate-x-1/2 sm:top-16 sm:w-[56rem]" />

        <!-- Fade into the page so text stays readable -->
        <div class="absolute inset-0 bg-linear-to-b from-transparent from-35% via-background/70 via-60% to-background" />

        <!-- Embers, above the fade so they stay visible -->
        <span
            v-for="(e, i) in embers"
            :key="i"
            class="srt-ember absolute rounded-full"
            :class="{ 'is-hot': e.hot }"
            :style="{
                left: e.left,
                top: e.top,
                width: e.size,
                height: e.size,
                animationDelay: e.delay,
                animationDuration: e.duration,
                '--drift': e.drift,
            }"
        />
    </div>
</template>

<style scoped>
.srt-speed {
    background-image: repeating-linear-gradient(
        -20deg,
        transparent 0 22px,
        color-mix(in oklab, var(--color-dept-lspd) 10%, transparent) 22px 23px
    );
    mask-image: linear-gradient(to bottom, black, transparent 70%);
}

.srt-siren {
    filter: blur(80px);
    opacity: 0.2;
}
.srt-siren-red {
    background: var(--color-live);
}
.srt-siren-blue {
    background: var(--color-dept-lspd);
}

.srt-slash {
    background: var(--color-live);
    transform: rotate(-24deg);
    opacity: 0.45;
    border-radius: 2px;
}
.srt-slash-thin {
    opacity: 0.35;
}

/* White like the thumbnails, faint enough to sit behind the profile text. */
.srt-bg-wordmark {
    color: var(--foreground);
    opacity: 0.09;
}

/* Fire, not a theme colour: embers stay orange on any accent. */
.srt-ember {
    background: #fb923c;
    box-shadow: 0 0 6px 1px #f97316;
    opacity: 0;
}
.srt-ember.is-hot {
    background: #fde68a;
    box-shadow: 0 0 8px 2px #f59e0b;
}

@media (prefers-reduced-motion: no-preference) {
    .srt-speed {
        animation: srt-rush 6s linear infinite;
    }
    .srt-siren-red {
        animation: srt-siren 3.2s ease-in-out infinite;
    }
    .srt-siren-blue {
        animation: srt-siren 3.2s ease-in-out 1.6s infinite;
    }
    .is-live .srt-siren-red,
    .is-live .srt-siren-blue {
        animation-duration: 1.2s;
    }
    .is-live .srt-siren-blue {
        animation-delay: 0.6s;
    }
    .srt-ember {
        animation-name: srt-ember;
        animation-timing-function: ease-out;
        animation-iteration-count: infinite;
    }
}
/* Without motion, a few embers still glow in place. */
@media (prefers-reduced-motion: reduce) {
    .srt-ember.is-hot {
        opacity: 0.5;
    }
}

@keyframes srt-rush {
    to {
        background-position: -240px 88px;
    }
}
@keyframes srt-siren {
    0%,
    100% {
        opacity: 0.1;
    }
    50% {
        opacity: 0.34;
    }
}
@keyframes srt-ember {
    0% {
        transform: translate(0, 0) scale(1);
        opacity: 0;
    }
    15% {
        opacity: 0.9;
    }
    100% {
        transform: translate(var(--drift), -38vh) scale(0.3);
        opacity: 0;
    }
}
</style>
