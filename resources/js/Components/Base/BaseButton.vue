<script setup>
// Deprecated: gunakan @/Components/ui/button (dihapus di MIG-10)
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Spinner } from "@/Components/ui/spinner";

const props = defineProps({
    href: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: "primary",
    },
    size: {
        type: String,
        default: "md",
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

const mappedVariant = computed(() => {
    switch (props.variant) {
        case "secondary":
            return "secondary";
        case "danger":
            return "destructive";
        case "ghost":
            return "ghost";
        case "outline":
        case "outline-solid":
            return "outline";
        default:
            return "default";
    }
});

const mappedSize = computed(() => {
    if (props.size === "sm") return "sm";
    if (props.size === "lg") return "lg";
    return "default";
});
</script>

<template>
    <Button
        v-if="href"
        as-child
        :variant="mappedVariant"
        :size="mappedSize"
        :disabled="disabled || loading"
    >
        <Link :href="href">
            <Spinner v-if="loading" class="size-3.5" />
            <slot />
        </Link>
    </Button>
    <Button
        v-else
        :type="type"
        :variant="mappedVariant"
        :size="mappedSize"
        :disabled="disabled || loading"
    >
        <Spinner v-if="loading" class="size-3.5" />
        <slot />
    </Button>
</template>
