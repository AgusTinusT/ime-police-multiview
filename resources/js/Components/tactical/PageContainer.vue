<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});
</script>

<template>
    <div :class="cn('mx-auto w-full max-w-(--breakpoint-2xl) px-4 sm:px-6 lg:px-8', props.class)">
        <slot />
    </div>
</template>
