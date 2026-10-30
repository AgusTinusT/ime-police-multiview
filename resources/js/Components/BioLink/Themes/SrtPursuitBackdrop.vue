<script setup>
// Background layer for the "SRT Pursuit" bio-link theme: an oversized SRT
// wordmark, a red slash, police-blue/red siren glow and diagonal speed lines. Purely decorative,
// CSS-only motion that stops for prefers-reduced-motion.
import SrtWordmark from "./SrtWordmark.vue";

defineProps({
    // While the officer is live the siren glow pulses; otherwise it rests dim.
    live: { type: Boolean, default: false },
});
</script>

<template>
    <div class="srt-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <!-- Speed lines: the pursuit feel -->
        <div class="srt-speed absolute inset-0" />

        <!-- Siren glow: red left, blue right -->
        <div class="srt-siren srt-siren-red absolute -left-40 -top-40 size-[34rem] rounded-full" />
        <div class="srt-siren srt-siren-blue absolute -right-40 -top-24 size-[34rem] rounded-full" />

        <!-- Red slash across the top corner, like the thumbnails (kept clear of the profile text) -->
        <div class="srt-slash absolute -right-40 top-6 h-5 w-[30rem]" />
        <div class="srt-slash srt-slash-thin absolute -right-32 top-16 h-1 w-[22rem]" />

        <!-- Oversized unit wordmark bleeding off the edges, like the stream thumbnails -->
        <SrtWordmark class="srt-bg-wordmark absolute left-1/2 top-24 w-[135vw] max-w-none -translate-x-1/2 sm:top-16 sm:w-[56rem]" />

        <!-- Fade into the page so text stays readable -->
        <div class="absolute inset-0 bg-linear-to-b from-transparent from-35% via-background/70 via-60% to-background" />
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
    opacity: 0.18;
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
    opacity: 0.4;
    border-radius: 2px;
}
.srt-slash-thin {
    opacity: 0.35;
}

/* Big wordmark in the theme accent, faint enough to sit behind the profile text. */
.srt-bg-wordmark {
    color: var(--primary);
    opacity: 0.14;
}

@media (prefers-reduced-motion: no-preference) {
    .srt-speed {
        animation: srt-rush 6s linear infinite;
    }
    .is-live .srt-siren-red {
        animation: srt-siren 1.6s ease-in-out infinite;
    }
    .is-live .srt-siren-blue {
        animation: srt-siren 1.6s ease-in-out 0.8s infinite;
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
        opacity: 0.12;
    }
    50% {
        opacity: 0.38;
    }
}
</style>
