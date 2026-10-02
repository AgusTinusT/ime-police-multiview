<script setup>
import { Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";

// Sub-components
import UserAccountMenu from "@/Components/UserAccountMenu.vue";

const props = defineProps({
    selectedDepartment: {
        type: String,
        default: "ALL",
    },
    allActiveStreams: {
        type: Array,
        default: () => [],
    },
    activePersonalStreams: {
        type: Array,
        default: () => [],
    },
    tacChannels: {
        type: Array,
        default: () => [],
    },
    isDataSaverEnabled: {
        type: Boolean,
        default: false,
    },
    isSyncingFeeds: {
        type: Boolean,
        default: false,
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "update:selectedDepartment",
    "enable-data-saver",
    "disable-data-saver",
    "trigger-sync",
    "toggle-fullscreen",
]);

const getDeptCount = (deptId) => {
    if (deptId === "ALL") return props.allActiveStreams?.length || 0;
    if (deptId === "PERSONAL") return props.activePersonalStreams?.length || 0;
    if (deptId === "TAC") {
        return (props.tacChannels || []).reduce(
            (acc, c) => acc + (c.unit_count || c.video_ids?.length || 0),
            0,
        );
    }
    return (props.allActiveStreams || []).filter((s) => {
        const d = (s.officer?.department || "").toUpperCase();
        if (deptId === "SAPR") return d === "SAPR" || d === "PARK RANGER";
        return d === deptId;
    }).length;
};
</script>

<template>
    <!-- Full-Width Edge-to-Edge Master Header Bar -->
    <header
        class="w-full bg-slate-950 border-b border-slate-800 sticky top-0 z-40 py-2.5 sm:py-3"
    >
        <div class="max-w-screen-2xl mx-auto w-full px-3 sm:px-4 lg:px-8 flex items-center justify-between gap-3 text-slate-100">
            <!-- LEFT BRANDING: Police Duty (Navigates to Dashboard) -->
            <Link
                href="/"
                class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                title="IME Roleplay — Police Duty Dashboard"
            >
                <div
                    class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-md bg-slate-900 border border-slate-800 group-hover:border-slate-700 p-1 overflow-hidden transition"
                >
                    <img
                        :src="logoSaspColor"
                        class="w-full h-full object-contain rounded"
                        alt="Police Badge"
                    />
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-[11px] sm:text-xs font-black tracking-wider text-blue-400 group-hover:text-blue-300 uppercase leading-tight transition"
                    >
                        IME ROLEPLAY
                    </span>
                    <span
                        class="text-[9px] sm:text-[10px] font-bold tracking-wide text-slate-300 group-hover:text-white uppercase leading-tight transition"
                    >
                        POLICE DUTY
                    </span>
                </div>
            </Link>

            <!-- CENTER CATEGORIES (Title Text Style) -->
            <nav class="hidden md:flex items-center space-x-2 overflow-x-auto scrollbar-none px-1 py-0.5">
                <button
                    @click="emit('update:selectedDepartment', 'ALL')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment === 'ALL'
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>All Units</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('ALL') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'LSPD')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment === 'LSPD'
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <img :src="iconLspd" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>LSPD</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('LSPD') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'BCSO')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment === 'BCSO'
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <img :src="iconBcso" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>BCSO</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('BCSO') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'SASP')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment === 'SASP'
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <img :src="iconSasp" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>SASP</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('SASP') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'SAPR')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment === 'SAPR'
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <img :src="iconSapr" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>SAPR</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('SAPR') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', selectedDepartment.startsWith('TAC_') ? 'ALL' : 'TAC_1')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment.startsWith('TAC_')
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" />
                    <span>{{ selectedDepartment.startsWith('TAC_') ? selectedDepartment.replace('_', ' ') : 'TAC' }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('TAC') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'PERSONAL')"
                    :class="[
                        'px-2.5 py-1 text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer border-b-2 font-mono uppercase tracking-wider',
                        selectedDepartment === 'PERSONAL'
                            ? 'text-blue-400 border-blue-500'
                            : 'text-slate-400 border-transparent hover:text-slate-200'
                    ]"
                >
                    <img :src="iconPersonal" class="w-3.5 h-3.5 invert opacity-90" />
                    <span>Watchlist</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono">{{ getDeptCount('PERSONAL') }}</span>
                </button>
            </nav>

            <!-- RIGHT AREA: Action Controls & User Avatar -->
            <div class="flex items-center space-x-2 shrink-0">
                <Link
                    href="/action-clips"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-bold font-mono transition flex items-center gap-1.5 cursor-pointer shadow-sm"
                    title="Buka TacClips — Galeri Momen Aksi Kepolisian"
                >
                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="hidden sm:inline">TacClips</span>
                </Link>
                <UserAccountMenu />
            </div>
        </div>

        <!-- MOBILE SECONDARY NAV RIBBON (< md) -->
        <div
            class="hidden max-w-screen-2xl mx-auto w-full px-3 pt-2 items-center space-x-3 overflow-x-auto scrollbar-none border-t border-slate-800 mt-2"
        >
            <button
                @click="emit('update:selectedDepartment', 'ALL')"
                :class="[
                    'pb-1 text-xs font-bold font-mono transition shrink-0 cursor-pointer border-b-2 uppercase tracking-wider',
                    selectedDepartment === 'ALL' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'
                ]"
            >
                ALL ({{ getDeptCount('ALL') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'LSPD')"
                :class="[
                    'pb-1 text-xs font-bold font-mono transition shrink-0 cursor-pointer border-b-2 uppercase tracking-wider',
                    selectedDepartment === 'LSPD' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'
                ]"
            >
                LSPD ({{ getDeptCount('LSPD') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'BCSO')"
                :class="[
                    'pb-1 text-xs font-bold font-mono transition shrink-0 cursor-pointer border-b-2 uppercase tracking-wider',
                    selectedDepartment === 'BCSO' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'
                ]"
            >
                BCSO ({{ getDeptCount('BCSO') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'SASP')"
                :class="[
                    'pb-1 text-xs font-bold font-mono transition shrink-0 cursor-pointer border-b-2 uppercase tracking-wider',
                    selectedDepartment === 'SASP' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'
                ]"
            >
                SASP ({{ getDeptCount('SASP') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'SAPR')"
                :class="[
                    'pb-1 text-xs font-bold font-mono transition shrink-0 cursor-pointer border-b-2 uppercase tracking-wider',
                    selectedDepartment === 'SAPR' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'
                ]"
            >
                SAPR ({{ getDeptCount('SAPR') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', selectedDepartment.startsWith('TAC_') ? 'ALL' : 'TAC_1')"
                :class="[
                    'pb-1 text-xs font-bold font-mono transition shrink-0 cursor-pointer border-b-2 uppercase tracking-wider',
                    selectedDepartment.startsWith('TAC_') ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'
                ]"
            >
                TAC
            </button>
        </div>
    </header>
</template>
