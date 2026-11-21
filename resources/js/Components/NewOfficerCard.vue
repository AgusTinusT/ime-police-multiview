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
    // Horizontal, low-height card used when only 1-3 officers are new, so a
    // short list fills the row width without tall mostly-empty cards.
    compact: {
        type: Boolean,
        default: false,
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
        v-if="compact"
        class="group relative w-72 shrink-0 snap-start gap-0 overflow-hidden border-primary/40 bg-primary/5 py-0 ring-1 ring-primary/20 transition-colors hover:border-primary/70 hover:bg-primary/10 md:w-full"
    >
        <div
            class="pointer-events-none absolute inset-y-0 left-0 w-24 bg-linear-to-r from-primary/20 to-transparent"
            aria-hidden="true"
        />
        <CardContent class="relative flex items-center gap-3 p-3">
            <OfficerAvatar
                :src="officer.avatar_url"
                :name="officer.officer_name"
                :live="Boolean(officer.is_live_now)"
                size="lg"
                class="shrink-0 ring-2 ring-primary/50 transition-shadow group-hover:ring-primary"
            />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5">
                    <Badge class="shrink-0 gap-1"><Sparkles class="size-3" /> BARU</Badge>
                    <DeptBadge v-if="officer.department" :dept="officer.department" class="shrink-0" />
                </div>
                <h3 class="mt-1 truncate text-sm font-semibold text-foreground" :title="officer.officer_name">
                    {{ officer.officer_name }}
                </h3>
                <p class="truncate text-xs text-muted-foreground">
                    <span v-if="officer.rank || officer.callsign">{{ [officer.rank, officer.callsign].filter(Boolean).join(" · ") }} · </span>
                    <span class="font-medium text-primary">{{ addedLabel }}</span>
                </p>
            </div>
            <Button
                v-if="officer.youtube_url"
                as-child
                size="icon-sm"
                variant="secondary"
                class="shrink-0 group-hover:bg-primary group-hover:text-primary-foreground"
                :title="`Lihat channel ${officer.officer_name}`"
            >
                <a :href="officer.youtube_url" target="_blank" rel="noopener" :aria-label="`Lihat channel ${officer.officer_name}`">
                    <ExternalLink />
                </a>
            </Button>
        </CardContent>
    </Card>

    <Card
        v-else
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
