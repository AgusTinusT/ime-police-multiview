<script setup>
import { Link } from "@inertiajs/vue3";
import { Check, ExternalLink, Play, Plus, Radio } from "@lucide/vue";
import { computed } from "vue";

import { DeptBadge, LiveBadge, OfficerAvatar } from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";

// Detail for one stream: who it is, and which other units share its TAC channel.
const props = defineProps({
    stream: { type: Object, default: null },
    streams: { type: Array, default: () => [] },
    tacChannels: { type: Array, default: () => [] },
    inTray: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "toggle-tray"]);

const open = computed({
    get: () => !!props.stream,
    set: (value) => !value && emit("close"),
});

const officer = computed(() => props.stream?.officer || {});
const isLive = computed(() => props.streams.some((s) => s.video_id === props.stream?.video_id));
const thumb = (s) => s.thumbnail || `https://i.ytimg.com/vi/${s.video_id}/hqdefault.jpg`;
const mvUrl = (ids) => `/multiview?v=${ids.filter(Boolean).join(",")}`;

const incident = computed(() => {
    const id = String(props.stream?.video_id || "");
    const channel = props.tacChannels.find((c) => (c.video_ids || []).map(String).includes(id));
    if (!channel) return null;
    const byId = Object.fromEntries(props.streams.map((s) => [String(s.video_id), s]));
    const units = (channel.video_ids || []).map(String).map((v) => byId[v]).filter(Boolean);
    return { name: channel.name, units, others: units.filter((u) => String(u.video_id) !== id) };
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent v-if="stream" class="max-h-[90dvh] gap-0 overflow-y-auto p-0 sm:max-w-2xl">
            <div class="relative aspect-video">
                <img :src="thumb(stream)" :alt="stream.title" class="size-full object-cover" />
                <div class="absolute inset-0 bg-linear-to-t from-background via-background/30 to-transparent" />
                <LiveBadge v-if="isLive" :viewers="stream.viewers_count" class="absolute left-4 top-4" />
                <div class="absolute inset-x-4 bottom-4 flex flex-wrap gap-2">
                    <Button as-child>
                        <Link :href="mvUrl([stream.video_id])"><Play /> {{ $t('officer_detail.watch') }}</Link>
                    </Button>
                    <Button variant="secondary" :aria-pressed="inTray" @click="emit('toggle-tray', stream)">
                        <Check v-if="inTray" />
                        <Plus v-else />
                        {{ inTray ? $t('officer_detail.in_multiview') : $t('officer_detail.multiview') }}
                    </Button>
                    <Button as-child variant="outline">
                        <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" rel="noopener">
                            <ExternalLink /> YouTube
                        </a>
                    </Button>
                </div>
            </div>

            <div class="space-y-6 p-4 sm:p-6">
                <DialogHeader class="text-left">
                    <DialogTitle class="line-clamp-2 text-xl">{{ stream.title }}</DialogTitle>
                    <DialogDescription class="flex flex-wrap items-center gap-2">
                        <DeptBadge v-if="officer.department" :dept="officer.department" />
                        <span>{{ [officer.rank, officer.callsign].filter(Boolean).join(" · ") }}</span>
                    </DialogDescription>
                </DialogHeader>

                <div class="flex items-center gap-3">
                    <OfficerAvatar :src="officer.avatar_url" :name="officer.officer_name || officer.streamer_name" :live="isLive" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-foreground">{{ officer.officer_name || officer.streamer_name }}</p>
                        <p v-if="officer.subscriber_count_text" class="text-xs text-muted-foreground">{{ officer.subscriber_count_text }}</p>
                    </div>
                </div>

                <div v-if="incident && incident.others.length">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <Radio class="size-4 text-warning" /> {{ $t('officer_detail.other_units_in_tac', { name: incident.name }) }}
                        </h3>
                        <Button as-child variant="link" size="sm" class="px-0">
                            <Link :href="mvUrl(incident.units.map((u) => u.video_id))">{{ $t('officer_detail.watch_povs_together', { count: incident.units.length }) }}</Link>
                        </Button>
                    </div>
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <li v-for="u in incident.others" :key="u.video_id">
                            <Link :href="mvUrl([stream.video_id, u.video_id])" class="group block rounded-md outline-none focus-visible:ring-3 focus-visible:ring-ring/50">
                                <img :src="thumb(u)" :alt="u.title" loading="lazy" class="aspect-video w-full rounded-md object-cover" />
                                <p class="mt-1.5 truncate text-xs font-semibold text-foreground group-hover:text-primary">{{ u.officer?.officer_name }}</p>
                            </Link>
                        </li>
                    </ul>
                </div>
                <p v-else class="text-sm text-muted-foreground">{{ $t('officer_detail.no_tac_channel') }}</p>
            </div>
        </DialogContent>
    </Dialog>
</template>
