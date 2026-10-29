<script setup>
import { computed } from "vue";
import { Card, CardContent } from "@/Components/ui/card";
import { cn } from "@/lib/utils";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    trend: {
        type: Number,
        default: null,
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const formattedTrend = computed(() => {
    if (props.trend === null || props.trend === undefined) {
        return "";
    }
    const sign = props.trend > 0 ? "+" : "";
    return `${sign}${props.trend}%`;
});
</script>

<template>
    <Card :class="cn('py-4', props.class)">
        <CardContent class="space-y-2 px-5">
            <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    {{ label }}
                </span>
                <component :is="icon" v-if="icon" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <span class="font-mono text-2xl font-bold tabular-nums text-foreground">
                    {{ value }}
                </span>
                <span
                    v-if="trend !== null && trend !== undefined"
                    :class="cn('font-mono text-xs font-semibold tabular-nums', trend >= 0 ? 'text-success' : 'text-destructive')"
                >
                    {{ formattedTrend }}
                </span>
            </div>
        </CardContent>
    </Card>
</template>
