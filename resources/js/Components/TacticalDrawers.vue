<script setup>
import { ref, computed } from "vue";

import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";
import iconSaver from "@/Components/Icons/gauge-low-svgrepo-com.svg";
import iconFeedback from "@/Components/Icons/report-svgrepo-com.svg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconSearch from "@/Components/Icons/search-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconPinPlus from "@/Components/Icons/star-line-svgrepo-com.svg";
import iconPinMinus from "@/Components/Icons/star-svgrepo-com.svg";
import iconEdit from "@/Components/Icons/edit-2-svgrepo-com.svg";
import iconDelete from "@/Components/Icons/delete-2-svgrepo-com.svg";
import iconUser from "@/Components/Icons/user-svgrepo-com.svg";
import iconBug from "@/Components/Icons/bug-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import iconReset from "@/Components/Icons/reset-svgrepo-com.svg";
import iconSend from "@/Components/Icons/send-svgrepo-com.svg";
import iconUrl from "@/Components/Icons/url-checker-svgrepo-com.svg";
import iconTarget from "@/Components/Icons/target-svgrepo-com.svg";
import iconShield from "@/Components/Icons/shield-svgrepo-com.svg";

const props = defineProps({
    activeRightDrawer: { type: String, default: null },
    quickAddMode: { type: String, default: "SEARCH" },
    liveSearchQuery: { type: String, default: "" },
    isLiveSearching: { type: Boolean, default: false },
    liveSearchError: { type: String, default: null },
    liveSearchSuccessNotice: { type: String, default: null },
    liveSearchResults: { type: Array, default: () => [] },
    popularHashtagPresets: { type: Array, default: () => [] },
    quickAddInput: { type: Object, default: () => ({ urlOrId: "", officerName: "", callsign: "", department: "LSPD", patrolZone: "Mission Row" }) },
    totalPersonalCount: { type: Number, default: 0 },
    totalSavedPersonalCount: { type: Number, default: 0 },
    savedPersonalList: { type: Array, default: () => [] },
    feedbackForm: { type: Object, default: () => ({ type: "CHANNEL_REQUEST", sender_name: "", handle_or_url: "", officer_name: "", department: "LSPD", message: "" }) },
    isSubmittingFeedback: { type: Boolean, default: false },
    feedbackSuccessToast: { type: String, default: null },
    directorySearch: { type: String, default: "" },
    directorySortBy: { type: String, default: "status" },
    directoryDeptFilter: { type: String, default: "ALL" },
    offlineOfficers: { type: Array, default: () => [] },
    streams: { type: Array, default: () => [] },
    allDirectoryOfficers: { type: Array, default: () => [] },
    radioCodesActiveTab: { type: String, default: "CODES" },
    radioCodesSearch: { type: String, default: "" },
    filteredPolice10Codes: { type: Array, default: () => [] },
    tacChannelGuides: { type: Array, default: () => [] },
    personalStreamIds: { type: Array, default: () => [] },
    getTacUnitCount: { type: Function, default: () => 0 }
});

const emit = defineEmits([
    "close",
    "update:quickAddMode",
    "update:liveSearchQuery",
    "update:directorySearch",
    "update:directorySortBy",
    "update:directoryDeptFilter",
    "update:radioCodesActiveTab",
    "update:radioCodesSearch",
    "search-live-streams",
    "add-live-stream-to-personal",
    "quick-add-stream",
    "clear-all-personal",
    "remove-personal-stream",
    "toggle-personal-stream",
    "submit-feedback",
    "open-subscribe-popup",
    "set-focus-stream",
    "set-department"
]);

const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return "bg-blue-900/60 text-blue-200 border-blue-700/80";
    if (d.includes("BCSO") || d.includes("LSCSD")) return "bg-amber-900/60 text-amber-200 border-amber-700/80";
    if (d.includes("SASP")) return "bg-purple-900/60 text-purple-200 border-purple-700/80";
    if (d.includes("SAPR") || d.includes("RANGER")) return "bg-emerald-900/60 text-emerald-200 border-emerald-700/80";
    return "bg-slate-800 text-slate-300 border-slate-700";
};

const isPersonalStream = (channelOrHandle) => {
    if (!channelOrHandle) return false;
    const target = String(channelOrHandle).trim().toLowerCase();
    return (props.personalStreamIds || []).some(
        (id) => String(id).trim().toLowerCase() === target
    );
};

const getTacUnitCountLocal = (tacKey) => {
    if (typeof props.getTacUnitCount === "function") {
        return props.getTacUnitCount(tacKey);
    }
    return 0;
};
</script>

<template>
    <div>
        <!-- Backdrop Overlay -->
        <div
            v-if="activeRightDrawer"
            @click="emit('close')"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 transition-opacity duration-300"
        ></div>

        <!-- Sliding Drawer Panel -->
        <aside
            class="fixed inset-y-0 right-0 z-50 bg-[#080d17]/98 border-l border-slate-800/90 shadow-2xl backdrop-blur-2xl flex flex-col transition-all duration-300 ease-in-out"
            :class="[
                activeRightDrawer
                    ? 'translate-x-0'
                    : 'translate-x-full pointer-events-none',
                activeRightDrawer === 'DIRECTORY'
                    ? 'w-full sm:w-[580px] md:w-[740px] lg:w-[860px]'
                    : activeRightDrawer === 'RADIO_CODES'
                      ? 'w-full sm:w-[540px] md:w-[680px] lg:w-[780px]'
                      : 'w-full sm:w-[440px] md:w-[480px]',
            ]"
        >
            <!-- Drawer Top Header Bar -->
            <div
                class="bg-slate-900/95 px-4 py-3.5 border-b border-slate-800 flex items-center justify-between shrink-0"
            >
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-base shrink-0 border"
                        :class="{
                            'bg-blue-950/80 border-blue-500/60 text-blue-400':
                                activeRightDrawer === 'DIRECTORY',
                            'bg-amber-950/80 border-amber-500/60 text-amber-400':
                                activeRightDrawer === 'RADIO_CODES',
                            'bg-emerald-950/80 border-emerald-500/60 text-emerald-400':
                                activeRightDrawer === 'QUICK_ADD',
                            'bg-blue-950/80 border-blue-500/60 text-blue-400':
                                activeRightDrawer === 'FEEDBACK',
                            'bg-indigo-950/80 border-indigo-500/60 text-indigo-400':
                                activeRightDrawer === 'ABOUT',
                        }"
                    >
                        <img
                            v-if="activeRightDrawer === 'DIRECTORY'"
                            :src="iconUser"
                            class="w-4 h-4 invert"
                            alt=""
                        />
                        <img
                            v-else-if="activeRightDrawer === 'RADIO_CODES'"
                            :src="iconRadio"
                            class="w-4 h-4 invert"
                            alt=""
                        />
                        <img
                            v-else-if="activeRightDrawer === 'QUICK_ADD'"
                            :src="iconQuickAdd"
                            class="w-4 h-4 invert"
                            alt=""
                        />
                        <img
                            v-else-if="activeRightDrawer === 'FEEDBACK'"
                            :src="iconFeedback"
                            class="w-4 h-4 invert"
                            alt=""
                        />
                        <svg
                            v-else-if="activeRightDrawer === 'ABOUT'"
                            class="w-4 h-4 text-indigo-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                            <path
                                stroke-width="2"
                                stroke-linecap="round"
                                d="M12 16v-4m0-4h.01"
                            />
                        </svg>
                    </div>
                    <div class="truncate">
                        <div class="flex items-center space-x-2">
                            <h2
                                class="text-xs font-black tracking-wider uppercase font-mono truncate"
                                :class="{
                                    'text-blue-300': activeRightDrawer === 'DIRECTORY',
                                    'text-amber-300': activeRightDrawer === 'RADIO_CODES',
                                    'text-emerald-300': activeRightDrawer === 'QUICK_ADD',
                                    'text-blue-300': activeRightDrawer === 'FEEDBACK',
                                    'text-indigo-300': activeRightDrawer === 'ABOUT',
                                }"
                            >
                                <span v-if="activeRightDrawer === 'DIRECTORY'">DIREKTORI PETUGAS & STREAMER</span>
                                <span v-else-if="activeRightDrawer === 'RADIO_CODES'">PANDUAN KODE 10 & PROTOKOL RADIO</span>
                                <span v-else-if="activeRightDrawer === 'QUICK_ADD'">QUICK ADD LIVE FEED</span>
                                <span v-else-if="activeRightDrawer === 'FEEDBACK'">LAPOR & USULAN STREAMER</span>
                                <span v-else-if="activeRightDrawer === 'ABOUT'">ABOUT COMMAND CENTER</span>
                            </h2>
                            <span
                                class="text-[9px] px-1.5 py-0.2 rounded-full border font-mono uppercase"
                                :class="{
                                    'bg-blue-500/20 text-blue-300 border-blue-500/40': activeRightDrawer === 'DIRECTORY',
                                    'bg-amber-500/20 text-amber-300 border-amber-500/40': activeRightDrawer === 'RADIO_CODES',
                                    'bg-emerald-500/20 text-emerald-300 border-emerald-500/40': activeRightDrawer === 'QUICK_ADD',
                                    'bg-blue-500/20 text-blue-300 border-blue-500/40': activeRightDrawer === 'FEEDBACK',
                                    'bg-indigo-500/20 text-indigo-300 border-indigo-500/40': activeRightDrawer === 'ABOUT',
                                }"
                            >
                                {{
                                    activeRightDrawer === "DIRECTORY"
                                        ? "Roster"
                                        : activeRightDrawer === "RADIO_CODES"
                                          ? "Guide"
                                          : activeRightDrawer === "FEEDBACK"
                                            ? "Discord"
                                            : activeRightDrawer === "ABOUT"
                                              ? "Overview"
                                              : "Temporary"
                                }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 truncate">
                            <span v-if="activeRightDrawer === 'DIRECTORY'">Daftar seluruh personil terdaftar LSPD, BCSO, SASP & status siaran</span>
                            <span v-else-if="activeRightDrawer === 'RADIO_CODES'">Referensi cepat kode radio 10-Codes & penugasan kanal TAC 1–5</span>
                            <span v-else-if="activeRightDrawer === 'QUICK_ADD'">Inject external YouTube live patrol feed into multifeed</span>
                            <span v-else-if="activeRightDrawer === 'FEEDBACK'">Kirim usul streamer atau perbaikan data ke Discord tim</span>
                            <span v-else-if="activeRightDrawer === 'ABOUT'">IME Roleplay Police Command Center & Tactical Multiview</span>
                        </p>
                    </div>
                </div>

                <!-- Header Actions: Close Button -->
                <div class="flex items-center space-x-1.5 shrink-0 ml-2">
                    <button
                        @click="emit('close')"
                        class="p-1.5 text-slate-400 hover:text-white rounded-lg bg-slate-800/80 hover:bg-slate-700 transition text-xs font-bold"
                        title="Tutup Panel (Esc)"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- DRAWER CONTENT BODY -->
            <div class="flex-1 overflow-y-auto scrollbar-thin flex flex-col min-h-0 bg-[#060a12]">
                <!-- 1. QUICK ADD STREAM PANEL -->
                <div v-if="activeRightDrawer === 'QUICK_ADD'" class="p-4 flex flex-col gap-3.5">
                    <div
                        v-if="liveSearchSuccessNotice"
                        class="bg-emerald-950/90 border border-emerald-500/60 rounded-xl px-3.5 py-2 text-xs text-emerald-300 font-mono flex items-center gap-2 shadow-lg animate-in fade-in duration-200"
                    >
                        <span>✓</span>
                        <span>{{ liveSearchSuccessNotice }}</span>
                    </div>

                    <div class="bg-purple-950/30 border border-purple-500/40 rounded-xl p-3 text-xs text-purple-200/90 leading-relaxed">
                        <div class="font-bold flex items-center justify-between mb-1 text-purple-300">
                            <span class="flex items-center gap-1.5">
                                <img :src="iconPersonal" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                <span>Quick Watchlist & Personal</span>
                            </span>
                            <span class="text-[10px] bg-purple-900/60 px-2 py-0.5 rounded-full font-mono font-bold text-purple-200 border border-purple-400/30">
                                {{ totalPersonalCount }}/6 Video
                            </span>
                        </div>
                        Cari lawan/gang atau masukkan live stream YouTube. Stream yang dipilih akan langsung masuk ke tab <strong>PERSONAL</strong> browser lokal Anda.
                    </div>

                    <div class="grid grid-cols-2 bg-slate-950 p-1 rounded-xl border border-slate-800 gap-1">
                        <button
                            type="button"
                            @click="emit('update:quickAddMode', 'SEARCH')"
                            :class="quickAddMode === 'SEARCH' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-400 hover:text-slate-200'"
                            class="py-1.5 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5"
                        >
                            <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                            <span>Cari Live Hashtag</span>
                        </button>
                        <button
                            type="button"
                            @click="emit('update:quickAddMode', 'MANUAL')"
                            :class="quickAddMode === 'MANUAL' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-400 hover:text-slate-200'"
                            class="py-1.5 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5"
                        >
                            <img :src="iconUrl" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                            <span>Input Manual</span>
                        </button>
                    </div>

                    <!-- SEARCH HASHTAG -->
                    <div v-if="quickAddMode === 'SEARCH'" class="flex flex-col gap-3">
                        <form @submit.prevent="emit('search-live-streams', liveSearchQuery)" class="flex flex-col gap-2">
                            <div class="relative">
                                <input
                                    :value="liveSearchQuery"
                                    @input="emit('update:liveSearchQuery', $event.target.value)"
                                    type="text"
                                    required
                                    placeholder="Ketik hashtag misal #imeroleplay #burgenk..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-20 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-purple-500 font-mono placeholder-slate-500"
                                />
                                <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" alt="" />
                                <button
                                    type="submit"
                                    :disabled="isLiveSearching"
                                    class="absolute right-1.5 top-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1"
                                >
                                    <img v-if="isLiveSearching" :src="iconRefresh" class="w-3 h-3 animate-spin invert" alt="" />
                                    <span>{{ isLiveSearching ? "Mencari..." : "Cari" }}</span>
                                </button>
                            </div>
                        </form>

                        <div class="flex flex-col gap-1.5">
                            <span class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Hashtag / Gang Populer:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="preset in popularHashtagPresets"
                                    :key="preset.label"
                                    type="button"
                                    @click="emit('search-live-streams', preset.query)"
                                    class="text-[11px] px-2.5 py-1 rounded-lg border font-mono transition"
                                    :class="liveSearchQuery === preset.query ? 'bg-purple-600 text-white font-bold border-purple-400' : 'bg-slate-900 text-purple-300 hover:bg-purple-950/60 border-purple-500/30 hover:border-purple-400/50'"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="liveSearchError" class="p-3 bg-red-950/30 border border-red-500/40 rounded-xl text-xs text-red-300">
                            {{ liveSearchError }}
                        </div>

                        <div v-if="isLiveSearching" class="py-8 flex flex-col items-center justify-center text-slate-400 gap-2">
                            <div class="w-7 h-7 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-xs font-mono">Sedang mencari stream YouTube live aktif...</span>
                        </div>

                        <div v-else-if="liveSearchResults.length > 0" class="flex flex-col gap-2.5">
                            <div class="flex items-center justify-between text-xs text-slate-400 font-mono px-0.5">
                                <span>Ditemukan: <strong class="text-slate-200">{{ liveSearchResults.length }}</strong> Live Stream</span>
                                <span class="text-[10px] text-emerald-400 font-bold">● 10-8 LIVE</span>
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    v-for="item in liveSearchResults"
                                    :key="item.video_id"
                                    class="bg-slate-900/90 border border-slate-800 hover:border-purple-500/60 rounded-xl p-2.5 transition flex gap-3 items-start"
                                >
                                    <div class="w-28 aspect-video bg-black rounded-lg overflow-hidden shrink-0 relative">
                                        <img :src="item.thumbnail_url" class="w-full h-full object-cover" loading="lazy" />
                                        <div class="absolute bottom-1 right-1 bg-black/80 px-1 py-0.2 rounded text-[9px] font-mono text-red-400 font-bold">
                                            LIVE
                                        </div>
                                    </div>

                                    <div class="flex-1 min-w-0 flex flex-col justify-between h-full">
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-100 line-clamp-2 leading-snug flex-1" :title="item.title">
                                                {{ item.title }}
                                            </h4>
                                            <div class="text-[11px] text-slate-400 truncate mt-1 flex items-center gap-1.5">
                                                <img :src="iconUser" class="w-3 h-3 invert opacity-60" alt="" />
                                                <span class="text-purple-300 font-semibold truncate">{{ item.channel_name }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between gap-2">
                                            <button
                                                type="button"
                                                @click="emit('add-live-stream-to-personal', item)"
                                                :class="isPersonalStream(item.video_id) ? 'bg-purple-950 text-purple-300 border-purple-500/50' : 'bg-purple-600 hover:bg-purple-500 text-white shadow-md shadow-purple-600/30'"
                                                class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center justify-center font-mono border border-transparent"
                                            >
                                                <span>{{ isPersonalStream(item.video_id) ? "✓ Pinned" : "Pin ke Personal" }}</span>
                                            </button>

                                            <a
                                                :href="`https://www.youtube.com/watch?v=${item.video_id}`"
                                                target="_blank"
                                                class="p-1 hover:text-white text-slate-400 hover:bg-slate-800 rounded transition flex items-center justify-center"
                                                title="Open on YouTube"
                                            >
                                                <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="Open on YouTube" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MANUAL INPUT -->
                    <div v-else-if="quickAddMode === 'MANUAL'">
                        <form @submit.prevent="emit('quick-add-stream')" class="flex flex-col gap-3.5">
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">YouTube Live URL or 11-char Video ID *</label>
                                <input
                                    v-model="quickAddInput.urlOrId"
                                    type="text"
                                    required
                                    placeholder="https://youtube.com/watch?v=xxxxxxxxxxx or dQw4w9WgXcQ"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-mono placeholder-slate-600"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Officer / Unit Name</label>
                                    <input
                                        v-model="quickAddInput.officerName"
                                        type="text"
                                        placeholder="Ofc. Raymond"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 placeholder-slate-600"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Callsign</label>
                                    <input
                                        v-model="quickAddInput.callsign"
                                        type="text"
                                        placeholder="1-ADAM-99"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-mono placeholder-slate-600"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Department</label>
                                    <select
                                        v-model="quickAddInput.department"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                                    >
                                        <option value="LSPD">LSPD (Police)</option>
                                        <option value="BCSO">BCSO (Sheriff)</option>
                                        <option value="SASP">SASP (State Police)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-300 block mb-1">Patrol Zone</label>
                                    <input
                                        v-model="quickAddInput.patrolZone"
                                        type="text"
                                        placeholder="Mission Row"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 placeholder-slate-600"
                                    />
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-600/30 transition flex items-center gap-1.5"
                                >
                                    <img :src="iconQuickAdd" class="w-3.5 h-3.5 invert" alt="" />
                                    <span>Inject Live Feed</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- SAVED PERSONAL WATCHLIST MANAGER -->
                    <div class="mt-2 pt-3 border-t border-slate-800/80 flex flex-col gap-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img :src="iconPersonal" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                <span class="text-xs font-bold text-slate-200 uppercase font-mono tracking-wide">
                                    Watchlist Tersimpan
                                </span>
                                <span class="text-[10px] bg-purple-950/80 px-2 py-0.5 rounded-full font-mono text-purple-300 border border-purple-500/40 font-bold">
                                    {{ totalPersonalCount }}/6 Online ({{ totalSavedPersonalCount }} Total)
                                </span>
                            </div>
                            <button
                                v-if="totalSavedPersonalCount > 0"
                                type="button"
                                @click="emit('clear-all-personal')"
                                class="text-[11px] text-red-400 hover:text-red-300 font-mono flex items-center gap-1 hover:underline"
                                title="Kosongkan seluruh pin personal"
                            >
                                <img :src="iconReset" class="w-3 h-3 invert opacity-80" alt="" />
                                <span>Reset Semua</span>
                            </button>
                        </div>

                        <div v-if="totalSavedPersonalCount === 0" class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center text-xs text-slate-500 font-mono">
                            Belum ada video atau channel yang disimpan di Personal.
                        </div>

                        <div v-else class="space-y-2 max-h-60 overflow-y-auto scrollbar-thin pr-0.5">
                            <div
                                v-for="savedItem in savedPersonalList"
                                :key="savedItem.id"
                                class="bg-slate-900/80 border border-slate-800 rounded-xl p-2.5 flex items-center justify-between gap-2.5 transition hover:border-slate-700"
                            >
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <img
                                        v-if="savedItem.thumbnail"
                                        :src="savedItem.thumbnail"
                                        class="w-12 h-8 rounded object-cover bg-black shrink-0 border border-slate-800"
                                    />
                                    <div v-else class="w-12 h-8 rounded bg-slate-950 border border-slate-800 flex items-center justify-center text-xs text-slate-500 shrink-0">
                                        <img :src="iconPersonal" class="w-4 h-4 invert opacity-40" alt="" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold shrink-0"
                                                :class="savedItem.isOnline ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/40' : 'bg-slate-950 text-slate-500 border border-slate-800'"
                                            >
                                                {{ savedItem.isOnline ? "● 10-8 LIVE" : "○ 10-7 OFFLINE" }}
                                            </span>
                                            <span class="text-[11px] font-bold text-slate-200 truncate" :title="savedItem.name">{{ savedItem.name }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5">
                                            {{ savedItem.subtext }}
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="emit('remove-personal-stream', savedItem.rawId)"
                                    class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-950 hover:bg-red-950/50 border border-slate-800 hover:border-red-500/40 rounded-lg text-xs transition shrink-0 flex items-center justify-center"
                                    title="Hapus dari daftar personal"
                                >
                                    <img :src="iconDelete" class="w-3.5 h-3.5 invert opacity-80 group-hover:opacity-100" alt="Delete" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. VISITOR FEEDBACK & DISCORD DISPATCHER PANEL -->
                <div v-if="activeRightDrawer === 'FEEDBACK'" class="p-4 flex flex-col gap-4">
                    <div
                        v-if="feedbackSuccessToast"
                        class="bg-emerald-950/90 border border-emerald-500/50 rounded-xl px-4 py-2.5 text-xs text-emerald-300 font-mono flex items-center gap-2 shadow-lg"
                    >
                        <span class="text-base">✓</span>
                        <span>{{ feedbackSuccessToast }}</span>
                    </div>

                    <div class="bg-blue-950/20 border border-blue-500/30 rounded-xl p-3 text-xs text-blue-200/90 leading-relaxed">
                        <div class="font-bold flex items-center gap-1.5 mb-1 text-blue-300">
                            <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                            <span>Direct Dispatcher Line</span>
                        </div>
                        Formulir ini akan otomatis mengirim pesan langsung ke channel Discord Dispatcher IME Roleplay.
                    </div>

                    <form @submit.prevent="emit('submit-feedback')" class="flex flex-col gap-3.5">
                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1.5 font-mono">Kategori Masukan *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="feedbackForm.type = 'CHANNEL_REQUEST'"
                                    :class="feedbackForm.type === 'CHANNEL_REQUEST' ? 'bg-blue-600 text-white font-bold border-blue-400 shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconQuickAdd" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Usul Streamer</span>
                                </button>
                                <button
                                    type="button"
                                    @click="feedbackForm.type = 'DATA_CORRECTION'"
                                    :class="feedbackForm.type === 'DATA_CORRECTION' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconEdit" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Koreksi Data</span>
                                </button>
                                <button
                                    type="button"
                                    @click="feedbackForm.type = 'BUG_REPORT'"
                                    :class="feedbackForm.type === 'BUG_REPORT' ? 'bg-red-600 text-white font-bold border-red-400 shadow-md shadow-red-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconBug" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Lapor Bug</span>
                                </button>
                                <button
                                    type="button"
                                    @click="feedbackForm.type = 'OTHER'"
                                    :class="feedbackForm.type === 'OTHER' ? 'bg-purple-600 text-white font-bold border-purple-400 shadow-md shadow-purple-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-2 rounded-lg border text-xs text-left transition flex items-center gap-1.5"
                                >
                                    <img :src="iconFeedback" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                    <span class="truncate">Lainnya</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Pengirim (Opsional)</label>
                                <input
                                    v-model="feedbackForm.sender_name"
                                    type="text"
                                    placeholder="Warga / Nama Anda"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500 placeholder-slate-600"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Handle / Link YouTube</label>
                                <input
                                    v-model="feedbackForm.handle_or_url"
                                    type="text"
                                    placeholder="@NamaStreamer atau URL"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500 font-mono placeholder-slate-600"
                                />
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-3 gap-2"
                            v-if="feedbackForm.type === 'CHANNEL_REQUEST' || feedbackForm.type === 'DATA_CORRECTION'"
                        >
                            <div class="sm:col-span-2">
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Nama Karakter / Callsign</label>
                                <input
                                    v-model="feedbackForm.officer_name"
                                    type="text"
                                    placeholder="Ofc. Budi / 1-ADAM-12"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500 placeholder-slate-600"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300 block mb-1">Departemen</label>
                                <select
                                    v-model="feedbackForm.department"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500"
                                >
                                    <option value="LSPD">LSPD (Police)</option>
                                    <option value="BCSO">BCSO (Sheriff)</option>
                                    <option value="SASP">SASP (State Police)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-300 block mb-1">Pesan / Catatan Detail *</label>
                            <textarea
                                v-model="feedbackForm.message"
                                required
                                rows="4"
                                :placeholder="
                                    feedbackForm.type === 'CHANNEL_REQUEST'
                                        ? 'Jelaskan jadwal live rutin streamer atau link channel YouTube resminya...'
                                        : feedbackForm.type === 'DATA_CORRECTION'
                                          ? 'Jelaskan data apa yang perlu dikoreksi (misal pangkat naik jadi Sergeant, ganti callsign)...'
                                          : 'Tuliskan detail masukan atau kendala Anda...'
                                "
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg p-3 text-xs text-slate-200 focus:outline-none focus:border-blue-500 placeholder-slate-600"
                            ></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="emit('close')"
                                class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-lg transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isSubmittingFeedback"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-600/30 flex items-center gap-1.5 transition"
                            >
                                <img v-if="isSubmittingFeedback" :src="iconRefresh" class="w-3.5 h-3.5 animate-spin invert" alt="" />
                                <img v-else :src="iconSend" class="w-3.5 h-3.5 invert" alt="" />
                                <span>{{ isSubmittingFeedback ? "Mengirim..." : "Kirim ke Discord" }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. ABOUT COMMAND CENTER PANEL -->
                <div v-if="activeRightDrawer === 'ABOUT'" class="p-4 sm:p-5 flex flex-col gap-4 text-xs">
                    <div class="bg-gradient-to-br from-blue-950/60 via-slate-900 to-slate-950 border border-blue-500/30 rounded-2xl p-4 shadow-xl flex items-center gap-3.5">
                        <div class="w-14 h-14 rounded-xl bg-slate-900/90 border border-blue-400/40 p-1 shrink-0 flex items-center justify-center shadow-inner">
                            <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="text-xs font-black tracking-wider text-blue-400 uppercase font-mono">IME ROLEPLAY</span>
                                <span class="text-[9px] bg-blue-500/20 text-blue-300 px-1.5 py-0.2 rounded font-mono font-bold border border-blue-500/40">v2.4</span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-100 leading-snug">Police Command Center & Tactical Multiview</h3>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                Sistem pengawasan dan monitoring siaran langsung terpadu bagi seluruh unit penegak hukum (LSPD, BCSO, SASP) di server GTA V IME Roleplay.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Fitur Utama Platform</span>
                        </h4>

                        <div class="grid grid-cols-1 gap-2">
                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-blue-950/70 border border-blue-500/30 text-blue-400 shrink-0">
                                    <img :src="iconFocus" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Tactical CCTV Grid & Focus Mode</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Pantau kamera bodycam banyak petugas secara simultan dengan tata letak adaptif (Auto, 2x2, 3x3, 4x4) atau Focus Mode berukuran besar.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-indigo-950/70 border border-indigo-500/30 text-indigo-400 shrink-0">
                                    <img :src="iconAllUnits" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Cinema Hub & Swimlanes</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Katalog visual ala Netflix dengan Top Spotlight dan swimlane per kesatuan untuk siaran langsung dan rekaman patroli VOD terkini.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-amber-950/70 border border-amber-500/30 text-amber-400 shrink-0">
                                    <img :src="iconRadio" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Radio Taktis TAC 1–5</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Kelompokkan petugas ke dalam kanal radio darurat (TAC 1 s.d. TAC 5) dengan timer situasi otomatis dan popover 1-klik.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-red-950/70 border border-red-500/30 text-red-400 shrink-0">
                                    <img :src="iconTarget" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Support 1K Subs Community Milestone</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Apresiasi komunitas untuk mendukung petugas patroli mencapai target 1.000 subscriber YouTube pertama mereka.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 flex gap-3 items-start">
                                <div class="p-2 rounded-lg bg-emerald-950/70 border border-emerald-500/30 text-emerald-400 shrink-0">
                                    <img :src="iconSaver" class="w-4 h-4 invert" alt="" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-200">Data Saver & Smart Bandwidth</h5>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                                        Hemat bandwidth dan cegah lag dengan mode standby ringan sebelum memutar stream YouTube.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Kesatuan Kepolisian</span>
                        </h4>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-slate-900/80 border border-blue-500/30 rounded-xl p-2.5 text-center flex flex-col items-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded bg-blue-600 text-white mb-1">LSPD</span>
                                <span class="text-[10px] text-slate-300 font-bold">Los Santos Police</span>
                            </div>
                            <div class="bg-slate-900/80 border border-amber-500/30 rounded-xl p-2.5 text-center flex flex-col items-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded bg-amber-600 text-white mb-1">BCSO</span>
                                <span class="text-[10px] text-slate-300 font-bold">Sheriff's Office</span>
                            </div>
                            <div class="bg-slate-900/80 border border-indigo-500/30 rounded-xl p-2.5 text-center flex flex-col items-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded bg-indigo-600 text-white mb-1">SASP</span>
                                <span class="text-[10px] text-slate-300 font-bold">State Police</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 pt-3 border-t border-slate-800/80 space-y-2 text-[11px] text-slate-400 leading-relaxed font-mono">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Server Komunitas:</span>
                            <span class="text-blue-400 font-bold">IME Roleplay Indonesia</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">API Provider:</span>
                            <span class="text-slate-300">YouTube Data API & Scraper</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Engine:</span>
                            <span class="text-slate-300">Laravel + Inertia Vue 3</span>
                        </div>
                    </div>

                    <div class="mt-2 pt-2 border-t border-slate-800">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-bold rounded-xl border border-slate-800 transition"
                        >
                            Tutup Informasi
                        </button>
                    </div>
                </div>

                <!-- 4. OFFICER DIRECTORY & STREAMER ROSTER PANEL -->
                <div v-if="activeRightDrawer === 'DIRECTORY'" class="flex-1 flex flex-col min-h-0">
                    <div class="bg-slate-950/95 px-4 py-3 border-b border-slate-800 flex flex-col gap-2.5 shrink-0">
                        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                            <div class="relative flex-1 min-w-[200px]">
                                <input
                                    :value="directorySearch"
                                    @input="emit('update:directorySearch', $event.target.value)"
                                    type="text"
                                    placeholder="Cari nama polisi, callsign, handle, badge, sektor..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono"
                                />
                                <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" alt="" />
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-[10px] font-mono text-slate-500 hidden sm:inline">Urutkan:</span>
                                <select
                                    :value="directorySortBy"
                                    @change="emit('update:directorySortBy', $event.target.value)"
                                    class="bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-slate-300 focus:outline-none focus:border-blue-500 font-mono"
                                >
                                    <option value="status">10-8 Live Teratas</option>
                                    <option value="subs_desc">Subscribers Terbanyak</option>
                                    <option value="subs_asc">Target 1K Milestone</option>
                                    <option value="name">Nama Petugas (A-Z)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 overflow-x-auto scrollbar-none pt-0.5">
                            <div class="flex items-center space-x-1.5">
                                <button
                                    @click="emit('update:directoryDeptFilter', 'ALL')"
                                    :class="directoryDeptFilter === 'ALL' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    Semua ({{ offlineOfficers.length + streams.length }})
                                </button>
                                <button
                                    @click="emit('update:directoryDeptFilter', 'LIVE_ONLY')"
                                    :class="directoryDeptFilter === 'LIVE_ONLY' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30 border-emerald-400' : 'bg-slate-900 text-emerald-400 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition flex items-center gap-1.5 whitespace-nowrap"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>10-8 Live ({{ streams.length }})</span>
                                </button>
                                <button
                                    @click="emit('update:directoryDeptFilter', 'LSPD')"
                                    :class="directoryDeptFilter === 'LSPD' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-900 text-blue-300 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    LSPD
                                </button>
                                <button
                                    @click="emit('update:directoryDeptFilter', 'BCSO')"
                                    :class="directoryDeptFilter === 'BCSO' ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400' : 'bg-slate-900 text-amber-300 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    BCSO
                                </button>
                                <button
                                    @click="emit('update:directoryDeptFilter', 'SASP')"
                                    :class="directoryDeptFilter === 'SASP' ? 'bg-teal-600 text-white font-bold shadow-md shadow-teal-600/30 border-teal-400' : 'bg-slate-900 text-teal-300 hover:bg-slate-800 border-slate-800'"
                                    class="px-2.5 py-1 text-xs rounded-full border transition whitespace-nowrap"
                                >
                                    SASP
                                </button>
                            </div>

                            <span class="text-[11px] font-mono text-slate-500 whitespace-nowrap hidden sm:inline">
                                {{ allDirectoryOfficers.length }} Personil
                            </span>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 scrollbar-thin">
                        <div v-if="allDirectoryOfficers.length === 0" class="py-12 text-center text-slate-500 flex flex-col items-center">
                            <img :src="iconSearch" class="w-8 h-8 invert opacity-30 mb-2" alt="" />
                            <p class="text-xs">Tidak ada petugas yang cocok dengan filter pencarian.</p>
                            <button
                                @click="emit('update:directorySearch', ''); emit('update:directoryDeptFilter', 'ALL');"
                                class="mt-2 text-xs text-blue-400 hover:underline font-mono"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div
                                v-for="officer in allDirectoryOfficers"
                                :key="officer.id"
                                class="bg-slate-900/90 border rounded-xl p-3.5 flex flex-col justify-between transition-all duration-200 hover:border-slate-700"
                                :class="officer.is_online ? 'border-emerald-500/40 shadow-sm shadow-emerald-500/5' : 'border-slate-800'"
                            >
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center space-x-1.5 min-w-0">
                                            <span class="px-1.5 py-0.5 text-[9px] font-black rounded border font-mono" :class="getDeptBadgeClass(officer.department)">
                                                {{ officer.department }}
                                            </span>
                                            <span class="font-mono text-xs font-bold text-slate-300 truncate">{{ officer.callsign }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono">{{ officer.badge_number }}</span>
                                        </div>

                                        <div class="shrink-0">
                                            <span v-if="officer.is_online" class="inline-flex items-center gap-1 text-[9px] px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 font-mono font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                <span>10-8 ON DUTY</span>
                                            </span>
                                            <span v-else class="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-950 text-slate-500 border border-slate-800 font-mono">
                                                10-7 OFF DUTY
                                            </span>
                                        </div>
                                    </div>

                                    <h4 class="text-sm font-bold text-slate-100 truncate" :title="officer.officer_name">
                                        {{ officer.officer_name }}
                                    </h4>

                                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 truncate">
                                        <span>{{ officer.rank }}</span>
                                        <span>•</span>
                                        <span class="text-blue-400 font-mono">{{ officer.handle }}</span>
                                        <span v-if="officer.streamer_name" class="text-slate-500 truncate">({{ officer.streamer_name }})</span>
                                    </div>

                                    <div class="text-[10px] font-mono text-slate-500 truncate mt-1 flex items-center gap-1">
                                        <img :src="iconShield" class="w-3 h-3 invert opacity-80 shrink-0" alt="" />
                                        <span>{{ officer.patrol_zone || "Los Santos Sector" }}</span>
                                    </div>

                                    <div class="mt-2.5 bg-slate-950/80 border border-slate-800/80 rounded-lg p-2">
                                        <div class="flex items-center justify-between text-[10px] font-mono mb-1">
                                            <span class="text-slate-400 flex items-center gap-1">
                                                <img :src="iconTarget" class="w-3 h-3 invert opacity-80" alt="" />
                                                <span>{{ (officer.subscriber_count || 0) < 1000 ? "Road to 1K" : "Subscriber Count" }}:</span>
                                            </span>
                                            <span class="font-bold text-slate-200">
                                                {{ officer.subscriber_count ? Number(officer.subscriber_count).toLocaleString("id-ID") : "0" }} / 1.000
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="(officer.subscriber_count || 0) >= 1000 ? 'bg-emerald-500' : 'bg-red-500'"
                                                :style="{ width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between gap-1.5">
                                    <div class="flex items-center space-x-1.5">
                                        <button
                                            @click="emit('toggle-personal-stream', officer.channel_id || officer.handle)"
                                            class="p-1.5 rounded-lg border transition text-xs flex items-center gap-1"
                                            :class="isPersonalStream(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/80 border-purple-500/50' : 'text-slate-400 hover:text-purple-300 bg-slate-950 hover:bg-slate-800 border-slate-800'"
                                            :title="isPersonalStream(officer.channel_id || officer.handle) ? 'Hapus dari Personal' : 'Pin ke Personal Tab'"
                                        >
                                            <img
                                                :src="isPersonalStream(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus"
                                                class="w-3.5 h-3.5 invert"
                                                alt=""
                                            />
                                        </button>

                                        <button
                                            @click="emit('open-subscribe-popup', officer.channel_id || officer.handle, officer.officer_name)"
                                            class="px-2.5 py-1 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-[10px] shadow-sm shadow-red-600/30 transition flex items-center gap-1"
                                            title="Subscribe ke YouTube channel"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            <span>Subscribe</span>
                                        </button>
                                    </div>

                                    <div>
                                        <button
                                            v-if="officer.is_online && officer.live_stream"
                                            @click="emit('set-focus-stream', officer.live_stream.video_id); emit('close'); emit('set-department', 'ALL');"
                                            class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-[10px] shadow transition flex items-center gap-1"
                                            title="Buka siaran live di CCTV Multiview"
                                        >
                                            <img :src="iconFocus" class="w-3 h-3 invert" alt="" />
                                            <span>Tonton Live</span>
                                        </button>
                                        <a
                                            v-else
                                            :href="`https://www.youtube.com/${officer.handle}`"
                                            target="_blank"
                                            class="text-[10px] text-blue-400 hover:underline flex items-center gap-1 font-mono px-2 py-1 rounded bg-slate-950 border border-slate-800 hover:border-blue-500/40"
                                        >
                                            <span>Channel</span>
                                            <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-70" alt="" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-950 px-4 py-2.5 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between font-mono shrink-0">
                        <span>Total: {{ allDirectoryOfficers.length }} Personil</span>
                        <span class="text-emerald-400 font-bold">{{ streams.length }} Unit 10-8 Live</span>
                    </div>
                </div>

                <!-- 5. 10-CODES & TAC RADIO GUIDE PANEL -->
                <div v-if="activeRightDrawer === 'RADIO_CODES'" class="flex-1 flex flex-col min-h-0">
                    <div class="bg-slate-950/95 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center space-x-1.5">
                            <button
                                @click="emit('update:radioCodesActiveTab', 'CODES')"
                                :class="radioCodesActiveTab === 'CODES' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-3 py-1.5 text-xs rounded-lg border transition font-mono"
                            >
                                Kode 10 Kepolisian
                            </button>
                            <button
                                @click="emit('update:radioCodesActiveTab', 'TAC')"
                                :class="radioCodesActiveTab === 'TAC' ? 'bg-amber-600 text-white font-bold border-amber-400 shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border-slate-800'"
                                class="px-3 py-1.5 text-xs rounded-lg border transition font-mono flex items-center gap-1.5"
                            >
                                <img :src="iconRadio" class="w-3.5 h-3.5 invert" alt="" />
                                <span>Kanal Radio TAC 1–5</span>
                            </button>
                        </div>
                    </div>

                    <!-- KODE 10 TAB -->
                    <div v-if="radioCodesActiveTab === 'CODES'" class="flex-1 flex flex-col min-h-0">
                        <div class="p-3.5 bg-slate-950 border-b border-slate-800 shrink-0">
                            <div class="relative">
                                <input
                                    :value="radioCodesSearch"
                                    @input="emit('update:radioCodesSearch', $event.target.value)"
                                    type="text"
                                    placeholder="Cari kode misal 10-80, 10-33, pursuit, darurat..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-500 font-mono"
                                />
                                <img :src="iconSearch" class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" alt="" />
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 scrollbar-thin space-y-2.5">
                            <div
                                v-for="item in filteredPolice10Codes"
                                :key="item.code"
                                class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 hover:border-amber-500/40 transition"
                            >
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/40 font-mono font-bold text-xs">
                                            {{ item.code }}
                                        </span>
                                        <h4 class="text-xs font-bold text-slate-200">
                                            {{ item.title }}
                                        </h4>
                                    </div>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-950 text-slate-400 border border-slate-800 font-mono">
                                        {{ item.category }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                    {{ item.meaning }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- TAC GUIDES TAB -->
                    <div v-else-if="radioCodesActiveTab === 'TAC'" class="flex-1 overflow-y-auto p-4 scrollbar-thin space-y-3">
                        <div class="bg-amber-950/20 border border-amber-500/30 rounded-xl p-3 text-xs text-amber-200/90 leading-relaxed">
                            <div class="font-bold flex items-center gap-1.5 mb-1 text-amber-300 font-mono">
                                <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                                <span>Protokol Komando Tactical Radio TAC 1 s.d TAC 5</span>
                            </div>
                            Kanal TAC digunakan untuk mengkoordinasikan unit saat merespon kejadian darurat berskala besar agar radio dispatch utama tetap steril.
                        </div>

                        <div
                            v-for="tac in tacChannelGuides"
                            :key="tac.code"
                            class="bg-slate-900/90 border border-slate-800 rounded-xl p-3.5 hover:border-blue-500/40 transition space-y-2"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 rounded-lg bg-blue-600 text-white font-mono font-black text-xs shadow-sm">
                                        {{ tac.code }}
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-100">
                                        {{ tac.title }}
                                    </h4>
                                </div>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                                    :class="getTacUnitCountLocal(tac.code.replace(' ', '_')) > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-slate-950 text-slate-500 border border-slate-800'"
                                >
                                    {{ getTacUnitCountLocal(tac.code.replace(' ', '_')) }} Unit Aktif
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-300 leading-relaxed">
                                <span class="text-blue-400 font-semibold font-mono">Ruang Lingkup:</span>
                                {{ tac.scope }}
                            </div>

                            <div class="text-[11px] text-slate-400 bg-slate-950/80 p-2.5 rounded-lg border border-slate-800/80 leading-relaxed">
                                <span class="text-amber-400 font-semibold font-mono">SOP & Protokol:</span>
                                {{ tac.protocol }}
                            </div>

                            <div class="pt-1 flex items-center justify-end">
                                <button
                                    @click="emit('set-department', tac.code.replace(' ', '_')); emit('close');"
                                    class="px-3 py-1.5 rounded-lg bg-slate-850 hover:bg-blue-600 text-slate-200 hover:text-white text-xs font-semibold border border-slate-700 hover:border-blue-500 transition flex items-center gap-1.5 font-mono"
                                >
                                    <span>Buka Multiview {{ tac.code }}</span>
                                    <span>›</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-950 px-4 py-2.5 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between font-mono shrink-0">
                        <span>Standar Komunikasi LSPD / BCSO / SASP</span>
                        <span class="text-amber-400">Radio Dispatch SOP</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>
