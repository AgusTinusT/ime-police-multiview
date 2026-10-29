<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";

// Refactored Sub-Components
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";
import HeroCarousel from "@/Components/Hero/HeroCarousel.vue";
import { PageContainer } from "@/Components/tactical";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import TacticalClipperBetaBanner from "@/Components/TacticalClipperBetaBanner.vue";
import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import TacticalMonthlyLeaderboard from "@/Components/TacticalMonthlyLeaderboard.vue";
import TacticalStreamSlimles from "@/Components/TacticalStreamSlimles.vue";
import TacticalSubSupportGrid from "@/Components/TacticalSubSupportGrid.vue";
import { Toaster } from "@/Components/ui/sonner";
import { useYouTubePlayer } from "@/Composables/useYouTubePlayer";
import { parseRelativeTimeToSeconds } from "@/Utils/timeHelpers";
import { RefreshCw } from "@lucide/vue";

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
const showOfficerClaimModal = ref(false);

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
// Cinematic Default Hero Slides (Fallback when database hero_slides has 0 active slides)
const defaultHeroSlides = computed(() => [
    {
        id: "slide-bodycam",
        slide_type: "standard",
        title: "IME POLICE BODYCAM STREAMING",
        subtitle:
            "Pusat pemantauan taktis & live streaming bodycam perwira LSPD, BCSO, SASP, dan SAPR secara bersamaan dalam mode multiview interaktif.",
        image_desktop_url: "/images/hero-fallback.jpg",
        image_alt: "IME Police Bodycam Streaming",
        badge_label: "LIVE MULTIVIEW • SASP COMMAND",
        accent: "primary",
        cta_primary: {
            label: "Buka Multi-Bodycam",
            url: "/multiview",
        },
        cta_secondary: {
            label: "Jelajahi Feed Patroli",
            url: "#units-grid",
        },
    },
    {
        id: "slide-clips",
        slide_type: "standard",
        title: "TACTICAL CLIPS STUDIO V2",
        subtitle:
            "Potong momen aksi pengejaran dan situasi tembak-menembak berkecepatan tinggi langsung dari siaran perwira. Tandai multi-cut dan ekspor video 1080p tanpa watermark ke media sosial.",
        image_desktop_url: "/images/hero-fallback.jpg",
        image_alt: "Tactical Clips Studio V2",
        badge_label: "FITUR BARU • AKSES BETA GRATIS",
        accent: "info",
        cta_primary: {
            label: "Coba Clips Studio",
            url: "/clips",
        },
        cta_secondary: {
            label: "Panduan Fitur Clipper",
            url: "/clipper-pricing",
        },
    },
    {
        id: "slide-biolink",
        slide_type: "standard",
        title: "OFFICER TACTICAL BIO-LINK HUB",
        subtitle:
            "Verifikasi kepemilikan channel YouTube dinas secara otomatis tanpa antre approval admin. Klaim profil resmi berformat /@callsign, tampilkan jadwal patroli, dan terima tactical donation.",
        image_desktop_url: "/images/hero-fallback.jpg",
        image_alt: "Officer Tactical Bio-Link Hub",
        badge_label: "SASP VERIFIED ID • BIO-LINK",
        accent: "warning",
        cta_primary: {
            label: "Direktori & Klaim Perwira",
            url: "/officers",
        },
        cta_secondary: {
            label: "Pelajari Fitur Bio-Link",
            url: "/faq",
        },
    },
]);

const effectiveHeroSlides = computed(() => {
    if (props.heroSlides && props.heroSlides.length > 0) {
        return props.heroSlides;
    }
    return defaultHeroSlides.value;
});
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
            <!-- CINEMATIC HERO SLIDER CAROUSEL (Netflix Style) -->
            <HeroCarousel :slides="effectiveHeroSlides" />

            <!-- SECTION 2: Live Officers & Recent Patrol Replays Section -->
            <section
                id="units-grid"
                v-reveal
                class="w-full bg-card/40 pt-8 sm:pt-12 pb-12 sm:pb-16 scroll-mt-6 relative z-10"
            >
                <PageContainer>
                    <TacticalStreamSlimles
                        :is-syncing-feeds="isSyncingFeeds"
                        :all-active-streams="allActiveStreams"
                        :trending-streams="trendingStreams"
                        :recent-replay-streams="recentReplayStreams"
                        :tac-channels="tacChannels"
                        :total-live-viewers="totalLiveViewers"
                        :total-officers="allActiveStreams.length + offlineOfficers.length"
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

        <!-- Officer Profile Claim Modal -->
        <OfficerClaimModal
            :show="showOfficerClaimModal"
            @close="showOfficerClaimModal = false"
        />

        <Toaster rich-colors position="top-right" />
    </div>
</template>
