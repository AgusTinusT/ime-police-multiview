<script setup>
import iconSearch from "@/Components/Icons/search-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";

const props = defineProps({
    selectedDepartment: {
        type: String,
        default: "ALL",
    },
    selectedLayout: {
        type: String,
        default: "auto",
    },
    searchFilter: {
        type: String,
        default: "",
    },
    departments: {
        type: Array,
        required: true,
    },
    dropdownTacDepartments: {
        type: Array,
        required: true,
    },
    totalPersonalCount: {
        type: Number,
        default: 0,
    },
    getTacUnitCount: {
        type: Function,
        required: true,
    },
    allActiveStreams: {
        type: Array,
        default: () => [],
    },
    isMoreTacOpen: {
        type: Boolean,
        default: false,
    },
    tacDropdownPos: {
        type: Object,
        default: () => ({ top: 0, left: 0 }),
    },
});

const emit = defineEmits([
    "update:selectedDepartment",
    "update:selectedLayout",
    "update:searchFilter",
    "toggle-more-tac",
    "close-more-tac",
]);

const selectDepartment = (id) => {
    emit("update:selectedDepartment", id);
};

const selectLayout = (layout) => {
    emit("update:selectedLayout", layout);
};

const updateSearch = (val) => {
    emit("update:searchFilter", val);
};
</script>

<template>
    <!-- Department Filter Toolbar & Search / Grid Controls -->
    <div
        class="bg-[#090f1a] border-b border-slate-800/80 px-3 sm:px-4 lg:px-8 py-2"
    >
        <div class="max-w-screen-2xl mx-auto w-full flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        <!-- Department Tabs (Horizontal Scrollable Bar on Left + Fixed More TAC Dropdown) -->
        <div
            class="flex items-center space-x-1.5 flex-1 min-w-0 relative z-30"
        >
            <!-- Scrollable Department Tabs -->
            <div
                class="flex items-center space-x-1.5 overflow-x-auto whitespace-nowrap scrollbar-none py-1 flex-1 min-w-0"
            >
                <button
                    v-for="dept in departments"
                    :key="dept.id"
                    @click="selectDepartment(dept.id)"
                    :class="[
                        'px-3 py-1.5 text-xs rounded-full border transition flex items-center space-x-1.5 whitespace-nowrap shrink-0 cursor-pointer',
                        selectedDepartment === dept.id
                            ? dept.id === 'PERSONAL'
                                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30 border-purple-400'
                                : dept.isTac
                                  ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400 ring-1 ring-amber-400'
                                  : 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400'
                            : dept.id === 'PERSONAL'
                              ? 'bg-purple-950/40 text-purple-300 hover:bg-purple-900/50 border-purple-500/40'
                              : dept.isTac
                                ? getTacUnitCount(dept.id) > 0
                                    ? 'bg-amber-950/50 text-amber-300 hover:bg-amber-900/60 border-amber-500/50 shadow-sm shadow-amber-500/10 font-semibold'
                                    : 'bg-slate-900/80 text-slate-400 hover:bg-slate-800 border-slate-800 opacity-70 hover:opacity-100'
                                : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800',
                    ]"
                >
                    <img
                        v-if="dept.isSvg"
                        :src="dept.icon"
                        class="w-4 h-4 inline-block object-contain brightness-0 invert opacity-90 shrink-0"
                        alt=""
                    />
                    <span v-else class="shrink-0">{{ dept.icon }}</span>
                    <span class="shrink-0">{{ dept.name }}</span>

                    <!-- Personal count badge -->
                    <span
                        v-if="dept.id === 'PERSONAL'"
                        class="text-[10px] px-1.5 py-0.2 bg-black/50 rounded-full font-mono font-bold text-purple-200 border border-purple-400/30 shrink-0"
                    >
                        {{ totalPersonalCount }}/6
                    </span>
                    <!-- TAC count badge -->
                    <span
                        v-else-if="dept.isTac"
                        class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold shrink-0"
                        :class="
                            getTacUnitCount(dept.id) > 0
                                ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30'
                                : 'bg-black/40 text-slate-500'
                        "
                    >
                        {{ getTacUnitCount(dept.id) }}
                    </span>
                    <!-- Standard dept count -->
                    <span
                        v-else-if="dept.id !== 'ALL'"
                        class="text-[10px] px-1.5 py-0.2 bg-black/40 rounded-full font-mono shrink-0"
                    >
                        {{
                            allActiveStreams.filter(
                                (s) =>
                                    s.officer?.department === dept.id ||
                                    (dept.id === 'SAPR' &&
                                        (s.officer?.department === 'SAPR' ||
                                            s.officer?.department ===
                                                'PARK RANGER')),
                            ).length
                        }}
                    </span>
                </button>

                <!-- More TAC Dropdown Selector (TAC 4 to TAC 10) -->
                <div class="relative shrink-0 text-left z-40 inline-block">
                    <button
                        @click.stop="$emit('toggle-more-tac', $event)"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-full border transition flex items-center space-x-1.5 whitespace-nowrap font-medium cursor-pointer',
                            dropdownTacDepartments.some(
                                (d) => d.id === selectedDepartment,
                            )
                                ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400 ring-1 ring-amber-400'
                                : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800',
                        ]"
                        :title="
                            dropdownTacDepartments.some(
                                (d) => d.id === selectedDepartment,
                            )
                                ? `Kanal Terpilih: ${selectedDepartment.replace('_', ' ')}`
                                : 'Kanal TAC Tambahan (TAC 4 - 10)'
                        "
                    >
                        <img
                            :src="iconRadio"
                            class="w-4 h-4 inline-block object-contain brightness-0 invert opacity-90"
                            alt=""
                        />
                        <span>{{
                            dropdownTacDepartments.some(
                                (d) => d.id === selectedDepartment,
                            )
                                ? selectedDepartment.replace("_", " ")
                                : "More TAC"
                        }}</span>
                        <span class="text-[10px] text-amber-400/80">▾</span>
                    </button>

                    <!-- Dropdown Menu Popover (Fixed Positioning) -->
                    <div
                        v-if="isMoreTacOpen"
                        class="fixed z-50 bg-slate-950/95 border border-amber-500/50 rounded-xl p-2 shadow-2xl backdrop-blur-xl text-xs w-48 animate-in fade-in zoom-in-95 font-sans"
                        :style="{
                            top: tacDropdownPos.top + 'px',
                            left: tacDropdownPos.left + 'px',
                        }"
                        @click.stop
                    >
                        <div
                            class="px-2 py-1 mb-1 border-b border-slate-800/80 flex items-center justify-between text-[11px] font-mono font-bold text-amber-400"
                        >
                            <span>RADIO TAC (4 – 10)</span>
                            <button
                                @click="$emit('close-more-tac')"
                                class="text-slate-400 hover:text-white text-[10px] cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>
                        <div class="space-y-1 max-h-60 overflow-y-auto">
                            <button
                                v-for="tac in dropdownTacDepartments"
                                :key="tac.id"
                                @click="
                                    selectDepartment(tac.id);
                                    $emit('close-more-tac');
                                "
                                :class="[
                                    'w-full px-2.5 py-1.5 rounded-lg flex items-center justify-between transition text-xs font-mono font-bold cursor-pointer',
                                    selectedDepartment === tac.id
                                        ? 'bg-amber-600 text-white shadow-md shadow-amber-600/30 border border-amber-400'
                                        : getTacUnitCount(tac.id) > 0
                                          ? 'bg-amber-950/40 text-amber-300 hover:bg-amber-900/60 border border-amber-500/30'
                                          : 'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border border-slate-800/80',
                                ]"
                            >
                                <div class="flex items-center space-x-2">
                                    <img
                                        :src="iconRadio"
                                        class="w-3.5 h-3.5 brightness-0 invert opacity-90"
                                        alt=""
                                    />
                                    <span>{{ tac.name }}</span>
                                </div>
                                <span
                                    class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-bold"
                                    :class="
                                        selectedDepartment === tac.id
                                            ? 'bg-black/40 text-white'
                                            : getTacUnitCount(tac.id) > 0
                                              ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30'
                                              : 'bg-black/40 text-slate-500'
                                    "
                                >
                                    {{ getTacUnitCount(tac.id) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Grid Layout Switcher -->
        <div
            class="flex items-center space-x-2 w-full md:w-auto shrink-0 z-30 justify-between md:justify-end"
        >
            <!-- Search Input -->
            <div class="relative flex-1 sm:w-60 flex items-center">
                <input
                    :value="searchFilter"
                    @input="updateSearch($event.target.value)"
                    type="text"
                    placeholder="Search callsign, badge, officer..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-7 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono"
                />
                <img
                    :src="iconSearch"
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 opacity-50 invert pointer-events-none"
                    alt="Search"
                />
                <button
                    v-if="searchFilter"
                    @click="updateSearch('')"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs font-bold leading-none p-0.5 rounded hover:bg-slate-800 transition cursor-pointer"
                    title="Bersihkan pencarian"
                >
                    ✕
                </button>
            </div>

            <!-- Mobile Layout Selector (< md) -->
            <div
                class="flex md:hidden items-center bg-slate-950/90 rounded-lg p-0.5 border border-slate-800 shrink-0"
            >
                <button
                    @click="selectLayout('focus')"
                    :class="
                        selectedLayout === 'focus'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2 py-1 text-xs rounded transition flex items-center gap-1 cursor-pointer"
                    title="Focus Priority Lead"
                >
                    <img
                        :src="iconFocus"
                        class="w-3 h-3 invert opacity-90"
                        alt="Focus"
                    />
                    <span>Focus</span>
                </button>
                <button
                    @click="selectLayout('grid-1x2')"
                    :class="
                        selectedLayout === 'grid-1x2'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    title="Mobile 1x2 Layout"
                >
                    1x2
                </button>
                <button
                    @click="selectLayout('grid-1x3')"
                    :class="
                        selectedLayout === 'grid-1x3'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    title="Mobile 1x3 Layout"
                >
                    1x3
                </button>
                <button
                    @click="selectLayout('auto')"
                    :class="
                        selectedLayout === 'auto'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    title="Auto Layout"
                >
                    Auto
                </button>
            </div>

            <!-- Desktop / Tablet Layout Selector (hidden on mobile) -->
            <div
                class="hidden md:flex items-center bg-slate-950/90 rounded-lg p-0.5 border border-slate-800 shrink-0"
            >
                <button
                    @click="selectLayout('auto')"
                    :class="
                        selectedLayout === 'auto'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition cursor-pointer"
                    title="Tata Letak Otomatis (Auto-Fit Grid)"
                >
                    Auto
                </button>
                <button
                    @click="selectLayout('grid-1x2')"
                    :class="
                        selectedLayout === 'grid-1x2'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition cursor-pointer"
                    title="1x2 Vertical Stack Layout"
                >
                    1x2
                </button>
                <button
                    @click="selectLayout('grid-1x3')"
                    :class="
                        selectedLayout === 'grid-1x3'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition cursor-pointer"
                    title="1x3 Tactical Layout"
                >
                    1x3
                </button>
                <button
                    @click="selectLayout('grid-2x2')"
                    :class="
                        selectedLayout === 'grid-2x2'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition cursor-pointer"
                    title="2x2 Quad Patrol Layout"
                >
                    2x2
                </button>
                <button
                    @click="selectLayout('grid-3x3')"
                    :class="
                        selectedLayout === 'grid-3x3'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition cursor-pointer"
                    title="3x3 Sector Command Layout"
                >
                    3x3
                </button>
                <button
                    @click="selectLayout('grid-4x4')"
                    :class="
                        selectedLayout === 'grid-4x4'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition hidden lg:inline-block cursor-pointer"
                    title="4x4 Tactical Wall Layout"
                >
                    4x4
                </button>
                <button
                    @click="selectLayout('focus')"
                    :class="
                        selectedLayout === 'focus'
                            ? 'bg-blue-600 text-white font-bold shadow'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    class="px-2.5 py-1 text-xs rounded transition flex items-center gap-1.5 cursor-pointer"
                    title="Focus Priority Lead + Right Sidebar Units"
                >
                    <img
                        :src="iconFocus"
                        class="w-3.5 h-3.5 invert opacity-90"
                        alt="Focus"
                    />
                    <span>Focus</span>
                </button>
            </div>
        </div>
        </div>
    </div>
</template>
