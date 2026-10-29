<script setup>
// Deprecated: gunakan @/Components/ui/badge atau @/Components/tactical (dihapus di MIG-10)
import { computed } from "vue";
import { Badge } from "@/Components/ui/badge";
import { DeptBadge } from "@/Components/tactical";
import { cn } from "@/lib/utils";

const props = defineProps({
    variant: {
        type: String,
        default: "default",
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

const isDeptVariant = computed(() =>
    ["lspd", "bcso", "sasp", "sapr"].includes((props.variant || "").toLowerCase()),
);

const mappedVariant = computed(() => {
    switch (props.variant) {
        case "blue":
            return "info";
        case "amber":
            return "warning";
        case "emerald":
            return "success";
        case "rose":
            return "destructive";
        default:
            return "secondary";
    }
});

const dotPulseClass = computed(() => {
    if (props.pulseColor) return props.pulseColor;
    if (props.variant === "amber") return "bg-warning";
    if (props.variant === "emerald") return "bg-success";
    if (props.variant === "rose") return "bg-destructive";
    return "bg-primary";
});
</script>

<template>
    <DeptBadge v-if="isDeptVariant" :dept="variant">
        <slot />
    </DeptBadge>
    <Badge v-else :variant="mappedVariant" class="font-mono">
        <span
            v-if="pulse"
            :class="cn('size-2 shrink-0 rounded-full motion-safe:animate-pulse', dotPulseClass)"
            aria-hidden="true"
        />
        <slot />
    </Badge>
</template>
