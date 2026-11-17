<script setup>
// Background layer for the "Stealth Interceptor" bio-link theme: matte carbon
// fibre weave, body-panel crease lines and a hidden lightbar sweeping across.
defineProps({
    live: { type: Boolean, default: false },
});
</script>

<template>
    <div class="stealth-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <div class="stealth-carbon absolute inset-0" />

        <!-- Body-panel creases -->
        <div class="stealth-crease absolute -left-20 top-40 h-px w-[60rem] -rotate-12" />
        <div class="stealth-crease absolute -right-24 top-64 h-px w-[50rem] rotate-6" />

        <!-- Hidden lightbar: a thin glowing strip that sweeps once in a while -->
        <div class="stealth-bar-track absolute inset-x-0 top-14 h-px">
            <div class="stealth-bar absolute top-0 h-px w-1/3" />
        </div>
        <div class="stealth-glow absolute -top-56 left-1/2 size-[40rem] -translate-x-1/2 rounded-full" />

        <!-- Vignette + fade into the page -->
        <div class="stealth-vignette absolute inset-0" />
        <div class="absolute inset-0 bg-linear-to-b from-transparent from-40% via-background/75 via-65% to-background" />
    </div>
</template>

<style scoped>
.stealth-carbon {
    background-color: color-mix(in oklab, var(--background) 55%, black);
    background-image:
        repeating-linear-gradient(45deg, color-mix(in oklab, var(--foreground) 4%, transparent) 0 2px, transparent 2px 6px),
        repeating-linear-gradient(-45deg, color-mix(in oklab, var(--foreground) 3%, transparent) 0 2px, transparent 2px 6px);
    mask-image: linear-gradient(to bottom, black, transparent 80%);
}

.stealth-crease {
    background: linear-gradient(to right, transparent, color-mix(in oklab, var(--foreground) 14%, transparent), transparent);
}

.stealth-bar {
    left: -33%;
    background: linear-gradient(to right, transparent, var(--primary), transparent);
    box-shadow: 0 0 18px 2px var(--primary);
    opacity: 0.7;
}

.stealth-glow {
    background: var(--primary);
    filter: blur(100px);
    opacity: 0.07;
}

.stealth-vignette {
    background: radial-gradient(ellipse 75% 60% at 50% 25%, transparent 40%, black 100%);
    opacity: 0.5;
}

@media (prefers-reduced-motion: no-preference) {
    .stealth-bar {
        animation: stealth-sweep 7s ease-in-out infinite;
    }
    .is-live .stealth-bar {
        animation-duration: 2.2s;
    }
}
@keyframes stealth-sweep {
    0% {
        left: -33%;
    }
    45%,
    100% {
        left: 100%;
    }
}
</style>
