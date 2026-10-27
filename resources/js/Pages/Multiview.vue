<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from "vue";

import MultiviewStreamPicker from "@/Components/MultiviewStreamPicker.vue";
import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalMobileBottomNav from "@/Components/TacticalMobileBottomNav.vue";
import { useAnalytics } from "@/Composables/useAnalytics";

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
const VIDEO_ID_RE = /^[\w-]{11}$/;

// ---------------------------------------------------------------------------
// Stream data
// ---------------------------------------------------------------------------
const streams = ref(props.initialStreams);
const offlineOfficers = ref(props.initialOfflineOfficers);

// Remember every stream we have seen so a tile keeps its label after the
// officer goes offline mid-session.
const streamCache = reactive({});
const cacheStreams = (list) => list.forEach((s) => (streamCache[s.video_id] = s));
cacheStreams(streams.value);

const liveIds = computed(() => new Set(streams.value.map((s) => s.video_id)));
const infoOf = (id) => streamCache[id] || null;

const refreshStreams = async () => {
    if (document.hidden) return;
    try {
        const res = await fetch("/api/v1/streams", { headers: { Accept: "application/json" } });
        if (!res.ok) return;
        const json = await res.json();
        if (Array.isArray(json.data)) {
            streams.value = json.data;
            cacheStreams(json.data);
        }
        if (Array.isArray(json.offline_officers)) offlineOfficers.value = json.offline_officers;
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
// Phone held upright: the page scrolls and tiles stack instead of fitting the screen.
const isNarrow = computed(() => viewport.w < 768 && viewport.h > viewport.w && !isFullscreen.value);
// Phone held sideways: hide the chrome so the video grid gets the whole screen.
const isCompactLandscape = computed(() => viewport.h < 500 && viewport.w > viewport.h);
const showChrome = computed(() => !isCompactLandscape.value && !isFullscreen.value);

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
const layoutMode = ref("grid"); // 'grid' | 'focus'

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

    // First visit: open with the most-watched POVs so the page is never empty.
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
    if (selectedIds.value.includes(stream.video_id)) removeStream(stream.video_id);
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
        // Keep Inertia's history state intact; this is just a shareable address.
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

const sendCommand = (id, func, args = []) => {
    const win = iframes[id]?.contentWindow;
    if (!win) return;
    win.postMessage(JSON.stringify({ event: "command", func, args }), "*");
};

const applyAudio = (id) => {
    if (id === audioId.value) {
        sendCommand(id, "unMute");
        sendCommand(id, "setVolume", [100]);
        sendCommand(id, "playVideo");
    } else {
        sendCommand(id, "mute");
    }
};

const onIframeLoad = (id) => {
    // The player inside the iframe boots a moment after the load event.
    setTimeout(() => applyAudio(id), 800);
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
// Every tile is absolutely positioned and never moves in the DOM, so switching
// layout or focus re-flows the grid without reloading any iframe.
// ---------------------------------------------------------------------------
const stageBox = ref(null);
const stageSize = reactive({ w: 0, h: 0 });

const hOf = (w) => w / RATIO;

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
        placeRow(rects, order.slice(r * cols, r * cols + cols), y0 + r * (th + GAP), tw, th, W);
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
            return { rects, height: mainH + GAP + rows * th + (rows - 1) * GAP };
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
        const sideW = Math.min(Math.max(W * (sc === 1 ? 0.24 : 0.34), 180), 480);
        const mainW = Math.min(W - sideW - GAP, H * RATIO);
        const mainH = hOf(mainW);

        let tw = (sideW - GAP * (sc - 1)) / sc;
        let th = hOf(tw);
        const rows = Math.ceil(others.length / sc);
        let colH = rows * th + (rows - 1) * GAP;
        if (colH > H) {
            th = (H - (rows - 1) * GAP) / rows;
            tw = th * RATIO;
            colH = H;
        }
        const sideRealW = sc * tw + (sc - 1) * GAP;
        const x0 = (W - (mainW + GAP + sideRealW)) / 2;

        rects[fi] = { x: x0, y: (H - mainH) / 2, w: mainW, h: mainH };
        const sx = x0 + mainW + GAP;
        const sy = (H - colH) / 2;
        others.forEach((i, k) => {
            rects[i] = { x: sx + (k % sc) * (tw + GAP), y: sy + Math.floor(k / sc) * (th + GAP), w: tw, h: th };
        });
        return { rects, height: H };
    }

    // Grid: pick the column count that gives the biggest 16:9 tiles.
    let best = { cols: 1, w: 0 };
    for (let cols = 1; cols <= n; cols++) {
        const rows = Math.ceil(n / cols);
        const w = Math.min((W - GAP * (cols - 1)) / cols, ((H - GAP * (rows - 1)) / rows) * RATIO);
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
    return { left: `${r.x}px`, top: `${r.y}px`, width: `${r.w}px`, height: `${r.h}px` };
};

const isThumb = (i) => {
    const r = layout.value.rects[i];
    return !!r && r.w < 260;
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
    if (isDesktop.value && showChrome.value) sidebarOpen.value = !sidebarOpen.value;
    else sheetOpen.value = !sheetOpen.value;
};

const chatTargetId = computed(() => audioId.value || focusId.value || selectedIds.value[0] || null);
const chatTarget = computed(() => (chatTargetId.value ? infoOf(chatTargetId.value) : null));
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
const toast = ref("");
let toastTimer = null;

const showToast = (msg) => {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = ""), 2200);
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
        else await stageWrap.value?.requestFullscreen();
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
        const id = selectedIds.value[Number(e.key) - 1];
        if (id) toggleAudio(id);
    } else if (e.key === "m") {
        muteAll();
    } else if (e.key === "g") {
        layoutMode.value = layoutMode.value === "grid" ? "focus" : "grid";
    } else if (e.key === "Escape") {
        sheetOpen.value = false;
    }
};

const deptOf = (info) => {
    const d = (info?.officer?.department || "").toUpperCase();
    return d === "PARK RANGER" ? "SAPR" : d;
};

const deptColor = {
    LSPD: "bg-blue-600",
    BCSO: "bg-amber-600",
    SASP: "bg-slate-500",
    SAPR: "bg-emerald-600",
};

const totalLive = computed(() => streams.value.length);

let pollTimer = null;

onMounted(() => {
    origin.value = window.location.origin;
    canFullscreen.value = !!document.fullscreenEnabled;
    updateViewport();

    try {
        const savedLayout = localStorage.getItem(LAYOUT_KEY);
        layoutMode.value = savedLayout === "focus" || savedLayout === "grid" ? savedLayout : isNarrow.value ? "focus" : "grid";
    } catch {
        layoutMode.value = isNarrow.value ? "focus" : "grid";
    }

    selectedIds.value = readInitialSelection().slice(0, maxTiles.value);
    focusId.value = selectedIds.value[0] || null;

    resizeObserver = new ResizeObserver(([entry]) => {
        stageSize.w = entry.contentRect.width;
        stageSize.h = entry.contentRect.height;
    });
    nextTick(() => stageBox.value && resizeObserver.observe(stageBox.value));

    window.addEventListener("resize", updateViewport);
    window.addEventListener("keydown", onKeydown);
    document.addEventListener("fullscreenchange", onFullscreenChange);
    pollTimer = setInterval(refreshStreams, POLL_MS);

    trackPageView(`Multiview (${selectedIds.value.length} POV)`);
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    window.removeEventListener("resize", updateViewport);
    window.removeEventListener("keydown", onKeydown);
    document.removeEventListener("fullscreenchange", onFullscreenChange);
    clearInterval(pollTimer);
    clearTimeout(toastTimer);
});

const btn =
    "inline-flex items-center justify-center gap-1.5 h-9 px-2.5 sm:px-3 rounded-lg border text-xs font-bold transition shrink-0";
const btnIdle = "bg-tactical-panel border-tactical-border text-slate-300 hover:text-white hover:border-tactical-border-light";
const btnActive = "bg-blue-600 border-blue-500 text-white";
</script>

<template>
    <Head title="Multiview — Pantau Banyak POV Sekaligus" />

    <div
        class="bg-[#070b12] text-slate-100 font-sans antialiased flex flex-col"
        :class="isNarrow ? 'min-h-[100dvh] pb-20' : 'h-[100dvh] overflow-hidden'"
    >
        <TacticalDashboardHeader v-if="showChrome" />

        <!-- Toolbar -->
        <div
            v-if="showChrome"
            class="flex items-center gap-2 px-2 sm:px-4 py-2 border-b border-tactical-border bg-tactical-base/80"
        >
            <div class="flex items-center gap-2 min-w-0 mr-auto">
                <h1 class="font-heading text-base sm:text-xl font-bold tracking-wide leading-none">MULTIVIEW</h1>
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-red-600/15 border border-red-500/30 text-[10px] font-bold text-red-400 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse" />
                    {{ totalLive }} LIVE
                </span>
                <span class="hidden sm:inline text-xs text-slate-500">{{ selectedIds.length }}/{{ maxTiles }} POV</span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <!-- Layout switch -->
                <div class="flex p-0.5 rounded-lg bg-tactical-panel border border-tactical-border shrink-0">
                    <button
                        type="button"
                        class="h-8 px-2 rounded-md text-xs font-bold flex items-center gap-1"
                        :class="layoutMode === 'grid' ? 'bg-slate-100 text-slate-900' : 'text-slate-400'"
                        title="Grid (G)"
                        @click="layoutMode = 'grid'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7.5" height="7.5" rx="1" /><rect x="13.5" y="3" width="7.5" height="7.5" rx="1" /><rect x="3" y="13.5" width="7.5" height="7.5" rx="1" /><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1" /></svg>
                        <span class="hidden md:inline">Grid</span>
                    </button>
                    <button
                        type="button"
                        class="h-8 px-2 rounded-md text-xs font-bold flex items-center gap-1"
                        :class="layoutMode === 'focus' ? 'bg-slate-100 text-slate-900' : 'text-slate-400'"
                        title="Fokus (G)"
                        @click="layoutMode = 'focus'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="13" height="18" rx="1" /><rect x="18.5" y="3" width="2.5" height="5" rx=".5" /><rect x="18.5" y="10" width="2.5" height="5" rx=".5" /><rect x="18.5" y="17" width="2.5" height="4" rx=".5" /></svg>
                        <span class="hidden md:inline">Fokus</span>
                    </button>
                </div>

                <button type="button" :class="[btn, btnActive]" @click="openPicker('pov')">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    <span class="hidden sm:inline">Tambah POV</span>
                </button>

                <button type="button" :class="[btn, btnIdle, 'max-sm:hidden']" title="Live chat" @click="openPicker('chat')">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 2.5-3H19a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2" /></svg>
                </button>

                <button v-if="audioId" type="button" :class="[btn, btnIdle]" title="Bisukan semua (M)" @click="muteAll">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5Zm11 4-6 6m0-6 6 6" /></svg>
                </button>

                <button type="button" :class="[btn, btnIdle]" title="Bagikan link multiview" :disabled="!selectedIds.length" @click="shareLink">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1 1m1.99 3.66a4 4 0 0 0-5.66 0l-3 3a4 4 0 0 0 5.66 5.66l1-1" /></svg>
                </button>

                <button v-if="canFullscreen" type="button" :class="[btn, btnIdle]" title="Layar penuh" @click="toggleFullscreen">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" /></svg>
                </button>

                <button v-if="selectedIds.length" type="button" :class="[btn, btnIdle, 'max-sm:hidden']" title="Kosongkan semua" @click="clearAll">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                </button>

                <button
                    v-if="isDesktop"
                    type="button"
                    :class="[btn, sidebarOpen ? btnActive : btnIdle]"
                    title="Tampilkan / sembunyikan panel"
                    @click="toggleSidebar"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2" /><path d="M15 4v16" /></svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 min-h-0 flex">
            <!-- Stage -->
            <section
                ref="stageWrap"
                class="relative flex-1 min-w-0 bg-black"
                :class="isCompactLandscape || isFullscreen ? 'p-0.5' : 'p-1.5 sm:p-2'"
            >
                <div
                    ref="stageBox"
                    class="relative w-full"
                    :class="isNarrow ? '' : 'h-full'"
                    :style="isNarrow ? { height: `${layout.height}px` } : {}"
                >
                    <div
                        v-for="(id, i) in selectedIds"
                        :key="id"
                        class="tile absolute group"
                        :style="tileStyle(i)"
                    >
                        <div
                            class="relative w-full h-full rounded-md overflow-hidden bg-slate-900 ring-2 transition-colors"
                            :class="audioId === id ? 'ring-blue-500' : 'ring-transparent'"
                        >
                            <iframe
                                :ref="(el) => el && (iframes[id] = el)"
                                :src="embedSrc(id)"
                                :title="infoOf(id)?.title || 'YouTube POV'"
                                class="absolute inset-0 w-full h-full"
                                allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                allowfullscreen
                                @load="onIframeLoad(id)"
                            />

                            <!-- Label -->
                            <div class="tile-controls absolute top-0 inset-x-0 flex items-start gap-1 p-1 bg-gradient-to-b from-black/80 to-transparent pointer-events-none">
                                <span class="shrink-0 w-5 h-5 rounded bg-black/70 text-[10px] font-mono font-bold flex items-center justify-center text-slate-300">{{ i + 1 }}</span>
                                <div class="min-w-0 flex-1 leading-tight" :class="isThumb(i) ? 'hidden' : ''">
                                    <p class="text-xs font-bold text-white truncate drop-shadow">
                                        {{ infoOf(id)?.officer?.officer_name || infoOf(id)?.title || id }}
                                    </p>
                                    <p class="flex items-center gap-1 text-[10px] text-slate-300 truncate">
                                        <span
                                            v-if="deptOf(infoOf(id))"
                                            class="px-1 rounded-sm font-bold text-white"
                                            :class="deptColor[deptOf(infoOf(id))] || 'bg-slate-600'"
                                        >{{ deptOf(infoOf(id)) }}</span>
                                        <span class="font-mono">{{ infoOf(id)?.officer?.callsign }}</span>
                                        <span v-if="!liveIds.has(id)" class="px-1 rounded-sm bg-slate-700 font-bold">OFFLINE</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="tile-controls absolute top-1 right-1 flex gap-1">
                                <button
                                    v-if="layoutMode !== 'focus' || focusId !== id"
                                    type="button"
                                    class="tile-btn"
                                    title="Jadikan utama"
                                    @click="setFocus(id)"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" /></svg>
                                </button>
                                <button type="button" class="tile-btn hover:!bg-red-600" title="Tutup POV" @click="removeStream(id)">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                                </button>
                            </div>

                            <!-- Audio toggle: always visible so the active sound source is obvious -->
                            <button
                                type="button"
                                class="absolute right-1.5 tile-btn !w-8 !h-8"
                                :class="[
                                    isThumb(i) ? 'bottom-1.5' : 'bottom-10',
                                    audioId === id ? '!bg-blue-600 !opacity-100' : 'tile-controls',
                                ]"
                                :title="audioId === id ? 'Bisukan' : 'Dengarkan POV ini'"
                                @click="toggleAudio(id)"
                            >
                                <svg v-if="audioId === id" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5Zm4.5 3.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" /></svg>
                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5Zm11 4-6 6m0-6 6 6" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-if="!selectedIds.length"
                    class="flex flex-col items-center justify-center text-center px-6"
                    :class="isNarrow ? 'py-20' : 'absolute inset-0'"
                >
                    <div class="w-14 h-14 rounded-2xl bg-tactical-panel border border-tactical-border flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7.5" height="7.5" rx="1" /><rect x="13.5" y="3" width="7.5" height="7.5" rx="1" /><rect x="3" y="13.5" width="7.5" height="7.5" rx="1" /><path stroke-linecap="round" d="M17.25 14v6.5M14 17.25h6.5" /></svg>
                    </div>
                    <h2 class="font-heading text-2xl font-bold">Belum ada POV dipilih</h2>
                    <p class="mt-1 max-w-sm text-sm text-slate-400">
                        <template v-if="totalLive">Pilih hingga {{ maxTiles }} siaran live untuk ditonton bersamaan.</template>
                        <template v-else>Belum ada officer yang live (10-8) saat ini. Coba lagi nanti.</template>
                    </p>
                    <button
                        v-if="totalLive"
                        type="button"
                        class="mt-5 inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-blue-600 hover:bg-blue-500 text-sm font-bold"
                        @click="openPicker('pov')"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                        Pilih POV
                    </button>
                </div>

                <!-- Floating controls when the toolbar is hidden (phone landscape / fullscreen) -->
                <div v-if="!showChrome" class="absolute bottom-2 right-2 z-20 flex gap-1.5 opacity-70 hover:opacity-100">
                    <button type="button" class="float-btn" title="Tambah POV" @click="sheetOpen = true; panelTab = 'pov'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    </button>
                    <button type="button" class="float-btn" title="Ganti layout" @click="layoutMode = layoutMode === 'grid' ? 'focus' : 'grid'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7.5" height="7.5" rx="1" /><rect x="13.5" y="3" width="7.5" height="7.5" rx="1" /><rect x="3" y="13.5" width="7.5" height="7.5" rx="1" /><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1" /></svg>
                    </button>
                    <button v-if="isFullscreen" type="button" class="float-btn" title="Keluar layar penuh" @click="toggleFullscreen">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5" /></svg>
                    </button>
                </div>

                <!-- Bottom sheet / drawer for tablets, phones & fullscreen -->
                <Transition name="sheet">
                    <div v-if="sheetOpen" class="fixed inset-0 z-50 flex" :class="isNarrow ? 'items-end' : 'justify-end'">
                        <div class="absolute inset-0 bg-black/60" @click="sheetOpen = false" />
                        <div
                            class="sheet-panel relative flex flex-col bg-tactical-panel border-tactical-border shadow-2xl"
                            :class="isNarrow
                                ? 'w-full h-[78dvh] rounded-t-2xl border-t pb-[env(safe-area-inset-bottom)]'
                                : 'sheet-side w-[min(22rem,90vw)] h-full border-l'"
                        >
                            <div v-if="isNarrow" class="pt-2 flex justify-center">
                                <span class="w-10 h-1 rounded-full bg-slate-600" />
                            </div>
                            <div class="flex items-center gap-1 px-3 pt-2 pb-1">
                                <button
                                    v-for="t in [{ key: 'pov', label: 'Daftar POV' }, { key: 'chat', label: 'Live Chat' }]"
                                    :key="t.key"
                                    type="button"
                                    class="px-3 py-1.5 rounded-md text-sm font-bold"
                                    :class="panelTab === t.key ? 'text-white bg-tactical-surface' : 'text-slate-400'"
                                    @click="panelTab = t.key"
                                >
                                    {{ t.label }}
                                </button>
                                <button type="button" class="ml-auto w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:text-white" title="Tutup" @click="sheetOpen = false">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                                </button>
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
                            <div v-else class="flex-1 min-h-0 flex flex-col">
                                <p v-if="chatTarget" class="px-3 py-1.5 text-xs text-slate-400 truncate">
                                    Chat: <span class="text-slate-200 font-semibold">{{ chatTarget.officer?.officer_name || chatTarget.title }}</span>
                                </p>
                                <iframe v-if="chatSrc" :key="chatSrc" :src="chatSrc" class="flex-1 w-full bg-white" title="YouTube live chat" />
                                <p v-else class="p-6 text-center text-sm text-slate-500">Pilih POV terlebih dahulu.</p>
                            </div>
                        </div>
                    </div>
                </Transition>
            </section>

            <!-- Desktop sidebar -->
            <aside
                v-if="isDesktop && showChrome && sidebarOpen"
                class="w-80 xl:w-96 shrink-0 flex flex-col border-l border-tactical-border bg-tactical-panel"
            >
                <div class="flex gap-1 px-3 pt-3">
                    <button
                        v-for="t in [{ key: 'pov', label: 'Daftar POV' }, { key: 'chat', label: 'Live Chat' }]"
                        :key="t.key"
                        type="button"
                        class="px-3 py-1.5 rounded-md text-sm font-bold"
                        :class="panelTab === t.key ? 'text-white bg-tactical-surface' : 'text-slate-400 hover:text-slate-200'"
                        @click="panelTab = t.key"
                    >
                        {{ t.label }}
                    </button>
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
                <div v-if="panelTab === 'chat'" class="flex-1 min-h-0 flex flex-col">
                    <p v-if="chatTarget" class="px-3 py-2 text-xs text-slate-400 truncate">
                        Chat: <span class="text-slate-200 font-semibold">{{ chatTarget.officer?.officer_name || chatTarget.title }}</span>
                        <span class="text-slate-500"> · ikut POV yang bersuara</span>
                    </p>
                    <iframe v-if="chatSrc" :key="chatSrc" :src="chatSrc" class="flex-1 w-full" title="YouTube live chat" />
                    <p v-else class="p-6 text-center text-sm text-slate-500">Pilih POV terlebih dahulu.</p>
                </div>
                <p class="px-3 py-2 border-t border-tactical-border text-[11px] text-slate-500 leading-relaxed">
                    Pintasan: <kbd>1</kbd>–<kbd>9</kbd> pilih suara · <kbd>M</kbd> bisukan · <kbd>G</kbd> ganti layout
                </p>
            </aside>
        </div>

        <!-- Hint for first-time sound -->
        <p
            v-if="isNarrow && selectedIds.length && !audioId"
            class="px-3 pt-2 text-center text-[11px] text-slate-500"
        >
            Semua POV dimulai tanpa suara — ketuk ikon speaker untuk mendengarkan satu POV.
        </p>

        <Transition name="fade">
            <div
                v-if="toast"
                class="fixed left-1/2 -translate-x-1/2 bottom-24 md:bottom-6 z-[60] px-4 py-2 rounded-lg bg-slate-100 text-slate-900 text-sm font-semibold shadow-xl"
            >
                {{ toast }}
            </div>
        </Transition>

        <TacticalMobileBottomNav v-if="isNarrow" />
    </div>
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
    background: rgb(0 0 0 / 0.7);
    color: #fff;
    backdrop-filter: blur(4px);
    transition: background-color 0.15s;
}
.tile-btn:hover {
    background: rgb(37 99 235);
}

/* Mouse users get the controls on hover; touch screens always see them. */
@media (hover: hover) {
    .tile-controls {
        opacity: 0;
        transition: opacity 0.15s;
    }
    .group:hover .tile-controls {
        opacity: 1;
    }
}

.float-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    background: rgb(15 23 42 / 0.85);
    border: 1px solid rgb(51 65 85);
    color: #fff;
}

kbd {
    padding: 0 0.25rem;
    border-radius: 0.25rem;
    border: 1px solid rgb(51 65 85);
    font-family: ui-monospace, monospace;
    font-size: 10px;
    color: rgb(203 213 225);
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

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
