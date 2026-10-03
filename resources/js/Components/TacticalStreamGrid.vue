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

// GMeet Dock Popover Controls State
const isDockCategoryOpen = ref(false);
const isDockLayoutOpen = ref(false);
const isDockSearchOpen = ref(false);

const toggleDockCategory = () => {
    isDockCategoryOpen.value = !isDockCategoryOpen.value;
    if (isDockCategoryOpen.value) {
        isDockLayoutOpen.value = false;
        isDockSearchOpen.value = false;
    }
};

const toggleDockLayout = () => {
    isDockLayoutOpen.value = !isDockLayoutOpen.value;
    if (isDockLayoutOpen.value) {
        isDockCategoryOpen.value = false;
        isDockSearchOpen.value = false;
    }
};

const toggleDockSearch = () => {
    isDockSearchOpen.value = !isDockSearchOpen.value;
    if (isDockSearchOpen.value) {
        isDockCategoryOpen.value = false;
        isDockLayoutOpen.value = false;
    }
};

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

        <!-- GOOGLE MEET BOTTOM STICKY CONTROL BAR -->
        <div
            v-if="selectedLayout !== 'focus'"
            class="fixed bottom-0 inset-x-0 z-50 flex flex-col items-center pointer-events-none"
        >
            <!-- Popover 1: Department & Category Selector Menu -->
            <div
                v-if="isDockCategoryOpen"
                class="hidden sm:block pointer-events-auto mb-2.5 w-80 sm:w-96 max-h-[70vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs space-y-3 animate-in fade-in zoom-in-95 font-sans scrollbar-thin scrollbar-thumb-slate-700"
                @click.stop
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-300 font-bold font-mono"
                >
                    <span class="flex items-center gap-1.5 text-blue-400">
                        <img
                            :src="iconAllUnits"
                            class="w-4 h-4 invert opacity-90"
                        />
                        <span>PILIH KATEGORI / DEPARTEMEN</span>
                    </span>
                    <button
                        @click="isDockCategoryOpen = false"
                        class="text-slate-500 hover:text-white p-1"
                    >
                        ✕
                    </button>
                </div>

                <!-- Main Departments Grid -->
                <div class="grid grid-cols-2 gap-1.5">
                    <button
                        @click="
                            emit('update:selectedDepartment', 'ALL');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'ALL'
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="iconAllUnits"
                                class="w-4 h-4 invert opacity-90"
                            />
                            <span>All units</span>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                            >{{ getDeptCount("ALL") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'LSPD');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'LSPD'
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="iconLspd"
                                class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                            />
                            <span>LSPD Metro</span>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                            >{{ getDeptCount("LSPD") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'BCSO');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'BCSO'
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="iconBcso"
                                class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                            />
                            <span>BCSO Sheriff</span>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                            >{{ getDeptCount("BCSO") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'SASP');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'SASP'
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="iconSasp"
                                class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                            />
                            <span>SASP State</span>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                            >{{ getDeptCount("SASP") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'SAPR');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'SAPR'
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="iconSapr"
                                class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                            />
                            <span>SAPR Ranger</span>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                            >{{ getDeptCount("SAPR") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'PERSONAL');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'PERSONAL'
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <img
                                :src="iconPersonal"
                                class="w-4 h-4 invert opacity-90"
                            />
                            <span>Watchlist</span>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                            >{{ getDeptCount("PERSONAL") }}</span
                        >
                    </button>
                </div>

                <!-- TAC Channels Section -->
                <div class="pt-2 border-t border-slate-800 space-y-1.5">
                    <div
                        class="text-[11px] font-mono font-bold text-amber-400 flex items-center gap-1.5"
                    >
                        <img
                            :src="iconRadio"
                            class="w-3.5 h-3.5 invert opacity-90"
                        />
                        <span>KANAL RADIO OPERASIONAL (TAC)</span>
                    </div>
                    <div class="grid grid-cols-5 gap-1">
                        <button
                            v-for="i in 10"
                            :key="`tac-${i}`"
                            @click="
                                emit('update:selectedDepartment', `TAC_${i}`);
                                isDockCategoryOpen = false;
                            "
                            :class="[
                                'p-1.5 rounded-md border text-center transition flex flex-col items-center justify-center gap-0.5 cursor-pointer',
                                selectedDepartment === `TAC_${i}`
                                    ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                    : getDeptCount(`TAC_${i}`) > 0
                                      ? 'bg-amber-950/40 text-amber-300 border-amber-800/50 font-semibold'
                                      : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-800',
                            ]"
                        >
                            <span class="text-[10px] font-mono"
                                >TAC {{ i }}</span
                            >
                            <span
                                class="text-[9px] px-1 rounded font-mono"
                                :class="
                                    getDeptCount(`TAC_${i}`) > 0
                                        ? 'bg-amber-400 text-black font-bold'
                                        : 'bg-slate-900 text-slate-500'
                                "
                            >
                                {{ getDeptCount(`TAC_${i}`) }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Popover 2: Grid Layout Selector Menu -->
            <div
                v-if="isDockLayoutOpen"
                class="hidden sm:block pointer-events-auto mb-2.5 w-64 bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs space-y-2 animate-in fade-in zoom-in-95 font-sans"
                @click.stop
            >
                <div
                    class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-300 font-bold font-mono"
                >
                    <span>TATA LETAK GRID (LAYOUT)</span>
                    <button
                        @click="isDockLayoutOpen = false"
                        class="text-slate-500 hover:text-white p-1"
                    >
                        ✕
                    </button>
                </div>
                <div class="space-y-1">
                    <button
                        v-for="l in [
                            {
                                id: 'auto',
                                name: 'Auto Grid',
                                desc: 'Adaptif sesuai jumlah stream',
                            },
                            {
                                id: 'grid-1x2',
                                name: '1 x 2 Grid',
                                desc: '2 Kamera bersampingan',
                            },
                            {
                                id: 'grid-2x2',
                                name: '2 x 2 Grid',
                                desc: '4 Kamera Quad Matrix',
                            },
                            {
                                id: 'grid-3x3',
                                name: '3 x 3 Grid',
                                desc: '9 Kamera Matrix',
                            },
                            {
                                id: 'grid-4x4',
                                name: '4 x 4 Grid',
                                desc: '16 Kamera Matrix',
                            },
                        ]"
                        :key="l.id"
                        @click="
                            emit('update:selectedLayout', l.id);
                            isDockLayoutOpen = false;
                        "
                        :class="[
                            'w-full p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                            selectedLayout === l.id
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                        ]"
                    >
                        <div>
                            <div class="font-bold text-xs">
                                {{ l.name }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-light">
                                {{ l.desc }}
                            </div>
                        </div>
                        <span v-if="selectedLayout === l.id" class="text-xs"
                            >✓</span
                        >
                    </button>
                </div>
            </div>

            <!-- Popover 3: Quick Search Filter Input Bar -->
            <div
                v-if="isDockSearchOpen"
                class="pointer-events-auto mb-2.5 w-72 sm:w-80 bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs animate-in fade-in zoom-in-95 font-sans"
                @click.stop
            >
                <div class="relative flex items-center">
                    <input
                        type="text"
                        :value="searchFilter"
                        @input="
                            emit('update:searchFilter', $event.target.value)
                        "
                        placeholder="Cari perwira, callsign, divisi, judul..."
                        class="w-full bg-slate-950 text-white placeholder-slate-500 text-xs px-3 py-2 pr-8 rounded-md border border-slate-800 focus:outline-none focus:border-blue-500 font-sans"
                    />
                    <button
                        v-if="searchFilter"
                        @click="emit('update:searchFilter', '')"
                        class="absolute right-2.5 text-slate-400 hover:text-white text-xs font-bold p-0.5 rounded"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- BOTTOM STICKY CONTROL BAR (DESKTOP ONLY, HIDDEN ON MOBILE TO PREVENT UI CLUTTER) -->
            <div
                class="hidden sm:flex pointer-events-auto w-full bg-slate-950 border-t border-slate-800 px-4 py-2.5 items-center justify-center gap-3 text-xs text-slate-200 overflow-x-auto scrollbar-none"
                @click.stop
            >
                <!-- Home / Beranda Button -->
                <Link
                    href="/"
                    class="px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    title="Kembali ke Dashboard Utama"
                >
                    <svg
                        class="w-3.5 h-3.5 text-slate-400 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                        />
                    </svg>
                    <span>Home</span>
                </Link>
                <!-- Category Selector Button -->
                <button
                    @click="toggleDockCategory"
                    :class="[
                        'hidden sm:flex px-3 py-1.5 rounded-md text-xs font-bold border transition items-center gap-1.5 shrink-0 cursor-pointer',
                        isDockCategoryOpen
                            ? 'bg-slate-800 text-blue-400 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                    title="Pilih Kategori / Departemen Kesatuan"
                >
                    <img
                        :src="getDeptIcon(selectedDepartment)"
                        class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                    />
                    <span class="font-mono text-[11px]">{{
                        selectedDeptLabel
                    }}</span>
                    <span
                        class="px-1.5 py-0.2 rounded bg-slate-950 border border-slate-800 text-blue-400 text-[10px] font-mono font-bold"
                        >{{ visibleStreams.length }}</span
                    >
                    <span class="text-[10px] text-slate-400">▾</span>
                </button>

                <div
                    class="hidden sm:block h-4 w-px bg-slate-800 shrink-0"
                ></div>

                <!-- Layout Selector Button -->
                <button
                    @click="toggleDockLayout"
                    :class="[
                        'hidden sm:flex px-3 py-1.5 rounded-md text-xs font-bold border transition items-center gap-1.5 shrink-0 cursor-pointer',
                        isDockLayoutOpen
                            ? 'bg-slate-800 text-blue-400 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                    title="Pilih Tata Letak Kamera (Grid Layout)"
                >
                    <img
                        :src="iconGrid4"
                        class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                        alt="Grid Layout"
                    />
                    <span class="font-mono text-[11px]">{{
                        selectedLayoutLabel
                    }}</span>
                    <span class="text-[10px] text-slate-400">▾</span>
                </button>

                <div
                    class="hidden sm:block h-4 w-px bg-slate-800 shrink-0"
                ></div>

                <!-- Quick Search Filter Button -->
                <button
                    @click="toggleDockSearch"
                    :class="[
                        'flex p-2 rounded-md border transition shrink-0 relative cursor-pointer',
                        isDockSearchOpen || searchFilter
                            ? 'bg-slate-800 text-blue-400 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800',
                    ]"
                    title="Cari Petugas / Stream"
                >
                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    <span
                        v-if="searchFilter"
                        class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-blue-400 rounded-full"
                    ></span>
                </button>

                <!-- Officer Visibility Bottom Sheet Button ([⚙️ Kelola]) -->
                <button
                    @click="emit('open-officer-visibility')"
                    class="flex px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition items-center gap-1.5 shrink-0 cursor-pointer"
                    title="Kelola Tampilan / Enable Disable Perwira (Bottom Sheet)"
                >
                    <svg
                        class="w-3.5 h-3.5 text-slate-400 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    <span>Kelola</span>
                </button>

                <!-- Sync / Refresh Feeds Button -->
                <button
                    @click="emit('trigger-manual-sync')"
                    :disabled="isSyncingFeeds"
                    class="p-2 rounded-md bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-slate-300 border border-slate-800 transition shrink-0 cursor-pointer"
                    title="Sinkronkan Feeds Live Terbaru"
                >
                    <img
                        :src="iconRefresh"
                        class="w-3.5 h-3.5 invert opacity-90"
                        :class="{ 'animate-spin': isSyncingFeeds }"
                    />
                </button>

                <!-- Data Saver Toggle Button -->
                <button
                    @click="
                        isDataSaverEnabled
                            ? emit('disable-data-saver')
                            : emit('enable-data-saver')
                    "
                    :class="[
                        'px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        isDataSaverEnabled
                            ? 'bg-slate-800 text-amber-400 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800',
                    ]"
                    :title="
                        isDataSaverEnabled
                            ? 'Hemat Data Aktif (Tutup untuk otomatis play)'
                            : 'Aktifkan Hemat Data'
                    "
                >
                    <img
                        :src="iconSaver"
                        class="w-3.5 h-3.5 invert opacity-90"
                    />
                    <span>{{
                        isDataSaverEnabled ? "Saver ON" : "Saver OFF"
                    }}</span>
                </button>

                <div
                    class="hidden sm:block h-4 w-px bg-slate-800 shrink-0"
                ></div>

                <!-- Theater / Bioskop Mode Toggle (100% Full Width vs 2XL Boxed) -->
                <button
                    @click="emit('toggle-theater-mode')"
                    class="hidden sm:flex px-3 py-1.5 rounded-md text-[11px] font-mono font-bold border transition items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="
                        isTheaterMode
                            ? 'bg-slate-800 text-blue-400 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800'
                    "
                    :title="
                        isTheaterMode
                            ? 'Mode Bioskop Aktif (100% Lebar Layar). Klik untuk kembali ke Ukuran 2XL Standar'
                            : 'Mode Bioskop (Perluas Layar 100% Full Width tanpa Margin)'
                    "
                >
                    <img
                        :src="iconTheater"
                        class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                        alt="Bioskop"
                    />
                    <span>Bioskop</span>
                </button>

                <!-- Audio Switch Button -->
                <button
                    @click="
                        emit(
                            'toggle-audio',
                            selectedLayout === 'focus' && primaryFocusedStream
                                ? primaryFocusedStream.video_id
                                : (activeAudioVideoId || (visibleStreams[0] ? visibleStreams[0].video_id : null)),
                        )
                    "
                    class="px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                    :class="
                        (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                        (selectedLayout !== 'focus' && activeAudioVideoId)
                            ? 'bg-slate-800 text-emerald-400 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800'
                    "
                    :title="
                        (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                        (selectedLayout !== 'focus' && activeAudioVideoId)
                            ? 'Suara Aktif (Klik untuk Mute)'
                            : 'Aktivasi Suara Feed'
                    "
                >
                    <img
                        :src="
                            (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                            (selectedLayout !== 'focus' && activeAudioVideoId)
                                ? iconUnmute
                                : iconMute
                        "
                        class="w-3.5 h-3.5 invert opacity-90"
                    />
                    <span>{{
                        (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                        (selectedLayout !== 'focus' && activeAudioVideoId)
                            ? "Audio ON"
                            : "Audio OFF"
                    }}</span>
                </button>

                <!-- Fullscreen Toggle -->
                <button
                    @click="emit('toggle-fullscreen')"
                    class="hidden sm:flex p-2 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition shrink-0 cursor-pointer"
                    title="Toggle Layar Penuh (Fullscreen)"
                >
                    <img
                        :src="iconFullscreen"
                        class="w-3.5 h-3.5 invert opacity-90"
                    />
                </button>
            </div>
        </div>
    </div>
</template>
