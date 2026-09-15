<script setup>
import { ref } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    quickAddInput: {
        type: Object,
        default: () => ({ videoUrlOrId: '', officerName: '', callsign: '', department: 'LSPD' })
    },
    isCheckingChannel: {
        type: Boolean,
        default: false
    },
    liveSearchResults: {
        type: Array,
        default: () => []
    },
    isSearchingLive: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'addStream', 'searchLive', 'addQuickSearchStream']);

const activeTab = ref('quick'); // 'quick' | 'search'
const searchQuery = ref('#imeroleplay');

const handleSubmitQuickAdd = () => {
    emit('addStream', { ...props.quickAddInput });
};

const handleSearch = () => {
    emit('searchLive', searchQuery.value);
};
</script>

<template>
    <div 
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200 font-sans"
    >
        <div 
            class="bg-[#090e1a] border border-blue-500/40 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]"
            @click.stop
        >
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-blue-950 via-slate-900 to-slate-950 border-b border-blue-900/40 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-400/40 flex items-center justify-center text-blue-300 text-sm font-bold">
                        📺
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">
                            Pusat Tambah Feed Siaran Taktis
                        </h3>
                        <p class="text-[11px] text-slate-400 font-mono">Input URL/Video ID atau cari siaran live publik</p>
                    </div>
                </div>

                <button 
                    @click="emit('close')"
                    class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition"
                >
                    ✕
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex border-b border-slate-800 bg-slate-950/60 px-6 pt-2 gap-4 text-xs font-mono">
                <button 
                    @click="activeTab = 'quick'"
                    class="pb-2.5 font-bold transition border-b-2"
                    :class="activeTab === 'quick' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'"
                >
                    🔗 Input URL / Video ID YouTube
                </button>
                <button 
                    @click="activeTab = 'search'"
                    class="pb-2.5 font-bold transition border-b-2"
                    :class="activeTab === 'search' ? 'text-blue-400 border-blue-500' : 'text-slate-400 border-transparent hover:text-slate-200'"
                >
                    🔍 Cari Siaran Live #imeroleplay
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                
                <!-- TAB 1: QUICK ADD FORM -->
                <div v-if="activeTab === 'quick'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1.5">
                            URL Video / ID Stream YouTube <span class="text-red-400">*</span>
                        </label>
                        <input 
                            v-model="quickAddInput.videoUrlOrId"
                            type="text"
                            placeholder="Contoh: https://www.youtube.com/watch?v=dQw4w9WgXcQ atau dQw4w9WgXcQ"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono shadow-inner"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1.5">
                                Nama Perwira / Unit
                            </label>
                            <input 
                                v-model="quickAddInput.officerName"
                                type="text"
                                placeholder="Misal: Officer John"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 font-sans"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1.5">
                                Callsign / Unit ID
                            </label>
                            <input 
                                v-model="quickAddInput.callsign"
                                type="text"
                                placeholder="Misal: 1A-12"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono uppercase"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1.5">
                                Agensi Kepolisian
                            </label>
                            <select 
                                v-model="quickAddInput.department"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono"
                            >
                                <option value="LSPD">LSPD (Los Santos PD)</option>
                                <option value="LSCSD">LSCSD (Sheriff Dept)</option>
                                <option value="SASP">SASP (State Police)</option>
                                <option value="SAPR">SAPR (Park Ranger)</option>
                                <option value="EMS">EMS / SAMFD</option>
                                <option value="CIVILIAN">CIVILIAN / DISPATCH</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button 
                            @click="handleSubmitQuickAdd"
                            :disabled="!quickAddInput.videoUrlOrId.trim() || isCheckingChannel"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white font-bold text-xs font-mono shadow-lg transition flex items-center gap-2"
                        >
                            <span>{{ isCheckingChannel ? 'Memeriksa Stream...' : '⚡ Tambahkan Stream ke Multiview' }}</span>
                        </button>
                    </div>
                </div>

                <!-- TAB 2: LIVE SEARCH FEED -->
                <div v-else class="space-y-4">
                    <div class="flex gap-2">
                        <input 
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kata kunci hashtag live..."
                            class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono"
                            @keyup.enter="handleSearch"
                        />
                        <button 
                            @click="handleSearch"
                            :disabled="isSearchingLive"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs font-mono shadow transition"
                        >
                            {{ isSearchingLive ? 'Mencari...' : 'Cari' }}
                        </button>
                    </div>

                    <!-- Search Results -->
                    <div class="space-y-2 max-h-[300px] overflow-y-auto">
                        <div v-if="liveSearchResults.length === 0" class="py-8 text-center text-slate-500 font-mono text-xs">
                            Tekan tombol Cari untuk memuat siaran live terkini.
                        </div>

                        <div 
                            v-for="item in liveSearchResults" 
                            :key="item.video_id"
                            class="p-3 bg-slate-900/80 border border-slate-800 hover:border-blue-500/50 rounded-xl flex items-center justify-between gap-3 transition"
                        >
                            <div class="flex items-center space-x-3 min-w-0">
                                <img :src="item.thumbnail" class="w-16 h-10 object-cover rounded-lg border border-slate-700 shrink-0" alt="" />
                                <div class="min-w-0">
                                    <h5 class="text-xs font-bold text-white truncate">{{ item.title }}</h5>
                                    <p class="text-[10px] text-slate-400 font-mono mt-0.5 truncate">{{ item.channel_title }}</p>
                                </div>
                            </div>

                            <button 
                                @click="emit('addQuickSearchStream', item)"
                                class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] font-mono shrink-0"
                            >
                                + Tambah
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
