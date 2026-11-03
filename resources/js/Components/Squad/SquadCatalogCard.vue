<script setup>
import { Link } from "@inertiajs/vue3";
import { GitFork, Pencil, Trash2, Users } from "@lucide/vue";
import { computed } from "vue";

import { LiveBadge, OfficerAvatar } from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import { cn } from "@/lib/utils";

const props = defineProps({
    squad: { type: Object, required: true },
    // Current live streams, used to show what the squad's members are broadcasting right now.
    liveStreams: { type: Array, default: () => [] },
});

const emit = defineEmits(["fork", "edit", "delete"]);

const officers = computed(() => props.squad.officers || []);

const liveThumbs = computed(() => {
    const byOfficer = new Map(props.liveStreams.map((s) => [Number(s.officer?.id), s]));
    return officers.value
        .map((o) => byOfficer.get(Number(o.id)))
        .filter(Boolean)
        .slice(0, 4)
        .map((s) => s.thumbnail || `https://i.ytimg.com/vi/${s.video_id}/mqdefault.jpg`);
});

const avatarOfficers = computed(() => officers.value.slice(0, 5));
const overflowCount = computed(() => Math.max(0, officers.value.length - 5));
</script>

<template>
    <Link
        :href="`/multiview?squad=${squad.slug}`"
        class="squad-card group relative block w-64 shrink-0 snap-start rounded-md outline-none focus-visible:ring-3 focus-visible:ring-ring/50 sm:w-72"
    >
        <div class="relative aspect-video overflow-hidden rounded-md bg-muted">
            <!-- What the squad is broadcasting right now -->
            <div
                v-if="liveThumbs.length"
                :class="cn('grid size-full gap-0.5', liveThumbs.length > 1 && 'grid-cols-2', liveThumbs.length > 2 && 'grid-rows-2')"
            >
                <img
                    v-for="(src, i) in liveThumbs"
                    :key="src"
                    :src="src"
                    alt=""
                    loading="lazy"
                    :class="cn('size-full min-h-0 object-cover', liveThumbs.length === 3 && i === 0 && 'row-span-2')"
                />
            </div>
            <!-- Nobody live: show who is in the squad -->
            <div v-else class="flex size-full items-center justify-center -space-x-2">
                <OfficerAvatar
                    v-for="o in avatarOfficers"
                    :key="o.id"
                    :src="o.avatar_url"
                    :name="o.officer_name"
                    size="md"
                    class="ring-2 ring-muted"
                />
            </div>

            <div class="absolute inset-0 bg-linear-to-t from-background/90 via-background/10 to-transparent" />

            <div class="absolute left-2 top-2">
                <LiveBadge v-if="squad.live_count > 0" :label="`${squad.live_count} LIVE`" />
            </div>
            <div class="absolute right-2 top-2 flex items-center gap-1">
                <Button
                    v-if="squad.is_owner"
                    size="icon-sm"
                    variant="secondary"
                    class="rounded-full bg-background/70 hover:bg-primary hover:text-primary-foreground"
                    :aria-label="$t('squad.edit_button')"
                    :title="$t('squad.edit_button')"
                    @click.prevent.stop="emit('edit', squad)"
                >
                    <Pencil class="size-3.5" />
                </Button>
                <Button
                    v-if="squad.is_owner"
                    size="icon-sm"
                    variant="secondary"
                    class="rounded-full bg-background/70 hover:bg-destructive hover:text-destructive-foreground"
                    :aria-label="$t('squad.delete_button')"
                    :title="$t('squad.delete_button')"
                    @click.prevent.stop="emit('delete', squad)"
                >
                    <Trash2 class="size-3.5" />
                </Button>
                <Button
                    v-if="!squad.is_owner"
                    size="icon-sm"
                    variant="secondary"
                    class="rounded-full bg-background/70 hover:bg-primary hover:text-primary-foreground"
                    :aria-label="$t('squad.fork_button')"
                    :title="$t('squad.fork_button')"
                    @click.prevent.stop="emit('fork', squad)"
                >
                    <GitFork class="size-3.5" />
                </Button>
            </div>

            <div class="absolute inset-x-2 bottom-2 flex items-end justify-between gap-2">
                <p class="truncate text-base font-bold tracking-tight text-foreground">{{ squad.name }}</p>
                <div v-if="liveThumbs.length" class="flex shrink-0 -space-x-1.5">
                    <OfficerAvatar
                        v-for="o in avatarOfficers.slice(0, 3)"
                        :key="o.id"
                        :src="o.avatar_url"
                        :name="o.officer_name"
                        size="sm"
                        class="ring-2 ring-background"
                    />
                </div>
            </div>
        </div>

        <div class="px-1 pt-2">
            <p class="truncate text-xs text-muted-foreground">
                <span class="inline-flex items-center gap-1"><Users class="size-3" /> {{ officers.length }}</span>
                <span v-if="overflowCount"> (+{{ overflowCount }})</span>
                · {{ squad.owner_name }}
                <span v-if="squad.forks_count"> · <GitFork class="inline size-3" /> {{ squad.forks_count }}</span>
            </p>
        </div>
    </Link>
</template>

<style scoped>
.squad-card {
    transition: transform 0.25s ease 0.15s;
}
@media (hover: hover) and (prefers-reduced-motion: no-preference) {
    .squad-card:hover {
        z-index: 10;
        transform: scale(1.06);
    }
}
</style>
