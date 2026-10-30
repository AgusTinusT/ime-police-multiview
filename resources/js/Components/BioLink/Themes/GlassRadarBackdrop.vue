<script setup>
// Background layer for the "Default Tactical Glass" bio-link theme: the
// tactical grid, an accent glow and a slow radar sweep behind the profile.
defineProps({
    live: { type: Boolean, default: false },
});
</script>

<template>
    <div class="radar-backdrop pointer-events-none fixed inset-0 z-0 overflow-hidden" :class="{ 'is-live': live }" aria-hidden="true">
        <div class="hud-grid absolute inset-0 opacity-25" />
        <div class="absolute left-1/2 top-0 h-96 w-full max-w-2xl -translate-x-1/2 bg-linear-to-b from-primary/15 via-primary/5 to-transparent blur-3xl" />

        <!-- Radar: rings + rotating sweep, centred behind the avatar -->
        <div class="radar absolute left-1/2 top-24 size-[30rem] -translate-x-1/2 -translate-y-1/4 rounded-full sm:size-[36rem]">
            <div class="radar-ring absolute inset-0 rounded-full" />
            <div class="radar-ring absolute inset-[16%] rounded-full" />
            <div class="radar-ring absolute inset-[32%] rounded-full" />
            <div class="radar-cross absolute inset-0" />
            <div class="radar-sweep absolute inset-0 rounded-full" />
        </div>

        <div class="absolute inset-0 bg-linear-to-b from-transparent from-40% via-background/75 via-65% to-background" />
    </div>
</template>

<style scoped>
.radar-ring {
    border: 1px solid color-mix(in oklab, var(--primary) 18%, transparent);
}
.radar-cross {
    background:
        linear-gradient(to right, transparent calc(50% - 0.5px), color-mix(in oklab, var(--primary) 12%, transparent) 50%, transparent calc(50% + 0.5px)),
        linear-gradient(to bottom, transparent calc(50% - 0.5px), color-mix(in oklab, var(--primary) 12%, transparent) 50%, transparent calc(50% + 0.5px));
    mask-image: radial-gradient(circle, black 60%, transparent 71%);
}
.radar-sweep {
    background: conic-gradient(from 0deg, color-mix(in oklab, var(--primary) 22%, transparent), transparent 22%);
    opacity: 0.6;
}

@media (prefers-reduced-motion: no-preference) {
    .radar-sweep {
        animation: radar-spin 9s linear infinite;
    }
    .is-live .radar-sweep {
        animation-duration: 3s;
    }
}
@keyframes radar-spin {
    to {
        transform: rotate(1turn);
    }
}
</style>
