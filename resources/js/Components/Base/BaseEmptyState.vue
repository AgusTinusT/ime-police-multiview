<script setup>
import BaseCard from "@/Components/Base/BaseCard.vue";

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
    <BaseCard class="text-center py-12 px-4 space-y-3">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-tactical-surface text-tactical-muted mx-auto mb-1">
            <slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </slot>
        </div>
        <h3 class="text-base font-bold text-slate-200 font-tactical uppercase tracking-wider">
            {{ title }}
        </h3>
        <p class="text-xs text-tactical-muted max-w-md mx-auto leading-relaxed">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="pt-2">
            <slot name="action" />
        </div>
    </BaseCard>
</template>
