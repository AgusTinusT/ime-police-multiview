<script setup>
// Deprecated: gunakan @/Components/tactical PageHeader/SectionHeader (dihapus di MIG-10)
import { PageHeader } from "@/Components/tactical";
import { cn } from "@/lib/utils";

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: "",
    },
    badgeText: {
        type: String,
        default: "",
    },
    badgeVariant: {
        type: String,
        default: "default",
    },
    centered: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div :class="cn('space-y-3', centered && 'mx-auto max-w-3xl text-center')">
        <PageHeader
            :eyebrow="badgeText"
            :title="title"
            :description="subtitle"
            :class="cn(centered && 'sm:flex-col sm:items-center text-center')"
        />
        <slot />
    </div>
</template>
