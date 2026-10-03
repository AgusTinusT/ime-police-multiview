<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";
import iconSaver from "@/Components/Icons/gauge-low-svgrepo-com.svg";
import iconMute from "@/Components/Icons/audio-off-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/audio-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-tac-svgrepo-com.svg";
import iconFullscreen from "@/Components/Icons/full-screen-svgrepo-com.svg";
import iconGrid4 from "@/Components/Icons/grid-4-svgrepo-com.svg";
import iconTheater from "@/Components/Icons/rectangle-o-svgrepo-com.svg";

const props = defineProps({
    selectedDepartment: { type: String, required: true },
    selectedLayout: { type: String, default: "auto" },
    searchFilter: { type: String, default: "" },
    isDataSaverEnabled: { type: Boolean, default: false },
    isSyncingFeeds: { type: Boolean, default: false },
    isTheaterMode: { type: Boolean, default: false },
    activeAudioVideoId: { type: String, default: null },
    visibleStreamsCount: { type: Number, default: 0 },
    getDeptCount: { type: Function, required: true },
    selectedDeptLabel: { type: String, default: "ALL" },
    selectedLayoutLabel: { type: String, default: "Auto Grid" },
    primaryFocusedStream: { type: Object, default: null },
});

const emit = defineEmits([
    "update:selectedDepartment",
    "update:selectedLayout",
    "update:searchFilter",
    "trigger-manual-sync",
    "toggle-fullscreen",
    "toggle-theater-mode",
    "toggle-audio",
    "disable-data-saver",
    "enable-data-saver",
    "open-officer-visibility",
]);

// Popover Toggle States
const isDockCategoryOpen = ref(false);
const isDockLayoutOpen = ref(false);
const isDockSearchOpen = ref(false);

const toggleDockCategory = () => {
    isDockCategoryOpen.value = !isDockCategoryOpen.value;
    if (isDockCategoryOpen.value) {
        isDockLayoutOpen.value = false;
        isDockSearchOpen.value = false;
    }
};

const toggleDockLayout = () => {
    isDockLayoutOpen.value = !isDockLayoutOpen.value;
    if (isDockLayoutOpen.value) {
        isDockCategoryOpen.value = false;
        isDockSearchOpen.value = false;
    }
};

const toggleDockSearch = () => {
    isDockSearchOpen.value = !isDockSearchOpen.value;
    if (isDockSearchOpen.value) {
        isDockCategoryOpen.value = false;
        isDockLayoutOpen.value = false;
    }
};

const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return iconSasp;
    if (d.includes("SAPR") || d.includes("RANGER")) return iconSapr;
    return iconAllUnits;
};
</script>

<template>
    <div
        v-if="selectedLayout !== 'focus'"
        class="fixed bottom-0 inset-x-0 z-50 flex flex-col items-center pointer-events-none"
    >
        <!-- Popover 1: Department & Category Selector Menu -->
        <div
            v-if="isDockCategoryOpen"
            class="hidden sm:block pointer-events-auto mb-2.5 w-80 sm:w-96 max-h-[70vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs space-y-3 animate-in fade-in zoom-in-95 font-sans scrollbar-thin scrollbar-thumb-slate-700"
            @click.stop
        >
            <div
                class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-300 font-bold font-mono"
            >
                <span class="flex items-center gap-1.5 text-blue-400">
                    <img
                        :src="iconAllUnits"
                        class="w-4 h-4 invert opacity-90"
                    />
                    <span>PILIH KATEGORI / DEPARTEMEN</span>
                </span>
                <button
                    @click="isDockCategoryOpen = false"
                    class="text-slate-500 hover:text-white p-1"
                >
                    ✕
                </button>
            </div>

            <!-- Main Departments Grid -->
            <div class="grid grid-cols-2 gap-1.5">
                <button
                    @click="
                        emit('update:selectedDepartment', 'ALL');
                        isDockCategoryOpen = false;
                    "
                    :class="[
                        'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedDepartment === 'ALL'
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <img
                            :src="iconAllUnits"
                            class="w-4 h-4 invert opacity-90"
                        />
                        <span>All units</span>
                    </div>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                        >{{ getDeptCount("ALL") }}</span
                    >
                </button>

                <button
                    @click="
                        emit('update:selectedDepartment', 'LSPD');
                        isDockCategoryOpen = false;
                    "
                    :class="[
                        'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedDepartment === 'LSPD'
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <img
                            :src="iconLspd"
                            class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                        />
                        <span>LSPD Metro</span>
                    </div>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                        >{{ getDeptCount("LSPD") }}</span
                    >
                </button>

                <button
                    @click="
                        emit('update:selectedDepartment', 'BCSO');
                        isDockCategoryOpen = false;
                    "
                    :class="[
                        'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedDepartment === 'BCSO'
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <img
                            :src="iconBcso"
                            class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                        />
                        <span>BCSO Sheriff</span>
                    </div>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                        >{{ getDeptCount("BCSO") }}</span
                    >
                </button>

                <button
                    @click="
                        emit('update:selectedDepartment', 'SASP');
                        isDockCategoryOpen = false;
                    "
                    :class="[
                        'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedDepartment === 'SASP'
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <img
                            :src="iconSasp"
                            class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                        />
                        <span>SASP State</span>
                    </div>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                        >{{ getDeptCount("SASP") }}</span
                    >
                </button>

                <button
                    @click="
                        emit('update:selectedDepartment', 'SAPR');
                        isDockCategoryOpen = false;
                    "
                    :class="[
                        'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedDepartment === 'SAPR'
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <img
                            :src="iconSapr"
                            class="w-4 h-4 object-contain brightness-0 invert opacity-90"
                        />
                        <span>SAPR Ranger</span>
                    </div>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                        >{{ getDeptCount("SAPR") }}</span
                    >
                </button>

                <button
                    @click="
                        emit('update:selectedDepartment', 'PERSONAL');
                        isDockCategoryOpen = false;
                    "
                    :class="[
                        'p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedDepartment === 'PERSONAL'
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <img
                            :src="iconPersonal"
                            class="w-4 h-4 invert opacity-90"
                        />
                        <span>Watchlist</span>
                    </div>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-900 border border-slate-800 text-slate-300 font-bold"
                        >{{ getDeptCount("PERSONAL") }}</span
                    >
                </button>
            </div>

            <!-- TAC Channels Section -->
            <div class="pt-2 border-t border-slate-800 space-y-1.5">
                <div
                    class="text-[11px] font-mono font-bold text-amber-400 flex items-center gap-1.5"
                >
                    <img
                        :src="iconRadio"
                        class="w-3.5 h-3.5 invert opacity-90"
                    />
                    <span>KANAL RADIO OPERASIONAL (TAC)</span>
                </div>
                <div class="grid grid-cols-5 gap-1">
                    <button
                        v-for="i in 10"
                        :key="`tac-${i}`"
                        @click="
                            emit('update:selectedDepartment', `TAC_${i}`);
                            isDockCategoryOpen = false;
                        "
                        :class="[
                            'p-1.5 rounded-md border text-center transition flex flex-col items-center justify-center gap-0.5 cursor-pointer',
                            selectedDepartment === `TAC_${i}`
                                ? 'bg-blue-600 text-white border-blue-500 font-bold'
                                : getDeptCount(`TAC_${i}`) > 0
                                  ? 'bg-amber-950/40 text-amber-300 border-amber-800/50 font-semibold'
                                  : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-800',
                        ]"
                    >
                        <span class="text-[10px] font-mono"
                            >TAC {{ i }}</span
                        >
                        <span
                            class="text-[9px] px-1 rounded font-mono"
                            :class="
                                getDeptCount(`TAC_${i}`) > 0
                                    ? 'bg-amber-400 text-black font-bold'
                                    : 'bg-slate-900 text-slate-500'
                            "
                        >
                            {{ getDeptCount(`TAC_${i}`) }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Popover 2: Grid Layout Selector Menu -->
        <div
            v-if="isDockLayoutOpen"
            class="hidden sm:block pointer-events-auto mb-2.5 w-64 bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs space-y-2 animate-in fade-in zoom-in-95 font-sans"
            @click.stop
        >
            <div
                class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-300 font-bold font-mono"
            >
                <span>TATA LETAK GRID (LAYOUT)</span>
                <button
                    @click="isDockLayoutOpen = false"
                    class="text-slate-500 hover:text-white p-1"
                >
                    ✕
                </button>
            </div>
            <div class="space-y-1">
                <button
                    v-for="l in [
                        {
                            id: 'auto',
                            name: 'Auto Grid',
                            desc: 'Adaptif sesuai jumlah stream',
                        },
                        {
                            id: 'grid-1x2',
                            name: '1 x 2 Grid',
                            desc: '2 Kamera bersampingan',
                        },
                        {
                            id: 'grid-2x2',
                            name: '2 x 2 Grid',
                            desc: '4 Kamera Quad Matrix',
                        },
                        {
                            id: 'grid-3x3',
                            name: '3 x 3 Grid',
                            desc: '9 Kamera Matrix',
                        },
                        {
                            id: 'grid-4x4',
                            name: '4 x 4 Grid',
                            desc: '16 Kamera Matrix',
                        },
                    ]"
                    :key="l.id"
                    @click="
                        emit('update:selectedLayout', l.id);
                        isDockLayoutOpen = false;
                    "
                    :class="[
                        'w-full p-2 rounded-md border flex items-center justify-between transition text-left cursor-pointer',
                        selectedLayout === l.id
                            ? 'bg-blue-600 text-white border-blue-500 font-bold'
                            : 'bg-slate-950 hover:bg-slate-800 text-slate-300 border-slate-800',
                    ]"
                >
                    <div>
                        <div class="font-bold text-xs">
                            {{ l.name }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-light">
                            {{ l.desc }}
                        </div>
                    </div>
                    <span v-if="selectedLayout === l.id" class="text-xs"
                        >✓</span
                    >
                </button>
            </div>
        </div>

        <!-- Popover 3: Quick Search Filter Input Bar -->
        <div
            v-if="isDockSearchOpen"
            class="pointer-events-auto mb-2.5 w-72 sm:w-80 bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-xs animate-in fade-in zoom-in-95 font-sans"
            @click.stop
        >
            <div class="relative flex items-center">
                <input
                    type="text"
                    :value="searchFilter"
                    @input="
                        emit('update:searchFilter', $event.target.value)
                    "
                    placeholder="Cari perwira, callsign, divisi, judul..."
                    class="w-full bg-slate-950 text-white placeholder-slate-500 text-xs px-3 py-2 pr-8 rounded-md border border-slate-800 focus:outline-none focus:border-blue-500 font-sans"
                />
                <button
                    v-if="searchFilter"
                    @click="emit('update:searchFilter', '')"
                    class="absolute right-2.5 text-slate-400 hover:text-white text-xs font-bold p-0.5 rounded"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- BOTTOM STICKY CONTROL BAR (DESKTOP ONLY) -->
        <div
            class="hidden sm:flex pointer-events-auto w-full bg-slate-950 border-t border-slate-800 px-4 py-2.5 items-center justify-center gap-3 text-xs text-slate-200 overflow-x-auto scrollbar-none"
            @click.stop
        >
            <!-- Home / Beranda Button -->
            <Link
                href="/"
                class="px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                title="Kembali ke Dashboard Utama"
            >
                <svg
                    class="w-3.5 h-3.5 text-slate-400 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                    />
                </svg>
                <span>Home</span>
            </Link>

            <!-- Category Selector Button -->
            <button
                @click="toggleDockCategory"
                :class="[
                    'hidden sm:flex px-3 py-1.5 rounded-md text-xs font-bold border transition items-center gap-1.5 shrink-0 cursor-pointer',
                    isDockCategoryOpen
                        ? 'bg-slate-800 text-blue-400 border-slate-700'
                        : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800',
                ]"
                title="Pilih Kategori / Departemen Kesatuan"
            >
                <img
                    :src="getDeptIcon(selectedDepartment)"
                    class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                />
                <span class="font-mono text-[11px]">{{
                    selectedDeptLabel
                }}</span>
                <span
                    class="px-1.5 py-0.2 rounded bg-slate-950 border border-slate-800 text-blue-400 text-[10px] font-mono font-bold"
                    >{{ visibleStreamsCount }}</span
                >
                <span class="text-[10px] text-slate-400">▾</span>
            </button>

            <div
                class="hidden sm:block h-4 w-px bg-slate-800 shrink-0"
            ></div>

            <!-- Layout Selector Button -->
            <button
                @click="toggleDockLayout"
                :class="[
                    'hidden sm:flex px-3 py-1.5 rounded-md text-xs font-bold border transition items-center gap-1.5 shrink-0 cursor-pointer',
                    isDockLayoutOpen
                        ? 'bg-slate-800 text-blue-400 border-slate-700'
                        : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800',
                ]"
                title="Pilih Tata Letak Kamera (Grid Layout)"
            >
                <img
                    :src="iconGrid4"
                    class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                    alt="Grid Layout"
                />
                <span class="font-mono text-[11px]">{{
                    selectedLayoutLabel
                }}</span>
                <span class="text-[10px] text-slate-400">▾</span>
            </button>

            <div
                class="hidden sm:block h-4 w-px bg-slate-800 shrink-0"
            ></div>

            <!-- Quick Search Filter Button -->
            <button
                @click="toggleDockSearch"
                :class="[
                    'flex p-2 rounded-md border transition shrink-0 relative cursor-pointer',
                    isDockSearchOpen || searchFilter
                        ? 'bg-slate-800 text-blue-400 border-slate-700'
                        : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800',
                ]"
                title="Cari Petugas / Stream"
            >
                <svg
                    class="w-3.5 h-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
                <span
                    v-if="searchFilter"
                    class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-blue-400 rounded-full"
                ></span>
            </button>

            <!-- Officer Visibility Bottom Sheet Button ([⚙️ Kelola]) -->
            <button
                @click="emit('open-officer-visibility')"
                class="flex px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition items-center gap-1.5 shrink-0 cursor-pointer"
                title="Kelola Tampilan / Enable Disable Perwira (Bottom Sheet)"
            >
                <svg
                    class="w-3.5 h-3.5 text-slate-400 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                </svg>
                <span>Kelola</span>
            </button>

            <!-- Sync / Refresh Feeds Button -->
            <button
                @click="emit('trigger-manual-sync')"
                :disabled="isSyncingFeeds"
                class="p-2 rounded-md bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-slate-300 border border-slate-800 transition shrink-0 cursor-pointer"
                title="Sinkronkan Feeds Live Terbaru"
            >
                <img
                    :src="iconRefresh"
                    class="w-3.5 h-3.5 invert opacity-90"
                    :class="{ 'animate-spin': isSyncingFeeds }"
                />
            </button>

            <!-- Data Saver Toggle Button -->
            <button
                @click="
                    isDataSaverEnabled
                        ? emit('disable-data-saver')
                        : emit('enable-data-saver')
                "
                :class="[
                    'px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                    isDataSaverEnabled
                        ? 'bg-slate-800 text-amber-400 border-slate-700'
                        : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800',
                ]"
                :title="
                    isDataSaverEnabled
                        ? 'Hemat Data Aktif (Tutup untuk otomatis play)'
                        : 'Aktifkan Hemat Data'
                "
            >
                <img
                    :src="iconSaver"
                    class="w-3.5 h-3.5 invert opacity-90"
                />
                <span>{{
                    isDataSaverEnabled ? "Saver ON" : "Saver OFF"
                }}</span>
            </button>

            <div
                class="hidden sm:block h-4 w-px bg-slate-800 shrink-0"
            ></div>

            <!-- Theater / Bioskop Mode Toggle -->
            <button
                @click="emit('toggle-theater-mode')"
                class="hidden sm:flex px-3 py-1.5 rounded-md text-[11px] font-mono font-bold border transition items-center gap-1.5 shrink-0 cursor-pointer"
                :class="
                    isTheaterMode
                        ? 'bg-slate-800 text-blue-400 border-slate-700'
                        : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-800'
                "
                :title="
                    isTheaterMode
                        ? 'Mode Bioskop Aktif (100% Lebar Layar). Klik untuk kembali ke Ukuran 2XL Standar'
                        : 'Mode Bioskop (Perluas Layar 100% Full Width tanpa Margin)'
                "
            >
                <img
                    :src="iconTheater"
                    class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                    alt="Bioskop"
                />
                <span>Bioskop</span>
            </button>

            <!-- Audio Switch Button -->
            <button
                @click="
                    emit(
                        'toggle-audio',
                        selectedLayout === 'focus' && primaryFocusedStream
                            ? primaryFocusedStream.video_id
                            : (activeAudioVideoId || null),
                    )
                "
                class="px-2.5 py-1.5 rounded-md text-[11px] font-mono font-bold border transition flex items-center gap-1.5 shrink-0 cursor-pointer"
                :class="
                    (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                    (selectedLayout !== 'focus' && activeAudioVideoId)
                        ? 'bg-slate-800 text-emerald-400 border-slate-700'
                        : 'bg-slate-900 hover:bg-slate-800 text-slate-400 border-slate-800'
                "
                :title="
                    (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                    (selectedLayout !== 'focus' && activeAudioVideoId)
                        ? 'Suara Aktif (Klik untuk Mute)'
                        : 'Aktivasi Suara Feed'
                "
            >
                <img
                    :src="
                        (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                        (selectedLayout !== 'focus' && activeAudioVideoId)
                            ? iconUnmute
                            : iconMute
                    "
                    class="w-3.5 h-3.5 invert opacity-90"
                />
                <span>{{
                    (selectedLayout === 'focus' && primaryFocusedStream && activeAudioVideoId === primaryFocusedStream.video_id) ||
                    (selectedLayout !== 'focus' && activeAudioVideoId)
                        ? "Audio ON"
                        : "Audio OFF"
                }}</span>
            </button>

            <!-- Fullscreen Toggle -->
            <button
                @click="emit('toggle-fullscreen')"
                class="hidden sm:flex p-2 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition shrink-0 cursor-pointer"
                title="Toggle Layar Penuh (Fullscreen)"
            >
                <img
                    :src="iconFullscreen"
                    class="w-3.5 h-3.5 invert opacity-90"
                />
            </button>
        </div>
    </div>
</template>
