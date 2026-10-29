<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

// Refactored Sub-Components
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import HeroCarousel from "@/Components/Hero/HeroCarousel.vue";
import {
    DeptBadge,
    PageContainer,
    StatTile,
    TacticalFrame,
} from "@/Components/tactical";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import TacticalClipperBetaBanner from "@/Components/TacticalClipperBetaBanner.vue";
import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import TacticalMonthlyLeaderboard from "@/Components/TacticalMonthlyLeaderboard.vue";
import TacticalStreamSlimles from "@/Components/TacticalStreamSlimles.vue";
import TacticalSubSupportGrid from "@/Components/TacticalSubSupportGrid.vue";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Toaster } from "@/Components/ui/sonner";
import { useYouTubePlayer } from "@/Composables/useYouTubePlayer";
import { parseRelativeTimeToSeconds } from "@/Utils/timeHelpers";
import { Eye, Film, Radio, RefreshCw, Shield, Users, Video } from "@lucide/vue";

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

// Computed All Active & Catalog Streams
const allActiveStreams = computed(() => streams.value);
const totalLiveViewers = computed(() =>
    streams.value.reduce(
        (sum, s) => sum + Number(s.viewers_count ?? s.viewer_count ?? 0),
        0,
    ),
);
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
        class="min-h-screen bg-background text-foreground font-sans selection:bg-primary selection:text-primary-foreground flex flex-col antialiased relative overflow-hidden"
    >
        <!-- Crisp Minimalist Grid Overlay -->
        <div
            class="fixed inset-0 hud-grid opacity-20 pointer-events-none z-0"
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
            <!-- HERO SPOTLIGHT BANNER SECTION (Fallback) -->
            <section
                v-else
                class="relative w-full bg-background overflow-hidden group"
            >
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
                    <div class="absolute inset-0 bg-background/40"></div>
                </div>

                <!-- Inner Content Container -->
                <PageContainer
                    class="pt-8 sm:pt-10 lg:pt-14 pb-10 sm:pb-14 relative z-10"
                >
                    <div
                        class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center"
                    >
                        <!-- Left Hero Content -->
                        <div class="lg:col-span-7 space-y-5">
                            <Badge
                                variant="outline"
                                class="hidden sm:inline-flex items-center gap-2 py-1 px-3 font-mono text-xs bg-card/80"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-primary motion-safe:animate-pulse shrink-0"
                                ></span>
                                <span>
                                    SERVER: IME ROLEPLAY // LIVE MULTIVIEW
                                    STREAMING
                                </span>
                                <span class="text-border">|</span>
                                <span class="text-muted-foreground">
                                    SAN ANDREAS STATE
                                </span>
                            </Badge>

                            <h1
                                class="text-3xl sm:text-5xl lg:text-6xl font-sans font-bold text-foreground tracking-tight leading-tight"
                            >
                                IME POLICE <br />
                                <span class="text-primary">
                                    BODYCAM STREAMING
                                </span>
                            </h1>

                            <p
                                class="text-muted-foreground text-xs sm:text-sm lg:text-base leading-relaxed max-w-xl font-normal"
                            >
                                {{ $t("dashboard.hero_description") }}
                            </p>

                            <!-- Department Quick Status Badges -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <Badge
                                    variant="secondary"
                                    class="gap-2 py-1 px-2.5 font-mono text-xs"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-success shrink-0"
                                    ></span>
                                    <span>
                                        ALL UNITS ({{ allActiveStreams.length }})
                                    </span>
                                </Badge>

                                <DeptBadge
                                    dept="LSPD"
                                    :label="`LSPD METRO (${lspdCatalogStreams.length})`"
                                />
                                <DeptBadge
                                    dept="BCSO"
                                    :label="`BCSO SHERIFF (${bcsoCatalogStreams.length})`"
                                />
                                <DeptBadge
                                    dept="SASP"
                                    :label="`SASP STATE (${saspCatalogStreams.length})`"
                                />
                                <DeptBadge
                                    dept="SAPR"
                                    :label="`PARK RANGER (${saprCatalogStreams.length})`"
                                />
                            </div>

                            <!-- Hero Action Links -->
                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <Button as-child size="default">
                                    <Link href="/multiview">
                                        <Video class="size-4" />
                                        <span>{{
                                            $t(
                                                "dashboard.btn_open_multi_bodycam",
                                            )
                                        }}</span>
                                    </Link>
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="default"
                                    @click="scrollToUnitsGrid"
                                >
                                    <Users class="size-4 text-muted-foreground" />
                                    <span>{{
                                        $t("dashboard.btn_explore_feeds")
                                    }}</span>
                                </Button>
                            </div>
                        </div>

                        <!-- Right Tactical Live HUD Card -->
                        <div class="lg:col-span-5">
                            <div
                                class="rounded-xl bg-card border border-border p-5 space-y-4"
                            >
                                <!-- HUD Accent Header -->
                                <div
                                    class="flex items-center justify-between pb-3 border-b border-border"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full bg-info"
                                        ></span>
                                        <span
                                            class="text-xs font-mono font-semibold tracking-wider text-foreground uppercase"
                                        >
                                            {{ $t("dashboard.grid_monitor") }}
                                        </span>
                                    </div>
                                    <Badge
                                        variant="success"
                                        class="font-mono text-xs"
                                    >
                                        {{ $t("dashboard.live_metrics") }}
                                    </Badge>
                                </div>

                                <!-- Stats Matrix Grid (2x2) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        class="p-3 rounded-lg bg-background border border-border space-y-1"
                                    >
                                        <span
                                            class="text-xs font-mono text-muted-foreground block uppercase"
                                        >
                                            {{ $t("dashboard.on_duty_feeds") }}
                                        </span>
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-info"
                                            >
                                                {{ allActiveStreams.length }}
                                            </span>
                                            <span
                                                class="text-xs text-muted-foreground font-mono"
                                            >
                                                {{ $t("dashboard.active") }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border border-border space-y-1"
                                    >
                                        <span
                                            class="text-xs font-mono text-muted-foreground block uppercase"
                                        >
                                            {{ $t("common.departments") }}
                                        </span>
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-success"
                                            >
                                                4
                                            </span>
                                            <span
                                                class="text-xs text-muted-foreground font-mono"
                                            >
                                                {{ $t("dashboard.divisions") }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border border-border space-y-1"
                                    >
                                        <span
                                            class="text-xs font-mono text-muted-foreground block uppercase"
                                        >
                                            {{ $t("dashboard.total_officers") }}
                                        </span>
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-warning"
                                            >
                                                {{
                                                    allActiveStreams.length +
                                                    offlineOfficers.length
                                                }}
                                            </span>
                                            <span
                                                class="text-xs text-muted-foreground font-mono"
                                            >
                                                {{
                                                    $t(
                                                        "dashboard.officers_unit",
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border border-border space-y-1"
                                    >
                                        <span
                                            class="text-xs font-mono text-muted-foreground block uppercase"
                                        >
                                            {{ $t("dashboard.replay_archive") }}
                                        </span>
                                        <div class="flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-mono font-bold text-primary"
                                            >
                                                {{ recentReplays.length }}
                                            </span>
                                            <span
                                                class="text-xs text-muted-foreground font-mono"
                                            >
                                                {{
                                                    $t("dashboard.videos_unit")
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Live Stream Transmission Info Box -->
                                <div
                                    class="bg-background rounded-lg p-3 border border-border space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-mono"
                                    >
                                        <span
                                            class="text-muted-foreground flex items-center gap-1.5"
                                        >
                                            <Video
                                                class="size-3.5 text-primary"
                                            />
                                            {{
                                                $t(
                                                    "dashboard.transmission_feed",
                                                )
                                            }}
                                        </span>
                                        <span class="text-info text-xs">
                                            YOUTUBE LIVE
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs font-mono text-muted-foreground bg-card p-2 rounded-sm border border-border leading-relaxed"
                                    >
                                        {{ $t("dashboard.transmission_log") }}
                                    </p>
                                    <div
                                        class="flex items-center justify-between pt-1"
                                    >
                                        <Button
                                            type="button"
                                            variant="link"
                                            size="sm"
                                            :disabled="isSyncingFeeds"
                                            class="h-auto p-0 text-xs font-mono text-primary hover:text-primary/80 gap-1.5"
                                            @click="triggerManualSync"
                                        >
                                            <RefreshCw
                                                :class="[
                                                    'size-3',
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
                                        </Button>
                                        <span
                                            class="text-xs text-muted-foreground font-mono"
                                        >
                                            Feeds: LSPD / BCSO / SASP / SAPR
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </PageContainer>
            </section>

            <!-- SECTION 2: Live Officers & Recent Patrol Replays Section -->
            <section
                id="units-grid"
                v-reveal
                class="w-full bg-card/40 pt-10 sm:pt-14 pb-12 sm:pb-16 scroll-mt-6 relative z-10"
            >
                <PageContainer class="space-y-8">
                    <!-- Ringkasan Statistik Taktis (1 baris TacticalFrame variant="panel") -->
                    <TacticalFrame
                        variant="panel"
                        accent="primary"
                        inner-class="p-4 sm:p-5"
                    >
                        <div
                            class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4"
                        >
                            <StatTile
                                :label="$t('dashboard.on_duty_feeds')"
                                :value="allActiveStreams.length"
                                :sublabel="$t('dashboard.active')"
                                :icon="Radio"
                            />
                            <StatTile
                                label="Total Penonton Live"
                                :value="totalLiveViewers"
                                sublabel="Penonton Aktif"
                                :icon="Eye"
                            />
                            <StatTile
                                :label="$t('dashboard.total_officers')"
                                :value="
                                    allActiveStreams.length +
                                    offlineOfficers.length
                                "
                                :sublabel="$t('dashboard.officers_unit')"
                                :icon="Shield"
                            />
                            <StatTile
                                :label="$t('dashboard.replay_archive')"
                                :value="recentReplays.length"
                                :sublabel="$t('dashboard.videos_unit')"
                                :icon="Film"
                            />
                        </div>
                    </TacticalFrame>

                    <TacticalStreamSlimles
                        :is-syncing-feeds="isSyncingFeeds"
                        :all-active-streams="allActiveStreams"
                        :trending-streams="trendingStreams"
                        :recent-replay-streams="recentReplayStreams"
                        :tac-channels="tacChannels"
                        @play-stream-in-focus="playStreamInFocus"
                        @trigger-manual-sync="triggerManualSync"
                    />
                </PageContainer>
            </section>

            <!-- SECTION 3: Monthly Patrol Streaming Leaderboard Section -->
            <section
                id="leaderboard-section"
                v-reveal
                class="w-full bg-background pt-12 sm:pt-16 pb-16 sm:pb-24 relative z-10"
            >
                <PageContainer>
                    <!-- Informative Clipper Beta Access Banner -->
                    <TacticalClipperBetaBanner
                        @open-claim-modal="showClipperClaimModal = true"
                    />

                    <TacticalMonthlyLeaderboard
                        :leaderboard="monthlyLeaderboard"
                    />
                </PageContainer>
            </section>

            <!-- SECTION 4: 1K Sub Support Section -->
            <section
                id="support-section"
                v-reveal
                class="w-full bg-card/40 py-12 sm:py-16 relative z-10"
            >
                <PageContainer>
                    <TacticalSubSupportGrid :officers="supportOfficers" />
                </PageContainer>
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

        <Toaster rich-colors position="top-right" />
    </div>
</template>
