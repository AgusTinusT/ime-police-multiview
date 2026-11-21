<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ChevronRight, History, Loader2, SlidersHorizontal } from "@lucide/vue";
import { computed, ref, onMounted, watch } from "vue";
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
import NewOfficerCard from "@/Components/NewOfficerCard.vue";
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
    newOfficers: {
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
const mvUrl = (ids) => `/multiview?v=${ids.filter(Boolean).join(",")}`;
// Klik kartu membuka halaman tonton (layout ala YouTube) di situs sendiri.
const watchUrl = (s) => `/watch/${s.video_id}`;
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
// Department filter on the "Sedang live" row (null = semua departemen).
const selectedDept = ref(null);
const liveDeptChips = computed(() => deptGroups.value.filter((g) => g.count > 0));
const liveFiltered = computed(() => {
    const group = selectedDept.value && deptGroups.value.find((g) => g.dept === selectedDept.value);
    return group ? group.items : liveByViewers.value;
});
// If the chosen department goes offline, fall back to all units.
watch(liveDeptChips, (chips) => {
    if (selectedDept.value && !chips.some((c) => c.dept === selectedDept.value)) selectedDept.value = null;
});
// Few units live: top the row up with streams that just ended so it doesn't sit
// mostly empty. Only on the unfiltered view; a department filter shows just that dept.
const LIVE_ROW_TARGET = 8;
const liveFillReplays = computed(() => {
    if (selectedDept.value) return [];
    const missing = LIVE_ROW_TARGET - liveFiltered.value.length;
    return missing > 2 ? recentReplayStreams.value.slice(0, missing) : [];
});
// Officers with no patrol hours yet this month would only show "0 Jam".
const top10 = computed(() =>
    (props.monthlyLeaderboard?.all_top || []).filter((o) => Number(o.total_hours_num) > 0).slice(0, 10),
);
// Avatar URLs can expire (YouTube CDN); show the initials card instead of an empty tile.
const brokenTopAvatars = ref(new Set());
const markTopAvatarBroken = (id) => {
    brokenTopAvatars.value = new Set(brokenTopAvatars.value).add(id);
};
const roadTo1kOfficers = computed(() => {
    if (!Array.isArray(props.supportOfficers) || !props.supportOfficers.length) {
        return [];
    }
    return [...props.supportOfficers]
        .filter((o) => !isOfficerDisabled(o))
        .sort((a, b) => (Number(b.subscriber_count) || 0) - (Number(a.subscriber_count) || 0))
        .slice(0, 12);
});

// Officer yang baru ditambahkan (<= 14 hari) — server sudah memfilter batas waktunya.
const recentlyAddedOfficers = computed(() =>
    (props.newOfficers || []).filter((o) => !isOfficerDisabled(o)),
);
// 1-3 new officers: compact horizontal cards spread across the row (empty list hides the row).
const newOfficersCompact = computed(() => recentlyAddedOfficers.value.length <= 3);

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
// "Rekaman terbaru" skips replays already shown at the end of the live row.
const replayRowStreams = computed(() => {
    const shown = new Set(liveFillReplays.value.map((r) => r.video_id));
    return recentReplayStreams.value.filter((r) => !shown.has(r.video_id)).slice(0, 15);
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

// One squad row: the user's own squads first, then public ones they don't own.
// A public squad of the user's would otherwise show up twice.
const allSquads = computed(() => {
    const mine = mySquads.value.map((s) => ({ ...s, is_owner: true }));
    const mineIds = new Set(mine.map((s) => s.id));
    return [...mine, ...publicSquads.value.filter((s) => !mineIds.has(s.id))];
});

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
                <CatalogRow
                    v-if="liveByViewers.length"
                    :title="$t('catalog.live_now')"
                    :subtitle="$t('catalog.live_summary', { units: liveByViewers.length, viewers: totalLiveViewers })"
                >
                    <template v-if="liveDeptChips.length > 1" #filters>
                        <div role="tablist" :aria-label="$t('catalog.patrol_by_dept')" class="no-scrollbar flex items-center gap-4 overflow-x-auto">
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="!selectedDept"
                                :class="[
                                    'flex shrink-0 items-center gap-1.5 border-b-2 pb-0.5 text-sm font-semibold transition-colors',
                                    !selectedDept ? 'border-foreground text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground',
                                ]"
                                @click="selectedDept = null"
                            >
                                {{ $t('catalog.all_depts') }}
                                <span class="text-xs font-normal text-muted-foreground">{{ liveByViewers.length }}</span>
                            </button>
                            <button
                                v-for="stat in liveDeptChips"
                                :key="`dept-chip-${stat.dept}`"
                                type="button"
                                role="tab"
                                :aria-selected="selectedDept === stat.dept"
                                :class="[
                                    'flex shrink-0 items-center gap-1.5 border-b-2 pb-0.5 text-sm font-semibold transition-colors',
                                    selectedDept === stat.dept ? 'border-foreground text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground',
                                ]"
                                @click="selectedDept = stat.dept"
                            >
                                <span class="size-1.5 rounded-full" :class="deptDot[stat.dept]" />
                                {{ stat.dept }}
                                <span class="text-xs font-normal text-muted-foreground">{{ stat.count }}</span>
                            </button>
                        </div>
                    </template>
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
                                <Link :href="mvUrl(liveFiltered.slice(0, 9).map((s) => s.video_id))">{{ $t('catalog.watch_all') }} <ChevronRight /></Link>
                            </Button>
                        </div>
                    </template>
                    <CatalogCard
                        v-for="s in liveFiltered"
                        :key="`live-${s.video_id}`"
                        :stream="s"
                        :href="watchUrl(s)"
                        kind="live"
                        @detail="detail = $event"
                        @hide="hideOfficer"
                    />
                    <template v-if="liveFillReplays.length">
                        <div
                            class="flex w-24 shrink-0 snap-start flex-col items-center justify-center gap-1.5 rounded-md border border-dashed border-border px-2 text-center text-xs font-medium text-muted-foreground"
                            aria-hidden="true"
                        >
                            <History class="size-4" />
                            {{ $t('catalog.just_ended') }}
                        </div>
                        <CatalogCard
                            v-for="r in liveFillReplays"
                            :key="`live-rep-${r.video_id}`"
                            :stream="r"
                            :href="watchUrl(r)"
                            kind="replay"
                            @detail="detail = $event"
                            @hide="hideOfficer"
                        />
                    </template>
                </CatalogRow>

                <!-- Top 10 patroli bulan ini -->
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
                                v-if="o.avatar_url && !brokenTopAvatars.has(o.id)"
                                :src="o.avatar_url"
                                :alt="o.officer_name"
                                loading="lazy"
                                class="size-full object-cover"
                                @error="markTopAvatarBroken(o.id)"
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

                <!-- Officer baru ditambahkan (tampil 14 hari sejak masuk database) -->
                <CatalogRow
                    v-if="recentlyAddedOfficers.length"
                    title="Officer baru bergabung"
                    :subtitle="`${recentlyAddedOfficers.length} officer ditambahkan dalam 14 hari terakhir`"
                    :fill-cols="newOfficersCompact ? recentlyAddedOfficers.length : 0"
                >
                    <NewOfficerCard
                        v-for="o in recentlyAddedOfficers"
                        :key="`new-${o.id}`"
                        :officer="o"
                        :compact="newOfficersCompact"
                    />
                </CatalogRow>

                <!-- Squad: milik saya dulu, lalu squad komunitas (tanpa duplikat) -->
                <CatalogRow
                    v-if="allSquads.length"
                    :title="mySquads.length ? $t('squad.squads_title') : $t('squad.community_squads')"
                    :subtitle="mySquads.length ? $t('squad.squads_subtitle') : $t('squad.community_subtitle')"
                >
                    <SquadCatalogCard
                        v-for="sq in allSquads"
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

                <CatalogRow v-if="replayRowStreams.length" :title="$t('catalog.recent_replays')">
                    <CatalogCard
                        v-for="s in replayRowStreams"
                        :key="`rep-${s.video_id}`"
                        :stream="s"
                        :href="watchUrl(s)"
                        kind="replay"
                        size="sm"
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
            @close="detail = null"
        />

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
