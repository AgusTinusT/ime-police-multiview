<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

import { DeptBadge, LiveBadge } from "@/Components/tactical";

// Compact "up next" row for the watch page sidebar.
const props = defineProps({
    stream: { type: Object, required: true },
    live: { type: Boolean, default: false },
});

const thumb = computed(
    () => props.stream.thumbnail || `https://i.ytimg.com/vi/${props.stream.video_id}/mqdefault.jpg`,
);
const name = computed(
    () => props.stream.officer?.officer_name || props.stream.officer?.streamer_name || "Officer",
);
</script>

<template>
    <Link
        :href="`/watch/${stream.video_id}`"
        class="group flex gap-2.5 rounded-md p-1 outline-none transition-colors hover:bg-accent/50 focus-visible:ring-3 focus-visible:ring-ring/50"
    >
        <span class="relative aspect-video w-40 shrink-0 overflow-hidden rounded-md bg-muted">
            <img :src="thumb" :alt="stream.title" loading="lazy" class="size-full object-cover" />
            <LiveBadge v-if="live" class="absolute bottom-1 left-1" />
            <span
                v-else-if="stream.streamed_at"
                class="absolute bottom-1 right-1 rounded-sm bg-background/80 px-1 text-xs font-medium text-foreground"
            >{{ stream.streamed_at }}</span>
        </span>
        <span class="min-w-0 flex-1 py-0.5">
            <span class="line-clamp-2 text-sm font-semibold leading-snug text-foreground group-hover:text-primary">
                {{ stream.title }}
            </span>
            <span class="mt-1 block truncate text-xs text-muted-foreground">{{ name }}</span>
            <span class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                <DeptBadge v-if="stream.officer?.department" :dept="stream.officer.department" />
                <span v-if="live">{{ $t('watch.watching', { count: stream.viewers_count || 0 }) }}</span>
            </span>
        </span>
    </Link>
</template>
