<script setup>
import { Link } from "@inertiajs/vue3";
import { Heart, Play } from "@lucide/vue";

import { cn } from "@/lib/utils";
import { formatClipTime } from "@/Utils/clipTime";

// momen rows for the watch page. Clips of the current video seek the player
// in place; clips of other videos link to their own watch page.
defineProps({
    clips: { type: Array, default: () => [] },
    sameVideo: { type: Boolean, default: true },
    activeId: { type: [Number, String], default: null },
});

const emit = defineEmits(["seek"]);
</script>

<template>
    <ul class="space-y-1">
        <li v-for="c in clips" :key="c.id">
            <component
                :is="sameVideo ? 'button' : Link"
                v-bind="sameVideo ? { type: 'button' } : { href: `/watch/${c.video_id}?t=${c.start_seconds}&clip=${c.id}` }"
                :class="cn(
                    'group flex w-full items-start gap-2.5 rounded-md p-2 text-left outline-none transition-colors hover:bg-accent/50 focus-visible:ring-3 focus-visible:ring-ring/50',
                    activeId != null && Number(activeId) === c.id && 'bg-primary/10 ring-1 ring-primary/40',
                )"
                @click="sameVideo && emit('seek', c)"
            >
                <img
                    v-if="!sameVideo"
                    :src="`https://i.ytimg.com/vi/${c.video_id}/mqdefault.jpg`"
                    :alt="c.title"
                    loading="lazy"
                    class="aspect-video w-28 shrink-0 rounded-md bg-muted object-cover"
                />
                <span
                    v-else
                    class="flex shrink-0 items-center gap-1 rounded-sm bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold text-foreground group-hover:bg-primary group-hover:text-primary-foreground"
                >
                    <Play class="size-3" />
                    {{ formatClipTime(c.start_seconds) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="line-clamp-2 text-sm font-medium leading-snug text-foreground">{{ c.title }}</span>
                    <span class="mt-0.5 flex items-center gap-1.5 truncate text-xs text-muted-foreground">
                        <span class="font-mono">{{ formatClipTime(c.end_seconds - c.start_seconds) }}</span>
                        <span>·</span>
                        <span class="truncate">{{ $t('watch_clips.by', { name: c.creator_name }) }}</span>
                        <span v-if="c.likes_count" class="inline-flex items-center gap-0.5">
                            · <Heart class="size-3" /> {{ c.likes_count }}
                        </span>
                    </span>
                </span>
            </component>
        </li>
    </ul>
</template>
