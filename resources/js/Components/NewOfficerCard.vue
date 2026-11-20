<script setup>
import { ExternalLink, Sparkles } from "@lucide/vue";
import { computed } from "vue";

import { DeptBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
});

const addedLabel = computed(() => {
    const d = Number(props.officer.days_since_added) || 0;
    if (d <= 0) return "Ditambahkan hari ini";
    if (d === 1) return "Ditambahkan kemarin";
    return `Ditambahkan ${d} hari lalu`;
});
</script>

<template>
    <Card
        class="group relative w-44 shrink-0 snap-start gap-0 overflow-hidden border-primary/40 bg-primary/5 py-0 ring-1 ring-primary/20 transition-colors hover:border-primary/70 hover:bg-primary/10 sm:w-52"
    >
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-16 bg-linear-to-b from-primary/20 to-transparent"
            aria-hidden="true"
        />
        <CardContent class="relative flex h-full flex-col items-center p-4 text-center">
            <Badge class="absolute left-2 top-2 gap-1">
                <Sparkles class="size-3" /> BARU
            </Badge>

            <OfficerAvatar
                :src="officer.avatar_url"
                :name="officer.officer_name"
                :live="Boolean(officer.is_live_now)"
                size="lg"
                class="mt-4 ring-2 ring-primary/50 transition-shadow group-hover:ring-primary"
            />

            <h3 class="mt-3 w-full truncate text-sm font-semibold text-foreground" :title="officer.officer_name">
                {{ officer.officer_name }}
            </h3>
            <p v-if="officer.callsign || officer.rank" class="w-full truncate text-xs text-muted-foreground">
                {{ [officer.rank, officer.callsign].filter(Boolean).join(" · ") }}
            </p>
            <DeptBadge v-if="officer.department" :dept="officer.department" class="mt-1.5" />

            <p class="mt-3 text-xs font-medium text-primary">{{ addedLabel }}</p>

            <Button
                v-if="officer.youtube_url"
                as-child
                size="sm"
                variant="secondary"
                class="mt-4 w-full group-hover:bg-primary group-hover:text-primary-foreground"
            >
                <a :href="officer.youtube_url" target="_blank" rel="noopener">
                    <ExternalLink /> Lihat Channel
                </a>
            </Button>
        </CardContent>
    </Card>
</template>
