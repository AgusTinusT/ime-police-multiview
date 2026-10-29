<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

// SVG Icon Assets & Branding Logos
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";

// Refactored Sub-Components
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import HeroCarousel from "@/Components/Hero/HeroCarousel.vue";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import TacticalClipperBetaBanner from "@/Components/TacticalClipperBetaBanner.vue";
import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import TacticalMonthlyLeaderboard from "@/Components/TacticalMonthlyLeaderboard.vue";
import TacticalStreamSlimles from "@/Components/TacticalStreamSlimles.vue";
import TacticalSubSupportGrid from "@/Components/TacticalSubSupportGrid.vue";
import { useYouTubePlayer } from "@/Composables/useYouTubePlayer";
import { parseRelativeTimeToSeconds } from "@/Utils/timeHelpers";
import { defineProps } from "vue";

const props = defineProps({
    heroSlides: {
        type: Array,
        default: () => [],
    },
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
    monthlyLeaderboard: {
        type: Object,
        default: () => ({}),
    },
    supportOfficers: {
        type: Array,
        default: () => [],
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
const isSyncingFeeds = ref(false);
const showClipperClaimModal = ref(false);

// Featured Spotlight Stream for Hero Section

// Computed All Active & Catalog Streams
const allActiveStreams = computed(() => streams.value);
const trendingStreams = computed(() => streams.value.slice(0, 8));
const recentReplayStreams = computed(() => {
    const list = [...(recentReplays.value || [])];
    return list.sort((a, b) => {
        const timeA = parseRelativeTimeToSeconds(
            a.streamed_at || a.incident_code,
        );
        const timeB = parseRelativeTimeToSeconds(
            b.streamed_at || b.incident_code,
        );
        return timeA - timeB;
    });
});
const lspdCatalogStreams = computed(() =>
    streams.value.filter((s) => s.officer?.department === "LSPD"),
);
const saspCatalogStreams = computed(() =>
    streams.value.filter((s) => s.officer?.department === "SASP"),
);
const bcsoCatalogStreams = computed(() =>
    streams.value.filter((s) => s.officer?.department === "BCSO"),
);
const saprCatalogStreams = computed(() =>
    streams.value.filter((s) =>
        ["SAPR", "PARK RANGER"].includes(s.officer?.department),
    ),
);
const displayedGridStreams = computed(() => streams.value);
const activeGridVideoIds = ref([]);
const activeAudioVideoId = ref(null);

const { isDataSaverEnabled, enableDataSaver, disableDataSaverAndPlayAll } =
    useYouTubePlayer({
        activeAudioVideoId,
        activeGridVideoIds,
        displayedGridStreams,
    });

const isFullscreen = ref(false);
const toggleBrowserFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
            isFullscreen.value = false;
        }
    }
};

const tacChannels = ref(props.initialTacChannels || []);

const triggerManualSync = async () => {
    isSyncingFeeds.value = true;
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
        if (res.ok) {
            const data = await res.json();
            if (data.status === "success") {
                if (Array.isArray(data.data)) streams.value = data.data;
                if (Array.isArray(data.replays))
                    recentReplays.value = data.replays;
                if (Array.isArray(data.offline_officers))
                    offlineOfficers.value = data.offline_officers;
            }
        }
    } catch (e) {
        console.error("Manual sync failed:", e);
    } finally {
        isSyncingFeeds.value = false;
    }
};

const playStreamInFocus = () => {};

const scrollToUnitsGrid = () => {
    const el = document.getElementById("units-grid");
    if (el) {
        el.scrollIntoView({ behavior: "smooth" });
    }
};
</script>

<template>
    <Head :title="$t('dashboard.title')">
        <meta
            name="description"
            content="Pusat pemantauan taktis & live streaming bodycam perwira kepolisian IME Roleplay (LSPD, BCSO, SASP, SAPR). Pantau seluruh siaran langsung perwira patroli secara multiview interaktif."
        />
        <meta
            property="og:title"
            content="IME RP — SASP Police Duty | Live Officer Bodycam & Discovery Hub"
        />
        <meta
            property="og:description"
            content="Pusat pemantauan taktis & live streaming bodycam perwira kepolisian IME Roleplay."
        />
        <meta property="og:type" content="website" />
    </Head>

    <div
        class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased relative overflow-hidden"
    >
        <!-- Crisp Minimalist Grid Overlay -->
        <div
            class="fixed inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] bg-size-[24px_24px] opacity-20 pointer-events-none z-0"
        ></div>
        <!-- Refactored Tactical Header Bar Component -->
        <TacticalDashboardHeader
            :is-data-saver-enabled="isDataSaverEnabled"
            :is-syncing-feeds="isSyncingFeeds"
            :is-fullscreen="isFullscreen"
            @enable-data-saver="enableDataSaver"
            @disable-data-saver="disableDataSaverAndPlayAll"
            @trigger-sync="triggerManualSync"
            @toggle-fullscreen="toggleBrowserFullscreen"
        />

        <!-- Main Content Area -->
        <main class="flex-1 w-full">
            <HeroCarousel
                v-if="heroSlides && heroSlides.length > 0"
                :slides="heroSlides"
            />
            <!-- HERO SPOTLIGHT BANNER SECTION (Section 1: Darkest Slate Canvas - bg-slate-950, No Border) -->
            <section v-else class="relative w-full bg-slate-950 overflow-hidden group">
                <!-- Full-Width Background Hero Backdrop Image with Minimal Overlay -->
                <div
                    class="absolute inset-0 z-0 pointer-events-none opacity-30"
                >
                    <img
                        src="/images/hero-fallback.jpg"
                        loading="eager"
                        fetchpriority="high"
                        class="w-full h-full object-cover grayscale mix-blend-luminosity"
                        alt="IME Police Duty Hero Backdrop"
                    />
                    <div class="absolute inset-0 bg-slate-950/40"></div>
                </div>

                <!-- Inner Content Container (Restricted to max-w-screen-2xl) -->
                <div
                    class="max-w-(--breakpoint-2xl) mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-10 lg:pt-14 pb-10 sm:pb-14 relative z-10"
                >
                    <div
                        class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center"
                    >
                        <!-- Left Hero Content -->
                        <div class="lg:col-span-7 space-y-5">
                            <div class="hidden sm:inline-flex tactical-badge">
                                <span
                                    class="w-2 h-2 rounded-full bg-blue-500 animate-pulse shrink-0"
                                ></span>
                                <span
                                    >SERVER: IME ROLEPLAY // LIVE MULTIVIEW
                                    STREAMING</span
                                >
                                <span class="text-slate-700">|</span>
                                <span class="text-slate-400"
                                    >SAN ANDREAS STATE</span
                                >
                            </div>

                            <h1
                                class="text-3xl sm:text-5xl lg:text-6xl font-sans font-bold text-slate-100 tracking-tight leading-tight"
                            >
                                IME POLICE <br />
                                <span class="text-blue-500"
                                    >BODYCAM STREAMING</span
                                >
                            </h1>

                            <p
                                class="text-tactical-muted text-xs sm:text-sm lg:text-base leading-relaxed max-w-xl font-normal"
                            >
                                {{ $t("dashboard.hero_description") }}
                            </p>

                            <!-- Department Quick Status Badges (Flat Crisp Badges) -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <div class="tactical-badge">
                                    <span
                                        class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"
                                    ></span>
                                    <span
                                        >ALL UNITS ({{
                                            allActiveStreams.length
                                        }})</span
                                    >
                                </div>

                                <div class="tactical-badge">
                                    <span
                                        class="w-2 h-2 rounded-full bg-blue-500 shrink-0"
                                    ></span>
                                    <span
                                        >LSPD METRO ({{
                                            lspdCatalogStreams.length
                                        }})</span
                                    >
                                </div>

                                <div
                                    class="px-3 py-1.5 rounded-md text-xs font-mono font-medium flex items-center gap-2 border bg-slate-900 border-slate-800 text-slate-300"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-amber-500 shrink-0"
                                    ></span>
                                    <span
                                        >BCSO SHERIFF ({{
                                            bcsoCatalogStreams.length
                                        }})</span
                                    >
                                </div>

                                <div
                                    class="px-3 py-1.5 rounded-md text-xs font-mono font-medium flex items-center gap-2 border bg-slate-900 border-slate-800 text-slate-300"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-teal-500 shrink-0"
                                    ></span>
                                    <span
                                        >SASP STATE ({{
                                            saspCatalogStreams.length
                                        }})</span
                                    >
                                </div>

                                <div
                                    class="px-3 py-1.5 rounded-md text-xs font-mono font-medium flex items-center gap-2 border bg-slate-900 border-slate-800 text-slate-300"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"
                                    ></span>
                                    <span
                                        >PARK RANGER ({{
                                            saprCatalogStreams.length
                                        }})</span
                                    >
                                </div>
                            </div>

                            <!-- Hero Action Links (Flat Buttons from panduan.html) -->
                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <Link
                                    href="/multiview"
                                    class="px-4 py-2.5 rounded-md bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-medium text-xs transition-colors flex items-center gap-2"
                                >
                                    <svg
                                        class="w-4 h-4 text-white"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span>{{
                                        $t("dashboard.btn_open_multi_bodycam")
                                    }}</span>
                                </Link>

                                <button
                                    type="button"
                                    @click="scrollToUnitsGrid"
                                    class="px-4 py-2.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 hover:border-slate-700 font-medium text-xs flex items-center gap-2 transition-colors cursor-pointer"
                                >
                                    <svg
                                        class="w-4 h-4 text-slate-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                    <span>{{
                                        $t("dashboard.btn_explore_feeds")
                                    }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Right Tactical Live HUD Card (Flat Surface & Crisp Borders) -->
                        <div class="lg:col-span-5">
                            <div
                                class="rounded-xl bg-slate-900 border border-slate-800 p-5 space-y-4"
                            >
                                <!-- HUD Accent Header -->
                                <div
                                    class="flex items-center justify-between pb-3 border-b border-slate-800"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full bg-cyan-400"
                                        ></span>
                                        <span
                                            class="text-xs font-mono font-semibold tracking-wider text-slate-200 uppercase"
                                            >{{
                                                $t("dashboard.grid_monitor")
                                            }}</span
                                        >
                                    </div>
                                    <span
                                        class="text-[11px] font-mono text-emerald-400 bg-emerald-950/60 px-2 py-0.5 rounded-sm border border-emerald-800/80 font-medium"
                                        >{{
                                            $t("dashboard.live_metrics")
                                        }}</span
                                    >
                                </div>

                                <!-- Stats Matrix Grid (2x2) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        class="p-3 rounded-lg bg-slate-950 border border-slate-800 space-y-1"
                                    >
                                        <span
                                            class="text-[10px] font-mono text-slate-400 block uppercase"
                                            >{{
                                                $t("dashboard.on_duty_feeds")
                                            }}</span
                                        >
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-cyan-400"
                                                >{{
                                                    allActiveStreams.length
                                                }}</span
                                            >
                                            <span
                                                class="text-[11px] text-slate-500 font-mono"
                                                >{{
                                                    $t("dashboard.active")
                                                }}</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-slate-950 border border-slate-800 space-y-1"
                                    >
                                        <span
                                            class="text-[10px] font-mono text-slate-400 block uppercase"
                                            >{{
                                                $t("common.departments")
                                            }}</span
                                        >
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-emerald-400"
                                                >4</span
                                            >
                                            <span
                                                class="text-[11px] text-slate-500 font-mono"
                                                >{{
                                                    $t("dashboard.divisions")
                                                }}</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-slate-950 border border-slate-800 space-y-1"
                                    >
                                        <span
                                            class="text-[10px] font-mono text-slate-400 block uppercase"
                                            >{{
                                                $t("dashboard.total_officers")
                                            }}</span
                                        >
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-amber-400"
                                                >{{
                                                    allActiveStreams.length +
                                                    offlineOfficers.length
                                                }}</span
                                            >
                                            <span
                                                class="text-[11px] text-slate-500 font-mono"
                                                >{{
                                                    $t(
                                                        "dashboard.officers_unit",
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-slate-950 border border-slate-800 space-y-1"
                                    >
                                        <span
                                            class="text-[10px] font-mono text-slate-400 block uppercase"
                                            >{{
                                                $t("dashboard.replay_archive")
                                            }}</span
                                        >
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-blue-400"
                                                >{{
                                                    recentReplays.length
                                                }}</span
                                            >
                                            <span
                                                class="text-[11px] text-slate-500 font-mono"
                                                >{{
                                                    $t("dashboard.videos_unit")
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Live Stream Transmission Info Box -->
                                <div
                                    class="bg-slate-950 rounded-lg p-3 border border-slate-800 space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-mono"
                                    >
                                        <span
                                            class="text-slate-400 flex items-center gap-1.5"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5 text-blue-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                />
                                            </svg>
                                            {{
                                                $t(
                                                    "dashboard.transmission_feed",
                                                )
                                            }}
                                        </span>
                                        <span class="text-cyan-400 text-[10px]"
                                            >YOUTUBE LIVE</span
                                        >
                                    </div>
                                    <p
                                        class="text-xs font-mono text-slate-400 bg-slate-900 p-2 rounded-sm border border-slate-800 leading-relaxed"
                                    >
                                        {{ $t("dashboard.transmission_log") }}
                                    </p>
                                    <div
                                        class="flex items-center justify-between pt-1"
                                    >
                                        <button
                                            @click="triggerManualSync"
                                            :disabled="isSyncingFeeds"
                                            class="text-[11px] font-mono text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors"
                                        >
                                            <img
                                                :src="iconRefresh"
                                                :class="[
                                                    'w-3 h-3 invert opacity-80',
                                                    isSyncingFeeds
                                                        ? 'animate-spin'
                                                        : '',
                                                ]"
                                            />
                                            <span>{{
                                                isSyncingFeeds
                                                    ? $t("common.syncing")
                                                    : $t(
                                                          "common.sync_live_feeds",
                                                      )
                                            }}</span>
                                        </button>
                                        <span
                                            class="text-[10px] text-slate-500 font-mono"
                                            >Feeds: LSPD / BCSO / SASP /
                                            SAPR</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: Live Officers & Recent Patrol Replays Section (Section 2: Tactical Navy Surface - bg-[#0a0f1d], No Border) -->
            <section
                id="units-grid"
                class="w-full bg-[#0a0f1d] pt-10 sm:pt-14 pb-12 sm:pb-16 scroll-mt-6 relative z-10"
            >
                <div class="max-w-(--breakpoint-2xl) mx-auto px-3.5 sm:px-6 lg:px-8">
                    <TacticalStreamSlimles
                        :is-syncing-feeds="isSyncingFeeds"
                        :all-active-streams="allActiveStreams"
                        :trending-streams="trendingStreams"
                        :recent-replay-streams="recentReplayStreams"
                        :tac-channels="tacChannels"
                        @play-stream-in-focus="playStreamInFocus"
                        @trigger-manual-sync="triggerManualSync"
                    />
                </div>
            </section>

            <!-- SECTION 3: Monthly Patrol Streaming Leaderboard Section (Section 3: Darkest Slate Canvas - bg-slate-950, No Border) -->
            <section
                id="leaderboard-section"
                class="w-full bg-slate-950 pt-12 sm:pt-16 pb-16 sm:pb-24 relative z-10"
            >
                <div class="max-w-(--breakpoint-2xl) mx-auto px-3.5 sm:px-6 lg:px-8">
                    <!-- Informative Clipper Beta Access Banner -->
                    <TacticalClipperBetaBanner
                        @open-claim-modal="showClipperClaimModal = true"
                    />

                    <TacticalMonthlyLeaderboard
                        :leaderboard="monthlyLeaderboard"
                    />
                </div>
            </section>

            <!-- SECTION 4: 1K Sub Support Section (Section 4: Tactical Navy Surface - bg-[#0a0f1d], No Border) -->
            <section
                id="support-section"
                class="w-full bg-[#0a0f1d] py-12 sm:py-16 relative z-10"
            >
                <div class="max-w-(--breakpoint-2xl) mx-auto px-3.5 sm:px-6 lg:px-8">
                    <TacticalSubSupportGrid :officers="supportOfficers" />
                </div>
            </section>
        </main>

        <!-- Full-Width Edge-to-Edge Tactical Footer -->
        <TacticalFooter />

        <!-- Floating Tactical Community Chat -->
        <TacticalChatDrawer />

        <!-- Clipper Beta Claim Access Modal -->
        <ClipperClaimModal
            :show="showClipperClaimModal"
            @close="showClipperClaimModal = false"
        />
    </div>
</template>
