<script setup>
import { computed, ref } from "vue";

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
    return [item.title, off.officer_name, off.streamer_name, off.callsign, off.handle, off.badge_number]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(q));
};

const filteredStreams = computed(() => props.streams.filter(matches));
const filteredOffline = computed(() => props.offlineOfficers.filter(matches));

const isFull = computed(() => props.selectedIds.length >= props.maxTiles);
const isSelected = (id) => props.selectedIds.includes(id);

const deptBadge = {
    LSPD: "bg-dept-lspd/20 text-blue-300 border-blue-500/30",
    BCSO: "bg-dept-bcso/20 text-amber-300 border-amber-500/30",
    SASP: "bg-slate-500/20 text-slate-200 border-slate-400/30",
    SAPR: "bg-dept-sapr/20 text-emerald-300 border-emerald-500/30",
};

const formatViewers = (n) => {
    const v = Number(n) || 0;
    return v >= 1000 ? `${(v / 1000).toFixed(1)}K` : String(v);
};
</script>

<template>
    <div class="flex flex-col min-h-0 h-full">
        <!-- Search & filters -->
        <div class="p-3 space-y-2.5 border-b border-tactical-border">
            <div class="flex gap-1 p-1 rounded-lg bg-tactical-base border border-tactical-border">
                <button
                    v-for="t in [
                        { key: 'live', label: `10-8 Live (${streams.length})` },
                        { key: 'offline', label: `10-7 Off (${offlineOfficers.length})` },
                    ]"
                    :key="t.key"
                    type="button"
                    class="flex-1 py-1.5 rounded-md text-xs font-bold tracking-wide transition"
                    :class="tab === t.key ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                    @click="tab = t.key"
                >
                    {{ t.label }}
                </button>
            </div>

            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
                </svg>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari officer, callsign, judul..."
                    class="w-full pl-8 pr-3 py-2 text-sm rounded-lg bg-tactical-base border-tactical-border text-slate-100 placeholder-slate-500 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1">
                <button
                    v-for="d in DEPARTMENTS"
                    :key="d"
                    type="button"
                    class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-bold border transition"
                    :class="dept === d
                        ? 'bg-slate-100 text-slate-900 border-slate-100'
                        : 'bg-tactical-base text-slate-400 border-tactical-border hover:text-slate-200'"
                    @click="dept = d"
                >
                    {{ d === "ALL" ? "Semua" : d }}
                </button>
            </div>
        </div>

        <!-- Live list -->
        <ul v-if="tab === 'live'" class="flex-1 min-h-0 overflow-y-auto p-2 space-y-1">
            <li v-for="s in filteredStreams" :key="s.video_id">
                <button
                    type="button"
                    class="w-full flex gap-2.5 p-1.5 rounded-lg text-left transition group"
                    :class="[
                        isSelected(s.video_id) ? 'bg-blue-600/15 ring-1 ring-blue-500/50' : 'hover:bg-tactical-surface',
                        !isSelected(s.video_id) && isFull ? 'opacity-50 cursor-not-allowed' : '',
                    ]"
                    :disabled="!isSelected(s.video_id) && isFull"
                    @click="emit('toggle', s)"
                >
                    <div class="relative shrink-0 w-28 aspect-video rounded-md overflow-hidden bg-tactical-base">
                        <img
                            :src="s.thumbnail || `https://i.ytimg.com/vi/${s.video_id}/mqdefault.jpg`"
                            :alt="s.title"
                            loading="lazy"
                            class="w-full h-full object-cover"
                        />
                        <span class="absolute bottom-1 left-1 px-1 rounded bg-red-600 text-[9px] font-bold text-white">LIVE</span>
                        <div
                            v-if="isSelected(s.video_id)"
                            class="absolute inset-0 bg-blue-600/50 flex items-center justify-center"
                        >
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1 py-0.5">
                        <p class="text-[13px] font-semibold text-slate-100 leading-snug line-clamp-2">{{ s.title }}</p>
                        <p class="mt-0.5 text-xs text-slate-400 truncate">{{ s.officer?.officer_name || s.officer?.streamer_name }}</p>
                        <div class="mt-1 flex items-center gap-1.5 text-[10px]">
                            <span
                                v-if="deptOf(s)"
                                class="px-1.5 py-px rounded border font-bold"
                                :class="deptBadge[deptOf(s)] || 'border-slate-600 text-slate-300'"
                            >{{ deptOf(s) }}</span>
                            <span class="font-mono text-slate-500 truncate">{{ s.officer?.callsign }}</span>
                            <span class="ml-auto text-slate-500 shrink-0">{{ formatViewers(s.viewers_count) }} 👁</span>
                        </div>
                    </div>
                </button>
            </li>
            <li v-if="!filteredStreams.length" class="py-10 text-center text-sm text-slate-500">
                Tidak ada siaran live yang cocok.
            </li>
        </ul>

        <!-- Offline roster -->
        <ul v-else class="flex-1 min-h-0 overflow-y-auto p-2 space-y-1">
            <li v-for="o in filteredOffline" :key="o.id">
                <a
                    :href="o.channel_id ? `https://www.youtube.com/channel/${o.channel_id}` : '#'"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-tactical-surface transition"
                >
                    <img
                        v-if="o.avatar_url"
                        :src="o.avatar_url"
                        :alt="o.officer_name"
                        loading="lazy"
                        class="w-9 h-9 rounded-full object-cover grayscale"
                    />
                    <div v-else class="w-9 h-9 rounded-full bg-tactical-surface" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-300 truncate">{{ o.officer_name || o.streamer_name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">
                            {{ deptOf(o) }} · <span class="font-mono">{{ o.callsign }}</span>
                        </p>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">10-7</span>
                </a>
            </li>
            <li v-if="!filteredOffline.length" class="py-10 text-center text-sm text-slate-500">
                Tidak ada officer offline yang cocok.
            </li>
        </ul>

        <p class="px-3 py-2 border-t border-tactical-border text-[11px] text-slate-500">
            {{ selectedIds.length }}/{{ maxTiles }} POV dipilih
            <span v-if="isFull" class="text-amber-400">· batas tercapai</span>
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
