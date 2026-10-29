<script setup>
// Deprecated: gunakan @/Components/ui/card (dihapus di MIG-10)
import { computed } from "vue";
import { Card } from "@/Components/ui/card";
import { cn } from "@/lib/utils";

const props = defineProps({
    as: {
        type: String,
        default: "div",
    },
    variant: {
        type: String,
        default: "default",
    },
    padding: {
        type: String,
        default: "md",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});

const variantClass = computed(() => {
    if (props.variant === "hover") {
        return "transition-colors hover:border-input hover:bg-muted/50";
    }
    if (props.variant === "outline-solid") {
        return "bg-transparent";
    }
    if (props.variant === "flat") {
        return "bg-background";
    }
    return "";
});

const paddingClass = computed(() => {
    if (props.padding === "none") return "p-0 py-0";
    if (props.padding === "sm") return "p-3 sm:p-4";
    if (props.padding === "lg") return "p-6 sm:p-8";
    return "p-5";
});
</script>

<template>
    <Card :as="as" :class="cn(variantClass, paddingClass, props.class)">
        <slot />
    </Card>
</template>
