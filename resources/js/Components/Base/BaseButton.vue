<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    href: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: "primary", // 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
    },
    size: {
        type: String,
        default: "md", // 'sm' | 'md' | 'lg'
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: "button",
    },
});

const buttonClasses = computed(() => {
    const base = "inline-flex items-center justify-center gap-2 rounded-lg font-mono font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer active:scale-95 disabled:opacity-50 disabled:pointer-events-none";

    let sizeClass = "px-4 py-2 text-xs";
    if (props.size === "sm") sizeClass = "px-2.5 py-1.5 text-[11px]";
    else if (props.size === "lg") sizeClass = "px-6 py-3 text-sm";

    let variantClass = "bg-blue-600 hover:bg-blue-500 text-white shadow-md";
    if (props.variant === "secondary") {
        variantClass = "bg-tactical-panel hover:bg-tactical-surface border border-tactical-border text-slate-300 hover:text-white";
    } else if (props.variant === "danger") {
        variantClass = "bg-rose-600 hover:bg-rose-500 text-white shadow-md";
    } else if (props.variant === "outline") {
        variantClass = "bg-transparent border border-tactical-border text-slate-300 hover:border-tactical-border-light hover:text-white";
    } else if (props.variant === "ghost") {
        variantClass = "bg-transparent hover:bg-tactical-surface text-slate-400 hover:text-slate-100";
    }

    return `${base} ${sizeClass} ${variantClass}`;
});
</script>

<template>
    <Link v-if="href" :href="href" :class="buttonClasses">
        <svg
            v-if="loading"
            class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-current"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </Link>
    <button v-else :type="type" :disabled="disabled || loading" :class="buttonClasses">
        <svg
            v-if="loading"
            class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-current"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </button>
</template>
