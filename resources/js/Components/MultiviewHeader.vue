<script setup>
import { Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";
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
    "open-quick-add",
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
        class="w-full bg-[#0b1320] border-b border-blue-900/40 sticky top-0 z-40 shadow-xl backdrop-blur-md py-2.5 sm:py-3"
    >
        <div class="max-w-screen-2xl mx-auto w-full px-3 sm:px-4 lg:px-8 flex items-center justify-between gap-3 text-slate-100">
            <!-- LEFT BRANDING: Police Duty (Navigates to Dashboard) -->
            <Link
                href="/"
                class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                title="IME Roleplay — Police Duty Dashboard"
            >
                <div
                    class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 group-hover:border-blue-400 p-1 overflow-hidden transition shadow-inner"
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

            <!-- CENTER CATEGORIES (Consistent Dark-Blue Pill Theme) -->
            <nav class="hidden md:flex items-center space-x-1.5 overflow-x-auto scrollbar-none px-1 py-0.5">
                <button
                    @click="emit('update:selectedDepartment', 'ALL')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment === 'ALL'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>All Units</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('ALL') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'LSPD')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment === 'LSPD'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconLspd" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>LSPD</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('LSPD') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'BCSO')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment === 'BCSO'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconBcso" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>BCSO</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('BCSO') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'SASP')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment === 'SASP'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconSasp" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>SASP</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('SASP') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'SAPR')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment === 'SAPR'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconSapr" class="w-3.5 h-3.5 object-contain brightness-0 invert opacity-90" />
                    <span>SAPR</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('SAPR') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', selectedDepartment.startsWith('TAC_') ? 'ALL' : 'TAC_1')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment.startsWith('TAC_')
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" />
                    <span>{{ selectedDepartment.startsWith('TAC_') ? selectedDepartment.replace('_', ' ') : 'TAC' }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('TAC') }}</span>
                </button>

                <button
                    @click="emit('update:selectedDepartment', 'PERSONAL')"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-full font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer',
                        selectedDepartment === 'PERSONAL'
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <img :src="iconPersonal" class="w-3.5 h-3.5 invert opacity-90" />
                    <span>Watchlist</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/40 font-mono">{{ getDeptCount('PERSONAL') }}</span>
                </button>
            </nav>

            <!-- RIGHT AREA: Action Controls & User Avatar -->
            <div class="flex items-center space-x-2 shrink-0">
                <button
                    @click="$emit('open-quick-add')"
                    class="h-8 px-2.5 text-xs font-bold rounded-lg bg-emerald-950/80 hover:bg-emerald-900/90 text-emerald-300 border border-emerald-500/40 transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer"
                    title="Add External YouTube URL to CCTV Wall"
                >
                    <img
                        :src="iconQuickAdd"
                        class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                        alt="Quick Feed"
                    />
                    <span class="hidden sm:inline leading-none">Quick Feed</span>
                </button>

                <UserAccountMenu />
            </div>
        </div>

        <!-- MOBILE SECONDARY NAV RIBBON (< md) -->
        <div
            class="md:hidden max-w-screen-2xl mx-auto w-full px-3 pt-2 flex items-center space-x-1.5 overflow-x-auto scrollbar-none border-t border-slate-800/60 mt-2"
        >
            <button
                @click="emit('update:selectedDepartment', 'ALL')"
                :class="[
                    'px-3 py-1 text-xs rounded-full font-bold transition shrink-0 cursor-pointer',
                    selectedDepartment === 'ALL' ? 'bg-blue-600 text-white' : 'text-slate-300'
                ]"
            >
                ALL ({{ getDeptCount('ALL') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'LSPD')"
                :class="[
                    'px-3 py-1 text-xs rounded-full font-bold transition shrink-0 cursor-pointer',
                    selectedDepartment === 'LSPD' ? 'bg-blue-600 text-white' : 'text-slate-300'
                ]"
            >
                LSPD ({{ getDeptCount('LSPD') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'BCSO')"
                :class="[
                    'px-3 py-1 text-xs rounded-full font-bold transition shrink-0 cursor-pointer',
                    selectedDepartment === 'BCSO' ? 'bg-blue-600 text-white' : 'text-slate-300'
                ]"
            >
                BCSO ({{ getDeptCount('BCSO') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'SASP')"
                :class="[
                    'px-3 py-1 text-xs rounded-full font-bold transition shrink-0 cursor-pointer',
                    selectedDepartment === 'SASP' ? 'bg-blue-600 text-white' : 'text-slate-300'
                ]"
            >
                SASP ({{ getDeptCount('SASP') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', 'SAPR')"
                :class="[
                    'px-3 py-1 text-xs rounded-full font-bold transition shrink-0 cursor-pointer',
                    selectedDepartment === 'SAPR' ? 'bg-blue-600 text-white' : 'text-slate-300'
                ]"
            >
                SAPR ({{ getDeptCount('SAPR') }})
            </button>
            <button
                @click="emit('update:selectedDepartment', selectedDepartment.startsWith('TAC_') ? 'ALL' : 'TAC_1')"
                :class="[
                    'px-3 py-1 text-xs rounded-full font-bold transition shrink-0 cursor-pointer',
                    selectedDepartment.startsWith('TAC_') ? 'bg-blue-600 text-white' : 'text-slate-300'
                ]"
            >
                TAC
            </button>
        </div>
    </header>
</template>
