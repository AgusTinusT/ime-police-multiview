<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ChevronRight, MonitorPlay } from "@lucide/vue";
import { computed, ref, onMounted } from "vue";
import axios from "axios";

// Refactored Sub-Components
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";
import HeroCarousel from "@/Components/Hero/HeroCarousel.vue";
import { LiveBadge } from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import CatalogCard from "@/Components/CatalogCard.vue";
import CatalogRow from "@/Components/CatalogRow.vue";
import ClipCatalogCard from "@/Components/ClipCatalogCard.vue";
import SupportCatalogCard from "@/Components/SupportCatalogCard.vue";
import OfficerDetailModal from "@/Components/OfficerDetailModal.vue";
import { Toaster } from "@/Components/ui/sonner";
import { useYouTubePlayer } from "@/Composables/useYouTubePlayer";
import { parseRelativeTimeToSeconds } from "@/Utils/timeHelpers";

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
const totalLiveViewers = computed(() =>
    streams.value.reduce(
        (sum, s) => sum + Number(s.viewers_count ?? s.viewer_count ?? 0),
        0,
    ),
);

// Netflix-style catalog rows
const deptOf = (s) => {
    const d = (s?.officer?.department || s?.department || "").toUpperCase();
    return d === "PARK RANGER" ? "SAPR" : d;
};
const nameOf = (s) => s.officer?.officer_name || s.officer?.streamer_name || s.officer_name || "Officer";
const mvUrl = (ids) => `/multiview?v=${ids.filter(Boolean).join(",")}`;
const liveByViewers = computed(() =>
    [...streams.value].sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0)),
);
const deptRows = computed(() =>
    ["LSPD", "BCSO", "SASP", "SAPR"]
        .map((d) => ({ dept: d, items: liveByViewers.value.filter((s) => deptOf(s) === d) }))
        .filter((r) => r.items.length),
);
const tacRows = computed(() => {
    const byId = Object.fromEntries(streams.value.map((s) => [s.video_id, s]));
    return (tacChannels.value || [])
        .map((ch) => ({ ...ch, units: (ch.video_ids || []).map(String).map((id) => byId[id]).filter(Boolean) }))
        .filter((ch) => ch.units.length);
});
// Officers with no patrol hours yet this month would only show "0 Jam".
const top10 = computed(() =>
    (props.monthlyLeaderboard?.all_top || []).filter((o) => Number(o.total_hours_num) > 0).slice(0, 10),
);
const roadTo1kOfficers = computed(() => {
    if (!Array.isArray(props.supportOfficers) || !props.supportOfficers.length) {
        return [];
    }
    return [...props.supportOfficers]
        .sort((a, b) => (Number(b.subscriber_count) || 0) - (Number(a.subscriber_count) || 0))
        .slice(0, 12);
});

// Multiview tray: pick POVs from any row, then open them together.
const tray = ref([]);
const inTray = (id) => tray.value.some((t) => t.video_id === id);
const toggleTray = (s) => {
    if (inTray(s.video_id)) tray.value = tray.value.filter((t) => t.video_id !== s.video_id);
    else if (tray.value.length < 9) tray.value = [...tray.value, s];
};
const detail = ref(null);
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
        image_desktop_url: "/images/cars-police-twopov.jpg",
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
        image_desktop_url: "/images/car-police-crash.jpg",
        image_mobile_url: "/images/car-police-crash-square.jpg",
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
        image_desktop_url: "/images/car-police-officer.jpg",
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

const communityClips = ref([]);

const fetchCommunityClips = async () => {
    try {
        const response = await axios.get("/api/v1/tac-clips");
        if (response.data && Array.isArray(response.data.data)) {
            communityClips.value = response.data.data.slice(0, 12);
        } else if (Array.isArray(response.data)) {
            communityClips.value = response.data.slice(0, 12);
        }
    } catch {
        // Gagal -> baris disembunyikan tanpa toast
        communityClips.value = [];
    }
};

onMounted(() => {
    fetchCommunityClips();
});
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
            transparent-at-top
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
            <div class="-mt-[57px] sm:-mt-[65px]">
                <HeroCarousel :slides="effectiveHeroSlides" />
            </div>

            <!-- SECTION 2: Catalog rows (Netflix-style browsing) -->
            <div id="units-grid" class="relative z-10 scroll-mt-6 space-y-8 py-8 sm:space-y-10 sm:py-12">
                <CatalogRow v-if="liveByViewers.length" :title="$t('catalog.live_now')" :subtitle="$t('catalog.live_summary', { units: liveByViewers.length, viewers: totalLiveViewers })">
                    <template #action>
                        <Button as-child variant="link" size="sm" class="ml-auto shrink-0">
                            <Link :href="mvUrl(liveByViewers.slice(0, 9).map((s) => s.video_id))">{{ $t('catalog.watch_all') }} <ChevronRight /></Link>
                        </Button>
                    </template>
                    <CatalogCard
                        v-for="s in liveByViewers"
                        :key="`live-${s.video_id}`"
                        :stream="s"
                        :href="mvUrl([s.video_id])"
                        kind="live"
                        :in-tray="inTray(s.video_id)"
                        @toggle-tray="toggleTray"
                        @detail="detail = $event"
                    />
                </CatalogRow>

                <CatalogRow
                    v-if="communityClips.length"
                    title="Momen aksi komunitas"
                >
                    <template #action>
                        <Button as-child variant="link" size="sm" class="ml-auto shrink-0">
                            <Link href="/action-clips">Lihat semua <ChevronRight /></Link>
                        </Button>
                    </template>
                    <ClipCatalogCard
                        v-for="c in communityClips"
                        :key="`clip-${c.id}`"
                        :clip="c"
                    />
                </CatalogRow>

                <CatalogRow v-for="ch in tacRows" :key="`tac-${ch.code}`" :title="$t('catalog.active_incident', { name: ch.name })" :subtitle="$t('catalog.active_incident_subtitle')">
                    <template #action>
                        <Button as-child variant="link" size="sm" class="ml-auto shrink-0">
                            <Link :href="mvUrl(ch.units.map((u) => u.video_id))">{{ $t('catalog.watch_povs', { count: ch.units.length }) }} <ChevronRight /></Link>
                        </Button>
                    </template>
                    <CatalogCard
                        v-for="s in ch.units"
                        :key="`tac-${ch.code}-${s.video_id}`"
                        :stream="s"
                        :href="mvUrl(ch.units.map((u) => u.video_id))"
                        kind="tac"
                        :in-tray="inTray(s.video_id)"
                        @toggle-tray="toggleTray"
                        @detail="detail = $event"
                    />
                </CatalogRow>

                <CatalogRow
                    v-if="top10.length"
                    :title="monthlyLeaderboard?.is_previous_period ? $t('catalog.top_10_patrols_last_month') : $t('catalog.top_10_patrols')"
                    :subtitle="monthlyLeaderboard?.period_text || ''"
                >
                    <a
                        v-for="o in top10"
                        :key="`top-${o.id}`"
                        :href="o.youtube_url || '/officers'"
                        :target="o.youtube_url ? '_blank' : null"
                        rel="noopener"
                        class="group relative flex h-40 w-44 shrink-0 snap-start items-end rounded-md outline-none focus-visible:ring-3 focus-visible:ring-ring/50 sm:h-48 sm:w-52"
                    >
                        <span class="rank-number -mr-6 select-none text-9xl font-extrabold leading-none" aria-hidden="true">{{ o.rank }}</span>
                        <span class="relative aspect-2/3 w-28 overflow-hidden rounded-md bg-muted motion-safe:transition-transform motion-safe:duration-300 group-hover:scale-105 sm:w-32">
                            <img :src="o.avatar_url" :alt="o.officer_name" loading="lazy" class="size-full object-cover" />
                            <span class="absolute inset-x-0 bottom-0 bg-linear-to-t from-background/90 to-transparent p-2">
                                <span class="block truncate text-xs font-semibold text-foreground">{{ o.officer_name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ o.total_hours }}</span>
                            </span>
                            <LiveBadge v-if="o.is_live" class="absolute left-1.5 top-1.5" />
                        </span>
                    </a>
                </CatalogRow>

                <CatalogRow v-for="r in deptRows" :key="`dept-${r.dept}`" :title="$t('catalog.dept_patrol', { dept: r.dept })">
                    <CatalogCard
                        v-for="s in r.items"
                        :key="`dept-${r.dept}-${s.video_id}`"
                        :stream="s"
                        :href="mvUrl([s.video_id])"
                        kind="live"
                        size="sm"
                        :in-tray="inTray(s.video_id)"
                        @toggle-tray="toggleTray"
                        @detail="detail = $event"
                    />
                </CatalogRow>

                <CatalogRow
                    v-if="roadTo1kOfficers.length"
                    title="Road to 1K: dukung mereka"
                >
                    <template #action>
                        <Button as-child variant="link" size="sm" class="ml-auto shrink-0">
                            <Link href="/officers">Lihat semua <ChevronRight /></Link>
                        </Button>
                    </template>
                    <SupportCatalogCard
                        v-for="o in roadTo1kOfficers"
                        :key="`road1k-${o.id}`"
                        :officer="o"
                    />
                </CatalogRow>

                <CatalogRow v-if="recentReplayStreams.length" :title="$t('catalog.recent_replays')">
                    <CatalogCard
                        v-for="s in recentReplayStreams.slice(0, 15)"
                        :key="`rep-${s.video_id}`"
                        :stream="s"
                        :href="mvUrl([s.video_id])"
                        kind="replay"
                        size="sm"
                        :in-tray="inTray(s.video_id)"
                        @toggle-tray="toggleTray"
                        @detail="detail = $event"
                    />
                </CatalogRow>
            </div>
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
        <OfficerDetailModal
            :stream="detail"
            :streams="streams"
            :tac-channels="tacChannels"
            :in-tray="!!detail && inTray(detail.video_id)"
            @close="detail = null"
            @toggle-tray="toggleTray"
        />
        <div
            v-if="tray.length"
            class="fixed inset-x-3 bottom-36 z-40 sm:bottom-20 md:inset-x-auto md:bottom-5 md:left-1/2 md:w-140 md:-translate-x-1/2"
        >
            <div class="flex items-center gap-3 rounded-xl border border-border bg-popover p-2 pl-4 text-popover-foreground shadow-lg">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">{{ $t('catalog.pov_selected', { count: tray.length }) }}</p>
                    <p class="truncate text-xs text-muted-foreground">{{ tray.map(nameOf).join(", ") }}</p>
                </div>
                <Button variant="ghost" size="sm" class="shrink-0" @click="tray = []">{{ $t('catalog.clear_tray') }}</Button>
                <Button as-child size="sm" class="shrink-0">
                    <Link :href="mvUrl(tray.map((t) => t.video_id))"><MonitorPlay /> {{ $t('catalog.open_multiview') }}</Link>
                </Button>
            </div>
        </div>

        <Toaster rich-colors position="top-right" />
    </div>
</template>

<style scoped>
.rank-number {
    color: var(--color-background);
    -webkit-text-stroke: 3px var(--color-muted-foreground);
}
</style>
