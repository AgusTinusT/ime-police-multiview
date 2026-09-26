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
import iconPause from "@/Components/Icons/controller-paus-svgrepo-com.svg";
import iconMute from "@/Components/Icons/audio-off-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/audio-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconPinPlus from "@/Components/Icons/star-line-svgrepo-com.svg";
import iconPinMinus from "@/Components/Icons/star-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-tac-svgrepo-com.svg";
import iconReset from "@/Components/Icons/reset-svgrepo-com.svg";
import iconChat from "@/Components/Icons/chat-svgrepo-com.svg";
import iconChatRemove from "@/Components/Icons/chat-remove-svgrepo-com.svg";
import iconClock from "@/Components/Icons/time-svgrepo-com.svg";
import iconTarget from "@/Components/Icons/target-svgrepo-com.svg";
import iconFullscreen from "@/Components/Icons/full-screen-svgrepo-com.svg";
import iconDoc from "@/Components/Icons/document-svgrepo-com.svg";
import iconCloseBold from "@/Components/Icons/close-bold-svgrepo-com.svg";
import iconGrid4 from "@/Components/Icons/grid-4-svgrepo-com.svg";
import iconTheater from "@/Components/Icons/rectangle-o-svgrepo-com.svg";

import AnnouncementBanner from "@/Components/AnnouncementBanner.vue";
import TacChannelToolbar from "@/Components/TacChannelToolbar.vue";

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

const toggleStopFeed = (videoId) => {
    if (!videoId) return;
    const idx = stoppedVideoIds.value.indexOf(videoId);
    if (idx === -1) {
        // Close / Stop video feed -> Return to Stop Feed mode (Thumbnail)
        stoppedVideoIds.value.push(videoId);
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
        // Resume / Play video feed
        stoppedVideoIds.value.splice(idx, 1);
        if (props.isDataSaverEnabled) {
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
    }
};

const handleSetFocusStream = (videoId) => {
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
        return "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5";

    // Auto GMeet Mode scaling based on stream count
    if (count <= 1) return "grid grid-cols-1 gap-3 sm:gap-4";
    if (count <= 2) return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (count <= 4) return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (count <= 9)
        return "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3";
    return "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5";
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
            class="relative rounded-2xl overflow-hidden border border-slate-800/80 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-8 text-center flex flex-col items-center justify-center gap-3 animate-in fade-in duration-200"
        >
            <div
                class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-1"
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
                menyinkronkan data terbaru atau melihat daftar anggota di 10-7
                Roster.
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
                    @click="emit('update:activeTab', '10-7')"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition"
                >
                    Lihat 10-7 Roster
                </button>
                <button
                    @click="emit('update:selectedDepartment', 'ALL')"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition"
                >
                    Tampilkan Seluruh Unit
                </button>
            </div>

            <!-- Offline Roster Officers of this Department -->
            <div
                v-if="filteredOfflineOfficers.length > 0"
                class="w-full mt-6 pt-6 border-t border-slate-800/80 text-left"
            >
                <h4
                    class="text-xs font-bold text-slate-300 font-mono mb-3 uppercase tracking-wider"
                >
                    Petugas 10-7 (Offline) Kesatuan
                    {{ selectedDepartment.replace("_", " ") }}:
                </h4>
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5"
                >
                    <div
                        v-for="officer in filteredOfflineOfficers"
                        :key="`dept-off-${officer.channel_id}`"
                        class="bg-slate-900/60 border border-slate-800 rounded-lg p-2 flex flex-col justify-between"
                    >
                        <div class="truncate">
                            <div
                                class="text-[11px] font-bold text-slate-200 truncate"
                            >
                                {{ officer.officer_name }}
                            </div>
                            <div
                                v-if="officer.rank"
                                class="text-[9px] text-slate-500 font-mono truncate"
                            >
                                {{ officer.rank }}
                            </div>
                        </div>
                        <div
                            class="mt-2 pt-1 border-t border-slate-800/80 flex items-center justify-between"
                        >
                            <button
                                @click="
                                    emit(
                                        'open-subscribe-popup',
                                        officer.channel_id || officer.handle,
                                        officer.officer_name,
                                    )
                                "
                                class="text-red-400 hover:text-red-300 text-[9px] font-mono font-bold"
                            >
                                +Sub
                            </button>
                            <a
                                :href="`https://www.youtube.com/${officer.handle}`"
                                target="_blank"
                                class="text-slate-500 hover:text-slate-300"
                            >
                                <img
                                    :src="iconExternal"
                                    class="w-2.5 h-2.5 invert opacity-60"
                                />
                            </a>
                        </div>
                    </div>
                </div>
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
                    <div
                        v-if="primaryFocusedStream"
                        class="bg-slate-950 rounded-2xl overflow-hidden border border-blue-500/40 shadow-2xl shadow-blue-950/20"
                    >
                        <!-- Player Header & Action HUD -->
                        <div
                            class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 px-4 py-2 flex flex-wrap items-center justify-between border-b border-slate-800/80 gap-2"
                        >
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <span
                                    class="px-2 py-0.5 text-xs font-black rounded border tracking-wider shrink-0 shadow"
                                    :class="
                                        getDeptBadgeClass(
                                            primaryFocusedStream.officer
                                                ?.department,
                                        )
                                    "
                                >
                                    {{
                                        primaryFocusedStream.officer
                                            ?.department || "UNIT"
                                    }}
                                </span>
                                <div class="truncate">
                                    <h3
                                        class="text-sm font-bold text-slate-100 flex items-center gap-2 truncate"
                                    >
                                        <span class="truncate">{{
                                            primaryFocusedStream.officer
                                                ?.officer_name || "Officer"
                                        }}</span>
                                    </h3>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <!-- TAC Channel Dispatch Selector -->
                                <div class="relative">
                                    <button
                                        @click.stop="
                                            emit(
                                                'update:activeTacPopoverVideoId',
                                                activeTacPopoverVideoId ===
                                                    primaryFocusedStream.video_id
                                                    ? null
                                                    : primaryFocusedStream.video_id,
                                            )
                                        "
                                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition flex items-center gap-1.5 border shadow-sm"
                                        :class="
                                            getStreamTac(
                                                primaryFocusedStream.video_id,
                                            )
                                                ? 'bg-blue-500 text-black border-blue-400 hover:bg-blue-400'
                                                : 'bg-slate-900 text-blue-400 hover:bg-blue-950/40 border-blue-500/30'
                                        "
                                    >
                                        <img
                                            :src="iconRadio"
                                            class="w-5 h-5"
                                            :class="
                                                getStreamTac(
                                                    primaryFocusedStream.video_id,
                                                )
                                                    ? ''
                                                    : 'invert'
                                            "
                                        />
                                        <span>{{
                                            getStreamTac(
                                                primaryFocusedStream.video_id,
                                            )
                                                ? getStreamTac(
                                                      primaryFocusedStream.video_id,
                                                  ).replace("_", " ")
                                                : "TAC"
                                        }}</span>
                                    </button>
                                    <!-- TAC Popover Menu -->
                                    <div
                                        v-if="
                                            activeTacPopoverVideoId ===
                                            primaryFocusedStream.video_id
                                        "
                                        class="absolute right-0 mt-2 w-56 bg-slate-950 border border-blue-500/50 rounded-xl p-2.5 shadow-2xl z-50 animate-in fade-in zoom-in-95 duration-150"
                                        @click.stop
                                    >
                                        <div
                                            class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-xs font-bold text-blue-300 font-mono"
                                        >
                                            <span
                                                >ASSIGN RADIO TAC CHANNEL:</span
                                            >
                                            <button
                                                @click="
                                                    emit(
                                                        'update:activeTacPopoverVideoId',
                                                        null,
                                                    )
                                                "
                                                class="text-slate-400 hover:text-white p-0.5 rounded hover:bg-slate-800 text-[10px]"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <div
                                            class="grid grid-cols-5 gap-1.5 mb-2"
                                        >
                                            <button
                                                v-for="t in [
                                                    1, 2, 3, 4, 5, 6, 7, 8, 9,
                                                    10,
                                                ]"
                                                :key="t"
                                                @click.stop="
                                                    emit(
                                                        'assign-stream-to-tac',
                                                        `TAC_${t}`,
                                                        primaryFocusedStream.video_id,
                                                    );
                                                    emit(
                                                        'update:activeTacPopoverVideoId',
                                                        null,
                                                    );
                                                "
                                                class="py-1.5 rounded-lg font-mono text-center font-bold text-xs transition border flex flex-col items-center justify-center gap-0.5"
                                                :class="
                                                    getStreamTac(
                                                        primaryFocusedStream.video_id,
                                                    ) === `TAC_${t}`
                                                        ? 'bg-blue-500 text-black border-blue-300 shadow-lg shadow-blue-500/30'
                                                        : 'bg-slate-900 text-slate-200 hover:bg-blue-950/60 hover:text-blue-300 hover:border-blue-500/50 border-slate-800'
                                                "
                                            >
                                                <span
                                                    class="text-[8px] text-slate-400 leading-none"
                                                    >TAC</span
                                                >
                                                <span class="leading-none">{{
                                                    t
                                                }}</span>
                                            </button>
                                        </div>
                                        <div
                                            v-if="
                                                getStreamTac(
                                                    primaryFocusedStream.video_id,
                                                )
                                            "
                                            class="pt-1.5 border-t border-slate-800/80"
                                        >
                                            <button
                                                @click.stop="
                                                    emit(
                                                        'remove-stream-from-tac',
                                                        primaryFocusedStream.video_id,
                                                    );
                                                    emit(
                                                        'update:activeTacPopoverVideoId',
                                                        null,
                                                    );
                                                "
                                                class="w-full py-1.5 px-2 text-[10px] rounded-lg bg-red-950/60 text-red-300 hover:bg-red-900/80 border border-red-500/40 text-center transition flex items-center justify-center gap-1 font-mono"
                                            >
                                                <span
                                                    >✕ Lepas dari
                                                    {{
                                                        getStreamTac(
                                                            primaryFocusedStream.video_id,
                                                        ).replace("_", " ")
                                                    }}</span
                                                >
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Live Audio Toggle Button -->
                                <button
                                    @click="
                                        emit(
                                            'toggle-audio',
                                            primaryFocusedStream.video_id,
                                        )
                                    "
                                    :class="
                                        activeAudioVideoId ===
                                        primaryFocusedStream.video_id
                                            ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 shadow-sm shadow-emerald-950/30 font-bold'
                                            : 'bg-slate-900/60 text-slate-400 hover:bg-slate-800 hover:text-slate-200 border-slate-800'
                                    "
                                    class="p-1.5 rounded-lg transition border flex items-center justify-center shrink-0"
                                    :title="
                                        activeAudioVideoId ===
                                        primaryFocusedStream.video_id
                                            ? 'Status Audio: LIVE AUDIO (Klik untuk Mute)'
                                            : 'Status Audio: MUTED (Klik untuk Mengaktifkan Audio Live)'
                                    "
                                >
                                    <img
                                        :src="
                                            activeAudioVideoId ===
                                            primaryFocusedStream.video_id
                                                ? iconUnmute
                                                : iconMute
                                        "
                                        class="w-4 h-4 invert opacity-90"
                                        alt=""
                                    />
                                </button>

                                <!-- Stop Feed / Tutup Video Toggle Button -->
                                <button
                                    @click="
                                        toggleStopFeed(
                                            primaryFocusedStream.video_id,
                                        )
                                    "
                                    :class="
                                        isStreamStopped(
                                            primaryFocusedStream.video_id,
                                        )
                                            ? 'bg-blue-950/60 text-blue-300 hover:bg-blue-900/80 border-blue-500/40 shadow-sm'
                                            : 'bg-blue-950/60 text-blue-300 hover:bg-blue-900/80 hover:text-white border-blue-500/40'
                                    "
                                    class="p-1.5 rounded-lg transition border flex items-center justify-center font-mono text-xs shrink-0"
                                    :title="
                                        isStreamStopped(
                                            primaryFocusedStream.video_id,
                                        )
                                            ? 'Putar Video (Aktifkan Kembali Feed)'
                                            : 'Pause Video (Stop Feed Sementara)'
                                    "
                                >
                                    <img
                                        :src="
                                            isStreamStopped(
                                                primaryFocusedStream.video_id,
                                            )
                                                ? iconPlayAll
                                                : iconPause
                                        "
                                        class="w-3.5 h-3.5 invert opacity-80"
                                        alt="Play/Pause Feed"
                                    />
                                </button>

                                <!-- Exit Focus / Kembali ke Mode Grid Button -->
                                <button
                                    @click="
                                        emit('update:selectedLayout', 'auto')
                                    "
                                    class="p-1.5 rounded-lg transition border flex items-center justify-center font-mono text-xs bg-blue-950/60 text-blue-300 hover:bg-blue-900/80 hover:text-white border-blue-500/40 shadow-sm shrink-0"
                                    title="Kembali ke Mode Grid (Tutup Mode Fokus)"
                                >
                                    <img
                                        :src="iconCloseBold"
                                        class="w-3.5 h-3.5 invert opacity-90"
                                        alt="Kembali ke Mode Grid"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Big Video Player Container with Floating Hover Overlay Bar -->
                        <div
                            class="relative w-full aspect-video bg-black overflow-hidden group/thumb min-w-[320px] min-h-[200px]"
                        >
                            <template
                                v-if="
                                    !isStreamStopped(
                                        primaryFocusedStream.video_id,
                                    )
                                "
                            >
                                <iframe
                                    :key="`primary-player-${primaryFocusedStream.video_id}`"
                                    :id="`yt-bodycam-${primaryFocusedStream.video_id}`"
                                    class="w-full h-full border-0 pointer-events-auto"
                                    :src="`https://www.youtube.com/embed/${primaryFocusedStream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=hd1080&origin=${originUrl}`"
                                    allow="
                                        accelerometer;
                                        autoplay;
                                        clipboard-write;
                                        encrypted-media;
                                        gyroscope;
                                        picture-in-picture;
                                        web-share;
                                    "
                                    allowfullscreen
                                ></iframe>
                            </template>
                            <template v-else>
                                <img
                                    :src="`https://i.ytimg.com/vi/${primaryFocusedStream.video_id}/hqdefault.jpg`"
                                    :alt="primaryFocusedStream.title"
                                    class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 transition duration-300"
                                    loading="lazy"
                                />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/50 flex flex-col items-center justify-center p-4 z-10"
                                >
                                    <div
                                        class="absolute top-3 left-3 flex items-center space-x-1.5 font-mono text-[10px] text-red-400 bg-black/75 backdrop-blur-sm px-2.5 py-1 rounded-md border border-red-500/30"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-red-500 animate-ping"
                                        ></span>
                                        <span>MODE STOP FEED</span>
                                    </div>
                                    <div
                                        class="flex items-center space-x-3 transition-all duration-300 transform scale-95 group-hover/thumb:scale-100"
                                    >
                                        <button
                                            @click="
                                                toggleStopFeed(
                                                    primaryFocusedStream.video_id,
                                                )
                                            "
                                            class="bg-emerald-600/90 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xl transition flex items-center gap-2 border border-emerald-400/30"
                                        >
                                            <img
                                                :src="iconPlayAll"
                                                class="w-4 h-4 invert opacity-90"
                                            />
                                            <span>Putar Video</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <!-- Floating Stream Description & Title Popover Overlay -->
                            <div
                                v-if="
                                    isStreamInfoOpen(
                                        primaryFocusedStream.video_id,
                                    )
                                "
                                class="absolute bottom-16 left-3 right-3 sm:left-auto sm:right-3 sm:max-w-md bg-slate-950/95 backdrop-blur-md p-3.5 rounded-2xl border border-blue-500/40 shadow-2xl z-30 animate-in fade-in zoom-in-95 duration-150 flex flex-col gap-2 pointer-events-auto"
                                @click.stop
                            >
                                <div
                                    class="flex items-center justify-between pb-2 border-b border-slate-800 text-xs font-bold text-blue-300 gap-2"
                                >
                                    <div
                                        class="flex items-center gap-1.5 min-w-0 truncate"
                                    >
                                        <img
                                            :src="iconRadio"
                                            class="w-3.5 h-3.5 invert opacity-80 shrink-0"
                                        />
                                        <span
                                            class="truncate"
                                            :title="primaryFocusedStream.title"
                                        >
                                            {{ primaryFocusedStream.title }}
                                        </span>
                                    </div>
                                    <button
                                        @click="
                                            toggleStreamInfo(
                                                primaryFocusedStream.video_id,
                                            )
                                        "
                                        class="text-slate-400 hover:text-white p-0.5 rounded hover:bg-slate-800 text-xs font-mono shrink-0"
                                        title="Tutup Deskripsi"
                                    >
                                        ✕
                                    </button>
                                </div>
                                <div
                                    v-if="
                                        getStreamDescription(
                                            primaryFocusedStream,
                                        )
                                    "
                                    class="text-[11px] text-slate-300 font-mono whitespace-pre-wrap break-words leading-relaxed max-h-56 overflow-y-auto scrollbar-thin pr-1 select-text"
                                    v-html="
                                        formatDescriptionWithLinks(
                                            getStreamDescription(
                                                primaryFocusedStream,
                                            ),
                                        )
                                    "
                                ></div>
                                <div
                                    v-else
                                    class="text-[11px] text-slate-500 font-mono italic"
                                >
                                    Tidak ada deskripsi tambahan dari streamer.
                                </div>
                            </div>

                            <!-- Floating Hover Overlay Bar -->
                            <div
                                class="absolute bottom-3 left-3 right-3 opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 bg-slate-950/85 backdrop-blur-md px-3 sm:px-4 py-2 rounded-xl border border-slate-800/90 shadow-2xl flex flex-wrap sm:flex-nowrap items-center justify-between text-xs text-slate-300 gap-2 z-20 pointer-events-auto"
                            >
                                <!-- Left Officer Metadata -->
                                <div
                                    class="flex items-center space-x-2.5 min-w-0 flex-1 truncate"
                                >
                                    <div
                                        class="flex items-center gap-1.5 shrink-0"
                                    >
                                        <span
                                            v-if="
                                                primaryFocusedStream.officer
                                                    ?.callsign
                                            "
                                            class="px-1.5 py-0.5 rounded bg-slate-800/90 text-slate-300 border border-slate-700/60 font-mono text-[10px] font-semibold tracking-wider shrink-0"
                                        >
                                            {{
                                                primaryFocusedStream.officer
                                                    .callsign
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center space-x-2 min-w-0 truncate text-slate-300"
                                    >
                                        <span
                                            v-if="
                                                primaryFocusedStream.officer
                                                    ?.rank
                                            "
                                            class="font-mono text-xs text-slate-300 truncate"
                                        >
                                            {{
                                                primaryFocusedStream.officer
                                                    .rank
                                            }}
                                        </span>
                                        <span
                                            v-if="
                                                primaryFocusedStream.officer
                                                    ?.badge_number
                                            "
                                            class="text-slate-600 hidden sm:inline"
                                            >•</span
                                        >
                                        <span
                                            v-if="
                                                primaryFocusedStream.officer
                                                    ?.badge_number
                                            "
                                            class="font-mono text-[11px] text-slate-400 truncate hidden sm:inline"
                                        >
                                            #{{
                                                primaryFocusedStream.officer
                                                    .badge_number
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Right Action Buttons -->
                                <div
                                    class="flex items-center space-x-1.5 shrink-0"
                                >
                                    <button
                                        v-if="
                                            primaryFocusedStream.officer
                                                ?.channel_id ||
                                            primaryFocusedStream.officer?.handle
                                        "
                                        @click="
                                            emit(
                                                'open-subscribe-popup',
                                                primaryFocusedStream.officer
                                                    ?.channel_id ||
                                                    primaryFocusedStream.officer
                                                        ?.handle,
                                                primaryFocusedStream.officer
                                                    ?.officer_name,
                                            )
                                        "
                                        class="bg-red-600/90 hover:bg-red-500 text-white font-bold px-2.5 py-1 rounded-lg text-[11px] shadow-md transition shrink-0 flex items-center gap-1"
                                        title="Subscribe to channel"
                                    >
                                        <span>Sub</span>
                                    </button>
                                    <!-- Stream Info / Description Popover Toggle Button -->
                                    <button
                                        @click.stop="
                                            toggleStreamInfo(
                                                primaryFocusedStream.video_id,
                                            )
                                        "
                                        :class="
                                            isStreamInfoOpen(
                                                primaryFocusedStream.video_id,
                                            )
                                                ? 'bg-blue-600/40 text-blue-200 border-blue-500/50'
                                                : 'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border-slate-800'
                                        "
                                        class="p-1.5 rounded-lg border transition flex items-center justify-center shrink-0"
                                        :title="
                                            isStreamInfoOpen(
                                                primaryFocusedStream.video_id,
                                            )
                                                ? 'Tutup Deskripsi Stream'
                                                : 'Lihat Deskripsi Stream & Judul YouTube'
                                        "
                                    >
                                        <img
                                            :src="iconDoc"
                                            class="w-3.5 h-3.5 invert opacity-80"
                                            alt="Description"
                                        />
                                    </button>
                                    <button
                                        @click="
                                            isRightChatOpen = !isRightChatOpen
                                        "
                                        :class="
                                            isRightChatOpen
                                                ? 'bg-blue-600/30 text-blue-200 border-blue-500/40'
                                                : 'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border-slate-800'
                                        "
                                        class="p-1.5 rounded-lg border transition flex items-center justify-center shrink-0"
                                        :title="
                                            isRightChatOpen
                                                ? 'Tutup Live Chat'
                                                : 'Buka Live Chat'
                                        "
                                    >
                                        <img
                                            :src="
                                                isRightChatOpen
                                                    ? iconChatRemove
                                                    : iconChat
                                            "
                                            class="w-3.5 h-3.5 invert opacity-80"
                                            alt=""
                                        />
                                    </button>
                                    <a
                                        :href="`https://www.youtube.com/watch?v=${primaryFocusedStream.video_id}`"
                                        target="_blank"
                                        class="p-1.5 text-slate-400 hover:text-white bg-slate-900/80 hover:bg-slate-800 rounded-lg border border-slate-800 transition flex items-center justify-center"
                                        title="Buka di YouTube"
                                    >
                                        <img
                                            :src="iconExternal"
                                            class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100"
                                        />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: LIVE CHAT & SUPPORTING UNITS -->
                <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-3">
                    <div
                        v-if="isRightChatOpen && primaryFocusedStream"
                        class="bg-slate-950 rounded-xl overflow-hidden border border-blue-500/50 shadow-2xl flex flex-col animate-in fade-in zoom-in-95 duration-200"
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
                        <div class="w-full h-[520px] bg-slate-900 relative">
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
                        <div
                            v-for="stream in secondaryStreams"
                            :key="`side-feed-${stream.video_id}`"
                            class="bg-slate-950 rounded-xl overflow-hidden border border-slate-800/80 hover:border-slate-700 transition flex flex-col shadow-lg"
                        >
                            <div
                                class="bg-slate-900/90 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 text-xs"
                            >
                                <div
                                    class="flex items-center space-x-2 min-w-0"
                                >
                                    <span
                                        class="px-1.5 py-0.5 text-[10px] font-black rounded border shrink-0 opacity-90"
                                        :class="
                                            getDeptBadgeClass(
                                                stream.officer?.department,
                                            )
                                        "
                                        >{{ stream.officer?.department }}</span
                                    >
                                    <span
                                        class="font-bold text-slate-200 truncate opacity-90"
                                        >{{
                                            stream.officer?.officer_name
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="flex items-center space-x-1 shrink-0"
                                >
                                    <button
                                        @click="
                                            emit(
                                                'toggle-personal-stream',
                                                stream.video_id,
                                            )
                                        "
                                        :class="
                                            isPersonalStream(stream.video_id)
                                                ? 'text-purple-300 bg-purple-950/60 border border-purple-500/40'
                                                : 'text-slate-500 hover:text-purple-300 bg-slate-900/60 border-slate-800'
                                        "
                                        class="p-1 rounded-md transition font-mono border"
                                        :title="
                                            isPersonalStream(stream.video_id)
                                                ? 'Hapus'
                                                : 'Pin ke Personal'
                                        "
                                    >
                                        <img
                                            :src="
                                                isPersonalStream(
                                                    stream.video_id,
                                                )
                                                    ? iconPinMinus
                                                    : iconPinPlus
                                            "
                                            class="w-3 h-3 invert opacity-80"
                                        />
                                    </button>
                                    <button
                                        @click="
                                            emit(
                                                'toggle-audio',
                                                stream.video_id,
                                            )
                                        "
                                        :class="
                                            activeAudioVideoId ===
                                            stream.video_id
                                                ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 font-bold'
                                                : 'text-slate-500 hover:text-slate-200 bg-slate-900/60 border-slate-800'
                                        "
                                        class="p-1 rounded-md transition font-mono border"
                                        title="Audio Switch"
                                    >
                                        <img
                                            :src="
                                                activeAudioVideoId ===
                                                stream.video_id
                                                    ? iconUnmute
                                                    : iconMute
                                            "
                                            class="w-3 h-3 invert opacity-80"
                                        />
                                    </button>
                                    <button
                                        @click="
                                            handleSetFocusStream(
                                                stream.video_id,
                                            )
                                        "
                                        class="bg-blue-950/60 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/40 hover:border-blue-400 px-2 py-0.5 rounded-md text-[10px] font-bold shadow transition flex items-center gap-1"
                                        title="Fokus"
                                    >
                                        <img
                                            :src="iconFocus"
                                            class="w-2.5 h-2.5 invert opacity-80"
                                        />
                                        <span>Fokus</span>
                                    </button>
                                </div>
                            </div>
                            <div
                                class="relative w-full aspect-video bg-black overflow-hidden group/thumb min-w-[320px] min-h-[200px]"
                            >
                                <template
                                    v-if="
                                        !isStreamStopped(stream.video_id) &&
                                        (activePreviewVideoIds.includes(
                                            stream.video_id,
                                        ) ||
                                            !isDataSaverEnabled)
                                    "
                                >
                                    <iframe
                                        :key="`support-iframe-${stream.video_id}`"
                                        :id="`yt-bodycam-${stream.video_id}`"
                                        class="w-full h-full border-0 pointer-events-auto"
                                        :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`"
                                        allow="
                                            accelerometer;
                                            autoplay;
                                            clipboard-write;
                                            encrypted-media;
                                            gyroscope;
                                            picture-in-picture;
                                            web-share;
                                        "
                                        allowfullscreen
                                    ></iframe>
                                    <button
                                        @click="toggleStopFeed(stream.video_id)"
                                        class="absolute top-2 left-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-2 py-0.5 rounded border border-white/20 z-20 font-mono"
                                        title="Tutup Video / Stop Feed"
                                    >
                                        ✕ Stop Feed
                                    </button>
                                </template>
                                <template v-else>
                                    <img
                                        :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                                        :alt="stream.title"
                                        class="w-full h-full object-cover opacity-90 group-hover/thumb:opacity-100 transition duration-300"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute inset-0 bg-black/50 backdrop-blur-[2px] opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 flex items-center justify-center gap-2 p-3 z-10"
                                    >
                                        <button
                                            @click="
                                                toggleStopFeed(stream.video_id)
                                            "
                                            class="bg-emerald-600/90 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-lg transition flex items-center gap-1.5"
                                        >
                                            <img
                                                :src="iconPlayAll"
                                                class="w-3.5 h-3.5 invert"
                                            />
                                            <span>Play</span>
                                        </button>
                                        <button
                                            @click="
                                                handleSetFocusStream(
                                                    stream.video_id,
                                                )
                                            "
                                            class="bg-blue-600/90 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-lg transition flex items-center gap-1.5"
                                        >
                                            <img
                                                :src="iconFocus"
                                                class="w-3.5 h-3.5 invert"
                                            />
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
                        <div
                            v-for="stream in displayedGridStreams"
                            :key="`grid-card-${stream.video_id}`"
                            :class="[
                                getCustomOrderRank(stream.video_id) > 0
                                    ? 'border-amber-500/40 shadow-md shadow-amber-950/20'
                                    : 'border-slate-800/80 hover:border-slate-700',
                                activeChatVideoId === stream.video_id
                                    ? 'border-amber-500/60 shadow-md shadow-amber-950/30'
                                    : '',
                            ]"
                            class="bg-slate-950 rounded-xl overflow-hidden border transition flex flex-col relative group"
                        >
                            <!-- HEADER HUD -->
                            <div
                                class="bg-slate-900/90 backdrop-blur-sm px-3 py-1.5 flex items-center justify-between border-b border-slate-800/80 z-10"
                            >
                                <div
                                    class="flex items-center space-x-2 min-w-0"
                                >
                                    <span
                                        class="px-1.5 py-0.5 text-[11px] font-black rounded border tracking-wider shrink-0 opacity-90"
                                        :class="
                                            getDeptBadgeClass(
                                                stream.officer?.department,
                                            )
                                        "
                                    >
                                        {{
                                            stream.officer?.department || "UNIT"
                                        }}
                                    </span>
                                    <span
                                        class="text-xs font-semibold text-slate-200 block truncate opacity-90"
                                        >{{
                                            stream.officer?.officer_name
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="flex items-center space-x-1 shrink-0"
                                >
                                    <!-- Order Rank Badge Button -->
                                    <button
                                        @click="
                                            emit(
                                                'toggle-custom-order',
                                                stream.video_id,
                                            )
                                        "
                                        class="px-1.5 py-0.5 text-[10px] rounded-md transition flex items-center font-mono font-bold border"
                                        :class="
                                            getCustomOrderRank(
                                                stream.video_id,
                                            ) > 0
                                                ? 'text-amber-300 bg-amber-500/15 border-amber-500/40'
                                                : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800'
                                        "
                                        :title="
                                            getCustomOrderRank(
                                                stream.video_id,
                                            ) > 0
                                                ? `Urutan #${getCustomOrderRank(stream.video_id)}`
                                                : 'Klik untuk prioritas urutan grid'
                                        "
                                    >
                                        <span>{{
                                            getCustomOrderRank(
                                                stream.video_id,
                                            ) > 0
                                                ? `#${getCustomOrderRank(stream.video_id)}`
                                                : "#"
                                        }}</span>
                                    </button>
                                    <!-- Audio Switch -->
                                    <button
                                        @click="
                                            emit(
                                                'toggle-audio',
                                                stream.video_id,
                                            )
                                        "
                                        :class="
                                            activeAudioVideoId ===
                                            stream.video_id
                                                ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 font-bold'
                                                : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800'
                                        "
                                        class="p-1 rounded-md transition font-mono border"
                                    >
                                        <img
                                            :src="
                                                activeAudioVideoId ===
                                                stream.video_id
                                                    ? iconUnmute
                                                    : iconMute
                                            "
                                            class="w-3 h-3 invert opacity-80"
                                        />
                                    </button>
                                    <!-- Personal Stream Pin Toggle -->
                                    <button
                                        v-if="stream.status === 'LIVE'"
                                        @click="
                                            emit(
                                                'toggle-personal-stream',
                                                stream.video_id,
                                            )
                                        "
                                        class="p-1 rounded-md transition border"
                                        :class="
                                            isPersonalStream(stream.video_id)
                                                ? 'text-purple-300 bg-purple-950/60 border-purple-500/40'
                                                : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800/80'
                                        "
                                        :title="
                                            isPersonalStream(stream.video_id)
                                                ? 'Hapus'
                                                : 'Pin ke Personal'
                                        "
                                    >
                                        <img
                                            :src="
                                                isPersonalStream(
                                                    stream.video_id,
                                                )
                                                    ? iconPinMinus
                                                    : iconPinPlus
                                            "
                                            class="w-3 h-3 invert opacity-80"
                                        />
                                    </button>
                                </div>
                            </div>
                            <!-- VIDEO DISPLAY: IFRAME OR POSTER THUMBNAIL -->
                            <div
                                class="relative w-full aspect-video bg-black overflow-hidden flex-1 group/thumb min-w-[320px] min-h-[200px]"
                            >
                                <template
                                    v-if="
                                        !isStreamStopped(stream.video_id) &&
                                        (!isDataSaverEnabled ||
                                            activeGridVideoIds.includes(
                                                stream.video_id,
                                            ) ||
                                            activeAudioVideoId ===
                                                stream.video_id)
                                    "
                                >
                                    <iframe
                                        :key="`grid-iframe-${stream.video_id}`"
                                        :id="`yt-bodycam-${stream.video_id}`"
                                        class="w-full h-full border-0 pointer-events-auto"
                                        :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`"
                                        allow="
                                            accelerometer;
                                            autoplay;
                                            clipboard-write;
                                            encrypted-media;
                                            gyroscope;
                                            picture-in-picture;
                                            web-share;
                                        "
                                        allowfullscreen
                                    ></iframe>
                                    <button
                                        @click="toggleStopFeed(stream.video_id)"
                                        class="absolute top-2 left-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-2 py-0.5 rounded border border-white/20 z-20 font-mono"
                                        title="Tutup Video / Stop Feed"
                                    >
                                        ✕ Stop Feed
                                    </button>
                                </template>
                                <template v-else>
                                    <img
                                        :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                                        :alt="stream.title"
                                        class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 transition duration-300"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/40 flex flex-col items-center justify-center p-3"
                                    >
                                        <div
                                            class="absolute top-2 left-2 flex items-center space-x-1 font-mono text-[9px] text-red-400 bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded border border-red-500/30"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"
                                            ></span>
                                            <span>10-8 LIVE</span>
                                        </div>
                                        <div
                                            class="flex items-center space-x-2 opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 transform scale-95 group-hover/thumb:scale-100"
                                        >
                                            <button
                                                @click="
                                                    toggleStopFeed(
                                                        stream.video_id,
                                                    )
                                                "
                                                class="bg-emerald-600/90 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg transition flex items-center gap-1.5"
                                            >
                                                <img
                                                    :src="iconPlayAll"
                                                    class="w-3.5 h-3.5 invert opacity-90"
                                                />
                                                <span>Play</span>
                                            </button>
                                            <button
                                                @click="
                                                    handleSetFocusStream(
                                                        stream.video_id,
                                                    )
                                                "
                                                class="bg-blue-600/90 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg transition flex items-center gap-1.5"
                                            >
                                                <img
                                                    :src="iconFocus"
                                                    class="w-3.5 h-3.5 invert opacity-90"
                                                />
                                                <span>Focus</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Floating Hover Overlay Bar -->
                                <div
                                    class="absolute bottom-2 left-2 right-2 opacity-0 group-hover/thumb:opacity-100 transition-all duration-200 bg-slate-950/85 backdrop-blur-md px-2.5 py-1.5 rounded-xl border border-slate-800/90 shadow-2xl flex items-center justify-between z-20 pointer-events-auto text-[11px] text-slate-300 gap-1.5"
                                >
                                    <div
                                        class="flex items-center space-x-1.5 min-w-0 flex-1 truncate"
                                    >
                                        <span
                                            v-if="stream.officer?.callsign"
                                            class="px-1.5 py-0.5 rounded bg-slate-800/90 text-slate-300 border border-slate-700/60 font-mono text-[9px] font-semibold tracking-wider shrink-0"
                                        >
                                            {{ stream.officer.callsign }}
                                        </span>
                                        <span
                                            v-if="stream.officer?.rank"
                                            class="font-mono text-[10px] text-slate-300 truncate"
                                        >
                                            {{ stream.officer.rank }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center space-x-1 shrink-0"
                                    >
                                        <button
                                            v-if="
                                                stream.officer?.channel_id ||
                                                stream.officer?.handle
                                            "
                                            @click="
                                                emit(
                                                    'open-subscribe-popup',
                                                    stream.officer
                                                        ?.channel_id ||
                                                        stream.officer?.handle,
                                                    stream.officer
                                                        ?.officer_name,
                                                )
                                            "
                                            class="px-2 py-0.5 bg-red-600/90 hover:bg-red-500 text-white rounded-md text-[10px] font-bold shadow transition shrink-0"
                                            title="Subscribe"
                                        >
                                            Sub
                                        </button>
                                        <button
                                            @click="
                                                emit(
                                                    'update:selectedLayout',
                                                    'focus',
                                                );
                                                emit(
                                                    'set-focus-stream',
                                                    stream.video_id,
                                                );
                                            "
                                            class="p-1 hover:text-blue-400 text-slate-300 rounded hover:bg-slate-800 transition"
                                            title="Focus"
                                        >
                                            <img
                                                :src="iconFocus"
                                                class="w-3.5 h-3.5 invert opacity-80 hover:opacity-100"
                                            />
                                        </button>
                                        <button
                                            @click="
                                                emit(
                                                    'update:activeChatVideoId',
                                                    activeChatVideoId ===
                                                        stream.video_id
                                                        ? null
                                                        : stream.video_id,
                                                )
                                            "
                                            :class="
                                                activeChatVideoId ===
                                                stream.video_id
                                                    ? 'bg-blue-600 text-white shadow-md'
                                                    : 'text-slate-300 hover:text-blue-400 hover:bg-slate-800'
                                            "
                                            class="p-1 rounded transition flex items-center justify-center"
                                        >
                                            <img
                                                :src="
                                                    activeChatVideoId ===
                                                    stream.video_id
                                                        ? iconChatRemove
                                                        : iconChat
                                                "
                                                class="w-3.5 h-3.5 invert opacity-90"
                                            />
                                        </button>
                                        <a
                                            :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                                            target="_blank"
                                            class="p-1 hover:text-white text-slate-300 rounded hover:bg-slate-800 transition"
                                        >
                                            <img
                                                :src="iconExternal"
                                                class="w-3.5 h-3.5 invert opacity-80 hover:opacity-100"
                                            />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR LIVE CHAT -->
                <div
                    v-if="activeGridChatStream"
                    class="lg:col-span-4 xl:col-span-3 space-y-3 animate-in fade-in zoom-in-95 duration-200"
                >
                    <div
                        class="bg-slate-950 rounded-xl overflow-hidden border border-blue-500/50 shadow-2xl flex flex-col"
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
                        <div class="w-full h-[580px] bg-slate-900 relative">
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

        <!-- GOOGLE MEET FLOATING CONTROL DOCK WITH INTEGRATED PLAYER FEATURES -->
        <div
            class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex flex-col items-center pointer-events-none"
        >
            <!-- Popover 1: Department & Category Selector Menu -->
            <div
                v-if="isDockCategoryOpen"
                class="pointer-events-auto mb-3 w-80 sm:w-96 max-h-[70vh] overflow-y-auto bg-slate-950/95 border border-slate-700/80 rounded-2xl p-3 shadow-2xl backdrop-blur-2xl text-xs space-y-3 animate-in fade-in zoom-in-95 font-sans scrollbar-thin scrollbar-thumb-slate-700"
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
                            'p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'ALL'
                                ? 'bg-blue-600 text-white border-blue-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border-slate-800',
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
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/40 font-bold"
                            >{{ getDeptCount("ALL") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'LSPD');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'LSPD'
                                ? 'bg-blue-600 text-white border-blue-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border-slate-800',
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
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/40 font-bold"
                            >{{ getDeptCount("LSPD") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'BCSO');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'BCSO'
                                ? 'bg-amber-600 text-white border-amber-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border-slate-800',
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
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/40 font-bold"
                            >{{ getDeptCount("BCSO") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'SASP');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'SASP'
                                ? 'bg-teal-600 text-white border-teal-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border-slate-800',
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
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/40 font-bold"
                            >{{ getDeptCount("SASP") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'SAPR');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'SAPR'
                                ? 'bg-emerald-600 text-white border-emerald-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border-slate-800',
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
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/40 font-bold"
                            >{{ getDeptCount("SAPR") }}</span
                        >
                    </button>

                    <button
                        @click="
                            emit('update:selectedDepartment', 'PERSONAL');
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedDepartment === 'PERSONAL'
                                ? 'bg-purple-600 text-white border-purple-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-purple-300 border-purple-900/50',
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
                            class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/40 font-bold"
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
                                'p-1.5 rounded-lg border text-center transition flex flex-col items-center justify-center gap-0.5 cursor-pointer',
                                selectedDepartment === `TAC_${i}`
                                    ? 'bg-amber-600 text-white border-amber-400 font-bold shadow'
                                    : getDeptCount(`TAC_${i}`) > 0
                                      ? 'bg-amber-950/40 text-amber-300 border-amber-700/50 font-semibold'
                                      : 'bg-slate-900/80 text-slate-400 border-slate-800 hover:bg-slate-800',
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
                                        : 'bg-black/40 text-slate-500'
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
                class="pointer-events-auto mb-3 w-64 bg-slate-950/95 border border-slate-700/80 rounded-2xl p-3 shadow-2xl backdrop-blur-2xl text-xs space-y-2 animate-in fade-in zoom-in-95 font-sans"
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
                            'w-full p-2 rounded-xl border flex items-center justify-between transition text-left cursor-pointer',
                            selectedLayout === l.id
                                ? 'bg-blue-600 text-white border-blue-400 font-bold shadow-md'
                                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border-slate-800',
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
                class="pointer-events-auto mb-3 w-72 sm:w-80 bg-slate-950/95 border border-slate-700/80 rounded-2xl p-2.5 shadow-2xl backdrop-blur-2xl text-xs animate-in fade-in zoom-in-95 font-sans"
                @click.stop
            >
                <div class="relative flex items-center">
                    <input
                        type="text"
                        :value="searchFilter"
                        @input="
                            emit('update:searchFilter', $event.target.value)
                        "
                        placeholder="Cari perwira, divisi, judul siaran..."
                        class="w-full bg-slate-900 text-white placeholder-slate-500 text-xs px-3 py-2 pr-8 rounded-xl border border-slate-700 focus:outline-none focus:border-blue-500 font-sans"
                    />
                    <button
                        v-if="searchFilter"
                        @click="emit('update:searchFilter', '')"
                        class="absolute right-2.5 text-slate-400 hover:text-white text-xs"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- FLOATING DOCK BAR -->
            <div
                class="pointer-events-auto bg-slate-950/90 backdrop-blur-xl border border-slate-700/70 shadow-2xl px-3 sm:px-4 py-2 rounded-full flex items-center gap-2 sm:gap-2.5 text-xs text-slate-200 animate-in fade-in slide-in-from-bottom-4 duration-300 max-w-[96vw] overflow-x-auto scrollbar-none"
                @click.stop
            >
                <!-- Category Selector Button -->
                <button
                    @click="toggleDockCategory"
                    :class="[
                        'px-3 py-1.5 rounded-full text-xs font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        isDockCategoryOpen
                            ? 'bg-blue-600 text-white border-blue-400 shadow-md ring-2 ring-blue-500/40'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-200 border-slate-700',
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
                        class="px-1.5 py-0.2 rounded-full bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold"
                        >{{ visibleStreams.length }}</span
                    >
                    <span class="text-[10px] text-slate-400">▾</span>
                </button>

                <div class="h-4 w-px bg-slate-800 shrink-0"></div>

                <!-- Layout Selector Button -->
                <button
                    @click="toggleDockLayout"
                    :class="[
                        'px-3 py-1.5 rounded-full text-xs font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        isDockLayoutOpen
                            ? 'bg-blue-600 text-white border-blue-400 shadow-md ring-2 ring-blue-500/40'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-200 border-slate-700',
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

                <div class="h-4 w-px bg-slate-800 shrink-0"></div>

                <!-- Quick Search Filter Button -->
                <button
                    @click="toggleDockSearch"
                    :class="[
                        'p-2 rounded-full border transition shrink-0 relative cursor-pointer',
                        isDockSearchOpen || searchFilter
                            ? 'bg-blue-950 text-blue-300 border-blue-500 ring-1 ring-blue-400'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-700',
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

                <!-- Sync / Refresh Feeds Button -->
                <button
                    @click="emit('trigger-manual-sync')"
                    :disabled="isSyncingFeeds"
                    class="p-2 rounded-full bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-slate-300 border border-slate-700 transition shrink-0 cursor-pointer"
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
                        'px-2.5 py-1.5 rounded-full text-[11px] font-mono font-bold border transition flex items-center gap-1 shrink-0 cursor-pointer',
                        isDataSaverEnabled
                            ? 'bg-amber-950 text-amber-300 border-amber-500 shadow-sm'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-700',
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
                    <span class="hidden sm:inline">{{
                        isDataSaverEnabled ? "Saver ON" : "Saver OFF"
                    }}</span>
                </button>

                <div class="h-4 w-px bg-slate-800 shrink-0"></div>

                <!-- Theater / Bioskop Mode Toggle (100% Full Width vs 2XL Boxed) -->
                <button
                    @click="emit('toggle-theater-mode')"
                    class="px-3 py-1.5 rounded-full text-[11px] font-mono font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer shadow-sm"
                    :class="
                        isTheaterMode
                            ? 'bg-blue-600 text-white border-blue-400 shadow-md shadow-blue-600/30 ring-2 ring-blue-500/40'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-700'
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
                            activeAudioVideoId ||
                                (visibleStreams[0]
                                    ? visibleStreams[0].video_id
                                    : null),
                        )
                    "
                    class="p-2 rounded-full border transition shrink-0 cursor-pointer"
                    :class="
                        activeAudioVideoId
                            ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/50'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-700'
                    "
                    :title="
                        activeAudioVideoId
                            ? 'Suara Aktif (Mute)'
                            : 'Aktivasi Suara Feed'
                    "
                >
                    <img
                        :src="activeAudioVideoId ? iconUnmute : iconMute"
                        class="w-3.5 h-3.5 invert opacity-90"
                    />
                </button>

                <!-- Fullscreen Toggle -->
                <button
                    @click="emit('toggle-fullscreen')"
                    class="p-2 rounded-full bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 transition shrink-0 cursor-pointer"
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
