<script setup>
// Deprecated: gunakan @/Components/tactical EmptyState (dihapus di MIG-10)
import { EmptyState } from "@/Components/tactical";

defineProps({
    title: {
        type: String,
        default: "Tidak Ada Data Ditemukan",
    },
    description: {
        type: String,
        default: "Tidak ada informasi yang tersedia sesuai kriteria pencarian Anda.",
    },
});
</script>

<template>
    <EmptyState :title="title" :description="description">
        <template v-if="$slots.icon" #icon>
            <slot name="icon" />
        </template>
        <template v-if="$slots.action" #action>
            <slot name="action" />
        </template>
    </EmptyState>
</template>
