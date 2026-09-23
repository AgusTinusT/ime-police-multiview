<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconSearch from "@/Components/Icons/search-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconPinPlus from "@/Components/Icons/star-line-svgrepo-com.svg";
import iconPinMinus from "@/Components/Icons/star-svgrepo-com.svg";
import iconFeedback from "@/Components/Icons/report-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconTarget from "@/Components/Icons/target-svgrepo-com.svg";

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
    return !!page.props.auth?.user?.is_admin || page.props.auth?.user?.role === "admin";
});

// State Management
const officers = ref(props.initialOfficers);
const searchQuery = ref("");
const selectedDept = ref("ALL"); // 'ALL', 'LIVE_ONLY', 'LSPD', 'BCSO', 'SASP', 'SAPR'
const statusFilter = ref("all"); // 'all', 'online', 'offline'
const sortBy = ref("status"); // 'status', 'subs_desc', 'subs_asc', 'name'
const viewMode = ref("grid"); // 'grid' | 'table'
const isSyncing = ref(false);

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
        alert("Mohon isi nama personil / officer.");
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

const handleAvatarError = (e, officer) => {
    e.target.src = `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officer.callsign || officer.officer_name || "officer")}`;
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
            alert("Maksimal 6 siaran pada Personal Watchlist!");
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

// Department Badge Class Helper
const getDeptBadgeClass = (dept) => {
    switch (dept) {
        case "LSPD":
            return "bg-blue-600/30 text-blue-300 border-blue-500/50";
        case "BCSO":
            return "bg-amber-600/30 text-amber-300 border-amber-500/50";
        case "SASP":
            return "bg-teal-600/30 text-teal-300 border-teal-500/50";
        case "SAPR":
        case "PARK RANGER":
            return "bg-green-600/30 text-green-300 border-green-500/50";
        default:
            return "bg-slate-700/40 text-slate-300 border-slate-600";
    }
};

// CSV Export Handler
const exportCSV = () => {
    if (!filteredOfficers.value.length) {
        alert("Tidak ada data officer untuk diekspor!");
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
        if (sortBy.value === "status") {
            if (a.is_online && !b.is_online) return -1;
            if (!a.is_online && b.is_online) return 1;
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
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

        <!-- Ambient background glow spots -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="absolute -top-32 left-1/4 -translate-x-1/2 w-[700px] h-[400px] bg-blue-600/10 blur-[140px] rounded-full"></div>
            <div class="absolute top-[35%] -right-28 w-[500px] h-[500px] bg-blue-800/15 blur-[130px] rounded-full"></div>
            <div class="absolute bottom-10 left-1/3 w-[600px] h-[350px] bg-slate-900/40 blur-[150px] rounded-full"></div>
        </div>

        <main
            class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8"
        >
            <!-- PAGE HERO & GOVERNANCE TITLE BANNER -->
            <section
                class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-blue-950/90 via-slate-900/90 to-slate-950 border border-blue-900/50 shadow-2xl relative overflow-hidden backdrop-blur-xl space-y-6"
            >
                <div
                    class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10"
                >
                    <div class="space-y-3 max-w-3xl">
                        <!-- Breadcrumb & Enterprise Badge -->
                        <div
                            class="flex flex-wrap items-center gap-2 text-xs font-mono text-slate-400"
                        >
                            <span>Direktori</span>
                            <span class="text-slate-600">›</span>
                            <span>Personil Kepolisian</span>
                            <span class="text-slate-600">›</span>
                            <span
                                class="text-blue-400 font-semibold px-2.5 py-0.5 rounded-full bg-blue-950/80 border border-blue-800/60 uppercase"
                            >
                                San Andreas Police
                            </span>
                        </div>

                        <!-- Main Title -->
                        <h1
                            class="text-3xl sm:text-4xl lg:text-5xl font-tactical font-extrabold text-slate-100 uppercase tracking-tight leading-tight"
                        >
                            Direktori & Manajemen Personil Kepolisian
                        </h1>

                        <!-- Subtitle Context -->
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl font-sans"
                        >
                            Pantau seluruh personil terdaftar, status siaga 10-8 / 10-7,
                            klasifikasi divisi operasional (LSPD, BCSO, SASP, SAPR),
                            serta siaran live stream secara real-time.
                        </p>

                        <!-- Operational Status Badges -->
                        <div
                            class="pt-1 flex flex-wrap items-center gap-2.5 text-xs font-mono text-slate-300"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-400 font-bold"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"
                                ></span>
                                10-8 Live Duty: {{ deptStats.total_live || 0 }} Unit
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-900/80 border border-slate-800"
                            >
                                <svg
                                    class="w-3.5 h-3.5 text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                Multi-Divisi: LSPD / BCSO / SASP / SAPR
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-300"
                            >
                                <svg
                                    class="w-3.5 h-3.5 text-amber-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                                Pembaruan Real-Time
                            </span>
                        </div>
                    </div>

                    <!-- Header Action Buttons (Restricted to Admin Only) -->
                    <div
                        v-if="isAdmin"
                        class="flex flex-row lg:flex-col items-center lg:items-end justify-start gap-3 shrink-0 font-mono"
                    >
                        <button
                            @click="openAddModal"
                            class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs sm:text-sm shadow-xl shadow-blue-600/30 transition transform hover:-translate-y-0.5"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                            <span>TAMBAH PENGGUNA BARU</span>
                        </button>
                        <button
                            @click="exportCSV"
                            class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-800 text-xs font-semibold transition"
                        >
                            <svg
                                class="w-4 h-4 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span>UNDUH LAPORAN (CSV)</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Top Telemetry KPI Cards -->
            <section class="grid grid-cols-2 md:grid-cols-4 gap-4 font-mono">
                <!-- Card 1: Total Officer -->
                <div
                    class="p-4 sm:p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm relative overflow-hidden group"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-slate-400"
                            >Total Personil</span
                        >
                        <div
                            class="p-2 rounded-xl bg-blue-950 text-blue-400 border border-blue-800/60"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl sm:text-3xl font-black text-slate-100"
                    >
                        {{ officers.length }}
                    </div>
                    <div
                        class="text-[11px] text-emerald-400 mt-1 flex items-center gap-1"
                    >
                        <span>✓</span> Terdaftar di database repositori
                    </div>
                </div>

                <!-- Card 2: 10-8 Live Active -->
                <div
                    class="p-4 sm:p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm relative overflow-hidden group"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-slate-400"
                            >Petugas 10-8 (On Duty)</span
                        >
                        <div
                            class="p-2 rounded-xl bg-emerald-950 text-emerald-400 border border-emerald-800/60"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl sm:text-3xl font-black text-emerald-400 flex items-center gap-2"
                    >
                        <span
                            class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"
                        ></span>
                        <span>{{ deptStats.total_live || 0 }}</span>
                    </div>
                    <div class="text-[11px] text-emerald-400 mt-1">
                        Sesi live streaming aktif
                    </div>
                </div>

                <!-- Card 3: 10-7 Off Duty -->
                <div
                    class="p-4 sm:p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm relative overflow-hidden group"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-slate-400"
                            >Petugas 10-7 (Off Duty)</span
                        >
                        <div
                            class="p-2 rounded-xl bg-zinc-800/80 text-zinc-400 border border-zinc-700/60"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl sm:text-3xl font-black text-slate-300"
                    >
                        {{
                            deptStats.total_offline ||
                            officers.length - (deptStats.total_live || 0)
                        }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">
                        Standby / Tidak siaran
                    </div>
                </div>

                <!-- Card 4: Target <1K Subs -->
                <div
                    class="p-4 sm:p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm relative overflow-hidden group"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-slate-400"
                            >Target &lt;1K Subs</span
                        >
                        <div
                            class="p-2 rounded-xl bg-amber-950 text-amber-400 border border-amber-800/60"
                        >
                            <img
                                :src="iconTarget"
                                class="w-4 h-4 invert"
                                alt=""
                            />
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl sm:text-3xl font-black text-amber-400"
                    >
                        {{
                            officers.filter(
                                (o) => (o.subscriber_count || 0) < 1000,
                            ).length
                        }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">
                        Personil butuh dukungan subscriber
                    </div>
                </div>
            </section>

            <!-- Comprehensive Search, Filter, Sort & View Mode Switcher -->
            <section
                class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl space-y-4"
            >
                <!-- Top Row: Search Input & Controls -->
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3"
                >
                    <!-- Search Input -->
                    <div class="relative flex-1 group">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-400 transition-colors"
                        >
                            <img
                                :src="iconSearch"
                                class="w-4 h-4 invert opacity-50"
                                alt=""
                            />
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari berdasarkan nama, email, departemen, peran, atau lokasi..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-9 py-2.5 text-xs sm:text-sm font-sans text-slate-100 placeholder:text-slate-500 focus:outline-none focus:border-blue-500 transition duration-200"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white text-xs font-bold font-mono transition"
                            title="Bersihkan pencarian"
                        >
                            ✕
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
                                class="appearance-none bg-slate-950 border border-slate-800 rounded-xl pl-3 py-2.5 pr-7 text-xs text-slate-200 focus:outline-none focus:border-blue-500 cursor-pointer"
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
                                class="appearance-none bg-slate-950 border border-slate-800 rounded-xl pl-3 py-2.5 pr-7 text-xs text-slate-200 focus:outline-none focus:border-blue-500 cursor-pointer"
                            >
                                <option value="status">Terakhir Aktif</option>
                                <option value="subs_desc">
                                    Tugas Terbanyak
                                </option>
                                <option value="subs_asc">
                                    Target 1K Milestone
                                </option>
                                <option value="name">Nama (A - Z)</option>
                            </select>
                        </div>

                        <!-- View Mode Switcher (Grid vs Table) -->
                        <div
                            class="inline-flex rounded-xl bg-slate-950 p-1 border border-slate-800"
                        >
                            <button
                                @click="viewMode = 'grid'"
                                :class="
                                    viewMode === 'grid'
                                        ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30'
                                        : 'text-slate-400 hover:text-white'
                                "
                                class="p-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                title="Tampilan Kartu Grid"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">Grid</span>
                            </button>
                            <button
                                @click="viewMode = 'table'"
                                :class="
                                    viewMode === 'table'
                                        ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30'
                                        : 'text-slate-400 hover:text-white'
                                "
                                class="p-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                title="Tampilan Tabel Detail"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">Tabel</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Department Division Pills -->
                <div
                    class="pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3 text-xs font-mono"
                >
                    <div
                        class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none"
                    >
                        <button
                            @click="selectedDept = 'ALL'"
                            :class="
                                selectedDept === 'ALL'
                                    ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400'
                                    : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'
                            "
                            class="px-3 py-1.5 rounded-full border transition whitespace-nowrap"
                        >
                            Semua Kesatuan ({{ officers.length }})
                        </button>
                        <button
                            @click="selectedDept = 'LIVE_ONLY'"
                            :class="
                                selectedDept === 'LIVE_ONLY'
                                    ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30 border-emerald-400'
                                    : 'bg-slate-950 text-emerald-400 hover:bg-slate-800 border-slate-800'
                            "
                            class="px-3 py-1.5 rounded-full border transition flex items-center gap-1.5 whitespace-nowrap"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
                            ></span>
                            <span
                                >10-8 Live ({{
                                    deptStats.total_live || 0
                                }})</span
                            >
                        </button>
                        <button
                            @click="selectedDept = 'LSPD'"
                            :class="
                                selectedDept === 'LSPD'
                                    ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400'
                                    : 'bg-slate-950 text-blue-300 hover:bg-slate-800 border-slate-800'
                            "
                            class="px-3 py-1.5 rounded-full border transition whitespace-nowrap"
                        >
                            LSPD ({{ deptStats.lspd_total || 0 }})
                        </button>
                        <button
                            @click="selectedDept = 'BCSO'"
                            :class="
                                selectedDept === 'BCSO'
                                    ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400'
                                    : 'bg-slate-950 text-amber-300 hover:bg-slate-800 border-slate-800'
                            "
                            class="px-3 py-1.5 rounded-full border transition whitespace-nowrap"
                        >
                            BCSO ({{ deptStats.bcso_total || 0 }})
                        </button>
                        <button
                            @click="selectedDept = 'SASP'"
                            :class="
                                selectedDept === 'SASP'
                                    ? 'bg-teal-600 text-white font-bold shadow-md shadow-teal-600/30 border-teal-400'
                                    : 'bg-slate-950 text-teal-300 hover:bg-slate-800 border-slate-800'
                            "
                            class="px-3 py-1.5 rounded-full border transition whitespace-nowrap"
                        >
                            SASP ({{ deptStats.sasp_total || 0 }})
                        </button>
                        <button
                            @click="selectedDept = 'SAPR'"
                            :class="
                                selectedDept === 'SAPR'
                                    ? 'bg-green-600 text-white font-bold shadow-md shadow-green-600/30 border-green-400'
                                    : 'bg-slate-950 text-green-300 hover:bg-slate-800 border-slate-800'
                            "
                            class="px-3 py-1.5 rounded-full border transition whitespace-nowrap"
                        >
                            SAPR ({{ deptStats.sapr_total || 0 }})
                        </button>
                    </div>

                    <div class="text-slate-400 shrink-0">
                        Menampilkan
                        <strong class="text-slate-100">{{
                            filteredOfficers.length
                        }}</strong>
                        dari
                        <strong class="text-slate-100">{{
                            officers.length
                        }}</strong>
                        pengguna
                    </div>
                </div>
            </section>

            <!-- Empty Search State -->
            <div
                v-if="filteredOfficers.length === 0"
                class="py-16 text-center text-slate-500 bg-slate-900/60 rounded-2xl border border-slate-800 space-y-3"
            >
                <div
                    class="w-12 h-12 rounded-2xl bg-blue-950/80 border border-blue-800/60 flex items-center justify-center mx-auto text-blue-400"
                >
                    <img
                        :src="iconSearch"
                        class="w-6 h-6 invert opacity-60"
                        alt=""
                    />
                </div>
                <h3
                    class="text-sm font-bold text-slate-200 uppercase font-tactical tracking-wider"
                >
                    PENGGUNA TIDAK DITEMUKAN
                </h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Tidak ada pengguna yang cocok dengan kriteria pencarian atau
                    filter klasifikasi yang Anda terapkan.
                </p>
                <button
                    @click="
                        searchQuery = '';
                        selectedDept = 'ALL';
                        statusFilter = 'all';
                    "
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-mono font-bold uppercase rounded-xl transition"
                >
                    Reset Filter Pencarian
                </button>
            </div>

            <!-- VIEW MODE 1: GRID VIEW -->
            <div
                v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <div
                    v-for="officer in filteredOfficers"
                    :key="officer.id"
                    @click="openDrawer(officer)"
                    class="bg-slate-900/90 border rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 hover:border-blue-500/50 hover:shadow-xl cursor-pointer group"
                    :class="
                        officer.is_online
                            ? 'border-emerald-500/40'
                            : 'border-slate-800'
                    "
                >
                    <div>
                        <!-- Card Header: Department, Callsign, Status Badge -->
                        <div
                            class="flex items-center justify-between gap-2 mb-3"
                        >
                            <div class="flex items-center space-x-2 min-w-0">
                                <span
                                    class="px-2 py-0.5 text-[10px] font-black rounded border font-mono uppercase"
                                    :class="
                                        getDeptBadgeClass(officer.department)
                                    "
                                >
                                    {{ officer.department }}
                                </span>
                                <span
                                    class="font-mono text-xs font-bold text-slate-300 truncate"
                                    >{{ officer.callsign }}</span
                                >
                                <span
                                    class="text-[10px] text-slate-500 font-mono"
                                    >{{ officer.badge_number }}</span
                                >
                            </div>

                            <!-- Status Duty Badge -->
                            <div class="shrink-0">
                                <span
                                    v-if="officer.is_online"
                                    class="inline-flex items-center gap-1.5 text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 font-mono font-bold"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
                                    ></span>
                                    <span>10-8 ON DUTY</span>
                                </span>
                                <span
                                    v-else
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-slate-950 text-slate-500 border border-slate-800 font-mono"
                                >
                                    10-7 OFF DUTY
                                </span>
                            </div>
                        </div>

                        <!-- Officer Identity Block (YouTube Profile Photo Prominently Featured) -->
                        <div class="flex items-start gap-3 mt-1.5">
                            <!-- Profile Photo Avatar -->
                            <div class="shrink-0">
                                <img
                                    :src="
                                        officer.avatar_url ||
                                        `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officer.callsign || officer.officer_name || 'officer')}`
                                    "
                                    :alt="officer.officer_name"
                                    referrerpolicy="no-referrer"
                                    @error="
                                        (e) => handleAvatarError(e, officer)
                                    "
                                    class="w-12 h-12 rounded-full object-cover border-2 border-slate-700/80 shadow-md transition-transform duration-200 group-hover:scale-105"
                                />
                            </div>

                            <!-- Identity Info -->
                            <div class="min-w-0 flex-1">
                                <h3
                                    class="text-base font-bold text-slate-100 truncate group-hover:text-blue-300 transition"
                                    :title="officer.officer_name"
                                >
                                    {{ officer.officer_name }}
                                </h3>

                                <div
                                    class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5 truncate font-mono"
                                >
                                    <span
                                        class="text-slate-300 font-semibold"
                                        >{{ officer.rank }}</span
                                    >
                                    <template v-if="officer.streamer_name">
                                        <span class="text-slate-600">•</span>
                                        <span class="text-slate-400 truncate">{{
                                            officer.streamer_name
                                        }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Subscriber Info & Road to 1K Progress Bar -->
                        <div class="mt-3.5 font-mono">
                            <div
                                v-if="(officer.subscriber_count || 0) < 1000"
                                class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-2.5"
                            >
                                <div
                                    class="flex items-center justify-between text-[11px] mb-1"
                                >
                                    <span
                                        class="text-slate-400 flex items-center gap-1"
                                    >
                                        <img
                                            :src="iconTarget"
                                            class="w-3 h-3 invert opacity-70"
                                            alt=""
                                        />
                                        <span>Road to 1,000 Subs:</span>
                                    </span>
                                    <span class="font-bold text-amber-400">
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
                                    class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden"
                                >
                                    <div
                                        class="h-full bg-amber-500 rounded-full transition-all duration-500"
                                        :style="{
                                            width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="text-[11px] text-slate-400 flex items-center justify-between px-1 bg-slate-950/40 rounded-xl p-2 border border-slate-800/60"
                            >
                                <span>Total Subscribers:</span>
                                <span class="font-bold text-slate-200">{{
                                    Number(
                                        officer.subscriber_count || 0,
                                    ).toLocaleString("id-ID")
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div
                        class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between"
                        @click.stop
                    >
                        <div
                            class="text-[10px] font-mono text-slate-500 truncate"
                        >
                            <span
                                >Sektor:
                                <strong class="text-slate-400">{{
                                    officer.patrol_zone || "LS"
                                }}</strong></span
                            >
                        </div>

                        <div class="flex items-center space-x-1.5 shrink-0">
                            <!-- Pin to Watchlist -->
                            <button
                                @click="
                                    togglePin(
                                        officer.channel_id || officer.handle,
                                        officer.officer_name,
                                    )
                                "
                                class="w-8 h-8 rounded-xl border transition flex items-center justify-center"
                                :class="
                                    isPinned(
                                        officer.channel_id || officer.handle,
                                    )
                                        ? 'text-purple-300 bg-purple-950/80 border-purple-500/50'
                                        : 'text-slate-400 hover:text-purple-300 bg-slate-950 hover:bg-slate-800 border-slate-800'
                                "
                                :title="
                                    isPinned(
                                        officer.channel_id || officer.handle,
                                    )
                                        ? 'Hapus dari Watchlist'
                                        : 'Pin ke Watchlist'
                                "
                            >
                                <img
                                    :src="
                                        isPinned(
                                            officer.channel_id ||
                                                officer.handle,
                                        )
                                            ? iconPinMinus
                                            : iconPinPlus
                                    "
                                    class="w-3.5 h-3.5 invert"
                                    alt=""
                                />
                            </button>

                            <!-- 1-Click YouTube Subscribe Popup -->
                            <button
                                @click="
                                    openSubscribePopup(
                                        officer.channel_id || officer.handle,
                                        officer.officer_name,
                                    )
                                "
                                class="w-8 h-8 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-300 hover:text-white transition flex items-center justify-center font-mono"
                                title="Subscribe ke YouTube channel"
                            >
                                <span class="text-[10px] font-black">SUB</span>
                            </button>

                            <!-- YouTube External Link -->
                            <a
                                :href="`https://www.youtube.com/${officer.handle}`"
                                target="_blank"
                                class="w-8 h-8 rounded-xl bg-slate-950 border border-slate-800 hover:border-blue-500/50 transition flex items-center justify-center"
                                title="Buka Channel YouTube"
                            >
                                <img
                                    :src="iconExternal"
                                    class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100"
                                    alt=""
                                />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW MODE 2: TABLE VIEW -->
            <div
                v-else
                class="bg-slate-900/90 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm font-sans">
                        <thead
                            class="bg-slate-950 text-slate-400 uppercase text-[11px] font-mono font-bold tracking-wider border-b border-slate-800"
                        >
                            <tr>
                                <th scope="col" class="py-3.5 px-4 sm:px-6">
                                    Pengguna & Foto Profil
                                </th>
                                <th scope="col" class="py-3.5 px-4">
                                    Klasifikasi / Rank
                                </th>
                                <th scope="col" class="py-3.5 px-4">
                                    Status Akun
                                </th>
                                <th scope="col" class="py-3.5 px-4">
                                    Subscribers / Target
                                </th>
                                <th scope="col" class="py-3.5 px-4">
                                    Sektor / TAC
                                </th>
                                <th scope="col" class="py-3.5 px-4 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-800/80 text-slate-200"
                        >
                            <tr
                                v-for="officer in filteredOfficers"
                                :key="officer.id"
                                @click="openDrawer(officer)"
                                class="hover:bg-slate-800/50 transition cursor-pointer group"
                            >
                                <!-- Officer & Avatar -->
                                <td class="py-3.5 px-4 sm:px-6">
                                    <div class="flex items-center space-x-3">
                                        <img
                                            :src="
                                                officer.avatar_url ||
                                                `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officer.callsign || officer.officer_name || 'officer')}`
                                            "
                                            :alt="officer.officer_name"
                                            referrerpolicy="no-referrer"
                                            @error="
                                                (e) =>
                                                    handleAvatarError(
                                                        e,
                                                        officer,
                                                    )
                                            "
                                            class="w-10 h-10 rounded-full object-cover border border-slate-700 shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <div
                                                class="font-bold text-slate-100 group-hover:text-blue-300 transition truncate"
                                            >
                                                {{ officer.officer_name }}
                                            </div>
                                            <div
                                                class="text-xs text-slate-400 font-mono flex items-center gap-1.5"
                                            >
                                                <span
                                                    class="text-slate-300 font-semibold"
                                                    >{{
                                                        officer.callsign
                                                    }}</span
                                                >
                                                <span class="text-slate-600"
                                                    >•</span
                                                >
                                                <span class="text-slate-500">{{
                                                    officer.badge_number
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Department & Rank -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <span
                                            class="px-2 py-0.5 text-[10px] font-black rounded border font-mono uppercase"
                                            :class="
                                                getDeptBadgeClass(
                                                    officer.department,
                                                )
                                            "
                                        >
                                            {{ officer.department }}
                                        </span>
                                        <div
                                            class="text-xs font-mono text-slate-400 truncate"
                                        >
                                            {{ officer.rank }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Duty -->
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="officer.is_online"
                                        class="inline-flex items-center gap-1.5 text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 font-mono font-bold"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
                                        ></span>
                                        <span>10-8 LIVE</span>
                                    </span>
                                    <span
                                        v-else
                                        class="text-[10px] px-2 py-0.5 rounded-full bg-slate-950 text-slate-500 border border-slate-800 font-mono"
                                    >
                                        10-7 OFF
                                    </span>
                                </td>

                                <!-- Subscribers / Progress Bar -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div
                                        v-if="
                                            (officer.subscriber_count || 0) <
                                            1000
                                        "
                                        class="w-36 space-y-1"
                                    >
                                        <div
                                            class="flex items-center justify-between text-[10px] text-slate-400"
                                        >
                                            <span>Road 1K</span>
                                            <span
                                                class="font-bold text-amber-400"
                                                >{{
                                                    Number(
                                                        officer.subscriber_count ||
                                                            0,
                                                    ).toLocaleString("id-ID")
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden"
                                        >
                                            <div
                                                class="h-full bg-amber-500 rounded-full"
                                                :style="{
                                                    width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="text-xs font-bold text-slate-200"
                                    >
                                        {{
                                            Number(
                                                officer.subscriber_count || 0,
                                            ).toLocaleString("id-ID")
                                        }}
                                    </div>
                                </td>

                                <!-- Sector / TAC -->
                                <td
                                    class="py-3.5 px-4 font-mono text-xs text-slate-400"
                                >
                                    <div>
                                        Sektor:
                                        <strong class="text-slate-300">{{
                                            officer.patrol_zone || "LS"
                                        }}</strong>
                                    </div>
                                    <div
                                        v-if="officer.tac_channel"
                                        class="text-[10px] text-blue-400"
                                    >
                                        TAC-{{ officer.tac_channel }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right" @click.stop>
                                    <div
                                        class="flex items-center justify-end space-x-1.5"
                                    >
                                        <button
                                            @click="
                                                togglePin(
                                                    officer.channel_id ||
                                                        officer.handle,
                                                    officer.officer_name,
                                                )
                                            "
                                            class="w-8 h-8 rounded-xl border transition flex items-center justify-center"
                                            :class="
                                                isPinned(
                                                    officer.channel_id ||
                                                        officer.handle,
                                                )
                                                    ? 'text-purple-300 bg-purple-950/80 border-purple-500/50'
                                                    : 'text-slate-400 hover:text-purple-300 bg-slate-950 hover:bg-slate-800 border-slate-800'
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
                                            <img
                                                :src="
                                                    isPinned(
                                                        officer.channel_id ||
                                                            officer.handle,
                                                    )
                                                        ? iconPinMinus
                                                        : iconPinPlus
                                                "
                                                class="w-3.5 h-3.5 invert"
                                                alt=""
                                            />
                                        </button>

                                        <button
                                            @click="
                                                openSubscribePopup(
                                                    officer.channel_id ||
                                                        officer.handle,
                                                    officer.officer_name,
                                                )
                                            "
                                            class="px-2.5 py-1.5 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-300 hover:text-white transition font-mono text-[10px] font-bold"
                                            title="Subscribe ke YouTube channel"
                                        >
                                            SUB
                                        </button>

                                        <a
                                            :href="`https://www.youtube.com/${officer.handle}`"
                                            target="_blank"
                                            class="w-8 h-8 rounded-xl bg-slate-950 border border-slate-800 hover:border-blue-500/50 transition flex items-center justify-center"
                                            title="Buka Channel YouTube"
                                        >
                                            <img
                                                :src="iconExternal"
                                                class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100"
                                                alt=""
                                            />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- ADD OFFICER MODAL (Directly adapted from addUserModal in officer_reverensi.html) -->
        <div
            v-if="isAddModalOpen"
            class="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4"
        >
            <!-- Modal Backdrop -->
            <div
                @click="closeAddModal"
                class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity duration-300"
            ></div>

            <!-- Modal Panel -->
            <div
                class="relative z-10 w-full max-w-lg bg-slate-950 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4"
            >
                <div
                    class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-900/90"
                >
                    <div class="flex items-center space-x-2.5">
                        <div
                            class="p-2 rounded-xl bg-blue-950 text-blue-400 border border-blue-800/60"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-100 font-tactical uppercase tracking-wider"
                            >
                                Tambah Pengguna / Officer Baru
                            </h3>
                            <p class="text-xs text-slate-400">
                                Daftarkan akun perwira dan tetapkan hak akses
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeAddModal"
                        class="text-slate-400 hover:text-white p-1 rounded-xl hover:bg-slate-800 font-mono transition text-xs font-bold"
                    >
                        ✕
                    </button>
                </div>

                <form
                    @submit.prevent="handleCreateOfficer"
                    class="p-6 space-y-4 font-sans text-xs"
                >
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">
                            Nama Lengkap & Callsign *
                        </label>
                        <input
                            v-model="newOfficerForm.officer_name"
                            type="text"
                            required
                            placeholder="Contoh: dr. Adelia Putri / Ofc. Budi"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 text-xs focus:outline-none focus:border-blue-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block font-semibold text-slate-300 mb-1"
                            >
                                Callsign Dinas
                            </label>
                            <input
                                v-model="newOfficerForm.callsign"
                                type="text"
                                placeholder="1-ADAM-12"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 text-xs font-mono focus:outline-none focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block font-semibold text-slate-300 mb-1"
                            >
                                Badge ID / Phone
                            </label>
                            <input
                                v-model="newOfficerForm.badge_number"
                                type="text"
                                placeholder="#302 / +62 812..."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 text-xs font-mono focus:outline-none focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block font-semibold text-slate-300 mb-1"
                            >
                                Departemen / Kesatuan *
                            </label>
                            <select
                                v-model="newOfficerForm.department"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 text-xs font-mono focus:outline-none focus:border-blue-500 cursor-pointer"
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
                                class="block font-semibold text-slate-300 mb-1"
                            >
                                Rank / Pangkat Dinas
                            </label>
                            <input
                                v-model="newOfficerForm.rank"
                                type="text"
                                placeholder="Sergeant / Officer II"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 text-xs focus:outline-none focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">
                            Handle YouTube / URL Channel
                        </label>
                        <input
                            v-model="newOfficerForm.handle"
                            type="text"
                            placeholder="@HandleYouTube"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 text-xs font-mono focus:outline-none focus:border-blue-500"
                        />
                    </div>

                    <div
                        class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2 font-mono"
                    >
                        <button
                            type="button"
                            @click="closeAddModal"
                            class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold uppercase transition shadow-lg shadow-blue-600/30"
                        >
                            Simpan & Daftarkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slide-over Officer Detail Drawer -->
        <div v-if="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden">
            <!-- Backdrop Overlay -->
            <div
                @click="closeDrawer"
                class="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity duration-300"
            ></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div
                    class="w-screen max-w-md bg-slate-950 border-l border-slate-800 shadow-2xl flex flex-col justify-between text-slate-200"
                >
                    <!-- Drawer Header -->
                    <div
                        class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-900/90"
                    >
                        <div class="flex items-center space-x-2">
                            <span
                                class="text-xs font-mono font-bold uppercase tracking-wider text-blue-400"
                                >PROFIL PERSONIL DISPATCH</span
                            >
                        </div>
                        <button
                            @click="closeDrawer"
                            class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition text-sm font-mono font-bold"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Drawer Content Body -->
                    <div
                        v-if="activeOfficer"
                        class="p-6 overflow-y-auto space-y-6 flex-1 text-xs sm:text-sm font-sans"
                    >
                        <!-- Header Profile Avatar & Basic Info -->
                        <div
                            class="flex items-start gap-4 p-4 rounded-2xl bg-slate-900 border border-slate-800"
                        >
                            <img
                                :src="
                                    activeOfficer.avatar_url ||
                                    `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(activeOfficer.callsign || activeOfficer.officer_name || 'officer')}`
                                "
                                :alt="activeOfficer.officer_name"
                                referrerpolicy="no-referrer"
                                @error="
                                    (e) => handleAvatarError(e, activeOfficer)
                                "
                                class="w-16 h-16 rounded-full object-cover border-2 border-slate-700 shadow-md shrink-0"
                            />
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-black rounded border font-mono uppercase"
                                        :class="
                                            getDeptBadgeClass(
                                                activeOfficer.department,
                                            )
                                        "
                                    >
                                        {{ activeOfficer.department }}
                                    </span>
                                    <span
                                        class="text-xs font-mono text-slate-400 font-bold"
                                        >{{ activeOfficer.badge_number }}</span
                                    >
                                </div>
                                <h2
                                    class="text-lg font-bold text-slate-100 leading-snug"
                                >
                                    {{ activeOfficer.officer_name }}
                                </h2>
                                <div
                                    class="text-xs font-mono text-blue-400 font-semibold"
                                >
                                    {{ activeOfficer.callsign }} •
                                    {{ activeOfficer.rank }}
                                </div>
                            </div>
                        </div>

                        <!-- Status & Patrol Details Grid -->
                        <div class="grid grid-cols-2 gap-3 font-mono text-xs">
                            <div
                                class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-1"
                            >
                                <span
                                    class="text-[10px] text-slate-500 uppercase font-bold"
                                    >Status Siaran</span
                                >
                                <div>
                                    <span
                                        v-if="activeOfficer.is_online"
                                        class="inline-flex items-center gap-1.5 text-xs text-emerald-400 font-bold"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"
                                        ></span>
                                        10-8 LIVE ON DUTY
                                    </span>
                                    <span v-else class="text-xs text-slate-500"
                                        >10-7 OFF DUTY</span
                                    >
                                </div>
                            </div>

                            <div
                                class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-1"
                            >
                                <span
                                    class="text-[10px] text-slate-500 uppercase font-bold"
                                    >Sektor Patroli</span
                                >
                                <div class="text-xs text-slate-200 font-bold">
                                    {{
                                        activeOfficer.patrol_zone ||
                                        "Los Santos"
                                    }}
                                </div>
                            </div>
                        </div>

                        <!-- YouTube Streamer Info & Subscriber Stats -->
                        <div
                            class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-3 font-mono"
                        >
                            <div
                                class="flex items-center justify-between text-xs border-b border-slate-800 pb-2"
                            >
                                <span class="text-slate-400"
                                    >Streamer Name:</span
                                >
                                <span class="text-slate-200 font-bold">{{
                                    activeOfficer.streamer_name || "-"
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between text-xs border-b border-slate-800 pb-2"
                            >
                                <span class="text-slate-400"
                                    >Handle YouTube:</span
                                >
                                <span class="text-blue-400 font-bold">{{
                                    activeOfficer.handle || "-"
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-slate-400"
                                    >Total Subscribers:</span
                                >
                                <span class="text-emerald-400 font-bold">{{
                                    Number(
                                        activeOfficer.subscriber_count || 0,
                                    ).toLocaleString("id-ID")
                                }}</span>
                            </div>

                            <!-- Road to 1K Subs Bar in Drawer -->
                            <div
                                v-if="
                                    (activeOfficer.subscriber_count || 0) < 1000
                                "
                                class="pt-2"
                            >
                                <div
                                    class="flex items-center justify-between text-[11px] text-amber-400 mb-1"
                                >
                                    <span>Target 1,000 Subscribers:</span>
                                    <span class="font-bold"
                                        >{{
                                            Number(
                                                activeOfficer.subscriber_count ||
                                                    0,
                                            ).toLocaleString("id-ID")
                                        }}
                                        / 1.000</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-slate-800 rounded-full h-2 overflow-hidden"
                                >
                                    <div
                                        class="h-full bg-amber-500 rounded-full"
                                        :style="{
                                            width: `${Math.min(100, Math.round(((activeOfficer.subscriber_count || 0) / 1000) * 100))}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Drawer Action Footer -->
                    <div
                        v-if="activeOfficer"
                        class="p-4 border-t border-slate-800 bg-slate-900 flex items-center justify-between gap-2 font-mono text-xs"
                    >
                        <button
                            @click="
                                togglePin(
                                    activeOfficer.channel_id ||
                                        activeOfficer.handle,
                                    activeOfficer.officer_name,
                                )
                            "
                            class="px-3.5 py-2 rounded-xl border text-xs font-semibold transition"
                            :class="
                                isPinned(
                                    activeOfficer.channel_id ||
                                        activeOfficer.handle,
                                )
                                    ? 'bg-purple-950/80 border-purple-500/50 text-purple-300'
                                    : 'bg-slate-950 border-slate-800 text-slate-300 hover:text-white'
                            "
                        >
                            {{
                                isPinned(
                                    activeOfficer.channel_id ||
                                        activeOfficer.handle,
                                )
                                    ? "PINNED ✓"
                                    : "PIN WATCHLIST"
                            }}
                        </button>

                        <button
                            @click="
                                openSubscribePopup(
                                    activeOfficer.channel_id ||
                                        activeOfficer.handle,
                                    activeOfficer.officer_name,
                                )
                            "
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition shadow-md shadow-blue-600/30"
                        >
                            SUBSCRIBE YOUTUBE
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Toast Notification -->
        <div
            v-if="showToastNotification"
            class="fixed bottom-6 right-6 z-50 bg-slate-900 border border-blue-500/60 text-slate-100 px-4 py-3 rounded-2xl shadow-2xl font-mono text-xs flex items-center gap-2"
        >
            <span class="text-blue-400 font-bold">✓</span>
            <span>{{ toastMessage }}</span>
        </div>
    </TacticalLayout>
</template>
