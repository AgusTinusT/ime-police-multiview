<script setup>
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/Components/ui/empty";
import { Inbox } from "@lucide/vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    icon: {
        type: [Object, Function],
        default: null,
    },
    title: {
        type: String,
        default: "Tidak Ada Data Ditemukan",
    },
    description: {
        type: String,
        default: "Tidak ada informasi yang tersedia sesuai kriteria pencarian Anda.",
    },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: "",
    },
});
</script>

<template>
    <Empty :class="cn('border border-dashed border-border bg-card/50 py-12', props.class)">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <slot name="icon">
                    <component :is="icon || Inbox" class="size-5 text-muted-foreground" aria-hidden="true" />
                </slot>
            </EmptyMedia>
            <EmptyTitle class="font-heading text-lg font-bold tracking-wide text-foreground">
                {{ title }}
            </EmptyTitle>
            <EmptyDescription v-if="description" class="text-xs sm:text-sm text-muted-foreground">
                {{ description }}
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent v-if="$slots.action">
            <slot name="action" />
        </EmptyContent>
    </Empty>
</template>

