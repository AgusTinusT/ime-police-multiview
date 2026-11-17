<script setup>
import { computed } from "vue";

import { DeptBadge } from "@/Components/tactical";
import { cn } from "@/lib/utils";

// Quick POV switcher for Solo mode: thumbnails only (no extra players), so
// jumping to another officer during an incident costs one click.
const props = defineProps({
    streams: { type: Array, default: () => [] },
    currentId: { type: String, default: null },
    allowedOfficerIds: { type: Array, default: null },
});

const emit = defineEmits(["select"]);

const filteredStreams = computed(() => {
    if (!props.allowedOfficerIds || !props.allowedOfficerIds.length) {
        return props.streams;
    }
    const allowed = new Set(props.allowedOfficerIds.map(Number));
    return props.streams.filter((s) => allowed.has(Number(s.officer?.id || s.officer_id)));
});

const items = computed(() =>
    [...filteredStreams.value].sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0)),
);

defineExpose({ items });

const thumb = (s) => s.thumbnail || `https://i.ytimg.com/vi/${s.video_id}/mqdefault.jpg`;
</script>

<template>
    <nav aria-label="Ganti POV" class="shrink-0 border-t border-border bg-background">
        <ul class="no-scrollbar flex gap-2 overflow-x-auto px-2 py-2 sm:px-3">
            <li v-for="(s, i) in items" :key="s.video_id" class="shrink-0">
                <button
                    type="button"
                    :aria-current="s.video_id === currentId ? 'true' : undefined"
                    :class="cn(
                        'group block w-32 rounded-md text-left outline-none focus-visible:ring-3 focus-visible:ring-ring/50 sm:w-36',
                        s.video_id === currentId && 'pointer-events-none',
                    )"
                    @click="emit('select', s.video_id)"
                >
                    <span
                        :class="cn(
                            'relative block aspect-video overflow-hidden rounded-md bg-muted ring-2 transition-shadow',
                            s.video_id === currentId ? 'ring-primary' : 'ring-transparent group-hover:ring-border',
                        )"
                    >
                        <img :src="thumb(s)" :alt="s.title" loading="lazy" class="size-full object-cover" />
                        <span
                            v-if="i < 9"
                            class="absolute left-1 top-1 flex size-5 items-center justify-center rounded-sm bg-background/80 font-mono text-xs font-bold"
                        >{{ i + 1 }}</span>
                        <span
                            v-if="s.video_id === currentId"
                            class="absolute inset-x-0 bottom-0 bg-primary py-0.5 text-center text-xs font-bold text-primary-foreground"
                        >Ditonton</span>
                    </span>
                    <span class="mt-1 flex items-center gap-1">
                        <DeptBadge v-if="s.officer?.department" :dept="s.officer.department" class="shrink-0" />
                        <span class="truncate text-xs font-semibold text-foreground">{{ s.officer?.officer_name || s.title }}</span>
                    </span>
                </button>
            </li>
        </ul>
    </nav>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>
