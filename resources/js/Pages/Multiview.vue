<script setup>
import { Head, Link } from "@inertiajs/vue3";
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    reactive,
    ref,
    watch,
} from "vue";
import { toast as sonnerToast } from "vue-sonner";

import MultiviewStreamPicker from "@/Components/MultiviewStreamPicker.vue";
import { DeptBadge, LiveBadge } from "@/Components/tactical";
import MultiviewSoloStrip from "@/Components/MultiviewSoloStrip.vue";
import TacticalMobileBottomNav from "@/Components/TacticalMobileBottomNav.vue";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Toaster } from "@/Components/ui/sonner";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { useAnalytics } from "@/Composables/useAnalytics";
import {
    LayoutGrid,
    Link2,
    Maximize,
    Menu,
    MessageSquare,
    PanelRight,
    Plus,
    Square,
    Trash2,
    Volume2,
    VolumeX,
    X,
} from "@lucide/vue";

const props = defineProps({
    initialStreams: { type: Array, required: true },
    initialOfflineOfficers: { type: Array, required: true },
    initialTacChannels: { type: Array, default: () => [] },
    initialReplays: { type: Array, default: () => [] },
    deptStats: { type: Object, default: () => ({}) },
    lastSyncedAt: { type: String, default: "" },
});

const { trackPageView, trackSelectStream } = useAnalytics();

const STORAGE_KEY = "ime_multiview_selection";
const LAYOUT_KEY = "ime_multiview_layout";
const POLL_MS = 60_000;
const GAP = 6;
const RATIO = 16 / 9;
const THUMB_W = 260;
const TILE_BAR = 32;
const VIDEO_ID_RE = /^[\w-]{11}$/;

// ---------------------------------------------------------------------------
// Stream data
// ---------------------------------------------------------------------------
const streams = ref(props.initialStreams);
const offlineOfficers = ref(props.initialOfflineOfficers);

const streamCache = reactive({});
const cacheStreams = (list) =>
    list.forEach((s) => (streamCache[s.video_id] = s));
cacheStreams(streams.value);
// Replays opened from the dashboard (?v=) keep their title and officer label.
cacheStreams(props.initialReplays || []);
const replayIds = new Set((props.initialReplays || []).map((r) => r.video_id));

const liveIds = computed(() => new Set(streams.value.map((s) => s.video_id)));
const infoOf = (id) => streamCache[id] || null;

const refreshStreams = async () => {
    if (document.hidden) return;
    try {
        const res = await fetch("/api/v1/streams", {
            headers: { Accept: "application/json" },
        });
        if (!res.ok) return;
        const json = await res.json();
        if (Array.isArray(json.data)) {
            streams.value = json.data;
            cacheStreams(json.data);
        }
        if (Array.isArray(json.offline_officers))
            offlineOfficers.value = json.offline_officers;
    } catch {
        // Network hiccup: keep the current list until the next poll.
    }
};

// ---------------------------------------------------------------------------
// Viewport
// ---------------------------------------------------------------------------
const viewport = reactive({ w: 1280, h: 800 });
const isFullscreen = ref(false);
const canFullscreen = ref(false);

const updateViewport = () => {
    viewport.w = window.innerWidth;
    viewport.h = window.innerHeight;
};

const isDesktop = computed(() => viewport.w >= 1024);
const isNarrow = computed(
    () => viewport.w < 768 && viewport.h > viewport.w && !isFullscreen.value,
);
const isCompactLandscape = computed(
    () => viewport.h < 500 && viewport.w > viewport.h,
);
// The compact toolbar stays visible in every orientation and in fullscreen, so
// no controls ever have to float on top of a video.
const showChrome = computed(() => true);

const maxTiles = computed(() => {
    if (viewport.w < 768) return 4;
    if (viewport.w < 1280) return 6;
    if (viewport.w < 1536) return 9;
    return 12;
});

// ---------------------------------------------------------------------------
// Selection, focus & audio
// ---------------------------------------------------------------------------
const selectedIds = ref([]);
const focusId = ref(null);
const audioId = ref(null);
const layoutMode = ref("grid"); // 'grid' | 'focus' | 'solo'

const readInitialSelection = () => {
    const params = new URLSearchParams(window.location.search);
    const fromUrl = (params.get("v") || "")
        .split(",")
        .map((s) => s.trim())
        .filter((s) => VIDEO_ID_RE.test(s));
    if (fromUrl.length) return fromUrl;

    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
        const stillLive = saved.filter((id) => liveIds.value.has(id));
        if (stillLive.length) return stillLive;
    } catch {
        // Corrupt or blocked storage falls through to the default pick.
    }

    return [...streams.value]
        .sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0))
        .slice(0, viewport.w < 768 ? 2 : 4)
        .map((s) => s.video_id);
};

const isFull = computed(() => selectedIds.value.length >= maxTiles.value);

const addStream = (id) => {
    if (selectedIds.value.includes(id) || isFull.value) return;
    selectedIds.value = [...selectedIds.value, id];
    if (!focusId.value) focusId.value = id;
    const info = infoOf(id);
    if (info) trackSelectStream(info);
};

const removeStream = (id) => {
    selectedIds.value = selectedIds.value.filter((v) => v !== id);
    delete iframes[id];
    if (audioId.value === id) audioId.value = null;
    if (focusId.value === id) focusId.value = selectedIds.value[0] || null;
};

const toggleStream = (stream) => {
    if (layoutMode.value === "solo") return switchSolo(stream.video_id);
    if (selectedIds.value.includes(stream.video_id))
        removeStream(stream.video_id);
    else addStream(stream.video_id);
};

const clearAll = () => {
    selectedIds.value = [];
    focusId.value = null;
    audioId.value = null;
    Object.keys(iframes).forEach((k) => delete iframes[k]);
};

const setFocus = (id) => {
    focusId.value = id;
    layoutMode.value = "focus";
};

const toggleAudio = (id) => {
    audioId.value = audioId.value === id ? null : id;
};

// Solo: watch one officer and jump to another POV in one click. Only one player
// runs, so switching swaps the video and moves the sound along with it.
const soloStrip = ref(null);

const siteNav = [
    { href: "/", label: "Beranda" },
    { href: "/multiview", label: "Multiview" },
    { href: "/clipper", label: "Clip Studio" },
    { href: "/action-clips", label: "TacClips" },
    { href: "/officers", label: "Direktori Perwira" },
];

const switchSolo = (id, { withAudio = true } = {}) => {
    if (!id) return;
    Object.keys(iframes).forEach((k) => k !== id && delete iframes[k]);
    selectedIds.value = [id];
    focusId.value = id;
    if (withAudio) audioId.value = id;
    const info = infoOf(id);
    if (info) trackSelectStream(info);
};

const enterSolo = (options) => {
    layoutMode.value = "solo";
    switchSolo(
        audioId.value || focusId.value || selectedIds.value[0] || streams.value[0]?.video_id,
        options,
    );
};

const setLayout = (mode) => {
    if (mode === "solo") enterSolo();
    else layoutMode.value = mode;
};

watch(
    selectedIds,
    (ids) => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
        } catch {
            // Private mode: selection still lives in the URL.
        }
        const url = new URL(window.location.href);
        if (ids.length) url.searchParams.set("v", ids.join(","));
        else url.searchParams.delete("v");
        window.history.replaceState(window.history.state, "", url);
    },
    { deep: true },
);

watch(layoutMode, (mode) => {
    try {
        localStorage.setItem(LAYOUT_KEY, mode);
    } catch {
        // Ignore storage failures.
    }
});

// ---------------------------------------------------------------------------
// Player control (YouTube IFrame postMessage API)
// ---------------------------------------------------------------------------
const iframes = {};
const origin = ref("");

const embedSrc = (id) =>
    `https://www.youtube.com/embed/${id}?autoplay=1&mute=1&playsinline=1&enablejsapi=1&rel=0&modestbranding=1&origin=${encodeURIComponent(origin.value)}`;

// A player's next status reports may still describe the state before our
// command; ignore them briefly so they don't undo our own change.
const SYNC_GRACE_MS = 1500;
const lastCommandAt = {};

const sendCommand = (id, func, args = []) => {
    const win = iframes[id]?.contentWindow;
    if (!win) return;
    lastCommandAt[id] = Date.now();
    win.postMessage(JSON.stringify({ event: "command", func, args }), "*");
};

// Ask the player to stream its state back (infoDelivery messages).
const subscribePlayer = (id) => {
    iframes[id]?.contentWindow?.postMessage(
        JSON.stringify({ event: "listening", id, channel: "widget" }),
        "*",
    );
};

// Keep the single-audio rule in sync when someone uses YouTube's own mute control.
const onPlayerMessage = (e) => {
    if (
        !/^https:\/\/www\.youtube(-nocookie)?\.com$/.test(e.origin) ||
        typeof e.data !== "string"
    )
        return;
    let msg;
    try {
        msg = JSON.parse(e.data);
    } catch {
        return;
    }
    if (msg.event !== "infoDelivery" || typeof msg.info?.muted !== "boolean") return;

    const id = Object.keys(iframes).find((k) => iframes[k]?.contentWindow === e.source);
    if (!id || Date.now() - (lastCommandAt[id] || 0) < SYNC_GRACE_MS) return;

    if (!msg.info.muted && audioId.value !== id) audioId.value = id;
    else if (msg.info.muted && audioId.value === id) audioId.value = null;
};

const applyAudio = (id) => {
    if (id === audioId.value) {
        sendCommand(id, "unMute");
        sendCommand(id, "playVideo");
    } else {
        sendCommand(id, "mute");
    }
};

const onIframeLoad = (id) => {
    setTimeout(() => {
        subscribePlayer(id);
        applyAudio(id);
    }, 800);
    setTimeout(() => applyAudio(id), 2500);
};

watch(audioId, (next, prev) => {
    if (prev) sendCommand(prev, "mute");
    if (next) applyAudio(next);
});

const muteAll = () => {
    audioId.value = null;
    selectedIds.value.forEach((id) => sendCommand(id, "mute"));
};

// ---------------------------------------------------------------------------
// Layout engine
// ---------------------------------------------------------------------------
const stageBox = ref(null);
const pageRoot = ref(null);
const stageSize = reactive({ w: 0, h: 0 });

// Tiles narrower than THUMB_W become tap-to-focus previews; wider tiles get a
// TILE_BAR-tall control bar under the 16:9 video, so YouTube's own controls stay uncovered.
const hOf = (w) => w / RATIO + (w >= THUMB_W ? TILE_BAR : 0);
const wOf = (h) => {
    const withBar = (h - TILE_BAR) * RATIO;
    return withBar >= THUMB_W ? withBar : Math.min(h * RATIO, THUMB_W - 1);
};

const placeRow = (rects, indices, y, tw, th, W) => {
    const rowW = indices.length * tw + (indices.length - 1) * GAP;
    let x = (W - rowW) / 2;
    indices.forEach((i) => {
        rects[i] = { x, y, w: tw, h: th };
        x += tw + GAP;
    });
};

const gridRects = (order, W, cols, tw, th, y0, rects) => {
    for (let r = 0; r * cols < order.length; r++) {
        placeRow(
            rects,
            order.slice(r * cols, r * cols + cols),
            y0 + r * (th + GAP),
            tw,
            th,
            W,
        );
    }
};

const computeLayout = (n, W, H, mode, fi, narrow) => {
    const rects = new Array(n);
    if (!n || !W) return { rects, height: 0 };

    const all = [...Array(n).keys()];
    const others = all.filter((i) => i !== fi);
    const useFocus = mode === "focus" && n > 1 && fi >= 0;

    if (narrow) {
        if (useFocus) {
            const mainH = hOf(W);
            rects[fi] = { x: 0, y: 0, w: W, h: mainH };
            const tw = (W - GAP) / 2;
            const th = hOf(tw);
            gridRects(others, W, 2, tw, th, mainH + GAP, rects);
            const rows = Math.ceil(others.length / 2);
            return {
                rects,
                height: mainH + GAP + rows * th + (rows - 1) * GAP,
            };
        }
        const cols = n === 1 ? 1 : 2;
        const tw = (W - GAP * (cols - 1)) / cols;
        const th = hOf(tw);
        gridRects(all, W, cols, tw, th, 0, rects);
        const rows = Math.ceil(n / cols);
        return { rects, height: rows * th + (rows - 1) * GAP };
    }

    if (useFocus) {
        const sc = others.length > 4 ? 2 : 1;
        const sideW = Math.min(
            Math.max(W * (sc === 1 ? 0.24 : 0.34), 180),
            480,
        );
        const mainW = Math.min(W - sideW - GAP, wOf(H));
        const mainH = hOf(mainW);

        let tw = (sideW - GAP * (sc - 1)) / sc;
        let th = hOf(tw);
        const rows = Math.ceil(others.length / sc);
        let colH = rows * th + (rows - 1) * GAP;
        if (colH > H) {
            tw = wOf((H - (rows - 1) * GAP) / rows);
            th = hOf(tw);
            colH = H;
        }
        const sideRealW = sc * tw + (sc - 1) * GAP;
        const x0 = (W - (mainW + GAP + sideRealW)) / 2;

        rects[fi] = { x: x0, y: (H - mainH) / 2, w: mainW, h: mainH };
        const sx = x0 + mainW + GAP;
        const sy = (H - colH) / 2;
        others.forEach((i, k) => {
            rects[i] = {
                x: sx + (k % sc) * (tw + GAP),
                y: sy + Math.floor(k / sc) * (th + GAP),
                w: tw,
                h: th,
            };
        });
        return { rects, height: H };
    }

    let best = { cols: 1, w: 0 };
    for (let cols = 1; cols <= n; cols++) {
        const rows = Math.ceil(n / cols);
        const w = Math.min(
            (W - GAP * (cols - 1)) / cols,
            wOf((H - GAP * (rows - 1)) / rows),
        );
        if (w > best.w) best = { cols, w };
    }
    const th = hOf(best.w);
    const rows = Math.ceil(n / best.cols);
    const blockH = rows * th + (rows - 1) * GAP;
    gridRects(all, W, best.cols, best.w, th, (H - blockH) / 2, rects);
    return { rects, height: H };
};

const layout = computed(() =>
    computeLayout(
        selectedIds.value.length,
        stageSize.w,
        stageSize.h,
        layoutMode.value,
        selectedIds.value.indexOf(focusId.value),
        isNarrow.value,
    ),
);

const tileStyle = (i) => {
    const r = layout.value.rects[i];
    if (!r) return { display: "none" };
    return {
        left: `${r.x}px`,
        top: `${r.y}px`,
        width: `${r.w}px`,
        height: `${r.h}px`,
    };
};

const isThumb = (i) => {
    const r = layout.value.rects[i];
    return !!r && r.w < THUMB_W;
};

let resizeObserver = null;

// ---------------------------------------------------------------------------
// Panels: desktop sidebar, mobile sheet, chat
// ---------------------------------------------------------------------------
const sidebarOpen = ref(true);
const sheetOpen = ref(false);
const panelTab = ref("pov"); // 'pov' | 'chat'

const openPicker = (tab = "pov") => {
    panelTab.value = tab;
    if (isDesktop.value && showChrome.value) sidebarOpen.value = true;
    else sheetOpen.value = true;
};

const toggleSidebar = () => {
    if (isDesktop.value && showChrome.value)
        sidebarOpen.value = !sidebarOpen.value;
    else sheetOpen.value = !sheetOpen.value;
};

const chatTargetId = computed(
    () => audioId.value || focusId.value || selectedIds.value[0] || null,
);
const chatTarget = computed(() =>
    chatTargetId.value ? infoOf(chatTargetId.value) : null,
);
const chatSrc = computed(() => {
    if (!chatTargetId.value) return "";
    return (
        chatTarget.value?.live_chat_url ||
        `https://www.youtube.com/live_chat?v=${chatTargetId.value}&embed_domain=${window.location.hostname}&dark_theme=1`
    );
});

// ---------------------------------------------------------------------------
// Misc actions
// ---------------------------------------------------------------------------
const stageWrap = ref(null);

const showToast = (msg) => {
    sonnerToast(msg);
};

const shareLink = async () => {
    const url = window.location.href;
    try {
        if (navigator.share && !isDesktop.value) {
            await navigator.share({ title: "IME RP Multiview", url });
            return;
        }
        await navigator.clipboard.writeText(url);
        showToast("Link multiview disalin");
    } catch {
        // User cancelled the share sheet.
    }
};

const toggleFullscreen = async () => {
    try {
        if (document.fullscreenElement) await document.exitFullscreen();
        else await pageRoot.value?.requestFullscreen();
    } catch {
        showToast("Fullscreen tidak didukung di perangkat ini");
    }
};

const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

const onKeydown = (e) => {
    if (e.target.closest?.("input, textarea, [contenteditable]")) return;
    if (e.key >= "1" && e.key <= "9") {
        const n = Number(e.key) - 1;
        if (layoutMode.value === "solo") {
            switchSolo(soloStrip.value?.items?.[n]?.video_id);
            return;
        }
        const id = selectedIds.value[n];
        if (id) toggleAudio(id);
    } else if (e.key === "m") {
        muteAll();
    } else if (e.key === "g") {
        const order = ["grid", "focus", "solo"];
        setLayout(order[(order.indexOf(layoutMode.value) + 1) % order.length]);
    } else if (e.key === "Escape") {
        sheetOpen.value = false;
    }
};

const deptOf = (info) => {
    const d = (info?.officer?.department || "").toUpperCase();
    return d === "PARK RANGER" ? "SAPR" : d;
};

const totalLive = computed(() => streams.value.length);

let pollTimer = null;

onMounted(() => {
    origin.value = window.location.origin;
    canFullscreen.value = !!document.fullscreenEnabled;
    updateViewport();

    try {
        const savedLayout = localStorage.getItem(LAYOUT_KEY);
        layoutMode.value =
            ["grid", "focus", "solo"].includes(savedLayout)
                ? savedLayout
                : isNarrow.value
                  ? "focus"
                  : "grid";
    } catch {
        layoutMode.value = isNarrow.value ? "focus" : "grid";
    }

    selectedIds.value = readInitialSelection().slice(0, maxTiles.value);
    focusId.value = selectedIds.value[0] || null;
    // No sound on page load: browsers block unmuted autoplay without a click.
    if (layoutMode.value === "solo") enterSolo({ withAudio: false });

    resizeObserver = new ResizeObserver(([entry]) => {
        stageSize.w = entry.contentRect.width;
        stageSize.h = entry.contentRect.height;
    });
    nextTick(() => stageBox.value && resizeObserver.observe(stageBox.value));

    window.addEventListener("resize", updateViewport);
    window.addEventListener("keydown", onKeydown);
    window.addEventListener("message", onPlayerMessage);
    document.addEventListener("fullscreenchange", onFullscreenChange);
    pollTimer = setInterval(refreshStreams, POLL_MS);

    trackPageView(`Multiview (${selectedIds.value.length} POV)`);
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    window.removeEventListener("resize", updateViewport);
    window.removeEventListener("keydown", onKeydown);
    window.removeEventListener("message", onPlayerMessage);
    document.removeEventListener("fullscreenchange", onFullscreenChange);
    clearInterval(pollTimer);
});
</script>

<template>
    <Head title="Multiview — Pantau Banyak POV Sekaligus" />

    <TooltipProvider :delay-duration="200">
        <div
            ref="pageRoot"
            class="bg-background text-foreground font-sans antialiased flex flex-col"
            :class="isNarrow ? 'min-h-dvh pb-20' : 'h-dvh overflow-hidden'"
        >
            <!-- Toolbar -->
            <div
                v-if="showChrome"
                class="flex items-center gap-2 px-2 sm:px-4 py-1.5 border-b border-border bg-background/80"
            >
                <div class="flex items-center gap-2 min-w-0 mr-auto">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="size-8 shrink-0" aria-label="Menu situs">
                                <Menu class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="w-48">
                            <DropdownMenuItem v-for="nav in siteNav" :key="nav.href" as-child>
                                <Link :href="nav.href">{{ nav.label }}</Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <h1
                        class="font-heading text-base sm:text-lg font-bold tracking-wide leading-none"
                    >
                        MULTIVIEW
                    </h1>
                    <LiveBadge :label="`${totalLive} LIVE`" />
                    <span class="hidden sm:inline text-xs text-muted-foreground">
                        {{ selectedIds.length }}/{{ maxTiles }} POV
                    </span>
                </div>

                <div class="flex items-center gap-1 sm:gap-1.5">
                    <!-- Layout switch -->
                    <div
                        class="flex p-0.5 rounded-lg bg-card border border-border shrink-0 gap-0.5"
                    >
                        <Button
                            type="button"
                            :variant="
                                layoutMode === 'grid' ? 'default' : 'ghost'
                            "
                            size="sm"
                            class="h-8 px-2 text-xs font-bold gap-1"
                            title="Grid (G)"
                            @click="setLayout('grid')"
                        >
                            <LayoutGrid class="size-4" />
                            <span class="hidden md:inline">Grid</span>
                        </Button>
                        <Button
                            type="button"
                            :variant="
                                layoutMode === 'focus' ? 'default' : 'ghost'
                            "
                            size="sm"
                            class="h-8 px-2 text-xs font-bold gap-1"
                            title="Fokus (G)"
                            @click="setLayout('focus')"
                        >
                            <PanelRight class="size-4" />
                            <span class="hidden md:inline">Fokus</span>
                        </Button>
                        <Button
                            type="button"
                            :variant="layoutMode === 'solo' ? 'default' : 'ghost'"
                            size="sm"
                            class="h-8 px-2 text-xs font-bold gap-1"
                            title="Solo: satu POV + ganti cepat (G)"
                            @click="setLayout('solo')"
                        >
                            <Square class="size-4" />
                            <span class="hidden md:inline">Solo</span>
                        </Button>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        class="h-9 px-2.5 sm:px-3 text-xs font-bold gap-1.5"
                        @click="openPicker('pov')"
                    >
                        <Plus class="size-4" />
                        <span class="hidden sm:inline">Tambah POV</span>
                    </Button>

                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-9 max-sm:hidden"
                                aria-label="Live chat"
                                @click="openPicker('chat')"
                            >
                                <MessageSquare class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Live chat</TooltipContent>
                    </Tooltip>

                    <Tooltip v-if="audioId">
                        <TooltipTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-9"
                                aria-label="Bisukan semua (M)"
                                @click="muteAll"
                            >
                                <VolumeX class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Bisukan semua (M)</TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-9"
                                aria-label="Bagikan link multiview"
                                :disabled="!selectedIds.length"
                                @click="shareLink"
                            >
                                <Link2 class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Bagikan link multiview</TooltipContent>
                    </Tooltip>

                    <Tooltip v-if="canFullscreen">
                        <TooltipTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-9"
                                aria-label="Layar penuh"
                                @click="toggleFullscreen"
                            >
                                <Maximize class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Layar penuh</TooltipContent>
                    </Tooltip>

                    <Tooltip v-if="selectedIds.length">
                        <TooltipTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-9 max-sm:hidden"
                                aria-label="Kosongkan semua"
                                @click="clearAll"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Kosongkan semua</TooltipContent>
                    </Tooltip>

                    <Tooltip v-if="isDesktop">
                        <TooltipTrigger as-child>
                            <Button
                                type="button"
                                :variant="sidebarOpen ? 'default' : 'outline'"
                                size="icon"
                                class="size-9"
                                aria-label="Tampilkan / sembunyikan panel"
                                @click="toggleSidebar"
                            >
                                <PanelRight class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            Tampilkan / sembunyikan panel
                        </TooltipContent>
                    </Tooltip>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 min-h-0 flex">
                <!-- Stage -->
                <section
                    ref="stageWrap"
                    class="relative flex flex-1 min-w-0 flex-col bg-background"
                    :class="
                        isCompactLandscape || isFullscreen
                            ? 'p-0.5'
                            : 'p-1.5 sm:p-2'
                    "
                >
                    <div
                        ref="stageBox"
                        class="relative w-full"
                        :class="isNarrow ? '' : 'min-h-0 flex-1'"
                        :style="
                            isNarrow ? { height: `${layout.height}px` } : {}
                        "
                    >
                        <div
                            v-for="(id, i) in selectedIds"
                            :key="id"
                            class="tile absolute group"
                            :style="tileStyle(i)"
                        >
                            <div
                                class="flex size-full flex-col overflow-hidden rounded-md bg-card ring-2 transition-colors"
                                :class="audioId === id ? 'ring-primary' : 'ring-transparent'"
                            >
                                <!-- The video area belongs to YouTube: nothing of ours covers a full tile. -->
                                <div class="relative min-h-0 flex-1 bg-background">
                                    <iframe
                                        :ref="(el) => el && (iframes[id] = el)"
                                        :src="embedSrc(id)"
                                        :title="infoOf(id)?.title || 'YouTube POV'"
                                        class="absolute inset-0 size-full"
                                        :class="isThumb(i) ? 'pointer-events-none' : ''"
                                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                        allowfullscreen
                                        @load="onIframeLoad(id)"
                                    />

                                    <!-- Thumbnail: too small for YouTube's controls, so it is a preview you tap to promote. -->
                                    <template v-if="isThumb(i)">
                                        <button
                                            type="button"
                                            class="absolute inset-0 flex size-full items-end bg-linear-to-t from-background/80 via-transparent to-transparent p-1.5 text-left"
                                            :aria-label="$t('multiview_tile.watch_as_main', { name: infoOf(id)?.officer?.officer_name || $t('multiview_tile.pov_fallback') })"
                                            @click="setFocus(id)"
                                        >
                                            <span class="flex min-w-0 items-center gap-1 text-xs font-bold text-foreground">
                                                <span class="flex size-4 shrink-0 items-center justify-center rounded-sm bg-background/70 font-mono">{{ i + 1 }}</span>
                                                <span class="truncate">{{ infoOf(id)?.officer?.officer_name || infoOf(id)?.title || id }}</span>
                                            </span>
                                        </button>
                                        <div class="absolute right-1 top-1 flex gap-1">
                                            <button
                                                type="button"
                                                class="tile-btn"
                                                :class="audioId === id ? 'bg-primary!' : ''"
                                                :aria-label="audioId === id ? $t('multiview_tile.mute') : $t('multiview_tile.unmute')"
                                                @click="toggleAudio(id)"
                                            >
                                                <Volume2 v-if="audioId === id" class="size-3.5" />
                                                <VolumeX v-else class="size-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="tile-btn hover:bg-destructive!"
                                                :aria-label="$t('multiview_tile.close_pov')"
                                                @click="removeStream(id)"
                                            >
                                                <X class="size-3.5" />
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <!-- Full tile: our controls live in their own bar above the video, next to the toolbar. -->
                                <div
                                    v-if="!isThumb(i)"
                                    class="order-first flex shrink-0 items-center gap-1.5 border-b border-border px-1.5"
                                    :style="{ height: `${TILE_BAR}px` }"
                                >
                                    <span class="flex size-5 shrink-0 items-center justify-center rounded-sm bg-muted font-mono text-xs font-bold text-muted-foreground">{{ i + 1 }}</span>
                                    <DeptBadge v-if="deptOf(infoOf(id))" :dept="deptOf(infoOf(id))" class="shrink-0" />
                                    <span class="min-w-0 truncate text-xs font-semibold text-foreground">
                                        {{ infoOf(id)?.officer?.officer_name || infoOf(id)?.title || id }}
                                    </span>
                                    <span class="hidden shrink-0 font-mono text-xs text-muted-foreground sm:inline">{{ infoOf(id)?.officer?.callsign }}</span>
                                    <Badge v-if="!liveIds.has(id)" variant="secondary" class="shrink-0">
                                        {{ replayIds.has(id) ? $t('multiview_tile.replay') : $t('multiview_tile.offline') }}
                                    </Badge>

                                    <div class="ml-auto flex shrink-0 items-center gap-0.5">
                                        <Button
                                            size="icon-xs"
                                            :variant="audioId === id ? 'default' : 'ghost'"
                                            :aria-label="audioId === id ? $t('multiview_tile.mute') : $t('multiview_tile.unmute')"
                                            :aria-pressed="audioId === id"
                                            @click="toggleAudio(id)"
                                        >
                                            <Volume2 v-if="audioId === id" />
                                            <VolumeX v-else />
                                        </Button>
                                        <Button
                                            v-if="layoutMode !== 'focus' || focusId !== id"
                                            size="icon-xs"
                                            variant="ghost"
                                            :aria-label="$t('multiview_tile.make_main')"
                                            @click="setFocus(id)"
                                        >
                                            <Maximize />
                                        </Button>
                                        <Button
                                            size="icon-xs"
                                            variant="ghost"
                                            class="hover:bg-destructive hover:text-foreground"
                                            :aria-label="$t('multiview_tile.close_pov')"
                                            @click="removeStream(id)"
                                        >
                                            <X />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <MultiviewSoloStrip
                        v-if="layoutMode === 'solo' && streams.length"
                        ref="soloStrip"
                        :streams="streams"
                        :current-id="selectedIds[0] || null"
                        :tac-channels="initialTacChannels"
                        class="-mx-1.5 -mb-1.5 mt-1.5 sm:-mx-2 sm:-mb-2 sm:mt-2"
                        @select="switchSolo"
                    />

                    <!-- Empty state -->
                    <div
                        v-if="!selectedIds.length"
                        class="flex flex-col items-center justify-center text-center px-6"
                        :class="isNarrow ? 'py-20' : 'absolute inset-0'"
                    >
                        <div
                            class="w-14 h-14 rounded-2xl bg-card border border-border flex items-center justify-center mb-4"
                        >
                            <LayoutGrid class="size-7 text-primary" />
                        </div>
                        <h2 class="font-heading text-2xl font-bold">
                            Belum ada POV dipilih
                        </h2>
                        <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                            <template v-if="totalLive">
                                Pilih hingga {{ maxTiles }} siaran live untuk
                                ditonton bersamaan.
                            </template>
                            <template v-else>
                                Belum ada officer yang live (10-8) saat ini.
                                Coba lagi nanti.
                            </template>
                        </p>
                        <Button
                            v-if="totalLive"
                            type="button"
                            class="mt-5 font-bold gap-2"
                            @click="openPicker('pov')"
                        >
                            <Plus class="size-4" />
                            <span>Pilih POV</span>
                        </Button>
                    </div>

                    <!-- Bottom sheet / drawer for tablets, phones & fullscreen -->
                    <Transition name="sheet">
                        <div
                            v-if="sheetOpen"
                            class="fixed inset-0 z-50 flex"
                            :class="
                                isNarrow ? 'items-end' : 'justify-end'
                            "
                        >
                            <div
                                class="absolute inset-0 bg-background/60"
                                @click="sheetOpen = false"
                            />
                            <div
                                class="sheet-panel relative flex flex-col bg-card border-border"
                                :class="
                                    isNarrow
                                        ? 'w-full h-[78dvh] rounded-t-2xl border-t pb-[env(safe-area-inset-bottom)]'
                                        : 'sheet-side w-[min(22rem,90vw)] h-full border-l'
                                "
                            >
                                <div
                                    v-if="isNarrow"
                                    class="pt-2 flex justify-center"
                                >
                                    <span
                                        class="w-10 h-1 rounded-full bg-muted"
                                    />
                                </div>
                                <div
                                    class="flex items-center gap-1 px-3 pt-2 pb-1"
                                >
                                    <Button
                                        v-for="t in [
                                            { key: 'pov', label: 'Daftar POV' },
                                            { key: 'chat', label: 'Live Chat' },
                                        ]"
                                        :key="t.key"
                                        type="button"
                                        :variant="
                                            panelTab === t.key
                                                ? 'secondary'
                                                : 'ghost'
                                        "
                                        size="sm"
                                        class="text-sm font-bold"
                                        @click="panelTab = t.key"
                                    >
                                        {{ t.label }}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="ml-auto size-9"
                                        aria-label="Tutup panel"
                                        title="Tutup"
                                        @click="sheetOpen = false"
                                    >
                                        <X class="size-5" />
                                    </Button>
                                </div>
                                <MultiviewStreamPicker
                                    v-if="panelTab === 'pov'"
                                    class="flex-1 min-h-0"
                                    :streams="streams"
                                    :offline-officers="offlineOfficers"
                                    :selected-ids="selectedIds"
                                    :max-tiles="maxTiles"
                                    @toggle="toggleStream"
                                />
                                <div
                                    v-else
                                    class="flex-1 min-h-0 flex flex-col"
                                >
                                    <p
                                        v-if="chatTarget"
                                        class="px-3 py-1.5 text-xs text-muted-foreground truncate"
                                    >
                                        Chat:
                                        <span
                                            class="text-foreground font-semibold"
                                        >
                                            {{
                                                chatTarget.officer
                                                    ?.officer_name ||
                                                chatTarget.title
                                            }}
                                        </span>
                                    </p>
                                    <iframe
                                        v-if="chatSrc"
                                        :key="chatSrc"
                                        :src="chatSrc"
                                        class="flex-1 w-full bg-background"
                                        title="YouTube live chat"
                                    />
                                    <p
                                        v-else
                                        class="p-6 text-center text-sm text-muted-foreground"
                                    >
                                        Pilih POV terlebih dahulu.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </section>

                <!-- Desktop sidebar -->
                <aside
                    v-if="isDesktop && showChrome && sidebarOpen"
                    class="w-80 xl:w-96 shrink-0 flex flex-col border-l border-border bg-card"
                >
                    <div class="flex gap-1 px-3 pt-3">
                        <Button
                            v-for="t in [
                                { key: 'pov', label: 'Daftar POV' },
                                { key: 'chat', label: 'Live Chat' },
                            ]"
                            :key="t.key"
                            type="button"
                            :variant="
                                panelTab === t.key ? 'secondary' : 'ghost'
                            "
                            size="sm"
                            class="text-sm font-bold"
                            @click="panelTab = t.key"
                        >
                            {{ t.label }}
                        </Button>
                    </div>
                    <MultiviewStreamPicker
                        v-show="panelTab === 'pov'"
                        class="flex-1 min-h-0"
                        :streams="streams"
                        :offline-officers="offlineOfficers"
                        :selected-ids="selectedIds"
                        :max-tiles="maxTiles"
                        @toggle="toggleStream"
                    />
                    <div
                        v-if="panelTab === 'chat'"
                        class="flex-1 min-h-0 flex flex-col"
                    >
                        <p
                            v-if="chatTarget"
                            class="px-3 py-2 text-xs text-muted-foreground truncate"
                        >
                            Chat:
                            <span class="text-foreground font-semibold">
                                {{
                                    chatTarget.officer?.officer_name ||
                                    chatTarget.title
                                }}
                            </span>
                            <span class="text-muted-foreground">
                                · ikut POV yang bersuara
                            </span>
                        </p>
                        <iframe
                            v-if="chatSrc"
                            :key="chatSrc"
                            :src="chatSrc"
                            class="flex-1 w-full"
                            title="YouTube live chat"
                        />
                        <p
                            v-else
                            class="p-6 text-center text-sm text-muted-foreground"
                        >
                            Pilih POV terlebih dahulu.
                        </p>
                    </div>
                    <p
                        class="px-3 py-2 border-t border-border text-xs text-muted-foreground leading-relaxed"
                    >
                        Pintasan: <kbd>1</kbd>–<kbd>9</kbd> pilih suara ·
                        <kbd>M</kbd> bisukan · <kbd>G</kbd> ganti layout
                    </p>
                </aside>
            </div>

            <!-- Hint for first-time sound -->
            <p
                v-if="isNarrow && selectedIds.length && !audioId"
                class="px-3 pt-2 text-center text-xs text-muted-foreground"
            >
                Semua POV dimulai tanpa suara — ketuk ikon speaker untuk
                mendengarkan satu POV.
            </p>

            <TacticalMobileBottomNav v-if="isNarrow" />
            <Toaster rich-colors position="top-right" />
        </div>
    </TooltipProvider>
</template>

<style scoped>
.tile {
    transition:
        left 0.3s ease,
        top 0.3s ease,
        width 0.3s ease,
        height 0.3s ease;
}

.tile-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.375rem;
    background: color-mix(in oklab, var(--background) 75%, transparent);
    color: var(--foreground);
    backdrop-filter: blur(4px);
    transition: background-color 0.15s;
}
.tile-btn:hover {
    background: var(--primary);
}

kbd {
    padding: 0 0.25rem;
    border-radius: 0.25rem;
    border: 1px solid var(--border);
    font-family: ui-monospace, monospace;
    font-size: 0.75rem;
    color: var(--foreground);
}

.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.2s ease;
}
.sheet-enter-active .sheet-panel,
.sheet-leave-active .sheet-panel {
    transition: transform 0.25s ease;
}
.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}
.sheet-enter-from .sheet-panel,
.sheet-leave-to .sheet-panel {
    transform: translateY(100%);
}
.sheet-enter-from .sheet-side,
.sheet-leave-to .sheet-side {
    transform: translateX(100%);
}
</style>
