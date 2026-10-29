<script setup>
import { computed, ref } from "vue";
import { DeptBadge, LiveBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { cn } from "@/lib/utils";
import { Check, Eye, Search } from "@lucide/vue";

const props = defineProps({
    streams: { type: Array, default: () => [] },
    offlineOfficers: { type: Array, default: () => [] },
    selectedIds: { type: Array, default: () => [] },
    maxTiles: { type: Number, default: 9 },
});

const emit = defineEmits(["toggle"]);

const DEPARTMENTS = ["ALL", "LSPD", "BCSO", "SASP", "SAPR"];

const tab = ref("live");
const search = ref("");
const dept = ref("ALL");

const deptOf = (item) => {
    const d = (item.officer?.department || item.department || "").toUpperCase();
    return d === "PARK RANGER" ? "SAPR" : d;
};

const matches = (item) => {
    if (dept.value !== "ALL" && deptOf(item) !== dept.value) return false;
    const q = search.value.trim().toLowerCase();
    if (!q) return true;
    const off = item.officer || item;
    return [
        item.title,
        off.officer_name,
        off.streamer_name,
        off.callsign,
        off.handle,
        off.badge_number,
    ]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(q));
};

const filteredStreams = computed(() => props.streams.filter(matches));
const filteredOffline = computed(() => props.offlineOfficers.filter(matches));

const isFull = computed(() => props.selectedIds.length >= props.maxTiles);
const isSelected = (id) => props.selectedIds.includes(id);

const formatViewers = (n) => {
    const v = Number(n) || 0;
    return v >= 1000 ? `${(v / 1000).toFixed(1)}K` : String(v);
};
</script>

<template>
    <div class="flex flex-col min-h-0 h-full">
        <!-- Search & filters -->
        <div class="p-3 space-y-2.5 border-b border-border">
            <div
                class="flex gap-1 p-1 rounded-lg bg-background border border-border"
            >
                <Button
                    v-for="t in [
                        { key: 'live', label: `10-8 Live (${streams.length})` },
                        {
                            key: 'offline',
                            label: `10-7 Off (${offlineOfficers.length})`,
                        },
                    ]"
                    :key="t.key"
                    type="button"
                    :variant="tab === t.key ? 'default' : 'ghost'"
                    size="sm"
                    class="flex-1 h-7 text-xs font-bold tracking-wide"
                    @click="tab = t.key"
                >
                    {{ t.label }}
                </Button>
            </div>

            <div class="relative">
                <Search
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Cari officer, callsign, judul..."
                    class="pl-8 pr-3 h-9 text-xs bg-background"
                />
            </div>

            <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1">
                <Button
                    v-for="d in DEPARTMENTS"
                    :key="d"
                    type="button"
                    :variant="dept === d ? 'default' : 'outline'"
                    size="sm"
                    class="shrink-0 h-7 px-2.5 rounded-full text-xs font-bold"
                    @click="dept = d"
                >
                    {{ d === "ALL" ? "Semua" : d }}
                </Button>
            </div>
        </div>

        <!-- Live list -->
        <ul
            v-if="tab === 'live'"
            class="flex-1 min-h-0 overflow-y-auto p-2 space-y-1"
        >
            <li v-for="s in filteredStreams" :key="s.video_id">
                <button
                    type="button"
                    :class="
                        cn(
                            'w-full flex gap-2.5 p-1.5 rounded-lg text-left transition group cursor-pointer',
                            isSelected(s.video_id)
                                ? 'bg-primary/15 ring-1 ring-primary/50'
                                : 'hover:bg-accent',
                            !isSelected(s.video_id) && isFull
                                ? 'opacity-50 cursor-not-allowed'
                                : '',
                        )
                    "
                    :disabled="!isSelected(s.video_id) && isFull"
                    @click="emit('toggle', s)"
                >
                    <div
                        class="relative shrink-0 w-28 aspect-video rounded-md overflow-hidden bg-background"
                    >
                        <img
                            :src="
                                s.thumbnail ||
                                `https://i.ytimg.com/vi/${s.video_id}/mqdefault.jpg`
                            "
                            :alt="s.title"
                            loading="lazy"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute bottom-1 left-1">
                            <LiveBadge />
                        </div>
                        <div
                            v-if="isSelected(s.video_id)"
                            class="absolute inset-0 bg-primary/50 flex items-center justify-center"
                        >
                            <Check class="size-6 text-primary-foreground" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1 py-0.5">
                        <p
                            class="text-xs font-semibold text-foreground leading-snug line-clamp-2"
                        >
                            {{ s.title }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground truncate">
                            {{
                                s.officer?.officer_name ||
                                s.officer?.streamer_name
                            }}
                        </p>
                        <div class="mt-1 flex items-center gap-1.5 text-xs">
                            <DeptBadge v-if="deptOf(s)" :dept="deptOf(s)" />
                            <span
                                class="font-mono text-muted-foreground truncate"
                            >
                                {{ s.officer?.callsign }}
                            </span>
                            <span
                                class="ml-auto text-muted-foreground shrink-0 inline-flex items-center gap-1"
                            >
                                <span>{{ formatViewers(s.viewers_count) }}</span>
                                <Eye class="size-3" />
                            </span>
                        </div>
                    </div>
                </button>
            </li>
            <li
                v-if="!filteredStreams.length"
                class="py-10 text-center text-sm text-muted-foreground"
            >
                Tidak ada siaran live yang cocok.
            </li>
        </ul>

        <!-- Offline roster -->
        <ul v-else class="flex-1 min-h-0 overflow-y-auto p-2 space-y-1">
            <li v-for="o in filteredOffline" :key="o.id">
                <a
                    :href="
                        o.channel_id
                            ? `https://www.youtube.com/channel/${o.channel_id}`
                            : '#'
                    "
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-accent transition"
                >
                    <OfficerAvatar
                        :src="o.avatar_url"
                        :name="o.officer_name || o.streamer_name"
                        size="md"
                        class="grayscale"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm font-semibold text-foreground/80 truncate"
                        >
                            {{ o.officer_name || o.streamer_name }}
                        </p>
                        <p class="text-xs text-muted-foreground truncate">
                            {{ deptOf(o) }} ·
                            <span class="font-mono">{{ o.callsign }}</span>
                        </p>
                    </div>
                    <Badge variant="outline" class="font-mono text-xs">
                        10-7
                    </Badge>
                </a>
            </li>
            <li
                v-if="!filteredOffline.length"
                class="py-10 text-center text-sm text-muted-foreground"
            >
                Tidak ada officer offline yang cocok.
            </li>
        </ul>

        <p class="px-3 py-2 border-t border-border text-xs text-muted-foreground">
            {{ selectedIds.length }}/{{ maxTiles }} POV dipilih
            <span v-if="isFull" class="text-warning">· batas tercapai</span>
        </p>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>
