<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
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
    <div :class="cn('flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between', props.class)">
        <div class="space-y-1">
            <h2 class="font-heading text-2xl font-semibold tracking-tight text-foreground">
                {{ title }}
            </h2>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
