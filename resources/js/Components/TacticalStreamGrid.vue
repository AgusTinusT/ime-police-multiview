<script setup>
/**
 * ==============================================================================
 * SPESIFIKASI DATA & CONTRACT INTEGRASI - TACTICAL STREAM GRID & FOCUS MODE
 * ==============================================================================
 * 
 * 1. STATE & PROPS MODE FOKUS (FocusModePlayer):
 *    - focusStream: Object (Stream perwira aktif yang sedang dalam Mode Fokus)
 *    - visibleStreams / allActiveStreams: Array (Daftar stream aktif dari YouTube Scraper)
 *    - activeTacChannel: String (Channel Radio TAC aktif, misal 'TAC_1')
 *    - isTheaterMode: Boolean (Status tampilan bioskop)
 *    - isRightChatOpen: Boolean (Status panel Live Chat)
 * 
 * 2. ENDPOINT & DOKUMEN SCRAPER YOUTUBE:
 *    - GET /api/streams/live             : Mengambil seluruh siaran live patroli aktif
 *    - GET /api/streams/description/{id} : Mengambil deskripsi lengkap streamer jika tidak ada di feed awal
 * 
 * ==============================================================================
 * CATATAN INTEGRASI:
 * Jika integrasi data stream real-time & controller ini sudah selesai dikerjakan,
 * HAPUS BLOK KOMENTAR INI.
 * ==============================================================================
 */
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";

const { toggleOfficerStatus, getOfficerKey } = useOfficerFilter();

// SVG Icon Assets & Branding Logos
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";
import iconSaver from "@/Components/Icons/gauge-low-svgrepo-com.svg";
import iconPlayAll from "@/Components/Icons/play-full-svgrepo-com.svg";
import iconPause from "@/Components/Icons/controller-paus-svgrepo-com.svg";
import iconMute from "@/Components/Icons/audio-off-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/audio-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-tac-svgrepo-com.svg";
import iconChat from "@/Components/Icons/chat-svgrepo-com.svg";
import iconChatRemove from "@/Components/Icons/chat-remove-svgrepo-com.svg";
import iconClock from "@/Components/Icons/time-svgrepo-com.svg";
import iconFullscreen from "@/Components/Icons/full-screen-svgrepo-com.svg";
import iconDoc from "@/Components/Icons/document-svgrepo-com.svg";
import iconCloseBold from "@/Components/Icons/close-bold-svgrepo-com.svg";
import iconGrid4 from "@/Components/Icons/grid-4-svgrepo-com.svg";
import iconTheater from "@/Components/Icons/rectangle-o-svgrepo-com.svg";

import AnnouncementBanner from "@/Components/AnnouncementBanner.vue";
import TacChannelToolbar from "@/Components/TacChannelToolbar.vue";
import FocusModePlayer from "@/Components/FocusModePlayer.vue";
import TacticalStreamCard from "@/Components/TacticalStreamCard.vue";
import TacticalDockControlBar from "@/Components/TacticalDockControlBar.vue";

const props = defineProps({
    mode: { type: String, default: "gmeet" }, // 'gmeet' (Multiview Grid) or 'netflix' (Dashboard Swimlane Discovery)
    selectedDepartment: { type: String, required: true },
    selectedLayout: { type: String, default: "auto" },
    searchFilter: { type: String, default: "" },
    isDataSaverEnabled: { type: Boolean, default: false },
    isSyncingFeeds: { type: Boolean, default: false },
    isTheaterMode: { type: Boolean, default: false },
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
    "toggle-theater-mode",
    "toggle-fullscreen",
    "toggle-personal-stream",
    "toggle-custom-order",
    "assign-stream-to-tac",
    "remove-stream-from-tac",
    "open-subscribe-popup",
    "open-quick-add",
    "open-officer-visibility",
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
    "update:searchFilter",
]);



const getDeptCount = (deptId) => {
    if (deptId === "ALL") return props.allActiveStreams?.length || 0;
    if (deptId === "PERSONAL") return props.activePersonalStreams?.length || 0;
    if (deptId && deptId.startsWith("TAC_")) {
        const ch = (props.tacChannels || []).find((c) => c.code === deptId);
        if (!ch) return 0;
        return ch.video_ids?.length || ch.unit_count || 0;
    }
    return (props.allActiveStreams || []).filter((s) => {
        const d = (s.officer?.department || "").toUpperCase();
        if (deptId === "SAPR") return d === "SAPR" || d === "PARK RANGER";
        return d === deptId;
    }).length;
};

const selectedDeptLabel = computed(() => {
    const d = props.selectedDepartment;
    if (d === "ALL") return "All units";
    if (d === "LSPD") return "LSPD Metro";
    if (d === "BCSO") return "BCSO Sheriff";
    if (d === "SASP") return "SASP State";
    if (d === "SAPR") return "SAPR Ranger";
    if (d === "PERSONAL") return "Watchlist";
    if (d && d.startsWith("TAC_")) return d.replace("_", " ");
    return d || "All units";
});

const selectedLayoutLabel = computed(() => {
    const l = props.selectedLayout;
    if (l === "grid-1x2") return "1x2 Grid";
    if (l === "grid-2x2") return "2x2 Grid";
    if (l === "grid-3x3") return "3x3 Grid";
    if (l === "grid-4x4") return "4x4 Grid";
    return "Auto Grid";
});

// Stopped / Closed Video Feeds State (Mode Stop Feed)
const stoppedVideoIds = ref([]);

const isStreamStopped = (videoId) => {
    if (!videoId) return false;
    return stoppedVideoIds.value.includes(videoId);
};

const isVideoPlaying = (videoId) => {
    if (!videoId) return false;
    if (stoppedVideoIds.value.includes(videoId)) return false;
    if (props.isDataSaverEnabled) {
        return (
            (props.activeGridVideoIds && props.activeGridVideoIds.includes(videoId)) ||
            (props.activePreviewVideoIds && props.activePreviewVideoIds.includes(videoId)) ||
            props.activeAudioVideoId === videoId
        );
    }
    return true;
};

const toggleStopFeed = (videoId) => {
    if (!videoId) return;
    const playing = isVideoPlaying(videoId);
    if (playing) {
        // Currently playing -> Stop / Close video feed (Return to poster thumbnail)
        if (!stoppedVideoIds.value.includes(videoId)) {
            stoppedVideoIds.value.push(videoId);
        }
        if (
            props.activeGridVideoIds &&
            props.activeGridVideoIds.includes(videoId)
        ) {
            emit("toggle-grid-stream-play", videoId);
        }
        if (
            props.activePreviewVideoIds &&
            props.activePreviewVideoIds.includes(videoId)
        ) {
            emit("toggle-sidebar-preview", videoId);
        }
        if (props.activeAudioVideoId === videoId) {
            emit("toggle-audio", videoId);
        }
    } else {
        // Currently stopped / showing poster thumbnail -> Resume / Start video feed
        const idx = stoppedVideoIds.value.indexOf(videoId);
        if (idx !== -1) {
            stoppedVideoIds.value.splice(idx, 1);
        }
        if (
            props.activeGridVideoIds &&
            !props.activeGridVideoIds.includes(videoId)
        ) {
            emit("toggle-grid-stream-play", videoId);
        }
        if (
            props.activePreviewVideoIds &&
            !props.activePreviewVideoIds.includes(videoId)
        ) {
            emit("toggle-sidebar-preview", videoId);
        }
    }
};

const handleSetFocusStream = (videoId) => {
    const isMobile = typeof window !== "undefined" && window.innerWidth < 640;
    if (isMobile) {
        emit("toggle-theater-mode", true);
    }
    const idx = stoppedVideoIds.value.indexOf(videoId);
    if (idx !== -1) {
        stoppedVideoIds.value.splice(idx, 1);
    }
    emit("update:selectedLayout", "focus");
    emit("set-focus-stream", videoId);
};

// Focus Mode Right-Column Live Chat State
const isRightChatOpen = ref(true);
const openInfoVideoIds = ref([]);
const fetchedDescriptions = ref({});

const handleChatClick = (streamId) => {
    const isMobile = typeof window !== "undefined" && window.innerWidth < 640;
    if (isMobile) {
        emit("toggle-theater-mode", true);
        emit("update:selectedLayout", "focus");
        emit("set-focus-stream", streamId);
        isRightChatOpen.value = true;
    } else {
        emit(
            "update:activeChatVideoId",
            props.activeChatVideoId === streamId ? null : streamId,
        );
    }
};

const isStreamInfoOpen = (videoId) => openInfoVideoIds.value.includes(videoId);

const getStreamDescription = (stream) => {
    if (!stream) return "";
    if (fetchedDescriptions.value[stream.video_id]) {
        return fetchedDescriptions.value[stream.video_id];
    }
    return stream.description || "";
};

const toggleStreamInfo = async (videoId) => {
    if (isStreamInfoOpen(videoId)) {
        openInfoVideoIds.value = openInfoVideoIds.value.filter(
            (id) => id !== videoId,
        );
    } else {
        openInfoVideoIds.value.push(videoId);

        const targetStream =
            (props.allActiveStreams || []).find(
                (s) => s.video_id === videoId,
            ) ||
            (props.visibleStreams || []).find((s) => s.video_id === videoId) ||
            (props.focusStream && props.focusStream.video_id === videoId
                ? props.focusStream
                : null);

        const currentDesc =
            (targetStream && targetStream.description) ||
            fetchedDescriptions.value[videoId] ||
            "";

        if (
            !fetchedDescriptions.value[videoId] &&
            (!currentDesc ||
                currentDesc.endsWith("...") ||
                currentDesc.length < 300)
        ) {
            try {
                const response = await fetch(
                    `/api/stream-details?video_id=${videoId}`,
                );
                const json = await response.json();
                if (
                    json.status === "success" &&
                    json.data &&
                    json.data.description
                ) {
                    fetchedDescriptions.value[videoId] = json.data.description;
                    if (targetStream) {
                        targetStream.description = json.data.description;
                        if (json.data.title) {
                            targetStream.title = json.data.title;
                        }
                    }
                }
            } catch (err) {
                console.warn("Could not fetch full stream description:", err);
            }
        }
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
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline hover:text-blue-300">$1</a>',
    );
};

// Computed Primary & Secondary Streams for Focus Layout
const primaryFocusedStream = computed(() => {
    if (props.focusStream) return props.focusStream;
    return props.visibleStreams.length > 0 ? props.visibleStreams[0] : null;
});

const secondaryStreams = computed(() => {
    if (props.sideStreams && props.sideStreams.length > 0)
        return props.sideStreams;
    const lead = primaryFocusedStream.value;
    if (!lead) return props.visibleStreams;
    return props.visibleStreams.filter((s) => s.video_id !== lead.video_id);
});

const displayedGridStreams = computed(() => {
    return props.gridStreams && props.gridStreams.length > 0
        ? props.gridStreams
        : props.visibleStreams;
});

// Dynamic Grid Layout CSS Class (Google Meet Viewport Responsive Grid)
const layoutGridClass = computed(() => {
    const layout = props.selectedLayout;
    const count = displayedGridStreams.value.length;

    if (layout === "grid-1x2")
        return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (layout === "grid-2x2")
        return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (layout === "grid-3x3")
        return "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3";
    if (layout === "grid-4x4")
        return "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3";

    // Auto GMeet Mode scaling based on stream count
    if (count <= 1) return "grid grid-cols-1 gap-3 sm:gap-4";
    if (count <= 2) return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (count <= 4) return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (count <= 9)
        return "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3";
    return "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3";
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
    if (d.includes("LSPD"))
        return "bg-blue-900/60 text-blue-200 border-blue-700/80";
    if (d.includes("BCSO") || d.includes("LSCSD"))
        return "bg-amber-900/60 text-amber-200 border-amber-700/80";
    if (d.includes("SASP"))
        return "bg-teal-900/60 text-teal-200 border-teal-700/80";
    if (d.includes("SAPR") || d.includes("RANGER"))
        return "bg-emerald-900/60 text-emerald-200 border-emerald-700/80";
    return "bg-slate-800 text-slate-300 border-slate-700";
};

// Department Icon Mapper
const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return iconSasp;
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
    if (
        stream.is_officer_card ||
        (typeof stream.video_id === "string" &&
            stream.video_id.startsWith("officer-"))
    ) {
        const handle = stream.officer?.handle
            ? stream.officer.handle.replace(/^@/, "")
            : "";
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
        (id) => String(id).trim().toLowerCase() === target,
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

const getCustomOrderRank = (item) => {
    if (!item || !props.customOrderMap) return 0;
    const key = getOfficerKey(item) || (typeof item === "string" ? item.trim() : (item.video_id ? String(item.video_id).trim() : ""));
    if (!key) return 0;

    if (Array.isArray(props.customOrderMap)) {
        const idx = props.customOrderMap.indexOf(key);
        if (idx !== -1) return idx + 1;
        if (typeof item === "object" && item.video_id) {
            const vKey = String(item.video_id).trim();
            const vIdx = props.customOrderMap.indexOf(vKey);
            if (vIdx !== -1) return vIdx + 1;
        }
        return 0;
    }
    if (typeof props.customOrderMap === "function") {
        return props.customOrderMap(key);
    }
    return props.customOrderMap[key] || 0;
};
</script>

<template>
    <!-- GLOBAL PROMO / ANNOUNCEMENT BANNERS -->
    <AnnouncementBanner
        v-if="activeTab === '10-8' && selectedDepartment === 'ALL'"
        :announcements="activeAnnouncements"
    />

    <!-- TAB 1: 10-8 ACTIVE LIVE BODYCAM FEEDS -->
    <div
        v-if="activeTab === '10-8'"
        class="w-full flex flex-col relative space-y-4 pb-24"
    >
        <!-- GMEET CCTV MULTIVIEW GRID -->
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
            class="relative rounded-xl overflow-hidden border border-slate-800 bg-slate-900 p-8 text-center flex flex-col items-center justify-center gap-3 animate-in fade-in duration-200"
        >
            <div
                class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-blue-400 mb-1"
            >
                <img
                    :src="getDeptIcon(selectedDepartment)"
                    class="w-7 h-7 invert opacity-80"
                    alt=""
                />
            </div>
            <h3
                class="text-lg font-bold text-slate-100 uppercase tracking-wider"
            >
                TIDAK ADA SIARAN AKTIF (10-8) DI KATEGORI
                {{ selectedDepartment.replace("_", " ") }}
            </h3>
            <p class="text-xs text-slate-400 max-w-md">
                Saat ini belum ada siaran langsung untuk kesatuan
                {{ selectedDepartment.replace("_", " ") }}. Anda dapat
                menyinkronkan data terbaru atau menampilkan seluruh unit.
            </p>
            <div class="flex items-center gap-2 mt-2 flex-wrap justify-center">
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
                    @click="emit('update:selectedDepartment', 'ALL')"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition"
                >
                    Tampilkan Seluruh Unit
                </button>
            </div>
        </div>

        <!-- FOCUS LAYOUT MODE (1 Lead Screen + Supporting Unit Grid Below / Side) -->
        <div
            v-else-if="selectedLayout === 'focus'"
            class="space-y-4"
            :class="{ 'pt-1.5 sm:pt-1.5': isTheaterMode }"
        >
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <!-- MAIN LEAD PLAYER COLUMN -->
                <div
                    :class="
                        isRightChatOpen
                            ? 'lg:col-span-8 xl:col-span-9'
                            : 'lg:col-span-12'
                    "
                    class="space-y-3"
                >
                    <FocusModePlayer
                        v-if="primaryFocusedStream"
                        :stream="primaryFocusedStream"
                        :originUrl="originUrl"
                        :activeAudioVideoId="activeAudioVideoId"
                        :isStreamStopped="isStreamStopped(primaryFocusedStream.video_id)"
                        :isRightChatOpen="isRightChatOpen"
                        :isTheaterMode="isTheaterMode"
                        :activeTacChannel="getStreamTac(primaryFocusedStream.video_id)"
                        :isPersonalStream="isPersonalStream(primaryFocusedStream.officer?.handle || primaryFocusedStream.video_id)"
                        :fetchedDescription="getStreamDescription(primaryFocusedStream)"
                        @toggle-audio="emit('toggle-audio', primaryFocusedStream.video_id)"
                        @toggle-stop-feed="toggleStopFeed(primaryFocusedStream.video_id)"
                        @toggle-right-chat="isRightChatOpen = !isRightChatOpen"
                        @toggle-theater-mode="emit('toggle-theater-mode')"
                        @toggle-tac-popover="emit('update:activeTacPopoverVideoId', activeTacPopoverVideoId === primaryFocusedStream.video_id ? null : primaryFocusedStream.video_id)"
                        @assign-tac="(tacCode) => emit('assign-stream-to-tac', tacCode, primaryFocusedStream.video_id)"
                        @remove-tac="emit('remove-stream-from-tac', primaryFocusedStream.video_id)"
                        @toggle-personal-stream="emit('toggle-personal-stream', primaryFocusedStream.officer?.handle || primaryFocusedStream.video_id)"
                        @close-focus="emit('update:selectedLayout', 'auto')"
                    />
                </div>

                <!-- RIGHT COLUMN: LIVE CHAT & SUPPORTING UNITS -->
                <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-3">
                    <div
                        v-if="isRightChatOpen && primaryFocusedStream"
                        class="bg-slate-900 rounded-xl overflow-hidden border border-slate-800 flex flex-col animate-in fade-in zoom-in-95 duration-200"
                    >
                        <div
                            class="bg-slate-900/95 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 text-xs"
                        >
                            <div
                                class="flex items-center space-x-1.5 text-blue-300 font-bold truncate"
                            >
                                <img
                                    :src="iconChat"
                                    class="w-3.5 h-3.5 invert opacity-90"
                                    alt=""
                                />
                                <span class="truncate"
                                    >Live Chat:
                                    {{
                                        primaryFocusedStream.officer
                                            ?.officer_name
                                    }}</span
                                >
                            </div>
                            <button
                                @click="isRightChatOpen = false"
                                class="text-slate-400 hover:text-white text-[11px] font-mono bg-slate-800 hover:bg-slate-700 px-1.5 py-0.2 rounded"
                            >
                                ✕ Close
                            </button>
                        </div>
                        <div
                            class="w-full h-[350px] sm:h-[520px] bg-slate-900 relative"
                        >
                            <iframe
                                :key="`chat-lead-${primaryFocusedStream.video_id}`"
                                :src="`https://www.youtube.com/live_chat?v=${primaryFocusedStream.video_id}&embed_domain=${chatEmbedDomain}&dark_theme=1`"
                                class="w-full h-full border-0"
                                allow="autoplay"
                            ></iframe>
                        </div>
                    </div>

                    <!-- SUPPORTING UNITS FEED LIST -->
                    <div class="space-y-3">
                        <TacticalStreamCard
                            v-for="stream in secondaryStreams"
                            :key="`side-feed-${stream.video_id}`"
                            :stream="stream"
                            :activeAudioVideoId="activeAudioVideoId"
                            :activeChatVideoId="activeChatVideoId"
                            :originUrl="originUrl"
                            :customOrderRank="getCustomOrderRank(stream)"
                            :isVideoPlaying="isVideoPlaying(stream.video_id)"
                            @toggle-audio="emit('toggle-audio', $event)"
                            @toggle-stop-feed="toggleStopFeed($event)"
                            @set-focus-stream="handleSetFocusStream($event)"
                            @handle-chat-click="handleChatClick($event)"
                            @toggle-officer-status="toggleOfficerStatus($event)"
                            @update:selectedLayout="emit('update:selectedLayout', $event)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- STANDARD GRID VIEWS (Auto, 2x2, 3x3, 4x4) -->
        <div v-else class="space-y-3">
            <!-- MAIN GRID VIEW -->
            <div
                :class="
                    activeGridChatStream
                        ? 'grid grid-cols-1 lg:grid-cols-12 gap-4 items-start'
                        : ''
                "
            >
                <div
                    :class="
                        activeGridChatStream
                            ? 'lg:col-span-8 xl:col-span-9'
                            : 'w-full'
                    "
                >
                    <div :class="layoutGridClass">
                        <TacticalStreamCard
                            v-for="stream in displayedGridStreams"
                            :key="`grid-card-${stream.video_id}`"
                            :stream="stream"
                            :activeAudioVideoId="activeAudioVideoId"
                            :activeChatVideoId="activeChatVideoId"
                            :originUrl="originUrl"
                            :customOrderRank="getCustomOrderRank(stream)"
                            :isVideoPlaying="isVideoPlaying(stream.video_id)"
                            @toggle-audio="emit('toggle-audio', $event)"
                            @toggle-stop-feed="toggleStopFeed($event)"
                            @set-focus-stream="handleSetFocusStream($event)"
                            @handle-chat-click="handleChatClick($event)"
                            @toggle-officer-status="toggleOfficerStatus($event)"
                            @update:selectedLayout="emit('update:selectedLayout', $event)"
                        />
                    </div>
                </div>

                <!-- RIGHT SIDEBAR LIVE CHAT -->
                <div
                    v-if="activeGridChatStream"
                    class="lg:col-span-4 xl:col-span-3 space-y-3 animate-in fade-in zoom-in-95 duration-200"
                >
                    <div
                        class="bg-slate-900 rounded-xl overflow-hidden border border-slate-800 flex flex-col"
                    >
                        <div
                            class="bg-slate-900/95 px-3.5 py-2 flex items-center justify-between border-b border-slate-800 text-xs"
                        >
                            <div
                                class="flex items-center space-x-2 text-blue-300 font-bold truncate"
                            >
                                <img
                                    :src="iconChat"
                                    class="w-3.5 h-3.5 invert opacity-90"
                                    alt=""
                                />
                                <span class="truncate"
                                    >Live Chat:
                                    {{
                                        activeGridChatStream.officer
                                            ?.officer_name ||
                                        activeGridChatStream.title
                                    }}</span
                                >
                            </div>
                            <button
                                @click="emit('update:activeChatVideoId', null)"
                                class="text-slate-400 hover:text-white text-[11px] font-mono bg-slate-800 hover:bg-slate-700 px-2 py-0.5 rounded transition"
                            >
                                ✕ Close
                            </button>
                        </div>
                        <div
                            class="w-full h-[260px] sm:h-[580px] bg-slate-900 relative"
                        >
                            <iframe
                                :key="`chat-grid-${activeGridChatStream.video_id}`"
                                :src="`https://www.youtube.com/live_chat?v=${activeGridChatStream.video_id}&embed_domain=${chatEmbedDomain}&dark_theme=1`"
                                class="w-full h-full border-0"
                                allow="autoplay"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GOOGLE MEET BOTTOM STICKY CONTROL BAR (Refactored Level 3) -->
        <TacticalDockControlBar
            :selectedDepartment="selectedDepartment"
            :selectedLayout="selectedLayout"
            :searchFilter="searchFilter"
            :isDataSaverEnabled="isDataSaverEnabled"
            :isSyncingFeeds="isSyncingFeeds"
            :isTheaterMode="isTheaterMode"
            :activeAudioVideoId="activeAudioVideoId"
            :visibleStreamsCount="visibleStreams.length"
            :getDeptCount="getDeptCount"
            :selectedDeptLabel="selectedDeptLabel"
            :selectedLayoutLabel="selectedLayoutLabel"
            :primaryFocusedStream="primaryFocusedStream"
            @update:selectedDepartment="emit('update:selectedDepartment', $event)"
            @update:selectedLayout="emit('update:selectedLayout', $event)"
            @update:searchFilter="emit('update:searchFilter', $event)"
            @trigger-manual-sync="emit('trigger-manual-sync')"
            @toggle-fullscreen="emit('toggle-fullscreen')"
            @toggle-theater-mode="emit('toggle-theater-mode')"
            @toggle-audio="emit('toggle-audio', $event)"
            @disable-data-saver="emit('disable-data-saver')"
            @enable-data-saver="emit('enable-data-saver')"
            @open-officer-visibility="emit('open-officer-visibility')"
        />
    </div>
</template>
