<script setup>
import { computed } from "vue";
import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";

const props = defineProps({
    viewers: {
        type: [Number, String],
        default: null,
    },
    label: {
        type: String,
        default: "LIVE",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const compactFormatter = new Intl.NumberFormat("id-ID", {
    notation: "compact",
    maximumFractionDigits: 1,
});

const formattedViewers = computed(() => {
    if (props.viewers === null || props.viewers === undefined || props.viewers === "") {
        return "";
    }
    const num = Number(props.viewers);
    if (Number.isNaN(num)) {
        return String(props.viewers);
    }
    return compactFormatter.format(num);
});
</script>

<template>
    <Badge variant="live" :class="cn('font-mono text-xs font-semibold uppercase tracking-wider', props.class)">
        <span class="size-1.5 shrink-0 rounded-full bg-live-foreground motion-safe:animate-pulse" aria-hidden="true" />
        <span><slot>{{ label || "LIVE" }}</slot></span>
        <span v-if="formattedViewers" class="tabular-nums">• {{ formattedViewers }}</span>
    </Badge>
</template>

