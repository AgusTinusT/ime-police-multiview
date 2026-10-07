<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";

// Refactored Sub-Components
import MultiviewHeader from "@/Components/MultiviewHeader.vue";
import MultiviewLandscapeOverlay from "@/Components/MultiviewLandscapeOverlay.vue";
import OfficerVisibilityBottomSheet from "@/Components/OfficerVisibilityBottomSheet.vue";
import TacChannelNotificationToast from "@/Components/TacChannelNotificationToast.vue";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import QuickAddDrawer from "@/Components/Drawers/QuickAddDrawer.vue";
import TacticalStreamGrid from "@/Components/TacticalStreamGrid.vue";
import { useAnalytics } from "@/Composables/useAnalytics";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";
import { useTacChannelManager } from "@/Composables/useTacChannelManager";
import { useYouTubePlayer } from "@/Composables/useYouTubePlayer";
import { defineProps } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const {
    trackPageView,
    trackSelectStream,
    trackDepartmentChange,
    trackHeartbeat,
} = useAnalytics();

const props = defineProps({
    initialStreams: {
        type: Array,
        required: true,
    },
    initialOfflineOfficers: {
        type: Array,
        required: true,
    },
    initialTacChannels: {
        type: Array,
        default: () => [],
    },
    initialReplays: {
        type: Array,
        default: () => [],
    },
    deptStats: {
        type: Object,
        default: () => ({}),
    },
    lastSyncedAt: {
        type: String,
        default: "",
    },
});

// State Management
const streams = ref(props.initialStreams);
const offlineOfficers = ref(props.initialOfflineOfficers);
const recentReplays = ref(props.initialReplays || []);

// GA4 & URL Query Param Sync for Department Tab Switching
const getInitialDepartment = () => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        const deptParam = params.get("dept");
        if (deptParam) {
            return deptParam.trim();
        }
    }
    return "ALL";
};

const selectedDepartment = ref(getInitialDepartment());

// Theater Mode toggle for full-height multiview grid
const isTheaterMode = ref(false);
const toggleTheaterMode = (forceState = null) => {
    if (typeof forceState === "boolean") {
        isTheaterMode.value = forceState;
    } else {
        isTheaterMode.value = !isTheaterMode.value;
    }
};

// Mobile Landscape Immersive Mode State & Touch Controls
const isMobileLandscape = ref(false);
const showLandscapeControls = ref(false);
let landscapeTimer = null;

const checkMobileLandscape = () => {
    if (typeof window === "undefined") return;
    const isMobileSize = window.innerWidth <= 1024;
    const isLandscape = window.matchMedia("(orientation: landscape)").matches;
    isMobileLandscape.value = isMobileSize && isLandscape;
};

const handleLandscapeTap = () => {
    if (!isMobileLandscape.value) return;
    showLandscapeControls.value = true;
    if (landscapeTimer) clearTimeout(landscapeTimer);
    landscapeTimer = setTimeout(() => {
        showLandscapeControls.value = false;
    }, 3500);
};

watch(selectedDepartment, (newDept) => {
    if (typeof window === "undefined") return;

    const url = new URL(window.location.href);
    if (newDept && newDept !== "ALL") {
        url.searchParams.set("dept", newDept);
    } else {
        url.searchParams.delete("dept");
    }

    // GA4 Department Navigation Event & Title Update
    trackDepartmentChange(newDept);

    // Shallow navigation via Inertia: updates address bar & GA4 pageview without reloading page/props
    router.get(
        url.pathname + url.search,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: [],
        },
    );
});

const activeTab = ref("10-8"); // '10-8' (online feeds) or '10-7' (offline roster)
const getInitialLayout = () => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        if (params.has("focus")) {
            return "focus";
        }
        if (window.innerWidth < 640) {
            return "focus";
        }
    }
    return "auto";
};

const getInitialFocusedStreamId = () => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        const focusParam = params.get("focus");
        if (focusParam && focusParam.trim()) {
            return focusParam.trim();
        }
    }
    return null;
};

const selectedLayout = ref(getInitialLayout());
const searchFilter = ref("");

// YouTube Player & Audio Composable
const {
    activeAudioVideoId,
    focusedStreamId,
    isDataSaverEnabled,
    activePreviewVideoIds,
    activeGridVideoIds,
    isFullscreen,
    originUrl,
    chatEmbedDomain,
    players,
    loadYouTubeAPI,
    ensureVideoPlaying,
    controlPlayerAudio,
    applyQualityToPlayer,
    initializePlayer,
    destroyAllPlayers,
    toggleAudio: composableToggleAudio,
    toggleSidebarPreview,
    toggleGridStreamPlay,
    enableDataSaver: composableEnableDataSaver,
    disableDataSaverAndPlayAll: composableDisableDataSaverAndPlayAll,
    openSubscribePopup,
    toggleBrowserFullscreen,
} = useYouTubePlayer();

// Sync focusedStreamId from URL query parameter 'focus'
const initialFocusId = getInitialFocusedStreamId();
if (initialFocusId) {
    focusedStreamId.value = initialFocusId;
}

// Dedicated Clipper Navigation
const page = usePage();

const canTrimVideo = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    return !!user.can_trim_video || ["admin", "clipper"].includes(user.role);
});

const openClipper = (url = "") => {
    if (url) {
        router.visit(`/clipper?url=${encodeURIComponent(url)}`);
    } else {
        router.visit("/clipper");
    }
};

const handleOpenClipperEvent = (e) => {
    openClipper(e?.detail?.url || "");
};

const activeAnnouncements = ref([]);

const fetchAnnouncements = async () => {
    try {
        const res = await fetch("/api/v1/active-announcements", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success") {
                activeAnnouncements.value = data.data;
            }
        }
    } catch (e) {
        console.error("Failed to fetch announcements:", e);
    }
};

// Tactical Radio Channels Manager Composable (Level 2 Refactoring)
const {
    tacChannels,
    activeTacPopoverVideoId,
    expiringTacChannel,
    tacticalToast,
    showTacticalToast,
    fetchTacChannels,
    isTacDepartment,
    formatRemainingTime,
    getTacStreams,
    assignStreamToTac,
    removeStreamFromTac,
    extendTacTimer,
    disbandTacChannel,
    tickTacTimers,
} = useTacChannelManager({
    initialTacChannels: props.initialTacChannels,
    allActiveStreams: () => allActiveStreams.value,
    selectedDepartment,
});

const handleKeyDown = (e) => {
    if (e.key === "F11") {
        e.preventDefault();
        toggleBrowserFullscreen();
    } else if (e.key === "Escape") {
        if (showOfficerFormModal.value) {
            showOfficerFormModal.value = false;
        } else if (activeRightDrawer.value) {
            closeRightDrawer();
        } else if (activeTacPopoverVideoId.value) {
            activeTacPopoverVideoId.value = null;
        }
    }
};

const handleGlobalClick = () => {
    if (activeTacPopoverVideoId.value) {
        activeTacPopoverVideoId.value = null;
    }
};

let tacTimerInterval = null;
let tacPollInterval = null;
let streamPollInterval = null;

const fetchLiveStreamsSilently = async () => {
    try {
        const res = await fetch("/api/v1/streams", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        if (res.ok) {
            const json = await res.json();
            if (json.status === "success" && Array.isArray(json.data)) {
                // Deduplicate incoming streams strictly by video_id
                const incomingMap = new Map();
                json.data.forEach((s) => {
                    if (s && s.video_id) {
                        incomingMap.set(String(s.video_id).trim(), s);
                    }
                });
                const incomingStreams = Array.from(incomingMap.values());

                // 1. Smart merge into streams.value
                streams.value.forEach((existing) => {
                    const fresh = incomingMap.get(
                        String(existing.video_id).trim(),
                    );
                    if (fresh) {
                        existing.title = fresh.title;
                        existing.viewers_count = fresh.viewers_count;
                        existing.status = fresh.status;
                        existing.description = fresh.description;
                        existing.incident_code = fresh.incident_code;
                        if (fresh.officer) {
                            existing.officer = fresh.officer;
                        }
                    }
                });

                // Remove ended streams from streams.value
                streams.value = streams.value.filter((existing) =>
                    incomingMap.has(String(existing.video_id).trim()),
                );

                // Add newly detected live streams
                incomingStreams.forEach((fresh) => {
                    if (
                        !streams.value.some(
                            (existing) =>
                                String(existing.video_id).trim() ===
                                String(fresh.video_id).trim(),
                        )
                    ) {
                        streams.value.push(fresh);
                    }
                });

                // 2. Update offline officers roster & replays
                if (Array.isArray(json.offline_officers)) {
                    offlineOfficers.value = json.offline_officers;
                }
                if (Array.isArray(json.replays)) {
                    recentReplays.value = json.replays;
                }
            }
        }
    } catch (e) {
        console.warn("Silent live streams polling failed:", e);
    }
};

const isSyncingFeeds = ref(false);

const triggerManualSync = async () => {
    if (isSyncingFeeds.value) return;
    isSyncingFeeds.value = true;
    showTacticalToast(
        "Menghubungkan ke satelit YouTube untuk mendeteksi unit 10-8...",
        "info",
    );

    try {
        await fetchLiveStreamsSilently();
        showTacticalToast(
            "Sinkronisasi selesai! Siaran terbaru telah dimuat.",
            "info",
        );
    } catch (e) {
        console.warn("Manual sync failed:", e);
        await fetchLiveStreamsSilently();
    } finally {
        isSyncingFeeds.value = false;
    }
};

// Suppress YouTube internal iframe postMessage error: isExternalMethodAvailable is not a function
const handleYouTubeInternalError = (event) => {
    if (
        event &&
        (event.message?.includes("isExternalMethodAvailable") ||
            event.error?.message?.includes("isExternalMethodAvailable"))
    ) {
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        if (event.preventDefault) event.preventDefault();
        return true;
    }
};

// Dynamic Browser Tab Title Computation
const dynamicPageTitle = computed(() => {
    if (selectedLayout.value === "focus" && primaryFocusedStream.value) {
        const officerName =
            primaryFocusedStream.value.officer?.officer_name ||
            primaryFocusedStream.value.title ||
            "Officer";
        const dept = primaryFocusedStream.value.officer?.department || "UNIT";
        return t("multiview.title_watching", { officer: officerName, dept });
    }
    if (selectedDepartment.value && selectedDepartment.value !== "ALL") {
        const deptName =
            selectedDepartment.value === "PERSONAL"
                ? t("multiview.watchlist")
                : selectedDepartment.value.replace("_", " ");
        return t("multiview.title_category", { category: deptName });
    }
    if (searchFilter.value && searchFilter.value.trim() !== "") {
        return t("multiview.title_search", {
            query: searchFilter.value.trim(),
        });
    }
    return t("multiview.title_default");
});

let heartbeatTimer = null;

onMounted(() => {
    checkMobileLandscape();
    window.addEventListener("resize", checkMobileLandscape);
    window.addEventListener("orientationchange", checkMobileLandscape);
    window.addEventListener("keydown", handleKeyDown);
    document.addEventListener("click", handleGlobalClick);
    window.addEventListener("error", handleYouTubeInternalError, true);
    window.addEventListener("open-clipper-modal", handleOpenClipperEvent);
    loadPersonalStreamsFromStorage();
    syncCloudWatchlist();
    fetchAnnouncements();

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("trim") && canTrimVideo.value) {
        openClipper();
    }

    // Initialize GA4 Pageview & Tab Title
    trackPageView(dynamicPageTitle.value);

    // TAC timer ticker (every 1s), TAC channel polling (every 15s), and Live Streams auto-sync (every 15s)
    tacTimerInterval = setInterval(tickTacTimers, 1000);
    tacPollInterval = setInterval(fetchTacChannels, 15000);
    streamPollInterval = setInterval(fetchLiveStreamsSilently, 15000);

    // Active session telemetry heartbeat for GA4 Realtime watching metrics (every 2 mins)
    heartbeatTimer = setInterval(() => {
        trackHeartbeat({
            activeStreamsCount: visibleStreams.value
                ? visibleStreams.value.length
                : 0,
            focusedStreamId:
                focusedStreamId.value ||
                primaryFocusedStream.value?.video_id ||
                null,
            focusedOfficer:
                primaryFocusedStream.value?.officer?.officer_name || null,
            selectedDepartment: selectedDepartment.value || "ALL",
        });
    }, 120000);
});

onUnmounted(() => {
    window.removeEventListener("resize", checkMobileLandscape);
    window.removeEventListener("orientationchange", checkMobileLandscape);
    if (landscapeTimer) clearTimeout(landscapeTimer);
    window.removeEventListener("keydown", handleKeyDown);
    document.removeEventListener("click", handleGlobalClick);
    window.removeEventListener("error", handleYouTubeInternalError, true);
    window.removeEventListener("open-clipper-modal", handleOpenClipperEvent);
    if (tacTimerInterval) clearInterval(tacTimerInterval);
    if (tacPollInterval) clearInterval(tacPollInterval);
    if (streamPollInterval) clearInterval(streamPollInterval);
    if (heartbeatTimer) clearInterval(heartbeatTimer);
});

const enableDataSaver = () => {
    composableEnableDataSaver(
        allActiveStreams.value,
        selectedLayout.value === "focus",
    );
};

const disableDataSaverAndPlayAll = () => {
    composableDisableDataSaverAndPlayAll(() => {
        initializeAllPlayers();
        if (primaryFocusedStream.value) {
            ensureVideoPlaying(primaryFocusedStream.value.video_id);
        }
        if (secondaryStreams.value) {
            secondaryStreams.value.forEach((s) =>
                ensureVideoPlaying(s.video_id),
            );
        }
        if (displayedGridStreams.value) {
            displayedGridStreams.value.forEach((s) =>
                ensureVideoPlaying(s.video_id),
            );
        }
    });
};

const activeChatVideoId = ref(null);

const syncCloudWatchlist = async () => {
    const user = usePage().props.auth?.user;
    if (!user) return;
    try {
        const res = await fetch("/api/v1/user/watchlist", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success" && Array.isArray(data.video_ids)) {
                const merged = Array.from(
                    new Set([...personalVideoIds.value, ...data.video_ids]),
                );
                personalVideoIds.value = merged;
                savePersonalStreamsToStorage();
            }
        }
    } catch (e) {
        console.warn("Failed to fetch cloud watchlist:", e);
    }
};

const toggleCloudWatchlist = async (videoId) => {
    const user = usePage().props.auth?.user;
    if (!user) return;
    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        await fetch("/api/v1/user/watchlist/toggle", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ video_id: videoId }),
        });
    } catch (e) {
        console.warn("Failed to sync watchlist toggle to cloud:", e);
    }
};

// Personal Category & Custom Ad-hoc Streams (Browser LocalStorage, Max 6 Active Videos)
const MAX_PERSONAL_STREAMS = 6;
const personalVideoIds = ref([]);
const customStreams = ref([]);

const loadPersonalStreamsFromStorage = () => {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            const savedIds = localStorage.getItem("ime_personal_video_ids");
            if (savedIds) {
                const parsed = JSON.parse(savedIds);
                if (Array.isArray(parsed)) {
                    personalVideoIds.value = parsed.filter(
                        (id) =>
                            typeof id === "string" &&
                            !id.startsWith("officer-"),
                    );
                }
            }
            const savedCustom = localStorage.getItem(
                "ime_personal_custom_streams",
            );
            if (savedCustom) {
                const parsedCustom = JSON.parse(savedCustom);
                if (Array.isArray(parsedCustom)) {
                    customStreams.value = parsedCustom.filter(
                        (s) =>
                            s &&
                            s.video_id &&
                            !s.is_officer_card &&
                            !String(s.video_id).startsWith("officer-"),
                    );
                }
            }
        }
    } catch (e) {
        console.warn("Failed to read personal streams from localStorage:", e);
    }
};

const savePersonalStreamsToStorage = () => {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            localStorage.setItem(
                "ime_personal_video_ids",
                JSON.stringify(personalVideoIds.value),
            );
            localStorage.setItem(
                "ime_personal_custom_streams",
                JSON.stringify(customStreams.value),
            );
        }
    } catch (e) {
        console.warn("Failed to save personal streams to localStorage:", e);
    }
};

const { isOfficerDisabled, getOfficerKey } = useOfficerFilter();
const isOfficerVisibilityOpen = ref(false);

const openOfficerVisibilityModal = () => {
    isOfficerVisibilityOpen.value = true;
};

const closeOfficerVisibilityModal = () => {
    isOfficerVisibilityOpen.value = false;
};

// All combined raw streams (Deduplicated strictly by video_id)
const rawActiveStreams = computed(() => {
    const streamMap = new Map();
    // 1. Add official synced streams first
    (streams.value || []).forEach((s) => {
        if (s && s.video_id) {
            streamMap.set(String(s.video_id).trim(), s);
        }
    });
    // 2. Add custom streams only if not already present from official sync
    (customStreams.value || []).forEach((cs) => {
        if (
            cs &&
            cs.video_id &&
            !cs.is_officer_card &&
            !String(cs.video_id).startsWith("officer-")
        ) {
            const key = String(cs.video_id).trim();
            if (!streamMap.has(key)) {
                streamMap.set(key, cs);
            }
        }
    });
    return Array.from(streamMap.values());
});

// Active Streams filtered by user-driven officer visibility (Enable/Disable perwira)
const allActiveStreams = computed(() => {
    return rawActiveStreams.value.filter((s) => !isOfficerDisabled(s));
});

// Currently selected stream for Grid Mode Live Chat Sidebar
const activeGridChatStream = computed(() => {
    if (!activeChatVideoId.value) return null;
    return (
        allActiveStreams.value.find(
            (s) => s.video_id === activeChatVideoId.value,
        ) || null
    );
});

// Currently active/online personal streams (matches video_id, officer channel/handle, or custom stream)
const activePersonalStreams = computed(() => {
    return allActiveStreams.value.filter(
        (s) =>
            personalVideoIds.value.includes(s.video_id) ||
            (s.officer?.channel_id &&
                personalVideoIds.value.includes(s.officer.channel_id)) ||
            (s.officer?.handle &&
                personalVideoIds.value.includes(s.officer.handle)) ||
            customStreams.value.some((cs) => cs.video_id === s.video_id),
    );
});

// Total count of currently active/online personal streams (0 to 6)
const totalPersonalCount = computed(() => {
    return activePersonalStreams.value.length;
});

// Total count of all saved personal entries (active + offline)
const totalSavedPersonalCount = computed(() => {
    const combined = new Set([
        ...personalVideoIds.value,
        ...customStreams.value.map((s) => s.video_id),
    ]);
    return combined.size;
});

const isPersonalStream = (streamOrId) => {
    if (!streamOrId) return false;
    if (typeof streamOrId === "string") {
        if (streamOrId.startsWith("officer-")) return false;
        return (
            personalVideoIds.value.includes(streamOrId) ||
            customStreams.value.some((s) => s.video_id === streamOrId)
        );
    }
    const s = streamOrId;
    if (s.is_officer_card || String(s.video_id).startsWith("officer-"))
        return false;
    return (
        personalVideoIds.value.includes(s.video_id) ||
        (s.officer?.channel_id &&
            personalVideoIds.value.includes(s.officer.channel_id)) ||
        (s.officer?.handle &&
            personalVideoIds.value.includes(s.officer.handle)) ||
        customStreams.value.some((cs) => cs.video_id === s.video_id)
    );
};

const togglePersonalStream = (streamOrId) => {
    if (!streamOrId) return;

    // Reject dummy officer support cards (is_officer_card or video_id starting with officer-)
    if (typeof streamOrId === "object" && streamOrId !== null) {
        if (
            streamOrId.is_officer_card ||
            String(streamOrId.video_id).startsWith("officer-")
        ) {
            const handle = streamOrId.officer?.handle
                ? streamOrId.officer.handle.replace(/^@/, "")
                : "";
            if (handle) {
                window.open(`https://www.youtube.com/@${handle}`, "_blank");
                showTacticalToast(
                    `Membuka channel YouTube ${streamOrId.officer?.officer_name || "Officer"}...`,
                    "info",
                );
            } else {
                showTacticalToast(
                    "Unit offline tidak dapat disematkan ke Personal Watchlist.",
                    "warning",
                );
            }
            return;
        }
    }
    if (typeof streamOrId === "string" && streamOrId.startsWith("officer-")) {
        showTacticalToast(
            "Unit offline tidak dapat disematkan ke Personal Watchlist.",
            "warning",
        );
        return;
    }

    let targetId = streamOrId;
    if (typeof streamOrId === "object" && streamOrId !== null) {
        targetId =
            streamOrId.officer?.channel_id ||
            streamOrId.officer?.handle ||
            streamOrId.video_id;
    } else if (typeof streamOrId === "string") {
        const foundStream = allActiveStreams.value.find(
            (s) => s.video_id === streamOrId,
        );
        if (foundStream?.officer?.channel_id) {
            targetId = foundStream.officer.channel_id;
        } else if (foundStream?.officer?.handle) {
            targetId = foundStream.officer.handle;
        }
    }

    if (!targetId || targetId.startsWith("officer-")) return;

    const idx = personalVideoIds.value.indexOf(targetId);
    if (idx !== -1) {
        personalVideoIds.value.splice(idx, 1);
        customStreams.value = customStreams.value.filter(
            (s) =>
                s.video_id !== targetId &&
                s.officer?.channel_id !== targetId &&
                s.officer?.handle !== targetId,
        );
        savePersonalStreamsToStorage();
        showTacticalToast("Unit dilepas dari Personal Watchlist", "info");
    } else {
        if (activePersonalStreams.value.length >= MAX_PERSONAL_STREAMS) {
            alert(
                `Maksimal ${MAX_PERSONAL_STREAMS} video aktif untuk kategori Personal! Hapus atau unpin salah satu video aktif terlebih dahulu.`,
            );
            return;
        }
        personalVideoIds.value.push(targetId);
        savePersonalStreamsToStorage();
        showTacticalToast("Unit ditambahkan ke Personal Watchlist", "info");
    }
    toggleCloudWatchlist(targetId);
};

const removePersonalStream = (id) => {
    personalVideoIds.value = personalVideoIds.value.filter(
        (itemId) => itemId !== id,
    );
    customStreams.value = customStreams.value.filter(
        (s) => s.video_id !== id && s.id !== id,
    );
    savePersonalStreamsToStorage();
    toggleCloudWatchlist(id);
};

const clearAllPersonalStreams = () => {
    if (
        confirm(
            "Apakah Anda yakin ingin mengosongkan seluruh daftar pin / feed tersimpan di kategori Personal?",
        )
    ) {
        personalVideoIds.value = [];
        customStreams.value = [];
        savePersonalStreamsToStorage();
    }
};

// Stream Info / Description Toggle State & Lazy Detail Loader
const isFetchingStreamDetail = ref({});

const fetchFullStreamDetails = async (videoId) => {
    if (!videoId || isFetchingStreamDetail.value[videoId]) return;
    isFetchingStreamDetail.value[videoId] = true;
    try {
        const res = await fetch(`/api/v1/stream-details?video_id=${videoId}`);
        if (res.ok) {
            const json = await res.json();
            if (json.status === "success" && json.data) {
                const data = json.data;
                // Update in customStreams if present
                const cs = customStreams.value.find(
                    (s) => s.video_id === videoId,
                );
                if (cs) {
                    if (data.description) cs.description = data.description;
                    if (data.title) cs.title = data.title;
                }
                // Update in streams if present
                const s = streams.value.find((s) => s.video_id === videoId);
                if (s) {
                    if (data.description) s.description = data.description;
                    if (data.title) s.title = data.title;
                }
                savePersonalStreamsToStorage();
            }
        }
    } catch (e) {
        console.warn("Failed to fetch stream details for " + videoId, e);
    } finally {
        isFetchingStreamDetail.value[videoId] = false;
    }
};

// Unified list of saved personal items with online/offline status for drawer management
const savedPersonalList = computed(() => {
    const list = [];
    const seen = new Set();

    // 1. Add from customStreams
    customStreams.value.forEach((cs) => {
        if (!seen.has(cs.video_id)) {
            seen.add(cs.video_id);
            const isOnline = allActiveStreams.value.some(
                (s) => s.video_id === cs.video_id,
            );
            list.push({
                id: cs.video_id,
                rawId: cs.video_id,
                video_id: cs.video_id,
                name: cs.officer?.officer_name || cs.title || "Custom Stream",
                subtext: cs.officer?.callsign || cs.video_id,
                thumbnail: cs.thumbnail,
                isOnline: isOnline,
            });
        }
    });

    // 2. Add from personalVideoIds
    personalVideoIds.value.forEach((id) => {
        if (!seen.has(id)) {
            seen.add(id);
            const activeStream = allActiveStreams.value.find(
                (s) =>
                    s.video_id === id ||
                    s.officer?.channel_id === id ||
                    s.officer?.handle === id,
            );
            const offlineOfficer = offlineOfficers.value.find(
                (o) => o.channel_id === id || o.handle === id,
            );

            if (activeStream) {
                list.push({
                    id: id,
                    rawId: id,
                    video_id: activeStream.video_id,
                    name:
                        activeStream.officer?.officer_name ||
                        activeStream.title ||
                        id,
                    subtext:
                        activeStream.officer?.callsign ||
                        activeStream.officer?.handle ||
                        id,
                    thumbnail: activeStream.thumbnail,
                    isOnline: true,
                });
            } else if (offlineOfficer) {
                list.push({
                    id: id,
                    rawId: id,
                    video_id: id,
                    name: offlineOfficer.officer_name,
                    subtext: `${offlineOfficer.department} • ${offlineOfficer.callsign}`,
                    thumbnail: offlineOfficer.avatar_url,
                    isOnline: false,
                });
            } else {
                list.push({
                    id: id,
                    rawId: id,
                    video_id: id,
                    name: id.length === 11 ? `Video (${id})` : id,
                    subtext: "Ended / Offline Stream",
                    thumbnail:
                        id.length === 11
                            ? `https://i.ytimg.com/vi/${id}/hqdefault.jpg`
                            : null,
                    isOnline: false,
                });
            }
        }
    });

    return list;
});

// Quick Add Live Hashtag / Keyword Search State
const quickAddMode = ref("SEARCH"); // 'SEARCH' or 'MANUAL'
const liveSearchQuery = ref("#imeroleplay");
const isLiveSearching = ref(false);
const liveSearchResults = ref([]);
const liveSearchError = ref("");
const liveSearchSuccessNotice = ref("");

// Popular / Gang Hashtag Preset Chips
const popularHashtagPresets = [
    { label: "#imeroleplay", query: "#imeroleplay" },
    { label: "#EMS", query: "#imeroleplay #emsime" },
    { label: "#DOJ", query: "#imeroleplay #DOJ" },
    { label: "#DOC", query: "#imeroleplay #DOC" },
    { label: "#GOV", query: "#imeroleplay #GOV" },
    { label: "#Vagabond", query: "#imeroleplay #Vagabond" },
    { label: "#Allstars", query: "#imeroleplay #Allstars" },
    { label: "#4Blood", query: "#imeroleplay #4Blood" },
    { label: "#5tar", query: "#imeroleplay #5tar" },
];

const quickAddInput = ref({
    urlOrId: "",
    officerName: "External Unit",
    callsign: "TAC-UNIT",
    department: "LSPD",
    patrolZone: "Incident Sector",
});

// Tactical Clock
const currentTime = ref("");
const currentDate = ref("");
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString("en-US", { hour12: false });
    currentDate.value = now.toLocaleDateString("en-US", {
        weekday: "short",
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

let timeInterval = null;
const closeMoreTac = () => {
    isMoreTacOpen.value = false;
};

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
    window.addEventListener("click", closeMoreTac);

    // Auto-detect mobile screen and default to Focus Mode
    if (typeof window !== "undefined" && window.innerWidth < 768) {
        selectedLayout.value = "focus";
    }
});
onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
    window.removeEventListener("click", closeMoreTac);
});

const isMoreTacOpen = ref(false);

// Filtered Streams (Online 10-8)
const filteredStreams = computed(() => {
    let result = allActiveStreams.value;

    if (selectedDepartment.value === "PERSONAL") {
        result = activePersonalStreams.value;
    } else if (isTacDepartment(selectedDepartment.value)) {
        result = getTacStreams(selectedDepartment.value);
    } else if (selectedDepartment.value !== "ALL") {
        if (selectedDepartment.value === "SAPR") {
            result = result.filter(
                (s) =>
                    s.officer &&
                    (s.officer.department === "SAPR" ||
                        s.officer.department === "PARK RANGER"),
            );
        } else {
            result = result.filter(
                (s) =>
                    s.officer &&
                    s.officer.department === selectedDepartment.value,
            );
        }
    }

    if (searchFilter.value.trim() !== "") {
        const query = normalizeUnicodeText(searchFilter.value.trim());
        result = result.filter((s) => {
            const title = normalizeUnicodeText(s.title || "");
            const officerName = normalizeUnicodeText(
                s.officer?.officer_name || s.officer_name || s.name || "",
            );
            const streamerName = normalizeUnicodeText(
                s.officer?.streamer_name || s.streamer_name || "",
            );
            const callsign = normalizeUnicodeText(
                s.officer?.callsign || s.callsign || "",
            );
            const badge = normalizeUnicodeText(
                s.officer?.badge_number || s.badge_number || "",
            );
            const dept = normalizeUnicodeText(
                s.officer?.department || s.department || "",
            );
            const rank = normalizeUnicodeText(s.officer?.rank || s.rank || "");
            const handle = normalizeUnicodeText(
                s.officer?.handle || s.handle || s.channel_id || "",
            );
            const zone = normalizeUnicodeText(
                s.officer?.patrol_zone || s.patrol_zone || "",
            );

            return (
                title.includes(query) ||
                officerName.includes(query) ||
                streamerName.includes(query) ||
                callsign.includes(query) ||
                badge.includes(query) ||
                dept.includes(query) ||
                rank.includes(query) ||
                handle.includes(query) ||
                zone.includes(query)
            );
        });
    }

    return result;
});

// Filtered Offline Officers (10-7)
const filteredOfflineOfficers = computed(() => {
    if (isTacDepartment(selectedDepartment.value)) {
        return [];
    }

    let result = offlineOfficers.value;

    if (selectedDepartment.value === "PERSONAL") {
        result = result.filter(
            (o) =>
                personalVideoIds.value.includes(o.channel_id) ||
                (o.handle && personalVideoIds.value.includes(o.handle)),
        );
    } else if (selectedDepartment.value !== "ALL") {
        if (selectedDepartment.value === "SAPR") {
            result = result.filter(
                (o) =>
                    o.department === "SAPR" || o.department === "PARK RANGER",
            );
        } else {
            result = result.filter(
                (o) => o.department === selectedDepartment.value,
            );
        }
    }

    if (searchFilter.value.trim() !== "") {
        const query = normalizeUnicodeText(searchFilter.value.trim());
        result = result.filter((o) => {
            const officerName = normalizeUnicodeText(
                o.officer_name || o.name || "",
            );
            const streamerName = normalizeUnicodeText(o.streamer_name || "");
            const callsign = normalizeUnicodeText(o.callsign || "");
            const badge = normalizeUnicodeText(o.badge_number || "");
            const dept = normalizeUnicodeText(o.department || "");
            const rank = normalizeUnicodeText(o.rank || "");
            const handle = normalizeUnicodeText(o.handle || o.channel_id || "");
            const zone = normalizeUnicodeText(o.patrol_zone || "");

            return (
                officerName.includes(query) ||
                streamerName.includes(query) ||
                callsign.includes(query) ||
                badge.includes(query) ||
                dept.includes(query) ||
                rank.includes(query) ||
                handle.includes(query) ||
                zone.includes(query)
            );
        });
    }

    return result;
});

// Custom Grid Sequence Ordering System (#1, #2, #3...)
const CUSTOM_ORDER_STORAGE_KEY = "ime_custom_stream_order";
const customStreamOrder = ref([]); // Array of officer key / video_id strings in priority sequence

onMounted(() => {
    if (typeof window !== "undefined") {
        try {
            const savedOrder = localStorage.getItem(CUSTOM_ORDER_STORAGE_KEY);
            if (savedOrder) {
                customStreamOrder.value = JSON.parse(savedOrder);
            }
        } catch (e) {
            console.error(
                "Gagal memuat custom stream order dari localStorage:",
                e,
            );
        }
    }
});

watch(
    customStreamOrder,
    (newVal) => {
        if (typeof window !== "undefined") {
            try {
                localStorage.setItem(
                    CUSTOM_ORDER_STORAGE_KEY,
                    JSON.stringify(newVal),
                );
            } catch (e) {
                console.error(
                    "Gagal menyimpan custom stream order ke localStorage:",
                    e,
                );
            }
        }
    },
    { deep: true },
);

const getCustomOrderRank = (item) => {
    if (!item) return 0;
    const key =
        getOfficerKey(item) ||
        (typeof item === "string"
            ? item.trim()
            : item.video_id
              ? String(item.video_id).trim()
              : "");
    if (!key) return 0;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx !== -1) return idx + 1;

    if (typeof item === "object" && item.video_id) {
        const vIdx = customStreamOrder.value.indexOf(
            String(item.video_id).trim(),
        );
        if (vIdx !== -1) return vIdx + 1;
    }

    return 0;
};

const toggleCustomOrderPin = (item) => {
    if (!item) return;
    const key =
        getOfficerKey(item) ||
        (typeof item === "string"
            ? item.trim()
            : item.video_id
              ? String(item.video_id).trim()
              : "");
    if (!key) return;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx !== -1) {
        customStreamOrder.value.splice(idx, 1);
        showTacticalToast("Perwira dikeluarkan dari urutan prioritas", "info");
    } else {
        customStreamOrder.value.push(key);
        const rank = customStreamOrder.value.length;
        showTacticalToast(
            `Perwira ditambahkan ke urutan prioritas #${rank}`,
            "info",
        );
    }
};

const moveCustomOrderUp = (item) => {
    if (!item) return;
    const key =
        getOfficerKey(item) ||
        (typeof item === "string"
            ? item.trim()
            : item.video_id
              ? String(item.video_id).trim()
              : "");
    if (!key) return;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx > 0) {
        const val = customStreamOrder.value.splice(idx, 1)[0];
        customStreamOrder.value.splice(idx - 1, 0, val);
    }
};

const moveCustomOrderDown = (item) => {
    if (!item) return;
    const key =
        getOfficerKey(item) ||
        (typeof item === "string"
            ? item.trim()
            : item.video_id
              ? String(item.video_id).trim()
              : "");
    if (!key) return;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx !== -1 && idx < customStreamOrder.value.length - 1) {
        const val = customStreamOrder.value.splice(idx, 1)[0];
        customStreamOrder.value.splice(idx + 1, 0, val);
    }
};

const resetCustomStreamOrder = () => {
    customStreamOrder.value = [];
    showTacticalToast("Urutan grid dikembalikan ke posisi semula", "info");
};

// Sidebar Hidden stream video IDs
const hiddenStreamVideoIds = ref([]);

const promoteStreamToFirst = (item) => {
    if (!item) return;
    const targetStream =
        typeof item === "object"
            ? item
            : (allActiveStreams.value || []).find((s) => s.video_id === item) ||
              (allCatalogStreams.value || []).find((s) => s.video_id === item);
    const key =
        getOfficerKey(targetStream || item) ||
        (typeof item === "string"
            ? item.trim()
            : targetStream?.video_id
              ? String(targetStream.video_id).trim()
              : "");
    if (!key) return;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx !== -1) {
        customStreamOrder.value.splice(idx, 1);
    }
    customStreamOrder.value.unshift(key);
};

const visibleStreams = computed(() => {
    const list = filteredStreams.value.filter(
        (s) => !hiddenStreamVideoIds.value.includes(s.video_id),
    );

    if (customStreamOrder.value.length === 0) {
        if (focusedStreamId.value) {
            const focusIdx = list.findIndex(
                (s) => s.video_id === focusedStreamId.value,
            );
            if (focusIdx > 0) {
                const copy = [...list];
                const [focusedItem] = copy.splice(focusIdx, 1);
                copy.unshift(focusedItem);
                return copy;
            }
        }
        return list;
    }

    return [...list].sort((a, b) => {
        const rankA = getCustomOrderRank(a);
        const rankB = getCustomOrderRank(b);

        if (rankA > 0 && rankB > 0) return rankA - rankB;
        if (rankA > 0) return -1;
        if (rankB > 0) return 1;
        return 0;
    });
});

// Displayed Grid Streams (Respects layout limits to prevent offscreen video bandwidth drain)
const displayedGridStreams = computed(() => {
    const list = visibleStreams.value;
    if (selectedLayout.value === "grid-1x2") {
        return list.slice(0, 2);
    }
    if (selectedLayout.value === "grid-1x3") {
        return list.slice(0, 3);
    }
    if (selectedLayout.value === "grid-2x2") {
        return list.slice(0, 4);
    }
    if (selectedLayout.value === "grid-3x3") {
        return list.slice(0, 9);
    }
    if (selectedLayout.value === "grid-4x4") {
        return list.slice(0, 16);
    }
    return list;
});

// ==========================================
// UNICODE FONT NORMALIZATION & HASHTAG VERIFICATION
// ==========================================

// Unicode NFKD Normalization for fancy streamer fonts (Double-Struck, Bold, Italics, Fullwidth, Circled, etc.)
function normalizeUnicodeText(text) {
    if (!text) return "";
    try {
        return text
            .normalize("NFKD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase();
    } catch (e) {
        return (text || "").toLowerCase();
    }
}

// Check if a stream or replay video is specifically an IME POLICE / PATROL stream
const hasRequiredImeHashtag = (stream) => {
    if (!stream) return false;

    // Support officer card fallbacks are always valid
    if (stream.is_officer_card) return true;

    // Must check STREAM TITLE ONLY (not auto-generated description) normalized for Unicode fonts
    const normTitle = normalizeUnicodeText(stream.title || "");

    // Required Police Duty Hashtags & Keywords in TITLE
    const policeTags = ["imepolice", "ime police"];

    return policeTags.some((tag) => normTitle.includes(tag));
};

// ==========================================
// TAC SITUATIONAL STREAMS COMPUTED
// ==========================================

const tacSituationalStreams = computed(() => {
    const allTacVideoIds = [];
    tacChannels.value.forEach((ch) => {
        if (ch.video_ids && Array.isArray(ch.video_ids)) {
            ch.video_ids.forEach((id) =>
                allTacVideoIds.push(String(id).trim()),
            );
        }
    });
    return allActiveStreams.value.filter((s) =>
        allTacVideoIds.includes(String(s.video_id).trim()),
    );
});

// Scroll helper for horizontal swimlane rows

// Helper to construct YouTube URL safely (Handles officer support cards)

// 1-Click Launch from Cinema Hub into Focus Lead View
const playStreamInFocus = (stream) => {
    if (!stream) return;

    // Check for dummy officer support card (is_officer_card or video_id starting with officer-)
    if (
        stream.is_officer_card ||
        (typeof stream.video_id === "string" &&
            stream.video_id.startsWith("officer-"))
    ) {
        const handle = stream.officer?.handle
            ? stream.officer.handle.replace(/^@/, "")
            : "";
        if (handle) {
            window.open(`https://www.youtube.com/@${handle}`, "_blank");
            showTacticalToast(
                `Membuka channel YouTube ${stream.officer?.officer_name || "Officer"} (Road to 1K)...`,
                "info",
            );
        } else {
            showTacticalToast(
                `Unit ${stream.officer?.officer_name || "Officer"} sedang 10-7 Offline (Belum ada siaran/VOD).`,
                "info",
            );
        }
        return;
    }

    if (stream.officer?.department) {
        selectedDepartment.value = stream.officer.department;
    }
    selectedLayout.value = "focus";
    focusedStreamId.value = stream.video_id || stream.id;
    promoteStreamToFirst(stream);

    // GA4 Stream Interaction Tracking & Tab Title Update
    trackSelectStream(stream);

    if (!isDataSaverEnabled.value) {
        toggleGridStreamPlay(stream.video_id);
    }
    showTacticalToast(
        `Memutar siaran unit: ${stream.officer?.callsign || "Unit"} (${stream.officer?.officer_name || "Officer"})`,
        "info",
    );
};

// YouTube Player Engine & Resilient Audio Controller Dashboard Helpers
const initializeAllPlayers = () => {
    if (selectedLayout.value === "focus") {
        if (primaryFocusedStream.value) {
            initializePlayer(primaryFocusedStream.value.video_id, true);
        }
        if (!isDataSaverEnabled.value) {
            secondaryStreams.value.forEach((stream) => {
                initializePlayer(stream.video_id, false);
            });
        } else {
            activePreviewVideoIds.value.forEach((id) => {
                initializePlayer(id, false);
            });
        }
    } else {
        if (!isDataSaverEnabled.value) {
            displayedGridStreams.value.forEach((stream) => {
                initializePlayer(stream.video_id, false);
            });
        } else {
            activeGridVideoIds.value.forEach((id) => {
                initializePlayer(id, false);
            });
            if (activeAudioVideoId.value) {
                initializePlayer(activeAudioVideoId.value, false);
            }
        }
    }
};

const updateAllStreamQualities = () => {
    if (selectedLayout.value === "focus") {
        const leadId = primaryFocusedStream.value?.video_id;
        if (leadId) {
            applyQualityToPlayer(leadId, true);
        }
        activePreviewVideoIds.value.forEach((id) => {
            if (id !== leadId) {
                applyQualityToPlayer(id, false);
            }
        });
    } else {
        displayedGridStreams.value.forEach((stream) => {
            applyQualityToPlayer(stream.video_id, false);
        });
    }
};

const toggleAudio = (videoId) => {
    composableToggleAudio(videoId, allActiveStreams.value);
};

// Clean up active YT player instances when changing department category
watch(selectedDepartment, () => {
    destroyAllPlayers();
});

// Watch for layout changes to automatically route audio & high-quality to Lead Unit in Focus Mode
watch(selectedLayout, (newLayout) => {
    if (newLayout === "focus" && primaryFocusedStream.value) {
        activeAudioVideoId.value = primaryFocusedStream.value.video_id;
        nextTick(() => {
            setTimeout(() => {
                controlPlayerAudio(primaryFocusedStream.value.video_id, true);
                applyQualityToPlayer(primaryFocusedStream.value.video_id, true);
            }, 350);
        });
    } else {
        nextTick(() => {
            setTimeout(() => {
                updateAllStreamQualities();
            }, 350);
        });
    }
});

// Watch for visible streams changes or layout changes
watch(
    [visibleStreams, selectedLayout, isDataSaverEnabled],
    () => {
        nextTick(() => {
            setTimeout(() => {
                initializeAllPlayers();
                updateAllStreamQualities();
            }, 200);
        });
    },
    { deep: true },
);

onMounted(() => {
    loadYouTubeAPI();
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        const focusId = params.get("focus");
        if (focusId) {
            const targetStream = streams.value.find(
                (s) => String(s.video_id) === String(focusId),
            );
            if (targetStream) {
                playStreamInFocus(targetStream);
            }
        }
    }
});

// Layout Grid CSS Class Computation

// Focus Mode & Grid Mode Shared Stream Sequence (Ensures 100% layout state consistency)
const sortedFocusStreams = computed(() => visibleStreams.value);

// Focus mode stream selector
const primaryFocusedStream = computed(() => {
    if (focusedStreamId.value) {
        const targetId = String(focusedStreamId.value).trim();

        // 1. Check in active live streams
        const active = (allActiveStreams.value || []).find(
            (s) => String(s.video_id).trim() === targetId,
        );
        if (active) return active;

        // 2. Check in recent replays / VOD recordings
        const replay = (recentReplays.value || []).find(
            (r) => String(r.video_id).trim() === targetId,
        );
        if (replay) return replay;

        // 3. Check in all catalog streams (combined live + replays)
        const catalog = (allCatalogStreams.value || []).find(
            (s) => String(s.video_id).trim() === targetId,
        );
        if (catalog) return catalog;

        // 4. Check in raw active streams / custom streams
        const raw = (rawActiveStreams.value || []).find(
            (s) => String(s.video_id).trim() === targetId,
        );
        if (raw) return raw;

        // 5. Fallback stream object if targetId is specified in URL but not in state array
        return {
            id: `replay-${targetId}`,
            video_id: targetId,
            title: "Rekaman Patroli",
            thumbnail: `https://i.ytimg.com/vi/${targetId}/hqdefault.jpg`,
            status: "REPLAY",
            incident_code: "10-7 Patrol Replay",
            description: "Rekaman patroli perwira.",
            viewers_count: 0,
            officer: {
                id: 0,
                officer_name: "Patrol Officer",
                department:
                    selectedDepartment.value !== "ALL"
                        ? selectedDepartment.value
                        : "LSPD",
                callsign: "REPLAY",
                badge_number: "#VOD",
                rank: "Officer",
                avatar_url: null,
            },
        };
    }
    if (!sortedFocusStreams.value.length) return null;
    return sortedFocusStreams.value[0];
});

const secondaryStreams = computed(() => {
    if (!primaryFocusedStream.value) return [];
    return sortedFocusStreams.value.filter(
        (s) =>
            String(s.video_id).trim() !==
            String(primaryFocusedStream.value.video_id).trim(),
    );
});

// Swap or set focus stream (Safe re-target with Auto-Audio Follow & Auto-HD)
const setFocusStream = (videoId) => {
    // 1. Mute all other streams and downgrade quality
    allActiveStreams.value.forEach((s) => {
        if (s.video_id !== videoId) {
            controlPlayerAudio(s.video_id, false);
            applyQualityToPlayer(s.video_id, false);
        }
    });
    Object.keys(players).forEach((id) => {
        if (id !== videoId) {
            controlPlayerAudio(id, false);
            applyQualityToPlayer(id, false);
        }
    });

    // 2. Automatically set the newly focused unit as the active audio channel!
    activeAudioVideoId.value = videoId;

    // 3. Clear out player JS handles safely
    delete players[videoId];
    if (primaryFocusedStream.value) {
        delete players[primaryFocusedStream.value.video_id];
    }

    // 4. Update the focused stream ID & GA4 telemetry
    focusedStreamId.value = videoId;
    promoteStreamToFirst(videoId);
    const targetStream =
        allActiveStreams.value.find((s) => s.video_id === videoId) ||
        allCatalogStreams.value.find((s) => s.video_id === videoId);
    if (targetStream) {
        trackSelectStream(targetStream);
    }

    // 5. Remove from sidebar active previews if present
    const prevIdx = activePreviewVideoIds.value.indexOf(videoId);
    if (prevIdx !== -1) {
        activePreviewVideoIds.value.splice(prevIdx, 1);
    }

    // 6. Re-initialize player after Vue mounts the fresh iframe, auto-unmute & set HD quality
    nextTick(() => {
        setTimeout(() => {
            initializePlayer(videoId);
            controlPlayerAudio(videoId, true);
            applyQualityToPlayer(videoId, true);
        }, 200);

        // Secondary confirmation once iframe document completes handshake
        setTimeout(() => {
            if (activeAudioVideoId.value === videoId) {
                controlPlayerAudio(videoId, true);
                applyQualityToPlayer(videoId, true);
            }
        }, 650);
    });
};

// Quick Add Feed (Saves to local Personal list)
const handleQuickAddStream = () => {
    if (!quickAddInput.value.urlOrId) return;

    let videoId = quickAddInput.value.urlOrId.trim();
    const match = videoId.match(
        /(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/,
    );
    if (match) {
        videoId = match[1];
    }

    if (videoId.length !== 11) {
        alert(
            "Invalid YouTube Video ID atau URL. Masukkan 11-digit Video ID atau URL YouTube yang valid.",
        );
        return;
    }

    if (
        activePersonalStreams.value.length >= MAX_PERSONAL_STREAMS &&
        !customStreams.value.some((s) => s.video_id === videoId)
    ) {
        alert(
            `Maksimal ${MAX_PERSONAL_STREAMS} video aktif pada kategori Personal / Custom! Hapus salah satu video terlebih dahulu.`,
        );
        return;
    }

    const newStream = {
        id: "custom-" + Date.now(),
        video_id: videoId,
        title: quickAddInput.value.officerName + " - Tactical Live Feed",
        thumbnail: `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
        status: "LIVE",
        incident_code: "10-8 Tactical Add",
        viewers_count: 0,
        live_chat_url: `https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${chatEmbedDomain.value}&dark_theme=1`,
        officer: {
            id: 999,
            channel_id: "custom-" + Date.now(),
            handle: "@TacticalFeed",
            streamer_name: "Tactical Dispatch Feed",
            officer_name: quickAddInput.value.officerName,
            callsign: quickAddInput.value.callsign || "TAC-01",
            badge_number: "#999",
            department: quickAddInput.value.department,
            rank: "Tactical Unit",
            patrol_zone: quickAddInput.value.patrolZone || "Tactical Sector",
            avatar_url: null,
        },
    };

    customStreams.value.push(newStream);
    if (!personalVideoIds.value.includes(videoId)) {
        personalVideoIds.value.push(videoId);
    }
    savePersonalStreamsToStorage();
    fetchFullStreamDetails(videoId);

    closeRightDrawer();
    quickAddInput.value = {
        urlOrId: "",
        officerName: "External Unit",
        callsign: "TAC-UNIT",
        department: "LSPD",
        patrolZone: "Incident Sector",
    };
};

// Live Hashtag Search Action
const handleSearchLiveStreams = async (searchOverride = null) => {
    const queryToSearch = (
        typeof searchOverride === "string"
            ? searchOverride
            : liveSearchQuery.value
    ).trim();
    if (!queryToSearch) return;

    if (typeof searchOverride === "string") {
        liveSearchQuery.value = searchOverride;
    }

    isLiveSearching.value = true;
    liveSearchError.value = "";
    liveSearchResults.value = [];

    try {
        const response = await fetch("/api/v1/search-live", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            body: JSON.stringify({ q: queryToSearch }),
        });

        const data = await response.json();
        if (data.status === "success") {
            liveSearchResults.value = data.data || [];
            if (liveSearchResults.value.length === 0) {
                liveSearchError.value = `Tidak ada stream YouTube yang sedang LIVE untuk "${queryToSearch}". Coba kata kunci atau hashtag lain.`;
            }
        } else {
            liveSearchError.value =
                data.message || "Gagal mencari live stream YouTube.";
        }
    } catch (err) {
        liveSearchError.value =
            "Terjadi kendala jaringan saat mencari live stream.";
    } finally {
        isLiveSearching.value = false;
    }
};

const handleAddLiveStreamToPersonal = (streamItem) => {
    const videoId = streamItem.video_id;
    if (!videoId) return;

    if (
        activePersonalStreams.value.length >= MAX_PERSONAL_STREAMS &&
        !isPersonalStream(videoId)
    ) {
        alert(
            `Maksimal ${MAX_PERSONAL_STREAMS} video aktif untuk kategori Personal! Hapus salah satu video terlebih dahulu.`,
        );
        return;
    }

    const existingCustomIdx = customStreams.value.findIndex(
        (s) => s.video_id === videoId,
    );
    if (existingCustomIdx === -1) {
        const newStream = {
            id:
                "search-" +
                Date.now() +
                "-" +
                Math.random().toString(36).substring(2, 6),
            video_id: videoId,
            title: streamItem.title,
            thumbnail:
                streamItem.thumbnail_url ||
                `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
            status: "LIVE",
            incident_code: "Target / Gang Feed",
            description: streamItem.description || "",
            viewers_count: streamItem.viewers_count || 0,
            viewers: streamItem.viewers || "Live",
            live_chat_url: `https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${chatEmbedDomain.value}&dark_theme=1`,
            officer: {
                id: 9999,
                channel_id: "target-" + videoId,
                handle: streamItem.channel_name
                    ? "@" + streamItem.channel_name.replace(/\s+/g, "")
                    : "@Target",
                streamer_name:
                    streamItem.channel_name || "Gang / Target Streamer",
                officer_name: streamItem.channel_name || "Target Unit",
                callsign: "TARGET",
                badge_number: "#GANG",
                department: "PERSONAL",
                rank: "Opponent / Gang",
                patrol_zone: "Target Sector",
                avatar_url: null,
            },
        };
        customStreams.value.push(newStream);
    } else {
        customStreams.value[existingCustomIdx].title = streamItem.title;
        customStreams.value[existingCustomIdx].description =
            streamItem.description || "";
        customStreams.value[existingCustomIdx].viewers =
            streamItem.viewers || "Live";
        customStreams.value[existingCustomIdx].viewers_count =
            streamItem.viewers_count || 0;
    }

    if (!personalVideoIds.value.includes(videoId)) {
        personalVideoIds.value.push(videoId);
    }
    savePersonalStreamsToStorage();
    fetchFullStreamDetails(videoId);

    liveSearchSuccessNotice.value = `✓ "${streamItem.channel_name}" berhasil ditambahkan ke 📌 Personal!`;
    setTimeout(() => {
        liveSearchSuccessNotice.value = "";
    }, 3500);
};

// Unified Right Slide-Over Drawer State ('QUICK_ADD', 'FEEDBACK', 'ABOUT', 'DIRECTORY', 'ROSTER', or null)
const activeRightDrawer = ref(null);

const openRightDrawer = (drawerName, extra = null) => {
    activeRightDrawer.value = drawerName;
    if (drawerName === "QUICK_ADD") {
        if (liveSearchResults.value.length === 0) {
            handleSearchLiveStreams("#imeroleplay");
        }
    } else if (drawerName === "FEEDBACK") {
        feedbackForm.value = {
            type: extra || "CHANNEL_REQUEST",
            sender_name: "",
            handle_or_url: "",
            officer_name: "",
            callsign: "",
            department: "LSPD",
            message: "",
        };
        feedbackSuccessToast.value = "";
    } else if (drawerName === "ROSTER") {
        fetchRosterOfficers();
    }
};

const closeRightDrawer = () => {
    activeRightDrawer.value = null;
    showOfficerFormModal.value = false;
};

// Admin Roster Management State & Methods (Protected for Admin)
// Visitor Feedback & Channel Request State & Methods (Option 1 Discord Webhook)
const feedbackSuccessToast = ref("");
const feedbackForm = ref({
    type: "CHANNEL_REQUEST", // 'CHANNEL_REQUEST', 'DATA_CORRECTION', 'BUG_REPORT', 'OTHER'
    sender_name: "",
    handle_or_url: "",
    officer_name: "",
    callsign: "",
    department: "LSPD",
    message: "",
});
</script>

<template>
    <Head :title="dynamicPageTitle" />

    <div
        class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased"
    >
        <!-- Dedicated Multiview Master Header Component (Hidden when Theater Mode or Mobile Landscape is active) -->
        <MultiviewHeader
            v-if="!isTheaterMode && !isMobileLandscape"
            v-model:selectedDepartment="selectedDepartment"
            :all-active-streams="allActiveStreams"
            :active-personal-streams="activePersonalStreams"
            :tac-channels="tacChannels"
            :is-data-saver-enabled="isDataSaverEnabled"
            :is-syncing-feeds="isSyncingFeeds"
            :is-fullscreen="isFullscreen"
            @enable-data-saver="enableDataSaver"
            @disable-data-saver="disableDataSaverAndPlayAll"
            @trigger-sync="triggerManualSync"
            @toggle-fullscreen="toggleBrowserFullscreen"
        />

        <!-- Main Content Area (Dynamic Immersive Fullscreen on Mobile Landscape) -->
        <main
            :class="[
                isMobileLandscape
                    ? 'fixed inset-0 z-50 bg-black w-screen h-[100dvh] p-0 overflow-hidden flex flex-col justify-center'
                    : isTheaterMode
                      ? 'flex-1 w-full px-2 sm:px-4 pt-3 sm:pt-4 pb-6 relative transition-all duration-300'
                      : 'flex-1 w-full max-w-screen-2xl mx-auto p-2 sm:p-4 pb-6 relative transition-all duration-300',
            ]"
            @click="handleLandscapeTap"
        >
            <!-- Refactored Tactical Stream Grid Component (Option B Clean Code) -->
            <TacticalStreamGrid
                v-model:activeTacPopoverVideoId="activeTacPopoverVideoId"
                v-model:activeChatVideoId="activeChatVideoId"
                v-model:activeTab="activeTab"
                v-model:selectedDepartment="selectedDepartment"
                v-model:selectedLayout="selectedLayout"
                v-model:searchFilter="searchFilter"
                :is-data-saver-enabled="isDataSaverEnabled"
                :is-syncing-feeds="isSyncingFeeds"
                :is-theater-mode="isTheaterMode"
                :all-active-streams="allActiveStreams"
                :active-personal-streams="activePersonalStreams"
                :tac-situational-streams="tacSituationalStreams"
                :filtered-offline-officers="filteredOfflineOfficers"
                :visible-streams="visibleStreams"
                :focus-stream="primaryFocusedStream"
                :side-streams="secondaryStreams"
                :grid-streams="displayedGridStreams"
                :active-preview-video-ids="activePreviewVideoIds"
                :active-grid-video-ids="activeGridVideoIds"
                :active-audio-video-id="activeAudioVideoId"
                :active-grid-chat-stream="activeGridChatStream"
                :origin-url="originUrl"
                :chat-embed-domain="chatEmbedDomain"
                :active-announcements="activeAnnouncements"
                :personal-stream-ids="personalVideoIds"
                :tac-channels="tacChannels"
                :custom-order-map="customStreamOrder"
                @play-stream-in-focus="playStreamInFocus"
                @set-focus-stream="setFocusStream"
                @toggle-audio="toggleAudio"
                @toggle-theater-mode="toggleTheaterMode"
                @toggle-fullscreen="toggleBrowserFullscreen"
                @toggle-personal-stream="togglePersonalStream"
                @toggle-custom-order="toggleCustomOrderPin"
                @assign-stream-to-tac="assignStreamToTac"
                @remove-stream-from-tac="removeStreamFromTac"
                @open-subscribe-popup="openSubscribePopup"
                @open-quick-add="openRightDrawer('QUICK_ADD')"
                @open-officer-visibility="openOfficerVisibilityModal"
                @trigger-manual-sync="triggerManualSync"
                @toggle-sidebar-preview="toggleSidebarPreview"
                @extend-tac-timer="extendTacTimer"
                @disband-tac-channel="disbandTacChannel"
                @disable-data-saver="disableDataSaverAndPlayAll"
                @enable-data-saver="enableDataSaver"
                @toggle-grid-stream-play="toggleGridStreamPlay"
            />
        </main>
        <!-- QUICK ADD STREAM DRAWER -->
        <QuickAddDrawer
            :is-open="activeRightDrawer === 'QUICK_ADD'"
            v-model:quick-add-mode="quickAddMode"
            v-model:live-search-query="liveSearchQuery"
            :is-live-searching="isLiveSearching"
            :live-search-error="liveSearchError"
            :live-search-success-notice="liveSearchSuccessNotice"
            :live-search-results="liveSearchResults"
            :popular-hashtag-presets="popularHashtagPresets"
            :quick-add-input="quickAddInput"
            :total-personal-count="totalPersonalCount"
            :total-saved-personal-count="totalSavedPersonalCount"
            :saved-personal-list="savedPersonalList"
            :personal-stream-ids="personalVideoIds"
            @close="closeRightDrawer"
            @search-live-streams="handleSearchLiveStreams"
            @add-live-stream-to-personal="handleAddLiveStreamToPersonal"
            @quick-add-stream="handleQuickAddStream"
            @clear-all-personal="clearAllPersonalStreams"
            @remove-personal-stream="removePersonalStream"
        />

        <!-- Global Expiring TAC Channel Alert & Action Toast -->
        <TacChannelNotificationToast
            :expiring-tac-channel="expiringTacChannel"
            :tactical-toast="tacticalToast"
            :format-remaining-time="formatRemainingTime"
            @extend-timer="(code, mins) => extendTacTimer(code, mins)"
            @disband-channel="(code) => disbandTacChannel(code)"
            @select-department="(code) => (selectedDepartment = code)"
        />

        <!-- Floating Tactical Community Chat -->
        <TacticalChatDrawer />

        <!-- OFFICER VISIBILITY BOTTOM SHEET PANEL (Enable/Disable Perwira) -->
        <OfficerVisibilityBottomSheet
            :is-open="isOfficerVisibilityOpen"
            :all-active-streams="rawActiveStreams"
            :offline-officers="offlineOfficers"
            :custom-stream-order="customStreamOrder"
            @close="closeOfficerVisibilityModal"
            @toggle-priority="toggleCustomOrderPin"
            @move-priority-up="moveCustomOrderUp"
            @move-priority-down="moveCustomOrderDown"
            @reset-priority="resetCustomStreamOrder"
        />

        <!-- Floating Immersive Overlay Controls for Mobile Landscape (Refactored Level 2) -->
        <MultiviewLandscapeOverlay
            :is-mobile-landscape="isMobileLandscape"
            :show-landscape-controls="showLandscapeControls"
            :is-fullscreen="isFullscreen"
            @toggle-fullscreen="toggleBrowserFullscreen"
        />
    </div>
</template>

<style scoped>
/* Tactical Command Center Aesthetics */
iframe {
    width: 100%;
    height: 100%;
}

/* Hide horizontal scrollbars across all browsers for swimlanes */
.scrollbar-none {
    -ms-overflow-style: none !important;
    scrollbar-width: none !important;
}
.scrollbar-none::-webkit-scrollbar {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
}

/* Custom Scrollbar for Right Supporting Column */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.6);
    border-radius: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.4);
    border-radius: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.7);
}
</style>
