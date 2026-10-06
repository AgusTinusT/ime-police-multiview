<script setup>
import BaseBadge from "@/Components/Base/BaseBadge.vue";

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: "",
    },
    badgeText: {
        type: String,
        default: "",
    },
    badgeVariant: {
        type: String,
        default: "default",
    },
    centered: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div :class="['space-y-3', centered ? 'text-center max-w-3xl mx-auto' : '']">
        <BaseBadge v-if="badgeText" :variant="badgeVariant" pulse>
            <span class="uppercase tracking-wider font-semibold">{{ badgeText }}</span>
        </BaseBadge>

        <h1
            class="text-2xl sm:text-4xl lg:text-5xl font-tactical font-extrabold uppercase text-slate-100 tracking-wider leading-[1.15]"
        >
            {{ title }}
        </h1>

        <p
            v-if="subtitle"
            class="text-xs sm:text-sm text-tactical-muted font-sans leading-relaxed max-w-2xl"
            :class="[centered ? 'mx-auto' : '']"
        >
            {{ subtitle }}
        </p>

        <slot />
    </div>
</template>
