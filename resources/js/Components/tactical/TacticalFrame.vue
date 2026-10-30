<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    variant: {
        type: String,
        default: "panel", // 'panel' | 'hero' | 'tag'
    },
    accent: {
        type: String,
        default: "primary", // 'primary' | 'highlight' | 'info' | 'success' | 'warning' | 'live' | 'dept-lspd' | 'dept-bcso' | 'dept-sasp' | 'dept-sapr'
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

const INNER_CLASS_RE = /^(?:[a-z0-9]+:)*(?:p[xytblrse]?-\S+|space-[xy]-\S+|grid(?:-cols-\S+|-rows-\S+)?|flex(?:-col|-row|-wrap|-nowrap)?|gap-\S+|items-\S+|justify-\S+|content-\S+)$/;
const STRIP_OUTER_RE = /^(?:[a-z0-9]+:)*(?:bg-card|border(?:-border)?|rounded(?:-\S+)?)$/;

const splitClasses = computed(() => {
    const raw = cn(props.class);
    if (!raw) return { outer: "", inner: "" };
    const outer = [];
    const inner = [];
    for (const token of raw.split(/\s+/).filter(Boolean)) {
        if (INNER_CLASS_RE.test(token)) {
            inner.push(token);
        } else if (!STRIP_OUTER_RE.test(token)) {
            outer.push(token);
        }
    }
    return {
        outer: outer.join(" "),
        inner: inner.join(" "),
    };
});
</script>

<template>
    <component
        :is="as"
        :class="cn('tactical-frame', splitClasses.outer)"
        :data-variant="variant"
        :style="{ '--tf-color': `var(--${accent})` }"
    >
        <div :class="cn('tactical-frame-inner', splitClasses.inner, props.innerClass)">
            <slot />
        </div>
    </component>
</template>

