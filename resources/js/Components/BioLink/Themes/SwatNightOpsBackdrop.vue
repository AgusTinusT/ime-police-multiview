<script setup>
// Background layer for the "SWAT Night Ops" bio-link theme: jet-black base,
// night-vision phosphor glow, NVG scanlines and the vest's "SWAT" lettering.
// Purely decorative, CSS-only motion that stops for prefers-reduced-motion.
defineProps({
    // While the officer is live the night-vision glow breathes; otherwise it rests.
    live: { type: Boolean, default: false },
});
</script>

<template>
    <div class="swat-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <!-- Jet-black base, like the uniform -->
        <div class="swat-base absolute inset-0" />

        <!-- Night-vision phosphor glow from above -->
        <div class="swat-glow absolute -top-56 left-1/2 size-[42rem] -translate-x-1/2 rounded-full" />

        <!-- Vest lettering, oversized and faint -->
        <span class="swat-lettering absolute left-1/2 top-20 -translate-x-1/2 select-none font-heading font-bold uppercase leading-none">SWAT</span>

        <!-- NVG scanlines + lens vignette -->
        <div class="swat-scan absolute inset-0" />
        <div class="swat-vignette absolute inset-0" />

        <!-- Fade into the same black so text stays readable -->
        <div class="swat-fade absolute inset-0" />
    </div>
</template>

<style scoped>
.swat-base,
.swat-fade {
    --swat-black: color-mix(in oklab, var(--background) 45%, black);
}
.swat-base {
    background: var(--swat-black);
}
.swat-fade {
    background: linear-gradient(
        to bottom,
        transparent 40%,
        color-mix(in oklab, var(--swat-black) 70%, transparent) 65%,
        var(--swat-black)
    );
}

.swat-glow {
    background: var(--primary);
    filter: blur(90px);
    opacity: 0.12;
}

.swat-lettering {
    font-size: clamp(8rem, 36vw, 20rem);
    letter-spacing: 0.06em;
    color: var(--foreground);
    opacity: 0.05;
}

.swat-scan {
    background-image: repeating-linear-gradient(
        to bottom,
        color-mix(in oklab, var(--primary) 7%, transparent) 0 1px,
        transparent 1px 4px
    );
    mask-image: linear-gradient(to bottom, black, transparent 75%);
}

.swat-vignette {
    background: radial-gradient(ellipse 70% 55% at 50% 30%, transparent 45%, black 100%);
    opacity: 0.55;
}

@media (prefers-reduced-motion: no-preference) {
    .swat-scan {
        animation: swat-scan 8s linear infinite;
    }
    .is-live .swat-glow {
        animation: swat-glow 2.4s ease-in-out infinite;
    }
}

@keyframes swat-scan {
    to {
        background-position: 0 64px;
    }
}
@keyframes swat-glow {
    0%,
    100% {
        opacity: 0.1;
    }
    50% {
        opacity: 0.24;
    }
}
</style>
