<script setup>
import iconPersonal from "@/Components/Icons/star-svgrepo-com.svg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";
import iconSearch from "@/Components/Icons/search-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconReset from "@/Components/Icons/reset-svgrepo-com.svg";
import iconUrl from "@/Components/Icons/url-checker-svgrepo-com.svg";
import iconDelete from "@/Components/Icons/delete-2-svgrepo-com.svg";
import iconUser from "@/Components/Icons/user-svgrepo-com.svg";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    quickAddMode: { type: String, default: "SEARCH" },
    liveSearchQuery: { type: String, default: "" },
    isLiveSearching: { type: Boolean, default: false },
    liveSearchError: { type: String, default: null },
    liveSearchSuccessNotice: { type: String, default: null },
    liveSearchResults: { type: Array, default: () => [] },
    popularHashtagPresets: { type: Array, default: () => [] },
    quickAddInput: {
        type: Object,
        default: () => ({
            urlOrId: "",
            officerName: "",
            callsign: "",
            department: "LSPD",
            patrolZone: "Mission Row",
        }),
    },
    totalPersonalCount: { type: Number, default: 0 },
    totalSavedPersonalCount: { type: Number, default: 0 },
    savedPersonalList: { type: Array, default: () => [] },
    personalStreamIds: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "close",
    "update:quickAddMode",
    "update:liveSearchQuery",
    "search-live-streams",
    "add-live-stream-to-personal",
    "quick-add-stream",
    "clear-all-personal",
    "remove-personal-stream",
]);

const isPersonalStream = (channelOrHandle) => {
    if (!channelOrHandle) return false;
    const target = String(channelOrHandle).trim().toLowerCase();
    return (props.personalStreamIds || []).some(
        (id) => String(id).trim().toLowerCase() === target,
    );
};
</script>

<template>
    <div>
        <!-- Backdrop Overlay -->
        <div
            v-if="isOpen"
            @click="emit('close')"
            class="fixed inset-0 bg-black/60 z-50 transition-opacity duration-300"
        ></div>

        <!-- Sliding Drawer Panel -->
        <aside
            class="fixed inset-y-0 right-0 z-50 bg-slate-950 border-l border-slate-800 flex flex-col transition-all duration-300 ease-in-out w-full sm:w-[440px] md:w-[480px]"
            :class="[
                isOpen
                    ? 'translate-x-0'
                    : 'translate-x-full pointer-events-none',
            ]"
        >
            <!-- Drawer Top Header Bar -->
            <div
                class="bg-slate-900/95 px-4 py-3.5 border-b border-slate-800 flex items-center justify-between shrink-0"
            >
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-base shrink-0 border bg-emerald-950/80 border-emerald-500/60 text-emerald-400"
                    >
                        <img :src="iconQuickAdd" class="w-4 h-4 invert" alt="" />
                    </div>
                    <div class="truncate">
                        <div class="flex items-center space-x-2">
                            <h2
                                class="text-xs font-black tracking-wider uppercase font-mono truncate text-emerald-300"
                            >
                                QUICK ADD LIVE FEED
                            </h2>
                            <span
                                class="text-[9px] px-1.5 py-0.2 rounded-full border font-mono uppercase bg-emerald-500/20 text-emerald-300 border-emerald-500/40"
                            >
                                Watchlist
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 truncate">
                            Inject external YouTube live patrol feed into multifeed
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
            <div
                class="flex-1 overflow-y-auto scrollbar-thin flex flex-col min-h-0 bg-slate-950 p-4 gap-3.5"
            >
                <div
                    v-if="liveSearchSuccessNotice"
                    class="bg-emerald-950/90 border border-emerald-500/60 rounded-xl px-3.5 py-2 text-xs text-emerald-300 font-mono flex items-center gap-2 shadow-lg animate-in fade-in duration-200"
                >
                    <span>✓</span>
                    <span>{{ liveSearchSuccessNotice }}</span>
                </div>

                <div
                    class="bg-purple-950/30 border border-purple-500/40 rounded-xl p-3 text-xs text-purple-200/90 leading-relaxed"
                >
                    <div
                        class="font-bold flex items-center justify-between mb-1 text-purple-300"
                    >
                        <span class="flex items-center gap-1.5">
                            <img
                                :src="iconPersonal"
                                class="w-3.5 h-3.5 invert opacity-90"
                                alt=""
                            />
                            <span>Quick Watchlist & Personal</span>
                        </span>
                        <span
                            class="text-[10px] bg-purple-900/60 px-2 py-0.5 rounded-full font-mono font-bold text-purple-200 border border-purple-400/30"
                        >
                            {{ totalPersonalCount }}/6 Video
                        </span>
                    </div>
                    Cari lawan/gang atau masukkan live stream YouTube. Stream yang dipilih akan langsung masuk ke tab <strong>PERSONAL</strong> browser lokal Anda.
                </div>

                <div
                    class="grid grid-cols-2 bg-slate-950 p-1 rounded-xl border border-slate-800 gap-1"
                >
                    <button
                        type="button"
                        @click="emit('update:quickAddMode', 'SEARCH')"
                        :class="
                            quickAddMode === 'SEARCH'
                                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                                : 'text-slate-400 hover:text-slate-200'
                        "
                        class="py-1.5 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5"
                    >
                        <img
                            :src="iconSearch"
                            class="w-3.5 h-3.5 invert opacity-80"
                            alt=""
                        />
                        <span>Cari Live Hashtag</span>
                    </button>
                    <button
                        type="button"
                        @click="emit('update:quickAddMode', 'MANUAL')"
                        :class="
                            quickAddMode === 'MANUAL'
                                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                                : 'text-slate-400 hover:text-slate-200'
                        "
                        class="py-1.5 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5"
                    >
                        <img
                            :src="iconUrl"
                            class="w-3.5 h-3.5 invert opacity-80"
                            alt=""
                        />
                        <span>Input Manual</span>
                    </button>
                </div>

                <!-- SEARCH HASHTAG -->
                <div
                    v-if="quickAddMode === 'SEARCH'"
                    class="flex flex-col gap-3"
                >
                    <form
                        @submit.prevent="
                            emit('search-live-streams', liveSearchQuery)
                        "
                        class="flex flex-col gap-2"
                    >
                        <div class="relative">
                            <input
                                :value="liveSearchQuery"
                                @input="
                                    emit(
                                        'update:liveSearchQuery',
                                        $event.target.value,
                                    )
                                "
                                type="text"
                                required
                                placeholder="Ketik hashtag misal #imeroleplay #burgenk..."
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-20 py-2.5 text-xs text-slate-200 focus:outline-hidden focus:border-purple-500 font-mono placeholder-slate-500"
                            />
                            <img
                                :src="iconSearch"
                                class="w-3.5 h-3.5 invert opacity-40 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
                                alt=""
                            />
                            <button
                                type="submit"
                                :disabled="isLiveSearching"
                                class="absolute right-1.5 top-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-1"
                            >
                                <img
                                    v-if="isLiveSearching"
                                    :src="iconRefresh"
                                    class="w-3 h-3 animate-spin invert"
                                    alt=""
                                />
                                <span>{{
                                    isLiveSearching ? "Mencari..." : "Cari"
                                }}</span>
                            </button>
                        </div>
                    </form>

                    <div class="flex flex-col gap-1.5">
                        <span
                            class="text-[10px] font-mono text-slate-400 uppercase tracking-wider"
                        >Hashtag / Gang Populer:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="preset in popularHashtagPresets"
                                :key="preset.label"
                                type="button"
                                @click="
                                    emit(
                                        'search-live-streams',
                                        preset.query,
                                    )
                                "
                                class="text-[11px] px-2.5 py-1 rounded-lg border font-mono transition"
                                :class="
                                    liveSearchQuery === preset.query
                                        ? 'bg-purple-600 text-white font-bold border-purple-400'
                                        : 'bg-slate-900 text-purple-300 hover:bg-purple-950/60 border-purple-500/30 hover:border-purple-400/50'
                                "
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="liveSearchError"
                        class="p-3 bg-red-950/30 border border-red-500/40 rounded-xl text-xs text-red-300"
                    >
                        {{ liveSearchError }}
                    </div>

                    <div
                        v-if="isLiveSearching"
                        class="py-8 flex flex-col items-center justify-center text-slate-400 gap-2"
                    >
                        <div
                            class="w-7 h-7 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"
                        ></div>
                        <span class="text-xs font-mono"
                            >Sedang mencari stream YouTube live aktif...</span
                        >
                    </div>

                    <div
                        v-else-if="liveSearchResults.length > 0"
                        class="flex flex-col gap-2.5"
                    >
                        <div
                            class="flex items-center justify-between text-xs text-slate-400 font-mono px-0.5"
                        >
                            <span
                                >Ditemukan:
                                <strong class="text-slate-200">{{
                                    liveSearchResults.length
                                }}</strong>
                                Live Stream</span
                            >
                            <span
                                class="text-[10px] text-emerald-400 font-bold"
                                >● 10-8 LIVE</span
                            >
                        </div>

                        <div class="space-y-2.5">
                            <div
                                v-for="item in liveSearchResults"
                                :key="item.video_id"
                                class="bg-slate-900/90 border border-slate-800 hover:border-purple-500/60 rounded-xl p-2.5 transition flex gap-3 items-start"
                            >
                                <div
                                    class="w-28 aspect-video bg-black rounded-lg overflow-hidden shrink-0 relative"
                                >
                                    <img
                                        :src="item.thumbnail_url"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute bottom-1 right-1 bg-black/80 px-1 py-0.2 rounded-sm text-[9px] font-mono text-red-400 font-bold"
                                    >
                                        LIVE
                                    </div>
                                </div>

                                <div
                                    class="flex-1 min-w-0 flex flex-col justify-between h-full"
                                >
                                    <div>
                                        <h4
                                            class="text-xs font-bold text-slate-100 line-clamp-2 leading-snug flex-1"
                                            :title="item.title"
                                        >
                                            {{ item.title }}
                                        </h4>
                                        <div
                                            class="text-[11px] text-slate-400 truncate mt-1 flex items-center gap-1.5"
                                        >
                                            <img
                                                :src="iconUser"
                                                class="w-3 h-3 invert opacity-60"
                                                alt=""
                                            />
                                            <span
                                                class="text-purple-300 font-semibold truncate"
                                                >{{
                                                    item.channel_name
                                                }}</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        class="mt-2 flex items-center justify-between gap-2"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                emit(
                                                    'add-live-stream-to-personal',
                                                    item,
                                                )
                                            "
                                            :class="
                                                isPersonalStream(
                                                    item.video_id,
                                                )
                                                    ? 'bg-purple-950 text-purple-300 border-purple-500/50'
                                                    : 'bg-purple-600 hover:bg-purple-500 text-white shadow-md shadow-purple-600/30'
                                            "
                                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center justify-center font-mono border border-transparent"
                                        >
                                            <span>{{
                                                isPersonalStream(
                                                    item.video_id,
                                                )
                                                    ? "✓ Pinned"
                                                    : "Pin ke Personal"
                                            }}</span>
                                        </button>

                                        <a
                                            :href="`https://www.youtube.com/watch?v=${item.video_id}`"
                                            target="_blank"
                                            class="p-1 hover:text-white text-slate-400 hover:bg-slate-800 rounded-sm transition flex items-center justify-center"
                                            title="Open on YouTube"
                                        >
                                            <img
                                                :src="iconExternal"
                                                class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100"
                                                alt="Open on YouTube"
                                            />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MANUAL INPUT -->
                <div v-else-if="quickAddMode === 'MANUAL'">
                    <form
                        @submit.prevent="emit('quick-add-stream')"
                        class="flex flex-col gap-3.5"
                    >
                        <div>
                            <label
                                class="text-xs font-semibold text-slate-300 block mb-1"
                                >YouTube Live URL or 11-char Video ID *</label
                            >
                            <input
                                v-model="quickAddInput.urlOrId"
                                type="text"
                                required
                                placeholder="https://youtube.com/watch?v=xxxxxxxxxxx or dQw4w9WgXcQ"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-hidden focus:border-emerald-500 font-mono placeholder-slate-600"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-xs font-semibold text-slate-300 block mb-1"
                                    >Officer / Unit Name</label
                                >
                                <input
                                    v-model="quickAddInput.officerName"
                                    type="text"
                                    placeholder="Ofc. Raymond"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-hidden focus:border-emerald-500 placeholder-slate-600"
                                />
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-slate-300 block mb-1"
                                    >Callsign</label
                                >
                                <input
                                    v-model="quickAddInput.callsign"
                                    type="text"
                                    placeholder="1-ADAM-99"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-hidden focus:border-emerald-500 font-mono placeholder-slate-600"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-xs font-semibold text-slate-300 block mb-1"
                                    >Department</label
                                >
                                <select
                                    v-model="quickAddInput.department"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-hidden focus:border-emerald-500"
                                >
                                    <option value="LSPD">LSPD (Police)</option>
                                    <option value="BCSO">BCSO (Sheriff)</option>
                                    <option value="SASP">SASP (State Police)</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-slate-300 block mb-1"
                                    >Patrol Zone</label
                                >
                                <input
                                    v-model="quickAddInput.patrolZone"
                                    type="text"
                                    placeholder="Mission Row"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-hidden focus:border-emerald-500 placeholder-slate-600"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-end space-x-2"
                        >
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
                                <img
                                    :src="iconQuickAdd"
                                    class="w-3.5 h-3.5 invert"
                                    alt=""
                                />
                                <span>Inject Live Feed</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SAVED PERSONAL WATCHLIST MANAGER -->
                <div
                    class="mt-2 pt-3 border-t border-slate-800/80 flex flex-col gap-2.5"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <img
                                :src="iconPersonal"
                                class="w-3.5 h-3.5 invert opacity-80"
                                alt=""
                            />
                            <span
                                class="text-xs font-bold text-slate-200 uppercase font-mono tracking-wide"
                            >
                                Watchlist Tersimpan
                            </span>
                            <span
                                class="text-[10px] bg-purple-950/80 px-2 py-0.5 rounded-full font-mono text-purple-300 border border-purple-500/40 font-bold"
                            >
                                {{ totalPersonalCount }}/6 Online ({{
                                    totalSavedPersonalCount
                                }}
                                Total)
                            </span>
                        </div>
                        <button
                            v-if="totalSavedPersonalCount > 0"
                            type="button"
                            @click="emit('clear-all-personal')"
                            class="text-[11px] text-red-400 hover:text-red-300 font-mono flex items-center gap-1 hover:underline"
                            title="Kosongkan seluruh pin personal"
                        >
                            <img
                                :src="iconReset"
                                class="w-3 h-3 invert opacity-80"
                                alt=""
                            />
                            <span>Reset Semua</span>
                        </button>
                    </div>

                    <div
                        v-if="totalSavedPersonalCount === 0"
                        class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center text-xs text-slate-500 font-mono"
                    >
                        Belum ada video atau channel yang disimpan di Personal.
                    </div>

                    <div
                        v-else
                        class="space-y-2 max-h-60 overflow-y-auto scrollbar-thin pr-0.5"
                    >
                        <div
                            v-for="savedItem in savedPersonalList"
                            :key="savedItem.id"
                            class="bg-slate-900/80 border border-slate-800 rounded-xl p-2.5 flex items-center justify-between gap-2.5 transition hover:border-slate-700"
                        >
                            <div
                                class="flex items-center space-x-2.5 min-w-0"
                            >
                                <img
                                    v-if="savedItem.thumbnail"
                                    :src="savedItem.thumbnail"
                                    class="w-12 h-8 rounded-sm object-cover shrink-0 border border-slate-800"
                                />
                                <div
                                    v-else
                                    class="w-12 h-8 rounded-sm bg-slate-950 border border-slate-800 flex items-center justify-center text-xs text-slate-500 shrink-0"
                                >
                                    <img
                                        :src="iconPersonal"
                                        class="w-4 h-4 invert opacity-40"
                                        alt=""
                                    />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="text-[9px] px-1.5 py-0.2 rounded-sm font-mono font-bold shrink-0"
                                            :class="
                                                savedItem.isOnline
                                                    ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/40'
                                                    : 'bg-slate-950 text-slate-500 border border-slate-800'
                                            "
                                        >
                                            {{
                                                savedItem.isOnline
                                                    ? "● 10-8 LIVE"
                                                    : "○ 10-7 OFFLINE"
                                            }}
                                        </span>
                                        <span
                                            class="text-[11px] font-bold text-slate-200 truncate"
                                            :title="savedItem.name"
                                            >{{ savedItem.name }}</span
                                        >
                                    </div>
                                    <div
                                        class="text-[10px] text-slate-400 font-mono truncate mt-0.5"
                                    >
                                        {{ savedItem.subtext }}
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="
                                    emit(
                                        'remove-personal-stream',
                                        savedItem.rawId,
                                    )
                                "
                                class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-950 hover:bg-red-950/50 border border-slate-800 hover:border-red-500/40 rounded-lg text-xs transition shrink-0 flex items-center justify-center"
                                title="Hapus dari daftar personal"
                            >
                                <img
                                    :src="iconDelete"
                                    class="w-3.5 h-3.5 invert opacity-80 group-hover:opacity-100"
                                    alt="Delete"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>
