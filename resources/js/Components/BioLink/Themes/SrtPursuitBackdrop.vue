<script setup>
// Background layer for the "SRT Pursuit" bio-link theme: a cobra emblem, a red
// slash, police-blue/red siren glow and diagonal speed lines. Purely decorative,
// CSS-only motion that stops for prefers-reduced-motion.
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

        <!-- Red slash across the top, like the thumbnails -->
        <div class="srt-slash absolute -right-24 top-24 h-6 w-[36rem]" />
        <div class="srt-slash srt-slash-thin absolute -right-16 top-36 h-1.5 w-[28rem]" />

        <!-- Cobra emblem watermark (original drawing) -->
        <svg
            class="srt-cobra absolute left-1/2 top-16 w-[26rem] -translate-x-1/2 sm:top-6 sm:w-[34rem]"
            viewBox="0 0 200 240"
            fill="none"
        >
            <!-- Hood -->
            <path
                d="M100 18c-15 0-26 12-26 30 0 4 .6 8 1.8 11.5C52 66 30 92 30 130c0 42 30 74 70 92 40-18 70-50 70-92 0-38-22-64-45.8-70.5 1.2-3.5 1.8-7.5 1.8-11.5 0-18-11-30-26-30Z"
                class="srt-cobra-hood"
            />
            <!-- Hood markings -->
            <path d="M100 74 72 104l28 22 28-22-28-30Z" class="srt-cobra-mark" />
            <path d="M60 128c14 10 26 26 40 50 14-24 26-40 40-50" class="srt-cobra-line" />
            <path d="M70 160c10 6 20 18 30 34 10-16 20-28 30-34" class="srt-cobra-line" />
            <!-- Eyes -->
            <path d="M86 40c4-3 8-3 11 1-4 2-8 2-11-1Z" class="srt-cobra-eye" />
            <path d="M114 40c-4-3-8-3-11 1 4 2 8 2 11-1Z" class="srt-cobra-eye" />
        </svg>

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
    opacity: 0.55;
    border-radius: 2px;
}
.srt-slash-thin {
    opacity: 0.35;
}

.srt-cobra {
    opacity: 0.34;
}
.srt-cobra-hood {
    fill: color-mix(in oklab, var(--primary) 35%, transparent);
    stroke: var(--primary);
    stroke-width: 2;
}
.srt-cobra-mark {
    fill: color-mix(in oklab, var(--primary) 60%, transparent);
}
.srt-cobra-line {
    stroke: var(--primary);
    stroke-width: 2;
    stroke-linecap: round;
}
.srt-cobra-eye {
    fill: var(--color-live);
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
