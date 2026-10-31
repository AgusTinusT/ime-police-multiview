<script setup>
import { ref, computed } from "vue";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";

const { toggleOfficerStatus, getOfficerKey } = useOfficerFilter();

import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";

import AnnouncementBanner from "@/Components/AnnouncementBanner.vue";
import FocusModePlayer from "@/Components/FocusModePlayer.vue";
import TacticalStreamCard from "@/Components/TacticalStreamCard.vue";
import TacticalDockControlBar from "@/Components/TacticalDockControlBar.vue";
import { EmptyState } from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { MessageSquare, RefreshCw, X } from "@lucide/vue";

const props = defineProps({
    mode: { type: String, default: "gmeet" },
    selectedDepartment: { type: String, required: true },
    selectedLayout: { type: String, default: "auto" },
    searchFilter: { type: String, default: "" },
    isDataSaverEnabled: { type: Boolean, default: false },
    isSyncingFeeds: { type: Boolean, default: false },
    isTheaterMode: { type: Boolean, default: false },
    allActiveStreams: { type: Array, default: () => [] },
    filteredOfflineOfficers: { type: Array, default: () => [] },
    visibleStreams: { type: Array, default: () => [] },
    focusStream: { type: Object, default: null },
    sideStreams: { type: Array, default: () => [] },
    gridStreams: { type: Array, default: () => [] },
    activePreviewVideoIds: { type: Array, default: () => [] },
    activeGridVideoIds: { type: Array, default: () => [] },
    activeAudioVideoId: { type: String, default: null },
    activeChatVideoId: { type: [String, Number], default: null },
    activeGridChatStream: { type: Object, default: null },
    originUrl: { type: String, default: "" },
    chatEmbedDomain: { type: String, default: "" },
    activeAnnouncements: { type: Array, default: () => [] },
    activeTab: { type: String, default: "10-8" },
    customOrderMap: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
    "play-stream-in-focus",
    "set-focus-stream",
    "toggle-audio",
    "toggle-theater-mode",
    "toggle-fullscreen",
    "toggle-custom-order",
    "open-subscribe-popup",
    "open-quick-add",
    "open-officer-visibility",
    "trigger-manual-sync",
    "toggle-sidebar-preview",
    "disable-data-saver",
    "enable-data-saver",
    "toggle-grid-stream-play",
    "update:activeChatVideoId",
    "update:activeTab",
    "update:selectedDepartment",
    "update:selectedLayout",
    "update:searchFilter",
]);

const getDeptCount = (deptId) => {
    if (deptId === "ALL") return props.allActiveStreams?.length || 0;
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
            (props.activeGridVideoIds &&
                props.activeGridVideoIds.includes(videoId)) ||
            (props.activePreviewVideoIds &&
                props.activePreviewVideoIds.includes(videoId)) ||
            props.activeAudioVideoId === videoId
        );
    }
    return true;
};

const toggleStopFeed = (videoId) => {
    if (!videoId) return;
    const playing = isVideoPlaying(videoId);
    if (playing) {
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

const isRightChatOpen = ref(true);
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

const getStreamDescription = (stream) => {
    if (!stream) return "";
    if (fetchedDescriptions.value[stream.video_id]) {
        return fetchedDescriptions.value[stream.video_id];
    }
    return stream.description || "";
};

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

    if (count <= 1) return "grid grid-cols-1 gap-3 sm:gap-4";
    if (count <= 2) return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (count <= 4) return "grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4";
    if (count <= 9)
        return "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3";
    return "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3";
});

const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return iconSasp;
    if (d.includes("SAPR") || d.includes("RANGER")) return iconSapr;
    return iconAllUnits;
};

const getCustomOrderRank = (item) => {
    if (!item || !props.customOrderMap) return 0;
    const key =
        getOfficerKey(item) ||
        (typeof item === "string"
            ? item.trim()
            : item.video_id
              ? String(item.video_id).trim()
              : "");
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
        <!-- Department Standby Banner when visibleStreams.length === 0 -->
        <EmptyState
            v-if="visibleStreams.length === 0"
            :title="`TIDAK ADA SIARAN AKTIF (10-8) DI KATEGORI ${selectedDepartment.replace('_', ' ')}`"
            :description="`Saat ini belum ada siaran langsung untuk kesatuan ${selectedDepartment.replace('_', ' ')}. Anda dapat menyinkronkan data terbaru atau menampilkan seluruh unit.`"
        >
            <template #action>
                <div class="flex items-center gap-2 flex-wrap justify-center">
                    <Button
                        type="button"
                        size="sm"
                        :disabled="isSyncingFeeds"
                        @click="emit('trigger-manual-sync')"
                    >
                        <RefreshCw
                            class="size-3.5"
                            :class="{ 'animate-spin': isSyncingFeeds }"
                        />
                        <span>{{
                            isSyncingFeeds
                                ? "Menyinkronkan..."
                                : "Cek Live Sekarang"
                        }}</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="emit('update:selectedDepartment', 'ALL')"
                    >
                        Tampilkan Seluruh Unit
                    </Button>
                </div>
            </template>
        </EmptyState>

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
                        :chatEmbedDomain="chatEmbedDomain"
                        :activeAudioVideoId="activeAudioVideoId"
                        :isStreamStopped="
                            isStreamStopped(primaryFocusedStream.video_id)
                        "
                        :isRightChatOpen="isRightChatOpen"
                        :isTheaterMode="isTheaterMode"
                        :fetchedDescription="
                            getStreamDescription(primaryFocusedStream)
                        "
                        @toggle-audio="
                            emit('toggle-audio', primaryFocusedStream.video_id)
                        "
                        @toggle-stop-feed="
                            toggleStopFeed(primaryFocusedStream.video_id)
                        "
                        @toggle-right-chat="isRightChatOpen = !isRightChatOpen"
                        @toggle-theater-mode="emit('toggle-theater-mode')"
                        @close-focus="emit('update:selectedLayout', 'auto')"
                    />
                </div>

                <!-- RIGHT COLUMN: LIVE CHAT & SUPPORTING UNITS -->
                <div class="lg:col-span-4 xl:col-span-3 flex flex-col gap-3">
                    <Card
                        v-if="isRightChatOpen && primaryFocusedStream"
                        class="hidden lg:flex overflow-hidden flex-col py-0 gap-0 animate-in fade-in zoom-in-95 duration-200"
                    >
                        <div
                            class="bg-card px-3 py-1.5 flex items-center justify-between border-b border-border text-xs"
                        >
                            <div
                                class="flex items-center space-x-1.5 text-primary font-bold truncate"
                            >
                                <MessageSquare class="size-3.5 shrink-0" />
                                <span class="truncate">
                                    Live Chat:
                                    {{
                                        primaryFocusedStream.officer
                                            ?.officer_name
                                    }}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-2 text-xs font-mono text-muted-foreground hover:text-foreground"
                                aria-label="Tutup live chat"
                                @click="isRightChatOpen = false"
                            >
                                <X class="size-3" />
                                <span>Close</span>
                            </Button>
                        </div>
                        <div
                            class="w-full h-[350px] sm:h-[520px] bg-card relative"
                        >
                            <iframe
                                :key="`chat-lead-${primaryFocusedStream.video_id}`"
                                :src="`https://www.youtube.com/live_chat?v=${primaryFocusedStream.video_id}&embed_domain=${chatEmbedDomain}&dark_theme=1`"
                                class="w-full h-full border-0"
                                allow="autoplay"
                            ></iframe>
                        </div>
                    </Card>

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
                            @update:selectedLayout="
                                emit('update:selectedLayout', $event)
                            "
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
                            @update:selectedLayout="
                                emit('update:selectedLayout', $event)
                            "
                        />
                    </div>
                </div>

                <!-- RIGHT SIDEBAR LIVE CHAT -->
                <div
                    v-if="activeGridChatStream"
                    class="lg:col-span-4 xl:col-span-3 space-y-3 animate-in fade-in zoom-in-95 duration-200"
                >
                    <Card class="overflow-hidden flex flex-col py-0 gap-0">
                        <div
                            class="bg-card px-3.5 py-2 flex items-center justify-between border-b border-border text-xs"
                        >
                            <div
                                class="flex items-center space-x-2 text-primary font-bold truncate"
                            >
                                <MessageSquare class="size-3.5 shrink-0" />
                                <span class="truncate">
                                    Live Chat:
                                    {{
                                        activeGridChatStream.officer
                                            ?.officer_name ||
                                        activeGridChatStream.title
                                    }}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-2 text-xs font-mono text-muted-foreground hover:text-foreground"
                                aria-label="Tutup live chat"
                                @click="emit('update:activeChatVideoId', null)"
                            >
                                <X class="size-3" />
                                <span>Close</span>
                            </Button>
                        </div>
                        <div
                            class="w-full h-[260px] sm:h-[580px] bg-card relative"
                        >
                            <iframe
                                :key="`chat-grid-${activeGridChatStream.video_id}`"
                                :src="`https://www.youtube.com/live_chat?v=${activeGridChatStream.video_id}&embed_domain=${chatEmbedDomain}&dark_theme=1`"
                                class="w-full h-full border-0"
                                allow="autoplay"
                            ></iframe>
                        </div>
                    </Card>
                </div>
            </div>
        </div>

        <!-- GOOGLE MEET BOTTOM STICKY CONTROL BAR -->
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
            @update:selectedDepartment="
                emit('update:selectedDepartment', $event)
            "
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
