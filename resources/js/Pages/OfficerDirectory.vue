<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import {
    ExternalLink,
    Search,
    Star,
    Target,
    Users,
    Zap,
    UserPlus,
    Download,
    Video,
    Ban,
    LayoutGrid,
    Table as TableIcon,
    X,
} from "@lucide/vue";

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
    PageHeader,
    StatTile,
    DeptBadge,
    LiveBadge,
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
const selectedDept = ref("ALL"); // 'ALL', 'LIVE_ONLY', 'LSPD', 'BCSO', 'SASP', 'SAPR'
const statusFilter = ref("all"); // 'all', 'online', 'offline'

// Read URL query parameters (e.g. /officers?sort=subs_asc)
const getInitialSort = () => {
    if (typeof window !== "undefined" && window.location.search) {
        const params = new URLSearchParams(window.location.search);
        const sortParam = params.get("sort");
        if (
            sortParam &&
            ["rank", "subs_desc", "subs_asc", "name"].includes(sortParam)
        ) {
            return sortParam;
        }
    }
    // Old links used ?sort=status; the directory now always defaults to rank order.
    return "rank";
};

const sortBy = ref(getInitialSort()); // 'rank', 'subs_desc', 'subs_asc', 'name'
const viewMode = ref("grid"); // 'grid' | 'table'

// Drawer State
const activeOfficer = ref(null);
const isDrawerOpen = ref(false);

// Add Officer Modal State
const isAddModalOpen = ref(false);
const newOfficerForm = ref({
    officer_name: "",
    callsign: "",
    badge_number: "",
    department: "LSPD",
    rank: "Officer",
    patrol_zone: "Los Santos",
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
        patrol_zone: "Los Santos",
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
        patrol_zone: newOfficerForm.value.patrol_zone || "Los Santos",
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

// Personal Streams saved to localStorage
const personalIds = ref([]);
const loadPersonalStorage = () => {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            const saved = localStorage.getItem("ime_personal_video_ids");
            if (saved) personalIds.value = JSON.parse(saved);
        }
    } catch (e) {
        console.warn(e);
    }
};
loadPersonalStorage();

const isPinned = (identifier) => {
    return personalIds.value.includes(identifier);
};

const togglePin = (identifier, officerName = "") => {
    const idx = personalIds.value.indexOf(identifier);
    if (idx !== -1) {
        personalIds.value.splice(idx, 1);
        triggerToast(
            `Dilepas dari Personal Watchlist (${officerName || identifier})`,
        );
    } else {
        if (personalIds.value.length >= 6) {
            toast.warning("Maksimal 6 siaran pada Personal Watchlist!");
            return;
        }
        personalIds.value.push(identifier);
        triggerToast(
            `Disimpan ke Personal Watchlist (${officerName || identifier})`,
        );
    }
    try {
        localStorage.setItem(
            "ime_personal_video_ids",
            JSON.stringify(personalIds.value),
        );
    } catch (e) {
        console.warn(e);
    }
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
        `"${o.patrol_zone || "LS"}"`,
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

    // Department Filter
    if (selectedDept.value === "LIVE_ONLY") {
        list = list.filter((o) => o.is_online);
    } else if (selectedDept.value !== "ALL") {
        if (selectedDept.value === "SAPR") {
            list = list.filter(
                (o) =>
                    o.department === "SAPR" || o.department === "PARK RANGER",
            );
        } else {
            list = list.filter((o) => o.department === selectedDept.value);
        }
    }

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
                (o.patrol_zone && o.patrol_zone.toLowerCase().includes(q)) ||
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
        } else if (sortBy.value === "subs_desc") {
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (sortBy.value === "subs_asc") {
            const isTargetA = (a.subscriber_count || 0) < 1000 ? 0 : 1;
            const isTargetB = (b.subscriber_count || 0) < 1000 ? 0 : 1;
            if (isTargetA !== isTargetB) return isTargetA - isTargetB;
            return (a.subscriber_count || 0) - (b.subscriber_count || 0);
        } else if (sortBy.value === "name") {
            return (a.officer_name || "").localeCompare(b.officer_name || "");
        }
        return 0;
    });
});
</script>

<template>
    <TacticalLayout>
        <Head title="Direktori & Manajemen Pengguna — IME Police Terminal" />

        <PageContainer class="space-y-8">
            <!-- PAGE HEADER -->
            <Card class="p-6 sm:p-8 space-y-6">
                <PageHeader
                    eyebrow="SAN ANDREAS DIRECTORY"
                    :title="$t('directory.title')"
                    :subtitle="$t('directory.subtitle')"
                >
                    <template #actions>
                        <div
                            v-if="isAdmin"
                            class="flex flex-row lg:flex-col items-center lg:items-end justify-start gap-2.5 shrink-0 font-mono"
                        >
                            <Button
                                type="button"
                                @click="openAddModal"
                                class="w-full sm:w-auto font-bold text-xs sm:text-sm gap-2"
                            >
                                <UserPlus class="w-4 h-4" />
                                <span>TAMBAH PENGGUNA BARU</span>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="exportCSV"
                                class="w-full sm:w-auto text-xs font-semibold gap-2"
                            >
                                <Download class="w-4 h-4 text-primary" />
                                <span>UNDUH LAPORAN (CSV)</span>
                            </Button>
                        </div>
                    </template>
                </PageHeader>

                <!-- Operational Status Badges -->
                <div
                    class="flex flex-wrap items-center gap-2.5 text-xs font-mono text-muted-foreground"
                >
                    <Badge
                        variant="success"
                        class="inline-flex items-center gap-1.5 px-3 py-1 font-bold"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-success"
                        ></span>
                        10-8 Live Duty: {{ deptStats.total_live || 0 }} Unit
                    </Badge>
                    <Badge
                        variant="outline"
                        class="inline-flex items-center gap-1.5 px-3 py-1"
                    >
                        <Users class="w-3.5 h-3.5 text-primary" />
                        Multi-Divisi: LSPD / BCSO / SASP / SAPR
                    </Badge>
                    <Badge
                        variant="outline"
                        class="inline-flex items-center gap-1.5 px-3 py-1"
                    >
                        <Zap class="w-3.5 h-3.5 text-warning" />
                        Pembaruan Real-Time
                    </Badge>
                </div>
            </Card>

            <!-- Top Telemetry KPI Cards -->
            <section class="grid grid-cols-2 md:grid-cols-4 gap-4 font-mono">
                <StatTile
                    label="Total Personil"
                    :value="officers.length"
                    delta="✓ Terdaftar di database repositori"
                    :icon="Users"
                />

                <StatTile
                    label="Petugas 10-8 (On Duty)"
                    :value="deptStats.total_live || 0"
                    delta="Sesi live streaming aktif"
                    :icon="Video"
                    accent="live"
                />

                <StatTile
                    label="Petugas 10-7 (Off Duty)"
                    :value="
                        deptStats.total_offline ||
                        officers.length - (deptStats.total_live || 0)
                    "
                    delta="Standby / Tidak siaran"
                    :icon="Ban"
                />

                <div
                    @click="sortBy = 'subs_asc'"
                    class="cursor-pointer transition hover:opacity-90"
                    title="Klik untuk mengurutkan perwira berdasarkan Target 1K Milestone"
                >
                    <StatTile
                        label="Target <1K Subs"
                        :value="
                            officers.filter(
                                (o) => (o.subscriber_count || 0) < 1000,
                            ).length
                        "
                        delta="Personil butuh dukungan subscriber"
                        :icon="Target"
                        accent="primary"
                    />
                </div>
            </section>

            <!-- Comprehensive Search, Filter, Sort & View Mode Switcher -->
            <Card class="p-4 sm:p-5 space-y-4">
                <!-- Top Row: Search Input & Controls -->
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3"
                >
                    <!-- Search Input -->
                    <div class="relative flex-1 group">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors"
                        >
                            <Search class="w-4 h-4" />
                        </div>
                        <Input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('directory.search_placeholder')"
                            class="w-full h-10 pl-10 pr-9 text-xs sm:text-sm font-sans"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            aria-label="Bersihkan pencarian"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-muted-foreground hover:text-foreground text-xs font-bold font-mono transition cursor-pointer"
                            title="Bersihkan pencarian"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Filter Dropdowns & View Mode Buttons -->
                    <div
                        class="flex flex-wrap items-center gap-2 sm:gap-3 font-mono text-xs"
                    >
                        <!-- Status Filter Dropdown -->
                        <div class="relative">
                            <select
                                v-model="statusFilter"
                                class="h-10 appearance-none bg-background border border-input rounded-md pl-3 py-2 pr-7 text-xs text-foreground focus:outline-hidden focus:border-ring cursor-pointer"
                            >
                                <option value="all">Semua Status Akun</option>
                                <option value="online">
                                    Aktif Saja (10-8)
                                </option>
                                <option value="offline">
                                    Menunggu / Off (10-7)
                                </option>
                            </select>
                        </div>

                        <!-- Sort Select -->
                        <div class="relative">
                            <select
                                v-model="sortBy"
                                class="h-10 appearance-none bg-background border border-input rounded-md pl-3 py-2 pr-7 text-xs text-foreground focus:outline-hidden focus:border-ring cursor-pointer"
                            >
                                <option value="rank">
                                    Pangkat (tertinggi)
                                </option>
                                <option value="subs_desc">
                                    Subscribers Terbanyak
                                </option>
                                <option value="subs_asc">
                                    Target 1K Milestone (&lt; 1K Subs)
                                </option>
                                <option value="name">Nama (A - Z)</option>
                            </select>
                        </div>

                        <!-- View Mode Switcher (Grid vs Table) -->
                        <div
                            class="inline-flex rounded-md bg-background p-1 border border-border gap-1"
                        >
                            <Button
                                type="button"
                                size="sm"
                                :variant="
                                    viewMode === 'grid' ? 'default' : 'ghost'
                                "
                                @click="viewMode = 'grid'"
                                class="h-8 px-2.5 text-xs font-semibold gap-1.5"
                                title="Tampilan Kartu Grid"
                            >
                                <LayoutGrid class="w-4 h-4" />
                                <span class="hidden sm:inline">Grid</span>
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                :variant="
                                    viewMode === 'table' ? 'default' : 'ghost'
                                "
                                @click="viewMode = 'table'"
                                class="h-8 px-2.5 text-xs font-semibold gap-1.5"
                                title="Tampilan Tabel Detail"
                            >
                                <TableIcon class="w-4 h-4" />
                                <span class="hidden sm:inline">Tabel</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Department Division Pills -->
                <div
                    class="pt-3 border-t border-border flex flex-wrap items-center justify-between gap-3 text-xs font-mono"
                >
                    <div
                        class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none"
                    >
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                selectedDept === 'ALL' ? 'default' : 'outline'
                            "
                            @click="selectedDept = 'ALL'"
                            class="h-8 px-3 text-xs whitespace-nowrap"
                        >
                            Semua Kesatuan ({{ officers.length }})
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                selectedDept === 'LIVE_ONLY'
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="selectedDept = 'LIVE_ONLY'"
                            :class="
                                selectedDept === 'LIVE_ONLY'
                                    ? ''
                                    : 'text-success'
                            "
                            class="h-8 px-3 text-xs gap-1.5 whitespace-nowrap"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-success"
                            ></span>
                            <span
                                >10-8 Live ({{
                                    deptStats.total_live || 0
                                }})</span
                            >
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                selectedDept === 'LSPD' ? 'default' : 'outline'
                            "
                            @click="selectedDept = 'LSPD'"
                            :class="
                                selectedDept === 'LSPD' ? '' : 'text-dept-lspd'
                            "
                            class="h-8 px-3 text-xs whitespace-nowrap"
                        >
                            LSPD ({{ deptStats.lspd_total || 0 }})
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                selectedDept === 'BCSO' ? 'default' : 'outline'
                            "
                            @click="selectedDept = 'BCSO'"
                            :class="
                                selectedDept === 'BCSO' ? '' : 'text-dept-bcso'
                            "
                            class="h-8 px-3 text-xs whitespace-nowrap"
                        >
                            BCSO ({{ deptStats.bcso_total || 0 }})
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                selectedDept === 'SASP' ? 'default' : 'outline'
                            "
                            @click="selectedDept = 'SASP'"
                            :class="
                                selectedDept === 'SASP' ? '' : 'text-dept-sasp'
                            "
                            class="h-8 px-3 text-xs whitespace-nowrap"
                        >
                            SASP ({{ deptStats.sasp_total || 0 }})
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                selectedDept === 'SAPR' ? 'default' : 'outline'
                            "
                            @click="selectedDept = 'SAPR'"
                            :class="
                                selectedDept === 'SAPR' ? '' : 'text-dept-sapr'
                            "
                            class="h-8 px-3 text-xs whitespace-nowrap"
                        >
                            SAPR ({{ deptStats.sapr_total || 0 }})
                        </Button>
                    </div>

                    <div class="text-muted-foreground shrink-0">
                        Menampilkan
                        <strong class="text-foreground">{{
                            filteredOfficers.length
                        }}</strong>
                        dari
                        <strong class="text-foreground">{{
                            officers.length
                        }}</strong>
                        pengguna
                    </div>
                </div>
            </Card>

            <!-- Empty Search State -->
            <EmptyState
                v-if="filteredOfficers.length === 0"
                :icon="Search"
                title="PENGGUNA TIDAK DITEMUKAN"
                description="Tidak ada pengguna yang cocok dengan kriteria pencarian atau filter klasifikasi yang Anda terapkan."
                action-label="Reset Filter Pencarian"
                @action="
                    searchQuery = '';
                    selectedDept = 'ALL';
                    statusFilter = 'all';
                "
            />

            <!-- VIEW MODE 1: GRID VIEW -->
            <div
                v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <Card
                    v-for="officer in filteredOfficers"
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
                                >Sektor:
                                <strong class="text-foreground">{{
                                    officer.patrol_zone || "LS"
                                }}</strong></span
                            >
                        </div>

                        <div class="flex items-center space-x-1.5 shrink-0">
                            <!-- Pin to Watchlist -->
                            <Button
                                type="button"
                                size="icon-sm"
                                :variant="
                                    isPinned(
                                        officer.channel_id || officer.handle,
                                    )
                                        ? 'secondary'
                                        : 'outline'
                                "
                                @click="
                                    togglePin(
                                        officer.channel_id || officer.handle,
                                        officer.officer_name,
                                    )
                                "
                                :class="
                                    isPinned(
                                        officer.channel_id || officer.handle,
                                    )
                                        ? 'text-primary border-primary/50'
                                        : 'text-muted-foreground'
                                "
                                class="w-8 h-8"
                                :aria-label="
                                    isPinned(
                                        officer.channel_id || officer.handle,
                                    )
                                        ? 'Hapus dari Watchlist'
                                        : 'Pin ke Watchlist'
                                "
                                :title="
                                    isPinned(
                                        officer.channel_id || officer.handle,
                                    )
                                        ? 'Hapus dari Watchlist'
                                        : 'Pin ke Watchlist'
                                "
                            >
                                <Star
                                    class="w-3.5 h-3.5"
                                    :class="
                                        isPinned(
                                            officer.channel_id ||
                                                officer.handle,
                                        )
                                            ? 'fill-current'
                                            : ''
                                    "
                                />
                            </Button>

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
                                Sektor / TAC
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
                                    />
                                    <div class="min-w-0">
                                        <div
                                            class="font-bold text-foreground group-hover:text-primary transition truncate"
                                        >
                                            {{ officer.officer_name }}
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

                            <!-- Sector / TAC -->
                            <TableCell
                                class="py-3.5 px-4 font-mono text-xs text-muted-foreground"
                            >
                                <div>
                                    Sektor:
                                    <strong class="text-foreground">{{
                                        officer.patrol_zone || "LS"
                                    }}</strong>
                                </div>
                                <div
                                    v-if="officer.tac_channel"
                                    class="text-xs text-primary"
                                >
                                    TAC-{{ officer.tac_channel }}
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
                                        size="icon-sm"
                                        :variant="
                                            isPinned(
                                                officer.channel_id ||
                                                    officer.handle,
                                            )
                                                ? 'secondary'
                                                : 'outline'
                                        "
                                        @click="
                                            togglePin(
                                                officer.channel_id ||
                                                    officer.handle,
                                                officer.officer_name,
                                            )
                                        "
                                        :class="
                                            isPinned(
                                                officer.channel_id ||
                                                    officer.handle,
                                            )
                                                ? 'text-primary border-primary/50'
                                                : 'text-muted-foreground'
                                        "
                                        class="w-8 h-8"
                                        :aria-label="
                                            isPinned(
                                                officer.channel_id ||
                                                    officer.handle,
                                            )
                                                ? 'Hapus dari Watchlist'
                                                : 'Pin ke Watchlist'
                                        "
                                        :title="
                                            isPinned(
                                                officer.channel_id ||
                                                    officer.handle,
                                            )
                                                ? 'Hapus dari Watchlist'
                                                : 'Pin ke Watchlist'
                                        "
                                    >
                                        <Star
                                            class="w-3.5 h-3.5"
                                            :class="
                                                isPinned(
                                                    officer.channel_id ||
                                                        officer.handle,
                                                )
                                                    ? 'fill-current'
                                                    : ''
                                            "
                                        />
                                    </Button>

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
                            class="shrink-0"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <DeptBadge :dept="activeOfficer.department" />
                                <span
                                    class="text-xs font-mono text-muted-foreground font-bold"
                                    >{{ activeOfficer.badge_number }}</span
                                >
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
                                >Sektor Patroli</span
                            >
                            <div class="text-xs text-foreground font-bold">
                                {{ activeOfficer.patrol_zone || "Los Santos" }}
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
                            <span class="text-primary font-bold">{{
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
                    class="p-4 border-t border-border bg-card flex-row items-center justify-between gap-2 font-mono text-xs shrink-0"
                >
                    <Button
                        type="button"
                        :variant="
                            isPinned(
                                activeOfficer.channel_id ||
                                    activeOfficer.handle,
                            )
                                ? 'secondary'
                                : 'outline'
                        "
                        size="sm"
                        @click="
                            togglePin(
                                activeOfficer.channel_id ||
                                    activeOfficer.handle,
                                activeOfficer.officer_name,
                            )
                        "
                        class="text-xs font-semibold"
                    >
                        {{
                            isPinned(
                                activeOfficer.channel_id ||
                                    activeOfficer.handle,
                            )
                                ? "PINNED ✓"
                                : "PIN WATCHLIST"
                        }}
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
