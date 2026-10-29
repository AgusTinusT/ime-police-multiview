<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
    variant: {
        type: String,
        default: "panel", // 'panel' | 'hero' | 'tag'
    },
    accent: {
        type: String,
        default: "primary", // 'primary' | 'info' | 'success' | 'warning' | 'live' | 'dept-lspd' | 'dept-bcso' | 'dept-sasp' | 'dept-sapr'
    },
    as: {
        type: String,
        default: "div",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
    innerClass: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});
</script>

<template>
    <component
        :is="as"
        :class="cn('tactical-frame', props.class)"
        :data-variant="variant"
        :style="{ '--tf-color': `var(--color-${accent})` }"
    >
        <div :class="cn('tactical-frame-inner', props.innerClass)">
            <slot />
        </div>
    </component>
</template>
