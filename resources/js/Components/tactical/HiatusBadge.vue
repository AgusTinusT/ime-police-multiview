<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";

const props = defineProps({
    label: {
        type: String,
        default: null,
    },
    days: {
        type: Number,
        default: 14,
    },
    class: {
        type: [String, Object, Array],
        default: "",
    },
});

const { t } = useI18n();

const displayLabel = computed(() => props.label || t("officer.hiatus_badge", "Hiatus"));
const tooltipText = computed(() => t("officer.hiatus_tooltip", { days: props.days }));
</script>

<template>
    <Badge
        variant="outline"
        :title="tooltipText"
        :class="
            cn(
                'text-muted-foreground border-muted-foreground/30 bg-muted/40 font-mono text-xs uppercase tracking-wider px-1.5 py-0',
                props.class,
            )
        "
    >
        {{ displayLabel }}
    </Badge>
</template>
