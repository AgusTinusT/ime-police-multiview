<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ChevronRight, Loader2, MonitorPlay, SlidersHorizontal } from "@lucide/vue";
import { computed, ref, onMounted } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";
import OfficerVisibilityModal from "@/Components/OfficerVisibilityModal.vue";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

// Refactored Sub-Components
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";
import AuthModal from "@/Components/AuthModal.vue";
import SquadCatalogCard from "@/Components/Squad/SquadCatalogCard.vue";
import SquadSaveDialog from "@/Components/Squad/SquadSaveDialog.vue";
import RecommendedSiteCard from "@/Components/Recommended/RecommendedSiteCard.vue";
import HeroCarousel from "@/Components/Hero/HeroCarousel.vue";
import AnnouncementBanner from "@/Components/AnnouncementBanner.vue";
import { LiveBadge, OfficerAvatar } from "@/Components/tactical";
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
    recommendedSites: {
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
const showVisibilityModal = ref(false);

const { isOfficerDisabled, hideOfficer } = useOfficerFilter();

// Filtered streams & officers based on user hidden list
const visibleStreams = computed(() =>
    streams.value.filter((s) => !isOfficerDisabled(s))
);

const allOfficersList = computed(() => {
    const list = [];
    const seen = new Set();
    streams.value.forEach((s) => {
        if (s.officer?.id && !seen.has(s.officer.id)) {
            seen.add(s.officer.id);
            list.push(s.officer);
        }
    });
    offlineOfficers.value.forEach((o) => {
        if (o.id && !seen.has(o.id)) {
            seen.add(o.id);
            list.push(o);
        }
    });
    return list;
});

// Computed All Active & Catalog Streams
const totalLiveViewers = computed(() =>
    visibleStreams.value.reduce(
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
    [...visibleStreams.value].sort((a, b) => (b.viewers_count || 0) - (a.viewers_count || 0)),
);
const DEPARTMENTS = ["LSPD", "BCSO", "SASP", "SAPR"];
const deptDot = { LSPD: "bg-dept-lspd", BCSO: "bg-dept-bcso", SASP: "bg-dept-sasp", SAPR: "bg-dept-sapr" };
const deptGroups = computed(() => {
    return DEPARTMENTS.map((dept) => {
        const items = liveByViewers.value.filter((s) => deptOf(s) === dept);
        return {
            dept,
            items,
            count: items.length,
        };
    });
});
const totalDeptLive = computed(() =>
    deptGroups.value.reduce((acc, curr) => acc + curr.count, 0),
);
const selectedDept = ref(null);
const activeDept = computed({
    get() {
        if (selectedDept.value && DEPARTMENTS.includes(selectedDept.value)) {
            return selectedDept.value;
        }
        // Default: dept with most live streams (tiebreak: LSPD -> BCSO -> SASP -> SAPR)
        let maxCount = -1;
        let chosen = DEPARTMENTS[0];
        for (const stat of deptGroups.value) {
            if (stat.count > maxCount) {
                maxCount = stat.count;
                chosen = stat.dept;
            }
        }
        return chosen;
    },
    set(val) {
        selectedDept.value = val;
    },
});
const activeDeptStreams = computed(() => {
    const found = deptGroups.value.find((s) => s.dept === activeDept.value);
    return found ? found.items : [];
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
        .filter((o) => !isOfficerDisabled(o))
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
    const list = [...(recentReplays.value || [])].filter((r) => !isOfficerDisabled(r));
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
        image_desktop_url: "/images/car-police-crash.webp",
        image_mobile_url: "/images/car-police-crash-square.webp",
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
        image_desktop_url: "/images/car-police-officer.webp",
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
    const raw = props.heroSlides && props.heroSlides.length > 0
        ? props.heroSlides
        : defaultHeroSlides.value;
    return raw.filter((slide) => {
        if (slide.officer_id && isOfficerDisabled(slide.officer_id)) return false;
        if (slide.officer && isOfficerDisabled(slide.officer)) return false;
        return true;
    });
});
const displayedGridStreams = computed(() => visibleStreams.value);
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

const page = usePage();
const publicSquads = ref([]);
const mySquads = ref([]);
const showSquadAuthModal = ref(false);
const editingSquad = ref(null);
const showSquadDialog = ref(false);

const fetchMySquads = async () => {
    if (!page.props.auth?.user) {
        mySquads.value = [];
        return;
    }
    try {
        const res = await axios.get("/api/v1/squads/mine");
        if (res.data?.status === "success" && Array.isArray(res.data.data)) {
            mySquads.value = res.data.data;
        }
    } catch {
        mySquads.value = [];
    }
};

const openEditSquad = (squad) => {
    editingSquad.value = squad;
    showSquadDialog.value = true;
};

const onSquadSaved = (updated) => {
    // Perbarui di mySquads
    const myIdx = mySquads.value.findIndex((s) => s.id === updated.id);
    if (myIdx !== -1) {
        mySquads.value[myIdx] = updated;
    } else {
        mySquads.value.unshift(updated);
    }

    // Perbarui juga di publicSquads jika ada
    const pubIdx = publicSquads.value.findIndex((s) => s.id === updated.id);
    if (pubIdx !== -1) {
        publicSquads.value[pubIdx] = updated;
    }

    showSquadDialog.value = false;
    editingSquad.value = null;
};

const fetchPublicSquads = async () => {
    try {
        const res = await axios.get("/api/v1/squads/public");
        if (res.data?.status === "success" && Array.isArray(res.data.data)) {
            publicSquads.value = res.data.data;
        }
    } catch {
        // Gagal -> baris disembunyikan
        publicSquads.value = [];
    }
};

const squadToFork = ref(null);
const showForkConfirm = ref(false);
const isForking = ref(false);

const handleForkSquad = (squad) => {
    if (!page.props.auth?.user) {
        showSquadAuthModal.value = true;
        return;
    }
    squadToFork.value = squad;
    showForkConfirm.value = true;
};

const confirmForkSquad = async () => {
    if (!squadToFork.value) return;
    isForking.value = true;
    try {
        const res = await axios.post(`/api/v1/squads/${squadToFork.value.id}/fork`);
        toast.success("Squad berhasil di-fork.");
        const newSquad = res.data?.data;
        if (newSquad?.slug) {
            window.location.href = `/multiview?squad=${newSquad.slug}`;
        }
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal melakukan fork squad.");
    } finally {
        isForking.value = false;
        showForkConfirm.value = false;
    }
};

const squadToDelete = ref(null);
const showDeleteConfirm = ref(false);
const isDeletingSquad = ref(false);

const handleDeleteSquad = (squad) => {
    squadToDelete.value = squad;
    showDeleteConfirm.value = true;
};

const confirmDeleteSquad = async () => {
    if (!squadToDelete.value) return;
    isDeletingSquad.value = true;
    try {
        await axios.delete(`/api/v1/squads/${squadToDelete.value.id}`);
        toast.success("Squad berhasil dihapus.");
        fetchMySquads();
        fetchPublicSquads();
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menghapus squad.");
    } finally {
        isDeletingSquad.value = false;
        showDeleteConfirm.value = false;
    }
};

const onSquadDeleted = () => {
    fetchMySquads();
    fetchPublicSquads();
};

// Admin-managed announcement banner (shown above the hero carousel).
const announcements = ref([]);
const fetchAnnouncements = async () => {
    try {
        const res = await axios.get("/api/v1/active-announcements");
        announcements.value = Array.isArray(res.data?.data) ? res.data.data : [];
    } catch {
        announcements.value = [];
    }
};

// recommendedSites arrives in the Inertia payload, so no extra request.
const recommendedSites = computed(() => props.recommendedSites || []);

onMounted(() => {
    fetchCommunityClips();
    fetchMySquads();
    fetchPublicSquads();
    fetchAnnouncements();
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
            <!-- Admin announcement sits under the header, above the cinematic hero -->
            <div v-if="announcements.length" class="relative z-20 px-3 pt-3 sm:px-6">
                <AnnouncementBanner :announcements="announcements" />
            </div>

            <!-- CINEMATIC HERO SLIDER CAROUSEL (Netflix Style) — pulled behind
                 the header only when no announcement is shown above it. -->
            <div :class="announcements.length ? 'mt-4' : '-mt-[57px] sm:-mt-[65px]'">
                <HeroCarousel :slides="effectiveHeroSlides" />
            </div>

            <!-- SECTION 2: Catalog rows (Netflix-style browsing) -->
            <div id="units-grid" class="relative z-10 scroll-mt-6 space-y-8 py-8 sm:space-y-10 sm:py-12">
                <CatalogRow v-if="liveByViewers.length" :title="$t('catalog.live_now')" :subtitle="$t('catalog.live_summary', { units: liveByViewers.length, viewers: totalLiveViewers })">
                    <template #action>
                        <div class="ml-auto flex items-center gap-1.5 shrink-0">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 gap-1.5 text-xs font-mono"
                                :title="$t('catalog.manage_visibility')"
                                @click="showVisibilityModal = true"
                            >
                                <SlidersHorizontal class="size-3.5" />
                                <span class="hidden sm:inline">Filter Visibilitas</span>
                            </Button>
                            <Button as-child variant="link" size="sm" class="shrink-0">
                                <Link :href="mvUrl(liveByViewers.slice(0, 9).map((s) => s.video_id))">{{ $t('catalog.watch_all') }} <ChevronRight /></Link>
                            </Button>
                        </div>
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
                        @hide="hideOfficer"
                    />
                </CatalogRow>

                <!-- Squad Saya (SQD-01) -->
                <CatalogRow
                    v-if="mySquads.length"
                    :title="$t('squad.my_squads')"
                    :subtitle="$t('squad.my_squads_subtitle')"
                >
                    <SquadCatalogCard
                        v-for="sq in mySquads"
                        :key="sq.id"
                        :squad="sq"
                        :live-streams="streams"
                        @fork="handleForkSquad"
                        @edit="openEditSquad"
                        @delete="handleDeleteSquad"
                    />
                </CatalogRow>

                <!-- Squad Komunitas (SQ-02) -->
                <CatalogRow
                    v-if="publicSquads.length"
                    :title="$t('squad.community_squads')"
                    :subtitle="$t('squad.community_subtitle')"
                >
                    <SquadCatalogCard
                        v-for="sq in publicSquads"
                        :key="sq.id"
                        :squad="sq"
                        :live-streams="streams"
                        @fork="handleForkSquad"
                        @edit="openEditSquad"
                        @delete="handleDeleteSquad"
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

                <!-- Website Rekomendasi (REC-01) -->
                <CatalogRow
                    v-if="recommendedSites.length"
                    :title="$t('dashboard.recommended_sites_title')"
                    :subtitle="$t('dashboard.recommended_sites_subtitle')"
                >
                    <RecommendedSiteCard
                        v-for="site in recommendedSites"
                        :key="site.id"
                        :site="site"
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
                            <img
                                v-if="o.avatar_url"
                                :src="o.avatar_url"
                                :alt="o.officer_name"
                                loading="lazy"
                                class="size-full object-cover"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center bg-card/60"
                            >
                                <OfficerAvatar
                                    :name="o.officer_name"
                                    size="lg"
                                />
                            </div>
                            <span class="absolute inset-x-0 bottom-0 bg-linear-to-t from-background/90 to-transparent p-2">
                                <span class="block truncate text-xs font-semibold text-foreground">{{ o.officer_name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ o.total_hours }}</span>
                            </span>
                            <LiveBadge v-if="o.is_live" class="absolute left-1.5 top-1.5" />
                        </span>
                    </a>
                </CatalogRow>

                <CatalogRow
                    v-if="totalDeptLive > 0"
                    :title="$t('catalog.patrol_by_dept')"
                >
                    <template #action>
                        <div role="tablist" class="no-scrollbar ml-2 flex items-center gap-4 overflow-x-auto">
                            <button
                                v-for="stat in deptGroups"
                                :key="`dept-chip-${stat.dept}`"
                                type="button"
                                role="tab"
                                :aria-selected="activeDept === stat.dept"
                                :class="[
                                    'flex shrink-0 items-center gap-1.5 border-b-2 pb-0.5 text-sm font-semibold transition-colors',
                                    activeDept === stat.dept
                                        ? 'border-foreground text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                ]"
                                @click="activeDept = stat.dept"
                            >
                                <span class="size-1.5 rounded-full" :class="deptDot[stat.dept]" />
                                {{ stat.dept }}
                                <span class="text-xs font-normal text-muted-foreground">{{ stat.count }}</span>
                            </button>
                        </div>
                    </template>

                    <template v-if="activeDeptStreams.length">
                        <CatalogCard
                            v-for="s in activeDeptStreams"
                            :key="`dept-${activeDept}-${s.video_id}`"
                            :stream="s"
                            :href="mvUrl([s.video_id])"
                            kind="live"
                            size="sm"
                            :in-tray="inTray(s.video_id)"
                            @toggle-tray="toggleTray"
                            @detail="detail = $event"
                            @hide="hideOfficer"
                        />
                    </template>
                    <div
                        v-else
                        class="flex w-72 shrink-0 snap-start items-center justify-center rounded-md border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ $t('catalog.no_live_units', { dept: activeDept }) }}
                    </div>
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
                        @hide="hideOfficer"
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

        <AuthModal
            :is-open="showSquadAuthModal"
            @close="showSquadAuthModal = false"
        />

        <SquadSaveDialog
            v-model:open="showSquadDialog"
            :squad="editingSquad"
            @saved="onSquadSaved"
            @deleted="onSquadDeleted"
        />

        <!-- ALERT DIALOG: FORK CONFIRMATION -->
        <AlertDialog :open="showForkConfirm" @update:open="showForkConfirm = $event">
            <AlertDialogContent class="font-mono text-xs z-[70]">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ $t('squad.fork_confirm_title') }}
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        Apakah Anda ingin menduplikasi squad <strong>"{{ squadToFork?.name }}"</strong> ke dalam koleksi squad pribadi Anda?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter class="gap-2">
                    <AlertDialogCancel :disabled="isForking">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="font-bold"
                        :disabled="isForking"
                        @click="confirmForkSquad"
                    >
                        <Loader2 v-if="isForking" class="size-3.5 motion-safe:animate-spin mr-1.5" />
                        {{ isForking ? "Menduplikasi..." : $t('squad.fork_confirm_action') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ALERT DIALOG: DELETE SQUAD CONFIRMATION -->
        <AlertDialog :open="showDeleteConfirm" @update:open="showDeleteConfirm = $event">
            <AlertDialogContent class="font-mono text-xs z-[70]">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ $t('squad.delete_confirm_title') }}
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        Apakah Anda yakin ingin menghapus squad <strong>"{{ squadToDelete?.name }}"</strong>? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter class="gap-2">
                    <AlertDialogCancel :disabled="isDeletingSquad">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90 font-bold"
                        :disabled="isDeletingSquad"
                        @click="confirmDeleteSquad"
                    >
                        <Loader2 v-if="isDeletingSquad" class="size-3.5 motion-safe:animate-spin mr-1.5" />
                        {{ isDeletingSquad ? "Menghapus..." : $t('squad.delete_confirm_action') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
        <!-- OFFICER VISIBILITY MODAL -->
        <OfficerVisibilityModal
            :open="showVisibilityModal"
            :initial-officers="allOfficersList"
            @update:open="showVisibilityModal = $event"
            @close="showVisibilityModal = false"
        />
    </div>
</template>

<style scoped>
.no-scrollbar {
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.rank-number {
    color: var(--color-background);
    -webkit-text-stroke: 3px var(--color-muted-foreground);
}
</style>
