<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';

// SVG Icon Assets & Branding Logos
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import iconFocus from '@/Components/Icons/focus-point-round-844-svgrepo-com.svg';
import iconSearch from '@/Components/Icons/search-svgrepo-com.svg';
import iconRefresh from '@/Components/Icons/refresh-cw-svgrepo-com.svg';
import iconPinPlus from '@/Components/Icons/star-line-svgrepo-com.svg';
import iconPinMinus from '@/Components/Icons/star-svgrepo-com.svg';
import iconFeedback from '@/Components/Icons/report-svgrepo-com.svg';
import iconExternal from '@/Components/Icons/link-external-svgrepo-com.svg';
import iconTarget from '@/Components/Icons/target-svgrepo-com.svg';
import iconFullscreen from '@/Components/Icons/full-screen-svgrepo-com.svg';
import iconExitFullscreen from '@/Components/Icons/minimize-svgrepo-com.svg';

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
        default: '',
    },
});

// State Management
const officers = ref(props.initialOfficers);
const searchQuery = ref('');
const selectedDept = ref('ALL'); // 'ALL', 'LIVE_ONLY', 'LSPD', 'BCSO', 'SASP'
const sortBy = ref('status'); // 'status', 'subs_desc', 'subs_asc', 'name'
const isSyncing = ref(false);
const isFullscreen = ref(false);
const isQuickAddOpen = ref(false);

const handleAvatarError = (e, officer) => {
    e.target.src = `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officer.callsign || officer.officer_name || 'officer')}`;
};

// Personal Streams saved to localStorage
const personalIds = ref([]);
const loadPersonalStorage = () => {
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            const saved = localStorage.getItem('ime_personal_video_ids');
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

const togglePin = (identifier) => {
    const idx = personalIds.value.indexOf(identifier);
    if (idx !== -1) {
        personalIds.value.splice(idx, 1);
    } else {
        if (personalIds.value.length >= 6) {
            alert('Maksimal 6 siaran pada Personal Watchlist!');
            return;
        }
        personalIds.value.push(identifier);
    }
    try {
        localStorage.setItem('ime_personal_video_ids', JSON.stringify(personalIds.value));
    } catch (e) {
        console.warn(e);
    }
};

// 1-Click Subscribe Popup Modal (With Smart Fallback to Video URL)
const openSubscribePopup = (channelIdOrHandle, officerName = '', videoId = '') => {
    let subUrl = '';
    if (channelIdOrHandle && typeof channelIdOrHandle === 'string' && channelIdOrHandle.trim() !== '') {
        const cleanStr = channelIdOrHandle.trim();
        if (cleanStr.startsWith('UC')) {
            subUrl = `https://www.youtube.com/channel/${cleanStr}?sub_confirmation=1`;
        } else {
            const cleanHandle = cleanStr.startsWith('@') ? cleanStr : `@${cleanStr}`;
            subUrl = `https://www.youtube.com/${cleanHandle}?sub_confirmation=1`;
        }
    } else if (videoId && typeof videoId === 'string' && videoId.trim() !== '') {
        subUrl = `https://www.youtube.com/watch?v=${videoId.trim()}?sub_confirmation=1`;
    } else {
        subUrl = 'https://www.youtube.com/';
    }
    const width = 640;
    const height = 660;
    const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - height) / 2));
    window.open(subUrl, 'YouTubeSubscribeModal', `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,resizable=yes`);
};

// Department Badge Class Helper
const getDeptBadgeClass = (dept) => {
    switch (dept) {
        case 'LSPD': return 'bg-blue-600/30 text-blue-300 border-blue-500/50';
        case 'BCSO': return 'bg-amber-600/30 text-amber-300 border-amber-500/50';
        case 'SASP': return 'bg-teal-600/30 text-teal-300 border-teal-500/50';
        case 'SAPR':
        case 'PARK RANGER': return 'bg-green-600/30 text-green-300 border-green-500/50';
        default: return 'bg-slate-700/40 text-slate-300 border-slate-600';
    }
};

// Filtered & Sorted Officers
const filteredOfficers = computed(() => {
    let list = officers.value;

    if (selectedDept.value === 'LIVE_ONLY') {
        list = list.filter(o => o.is_online);
    } else if (selectedDept.value !== 'ALL') {
        if (selectedDept.value === 'SAPR') {
            list = list.filter(o => o.department === 'SAPR' || o.department === 'PARK RANGER');
        } else {
            list = list.filter(o => o.department === selectedDept.value);
        }
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(o => 
            (o.officer_name && o.officer_name.toLowerCase().includes(q)) ||
            (o.callsign && o.callsign.toLowerCase().includes(q)) ||
            (o.streamer_name && o.streamer_name.toLowerCase().includes(q)) ||
            (o.handle && o.handle.toLowerCase().includes(q)) ||
            (o.badge_number && o.badge_number.toLowerCase().includes(q)) ||
            (o.patrol_zone && o.patrol_zone.toLowerCase().includes(q)) ||
            (o.rank && o.rank.toLowerCase().includes(q))
        );
    }

    return [...list].sort((a, b) => {
        if (sortBy.value === 'status') {
            if (a.is_online && !b.is_online) return -1;
            if (!a.is_online && b.is_online) return 1;
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (sortBy.value === 'subs_desc') {
            return (b.subscriber_count || 0) - (a.subscriber_count || 0);
        } else if (sortBy.value === 'subs_asc') {
            const isTargetA = (a.subscriber_count || 0) < 1000 ? 0 : 1;
            const isTargetB = (b.subscriber_count || 0) < 1000 ? 0 : 1;
            if (isTargetA !== isTargetB) return isTargetA - isTargetB;
            return (a.subscriber_count || 0) - (b.subscriber_count || 0);
        } else if (sortBy.value === 'name') {
            return (a.officer_name || '').localeCompare(b.officer_name || '');
        }
        return 0;
    });
});

const triggerSync = async () => {
    isSyncing.value = true;
    try {
        const res = await fetch('/api/v1/sync', { method: 'POST', headers: { 'Accept': 'application/json' } });
        if (res.ok) {
            window.location.reload();
        }
    } catch (e) {
        console.warn(e);
    } finally {
        isSyncing.value = false;
    }
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        document.exitFullscreen();
        isFullscreen.value = false;
    }
};
</script>

<template>
    <TacticalLayout>
        <Head title="Direktori Officer — IME RP SASP Police Duty" />

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            
            <!-- Hero Title & Telemetry Cards -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2 py-0.5 rounded-full bg-blue-950 text-blue-300 border border-blue-500/40 text-[10px] font-mono font-bold uppercase tracking-wider">
                            Database Kepolisian
                        </span>
                        <span class="text-xs text-slate-500 font-mono">• Terverifikasi IME RP</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-100 tracking-tight">
                        DIREKTORI PETUGAS & STREAMER
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        Daftar lengkap seluruh personil kepolisian Los Santos (LSPD), Blaine County Sheriff (BCSO), San Andreas State Police (SASP), dan San Andreas Park Rangers (SAPR) beserta status siaran langsung dan milestone YouTube.
                    </p>
                </div>

                <!-- Fast Telemetry Stats -->
                <div class="grid grid-cols-3 gap-2.5 shrink-0 font-mono">
                    <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-3 text-center">
                        <div class="text-base font-black text-slate-100">{{ officers.length }}</div>
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Total Unit</div>
                    </div>
                    <div class="bg-emerald-950/40 border border-emerald-500/40 rounded-xl p-3 text-center">
                        <div class="text-base font-black text-emerald-300 flex items-center justify-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>{{ deptStats.total_live || 0 }}</span>
                        </div>
                        <div class="text-[10px] text-emerald-400 uppercase font-semibold">10-8 Live</div>
                    </div>
                    <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-3 text-center">
                        <div class="text-base font-black text-slate-400">{{ deptStats.total_offline || (officers.length - (deptStats.total_live || 0)) }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold">10-7 Offline</div>
                    </div>
                </div>
            </div>

            <!-- Toolbar: Search, Dept Filters, Sort By -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 shadow-lg">
                               <!-- Search Input -->
                <div class="relative flex-1 sm:w-80 flex items-center">
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Cari nama polisi, callsign, handle YouTube, badge, sektor..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-8 py-2.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono"
                    />
                    <img :src="iconSearch" class="w-4 h-4 invert opacity-40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" alt="" />
                    <button 
                        v-if="searchQuery" 
                        @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs font-bold leading-none p-0.5 rounded hover:bg-slate-800 transition"
                        title="Bersihkan pencarian"
                    >
                        ✕
                    </button>
                </div>

                <!-- Dept Filter Pills & Sort Selector -->
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap justify-between md:justify-end">
                    <!-- Dept Pills -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none">
                        <button 
                            @click="selectedDept = 'ALL'"
                            :class="selectedDept === 'ALL' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-950 text-slate-400 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition whitespace-nowrap"
                        >
                            Semua
                        </button>
                        <button 
                            @click="selectedDept = 'LIVE_ONLY'"
                            :class="selectedDept === 'LIVE_ONLY' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30 border-emerald-400' : 'bg-slate-950 text-emerald-400 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition flex items-center gap-1.5 whitespace-nowrap"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>10-8 Live ({{ deptStats.total_live || 0 }})</span>
                        </button>
                        <button 
                            @click="selectedDept = 'LSPD'"
                            :class="selectedDept === 'LSPD' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/30 border-blue-400' : 'bg-slate-950 text-blue-300 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition whitespace-nowrap"
                        >
                            LSPD ({{ deptStats.lspd_total || 0 }})
                        </button>
                        <button 
                            @click="selectedDept = 'BCSO'"
                            :class="selectedDept === 'BCSO' ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 border-amber-400' : 'bg-slate-950 text-amber-300 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition whitespace-nowrap"
                        >
                            BCSO ({{ deptStats.bcso_total || 0 }})
                        </button>
                        <button 
                            @click="selectedDept = 'SASP'"
                            :class="selectedDept === 'SASP' ? 'bg-teal-600 text-white font-bold shadow-md shadow-teal-600/30 border-teal-400' : 'bg-slate-950 text-teal-300 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition whitespace-nowrap"
                        >
                            SASP ({{ deptStats.sasp_total || 0 }})
                        </button>
                        <button 
                            @click="selectedDept = 'SAPR'"
                            :class="selectedDept === 'SAPR' ? 'bg-green-600 text-white font-bold shadow-md shadow-green-600/30 border-green-400' : 'bg-slate-950 text-green-300 hover:bg-slate-800 border-slate-800'"
                            class="px-3 py-1.5 text-xs rounded-full border transition whitespace-nowrap"
                        >
                            SAPR ({{ deptStats.sapr_total || 0 }})
                        </button>
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="flex items-center gap-2 shrink-0 font-mono">
                        <select 
                            v-model="sortBy" 
                            class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-blue-500"
                        >
                            <option value="status">10-8 Live Teratas</option>
                            <option value="subs_desc">Subscribers Terbanyak</option>
                            <option value="subs_asc">Target 1K Milestone</option>
                            <option value="name">Nama Petugas (A-Z)</option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Officers Cards Grid -->
            <div v-if="filteredOfficers.length === 0" class="py-20 text-center text-slate-500 flex flex-col items-center justify-center bg-slate-900/40 rounded-2xl border border-slate-800/80">
                <img :src="iconSearch" class="w-10 h-10 invert opacity-20 mb-3" alt="" />
                <h3 class="text-sm font-bold text-slate-300">Tidak ada personil yang sesuai</h3>
                <p class="text-xs text-slate-500 mt-1">Coba ubah kata kunci pencarian atau ganti filter kesatuan.</p>
                <button 
                    @click="searchQuery = ''; selectedDept = 'ALL'" 
                    class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition"
                >
                    Reset Filter
                </button>
            </div>

            <!-- Officers Cards Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                    v-for="officer in filteredOfficers" 
                    :key="officer.id"
                    class="bg-slate-900/90 border rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 hover:border-slate-700 hover:shadow-xl group"
                    :class="officer.is_online ? 'border-emerald-500/40' : 'border-slate-800'"
                >
                    <div>
                        <!-- Card Top: Department Badge, Callsign, Status -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center space-x-2 min-w-0">
                                <span 
                                    class="px-2 py-0.5 text-[10px] font-black rounded border font-mono"
                                    :class="getDeptBadgeClass(officer.department)"
                                >
                                    {{ officer.department }}
                                </span>
                                <span class="font-mono text-xs font-bold text-slate-300 truncate">{{ officer.callsign }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">{{ officer.badge_number }}</span>
                            </div>

                            <!-- Status Badge -->
                            <div class="shrink-0">
                                <span 
                                    v-if="officer.is_online" 
                                    class="inline-flex items-center gap-1.5 text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 font-mono font-bold"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
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

                        <!-- Officer Identity Block with YouTube Profile Photo -->
                        <div class="flex items-start gap-3 mt-1.5">
                            <!-- YouTube Profile Avatar (Clean, no distracting pulse/ring overlays) -->
                            <div class="shrink-0">
                                <img 
                                    :src="officer.avatar_url || `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officer.callsign || officer.officer_name || 'officer')}`" 
                                    :alt="officer.officer_name"
                                    @error="(e) => handleAvatarError(e, officer)"
                                    class="w-11 h-11 rounded-full object-cover border border-slate-700/80 shadow-md transition-transform duration-200 group-hover:scale-105"
                                />
                            </div>

                            <!-- Identity Info -->
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold text-slate-100 truncate group-hover:text-blue-300 transition" :title="officer.officer_name">
                                    {{ officer.officer_name }}
                                </h3>

                                <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5 truncate font-mono">
                                    <span class="text-slate-300 font-semibold">{{ officer.rank }}</span>
                                    <template v-if="officer.streamer_name">
                                        <span class="text-slate-600">•</span>
                                        <span class="text-slate-400 truncate">{{ officer.streamer_name }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Subscriber Info & Milestone Progress -->
                        <div class="mt-3 font-mono">
                            <!-- Road to 1K Progress Bar (Only for officers < 1000 subs) -->
                            <div v-if="(officer.subscriber_count || 0) < 1000" class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-2">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="text-slate-400 flex items-center gap-1">
                                        <img :src="iconTarget" class="w-3 h-3 invert opacity-70" alt="" />
                                        <span>Road to 1,000 Subs:</span>
                                    </span>
                                    <span class="font-bold text-slate-300">
                                        {{ officer.subscriber_count ? Number(officer.subscriber_count).toLocaleString('id-ID') : '0' }} / 1.000
                                    </span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div 
                                        class="h-full bg-amber-500 rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Simplified Clean Text for officers >= 1000 subs -->
                            <div v-else class="text-[11px] text-slate-400 flex items-center justify-between px-1">
                                <span>Total Subscribers:</span>
                                <span class="font-bold text-slate-200">{{ Number(officer.subscriber_count || 0).toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer: Minimalist Symmetrical Icon Buttons -->
                    <div class="mt-3.5 pt-2.5 border-t border-slate-800/80 flex items-center justify-between">
                        <div class="text-[10px] font-mono text-slate-500 truncate">
                            <span>Sektor: <strong class="text-slate-400">{{ officer.patrol_zone || 'LS' }}</strong></span>
                        </div>

                        <div class="flex items-center space-x-1.5 shrink-0">
                            <!-- Pin to Personal Watchlist -->
                            <button 
                                @click="togglePin(officer.channel_id || officer.handle)"
                                class="w-8 h-8 rounded-xl border transition flex items-center justify-center"
                                :class="isPinned(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/80 border-purple-500/50' : 'text-slate-400 hover:text-purple-300 bg-slate-950 hover:bg-slate-800 border-slate-800'"
                                :title="isPinned(officer.channel_id || officer.handle) ? 'Hapus dari Personal' : 'Pin ke Personal Tab'"
                            >
                                <img :src="isPinned(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus" class="w-3.5 h-3.5 invert" alt="" />
                            </button>

                            <!-- 1-Click YouTube Subscribe Modal -->
                            <button 
                                @click="openSubscribePopup(officer.channel_id || officer.handle, officer.officer_name)"
                                class="w-8 h-8 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-300 hover:text-white transition-all duration-200 flex items-center justify-center font-mono"
                                title="Subscribe ke YouTube channel"
                            >
                                <span class="text-[10px] font-black">SUB</span>
                            </button>

                            <!-- YouTube Channel External Link -->
                            <a 
                                :href="`https://www.youtube.com/${officer.handle}`" 
                                target="_blank" 
                                class="w-8 h-8 rounded-xl bg-slate-950 border border-slate-800 hover:border-blue-500/50 transition flex items-center justify-center"
                                title="Buka Channel YouTube"
                            >
                                <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="" />
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </TacticalLayout>
</template>
