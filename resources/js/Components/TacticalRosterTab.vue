<script setup>
import { computed } from "vue";

import logoSaspColor from "@/Components/Icons/SASP_256.jpg";
import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconRoster from "@/Components/Icons/user-svgrepo-com.svg";
import iconShield from "@/Components/Icons/shield-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconPinPlus from "@/Components/Icons/star-line-svgrepo-com.svg";
import iconPinMinus from "@/Components/Icons/star-svgrepo-com.svg";

const props = defineProps({
    offlineOfficers: { type: Array, default: () => [] },
    allActiveStreams: { type: Array, default: () => [] },
    filteredOfflineOfficers: { type: Array, default: () => [] },
    personalStreamIds: { type: Array, default: () => [] },
});

const emit = defineEmits(["toggle-personal-stream", "open-subscribe-popup"]);

const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return "bg-blue-900/60 text-blue-200 border-blue-700/80";
    if (d.includes("BCSO") || d.includes("LSCSD")) return "bg-amber-900/60 text-amber-200 border-amber-700/80";
    if (d.includes("SASP")) return "bg-teal-900/60 text-teal-200 border-teal-700/80";
    if (d.includes("SAPR") || d.includes("RANGER")) return "bg-emerald-900/60 text-emerald-200 border-emerald-700/80";
    return "bg-slate-800 text-slate-300 border-slate-700";
};

const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return iconSasp;
    if (d.includes("SAPR") || d.includes("RANGER")) return iconSapr;
    return iconAllUnits;
};

const isPersonalStream = (channelOrHandle) => {
    if (!channelOrHandle) return false;
    const target = String(channelOrHandle).trim().toLowerCase();
    return (props.personalStreamIds || []).some(
        (id) => String(id).trim().toLowerCase() === target
    );
};
</script>

<template>
    <div class="max-w-6xl mx-auto bg-slate-950 rounded-2xl border border-slate-800/80 p-5 shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-4 mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-100 flex items-center gap-2">
                    <img :src="iconRoster" class="w-4 h-4 invert opacity-90" alt="" />
                    <span>IME ROLEPLAY POLICE DEPARTMENT ROSTER (10-7 OFFLINE)</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Showing registered law enforcement officers currently off-duty or not broadcasting.
                </p>
            </div>
            <div class="text-xs font-mono text-slate-400 bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800">
                Total Registered: {{ offlineOfficers.length + allActiveStreams.length }} Units
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <div
                v-for="officer in filteredOfflineOfficers"
                :key="officer.channel_id"
                class="bg-[#0b121e] rounded-xl p-3.5 border border-slate-800 hover:border-slate-700 transition flex items-start space-x-3"
            >
                <!-- Avatar / Badge -->
                <div class="w-11 h-11 rounded-lg bg-slate-900 border border-slate-800 overflow-hidden shrink-0 flex items-center justify-center p-1.5">
                    <img
                        v-if="officer.avatar_url"
                        :src="officer.avatar_url"
                        referrerpolicy="no-referrer"
                        class="w-full h-full object-cover rounded"
                    />
                    <img
                        v-else
                        :src="getDeptIcon(officer.department)"
                        class="w-full h-full object-contain"
                        alt=""
                    />
                </div>

                <!-- Officer Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-1.5">
                        <span class="px-1.5 py-0.2 text-[10px] font-black rounded border" :class="getDeptBadgeClass(officer.department)">
                            {{ officer.department }}
                        </span>
                        <span class="font-mono text-xs font-bold text-slate-300">{{ officer.callsign }}</span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-100 truncate mt-0.5">
                        {{ officer.officer_name }}
                    </h3>
                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                        <span>{{ officer.rank }}</span>
                        <span>•</span>
                        <span class="font-mono">{{ officer.badge_number }}</span>
                    </div>
                    <div class="text-[11px] font-mono text-blue-400/80 truncate mt-1 flex items-center gap-1">
                        <img :src="iconShield" class="w-3 h-3 invert opacity-80 shrink-0" alt="" />
                        <span>{{ officer.patrol_zone || "Los Santos" }}</span>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px] text-slate-500">
                        <div class="truncate mr-2">
                            Streamer: <span class="text-slate-300 font-semibold">{{ officer.streamer_name }}</span>
                        </div>
                        <div class="flex items-center space-x-1.5 shrink-0">
                            <button
                                @click="emit('toggle-personal-stream', officer.channel_id || officer.handle)"
                                class="p-1 rounded transition flex items-center gap-0.5 text-[10px]"
                                :class="isPersonalStream(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/70 border border-purple-500/50' : 'text-slate-400 hover:text-purple-300 hover:bg-slate-800'"
                                :title="isPersonalStream(officer.channel_id || officer.handle) ? 'Hapus dari Personal' : 'Tambah ke Personal Watchlist (Maks 6)'"
                            >
                                <img
                                    :src="isPersonalStream(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus"
                                    class="w-3 h-3 invert"
                                    alt=""
                                />
                            </button>
                            <button
                                @click="emit('open-subscribe-popup', officer.channel_id || officer.handle, officer.officer_name)"
                                class="bg-red-600/90 hover:bg-red-600 text-white font-bold px-2 py-0.5 rounded text-[10px] transition flex items-center gap-1 shadow-sm shadow-red-600/30"
                                title="Subscribe to channel without leaving page"
                            >
                                <span class="w-2 h-2 rounded-full bg-white mr-0.5"></span>
                                <span>Sub</span>
                            </button>
                            <a
                                :href="`https://www.youtube.com/${officer.handle}`"
                                target="_blank"
                                class="text-blue-400 hover:underline flex items-center gap-0.5"
                            >
                                <span>{{ officer.handle }}</span>
                                <img :src="iconExternal" class="w-2.5 h-2.5 invert opacity-70" alt="" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
