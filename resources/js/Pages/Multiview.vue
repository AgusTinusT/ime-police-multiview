<script setup>
import { ref, onMounted, computed, watch, onUnmounted, nextTick } from "vue";
import { usePage, Head, Link, router } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";

// Refactored Sub-Components
import MultiviewHeader from "@/Components/MultiviewHeader.vue";
import TacticalStreamGrid from "@/Components/TacticalStreamGrid.vue";
import TacticalDrawers from "@/Components/TacticalDrawers.vue";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import OfficerVisibilityBottomSheet from "@/Components/OfficerVisibilityBottomSheet.vue";
import { useYouTubePlayer } from "@/Composables/useYouTubePlayer";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";
import { useAnalytics } from "@/Composables/useAnalytics";

const { trackPageView, trackSelectStream, trackDepartmentChange, trackHeartbeat } = useAnalytics();

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

const handleScrollTrigger = () => {
    if (typeof window === "undefined") return;
    const isMobile = window.innerWidth < 640;
    if (isMobile && window.scrollY > 30 && !isTheaterMode.value) {
        isTheaterMode.value = true;
    }
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
const isSidebarOpen = ref(true);
const getInitialLayout = () => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        if (params.has("focus")) {
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
    muteAll: composableMuteAll,
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

// Tactical Radio Channels (TAC 1 to TAC 10) State
const defaultTacChannels = [
    {
        id: 1,
        code: "TAC_1",
        name: "TAC 1",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 2,
        code: "TAC_2",
        name: "TAC 2",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 3,
        code: "TAC_3",
        name: "TAC 3",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 4,
        code: "TAC_4",
        name: "TAC 4",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 5,
        code: "TAC_5",
        name: "TAC 5",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 6,
        code: "TAC_6",
        name: "TAC 6",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 7,
        code: "TAC_7",
        name: "TAC 7",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 8,
        code: "TAC_8",
        name: "TAC 8",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 9,
        code: "TAC_9",
        name: "TAC 9",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 10,
        code: "TAC_10",
        name: "TAC 10",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
];
const tacChannels = ref(
    props.initialTacChannels && props.initialTacChannels.length > 0
        ? props.initialTacChannels
        : defaultTacChannels,
);
const activeTacPopoverVideoId = ref(null);
const tacticalToast = ref(null);

const getCsrfToken = () => {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || ""
    );
};

const showTacticalToast = (message, type = "info") => {
    tacticalToast.value = { message, type };
    setTimeout(() => {
        if (tacticalToast.value?.message === message) {
            tacticalToast.value = null;
        }
    }, 3500);
};

const getStreamTac = (videoId) => {
    if (!videoId) return null;
    const ch = tacChannels.value.find(
        (c) => c.video_ids && c.video_ids.includes(videoId),
    );
    return ch ? ch.code : null;
};

const getTacChannel = (tacCode) => {
    return tacChannels.value.find((c) => c.code === tacCode) || null;
};

const getTacUnitCount = (tacCode) => {
    const ch = getTacChannel(tacCode);
    return ch && ch.video_ids ? ch.video_ids.length : 0;
};

const getTacRemainingSeconds = (tacCode) => {
    const ch = getTacChannel(tacCode);
    return ch ? ch.remaining_seconds || 0 : 0;
};

const isTacDepartment = (deptId) => {
    return typeof deptId === "string" && deptId.startsWith("TAC_");
};

const formatRemainingTime = (seconds) => {
    if (!seconds || seconds <= 0) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
};

const getTacStreams = (tacCode) => {
    const ch = getTacChannel(tacCode);
    if (!ch || !ch.video_ids || ch.video_ids.length === 0) return [];
    const ids = ch.video_ids.map((id) => String(id).trim());
    return allActiveStreams.value.filter((s) =>
        ids.includes(String(s.video_id).trim()),
    );
};

// Announcements & Promos State
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

// Carousel Logic
const activePromoIndex = ref(0);
let promoTimer = null;

const startPromoTimer = () => {
    if (promoTimer) clearInterval(promoTimer);
    if (activeAnnouncements.value.length > 1) {
        promoTimer = setInterval(() => {
            nextPromo();
        }, 5000); // 5 seconds
    }
};

const pausePromoTimer = () => {
    if (promoTimer) clearInterval(promoTimer);
};

const nextPromo = () => {
    if (activeAnnouncements.value.length === 0) return;
    activePromoIndex.value =
        (activePromoIndex.value + 1) % activeAnnouncements.value.length;
};

const prevPromo = () => {
    if (activeAnnouncements.value.length === 0) return;
    activePromoIndex.value =
        (activePromoIndex.value - 1 + activeAnnouncements.value.length) %
        activeAnnouncements.value.length;
};

const setPromo = (index) => {
    activePromoIndex.value = index;
    startPromoTimer(); // Reset timer on manual selection
};

// Start timer when announcements are successfully fetched
watch(
    activeAnnouncements,
    (newVal) => {
        if (newVal.length > 0) {
            activePromoIndex.value = 0;
            startPromoTimer();
        } else {
            pausePromoTimer();
        }
    },
    { deep: true },
);

// Fetch & Synchronize TAC Channels from Server
const fetchTacChannels = async () => {
    try {
        const res = await fetch("/api/v1/tac", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success" && data.data) {
                tacChannels.value = data.data;
            }
        }
    } catch (e) {
        console.warn("TAC sync failed:", e);
    }
};

// 1-Click TAC Assignment
const assignStreamToTac = async (tacCode, videoId) => {
    if (!tacCode || !videoId) return;
    const cleanVideoId = String(videoId).trim();

    // Optimistic UI update
    tacChannels.value.forEach((ch) => {
        if (
            ch.code !== tacCode &&
            ch.video_ids &&
            ch.video_ids.includes(cleanVideoId)
        ) {
            ch.video_ids = ch.video_ids.filter((id) => id !== cleanVideoId);
            ch.unit_count = ch.video_ids.length;
            if (ch.unit_count === 0) {
                ch.remaining_seconds = 0;
                ch.is_active = false;
            }
        }
    });

    const targetCh = getTacChannel(tacCode);
    if (targetCh) {
        if (!targetCh.video_ids) targetCh.video_ids = [];
        if (!targetCh.video_ids.includes(cleanVideoId)) {
            targetCh.video_ids.push(cleanVideoId);
        }
        targetCh.unit_count = targetCh.video_ids.length;
        if (!targetCh.remaining_seconds || targetCh.remaining_seconds <= 0) {
            targetCh.remaining_seconds = 1800; // 30 mins
        }
        targetCh.is_active = true;
    }

    showTacticalToast(
        `Unit berhasil dimasukkan ke ${tacCode.replace("_", " ")} (30 Menit)`,
    );

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/api/v1/tac/assign", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                tac_code: tacCode,
                video_id: cleanVideoId,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success" && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error(
                "TAC Assign API Error:",
                res.status,
                await res.text(),
            );
        }
    } catch (e) {
        console.error("Failed to assign stream to TAC:", e);
    }
};

// Remove stream from TAC
const removeStreamFromTac = async (videoId, tacCode = null) => {
    if (!videoId) return;
    const cleanVideoId = String(videoId).trim();

    // Optimistic UI update
    tacChannels.value.forEach((ch) => {
        if (!tacCode || ch.code === tacCode) {
            if (ch.video_ids && ch.video_ids.includes(cleanVideoId)) {
                ch.video_ids = ch.video_ids.filter((id) => id !== cleanVideoId);
                ch.unit_count = ch.video_ids.length;
                if (ch.unit_count === 0) {
                    ch.remaining_seconds = 0;
                    ch.is_active = false;
                }
            }
        }
    });

    showTacticalToast("Unit dilepas dari Tactical Radio");

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/api/v1/tac/remove", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                video_id: cleanVideoId,
                tac_code: tacCode,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success" && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error(
                "TAC Remove API Error:",
                res.status,
                await res.text(),
            );
        }
    } catch (e) {
        console.error("Failed to remove stream from TAC:", e);
    }
};

// Extend TAC Timer
const extendTacTimer = async (tacCode, minutes = 20) => {
    if (!tacCode) return;

    // Optimistic UI update
    const targetCh = getTacChannel(tacCode);
    if (targetCh) {
        targetCh.remaining_seconds =
            (targetCh.remaining_seconds || 0) + minutes * 60;
    }

    showTacticalToast(
        `Waktu situasi ${tacCode.replace("_", " ")} diperpanjang +${minutes} menit`,
    );

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/api/v1/tac/extend", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                tac_code: tacCode,
                minutes: minutes,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success" && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error(
                "TAC Extend API Error:",
                res.status,
                await res.text(),
            );
        }
    } catch (e) {
        console.error("Failed to extend TAC timer:", e);
    }
};

// Disband / Clear TAC Channel
const disbandTacChannel = async (tacCode) => {
    if (!tacCode) return;

    if (
        !confirm(
            `Apakah Anda yakin ingin mengosongkan / membubarkan kanal ${tacCode.replace("_", " ")} untuk seluruh penonton?`,
        )
    ) {
        return;
    }

    // Optimistic UI update
    const targetCh = getTacChannel(tacCode);
    if (targetCh) {
        targetCh.video_ids = [];
        targetCh.unit_count = 0;
        targetCh.remaining_seconds = 0;
        targetCh.is_active = false;
        targetCh.expires_at = null;
    }

    showTacticalToast(
        `Kanal ${tacCode.replace("_", " ")} telah dibubarkan / dikosongkan`,
    );

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/api/v1/tac/clear", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                tac_code: tacCode,
            }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success" && data.data) {
                tacChannels.value = data.data;
            }
        } else {
            console.error("TAC Clear API Error:", res.status, await res.text());
        }
    } catch (e) {
        console.error("Failed to disband TAC channel:", e);
    }
};

// Expiring TAC channel for global alert prompt (Active only in last 60 seconds)
const expiringTacChannel = computed(() => {
    return tacChannels.value.find(
        (c) =>
            c.video_ids &&
            c.video_ids.length > 0 &&
            c.remaining_seconds > 0 &&
            c.remaining_seconds <= 60 &&
            selectedDepartment.value !== c.code,
    );
});

// Focus Mode Right-Column Live Chat State (Default Open)
const isRightChatOpen = ref(true);

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

const handleGlobalClick = (e) => {
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
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/api/v1/sync", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
        });
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

const tickTacTimers = () => {
    let hasExpired = false;
    tacChannels.value.forEach((ch) => {
        if (ch.remaining_seconds > 0) {
            ch.remaining_seconds -= 1;
            if (ch.remaining_seconds <= 0) {
                ch.remaining_seconds = 0;
                ch.is_active = false;
                ch.video_ids = [];
                ch.unit_count = 0;
                hasExpired = true;
            }
        }
    });
    if (hasExpired) {
        fetchTacChannels();
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
        const dept =
            primaryFocusedStream.value.officer?.department || "UNIT";
        return `Menonton: ${officerName} (${dept}) – IME RP Multiview`;
    }
    if (selectedDepartment.value && selectedDepartment.value !== "ALL") {
        const deptName =
            selectedDepartment.value === "PERSONAL"
                ? "Watchlist Personal"
                : selectedDepartment.value.replace("_", " ");
        return `Kategori: ${deptName} – IME RP Multiview`;
    }
    if (searchFilter.value && searchFilter.value.trim() !== "") {
        return `Cari: ${searchFilter.value.trim()} – IME RP Multiview`;
    }
    return "Multiview (10-8 Live Officer Feeds) – IME RP Multiview";
});

let heartbeatTimer = null;

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
    document.addEventListener("click", handleGlobalClick);
    window.addEventListener("error", handleYouTubeInternalError, true);
    window.addEventListener("open-clipper-modal", handleOpenClipperEvent);
    window.addEventListener("scroll", handleScrollTrigger, { passive: true });
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
            activeStreamsCount: visibleStreams.value ? visibleStreams.value.length : 0,
            focusedStreamId: focusedStreamId.value || primaryFocusedStream.value?.video_id || null,
            focusedOfficer: primaryFocusedStream.value?.officer?.officer_name || null,
            selectedDepartment: selectedDepartment.value || "ALL",
        });
    }, 120000);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyDown);
    document.removeEventListener("click", handleGlobalClick);
    window.removeEventListener("error", handleYouTubeInternalError, true);
    window.removeEventListener("open-clipper-modal", handleOpenClipperEvent);
    window.removeEventListener("scroll", handleScrollTrigger);
    if (tacTimerInterval) clearInterval(tacTimerInterval);
    if (tacPollInterval) clearInterval(tacPollInterval);
    if (streamPollInterval) clearInterval(streamPollInterval);
    if (heartbeatTimer) clearInterval(heartbeatTimer);
    pausePromoTimer();
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

const playAllGridStreams = () => {
    disableDataSaverAndPlayAll();
};

const pauseAllGridStreams = () => {
    enableDataSaver();
};

const isQuickAddModalOpen = ref(false);
const activeChatVideoId = ref(null);

const handleLogout = () => {
    router.post("/logout");
};

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

// Safe HTML escaping helper to prevent XSS
const escapeHtml = (text) => {
    if (!text) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
};

// Format description with clickable links and preserved line breaks
const formatDescriptionWithLinks = (text) => {
    if (!text) return "";
    const safeText = escapeHtml(text);

    // Convert standard URLs (https://, http://) into clickable styled links
    const urlRegex = /(https?:\/\/[^\s<>"']+)/g;
    return safeText.replace(urlRegex, (url) => {
        return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300 underline font-medium break-all hover:text-white transition-colors">${url}</a>`;
    });
};

// Stream Info / Description Toggle State & Lazy Detail Loader
const expandedInfoVideoIds = ref([]);
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

const toggleStreamInfo = (videoId) => {
    const idx = expandedInfoVideoIds.value.indexOf(videoId);
    if (idx !== -1) {
        expandedInfoVideoIds.value.splice(idx, 1);
        return;
    }

    expandedInfoVideoIds.value.push(videoId);

    // If description is missing or ends with "..." (search snippet), fetch full description in background
    const targetStream = allActiveStreams.value.find(
        (s) => s.video_id === videoId,
    );
    if (
        !targetStream?.description ||
        targetStream.description.endsWith("...") ||
        targetStream.description.endsWith("…")
    ) {
        fetchFullStreamDetails(videoId);
    }
};

const isStreamInfoOpen = (videoId) =>
    expandedInfoVideoIds.value.includes(videoId);

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

// Main Visible Department Tabs (ALL, PERSONAL, LSPD, BCSO, SASP, SAPR + Primary TAC 1 to 3)
const departments = [
    {
        id: "ALL",
        name: "ALL UNITS",
        icon: iconAllUnits,
        isSvg: true,
        color: "border-slate-600 text-slate-300",
    },
    {
        id: "PERSONAL",
        name: "PERSONAL",
        icon: iconPersonal,
        isSvg: true,
        color: "border-purple-500 text-purple-300 bg-purple-950/40",
    },
    {
        id: "LSPD",
        name: "LSPD",
        icon: iconLspd,
        isSvg: true,
        color: "border-blue-500 text-blue-400 bg-blue-950/40",
    },
    {
        id: "BCSO",
        name: "BCSO",
        icon: iconBcso,
        isSvg: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "SASP",
        name: "SASP",
        icon: iconSasp,
        isSvg: true,
        color: "border-teal-500 text-teal-400 bg-teal-950/40",
    },
    {
        id: "SAPR",
        name: "SAPR",
        icon: iconSapr,
        isSvg: true,
        color: "border-green-500 text-green-400 bg-green-950/40",
    },
    {
        id: "TAC_1",
        name: "TAC 1",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_2",
        name: "TAC 2",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_3",
        name: "TAC 3",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
];

// Extended Dropdown TAC Channels (TAC 4 to TAC 10)
const dropdownTacDepartments = [
    {
        id: "TAC_4",
        name: "TAC 4",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_5",
        name: "TAC 5",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_6",
        name: "TAC 6",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_7",
        name: "TAC 7",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_8",
        name: "TAC 8",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_9",
        name: "TAC 9",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
    {
        id: "TAC_10",
        name: "TAC 10",
        icon: iconRadio,
        isSvg: true,
        isTac: true,
        color: "border-amber-500 text-amber-400 bg-amber-950/40",
    },
];

const isMoreTacOpen = ref(false);
const tacDropdownPos = ref({ top: 0, left: 0 });

const toggleMoreTac = (event) => {
    isMoreTacOpen.value = !isMoreTacOpen.value;
    if (isMoreTacOpen.value && event?.currentTarget) {
        const rect = event.currentTarget.getBoundingClientRect();
        tacDropdownPos.value = {
            top: rect.bottom + 6,
            left: Math.min(rect.left, Math.max(10, window.innerWidth - 205)),
        };
    }
};

const getDeptIcon = (dept) => {
    if (typeof dept === "string" && dept.startsWith("TAC_")) return iconRadio;
    switch (dept) {
        case "LSPD":
            return iconLspd;
        case "BCSO":
            return iconBcso;
        case "SASP":
            return iconSasp;
        case "SAPR":
        case "PARK RANGER":
            return iconSapr;
        case "PERSONAL":
            return iconPersonal;
        default:
            return iconAllUnits;
    }
};

// Department styling helper
const getDeptBadgeClass = (dept) => {
    if (typeof dept === "string" && dept.startsWith("TAC_"))
        return "bg-amber-600/30 text-amber-300 border-amber-500/50";
    switch (dept) {
        case "LSPD":
            return "bg-blue-600/30 text-blue-300 border-blue-500/50";
        case "BCSO":
            return "bg-amber-600/30 text-amber-300 border-amber-500/50";
        case "SASP":
            return "bg-teal-600/30 text-teal-300 border-teal-500/50";
        case "SAPR":
        case "PARK RANGER":
            return "bg-green-600/30 text-green-300 border-green-500/50";
        default:
            return "bg-slate-700/40 text-slate-300 border-slate-600";
    }
};

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
            const officerName = normalizeUnicodeText(s.officer?.officer_name || s.officer_name || s.name || "");
            const streamerName = normalizeUnicodeText(s.officer?.streamer_name || s.streamer_name || "");
            const callsign = normalizeUnicodeText(s.officer?.callsign || s.callsign || "");
            const badge = normalizeUnicodeText(s.officer?.badge_number || s.badge_number || "");
            const dept = normalizeUnicodeText(s.officer?.department || s.department || "");
            const rank = normalizeUnicodeText(s.officer?.rank || s.rank || "");
            const handle = normalizeUnicodeText(s.officer?.handle || s.handle || s.channel_id || "");
            const zone = normalizeUnicodeText(s.officer?.patrol_zone || s.patrol_zone || "");

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
            const officerName = normalizeUnicodeText(o.officer_name || o.name || "");
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
            console.error("Gagal memuat custom stream order dari localStorage:", e);
        }
    }
});

watch(
    customStreamOrder,
    (newVal) => {
        if (typeof window !== "undefined") {
            try {
                localStorage.setItem(CUSTOM_ORDER_STORAGE_KEY, JSON.stringify(newVal));
            } catch (e) {
                console.error("Gagal menyimpan custom stream order ke localStorage:", e);
            }
        }
    },
    { deep: true }
);

const getCustomOrderRank = (item) => {
    if (!item) return 0;
    const key = getOfficerKey(item) || (typeof item === "string" ? item.trim() : (item.video_id ? String(item.video_id).trim() : ""));
    if (!key) return 0;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx !== -1) return idx + 1;

    if (typeof item === "object" && item.video_id) {
        const vIdx = customStreamOrder.value.indexOf(String(item.video_id).trim());
        if (vIdx !== -1) return vIdx + 1;
    }

    return 0;
};

const toggleCustomOrderPin = (item) => {
    if (!item) return;
    const key = getOfficerKey(item) || (typeof item === "string" ? item.trim() : (item.video_id ? String(item.video_id).trim() : ""));
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
    const key = getOfficerKey(item) || (typeof item === "string" ? item.trim() : (item.video_id ? String(item.video_id).trim() : ""));
    if (!key) return;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx > 0) {
        const val = customStreamOrder.value.splice(idx, 1)[0];
        customStreamOrder.value.splice(idx - 1, 0, val);
    }
};

const moveCustomOrderDown = (item) => {
    if (!item) return;
    const key = getOfficerKey(item) || (typeof item === "string" ? item.trim() : (item.video_id ? String(item.video_id).trim() : ""));
    if (!key) return;

    const idx = customStreamOrder.value.indexOf(key);
    if (idx !== -1 && idx < customStreamOrder.value.length - 1) {
        const val = customStreamOrder.value.splice(idx, 1)[0];
        customStreamOrder.value.splice(idx + 1, 0, val);
    }
};

const moveCustomOrderToTop = (videoId) => {
    const strId = String(videoId).trim();
    const idx = customStreamOrder.value.indexOf(strId);
    if (idx !== -1) {
        customStreamOrder.value.splice(idx, 1);
    }
    customStreamOrder.value.unshift(strId);
    showTacticalToast("Siaran dijadikan urutan #1 terdepan", "info");
};

const resetCustomStreamOrder = () => {
    customStreamOrder.value = [];
    showTacticalToast("Urutan grid dikembalikan ke posisi semula", "info");
};

// Sidebar Hidden stream video IDs
const hiddenStreamVideoIds = ref([]);
const toggleStreamVisibility = (videoId) => {
    const idx = hiddenStreamVideoIds.value.indexOf(videoId);
    if (idx === -1) {
        hiddenStreamVideoIds.value.push(videoId);
    } else {
        hiddenStreamVideoIds.value.splice(idx, 1);
    }
};

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
// NETFLIX-STYLE CINEMA HUB COMPUTED CATEGORIES
// ==========================================

// All combined catalog streams (Active Live Streams prioritized + Offline Patrol Replays, strictly deduplicated by video_id)
const allCatalogStreams = computed(() => {
    const catalogMap = new Map();

    // 1. Prioritize active live streams first
    allActiveStreams.value.forEach((s) => {
        if (s && s.video_id && hasRequiredImeHashtag(s)) {
            catalogMap.set(String(s.video_id).trim(), s);
        }
    });

    // 2. Add recent replays only if video_id is not already present and passes hashtag check
    (recentReplays.value || []).forEach((replay) => {
        if (replay && replay.video_id && hasRequiredImeHashtag(replay)) {
            const key = String(replay.video_id).trim();
            if (!catalogMap.has(key)) {
                catalogMap.set(key, replay);
            }
        }
    });

    let list = Array.from(catalogMap.values());

    if (searchFilter.value.trim() !== "") {
        const query = normalizeUnicodeText(searchFilter.value);
        list = list.filter((s) => {
            const normTitle = normalizeUnicodeText(s.title);
            const normOfficerName = normalizeUnicodeText(
                s.officer?.officer_name,
            );
            const normCallsign = normalizeUnicodeText(s.officer?.callsign);
            const normBadge = normalizeUnicodeText(s.officer?.badge_number);
            const normStreamer = normalizeUnicodeText(s.officer?.streamer_name);
            const normZone = normalizeUnicodeText(s.officer?.patrol_zone);

            return (
                normTitle.includes(query) ||
                normOfficerName.includes(query) ||
                normCallsign.includes(query) ||
                normBadge.includes(query) ||
                normStreamer.includes(query) ||
                normZone.includes(query)
            );
        });
    }

    return sortStreamsByStatusAndRecency(list);
});

// Hero Spotlight: #1 Live patrol stream, or if none live -> #1 Latest patrol replay
const topHeroStream = computed(() => {
    const validLive = allActiveStreams.value.filter((s) =>
        hasRequiredImeHashtag(s),
    );
    if (validLive.length > 0) {
        return [...validLive].sort(
            (a, b) => (b.viewers_count || 0) - (a.viewers_count || 0),
        )[0];
    }
    const validReplays = recentReplayStreams.value;
    if (validReplays.length > 0) {
        return validReplays[0];
    }
    return null;
});

// Live trending streams (sorted by viewers, strictly filtered for police hashtags in title)
const trendingStreams = computed(() => {
    return allActiveStreams.value
        .filter((s) => hasRequiredImeHashtag(s))
        .sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0));
});

const formatRelativeTimeLabel = (str) => {
    if (!str) return "REPLAY";
    const text = String(str).trim();
    if (!text || text === "REPLAY" || text === "Target 1K Milestone")
        return text;

    const lower = text.toLowerCase();
    if (
        lower.includes("baru saja") ||
        lower.includes("just now") ||
        lower.includes("live") ||
        lower.includes("sedang tayang")
    ) {
        return "Baru saja";
    }

    const numMatch = lower.match(/(\d+)/);
    if (!numMatch) return text;
    const num = parseInt(numMatch[1], 10);
    const isIndonesian = lower.includes("lalu");

    if (
        lower.includes("tahun") ||
        lower.includes("year") ||
        lower.match(/\b\d+\s*(?:y|thn|th)\b/)
    ) {
        return `${num} tahun yang lalu`;
    }
    if (
        lower.includes("bulan") ||
        lower.includes("month") ||
        lower.match(/\b\d+\s*(?:mo|bln)\b/)
    ) {
        return `${num} bulan yang lalu`;
    }
    if (
        lower.includes("minggu") ||
        lower.includes("week") ||
        lower.match(/\b\d+\s*w\b/)
    ) {
        return `${num} minggu yang lalu`;
    }

    // Handle "hari" vs "jam": In YouTube Indonesian UI, "2 h lalu" = 2 HARI LALU! In English UI, "2h ago" = 2 HOURS AGO!
    if (
        lower.includes("hari") ||
        lower.includes("day") ||
        (isIndonesian && lower.match(/\b\d+\s*h\b/)) ||
        (!isIndonesian && lower.match(/\b\d+\s*d\b/))
    ) {
        return `${num} hari yang lalu`;
    }
    if (
        lower.includes("jam") ||
        lower.includes("hour") ||
        (isIndonesian && lower.match(/\b\d+\s*j\b/)) ||
        (!isIndonesian && lower.match(/\b\d+\s*h\b/))
    ) {
        return `${num} jam yang lalu`;
    }
    if (
        lower.includes("menit") ||
        lower.includes("minute") ||
        lower.includes("min") ||
        lower.match(/\b\d+\s*m\b/)
    ) {
        return `${num} menit yang lalu`;
    }
    if (
        lower.includes("detik") ||
        lower.includes("second") ||
        lower.includes("sec") ||
        lower.match(/\b\d+\s*s\b/)
    ) {
        return `${num} detik yang lalu`;
    }

    return text;
};

const parseRelativeTimeToSeconds = (str) => {
    if (!str) return 999999999;
    const text = String(str).toLowerCase().trim();
    if (
        text.includes("baru saja") ||
        text.includes("just now") ||
        text.includes("live") ||
        text.includes("sedang tayang")
    )
        return 0;

    const numMatch = text.match(/(\d+)/);
    const num = numMatch ? parseInt(numMatch[1], 10) : 1;
    const isIndonesian = text.includes("lalu");

    if (
        text.includes("tahun") ||
        text.includes("year") ||
        text.match(/\b\d+\s*(?:y|thn|th)\b/)
    )
        return num * 31536000;
    if (
        text.includes("bulan") ||
        text.includes("month") ||
        text.match(/\b\d+\s*(?:mo|bln)\b/)
    )
        return num * 2592000;
    if (
        text.includes("minggu") ||
        text.includes("week") ||
        text.match(/\b\d+\s*w\b/)
    )
        return num * 604800;
    if (
        text.includes("hari") ||
        text.includes("day") ||
        (isIndonesian && text.match(/\b\d+\s*h\b/)) ||
        (!isIndonesian && text.match(/\b\d+\s*d\b/))
    )
        return num * 86400;
    if (
        text.includes("jam") ||
        text.includes("hour") ||
        (isIndonesian && text.match(/\b\d+\s*j\b/)) ||
        (!isIndonesian && text.match(/\b\d+\s*h\b/))
    )
        return num * 3600;
    if (
        text.includes("menit") ||
        text.includes("minute") ||
        text.includes("min") ||
        text.match(/\b\d+\s*m\b/)
    )
        return num * 60;
    if (
        text.includes("detik") ||
        text.includes("second") ||
        text.includes("sec") ||
        text.match(/\b\d+\s*s\b/)
    )
        return num;

    return 999999999;
};

// Sort streams helper: 1) LIVE status first, 2) Video streams/replays over static cards, 3) Recency timestamp (newest stream/replay first)
const sortStreamsByStatusAndRecency = (streamList) => {
    return [...streamList].sort((a, b) => {
        if (a.status === "LIVE" && b.status !== "LIVE") return -1;
        if (b.status === "LIVE" && a.status !== "LIVE") return 1;

        if (!a.is_officer_card && b.is_officer_card) return -1;
        if (a.is_officer_card && !b.is_officer_card) return 1;

        const timeA = parseRelativeTimeToSeconds(
            a.streamed_at || a.incident_code,
        );
        const timeB = parseRelativeTimeToSeconds(
            b.streamed_at || b.incident_code,
        );
        if (timeA !== timeB) {
            return timeA - timeB;
        }

        return 0;
    });
};

// Support 1K Subs (Streamers / Officers with < 1,000 subscribers)
const support1kStreams = computed(() => {
    const list = [
        ...allCatalogStreams.value.filter((s) => {
            const count = Number(s.officer?.subscriber_count);
            return !isNaN(count) && count > 0 && count < 1000;
        }),
    ];

    const seenOfficerIds = new Set(
        list.map((s) => s.officer?.id).filter(Boolean),
    );

    // Fallback: include offline officers with < 1,000 subs who do not have an active stream / VOD replay in catalog
    (offlineOfficers.value || []).forEach((o) => {
        if (o.id && !seenOfficerIds.has(o.id)) {
            const count = Number(o.subscriber_count);
            if (!isNaN(count) && count > 0 && count < 1000) {
                seenOfficerIds.add(o.id);
                list.push({
                    id: `sub1k-officer-${o.id}`,
                    video_id: `officer-${o.id}`,
                    title: `Support Officer ${o.officer_name}`,
                    thumbnail:
                        o.avatar_url ||
                        `https://api.dicebear.com/7.x/bottts/svg?seed=${o.id}`,
                    status: "OFFLINE",
                    incident_code: "10-7 Road to 1K",
                    streamed_at: "Target 1K Milestone",
                    description: `Dukung ${o.officer_name} mencapai 1.000 subscriber YouTube pertama!`,
                    viewers_count: 0,
                    is_officer_card: true,
                    officer: o,
                });
            }
        }
    });

    return sortStreamsByStatusAndRecency(list);
});

// Recent offline patrol video replays / VODs (Sorted newest first, filtered for valid IME RP/Police hashtags)
const recentReplayStreams = computed(() => {
    const liveIds = new Set(
        allActiveStreams.value.map((s) => String(s.video_id).trim()),
    );
    const list = (recentReplays.value || []).filter((r) => {
        if (!r || !r.video_id) return false;
        if (liveIds.has(String(r.video_id).trim())) return false;
        return hasRequiredImeHashtag(r);
    });
    return sortStreamsByStatusAndRecency(list);
});

// Department Catalog Streams (Live + Replay VODs)
const lspdCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(
        (s) => s.officer && s.officer.department === "LSPD",
    );
});

const bcsoCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(
        (s) => s.officer && s.officer.department === "BCSO",
    );
});

const saspCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(
        (s) => s.officer && s.officer.department === "SASP",
    );
});

const saprCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter(
        (s) =>
            s.officer &&
            (s.officer.department === "SAPR" ||
                s.officer.department === "PARK RANGER"),
    );
});

const specialOpsCatalogStreams = computed(() => {
    return allCatalogStreams.value.filter((s) => {
        const text =
            `${s.officer?.rank || ""} ${s.officer?.callsign || ""} ${s.title || ""}`.toLowerCase();
        return (
            text.includes("swat") ||
            text.includes("k9") ||
            text.includes("k-9") ||
            text.includes("srt") ||
            text.includes("air") ||
            text.includes("trooper") ||
            text.includes("investigation") ||
            text.includes("detective")
        );
    });
});

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
const scrollRow = (rowId, direction = "right") => {
    const el = document.getElementById(rowId);
    if (!el) return;
    const scrollAmount = direction === "left" ? -650 : 650;
    el.scrollBy({ left: scrollAmount, behavior: "smooth" });
};

// Helper to construct YouTube URL safely (Handles officer support cards)
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

const toggleGlobalDataSaver = () => {
    if (isDataSaverEnabled.value) {
        disableDataSaverAndPlayAll();
    } else {
        enableDataSaver();
    }
};

const toggleAudio = (videoId) => {
    composableToggleAudio(videoId, allActiveStreams.value);
};

const muteAll = () => {
    composableMuteAll(allActiveStreams.value);
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
const layoutGridClass = computed(() => {
    if (selectedLayout.value === "grid-1x2") {
        return "grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-2";
    }
    if (selectedLayout.value === "grid-1x3") {
        return "grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3";
    }
    if (selectedLayout.value === "grid-2x2") {
        return "grid grid-cols-1 md:grid-cols-2 gap-3.5";
    }
    if (selectedLayout.value === "grid-3x3") {
        return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5";
    }
    if (selectedLayout.value === "grid-4x4") {
        return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3";
    }
    if (selectedLayout.value === "focus") {
        return "";
    }

    // Auto mode
    const count = visibleStreams.value.length;
    if (count <= 1) return "grid grid-cols-1 gap-4";
    if (count === 2) return "grid grid-cols-1 md:grid-cols-2 gap-4";
    if (count <= 4) return "grid grid-cols-1 md:grid-cols-2 gap-3.5";
    if (count <= 6)
        return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5";
    return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5";
});

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
                department: selectedDepartment.value !== "ALL" ? selectedDepartment.value : "LSPD",
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
        (s) => String(s.video_id).trim() !== String(primaryFocusedStream.value.video_id).trim(),
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

// Officer Directory State & Filtering
const directorySearch = ref("");
const directoryDeptFilter = ref("ALL"); // 'ALL', 'LIVE_ONLY', 'LSPD', 'BCSO', 'SASP'
const directorySortBy = ref("status"); // 'status', 'subs_desc', 'subs_asc', 'name'

// All Registered Officers Combined (Deduplicated with real-time online status and subscriber data)
const allDirectoryOfficers = computed(() => {
    const list = [];
    const seenHandles = new Set();
    const seenIds = new Set();

    // 1. First add all currently live/active officers
    allActiveStreams.value.forEach((s) => {
        if (!s.officer) return;
        const key =
            s.officer.handle || s.officer.channel_id || s.officer.officer_name;
        if (!seenHandles.has(key)) {
            seenHandles.add(key);
            if (s.officer.id) seenIds.add(s.officer.id);
            list.push({
                id: s.officer.id || `live-${s.video_id}`,
                channel_id: s.officer.channel_id,
                handle: s.officer.handle || "",
                streamer_name: s.officer.streamer_name || "",
                officer_name: s.officer.officer_name || "",
                callsign: s.officer.callsign || "",
                department: s.officer.department || "LSPD",
                rank: s.officer.rank || "Officer",
                badge_number: s.officer.badge_number || "#000",
                patrol_zone: s.officer.patrol_zone || "",
                avatar_url: s.officer.avatar_url || null,
                subscriber_count: s.officer.subscriber_count || 0,
                is_online: true,
                live_stream: s,
            });
        }
    });

    // 2. Then add all offline officers from props
    offlineOfficers.value.forEach((o) => {
        const key = o.handle || o.channel_id || o.officer_name;
        if (!seenHandles.has(key) && (!o.id || !seenIds.has(o.id))) {
            seenHandles.add(key);
            list.push({
                id: o.id,
                channel_id: o.channel_id,
                handle: o.handle || "",
                streamer_name: o.streamer_name || "",
                officer_name: o.officer_name || "",
                callsign: o.callsign || "",
                department: o.department || "LSPD",
                rank: o.rank || "Officer",
                badge_number: o.badge_number || "#000",
                patrol_zone: o.patrol_zone || "",
                avatar_url: o.avatar_url || null,
                subscriber_count: o.subscriber_count || 0,
                is_online: false,
                live_stream: null,
            });
        }
    });

    // 3. Filter by department or online status
    let filtered = list;
    if (directoryDeptFilter.value === "LIVE_ONLY") {
        filtered = filtered.filter((o) => o.is_online);
    } else if (directoryDeptFilter.value !== "ALL") {
        filtered = filtered.filter(
            (o) => o.department === directoryDeptFilter.value,
        );
    }

    // 4. Filter by search term (name, callsign, handle, badge, streamer, rank, zone)
    if (directorySearch.value.trim()) {
        const q = directorySearch.value.toLowerCase().trim();
        filtered = filtered.filter(
            (o) =>
                (o.officer_name && o.officer_name.toLowerCase().includes(q)) ||
                (o.callsign && o.callsign.toLowerCase().includes(q)) ||
                (o.streamer_name &&
                    o.streamer_name.toLowerCase().includes(q)) ||
                (o.handle && o.handle.toLowerCase().includes(q)) ||
                (o.badge_number && o.badge_number.toLowerCase().includes(q)) ||
                (o.patrol_zone && o.patrol_zone.toLowerCase().includes(q)) ||
                (o.rank && o.rank.toLowerCase().includes(q)),
        );
    }

    // 5. Sorting
    return filtered.sort((a, b) => {
        if (directorySortBy.value === "status") {
            if (a.is_online && !b.is_online) return -1;
            if (!a.is_online && b.is_online) return 1;
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (directorySortBy.value === "subs_desc") {
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (directorySortBy.value === "subs_asc") {
            return (a.subscriber_count || 0) - (b.subscriber_count || 0);
        } else if (directorySortBy.value === "name") {
            return (a.officer_name || "").localeCompare(b.officer_name || "");
        }
        return 0;
    });
});

// 10-Codes and Radio Operational Protocols State & Data
const radioCodesActiveTab = ref("CODES"); // 'CODES' or 'TAC'
const radioCodesSearch = ref("");

const standardPolice10Codes = [
    {
        code: "10-4",
        title: "Message Received / Roger",
        meaning:
            "Pesan atau instruksi telah diterima dengan jelas dan dipahami sepenuhnya.",
        category: "Umum",
    },
    {
        code: "10-7",
        title: "Out of Service / Off Duty",
        meaning: "Petugas keluar dari dinas patroli atau unit dinonaktifkan.",
        category: "Status",
    },
    {
        code: "10-8",
        title: "In Service / On Duty",
        meaning:
            "Petugas aktif bertugas, siap menerima panggilan dispatch dan penugasan.",
        category: "Status",
    },
    {
        code: "10-20",
        title: "Location / Coordinates",
        meaning:
            "Permintaan atau konfirmasi posisi / lokasi spesifik unit saat ini.",
        category: "Navigasi",
    },
    {
        code: "10-23",
        title: "Arrived on Scene",
        meaning:
            "Unit telah tiba di lokasi kejadian / Tempat Kejadian Perkara (TKP).",
        category: "Taktis",
    },
    {
        code: "10-33",
        title: "Officer in Distress / Emergency",
        meaning:
            "Panggilan darurat kritis! Petugas berada di bawah ancaman dan butuh bantuan segera.",
        category: "Darurat",
    },
    {
        code: "10-50",
        title: "Motor Vehicle Accident",
        meaning:
            "Terjadi tabrakan atau kecelakaan lalu lintas kendaraan bermotor.",
        category: "Lantas",
    },
    {
        code: "10-70",
        title: "Foot Pursuit",
        meaning: "Pengejaran tersangka dengan berlari / berjalan kaki.",
        category: "Pengejaran",
    },
    {
        code: "10-80",
        title: "High Speed Pursuit",
        meaning:
            "Pengejaran kendaraan berkecepatan tinggi yang berpotensi membahayakan publik.",
        category: "Pengejaran",
    },
    {
        code: "10-90",
        title: "Armed Robbery in Progress",
        meaning:
            "Perampokan bersenjata sedang berlangsung (Toko, Bank, Perhiasan).",
        category: "Darurat",
    },
    {
        code: "10-99",
        title: "Situation Under Control / Code 4",
        meaning: "Situasi telah sepenuhnya terkendali, area aman kembali.",
        category: "Status",
    },
    {
        code: "Code 1",
        title: "Routine Response",
        meaning:
            "Respon rutin normal tanpa sirine dan tanpa strobo, patuhi lalu lintas umum.",
        category: "Respon",
    },
    {
        code: "Code 2",
        title: "Urgent Silent Run",
        meaning:
            "Respon mendesak dengan lampu strobo tanpa sirine untuk mendekati TKP diam-diam.",
        category: "Respon",
    },
    {
        code: "Code 3",
        title: "Emergency Full Lights & Sirens",
        meaning:
            "Respon darurat penuh prioritas tertinggi, sirine dan strobo aktif.",
        category: "Respon",
    },
    {
        code: "Code 4",
        title: "No Further Assistance Needed",
        meaning:
            "Situasi aman, tidak diperlukan penambahan unit tambahan ke lokasi.",
        category: "Respon",
    },
    {
        code: "Signal 100",
        title: "Radio Silence for High-Risk Event",
        meaning:
            "Seluruh unit dilarang berbicara di radio selain unit komando penanganan situasi.",
        category: "Protokol",
    },
];

const tacChannelGuides = [
    {
        code: "TAC 1",
        title: "Dispatch & General Patrol",
        badgeColor: "blue",
        scope: "Komunikasi lalu lintas patroli reguler, tilang harian, dan respon panggilan 911 standar.",
        protocol:
            "Digunakan oleh seluruh unit patroli LSPD, BCSO, dan SASP saat tidak berada dalam situasi khusus. Pertahankan transmisi singkat dan jelas.",
    },
    {
        code: "TAC 2",
        title: "High Speed Vehicle Pursuit (10-80)",
        badgeColor: "amber",
        scope: "Pengejaran kendaraan tersangka dan koordinasi formasi interception di jalan raya.",
        protocol:
            "Lead unit bertanggung jawab memberikan callout arah (heading, visual, nomor plat, kecepatan). Unit sekunder menyiapkan manuver PIT atau Spikestrip.",
    },
    {
        code: "TAC 3",
        title: "Major Robbery & Bank Heist (10-90)",
        badgeColor: "red",
        scope: "Penanganan perampokan toko bersenjata, Fleeca Bank, Paleto Bank, Pacific Standard.",
        protocol:
            "Hanya unit yang ditugaskan di perimeter dalam dan negosiator yang berkomunikasi. Unit lain menjaga perimeter luar dan jalur pelarian.",
    },
    {
        code: "TAC 4",
        title: "Special Weapons & SWAT Tactical Ops",
        badgeColor: "purple",
        scope: "Operasi penggerebekan senjata berat, drug lab raid, dan hostile hostage rescue.",
        protocol:
            "Di bawah komando langsung SWAT Commander / Tactical Supervisor. Disiplin radio penuh, gunakan formasi breaching standar.",
    },
    {
        code: "TAC 5",
        title: "Air Support & Inter-Agency Joint Command",
        badgeColor: "indigo",
        scope: "Koordinasi unit udara Air-1/Helikopter, Unit Maritim, dan komando gabungan lintas instansi.",
        protocol:
            "Memberikan visual bird-eye view kepada ground units. Koordinasi gabungan LSPD, BCSO, SASP, dan EMS.",
    },
];

const filteredPolice10Codes = computed(() => {
    if (!radioCodesSearch.value.trim()) return standardPolice10Codes;
    const q = radioCodesSearch.value.toLowerCase().trim();
    return standardPolice10Codes.filter(
        (c) =>
            c.code.toLowerCase().includes(q) ||
            c.title.toLowerCase().includes(q) ||
            c.meaning.toLowerCase().includes(q) ||
            c.category.toLowerCase().includes(q),
    );
});

// Unified Right Slide-Over Drawer State ('QUICK_ADD', 'FEEDBACK', 'ABOUT', 'DIRECTORY', 'RADIO_CODES', 'ROSTER', or null)
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
const rosterOfficers = ref([]);
const isRosterLoading = ref(false);
const rosterSearch = ref("");
// Visitor Feedback & Channel Request State & Methods (Option 1 Discord Webhook)
const isSubmittingFeedback = ref(false);
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

const submitFeedbackForm = async () => {
    isSubmittingFeedback.value = true;
    try {
        const res = await fetch("/api/v1/feedback", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            body: JSON.stringify(feedbackForm.value),
        });
        const data = await res.json();
        if (res.ok) {
            feedbackSuccessToast.value =
                data.message || "Laporan berhasil dikirim ke Command Center!";
            setTimeout(() => {
                closeRightDrawer();
                feedbackSuccessToast.value = "";
            }, 3000);
        } else {
            alert(data.message || "Gagal mengirim masukan. Silakan coba lagi.");
        }
    } catch (e) {
        alert("Terjadi kesalahan jaringan saat mengirim laporan.");
    } finally {
        isSubmittingFeedback.value = false;
    }
};
</script>

<template>
    <Head :title="dynamicPageTitle" />

    <div
        class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased"
    >
        <!-- Dedicated Multiview Master Header Component (Hidden when Theater Mode is active) -->
        <MultiviewHeader
            v-if="!isTheaterMode"
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

        <!-- Main Content Area -->
        <main
            :class="
                isTheaterMode
                    ? 'flex-1 w-full px-2 sm:px-4 pt-3 sm:pt-4 pb-6 relative transition-all duration-300'
                    : 'flex-1 w-full max-w-screen-2xl mx-auto p-2 sm:p-4 pb-6 relative transition-all duration-300'
            "
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
                :all-catalog-streams="allCatalogStreams"
                :trending-streams="trendingStreams"
                :recent-replay-streams="recentReplayStreams"
                :active-personal-streams="activePersonalStreams"
                :lspd-catalog-streams="lspdCatalogStreams"
                :sasp-catalog-streams="saspCatalogStreams"
                :bcso-catalog-streams="bcsoCatalogStreams"
                :sapr-catalog-streams="saprCatalogStreams"
                :special-ops-catalog-streams="specialOpsCatalogStreams"
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
        <!-- UNIFIED RIGHT SLIDE-OVER SIDEBAR / DRAWER -->
        <TacticalDrawers
            :active-right-drawer="activeRightDrawer"
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
            :feedback-form="feedbackForm"
            :is-submitting-feedback="isSubmittingFeedback"
            :feedback-success-toast="feedbackSuccessToast"
            v-model:directory-search="directorySearch"
            v-model:directory-sort-by="directorySortBy"
            v-model:directory-dept-filter="directoryDeptFilter"
            :offline-officers="offlineOfficers"
            :streams="streams"
            :all-directory-officers="allDirectoryOfficers"
            v-model:radio-codes-active-tab="radioCodesActiveTab"
            v-model:radio-codes-search="radioCodesSearch"
            :filtered-police-10-codes="filteredPolice10Codes"
            :tac-channel-guides="tacChannelGuides"
            :personal-stream-ids="personalVideoIds"
            :get-tac-unit-count="getTacUnitCount"
            @close="closeRightDrawer"
            @search-live-streams="handleSearchLiveStreams"
            @add-live-stream-to-personal="handleAddLiveStreamToPersonal"
            @quick-add-stream="handleQuickAddStream"
            @clear-all-personal="clearAllPersonalStreams"
            @remove-personal-stream="removePersonalStream"
            @toggle-personal-stream="togglePersonalStream"
            @submit-feedback="submitFeedbackForm"
            @open-subscribe-popup="openSubscribePopup"
            @set-focus-stream="setFocusStream"
            @set-department="(dept) => (selectedDepartment = dept)"
        />

        <!-- Global Expiring TAC Channel Alert Prompt (When viewing other tabs) -->
        <div
            v-if="expiringTacChannel"
            class="fixed bottom-4 left-4 z-50 bg-slate-900 border border-amber-800 rounded-lg p-3 shadow-lg flex items-center space-x-3 text-xs animate-in slide-in-from-bottom duration-300 max-w-lg"
        >
            <div
                class="w-8 h-8 rounded bg-slate-950 border border-slate-800 flex items-center justify-center shrink-0"
            >
                <img :src="iconRadio" class="w-4 h-4 brightness-0 invert opacity-90" alt="" />
            </div>
            <div class="flex-1 min-w-0">
                <div
                    class="font-bold text-amber-300 font-mono text-[11px] truncate"
                >
                    {{ expiringTacChannel.name }} ({{
                        expiringTacChannel.video_ids.length
                    }}
                    Units) tersisa
                    {{
                        formatRemainingTime(
                            expiringTacChannel.remaining_seconds,
                        )
                    }}
                </div>
                <div class="text-[10px] text-slate-400 truncate">
                    Apakah situasi masih berlangsung?
                </div>
            </div>
            <div class="flex items-center space-x-1.5 shrink-0">
                <button
                    @click="extendTacTimer(expiringTacChannel.code, 20)"
                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-950 font-bold text-[10px] rounded-md font-mono transition-colors shadow-none"
                >
                    +20m
                </button>
                <button
                    @click="disbandTacChannel(expiringTacChannel.code)"
                    class="px-2.5 py-1 bg-rose-950/60 hover:bg-rose-900/70 text-rose-400 hover:text-rose-300 text-[10px] rounded-md border border-rose-900/80 font-mono transition-colors shadow-none"
                >
                    Bubarkan
                </button>
                <button
                    @click="selectedDepartment = expiringTacChannel.code"
                    class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 hover:border-slate-600 text-[10px] rounded-md font-mono transition-colors shadow-none"
                >
                    Buka
                </button>
            </div>
        </div>

        <!-- Floating Tactical Action Toast -->
        <div
            v-if="tacticalToast"
            class="fixed bottom-16 md:bottom-16 right-4 z-[60] bg-slate-900 border border-amber-800 rounded-md px-4 py-2.5 flex items-center space-x-2.5 text-xs font-mono text-amber-300 animate-in slide-in-from-bottom duration-200 pointer-events-auto"
        >
            <img
                :src="iconRadio"
                class="w-4 h-4 brightness-0 invert opacity-90 shrink-0"
                alt=""
            />
            <span>{{ tacticalToast.message }}</span>
        </div>


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
