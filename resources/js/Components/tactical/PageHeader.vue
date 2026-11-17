<script setup>
import { cn } from "@/lib/utils";
import TacticalFrame from "./TacticalFrame.vue";

const props = defineProps({
    eyebrow: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    subtitle: {
        type: String,
        default: "",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});
</script>

<template>
    <div :class="cn('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', props.class)">
        <div class="space-y-2">
            <TacticalFrame v-if="eyebrow" variant="tag" accent="primary" class="inline-block w-fit">
                <span class="block px-2.5 py-0.5 font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                    {{ eyebrow }}
                </span>
            </TacticalFrame>
            <h1 class="font-heading text-3xl font-bold tracking-tight text-foreground">
                {{ title }}
            </h1>
            <p v-if="description || subtitle" class="max-w-2xl text-sm text-muted-foreground">
                {{ description || subtitle }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

