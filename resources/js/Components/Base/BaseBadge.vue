<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: {
        type: String,
        default: "default", // 'default' | 'blue' | 'amber' | 'emerald' | 'rose' | 'lspd' | 'bcso' | 'sasp' | 'sapr'
    },
    pulse: {
        type: Boolean,
        default: false,
    },
    pulseColor: {
        type: String,
        default: "",
    },
});

const badgeClasses = computed(() => {
    const base = "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-semibold transition-all";
    
    switch (props.variant) {
        case "blue":
            return `${base} bg-blue-500/10 text-blue-400 border border-blue-500/30`;
        case "amber":
            return `${base} bg-amber-500/10 text-amber-400 border border-amber-500/30`;
        case "emerald":
            return `${base} bg-emerald-500/10 text-emerald-400 border border-emerald-500/30`;
        case "rose":
            return `${base} bg-rose-500/10 text-rose-400 border border-rose-500/30`;
        case "lspd":
            return `${base} bg-dept-lspd/20 text-blue-300 border border-dept-lspd/40`;
        case "bcso":
            return `${base} bg-dept-bcso/20 text-amber-300 border border-dept-bcso/40`;
        case "sasp":
            return `${base} bg-dept-sasp/20 text-slate-300 border border-dept-sasp/40`;
        case "sapr":
            return `${base} bg-dept-sapr/20 text-emerald-300 border border-dept-sapr/40`;
        default:
            return `${base} bg-tactical-base text-slate-300 border border-tactical-border`;
    }
});

const dotPulseClass = computed(() => {
    if (props.pulseColor) return props.pulseColor;
    if (props.variant === "blue" || props.variant === "lspd") return "bg-blue-400";
    if (props.variant === "amber" || props.variant === "bcso") return "bg-amber-400";
    if (props.variant === "emerald" || props.variant === "sapr") return "bg-emerald-400";
    if (props.variant === "rose") return "bg-rose-400";
    return "bg-blue-400";
});
</script>

<template>
    <span :class="badgeClasses">
        <span
            v-if="pulse"
            class="w-2 h-2 rounded-full shrink-0 animate-pulse"
            :class="dotPulseClass"
        ></span>
        <slot />
    </span>
</template>
