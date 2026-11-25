<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import OfficerSubscribeButton from "@/Components/OfficerSubscribeButton.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import {
    ExternalLink,
    Search,
    Target,
    Trophy,
    UserPlus,
    Download,
    LayoutGrid,
    Link2,
    Table as TableIcon,
    X,
} from "@lucide/vue";

import logoBcso from "@/Components/Icons/LSCSD_256.jpg";
import logoLspd from "@/Components/Icons/LSPD_256.jpg";
import logoSapr from "@/Components/Icons/SAPR_256.png";
import logoSasp from "@/Components/Icons/SASP_256.jpg";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Separator } from "@/Components/ui/separator";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetFooter,
} from "@/Components/ui/sheet";
import {
    PageContainer,
    DeptBadge,
    LiveBadge,
    HiatusBadge,
    OfficerAvatar,
    EmptyState,
} from "@/Components/tactical";

const { t } = useI18n();

const props = defineProps({
    initialOfficers: {
        type: Array,
        required: true,
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

const page = usePage();
const isAdmin = computed(() => {
    return (
        !!page.props.auth?.user?.is_admin ||
        page.props.auth?.user?.role === "admin"
    );
});

// State Management
const officers = ref(props.initialOfficers);
const searchQuery = ref("");
// The directory is read one department at a time, like a chain of command.
const DEPARTMENTS = [
    { code: "LSPD", name: "Los Santos Police Department", logo: logoLspd },
    { code: "BCSO", name: "Blaine County Sheriff's Office", logo: logoBcso },
    { code: "SASP", name: "San Andreas State Police", logo: logoSasp },
    { code: "SAPR", name: "San Andreas Park Rangers", logo: logoSapr },
];
// Ranks at or above this level form the department's command staff.
const COMMAND_LEVEL = 8;

const deptOfOfficer = (o) => (o.department === "PARK RANGER" ? "SAPR" : o.department);
const deptCounts = computed(() =>
    Object.fromEntries(
        DEPARTMENTS.map((d) => [d.code, props.initialOfficers.filter((o) => deptOfOfficer(o) === d.code).length]),
    ),
);

const getInitialDept = () => {
    const fromUrl = new URLSearchParams(window.location.search).get("dept")?.toUpperCase();
    if (DEPARTMENTS.some((d) => d.code === fromUrl)) return fromUrl;
    return [...DEPARTMENTS].sort((a, b) => deptCounts.value[b.code] - deptCounts.value[a.code])[0].code;
};
const selectedDept = ref(getInitialDept());
const activeDept = computed(() => DEPARTMENTS.find((d) => d.code === selectedDept.value));

const selectDept = (code) => {
    selectedDept.value = code;
    const url = new URL(window.location.href);
    url.searchParams.set("dept", code);
    url.searchParams.delete("sort");
    window.history.replaceState(window.history.state, "", url);
};
const statusFilter = ref("all"); // 'all', 'online', 'offline'

// Read URL query parameters (e.g. /officers?sort=subs_asc)
const getInitialSort = () => {
    if (typeof window !== "undefined" && window.location.search) {
        const params = new URLSearchParams(window.location.search);
        const sortParam = params.get("sort");
        if (
            sortParam &&
            ["rank", "name"].includes(sortParam)
        ) {
            return sortParam;
        }
    }
    // Old links used ?sort=status; the directory now always defaults to rank order.
    return "rank";
};

const sortBy = ref(getInitialSort()); // 'rank' | 'name'
const viewMode = ref("grid"); // 'grid' | 'table'

// Drawer State
const activeOfficer = ref(null);

// Channel page for the profile drawer: handle first, then a real channel ID
// (seeded placeholders start with "UC_").
const activeChannelUrl = computed(() => {
    const o = activeOfficer.value;
    if (!o) return null;
    const handle = String(o.handle || "").trim();
    if (handle) return `https://www.youtube.com/${handle.startsWith("@") ? handle : `@${handle}`}`;
    const channelId = String(o.channel_id || "");
    if (channelId && !channelId.startsWith("UC_")) return `https://www.youtube.com/channel/${channelId}`;
    return null;
});
const isDrawerOpen = ref(false);

// Add Officer Modal State
const isAddModalOpen = ref(false);
const newOfficerForm = ref({
    officer_name: "",
    callsign: "",
    badge_number: "",
    department: "LSPD",
    rank: "Officer",
    station: "Central",
    handle: "",
});

const openAddModal = () => {
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    newOfficerForm.value = {
        officer_name: "",
        callsign: "",
        badge_number: "",
        department: "LSPD",
        rank: "Officer",
        station: "Central",
        handle: "",
    };
};

const handleCreateOfficer = () => {
    if (!newOfficerForm.value.officer_name.trim()) {
        toast.warning("Mohon isi nama personil / officer.");
        return;
    }
    const newOfficer = {
        id: `officer-${Date.now()}`,
        officer_name: newOfficerForm.value.officer_name,
        callsign: newOfficerForm.value.callsign || "1-ADAM-01",
        badge_number:
            newOfficerForm.value.badge_number ||
            `#${Math.floor(Math.random() * 899 + 100)}`,
        department: newOfficerForm.value.department,
        rank: newOfficerForm.value.rank || "Officer",
        station: newOfficerForm.value.station || "Central",
        handle: newOfficerForm.value.handle || "",
        streamer_name: newOfficerForm.value.officer_name,
        is_online: false,
        subscriber_count: 0,
        avatar_url: `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(newOfficerForm.value.callsign || newOfficerForm.value.officer_name)}`,
    };

    officers.value.unshift(newOfficer);
    closeAddModal();
    triggerToast(
        `Petugas ${newOfficer.officer_name} (${newOfficer.callsign}) berhasil didaftarkan!`,
    );
};

// Toast Notification State
const toastMessage = ref("");
const showToastNotification = ref(false);
let toastTimeout = null;

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToastNotification.value = true;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        showToastNotification.value = false;
    }, 3000);
};

// Open Officer Detail Drawer
const openDrawer = (officer) => {
    activeOfficer.value = officer;
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
};

// 1-Click Subscribe Popup Modal
const openSubscribePopup = (
    channelIdOrHandle,
    officerName = "",
    videoId = "",
    handle = "",
) => {
    let subUrl = "";
    const rawStr =
        typeof channelIdOrHandle === "string" ? channelIdOrHandle.trim() : "";
    const rawHandle =
        typeof handle === "string" && handle.trim()
            ? handle.trim()
            : rawStr.includes("@")
              ? rawStr
              : "";
    const rawVideoId = typeof videoId === "string" ? videoId.trim() : "";

    if (rawHandle) {
        const cleanHandle = rawHandle.startsWith("@")
            ? rawHandle
            : `@${rawHandle}`;
        subUrl = `https://www.youtube.com/${cleanHandle}?sub_confirmation=1`;
    } else if (rawStr.startsWith("@")) {
        subUrl = `https://www.youtube.com/${rawStr}?sub_confirmation=1`;
    } else if (/^UC[A-Za-z0-9_-]{22}$/.test(rawStr)) {
        subUrl = `https://www.youtube.com/channel/${rawStr}?sub_confirmation=1`;
    } else if (rawVideoId && !rawVideoId.startsWith("officer-")) {
        subUrl = `https://www.youtube.com/watch?v=${rawVideoId}?sub_confirmation=1`;
    } else if (rawStr && !rawStr.startsWith("UC_")) {
        subUrl = `https://www.youtube.com/@${rawStr}?sub_confirmation=1`;
    } else {
        subUrl = "https://www.youtube.com/";
    }

    const width = 640;
    const height = 660;
    const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - height) / 2));
    window.open(
        subUrl,
        "YouTubeSubscribeModal",
        `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,resizable=yes`,
    );
    triggerToast(
        `Membuka popup subscribe YouTube untuk ${officerName || "Officer"}`,
    );
};

// CSV Export Handler
const exportCSV = () => {
    if (!filteredOfficers.value.length) {
        toast.warning("Tidak ada data officer untuk diekspor!");
        return;
    }
    const headers = [
        "Callsign",
        "Nama Officer",
        "Badge Number",
        "Departemen",
        "Rank",
        "Status Duty",
        "Subscribers",
        "Handle YouTube",
        "Patrol Zone",
    ];
    const rows = filteredOfficers.value.map((o) => [
        `"${o.callsign || ""}"`,
        `"${o.officer_name || ""}"`,
        `"${o.badge_number || ""}"`,
        `"${o.department || ""}"`,
        `"${o.rank || ""}"`,
        `"${o.is_online ? "10-8 ON DUTY" : "10-7 OFF DUTY"}"`,
        `"${o.subscriber_count || 0}"`,
        `"${o.handle || ""}"`,
        `"${o.station || "Central"}"`,
    ]);

    const csvContent =
        "data:text/csv;charset=utf-8," +
        [headers.join(","), ...rows.map((e) => e.join(","))].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute(
        "download",
        `Daftar_Officer_IME_${new Date().toISOString().slice(0, 10)}.csv`,
    );
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    triggerToast("Daftar officer berhasil diekspor ke CSV!");
};

// Filtered & Sorted Officers
const filteredOfficers = computed(() => {
    let list = officers.value;

    // Status Filter (Dropdown / Pill)
    if (statusFilter.value === "online") {
        list = list.filter((o) => o.is_online);
    } else if (statusFilter.value === "offline") {
        list = list.filter((o) => !o.is_online);
    }

    // Department Filter (one department at a time)
    list = list.filter((o) => deptOfOfficer(o) === selectedDept.value);

    // Search Query Filter
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (o) =>
                (o.officer_name && o.officer_name.toLowerCase().includes(q)) ||
                (o.callsign && o.callsign.toLowerCase().includes(q)) ||
                (o.streamer_name &&
                    o.streamer_name.toLowerCase().includes(q)) ||
                (o.handle && o.handle.toLowerCase().includes(q)) ||
                (o.badge_number && o.badge_number.toLowerCase().includes(q)) ||
                (o.station && o.station.toLowerCase().includes(q)) ||
                (o.rank && o.rank.toLowerCase().includes(q)),
        );
    }

    // Sorting Logic
    return [...list].sort((a, b) => {
        if (sortBy.value === "rank") {
            // Highest rank first (ranks.level, 10 = Chief); unlinked ranks (0) last.
            return (
                (b.rank_level || 0) - (a.rank_level || 0) ||
                (a.department || "").localeCompare(b.department || "") ||
                (a.officer_name || "").localeCompare(b.officer_name || "")
            );
        } else if (sortBy.value === "name") {
            return (a.officer_name || "").localeCompare(b.officer_name || "");
        }
        return 0;
    });
});

// Group the (rank-sorted) list into tiers: command staff first, then one group per rank.
// The department leader(s): everyone at the highest rank level present, shown as a
// spotlight above the rest. Usually one officer, two at most; capped at three.
const LEADER_MAX = 3;
const leaders = computed(() => {
    const top = Math.max(0, ...filteredOfficers.value.map((o) => o.rank_level || 0));
    if (!top) return [];
    const atTop = filteredOfficers.value.filter((o) => (o.rank_level || 0) === top);
    return atTop.length <= LEADER_MAX ? atTop : [];
});
const leaderIds = computed(() => new Set(leaders.value.map((o) => o.id)));

const deptTint = {
    LSPD: "from-dept-lspd/25",
    BCSO: "from-dept-bcso/25",
    SASP: "from-dept-sasp/25",
    SAPR: "from-dept-sapr/25",
};

const rankGroups = computed(() => {
    const groups = [];
    const byKey = new Map();
    for (const o of filteredOfficers.value.filter((o) => !leaderIds.value.has(o.id))) {
        const level = o.rank_level || 0;
        const key = level >= COMMAND_LEVEL ? "command" : level > 0 ? `rank-${level}-${o.rank}` : "unassigned";
        if (!byKey.has(key)) {
            const group = {
                key,
                command: key === "command",
                title: key === "command" ? "Komando" : key === "unassigned" ? "Pangkat belum ditentukan" : o.rank,
                officers: [],
            };
            byKey.set(key, group);
            groups.push(group);
        }
        byKey.get(key).officers.push(o);
    }
    // "Belum ditentukan" always last.
    return groups.sort((a, b) => (a.key === "unassigned") - (b.key === "unassigned"));
});

const commandRange = computed(() => {
    const titles = rankGroups.value.find((g) => g.command)?.officers.map((o) => o.rank) || [];
    const unique = [...new Set(titles)];
    return unique.length > 1 ? `${unique[0]} — ${unique[unique.length - 1]}` : unique[0] || "";
});

// When a search has no hits here, point at departments that do have matches.
const otherDeptMatches = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return [];
    return DEPARTMENTS.filter((d) => d.code !== selectedDept.value)
        .map((d) => ({
            ...d,
            count: officers.value.filter(
                (o) =>
                    deptOfOfficer(o) === d.code &&
                    [o.officer_name, o.callsign, o.streamer_name, o.handle, o.rank]
                        .filter(Boolean)
                        .some((v) => v.toLowerCase().includes(q)),
            ).length,
        }))
        .filter((d) => d.count > 0);
});

const liveInDept = computed(() =>
    props.initialOfficers.filter((o) => deptOfOfficer(o) === selectedDept.value && o.is_online).length,
);
</script>

<template>
    <TacticalLayout>
        <Head title="Direktori & Manajemen Pengguna — IME Police Terminal" />

        <PageContainer class="space-y-6 pb-16 pt-8 sm:pt-10">
            <!-- Header: title and tools in one row, department tabs right below -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Direktori Perwira</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ officers.length }} perwira terdaftar ·
                        <span class="text-live">{{ deptStats.total_live || 0 }} sedang live</span>
                        · disusun menurut rantai komando
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-64">
                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('directory.search_placeholder')"
                            class="h-9 w-full pl-9 pr-8 text-sm"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            aria-label="Bersihkan pencarian"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            @click="searchQuery = ''"
                        >
                            <X class="size-4" />
                        </button>
                    </div>

                    <select
                        v-model="statusFilter"
                        aria-label="Filter status"
                        class="h-9 cursor-pointer appearance-none rounded-md border border-input bg-background py-1 pl-3 pr-7 text-sm text-foreground focus:border-ring focus:outline-hidden"
                    >
                        <option value="all">Semua status</option>
                        <option value="online">Sedang live (10-8)</option>
                        <option value="offline">Off duty (10-7)</option>
                    </select>

                    <Button as-child variant="outline" size="sm" class="h-9 gap-1.5">
                        <Link href="/ranking"><Trophy class="size-4" /> Ranking</Link>
                    </Button>

                    <div class="inline-flex gap-0.5 rounded-md border border-border bg-background p-0.5">
                        <Button
                            type="button"
                            size="icon-sm"
                            :variant="viewMode === 'grid' ? 'secondary' : 'ghost'"
                            aria-label="Tampilan kartu"
                            title="Tampilan kartu"
                            @click="viewMode = 'grid'"
                        >
                            <LayoutGrid />
                        </Button>
                        <Button
                            type="button"
                            size="icon-sm"
                            :variant="viewMode === 'table' ? 'secondary' : 'ghost'"
                            aria-label="Tampilan tabel"
                            title="Tampilan tabel"
                            @click="viewMode = 'table'"
                        >
                            <TableIcon />
                        </Button>
                    </div>

                    <template v-if="isAdmin">
                        <Button size="sm" class="h-9 gap-1.5" @click="openAddModal">
                            <UserPlus class="size-4" /> Tambah
                        </Button>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            class="size-9"
                            aria-label="Unduh laporan CSV"
                            title="Unduh laporan CSV"
                            @click="exportCSV"
                        >
                            <Download />
                        </Button>
                    </template>
                </div>
            </div>

            <div class="scrollbar-none flex items-center gap-6 overflow-x-auto border-b border-border" role="tablist">
                <button
                    v-for="d in DEPARTMENTS"
                    :key="d.code"
                    type="button"
                    role="tab"
                    :aria-selected="selectedDept === d.code"
                    :class="[
                        '-mb-px flex shrink-0 items-center gap-1.5 border-b-2 pb-2 text-sm font-semibold transition-colors',
                        selectedDept === d.code
                            ? 'border-foreground text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground',
                    ]"
                    @click="selectDept(d.code)"
                >
                    {{ d.code }}
                    <span class="text-xs font-normal text-muted-foreground">{{ deptCounts[d.code] }}</span>
                </button>
            </div>

            <!-- Department header -->
            <div class="flex items-center gap-3">
                <img :src="activeDept.logo" :alt="activeDept.code" class="size-12 shrink-0 rounded-md object-cover" />
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold text-foreground">{{ activeDept.name }}</h2>
                    <p class="text-xs text-muted-foreground">
                        {{ deptCounts[selectedDept] }} perwira
                        <template v-if="liveInDept"> · <span class="text-live">{{ liveInDept }} sedang live</span></template>
                    </p>
                </div>
            </div>

            <p v-if="otherDeptMatches.length" class="text-sm text-muted-foreground">
                Juga ditemukan di:
                <button
                    v-for="d in otherDeptMatches"
                    :key="d.code"
                    type="button"
                    class="ml-2 font-semibold text-primary hover:underline"
                    @click="selectDept(d.code)"
                >
                    {{ d.code }} ({{ d.count }}) →
                </button>
            </p>

            <!-- Empty Search State -->
            <EmptyState
                v-if="filteredOfficers.length === 0"
                :icon="Search"
                title="PENGGUNA TIDAK DITEMUKAN"
                description="Tidak ada pengguna yang cocok dengan kriteria pencarian atau filter klasifikasi yang Anda terapkan."
                action-label="Reset Filter Pencarian"
                @action="
                    searchQuery = '';
                    statusFilter = 'all';
                "
            />

            <!-- VIEW MODE 1: GRID VIEW -->
            <div v-else-if="viewMode === 'grid'" class="space-y-8">
                <!-- Leader spotlight: the highest rank in the department -->
                <section v-if="leaders.length" aria-label="Pimpinan departemen">
                    <div :class="['grid gap-4', leaders.length > 1 ? 'md:grid-cols-2' : '', leaders.length > 2 ? 'lg:grid-cols-3' : '']">
                        <button
                            v-for="officer in leaders"
                            :key="`leader-${officer.id}`"
                            type="button"
                            :class="[
                                'group relative flex items-center gap-5 overflow-hidden rounded-xl border border-border bg-linear-to-r to-card p-5 text-left transition-colors hover:border-primary/40 sm:p-6',
                                deptTint[selectedDept] || 'from-muted',
                            ]"
                            @click="openDrawer(officer)"
                        >
                            <img
                                :src="activeDept.logo"
                                alt=""
                                aria-hidden="true"
                                class="pointer-events-none absolute -right-6 -top-6 size-40 rounded-full object-cover opacity-10"
                            />
                            <OfficerAvatar
                                :src="officer.avatar_url"
                                :name="officer.officer_name"
                                :live="officer.is_online"
                                size="xl"
                                :class="['shrink-0 ring-2 ring-border', officer.is_hiatus && 'opacity-60']"
                            />
                            <div class="relative min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Pimpinan {{ selectedDept }} · {{ officer.rank }}
                                </p>
                                <h3 class="mt-1 truncate text-xl font-bold text-foreground group-hover:text-primary sm:text-2xl">
                                    {{ officer.officer_name }}
                                </h3>
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <span class="font-mono">{{ officer.callsign }}</span>
                                    <span v-if="officer.badge_number" class="font-mono">{{ officer.badge_number }}</span>
                                    <LiveBadge v-if="officer.is_online" />
                                    <HiatusBadge v-if="officer.is_hiatus" />
                                </p>
                            </div>
                        </button>
                    </div>
                </section>

                <section v-for="group in rankGroups" :key="group.key">
                    <h3 class="mb-3 flex items-baseline gap-2 text-sm font-semibold text-foreground">
                        {{ group.title }}
                        <span class="text-xs font-normal text-muted-foreground">
                            <template v-if="group.command && commandRange">{{ commandRange }} · </template>{{ group.officers.length }}
                        </span>
                    </h3>

                    <!-- Command staff: full cards -->
                    <div v-if="group.command" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="officer in group.officers"
                    :key="officer.id"
                    @click="openDrawer(officer)"
                    class="hover:border-primary/40 hover:-translate-y-1 hover:shadow-lg p-4 flex flex-col justify-between transition-all duration-300 cursor-pointer group"
                >
                    <div>
                        <!-- Card Header: Department, Callsign, Status Badge -->
                        <div
                            class="flex items-center justify-between gap-2 mb-3"
                        >
                            <div class="flex items-center space-x-2 min-w-0">
                                <DeptBadge :dept="officer.department" />
                                <span
                                    class="font-mono text-xs font-bold text-foreground truncate"
                                    >{{ officer.callsign }}</span
                                >
                                <span
                                    class="text-xs text-muted-foreground font-mono"
                                    >{{ officer.badge_number }}</span
                                >
                            </div>

                            <!-- Status Duty Badge -->
                            <div class="shrink-0">
                                <LiveBadge
                                    v-if="officer.is_online"
                                    label="10-8 ON DUTY"
                                />
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="text-xs font-mono text-muted-foreground whitespace-nowrap"
                                >
                                    10-7 OFF DUTY
                                </Badge>
                            </div>
                        </div>

                        <!-- Officer Identity Block -->
                        <div class="flex items-start gap-3 mt-1.5">
                            <OfficerAvatar
                                :src="officer.avatar_url"
                                :name="officer.officer_name"
                                :callsign="officer.callsign"
                                :dept="officer.department"
                                :live="officer.is_online"
                                size="md"
                                class="shrink-0"
                            />

                            <!-- Identity Info -->
                            <div class="min-w-0 flex-1">
                                <h3
                                    class="text-base font-bold text-foreground truncate group-hover:text-primary transition"
                                    :title="officer.officer_name"
                                >
                                    {{ officer.officer_name }}
                                </h3>

                                <div
                                    class="text-xs text-muted-foreground flex items-center gap-1.5 mt-0.5 truncate font-mono"
                                >
                                    <Badge
                                        variant="secondary"
                                        class="text-xs px-1.5 py-0 font-semibold"
                                        >{{ officer.rank }}</Badge
                                    >
                                    <Button
                                        v-if="officer.bio_slug || officer.bio_profile?.slug"
                                        as-child
                                        variant="link"
                                        size="sm"
                                        class="h-auto p-0 text-xs font-mono"
                                        @click.stop
                                    >
                                        <a
                                            :href="`/@${officer.bio_slug || officer.bio_profile.slug}`"
                                            target="_blank"
                                        >
                                            /@{{ officer.bio_slug || officer.bio_profile.slug }}
                                        </a>
                                    </Button>
                                    <template v-else-if="officer.streamer_name">
                                        <span class="text-muted-foreground/60"
                                            >•</span
                                        >
                                        <span
                                            class="text-muted-foreground truncate"
                                            >{{ officer.streamer_name }}</span
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Subscriber Info & Road to 1K Progress Bar -->
                        <div class="mt-3.5 font-mono">
                            <div
                                v-if="(officer.subscriber_count || 0) < 1000"
                                class="bg-background border border-border rounded-md p-2.5"
                            >
                                <div
                                    class="flex items-center justify-between text-xs mb-1"
                                >
                                    <span
                                        class="text-muted-foreground flex items-center gap-1"
                                    >
                                        <Target class="w-3 h-3" />
                                        <span>Road to 1,000 Subs:</span>
                                    </span>
                                    <span class="font-bold text-warning">
                                        {{
                                            officer.subscriber_count
                                                ? Number(
                                                      officer.subscriber_count,
                                                  ).toLocaleString("id-ID")
                                                : "0"
                                        }}
                                        / 1.000
                                    </span>
                                </div>
                                <div
                                    class="w-full bg-muted rounded-full h-1.5 overflow-hidden"
                                >
                                    <div
                                        class="h-full bg-warning rounded-full"
                                        :style="{
                                            width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="text-xs text-muted-foreground flex items-center justify-between bg-background rounded-md p-2 border border-border"
                            >
                                <span>Total Subscribers:</span>
                                <span class="font-bold text-foreground">{{
                                    Number(
                                        officer.subscriber_count || 0,
                                    ).toLocaleString("id-ID")
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div
                        class="mt-4 pt-3 border-t border-border flex items-center justify-between"
                        @click.stop
                    >
                        <div
                            class="text-xs font-mono text-muted-foreground truncate"
                        >
                            <span
                                >Station:
                                <strong class="text-foreground">{{
                                    officer.station || "Central"
                                }}</strong></span
                            >
                        </div>

                        <div class="flex items-center space-x-1.5 shrink-0">
                            <OfficerSubscribeButton
                                :officer-id="officer.id"
                                :officer-name="officer.officer_name"
                                icon-only
                                class="w-8 h-8"
                            />
                            <!-- 1-Click YouTube Subscribe Popup -->
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="
                                    openSubscribePopup(
                                        officer.channel_id || officer.handle,
                                        officer.officer_name,
                                    )
                                "
                                class="h-8 px-2.5 font-mono text-xs font-extrabold"
                                title="Subscribe ke YouTube channel"
                            >
                                SUB
                            </Button>

                            <!-- YouTube External Link -->
                            <Button
                                as-child
                                variant="outline"
                                size="icon-sm"
                                class="w-8 h-8"
                                title="Buka Channel YouTube"
                                aria-label="Buka Channel YouTube"
                            >
                                <a
                                    :href="
                                        officer.handle
                                            ? officer.handle.startsWith('@')
                                                ? `https://www.youtube.com/${officer.handle}`
                                                : `https://www.youtube.com/@${officer.handle}`
                                            : 'https://www.youtube.com/'
                                    "
                                    target="_blank"
                                >
                                    <ExternalLink class="w-3.5 h-3.5" />
                                </a>
                            </Button>
                        </div>
                    </div>
                </Card>
                    </div>

                    <!-- Other ranks: compact cards -->
                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
                        <button
                            v-for="officer in group.officers"
                            :key="officer.id"
                            type="button"
                            class="group flex flex-col items-center gap-2 rounded-lg border border-border bg-card p-3 text-center transition-colors hover:border-primary/40 hover:bg-accent/40"
                            @click="openDrawer(officer)"
                        >
                            <OfficerAvatar
                                :src="officer.avatar_url"
                                :name="officer.officer_name"
                                :live="officer.is_online"
                                size="md"
                                :class="officer.is_hiatus ? 'opacity-60' : ''"
                            />
                            <span class="w-full min-w-0">
                                <span class="block truncate text-sm font-semibold text-foreground group-hover:text-primary">{{ officer.officer_name }}</span>
                                <span class="block truncate font-mono text-xs text-muted-foreground">{{ officer.callsign }}</span>
                            </span>
                            <div class="flex items-center gap-1">
                                <LiveBadge v-if="officer.is_online" />
                                <HiatusBadge v-if="officer.is_hiatus" />
                            </div>
                        </button>
                    </div>
                </section>
            </div>

            <!-- VIEW MODE 2: TABLE VIEW -->
            <Card v-else class="overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/40">
                            <TableHead
                                class="py-3.5 px-4 sm:px-6 font-mono text-xs uppercase"
                            >
                                Pengguna & Foto Profil
                            </TableHead>
                            <TableHead
                                class="py-3.5 px-4 font-mono text-xs uppercase"
                            >
                                Klasifikasi / Rank
                            </TableHead>
                            <TableHead
                                class="py-3.5 px-4 font-mono text-xs uppercase"
                            >
                                Status Akun
                            </TableHead>
                            <TableHead
                                class="py-3.5 px-4 font-mono text-xs uppercase"
                            >
                                Subscribers / Target
                            </TableHead>
                            <TableHead
                                class="py-3.5 px-4 font-mono text-xs uppercase"
                            >
                                Station
                            </TableHead>
                            <TableHead
                                class="py-3.5 px-4 text-right font-mono text-xs uppercase"
                            >
                                Aksi
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="officer in filteredOfficers"
                            :key="officer.id"
                            @click="openDrawer(officer)"
                            class="cursor-pointer group"
                        >
                            <!-- Officer & Avatar -->
                            <TableCell class="py-3.5 px-4 sm:px-6">
                                <div class="flex items-center space-x-3">
                                    <OfficerAvatar
                                        :src="officer.avatar_url"
                                        :name="officer.officer_name"
                                        :callsign="officer.callsign"
                                        :dept="officer.department"
                                        :live="officer.is_online"
                                        size="md"
                                        :class="officer.is_hiatus ? 'opacity-60' : ''"
                                    />
                                    <div class="min-w-0">
                                        <div
                                            class="font-bold text-foreground group-hover:text-primary transition truncate flex items-center gap-1.5"
                                        >
                                            <span>{{ officer.officer_name }}</span>
                                            <LiveBadge v-if="officer.is_online" />
                                            <HiatusBadge v-if="officer.is_hiatus" />
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground font-mono flex items-center gap-1.5"
                                        >
                                            <span
                                                class="text-foreground font-semibold"
                                                >{{ officer.callsign }}</span
                                            >
                                            <span
                                                class="text-muted-foreground/60"
                                                >•</span
                                            >
                                            <span>{{
                                                officer.badge_number
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Department & Rank -->
                            <TableCell class="py-3.5 px-4">
                                <div class="space-y-1">
                                    <DeptBadge :dept="officer.department" />
                                    <div
                                        class="text-xs font-mono text-muted-foreground truncate"
                                    >
                                        {{ officer.rank }}
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Status Duty -->
                            <TableCell class="py-3.5 px-4 whitespace-nowrap">
                                <LiveBadge
                                    v-if="officer.is_online"
                                    label="10-8 LIVE"
                                />
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="text-xs font-mono text-muted-foreground whitespace-nowrap"
                                >
                                    10-7 OFF
                                </Badge>
                            </TableCell>

                            <!-- Subscribers / Progress Bar -->
                            <TableCell class="py-3.5 px-4 font-mono">
                                <div
                                    v-if="
                                        (officer.subscriber_count || 0) < 1000
                                    "
                                    class="w-36 space-y-1"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs text-muted-foreground"
                                    >
                                        <span>Road 1K</span>
                                        <span class="font-bold text-warning">{{
                                            Number(
                                                officer.subscriber_count || 0,
                                            ).toLocaleString("id-ID")
                                        }}</span>
                                    </div>
                                    <div
                                        class="w-full bg-muted rounded-full h-1.5 overflow-hidden"
                                    >
                                        <div
                                            class="h-full bg-warning rounded-full"
                                            :style="{
                                                width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="text-xs font-bold text-foreground"
                                >
                                    {{
                                        Number(
                                            officer.subscriber_count || 0,
                                        ).toLocaleString("id-ID")
                                    }}
                                </div>
                            </TableCell>

                            <!-- Station -->
                            <TableCell
                                class="py-3.5 px-4 font-mono text-xs text-muted-foreground"
                            >
                                <div>
                                    Station:
                                    <strong class="text-foreground">{{
                                        officer.station || "Central"
                                    }}</strong>
                                </div>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell
                                class="py-3.5 px-4 text-right"
                                @click.stop
                            >
                                <div
                                    class="flex items-center justify-end space-x-1.5"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            openSubscribePopup(
                                                officer.channel_id ||
                                                    officer.handle,
                                                officer.officer_name,
                                            )
                                        "
                                        class="h-8 px-2.5 font-mono text-xs font-bold"
                                        title="Subscribe ke YouTube channel"
                                    >
                                        SUB
                                    </Button>

                                    <Button
                                        as-child
                                        variant="outline"
                                        size="icon-sm"
                                        class="w-8 h-8"
                                        title="Buka Channel YouTube"
                                        aria-label="Buka Channel YouTube"
                                    >
                                        <a
                                            :href="
                                                officer.handle
                                                    ? officer.handle.startsWith(
                                                          '@',
                                                      )
                                                        ? `https://www.youtube.com/${officer.handle}`
                                                        : `https://www.youtube.com/@${officer.handle}`
                                                    : 'https://www.youtube.com/'
                                            "
                                            target="_blank"
                                        >
                                            <ExternalLink
                                                class="w-3.5 h-3.5"
                                            />
                                        </a>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
        </PageContainer>

        <!-- ADD OFFICER MODAL -->
        <Dialog v-model:open="isAddModalOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 font-tactical uppercase tracking-wider"
                    >
                        <UserPlus class="w-5 h-5 text-primary" />
                        <span>Tambah Pengguna / Officer Baru</span>
                    </DialogTitle>
                    <DialogDescription>
                        Daftarkan akun perwira dan tetapkan hak akses
                    </DialogDescription>
                </DialogHeader>

                <form
                    @submit.prevent="handleCreateOfficer"
                    class="space-y-4 font-sans text-xs"
                >
                    <div>
                        <label
                            class="block font-semibold text-foreground mb-1"
                        >
                            Nama Lengkap & Callsign *
                        </label>
                        <Input
                            v-model="newOfficerForm.officer_name"
                            type="text"
                            required
                            placeholder="Contoh: dr. Adelia Putri / Ofc. Budi"
                            class="h-9 text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block font-semibold text-foreground mb-1"
                            >
                                Callsign Dinas
                            </label>
                            <Input
                                v-model="newOfficerForm.callsign"
                                type="text"
                                placeholder="1-ADAM-12"
                                class="h-9 text-xs font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="block font-semibold text-foreground mb-1"
                            >
                                Badge ID / Phone
                            </label>
                            <Input
                                v-model="newOfficerForm.badge_number"
                                type="text"
                                placeholder="#302 / +62 812..."
                                class="h-9 text-xs font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block font-semibold text-foreground mb-1"
                            >
                                Departemen / Kesatuan *
                            </label>
                            <select
                                v-model="newOfficerForm.department"
                                class="w-full h-9 px-3 rounded-md bg-background border border-input text-foreground text-xs font-mono focus:outline-hidden focus:border-ring cursor-pointer"
                            >
                                <option value="LSPD">LSPD (Police)</option>
                                <option value="BCSO">BCSO (Sheriff)</option>
                                <option value="SASP">
                                    SASP (State Police)
                                </option>
                                <option value="SAPR">
                                    SAPR (Park Rangers)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block font-semibold text-foreground mb-1"
                            >
                                Rank / Pangkat Dinas
                            </label>
                            <Input
                                v-model="newOfficerForm.rank"
                                type="text"
                                placeholder="Sergeant / Officer II"
                                class="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="block font-semibold text-foreground mb-1"
                        >
                            Handle YouTube / URL Channel
                        </label>
                        <Input
                            v-model="newOfficerForm.handle"
                            type="text"
                            placeholder="@HandleYouTube"
                            class="h-9 text-xs font-mono"
                        />
                    </div>

                    <DialogFooter class="pt-3 border-t border-border">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="closeAddModal"
                            class="text-xs font-semibold font-mono"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            class="text-xs font-bold uppercase font-mono"
                        >
                            Simpan & Daftarkan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Slide-over Officer Detail Sheet -->
        <Sheet v-model:open="isDrawerOpen">
            <SheetContent
                side="right"
                class="w-full sm:max-w-md flex flex-col justify-between p-0 overflow-hidden"
            >
                <!-- Drawer Header -->
                <SheetHeader
                    class="p-5 border-b border-border bg-card shrink-0"
                >
                    <SheetTitle
                        class="text-xs font-mono font-bold uppercase tracking-wider text-primary"
                    >
                        PROFIL PERSONIL DISPATCH
                    </SheetTitle>
                </SheetHeader>

                <!-- Drawer Content Body -->
                <div
                    v-if="activeOfficer"
                    class="p-6 overflow-y-auto space-y-6 flex-1 text-xs sm:text-sm font-sans"
                >
                    <!-- Header Profile Avatar & Basic Info -->
                    <Card class="flex flex-row items-start gap-4 p-4">
                        <OfficerAvatar
                            :src="activeOfficer.avatar_url"
                            :name="activeOfficer.officer_name"
                            :callsign="activeOfficer.callsign"
                            :dept="activeOfficer.department"
                            :live="activeOfficer.is_online"
                            size="lg"
                            :class="['shrink-0', activeOfficer.is_hiatus && 'opacity-60']"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <DeptBadge :dept="activeOfficer.department" />
                                <span
                                    class="text-xs font-mono text-muted-foreground font-bold"
                                    >{{ activeOfficer.badge_number }}</span
                                >
                                <HiatusBadge v-if="activeOfficer.is_hiatus" />
                            </div>
                            <h2
                                class="text-lg font-bold text-foreground leading-snug"
                            >
                                {{ activeOfficer.officer_name }}
                            </h2>
                            <div
                                class="text-xs font-mono text-primary font-semibold"
                            >
                                {{ activeOfficer.callsign }} •
                                {{ activeOfficer.rank }}
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <OfficerSubscribeButton
                                    :officer-id="activeOfficer.id"
                                    :officer-name="activeOfficer.officer_name"
                                    size="sm"
                                    class="h-8 text-xs font-mono"
                                />
                                <Button
                                    v-if="activeOfficer.bio_slug"
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1.5 text-xs font-mono"
                                >
                                    <Link :href="`/@${activeOfficer.bio_slug}`">
                                        <Link2 class="size-3.5 text-primary" />
                                        <span>/@{{ activeOfficer.bio_slug }}</span>
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </Card>

                    <!-- Status & Patrol Details Grid -->
                    <div class="grid grid-cols-2 gap-3 font-mono text-xs">
                        <Card class="p-3 space-y-1">
                            <span
                                class="text-xs text-muted-foreground uppercase font-bold"
                                >Status Siaran</span
                            >
                            <div>
                                <LiveBadge
                                    v-if="activeOfficer.is_online"
                                    label="10-8 LIVE ON DUTY"
                                />
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >10-7 OFF DUTY</span
                                >
                            </div>
                        </Card>

                        <Card class="p-3 space-y-1">
                            <span
                                class="text-xs text-muted-foreground uppercase font-bold"
                                >Station</span
                            >
                            <div class="text-xs text-foreground font-bold">
                                {{ activeOfficer.station || "Central" }}
                            </div>
                        </Card>
                    </div>

                    <!-- YouTube Streamer Info & Subscriber Stats -->
                    <Card class="p-4 space-y-3 font-mono">
                        <div
                            class="flex items-center justify-between text-xs pb-2"
                        >
                            <span class="text-muted-foreground"
                                >Streamer Name:</span
                            >
                            <span class="text-foreground font-bold">{{
                                activeOfficer.streamer_name || "-"
                            }}</span>
                        </div>
                        <Separator />
                        <div
                            class="flex items-center justify-between text-xs pb-2"
                        >
                            <span class="text-muted-foreground"
                                >Handle YouTube:</span
                            >
                            <a
                                v-if="activeChannelUrl && activeOfficer.handle"
                                :href="activeChannelUrl"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1 text-primary font-bold hover:underline"
                            >
                                {{ activeOfficer.handle }}
                                <ExternalLink class="size-3" />
                            </a>
                            <span v-else class="text-primary font-bold">{{
                                activeOfficer.handle || "-"
                            }}</span>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground"
                                >Total Subscribers:</span
                            >
                            <span class="text-success font-bold">{{
                                Number(
                                    activeOfficer.subscriber_count || 0,
                                ).toLocaleString("id-ID")
                            }}</span>
                        </div>

                        <!-- Road to 1K Subs Bar in Drawer -->
                        <div
                            v-if="(activeOfficer.subscriber_count || 0) < 1000"
                            class="pt-2"
                        >
                            <div
                                class="flex items-center justify-between text-xs text-warning mb-1"
                            >
                                <span>Target 1,000 Subscribers:</span>
                                <span class="font-bold"
                                    >{{
                                        Number(
                                            activeOfficer.subscriber_count || 0,
                                        ).toLocaleString("id-ID")
                                    }}
                                    / 1.000</span
                                >
                            </div>
                            <div
                                class="w-full bg-muted rounded-full h-2 overflow-hidden"
                            >
                                <div
                                    class="h-full bg-warning rounded-full"
                                    :style="{
                                        width: `${Math.min(100, Math.round(((activeOfficer.subscriber_count || 0) / 1000) * 100))}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Drawer Action Footer -->
                <SheetFooter
                    v-if="activeOfficer"
                    class="p-4 border-t border-border bg-card flex-row items-center justify-end gap-2 font-mono text-xs shrink-0"
                >
                    <Button
                        v-if="activeChannelUrl"
                        as-child
                        size="sm"
                        variant="outline"
                        class="font-bold text-xs"
                    >
                        <a :href="activeChannelUrl" target="_blank" rel="noopener">
                            <ExternalLink /> BUKA CHANNEL
                        </a>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        @click="
                            openSubscribePopup(
                                activeOfficer.channel_id ||
                                    activeOfficer.handle,
                                activeOfficer.officer_name,
                            )
                        "
                        class="font-bold text-xs"
                    >
                        SUBSCRIBE YOUTUBE
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>

        <!-- Floating Toast Notification -->
        <div
            v-if="showToastNotification"
            class="fixed bottom-6 right-6 z-50 bg-card border border-border text-foreground px-4 py-3 rounded-md shadow-lg font-mono text-xs flex items-center gap-2"
        >
            <span class="text-primary font-bold">✓</span>
            <span>{{ toastMessage }}</span>
        </div>
    </TacticalLayout>
</template>
