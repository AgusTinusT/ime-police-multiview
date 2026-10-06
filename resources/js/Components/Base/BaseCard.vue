<script setup>
import { computed } from "vue";

const props = defineProps({
    as: {
        type: String,
        default: "div",
    },
    variant: {
        type: String,
        default: "default", // 'default' | 'hover' | 'outline' | 'flat'
    },
    padding: {
        type: String,
        default: "md", // 'none' | 'sm' | 'md' | 'lg'
    },
});

const cardClasses = computed(() => {
    const base = "rounded-xl transition-all duration-200";
    
    let variantClass = "bg-tactical-panel border border-tactical-border";
    if (props.variant === "hover") {
        variantClass = "bg-tactical-panel border border-tactical-border hover:border-tactical-border-light hover:bg-tactical-surface/50";
    } else if (props.variant === "outline") {
        variantClass = "bg-transparent border border-tactical-border";
    } else if (props.variant === "flat") {
        variantClass = "bg-tactical-base border border-tactical-border";
    }

    let paddingClass = "p-5";
    if (props.padding === "none") paddingClass = "p-0";
    else if (props.padding === "sm") paddingClass = "p-3 sm:p-4";
    else if (props.padding === "lg") paddingClass = "p-6 sm:p-8";

    return `${base} ${variantClass} ${paddingClass}`;
});
</script>

<template>
    <component :is="as" :class="cardClasses">
        <slot />
    </component>
</template>
