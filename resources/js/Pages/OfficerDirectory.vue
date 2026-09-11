<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

// SVG Icon Assets & Branding Logos
import logoSaspColor from '@/Components/Icons/SASP256.jpg';
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

// 1-Click Subscribe Popup Modal
const openSubscribePopup = (channelIdOrHandle, officerName = '') => {
    if (!channelIdOrHandle) return;
    let subUrl = '';
    if (channelIdOrHandle.startsWith('UC')) {
        subUrl = `https://www.youtube.com/channel/${channelIdOrHandle}?sub_confirmation=1`;
    } else {
        const cleanHandle = channelIdOrHandle.startsWith('@') ? channelIdOrHandle : `@${channelIdOrHandle}`;
        subUrl = `https://www.youtube.com/${cleanHandle}?sub_confirmation=1`;
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
    <Head title="Direktori Petugas & Streamer - IME Police Command Center" />

    <div class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased">
        
        <!-- Tactical Header Bar -->
        <header class="bg-[#0b1320] border-b border-blue-900/40 px-4 py-2 flex items-center justify-between gap-3 sticky top-0 z-40 shadow-xl backdrop-blur-md">
            
            <!-- Left Area: Branding & Standalone Page Navigation Links -->
            <div class="flex items-center space-x-3 shrink-0">
                <!-- Branding: IME Roleplay Police Division -->
                <Link href="/" class="flex items-center space-x-2.5 shrink-0 group">
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-950/50 via-slate-900 to-slate-950 border border-blue-500/40 shadow-inner p-1 overflow-hidden group-hover:border-blue-400 transition">
                        <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-black tracking-wider text-blue-400 uppercase leading-tight group-hover:text-blue-300 transition">IME ROLEPLAY</span>
                        <span class="text-[10px] font-bold tracking-wide text-slate-300 uppercase leading-tight">POLICE DIVISION</span>
                    </div>
                </Link>

                <!-- Vertical Divider -->
                <div class="h-6 w-px bg-slate-800/80 hidden md:block"></div>

                <!-- Page Navigation Links (Clean Minimalist Text Tabs) -->
                <nav class="hidden md:flex items-center space-x-1">
                    <Link 
                        href="/officers"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600/30 text-blue-300 border border-blue-500/50 shadow-sm transition"
                        title="Active Page: Officer Directory (LSPD, BCSO, SASP)"
                    >
                        Officer Directory
                    </Link>
                    
                    <!-- Perlu dilakukan penyesuaian tampilan untuk radio-codes, about, dan feedback -->
                    <!-- <Link 
                        href="/radio-codes"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="10-Codes & Tactical Radio Channels (TAC 1-10)"
                    >
                        10-Codes & Radio
                    </Link>

                    <Link 
                        href="/about"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition"
                        title="About Police Command Center"
                    >
                        About Platform
                    </Link> -->

                    <Link 
                        href="/feedback"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-sky-300 hover:bg-sky-950/40 transition"
                        title="Channel Requests & System Feedback"
                    >
                        Feedback & Reports
                    </Link>
                </nav>
            </div>

            <!-- Right Controls: Return to Multiview & Tools -->
            <div class="flex items-center space-x-2">
                <button 
                    @click="triggerSync" 
                    :disabled="isSyncing"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700/80 transition flex items-center gap-1.5 disabled:opacity-50"
                    title="Sinkronkan status siaran langsung dari YouTube"
                >
                    <img :src="iconRefresh" class="w-3.5 h-3.5 invert opacity-90" :class="{ 'animate-spin': isSyncing }" alt="Sync" />
                    <span class="hidden sm:inline">{{ isSyncing ? 'Syncing...' : 'Sync' }}</span>
                </button>

                <button 
                    @click="toggleFullscreen"
                    class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-700 bg-slate-900 text-slate-300 hover:bg-slate-800 transition flex items-center gap-1.5"
                    title="Toggle Fullscreen"
                >
                    <img :src="isFullscreen ? iconExitFullscreen : iconFullscreen" class="w-3.5 h-3.5 invert opacity-90" alt="Fullscreen" />
                    <span class="hidden md:inline">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
                </button>

                <Link 
                    href="/" 
                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-600/30 transition flex items-center gap-1.5"
                    title="Kembali ke Halaman Utama CCTV Multiview"
                >
                    <span>Multiview</span>
                </Link>
            </div>
        </header>

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
                <div class="relative flex-1 max-w-md">
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Cari nama polisi, callsign, handle YouTube, badge, sektor..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 font-mono"
                    />
                    <img :src="iconSearch" class="w-4 h-4 invert opacity-40 absolute left-3 top-3" alt="" />
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
                    <div class="flex items-center gap-1.5 shrink-0 font-mono">
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

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                    v-for="officer in filteredOfficers" 
                    :key="officer.id"
                    class="bg-slate-900/90 border rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 hover:border-slate-700 hover:shadow-xl group"
                    :class="officer.is_online ? 'border-emerald-500/40 shadow-md shadow-emerald-500/5' : 'border-slate-800'"
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
                                    class="inline-flex items-center gap-1.5 text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/40 font-mono font-bold shadow-sm"
                                >
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
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

                        <!-- Officer Identity -->
                        <h3 class="text-base font-bold text-slate-100 truncate group-hover:text-blue-300 transition" :title="officer.officer_name">
                            {{ officer.officer_name }}
                        </h3>

                        <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5 truncate font-mono">
                            <span class="text-slate-300 font-semibold">{{ officer.rank }}</span>
                            <span>•</span>
                            <span class="text-blue-400">{{ officer.handle }}</span>
                            <span v-if="officer.streamer_name" class="text-slate-500 truncate">({{ officer.streamer_name }})</span>
                        </div>

                        <div class="text-[11px] font-mono text-slate-500 truncate mt-1">
                            📍 {{ officer.patrol_zone || 'Los Santos Sector' }}
                        </div>

                        <!-- Milestone Progress (Towards 1K Subs) -->
                        <div class="mt-3.5 bg-slate-950/80 border border-slate-800/80 rounded-xl p-2.5">
                            <div class="flex items-center justify-between text-[11px] font-mono mb-1.5">
                                <span class="text-slate-400 flex items-center gap-1">
                                    <img :src="iconTarget" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                    <span>{{ (officer.subscriber_count || 0) < 1000 ? 'Road to 1,000 Subs' : 'Total Subscribers' }}:</span>
                                </span>
                                <span class="font-bold text-slate-200">
                                    {{ officer.subscriber_count ? Number(officer.subscriber_count).toLocaleString('id-ID') : '0' }} / 1.000
                                </span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="(officer.subscriber_count || 0) >= 1000 ? 'bg-emerald-500' : 'bg-red-500'"
                                    :style="{ width: `${Math.min(100, Math.round(((officer.subscriber_count || 0) / 1000) * 100))}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-1.5">
                            <!-- Pin to Personal Watchlist -->
                            <button 
                                @click="togglePin(officer.channel_id || officer.handle)"
                                class="p-2 rounded-xl border transition text-xs flex items-center gap-1"
                                :class="isPinned(officer.channel_id || officer.handle) ? 'text-purple-300 bg-purple-950/80 border-purple-500/50' : 'text-slate-400 hover:text-purple-300 bg-slate-950 hover:bg-slate-800 border-slate-800'"
                                :title="isPinned(officer.channel_id || officer.handle) ? 'Hapus dari Personal' : 'Pin ke Personal Tab'"
                            >
                                <img :src="isPinned(officer.channel_id || officer.handle) ? iconPinMinus : iconPinPlus" class="w-3.5 h-3.5 invert" alt="" />
                            </button>

                            <!-- 1-Click YouTube Subscribe Modal -->
                            <button 
                                @click="openSubscribePopup(officer.channel_id || officer.handle, officer.officer_name)"
                                class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-sm shadow-red-600/30 transition flex items-center gap-1.5"
                                title="Subscribe ke YouTube channel"
                            >
                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                <span>Subscribe</span>
                            </button>
                        </div>

                        <!-- Live Action or Channel Link -->
                        <div>
                            <Link 
                                v-if="officer.is_online && officer.live_video_id"
                                href="/" 
                                class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/30 transition flex items-center gap-1.5"
                            >
                                <img :src="iconFocus" class="w-3.5 h-3.5 invert" alt="" />
                                <span>Tonton Multiview</span>
                            </Link>
                            <a 
                                v-else
                                :href="`https://www.youtube.com/${officer.handle}`" 
                                target="_blank" 
                                class="text-xs text-blue-400 hover:underline flex items-center gap-1 font-mono px-2.5 py-1.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-blue-500/40 transition"
                            >
                                <span>Channel</span>
                                <img :src="iconExternal" class="w-3 h-3 invert opacity-70" alt="" />
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- TACTICAL COMMAND CENTER FOOTER (4 COLUMNS) -->
        <footer class="mt-20 bg-[#080d18]/95 border-t border-blue-900/40 text-slate-400 text-xs backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    
                    <!-- Column 1: Branding & Live Status -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-950/70 via-slate-900 to-slate-950 border border-blue-500/40 p-1 shadow-inner overflow-hidden shrink-0">
                                <img :src="logoSaspColor" class="w-full h-full object-contain rounded" alt="SASP Badge" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black tracking-wider text-blue-400 uppercase font-mono leading-tight">IME ROLEPLAY</h3>
                                <p class="text-[11px] font-bold text-slate-300 tracking-wide uppercase leading-tight">POLICE COMMAND CENTER</p>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Dashboard pemantauan multiview taktis siaran langsung seluruh petugas kepolisian (LSPD, BCSO, SASP) terdaftar di server GTA V IME Roleplay.
                        </p>
                        <div class="flex items-center space-x-2 pt-1 font-mono text-[11px]">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Multi-Sync Live</span>
                            </span>
                            <span class="text-slate-500">v2.4 Pro</span>
                        </div>
                    </div>

                    <!-- Column 2: Quick Tactical Navigation -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Navigasi Taktis</span>
                        </h4>
                        <ul class="space-y-2 text-[11px]">
                            <li>
                                <Link href="/" class="hover:text-blue-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span>›</span>
                                    <span>CCTV Multiview & Cinema Hub</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/officers" class="text-blue-400 font-semibold flex items-center gap-1.5">
                                    <span>›</span>
                                    <span>Direktori Petugas ({{ officers.length }} Personil)</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/radio-codes" class="hover:text-amber-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span>›</span>
                                    <span>Panduan Kode 10 & Radio TAC 1–5</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="/about" class="hover:text-indigo-300 transition flex items-center gap-1.5 text-slate-300">
                                    <span>›</span>
                                    <span>Tentang Command Center Platform</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Police Departments & Milestone -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Kesatuan Wilayah</span>
                        </h4>
                        <ul class="space-y-2 text-[11px]">
                            <li>
                                <button @click="selectedDept = 'LSPD'" class="hover:text-blue-300 transition flex items-center justify-between w-full text-slate-300">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>LSPD - Mission Row HQ</span>
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-500">{{ deptStats.lspd_total || 0 }} Personil</span>
                                </button>
                            </li>
                            <li>
                                <button @click="selectedDept = 'BCSO'" class="hover:text-amber-300 transition flex items-center justify-between w-full text-slate-300">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span>BCSO - Paleto & Sandy Shores</span>
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-500">{{ deptStats.bcso_total || 0 }} Personil</span>
                                </button>
                            </li>
                            <li>
                                <button @click="selectedDept = 'SASP'" class="hover:text-teal-300 transition flex items-center justify-between w-full text-slate-300">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                        <span>SASP - State Troopers</span>
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-500">{{ deptStats.sasp_total || 0 }} Personil</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Community & Dispatcher Support -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400 font-mono flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Dispatcher & Bantuan</span>
                        </h4>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Punya streamer polisi baru atau ingin update callsign dan pangkat dinas? Laporkan langsung ke tim dispatcher.
                        </p>
                        <div class="flex flex-col gap-2 pt-1">
                            <Link 
                                href="/feedback" 
                                class="w-full py-2 px-3 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 transition flex items-center justify-center gap-2"
                            >
                                <img :src="iconFeedback" class="w-3.5 h-3.5 invert" alt="" />
                                <span>Kirim Laporan Dispatcher</span>
                            </Link>
                            <a 
                                href="https://discord.gg/imeroleplay" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="w-full py-2 px-3 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-xs rounded-xl border border-slate-800 transition flex items-center justify-center gap-2"
                            >
                                <span>Discord IME Roleplay</span>
                                <img :src="iconExternal" class="w-3 h-3 invert opacity-70" alt="" />
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Bottom Copyright -->
                <div class="mt-10 pt-6 border-t border-slate-800/80 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-slate-500 font-mono">
                    <div class="flex items-center gap-2 text-center md:text-left">
                        <span>© 2026 IME Roleplay Police Command Center.</span>
                        <span class="hidden sm:inline">•</span>
                        <span class="hidden sm:inline text-slate-400">Tactical Multiview System</span>
                    </div>
                    <div class="text-center md:text-right text-[10px] text-slate-500">
                        GTA V / FiveM community fan project. All video feeds belong to their respective YouTube creators.
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
