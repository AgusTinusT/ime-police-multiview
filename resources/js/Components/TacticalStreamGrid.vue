<script setup>
import { ref, computed } from "vue";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";
import iconSaver from "@/Components/Icons/gauge-low-svgrepo-com.svg";
import iconPlayAll from "@/Components/Icons/play-full-svgrepo-com.svg";
import iconMute from "@/Components/Icons/mute-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/unmute-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconPinPlus from "@/Components/Icons/star-line-svgrepo-com.svg";
import iconPinMinus from "@/Components/Icons/star-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import iconReset from "@/Components/Icons/reset-svgrepo-com.svg";
import iconShield from "@/Components/Icons/shield-svgrepo-com.svg";
import iconChat from "@/Components/Icons/chat-svgrepo-com.svg";
import iconChatRemove from "@/Components/Icons/chat-remove-svgrepo-com.svg";
import iconClock from "@/Components/Icons/time-svgrepo-com.svg";
import iconTarget from "@/Components/Icons/target-svgrepo-com.svg";

import AnnouncementBanner from "@/Components/AnnouncementBanner.vue";
import TacChannelToolbar from "@/Components/TacChannelToolbar.vue";

const props = defineProps({
    selectedDepartment: { type: String, required: true },
    selectedLayout: { type: String, default: "auto" },
    searchFilter: { type: String, default: "" },
    isDataSaverEnabled: { type: Boolean, default: false },
    isSyncingFeeds: { type: Boolean, default: false },
    allActiveStreams: { type: Array, default: () => [] },
    allCatalogStreams: { type: Array, default: () => [] },
    trendingStreams: { type: Array, default: () => [] },
    recentReplayStreams: { type: Array, default: () => [] },
    activePersonalStreams: { type: Array, default: () => [] },
    lspdCatalogStreams: { type: Array, default: () => [] },
    saspCatalogStreams: { type: Array, default: () => [] },
    bcsoCatalogStreams: { type: Array, default: () => [] },
    saprCatalogStreams: { type: Array, default: () => [] },
    specialOpsCatalogStreams: { type: Array, default: () => [] },
    tacSituationalStreams: { type: Array, default: () => [] },
    filteredOfflineOfficers: { type: Array, default: () => [] },
    visibleStreams: { type: Array, default: () => [] },
    focusStream: { type: Object, default: null },
    sideStreams: { type: Array, default: () => [] },
    gridStreams: { type: Array, default: () => [] },
    activePreviewVideoIds: { type: Array, default: () => [] },
    activeGridVideoIds: { type: Array, default: () => [] },
    activeAudioVideoId: { type: String, default: null },
    activeTacPopoverVideoId: { type: [String, Number], default: null },
    activeChatVideoId: { type: [String, Number], default: null },
    activeGridChatStream: { type: Object, default: null },
    originUrl: { type: String, default: "" },
    chatEmbedDomain: { type: String, default: "" },
    activeAnnouncements: { type: Array, default: () => [] },
    activeTab: { type: String, default: "10-8" },
    personalStreamIds: { type: Array, default: () => [] },
    tacChannels: { type: Array, default: () => [] },
    customOrderMap: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
    "play-stream-in-focus",
    "set-focus-stream",
    "toggle-audio",
    "toggle-personal-stream",
    "toggle-custom-order",
    "assign-stream-to-tac",
    "remove-stream-from-tac",
    "open-subscribe-popup",
    "trigger-manual-sync",
    "toggle-sidebar-preview",
    "extend-tac-timer",
    "disband-tac-channel",
    "disable-data-saver",
    "enable-data-saver",
    "toggle-grid-stream-play",
    "update:activeTacPopoverVideoId",
    "update:activeChatVideoId",
    "update:activeTab",
    "update:selectedDepartment",
    "update:selectedLayout",
]);

// Focus Mode Right-Column Live Chat State
const isRightChatOpen = ref(true);
const openInfoVideoIds = ref([]);

const isStreamInfoOpen = (videoId) => openInfoVideoIds.value.includes(videoId);

const toggleStreamInfo = (videoId) => {
    if (isStreamInfoOpen(videoId)) {
        openInfoVideoIds.value = openInfoVideoIds.value.filter((id) => id !== videoId);
    } else {
        openInfoVideoIds.value.push(videoId);
    }
};

const formatDescriptionWithLinks = (text) => {
    if (!text) return "";
    const escaped = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
    const urlPattern = /(https?:\/\/[^\s]+)/g;
    return escaped.replace(
        urlPattern,
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline hover:text-blue-300">$1</a>'
    );
};

// Computed Primary & Secondary Streams for Focus Layout
const primaryFocusedStream = computed(() => {
    if (props.focusStream) return props.focusStream;
    return props.visibleStreams.length > 0 ? props.visibleStreams[0] : null;
});

const secondaryStreams = computed(() => {
    if (props.sideStreams && props.sideStreams.length > 0) return props.sideStreams;
    const lead = primaryFocusedStream.value;
    if (!lead) return props.visibleStreams;
    return props.visibleStreams.filter((s) => s.video_id !== lead.video_id);
});

const displayedGridStreams = computed(() => {
    return props.gridStreams && props.gridStreams.length > 0
        ? props.gridStreams
        : props.visibleStreams;
});

// Dynamic Grid Layout CSS Class
const layoutGridClass = computed(() => {
    const layout = props.selectedLayout;
    if (layout === "grid-1x2") return "grid grid-cols-1 md:grid-cols-2 gap-3.5";
    if (layout === "grid-2x2") return "grid grid-cols-1 md:grid-cols-2 gap-3.5";
    if (layout === "grid-3x3") return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5";
    if (layout === "grid-4x4") return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3.5";
    return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5";
});

// Horizontal Scroll Helper for Netflix Swimlanes
const scrollRow = (rowId, direction = "right") => {
    const el = document.getElementById(rowId);
    if (!el) return;
    const scrollAmount = direction === "left" ? -650 : 650;
    el.scrollBy({ left: scrollAmount, behavior: "smooth" });
};

// Department Badge Class Mapper
const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return "bg-blue-900/60 text-blue-200 border-blue-700/80";
    if (d.includes("BCSO") || d.includes("LSCSD")) return "bg-amber-900/60 text-amber-200 border-amber-700/80";
    if (d.includes("SASP")) return "bg-purple-900/60 text-purple-200 border-purple-700/80";
    if (d.includes("SAPR") || d.includes("RANGER")) return "bg-emerald-900/60 text-emerald-200 border-emerald-700/80";
    return "bg-slate-800 text-slate-300 border-slate-700";
};

// Department Icon Mapper
const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return logoSaspColor;
    if (d.includes("SAPR") || d.includes("RANGER")) return iconSapr;
    return iconAllUnits;
};

// Relative Time Label Helper
const formatRelativeTimeLabel = (dateStr) => {
    if (!dateStr) return "REPLAY";
    return String(dateStr);
};

// YouTube Link Helper
const getStreamYoutubeUrl = (stream) => {
    if (!stream) return "#";
    if (stream.is_officer_card || (typeof stream.video_id === "string" && stream.video_id.startsWith("officer-"))) {
        const handle = stream.officer?.handle ? stream.officer.handle.replace(/^@/, "") : "";
        return handle ? `https://www.youtube.com/@${handle}` : "#";
    }
    return `https://www.youtube.com/watch?v=${stream.video_id}`;
};

// TAC Department Check Helper
const isTacDepartment = (deptId) => {
    return typeof deptId === "string" && deptId.startsWith("TAC_");
};

// TAC Remaining Seconds Helper
const getTacRemainingSeconds = (tacCode) => {
    const ch = props.tacChannels.find((c) => c.code === tacCode);
    return ch ? ch.remaining_seconds || 0 : 0;
};

// Check if Channel/Handle is pinned to personal watchlist
const isPersonalStream = (channelOrHandle) => {
    if (!channelOrHandle) return false;
    const target = String(channelOrHandle).trim().toLowerCase();
    return (props.personalStreamIds || []).some(
        (id) => String(id).trim().toLowerCase() === target
    );
};

const getStreamTac = (videoId) => {
    if (!videoId) return null;
    const targetId = String(videoId).trim();
    for (const channel of props.tacChannels || []) {
        if (
            channel.video_ids &&
            Array.isArray(channel.video_ids) &&
            channel.video_ids.some((id) => String(id).trim() === targetId)
        ) {
            return channel.code;
        }
    }
    return null;
};

const getCustomOrderRank = (videoId) => {
    if (!videoId || !props.customOrderMap) return 0;
    const strId = String(videoId).trim();
    if (Array.isArray(props.customOrderMap)) {
        const idx = props.customOrderMap.indexOf(strId);
        return idx !== -1 ? idx + 1 : 0;
    }
    if (typeof props.customOrderMap === "function") {
        return props.customOrderMap(strId);
    }
    return props.customOrderMap[strId] || 0;
};
</script>

<template>
    <!-- GLOBAL PROMO / ANNOUNCEMENT BANNERS -->
    <AnnouncementBanner
        v-if="activeTab === '10-8' && selectedDepartment === 'ALL'"
        :announcements="activeAnnouncements"
    />

    <!-- TAB 1: 10-8 ACTIVE LIVE BODYCAM FEEDS -->
    <div v-if="activeTab === '10-8'">
        <!-- MODE A: ALL UNITS -> NETFLIX-STYLE POLICE CINEMA & DISCOVERY HUB -->
        <div
            v-if="selectedDepartment === 'ALL'"
            class="space-y-8 pb-12 animate-in fade-in duration-300"
        >
            <!-- Standby Banner (Only when all streams are off-duty / empty) -->
            <div
                v-if="allActiveStreams.length === 0"
                class="relative rounded-2xl overflow-hidden border border-slate-800/80 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-8 text-center flex flex-col items-center justify-center gap-3"
            >
                <div
                    class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-1"
                >
                    <img
                        :src="iconAllUnits"
                        class="w-7 h-7 invert opacity-80"
                        alt=""
                    />
                </div>
                <h3 class="text-lg font-bold text-slate-100">
                    SELURUH KESATUAN SEDANG 10-7 (OFF-DUTY)
                </h3>
                <p class="text-xs text-slate-400 max-w-md">
                    Belum ada siaran langsung patroli atau video rekaman yang termuat. Anda dapat menyinkronkan feed terbaru atau melihat daftar nama petugas di tab 10-7 Roster.
                </p>
                <div class="flex items-center gap-2 mt-2">
                    <button
                        @click="emit('trigger-manual-sync')"
                        :disabled="isSyncingFeeds"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition flex items-center gap-2"
                    >
                        <img
                            :src="iconRefresh"
                            class="w-3.5 h-3.5 invert"
                            :class="{ 'animate-spin': isSyncingFeeds }"
                        />
                        <span>{{
                            isSyncingFeeds
                                ? "Menyinkronkan..."
                                : "Cek Live Sekarang"
                        }}</span>
                    </button>
                    <button
                        @click="emit('update:activeTab', '10-7')"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition"
                    >
                        Lihat 10-7 Roster
                    </button>
                </div>
            </div>

            <!-- 1. TRENDING & TOP FEATURED PATROL SWIMLANE -->
            <section v-if="trendingStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                        <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">
                            Patroli Populer (Trending Live)
                        </h3>
                    </div>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-trending', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri">
                            <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="scrollRow('row-trending', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan">
                            <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
                <div id="row-trending" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in trendingStreams" :key="`trend-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                    <span v-if="stream.viewers_count">({{ stream.viewers_count }})</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <div class="flex items-center gap-1">
                                    <span v-if="getStreamTac(stream.video_id)" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow">{{ getStreamTac(stream.video_id).replace("_", " ") }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "UNIT" }}</span>
                                </div>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Officer" }}</span>
                                <div class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10" @click.stop>
                                    <a :href="getStreamYoutubeUrl(stream)" target="_blank" class="p-1 text-slate-400 hover:text-white transition" title="Buka di YouTube" @click.stop><img :src="iconExternal" class="w-3 h-3 invert opacity-80" /></a>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. RECENT PATROL REPLAYS SWIMLANE -->
            <section v-if="recentReplayStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">Rekaman Patroli</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-replays', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri">
                            <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="scrollRow('row-replays', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan">
                            <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
                <div id="row-replays" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in recentReplayStreams" :key="`replay-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "POLICE" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Officer" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. PERSONAL WATCHLIST SWIMLANE -->
            <section v-if="activePersonalStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">Personal Watchlist</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-personal', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri">
                            <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="scrollRow('row-personal', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan">
                            <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
                <div id="row-personal" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in activePersonalStreams" :key="`personal-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "WATCH" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Officer" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. LSPD CITY PATROL SWIMLANE -->
            <section v-if="lspdCatalogStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">Los Santos Police Department</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-lspd', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri"><svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                        <button @click="scrollRow('row-lspd', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan"><svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div id="row-lspd" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in lspdCatalogStreams" :key="`lspd-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "LSPD" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Officer" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 7. BCSO COUNTY PATROL SWIMLANE -->
            <section v-if="bcsoCatalogStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">Blaine County Sheriff's Office</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-bcso', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri"><svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                        <button @click="scrollRow('row-bcso', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan"><svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div id="row-bcso" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in bcsoCatalogStreams" :key="`bcso-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "BCSO" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Deputy" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 8. SASP STATE TROOPERS SWIMLANE -->
            <section v-if="saspCatalogStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">San Andreas State Police</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-sasp', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri"><svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                        <button @click="scrollRow('row-sasp', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan"><svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div id="row-sasp" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in saspCatalogStreams" :key="`sasp-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "SASP" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Trooper" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 8.5 SAPR PARK RANGERS SWIMLANE -->
            <section v-if="saprCatalogStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">San Andreas Park Rangers</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-sapr', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri"><svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                        <button @click="scrollRow('row-sapr', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan"><svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div id="row-sapr" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in saprCatalogStreams" :key="`sapr-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "SAPR" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Ranger" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 9. SPECIAL OPS & SWAT SWIMLANE -->
            <section v-if="specialOpsCatalogStreams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">Special Operations & SWAT</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-specops', 'left')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri"><svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                        <button @click="scrollRow('row-specops', 'right')" class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan"><svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div id="row-specops" class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="stream in specialOpsCatalogStreams" :key="`specops-${stream.video_id}`" class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer" @click="emit('play-stream-in-focus', stream)">
                        <div class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5">
                            <img :src="stream.thumbnail || `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" loading="lazy" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"></div>
                            <div class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
                                <span v-if="stream.status === 'LIVE'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80">
                                    <span>{{ formatRelativeTimeLabel(stream.streamed_at) }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department || "SPEC" }}</span>
                            </div>
                            <div class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto">
                                <span class="text-xs font-bold text-white truncate drop-shadow">{{ stream.officer?.officer_name || "Operator" }}</span>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug">{{ stream.title }}</h4>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 10. 10-7 OFFLINE ROSTER SWIMLANE -->
            <section v-if="filteredOfflineOfficers.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide">10-7 Officer Roster</h3>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button @click="scrollRow('row-offline', 'left')" class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kiri"><svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                        <button @click="scrollRow('row-offline', 'right')" class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group" title="Geser Kanan"><svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div id="row-offline" class="flex gap-3.5 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none">
                    <div v-for="officer in filteredOfflineOfficers" :key="`off-${officer.channel_id}`" class="w-[200px] sm:w-[220px] shrink-0 snap-start bg-[#0b121e]/90 hover:bg-[#0f1828] border border-slate-800/80 hover:border-slate-700 rounded-xl p-3 transition flex flex-col justify-between">
                        <div class="flex flex-col min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-1.5">
                                <span class="px-1.5 py-0.5 text-[9px] font-black rounded border" :class="getDeptBadgeClass(officer.department)">{{ officer.department }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">10-7 OFFLINE</span>
                            </div>
                            <h5 class="text-xs font-bold text-slate-100 truncate">{{ officer.officer_name }}</h5>
                            <div v-if="officer.rank" class="text-[11px] text-slate-400 font-mono truncate mt-0.5">{{ officer.rank }}</div>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                            <span class="text-slate-500 font-mono text-[9px]">OFF-DUTY</span>
                            <div class="flex items-center space-x-1">
                                <button @click="emit('toggle-personal-stream', officer.channel_id || officer.handle)" class="p-1 rounded transition" :class="isPersonalStream(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/70 border border-purple-500/50' : 'text-slate-400 hover:text-purple-300 hover:bg-slate-800'" :title="isPersonalStream(officer.channel_id || officer.handle) ? 'Hapus' : 'Pin ke Personal'">
                                    <img :src="isPersonalStream(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus" class="w-2.5 h-2.5 invert" />
                                </button>
                                <button @click="emit('open-subscribe-popup', officer.channel_id || officer.handle, officer.officer_name)" class="bg-red-600/90 hover:bg-red-600 text-white font-bold px-1.5 py-0.5 rounded text-[9px] transition">Sub</button>
                                <a :href="`https://www.youtube.com/${officer.handle}`" target="_blank" class="text-blue-400 hover:underline flex items-center gap-0.5">
                                    <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-70" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- MODE B: SPECIFIC DEPARTMENTS / TAC / PERSONAL -> TACTICAL CCTV GRID -->
        <template v-else>
            <TacChannelToolbar
                v-if="isTacDepartment(selectedDepartment)"
                :selectedDepartment="selectedDepartment"
                :visibleStreamsCount="visibleStreams.length"
                :remainingSeconds="getTacRemainingSeconds(selectedDepartment)"
                :iconRadio="iconRadio"
                :iconClock="iconClock"
                @extendTimer="emit('extend-tac-timer', $event)"
                @disbandChannel="emit('disband-tac-channel', $event)"
            />

            <!-- Department Standby Banner when visibleStreams.length === 0 -->
            <div
                v-if="visibleStreams.length === 0"
                class="relative rounded-2xl overflow-hidden border border-slate-800/80 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-8 text-center flex flex-col items-center justify-center gap-3 animate-in fade-in duration-200"
            >
                <div class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-1">
                    <img :src="getDeptIcon(selectedDepartment)" class="w-7 h-7 invert opacity-80" alt="" />
                </div>
                <h3 class="text-lg font-bold text-slate-100 uppercase tracking-wider">
                    TIDAK ADA SIARAN AKTIF (10-8) DI KATEGORI {{ selectedDepartment.replace("_", " ") }}
                </h3>
                <p class="text-xs text-slate-400 max-w-md">
                    Saat ini belum ada siaran langsung untuk kesatuan {{ selectedDepartment.replace("_", " ") }}. Anda dapat menyinkronkan data terbaru atau melihat daftar anggota di 10-7 Roster.
                </p>
                <div class="flex items-center gap-2 mt-2 flex-wrap justify-center">
                    <button @click="emit('trigger-manual-sync')" :disabled="isSyncingFeeds" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition flex items-center gap-2">
                        <img :src="iconRefresh" class="w-3.5 h-3.5 invert" :class="{ 'animate-spin': isSyncingFeeds }" />
                        <span>{{ isSyncingFeeds ? "Menyinkronkan..." : "Cek Live Sekarang" }}</span>
                    </button>
                    <button @click="emit('update:activeTab', '10-7')" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition">
                        Lihat 10-7 Roster
                    </button>
                    <button @click="emit('update:selectedDepartment', 'ALL')" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition">
                        Tampilkan Seluruh Unit
                    </button>
                </div>

                <!-- Offline Roster Officers of this Department -->
                <div v-if="filteredOfflineOfficers.length > 0" class="w-full mt-6 pt-6 border-t border-slate-800/80 text-left">
                    <h4 class="text-xs font-bold text-slate-300 font-mono mb-3 uppercase tracking-wider">
                        Petugas 10-7 (Offline) Kesatuan {{ selectedDepartment.replace("_", " ") }}:
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                        <div v-for="officer in filteredOfflineOfficers" :key="`dept-off-${officer.channel_id}`" class="bg-slate-900/60 border border-slate-800 rounded-lg p-2 flex flex-col justify-between">
                            <div class="truncate">
                                <div class="text-[11px] font-bold text-slate-200 truncate">{{ officer.officer_name }}</div>
                                <div v-if="officer.rank" class="text-[9px] text-slate-500 font-mono truncate">{{ officer.rank }}</div>
                            </div>
                            <div class="mt-2 pt-1 border-t border-slate-800/80 flex items-center justify-between">
                                <button @click="emit('open-subscribe-popup', officer.channel_id || officer.handle, officer.officer_name)" class="text-red-400 hover:text-red-300 text-[9px] font-mono font-bold">+Sub</button>
                                <a :href="`https://www.youtube.com/${officer.handle}`" target="_blank" class="text-slate-500 hover:text-slate-300">
                                    <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-60" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOCUS LAYOUT MODE (1 Lead Screen + Supporting Unit Grid Below / Side) -->
            <div v-else-if="selectedLayout === 'focus'" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    <!-- MAIN LEAD PLAYER COLUMN -->
                    <div :class="isRightChatOpen ? 'lg:col-span-8 xl:col-span-9' : 'lg:col-span-12'" class="space-y-3">
                        <div v-if="primaryFocusedStream" class="bg-slate-950 rounded-2xl overflow-hidden border border-blue-500/40 shadow-2xl shadow-blue-950/20">
                            <!-- Player Header & Action HUD -->
                            <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 px-4 py-2 flex flex-wrap items-center justify-between border-b border-slate-800/80 gap-2">
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <span class="px-2 py-0.5 text-xs font-black rounded border tracking-wider shrink-0 shadow" :class="getDeptBadgeClass(primaryFocusedStream.officer?.department)">
                                        {{ primaryFocusedStream.officer?.department }} {{ primaryFocusedStream.officer?.callsign }}
                                    </span>
                                    <div class="truncate">
                                        <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2 truncate">
                                            <span class="truncate">{{ primaryFocusedStream.officer?.officer_name }}</span>
                                            <span v-if="primaryFocusedStream.officer?.rank" class="text-xs text-slate-400 font-mono font-normal hidden sm:inline">({{ primaryFocusedStream.officer.rank }})</span>
                                        </h3>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 shrink-0">
                                    <!-- TAC Channel Dispatch Selector -->
                                    <div class="relative">
                                        <button
                                            @click.stop="emit('update:activeTacPopoverVideoId', activeTacPopoverVideoId === primaryFocusedStream.video_id ? null : primaryFocusedStream.video_id)"
                                            class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition flex items-center gap-1.5 border shadow-sm"
                                            :class="getStreamTac(primaryFocusedStream.video_id) ? 'bg-amber-500 text-black border-amber-400 hover:bg-amber-400' : 'bg-slate-900 text-amber-400 hover:bg-amber-950/40 border-amber-500/30'"
                                        >
                                            <img :src="iconRadio" class="w-3.5 h-3.5" :class="getStreamTac(primaryFocusedStream.video_id) ? '' : 'invert'" />
                                            <span>{{ getStreamTac(primaryFocusedStream.video_id) ? getStreamTac(primaryFocusedStream.video_id).replace("_", " ") : "+ Assign TAC" }}</span>
                                        </button>
                                        <!-- TAC Popover Menu -->
                                        <div v-if="activeTacPopoverVideoId === primaryFocusedStream.video_id" class="absolute right-0 mt-2 w-56 bg-slate-950 border border-amber-500/50 rounded-xl p-2.5 shadow-2xl z-50 animate-in fade-in zoom-in-95 duration-150" @click.stop>
                                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-xs font-bold text-amber-300 font-mono">
                                                <span>ASSIGN RADIO TAC CHANNEL:</span>
                                                <button @click="emit('update:activeTacPopoverVideoId', null)" class="text-slate-400 hover:text-white p-0.5 rounded hover:bg-slate-800 text-[10px]">✕</button>
                                            </div>
                                            <div class="grid grid-cols-5 gap-1.5 mb-2">
                                                <button v-for="t in [1,2,3,4,5,6,7,8,9,10]" :key="t" @click.stop="emit('assign-stream-to-tac', `TAC_${t}`, primaryFocusedStream.video_id); emit('update:activeTacPopoverVideoId', null);" class="py-1.5 rounded-lg font-mono text-center font-bold text-xs transition border flex flex-col items-center justify-center gap-0.5" :class="getStreamTac(primaryFocusedStream.video_id) === `TAC_${t}` ? 'bg-amber-500 text-black border-amber-300 shadow-lg shadow-amber-500/30' : 'bg-slate-900 text-slate-200 hover:bg-amber-950/60 hover:text-amber-300 hover:border-amber-500/50 border-slate-800'">
                                                    <span class="text-[8px] text-slate-400 leading-none">TAC</span>
                                                    <span class="leading-none">{{ t }}</span>
                                                </button>
                                            </div>
                                            <div v-if="getStreamTac(primaryFocusedStream.video_id)" class="pt-1.5 border-t border-slate-800/80">
                                                <button @click.stop="emit('remove-stream-from-tac', primaryFocusedStream.video_id); emit('update:activeTacPopoverVideoId', null);" class="w-full py-1.5 px-2 text-[10px] rounded-lg bg-red-950/60 text-red-300 hover:bg-red-900/80 border border-red-500/40 text-center transition flex items-center justify-center gap-1 font-mono">
                                                    <span>✕ Lepas dari {{ getStreamTac(primaryFocusedStream.video_id).replace("_", " ") }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Live Audio Toggle Button -->
                                    <button @click="emit('toggle-audio', primaryFocusedStream.video_id)" :class="activeAudioVideoId === primaryFocusedStream.video_id ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 shadow-sm shadow-emerald-950/30 font-bold' : 'bg-slate-900/60 text-slate-400 hover:bg-slate-800 hover:text-slate-200 border-slate-800'" class="px-2.5 py-1 text-xs rounded-lg transition border flex items-center gap-1.5">
                                        <img :src="activeAudioVideoId === primaryFocusedStream.video_id ? iconUnmute : iconMute" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                        <span>{{ activeAudioVideoId === primaryFocusedStream.video_id ? "LIVE AUDIO" : "MUTED" }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Big Video Player Container with Floating Hover Overlay Bar -->
                            <div class="relative w-full aspect-video bg-black group/thumb">
                                <iframe :key="`primary-player-${primaryFocusedStream.video_id}`" :id="`yt-bodycam-${primaryFocusedStream.video_id}`" class="w-full h-full border-0 pointer-events-auto" :src="`https://www.youtube.com/embed/${primaryFocusedStream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=hd1080&origin=${originUrl}`" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                <!-- Floating Hover Overlay Bar -->
                                <div class="absolute bottom-3 left-3 right-3 opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 bg-slate-950/90 backdrop-blur-md px-3 sm:px-4 py-2 rounded-xl border border-slate-700/70 shadow-2xl flex flex-wrap sm:flex-nowrap items-center justify-between text-xs text-slate-300 gap-1.5 z-20 pointer-events-auto">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1 truncate">
                                        <span class="font-mono text-blue-400 font-semibold truncate text-[11px] sm:text-xs flex items-center gap-1">
                                            <img :src="iconShield" class="w-3.5 h-3.5 invert opacity-80 shrink-0" alt="" />
                                            <span>{{ primaryFocusedStream.officer?.patrol_zone || "Mission Row Sector" }}</span>
                                        </span>
                                        <span class="text-slate-600 hidden sm:inline">|</span>
                                        <span class="font-mono text-slate-300 truncate hidden sm:inline">Badge: {{ primaryFocusedStream.officer?.badge_number || "#000" }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1.5 shrink-0">
                                        <button v-if="primaryFocusedStream.officer?.channel_id || primaryFocusedStream.officer?.handle" @click="emit('open-subscribe-popup', primaryFocusedStream.officer?.channel_id || primaryFocusedStream.officer?.handle, primaryFocusedStream.officer?.officer_name)" class="bg-red-600/90 hover:bg-red-500 text-white font-bold px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-[10px] sm:text-xs shadow-md shadow-red-600/30 flex items-center gap-1 transition shrink-0" title="Subscribe to channel">
                                            <span>Sub</span>
                                        </button>
                                        <button @click="isRightChatOpen = !isRightChatOpen" :class="isRightChatOpen ? 'text-amber-400 font-bold bg-amber-950/40 border border-amber-500/30' : 'text-slate-300 hover:text-amber-300'" class="font-mono text-[10px] sm:text-xs px-2 py-0.5 rounded flex items-center gap-1 transition shrink-0">
                                            <img :src="isRightChatOpen ? iconChatRemove : iconChat" class="w-3.5 h-3.5 invert opacity-80 shrink-0" alt="" />
                                            <span>Chat</span>
                                        </button>
                                        <a :href="`https://www.youtube.com/watch?v=${primaryFocusedStream.video_id}`" target="_blank" class="p-1.5 hover:text-white text-slate-300 rounded hover:bg-slate-800 transition flex items-center justify-center">
                                            <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" />
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Stream Description & Info Section -->
                            <div class="bg-[#080d16] px-4 py-2 border-t border-slate-800/80 flex flex-col gap-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-xs font-bold text-slate-200 truncate flex items-center gap-1.5 min-w-0">
                                        <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-70 shrink-0" alt="" />
                                        <span class="truncate" :title="primaryFocusedStream.title">{{ primaryFocusedStream.title }}</span>
                                    </div>
                                    <button @click="toggleStreamInfo(primaryFocusedStream.video_id)" class="text-[10px] text-slate-300 hover:text-white font-mono bg-slate-900 hover:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-700 flex items-center gap-1 shrink-0 transition">
                                        <span>{{ isStreamInfoOpen(primaryFocusedStream.video_id) ? "▲ Tutup Deskripsi" : "▼ Lihat Deskripsi" }}</span>
                                    </button>
                                </div>
                                <div v-if="isStreamInfoOpen(primaryFocusedStream.video_id)" class="bg-slate-950/90 p-3 rounded-xl border border-slate-800 mt-1.5 animate-in fade-in duration-150 shadow-inner">
                                    <div v-if="primaryFocusedStream.description" class="text-[11px] text-slate-300 font-mono whitespace-pre-wrap break-words leading-relaxed max-h-56 overflow-y-auto scrollbar-thin" v-html="formatDescriptionWithLinks(primaryFocusedStream.description)"></div>
                                    <div v-else class="text-[11px] text-slate-500 font-mono italic">Tidak ada deskripsi tambahan dari streamer.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: LIVE CHAT & SUPPORTING UNITS -->
                    <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-3">
                        <div v-if="isRightChatOpen && primaryFocusedStream" class="bg-slate-950 rounded-xl overflow-hidden border border-amber-500/50 shadow-2xl flex flex-col animate-in fade-in zoom-in-95 duration-200">
                            <div class="bg-slate-900/95 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 text-xs">
                                <div class="flex items-center space-x-1.5 text-amber-300 font-bold truncate">
                                    <img :src="iconChat" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Live Chat: {{ primaryFocusedStream.officer?.officer_name }}</span>
                                </div>
                                <button @click="isRightChatOpen = false" class="text-slate-400 hover:text-white text-[11px] font-mono bg-slate-800 hover:bg-slate-700 px-1.5 py-0.2 rounded">✕ Close</button>
                            </div>
                            <div class="w-full h-[520px] bg-slate-900 relative">
                                <iframe :key="`chat-lead-${primaryFocusedStream.video_id}`" :src="`https://www.youtube.com/live_chat?v=${primaryFocusedStream.video_id}&embed_domain=${chatEmbedDomain}&dark_theme=1`" class="w-full h-full border-0" allow="autoplay"></iframe>
                            </div>
                        </div>

                        <!-- SUPPORTING UNITS FEED LIST -->
                        <div class="space-y-3">
                            <div v-for="stream in secondaryStreams" :key="`side-feed-${stream.video_id}`" class="bg-slate-950 rounded-xl overflow-hidden border border-slate-800/80 hover:border-slate-700 transition flex flex-col shadow-lg">
                                <div class="bg-slate-900/90 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 text-xs">
                                    <div class="flex items-center space-x-2 min-w-0">
                                        <span class="px-1.5 py-0.5 text-[10px] font-black rounded border shrink-0 opacity-90" :class="getDeptBadgeClass(stream.officer?.department)">{{ stream.officer?.department }}</span>
                                        <span class="font-bold text-slate-200 truncate opacity-90">{{ stream.officer?.officer_name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1 shrink-0">
                                        <button @click="emit('toggle-personal-stream', stream.video_id)" :class="isPersonalStream(stream.video_id) ? 'text-purple-300 bg-purple-950/60 border border-purple-500/40' : 'text-slate-500 hover:text-purple-300 bg-slate-900/60 border-slate-800'" class="p-1 rounded-md transition font-mono border" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin ke Personal'">
                                            <img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert opacity-80" />
                                        </button>
                                        <button @click="emit('toggle-audio', stream.video_id)" :class="activeAudioVideoId === stream.video_id ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 font-bold' : 'text-slate-500 hover:text-slate-200 bg-slate-900/60 border-slate-800'" class="p-1 rounded-md transition font-mono border" title="Audio Switch">
                                            <img :src="activeAudioVideoId === stream.video_id ? iconUnmute : iconMute" class="w-3 h-3 invert opacity-80" />
                                        </button>
                                        <button @click="emit('set-focus-stream', stream.video_id)" class="bg-blue-950/60 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/40 hover:border-blue-400 px-2 py-0.5 rounded-md text-[10px] font-bold shadow transition flex items-center gap-1" title="Fokus">
                                            <img :src="iconFocus" class="w-2.5 h-2.5 invert opacity-80" />
                                            <span>Fokus</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="relative w-full aspect-video bg-black overflow-hidden group/thumb">
                                    <template v-if="activePreviewVideoIds.includes(stream.video_id) || !isDataSaverEnabled">
                                        <iframe :key="`support-iframe-${stream.video_id}`" :id="`yt-bodycam-${stream.video_id}`" class="w-full h-full border-0 pointer-events-auto" :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                        <button v-if="isDataSaverEnabled" @click="emit('toggle-sidebar-preview', stream.video_id)" class="absolute top-2 right-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-1.5 py-0.5 rounded border border-white/20 z-20">✕ Close Preview</button>
                                    </template>
                                    <template v-else>
                                        <img :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" :alt="stream.title" class="w-full h-full object-cover opacity-90 group-hover/thumb:opacity-100 transition duration-300" loading="lazy" />
                                        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px] opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 flex items-center justify-center gap-2 p-3 z-10">
                                            <button @click="emit('toggle-sidebar-preview', stream.video_id)" class="bg-emerald-600/90 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-lg transition flex items-center gap-1.5">
                                                <img :src="iconPlayAll" class="w-3.5 h-3.5 invert" />
                                                <span>Play</span>
                                            </button>
                                            <button @click="emit('set-focus-stream', stream.video_id)" class="bg-blue-600/90 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-lg transition flex items-center gap-1.5">
                                                <img :src="iconFocus" class="w-3.5 h-3.5 invert" />
                                                <span>Fokus</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STANDARD GRID VIEWS (Auto, 2x2, 3x3, 4x4) -->
            <div v-else class="space-y-3">
                <!-- DATA SAVER HELPER BANNER -->
                <div v-if="isDataSaverEnabled" class="bg-slate-900/90 border border-slate-800/90 px-3.5 py-2.5 rounded-xl flex items-center justify-between flex-wrap gap-2.5 shadow-lg">
                    <div class="flex items-center space-x-2 text-xs font-mono">
                        <span class="text-emerald-400 font-bold flex items-center gap-1.5">
                            <img :src="iconSaver" class="w-3.5 h-3.5 invert" alt="" />
                            <span>SAVER MODE ACTIVE:</span>
                        </span>
                        <span class="text-slate-300">Feeds are in standby. Click Play to view any feed, or switch to Play All.</span>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <button @click="emit('disable-data-saver')" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow">
                            <img :src="iconPlayAll" class="w-3 h-3 invert" alt="" />
                            <span>Play All Videos</span>
                        </button>
                        <button v-if="activeGridVideoIds.length > 0" @click="emit('enable-data-saver')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg text-xs font-mono transition flex items-center gap-1">
                            <img :src="iconReset" class="w-3 h-3 invert opacity-70" alt="" />
                            <span>Reset to Saver</span>
                        </button>
                    </div>
                </div>

                <!-- MAIN GRID VIEW -->
                <div :class="activeGridChatStream ? 'grid grid-cols-1 lg:grid-cols-12 gap-4 items-start' : ''">
                    <div :class="activeGridChatStream ? 'lg:col-span-8 xl:col-span-9' : 'w-full'">
                        <div :class="layoutGridClass">
                            <div v-for="stream in displayedGridStreams" :key="`grid-card-${stream.video_id}`" :class="[getCustomOrderRank(stream.video_id) > 0 ? 'border-amber-500/40 shadow-md shadow-amber-950/20' : 'border-slate-800/80 hover:border-slate-700', activeChatVideoId === stream.video_id ? 'border-amber-500/60 shadow-md shadow-amber-950/30' : '']" class="bg-slate-950 rounded-xl overflow-hidden border transition flex flex-col relative group">
                                <!-- HEADER HUD -->
                                <div class="bg-slate-900/90 backdrop-blur-sm px-3 py-1.5 flex items-center justify-between border-b border-slate-800/80 z-10">
                                    <div class="flex items-center space-x-2 min-w-0">
                                        <span class="px-1.5 py-0.5 text-[11px] font-black rounded border tracking-wider shrink-0 opacity-90" :class="getDeptBadgeClass(stream.officer?.department)">
                                            {{ stream.officer?.department }} {{ stream.officer?.callsign }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-200 block truncate opacity-90">{{ stream.officer?.officer_name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1 shrink-0">
                                        <!-- Order Rank Badge Button -->
                                        <button @click="emit('toggle-custom-order', stream.video_id)" class="px-1.5 py-0.5 text-[10px] rounded-md transition flex items-center font-mono font-bold border" :class="getCustomOrderRank(stream.video_id) > 0 ? 'text-amber-300 bg-amber-500/15 border-amber-500/40' : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800'" :title="getCustomOrderRank(stream.video_id) > 0 ? `Urutan #${getCustomOrderRank(stream.video_id)}` : 'Klik untuk prioritas urutan grid'">
                                            <span>{{ getCustomOrderRank(stream.video_id) > 0 ? `#${getCustomOrderRank(stream.video_id)}` : '#' }}</span>
                                        </button>
                                        <!-- Audio Switch -->
                                        <button @click="emit('toggle-audio', stream.video_id)" :class="activeAudioVideoId === stream.video_id ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 font-bold' : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800'" class="p-1 rounded-md transition font-mono border">
                                            <img :src="activeAudioVideoId === stream.video_id ? iconUnmute : iconMute" class="w-3 h-3 invert opacity-80" />
                                        </button>
                                        <!-- Personal Stream Pin Toggle -->
                                        <button v-if="stream.status === 'LIVE'" @click="emit('toggle-personal-stream', stream.video_id)" class="p-1 rounded-md transition border" :class="isPersonalStream(stream.video_id) ? 'text-purple-300 bg-purple-950/60 border-purple-500/40' : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800/80'" :title="isPersonalStream(stream.video_id) ? 'Hapus' : 'Pin ke Personal'">
                                            <img :src="isPersonalStream(stream.video_id) ? iconPinMinus : iconPinPlus" class="w-3 h-3 invert opacity-80" />
                                        </button>
                                    </div>
                                </div>

                                <!-- VIDEO DISPLAY: IFRAME OR POSTER THUMBNAIL -->
                                <div class="relative w-full aspect-video bg-black overflow-hidden flex-1 group/thumb">
                                    <template v-if="!isDataSaverEnabled || activeGridVideoIds.includes(stream.video_id) || activeAudioVideoId === stream.video_id">
                                        <iframe :key="`grid-iframe-${stream.video_id}`" :id="`yt-bodycam-${stream.video_id}`" class="w-full h-full border-0 pointer-events-auto" :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                        <button v-if="isDataSaverEnabled" @click="emit('toggle-grid-stream-play', stream.video_id)" class="absolute top-2 left-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-2 py-0.5 rounded border border-white/20 z-20 font-mono">
                                            ✕ Stop Feed
                                        </button>
                                    </template>
                                    <template v-else>
                                        <img :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`" :alt="stream.title" class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 transition duration-300" loading="lazy" />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/40 flex flex-col items-center justify-center p-3">
                                            <div class="absolute top-2 left-2 flex items-center space-x-1 font-mono text-[9px] text-red-400 bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded border border-red-500/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
                                                <span>10-8 LIVE</span>
                                            </div>
                                            <div class="flex items-center space-x-2 opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 transform scale-95 group-hover/thumb:scale-100">
                                                <button @click="emit('update:selectedLayout', 'focus'); emit('set-focus-stream', stream.video_id);" class="bg-blue-600/90 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg transition flex items-center gap-1.5">
                                                    <img :src="iconFocus" class="w-3.5 h-3.5 invert opacity-90" />
                                                    <span>Focus</span>
                                                </button>
                                                <button @click="emit('toggle-grid-stream-play', stream.video_id)" class="bg-emerald-600/90 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg transition flex items-center gap-1.5">
                                                    <img :src="iconPlayAll" class="w-3.5 h-3.5 invert opacity-90" />
                                                    <span>Play</span>
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Floating Hover Overlay Bar -->
                                    <div class="absolute bottom-2 left-2 right-2 opacity-0 group-hover/thumb:opacity-100 transition-all duration-200 bg-slate-950/85 backdrop-blur-md px-2.5 py-1.5 rounded-xl border border-slate-700/70 shadow-2xl flex items-center justify-between z-20 pointer-events-auto text-[11px] text-slate-300">
                                        <div class="flex items-center space-x-2 truncate">
                                            <span class="font-mono text-blue-400 truncate flex items-center gap-1">
                                                <img :src="iconShield" class="w-3 h-3 invert opacity-80 shrink-0" alt="" />
                                                <span>{{ stream.officer?.patrol_zone || "Los Santos Sector" }}</span>
                                            </span>
                                            <span class="text-slate-600">|</span>
                                            <span class="font-mono text-slate-400 truncate">{{ stream.officer?.badge_number || "#000" }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1.5 shrink-0">
                                            <button v-if="stream.officer?.channel_id || stream.officer?.handle" @click="emit('open-subscribe-popup', stream.officer?.channel_id || stream.officer?.handle, stream.officer?.officer_name)" class="p-1 hover:text-red-400 text-slate-300 hover:bg-red-950/40 rounded transition flex items-center gap-0.5 text-[10px] font-bold">
                                                <span class="w-2 h-2 rounded-full bg-red-500 mr-0.5"></span>
                                                <span class="hidden sm:inline">Sub</span>
                                            </button>
                                            <button @click="emit('update:selectedLayout', 'focus'); emit('set-focus-stream', stream.video_id);" class="p-1.5 hover:text-blue-400 text-slate-300 rounded hover:bg-slate-800 transition" title="Focus">
                                                <img :src="iconFocus" class="w-3.5 h-3.5 invert opacity-80 hover:opacity-100" />
                                            </button>
                                            <button @click="emit('update:activeChatVideoId', activeChatVideoId === stream.video_id ? null : stream.video_id)" :class="activeChatVideoId === stream.video_id ? 'bg-amber-600 text-white shadow-md' : 'text-slate-300 hover:text-amber-400 hover:bg-slate-800'" class="p-1.5 rounded transition flex items-center justify-center">
                                                <img :src="activeChatVideoId === stream.video_id ? iconChatRemove : iconChat" class="w-3.5 h-3.5 invert opacity-90" />
                                            </button>
                                            <a :href="`https://www.youtube.com/watch?v=${stream.video_id}`" target="_blank" class="p-1.5 hover:text-white text-slate-300 rounded hover:bg-slate-800 transition">
                                                <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-80 hover:opacity-100" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT SIDEBAR LIVE CHAT -->
                    <div v-if="activeGridChatStream" class="lg:col-span-4 xl:col-span-3 space-y-3 animate-in fade-in zoom-in-95 duration-200">
                        <div class="bg-slate-950 rounded-xl overflow-hidden border border-amber-500/50 shadow-2xl flex flex-col">
                            <div class="bg-slate-900/95 px-3.5 py-2 flex items-center justify-between border-b border-slate-800 text-xs">
                                <div class="flex items-center space-x-2 text-amber-300 font-bold truncate">
                                    <img :src="iconChat" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Live Chat: {{ activeGridChatStream.officer?.officer_name || activeGridChatStream.title }}</span>
                                </div>
                                <button @click="emit('update:activeChatVideoId', null)" class="text-slate-400 hover:text-white text-[11px] font-mono bg-slate-800 hover:bg-slate-700 px-2 py-0.5 rounded transition">
                                    ✕ Close
                                </button>
                            </div>
                            <div class="w-full h-[580px] bg-slate-900 relative">
                                <iframe :key="`chat-grid-${activeGridChatStream.video_id}`" :src="`https://www.youtube.com/live_chat?v=${activeGridChatStream.video_id}&embed_domain=${chatEmbedDomain}&dark_theme=1`" class="w-full h-full border-0" allow="autoplay"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
