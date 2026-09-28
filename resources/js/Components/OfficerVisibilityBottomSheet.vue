<script setup>
import { ref, computed } from "vue";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";
import iconSearch from "@/Components/Icons/search-svgrepo-com.svg";

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    allActiveStreams: {
        type: Array,
        default: () => [],
    },
    offlineOfficers: {
        type: Array,
        default: () => [],
    },
    customStreamOrder: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "close",
    "toggle-priority",
    "move-priority-up",
    "move-priority-down",
    "reset-priority",
]);

const {
    disabledOfficerKeys,
    getOfficerKey,
    isOfficerDisabled,
    toggleOfficerStatus,
    resetAllFilters,
} = useOfficerFilter();

const searchQuery = ref("");
const selectedDeptFilter = ref("ALL");

// Department Badge Color Helper
const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD"))
        return "bg-blue-950 text-blue-300 border-blue-800/80";
    if (d.includes("BCSO") || d.includes("LSCSD"))
        return "bg-amber-950 text-amber-300 border-amber-800/80";
    if (d.includes("SASP"))
        return "bg-teal-950 text-teal-300 border-teal-800/80";
    if (d.includes("SAPR") || d.includes("RANGER"))
        return "bg-emerald-950 text-emerald-300 border-emerald-800/80";
    return "bg-slate-950 text-slate-400 border-slate-800";
};

const getOfficerRank = (rawItem) => {
    if (!rawItem || !props.customStreamOrder) return 0;
    const key = getOfficerKey(rawItem);
    if (!key) return 0;

    const idx = props.customStreamOrder.indexOf(key);
    if (idx !== -1) return idx + 1;

    if (rawItem.video_id) {
        const vIdx = props.customStreamOrder.indexOf(String(rawItem.video_id).trim());
        if (vIdx !== -1) return vIdx + 1;
    }

    return 0;
};

// Combine all unique officers (both Live 10-8 and Offline 10-7)
const combinedOfficers = computed(() => {
    const list = [];
    const seenKeys = new Set();

    // 1. Add Live Streams officers
    (props.allActiveStreams || []).forEach((stream) => {
        const key = getOfficerKey(stream);
        if (key && !seenKeys.has(key)) {
            seenKeys.add(key);
            list.push({
                key,
                raw: stream,
                officer_name: stream.officer?.officer_name || stream.streamer_name || "Officer",
                handle: stream.officer?.handle || stream.channel_id || "",
                department: stream.officer?.department || "UNIT",
                callsign: stream.officer?.callsign || "",
                avatar_url: stream.officer?.avatar_url || stream.avatar || `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(key)}`,
                is_live: true,
                live_viewers: stream.viewers_count || 0,
            });
        }
    });

    // 2. Add Offline Officers
    (props.offlineOfficers || []).forEach((officer) => {
        const key = getOfficerKey(officer);
        if (key && !seenKeys.has(key)) {
            seenKeys.add(key);
            list.push({
                key,
                raw: officer,
                officer_name: officer.officer_name || officer.name || "Officer",
                handle: officer.handle || officer.channel_id || "",
                department: officer.department || "UNIT",
                callsign: officer.callsign || "",
                avatar_url: officer.avatar_url || `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(key)}`,
                is_live: false,
                live_viewers: 0,
            });
        }
    });

    return list;
});

// Filtered Officers List by Search & Department
const filteredOfficersList = computed(() => {
    return combinedOfficers.value.filter((officer) => {
        // Department Filter
        if (selectedDeptFilter.value !== "ALL") {
            const d = (officer.department || "").toUpperCase();
            if (selectedDeptFilter.value === "SAPR") {
                if (d !== "SAPR" && d !== "PARK RANGER") return false;
            } else if (d !== selectedDeptFilter.value) {
                return false;
            }
        }

        // Search Query Filter
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const nameMatch = officer.officer_name.toLowerCase().includes(q);
            const deptMatch = officer.department.toLowerCase().includes(q);
            const callsignMatch = officer.callsign.toLowerCase().includes(q);
            const handleMatch = officer.handle.toLowerCase().includes(q);
            return nameMatch || deptMatch || callsignMatch || handleMatch;
        }

        return true;
    });
});

const totalOfficerCount = computed(() => combinedOfficers.value.length);
const disabledCount = computed(() => {
    return combinedOfficers.value.filter((o) => isOfficerDisabled(o.raw)).length;
});
const activeCount = computed(() => totalOfficerCount.value - disabledCount.value);

const disableAllOffline = () => {
    combinedOfficers.value.forEach((officer) => {
        if (!officer.is_live && !isOfficerDisabled(officer.raw)) {
            toggleOfficerStatus(officer.raw);
        }
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 bg-slate-950/90 transform-gpu flex flex-col justify-end sm:justify-center items-center p-0 sm:p-4"
                @click.self="emit('close')"
            >
                <!-- Bottom Sheet Drawer Panel (Mobile Slide Up, Desktop Center Box) -->
                <Transition
                    enter-active-class="transition duration-200 ease-out transform-gpu"
                    enter-from-class="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
                    enter-to-class="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in transform-gpu"
                    leave-from-class="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
                    leave-to-class="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
                >
                    <div
                        v-if="isOpen"
                        class="w-full sm:max-w-xl bg-slate-900 border-t sm:border border-slate-800 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[85vh] sm:max-h-[80vh] flex flex-col font-sans transform-gpu will-change-transform"
                    >
                        <!-- Mobile Drag Indicator Bar -->
                        <div class="pt-2 pb-1 bg-slate-900 shrink-0 sm:hidden flex justify-center cursor-pointer" @click="emit('close')">
                            <div class="w-12 h-1.5 bg-slate-700 rounded-full"></div>
                        </div>

                        <!-- Panel Header -->
                        <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between gap-3 shrink-0">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="p-2 rounded-md bg-slate-900 border border-slate-800 text-blue-400 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-100 tracking-tight uppercase flex items-center gap-2">
                                        <span>KELOLA TAMPILAN PERWIRA</span>
                                    </h3>
                                    <p class="text-[11px] font-mono text-slate-400 truncate flex items-center gap-1.5">
                                        <span class="text-emerald-400 font-semibold">{{ activeCount }} Tampil</span>
                                        <span>•</span>
                                        <span class="text-rose-400 font-semibold">{{ disabledCount }} Disembunyikan</span>
                                        <span>•</span>
                                        <span class="text-amber-400 font-semibold">{{ (customStreamOrder || []).length }} Prioritas</span>
                                    </p>
                                </div>
                            </div>

                            <button
                                @click="emit('close')"
                                class="p-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-100 border border-slate-800 transition-colors cursor-pointer shrink-0"
                                title="Tutup Panel"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Filter Controls Bar (Department Tabs & Search Input) -->
                        <div class="p-3 bg-slate-950/60 border-b border-slate-800 space-y-2.5 shrink-0">
                            <!-- Search & Action Buttons -->
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1 items-center">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari perwira, callsign, departemen..."
                                        class="w-full bg-slate-950 border border-slate-800 rounded-md pl-8 pr-7 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-slate-700 font-mono"
                                    />
                                    <img
                                        :src="iconSearch"
                                        class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 opacity-50 invert pointer-events-none"
                                        alt=""
                                    />
                                    <button
                                        v-if="searchQuery"
                                        @click="searchQuery = ''"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs font-bold leading-none p-0.5 rounded"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <button
                                    v-if="disabledCount > 0"
                                    @click="resetAllFilters"
                                    class="px-3 py-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-blue-400 border border-slate-800 hover:border-slate-700 font-mono text-[11px] font-semibold transition-colors shrink-0 cursor-pointer flex items-center gap-1"
                                    title="Tampilkan Kembali Semua Perwira"
                                >
                                    <span>↺ Reset</span>
                                </button>
                            </div>

                            <!-- Department Pills Selector -->
                            <div class="flex items-center gap-1.5 overflow-x-auto whitespace-nowrap scrollbar-none py-0.5 font-mono text-xs">
                                <button
                                    v-for="dept in ['ALL', 'LSPD', 'BCSO', 'SASP', 'SAPR']"
                                    :key="dept"
                                    @click="selectedDeptFilter = dept"
                                    :class="[
                                        'px-2.5 py-1 rounded-md border transition-colors cursor-pointer font-medium text-[11px]',
                                        selectedDeptFilter === dept
                                            ? 'bg-slate-800 text-blue-400 border-slate-700 font-bold'
                                            : 'bg-slate-900 text-slate-400 hover:text-slate-200 border-slate-800'
                                    ]"
                                >
                                    {{ dept === 'ALL' ? 'Semua Dept' : dept }}
                                </button>
                            </div>
                        </div>

                        <!-- Officer List (Scrollable Content) -->
                        <div class="p-3 overflow-y-auto space-y-2 flex-1 scrollbar-thin scrollbar-thumb-slate-800">
                            <div
                                v-if="filteredOfficersList.length === 0"
                                class="p-8 text-center text-slate-500 font-mono text-xs"
                            >
                                Tidak ada perwira yang sesuai dengan pencarian.
                            </div>

                            <div
                                v-for="officer in filteredOfficersList"
                                :key="officer.key"
                                class="p-2.5 rounded-lg border transition-colors flex items-center justify-between gap-2.5"
                                :class="[
                                    isOfficerDisabled(officer.raw)
                                        ? 'bg-slate-950/60 border-slate-900 opacity-50'
                                        : 'bg-slate-950 border-slate-800 hover:border-slate-700'
                                ]"
                            >
                                <!-- Left Info (Rank Badge, Avatar, Name, Dept, Callsign) -->
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <span
                                        v-if="getOfficerRank(officer.raw) > 0"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-950/80 text-amber-400 border border-amber-800/80 shrink-0"
                                        title="Urutan Prioritas Penayangan Grid"
                                    >
                                        #{{ getOfficerRank(officer.raw) }}
                                    </span>

                                    <div class="relative shrink-0">
                                        <img
                                            :src="officer.avatar_url"
                                            class="w-8 h-8 rounded-full object-cover border border-slate-700"
                                            loading="lazy"
                                        />
                                        <span
                                            class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-950"
                                            :class="officer.is_live ? 'bg-red-500' : 'bg-slate-600'"
                                        ></span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs font-bold text-slate-100 truncate">
                                                {{ officer.officer_name }}
                                            </span>
                                            <span
                                                class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold border"
                                                :class="getDeptBadgeClass(officer.department)"
                                            >
                                                {{ officer.department }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] font-mono text-slate-400 truncate">
                                            {{ officer.callsign || officer.handle }}
                                            <span v-if="officer.is_live" class="text-red-400 font-semibold ml-1">LIVE</span>
                                            <span v-else class="text-slate-500 ml-1">Standby</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Controls: Priority Pin + Up/Down + Tampil/Sembunyi -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <div v-if="!isOfficerDisabled(officer.raw)" class="flex items-center gap-1">
                                        <button
                                            @click="emit('toggle-priority', officer.raw)"
                                            class="px-2 py-1 rounded-md text-xs font-mono font-bold border transition cursor-pointer flex items-center justify-center gap-1"
                                            :class="[
                                                getOfficerRank(officer.raw) > 0
                                                    ? 'bg-slate-800 text-amber-400 border-slate-700'
                                                    : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-amber-400 border-slate-800'
                                            ]"
                                            :title="getOfficerRank(officer.raw) > 0 ? 'Lepas dari Prioritas Utama' : 'Pin ke Prioritas Utama (#1, #2, #3...)'"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5v6l1 1 1-1v-6h5v-2l-2-2z" />
                                            </svg>
                                        </button>

                                        <div v-if="getOfficerRank(officer.raw) > 0" class="flex flex-col gap-0.5">
                                            <button
                                                @click="emit('move-priority-up', officer.raw)"
                                                class="px-1 py-0.2 rounded text-[9px] bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 leading-none cursor-pointer"
                                                title="Naikkan Urutan Prioritas"
                                            >
                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="emit('move-priority-down', officer.raw)"
                                                class="px-1 py-0.2 rounded text-[9px] bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 leading-none cursor-pointer"
                                                title="Turunkan Urutan Prioritas"
                                            >
                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Enable/Disable Switch Toggle -->
                                    <button
                                        @click="toggleOfficerStatus(officer.raw)"
                                        class="px-3 py-1.5 rounded-md text-xs font-mono font-medium transition-colors flex items-center gap-1.5 cursor-pointer shrink-0 border"
                                        :class="[
                                            isOfficerDisabled(officer.raw)
                                                ? 'bg-slate-900 text-slate-400 border-slate-800 hover:bg-slate-800 hover:text-slate-200'
                                                : 'bg-slate-800 text-emerald-400 border-slate-700 hover:bg-slate-700'
                                        ]"
                                        :title="isOfficerDisabled(officer.raw) ? 'Klik untuk menampilkan perwira' : 'Klik untuk menyembunyikan perwira'"
                                    >
                                        <span>{{ isOfficerDisabled(officer.raw) ? "Sembunyi" : "Tampil" }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Bottom Footer -->
                        <div class="p-3 bg-slate-950 border-t border-slate-800 flex items-center justify-between gap-2 shrink-0 text-xs font-mono">
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 text-[11px]">
                                    Filter tersimpan otomatis di browser
                                </span>
                                <button
                                    v-if="(customStreamOrder || []).length > 0"
                                    @click="emit('reset-priority')"
                                    class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 text-amber-400 border border-slate-800 hover:border-slate-700 text-[10px] transition-colors cursor-pointer flex items-center gap-1"
                                    title="Reset seluruh urutan prioritas ke standar"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Reset Prioritas</span>
                                </button>
                            </div>
                            <button
                                @click="emit('close')"
                                class="px-4 py-1.5 rounded-md bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-semibold transition-colors cursor-pointer shadow-none"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
