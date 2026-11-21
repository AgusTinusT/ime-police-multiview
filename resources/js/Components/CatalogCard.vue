<script setup>
import { Link } from "@inertiajs/vue3";
import { EyeOff, Info } from "@lucide/vue";
import { computed } from "vue";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { DeptBadge, LiveBadge, HiatusBadge } from "@/Components/tactical";
import { cn } from "@/lib/utils";

const props = defineProps({
    stream: { type: Object, required: true },
    href: { type: String, required: true },
    kind: { type: String, default: "live" }, // 'live' | 'replay'
    size: { type: String, default: "md" }, // 'md' | 'sm'
});

const emit = defineEmits(["detail", "hide"]);

const thumb = computed(
    () => props.stream.thumbnail || `https://i.ytimg.com/vi/${props.stream.video_id}/hqdefault.jpg`,
);
const name = computed(
    () => props.stream.officer?.officer_name || props.stream.officer?.streamer_name || "Officer",
);
const dept = computed(() => props.stream.officer?.department || "");
</script>

<template>
    <Link
        :href="href"
        :class="cn(
            'catalog-card group relative block shrink-0 snap-start rounded-md outline-none focus-visible:ring-3 focus-visible:ring-ring/50',
            size === 'sm' ? 'w-52 sm:w-64' : 'w-60 sm:w-72',
        )"
    >
        <div class="relative aspect-video overflow-hidden rounded-md bg-muted">
            <img :src="thumb" :alt="stream.title" loading="lazy" class="size-full object-cover" />

            <div class="absolute left-2 top-2">
                <LiveBadge v-if="kind === 'live'" />
            </div>
            <Badge
                v-if="kind === 'replay' && stream.streamed_at"
                variant="secondary"
                class="absolute bottom-2 right-2"
            >
                {{ stream.streamed_at }}
            </Badge>

            <div class="absolute right-2 top-2 flex gap-1.5">
                <Button
                    size="icon-sm"
                    variant="secondary"
                    class="rounded-full bg-background/70 hover:bg-destructive hover:text-destructive-foreground opacity-0 group-hover:opacity-100 transition-opacity"
                    :aria-label="$t('catalog.hide_officer')"
                    :title="$t('catalog.hide_officer')"
                    @click.prevent.stop="emit('hide', stream)"
                >
                    <EyeOff class="size-3.5" />
                </Button>
                <Button
                    size="icon-sm"
                    variant="secondary"
                    class="rounded-full bg-background/70 hover:bg-background"
                    :aria-label="$t('catalog.detail_officer_aria')"
                    @click.prevent.stop="emit('detail', stream)"
                >
                    <Info class="size-3.5" />
                </Button>
            </div>
        </div>

        <div class="px-1 pt-2">
            <p class="line-clamp-1 text-sm font-semibold text-foreground">
                {{ kind === "replay" ? stream.title : name }}
            </p>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                <DeptBadge v-if="dept" :dept="dept" />
                <HiatusBadge v-if="stream.officer?.is_hiatus" />
                <span class="truncate">
                    <template v-if="kind === 'replay'">{{ name }}</template>
                    <template v-else-if="kind === 'live'">{{ $t('catalog.watching_title', { count: stream.viewers_count || 0, title: stream.title }) }}</template>
                    <template v-else>{{ stream.title }}</template>
                </span>
            </div>
        </div>
    </Link>
</template>

<style scoped>
.catalog-card {
    transition: transform 0.25s ease 0.15s;
}
.catalog-card img {
    transition: box-shadow 0.25s ease 0.15s;
}
@media (hover: hover) and (prefers-reduced-motion: no-preference) {
    .catalog-card:hover {
        z-index: 10;
        transform: scale(1.06);
    }
    .catalog-card:hover img {
        box-shadow: 0 12px 30px color-mix(in oklab, var(--color-background) 80%, transparent);
    }
}
.catalog-card:focus-visible {
    z-index: 10;
}
</style>
