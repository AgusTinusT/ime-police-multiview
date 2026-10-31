<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
    size: {
        type: String,
        default: "2xl",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const sizeClasses = {
    sm: "max-w-screen-sm mx-auto",
    md: "max-w-3xl mx-auto",
    lg: "max-w-5xl mx-auto",
    xl: "max-w-7xl mx-auto",
    "2xl": "max-w-(--breakpoint-2xl) mx-auto",
    full: "max-w-none w-full",
    fluid: "max-w-none w-full",
};
</script>

<template>
    <div
        :class="
            cn(
                'w-full px-4 sm:px-6 lg:px-8',
                sizeClasses[props.size] || sizeClasses['2xl'],
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>

