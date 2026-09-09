<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    initialStreams: {
        type: Array,
        required: true,
    },
    initialOfflineChannels: {
        type: Array,
        required: true,
    },
});

// App State
const streams = ref(props.initialStreams);
const offlineChannels = ref(props.initialOfflineChannels);
const activeTab = ref('online'); // 'online' or 'offline'
const isSidebarOpen = ref(true);
const selectedLayout = ref('auto'); // 'auto', 'grid-2x2', 'grid-3x3', 'focus'
const activeAudioVideoId = ref(null); // ID of stream that is unmuted
const searchFilter = ref('');
const originUrl = ref('');

// Dynamic Clock
const currentTime = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', { hour12: false });
};

// Filtered Streams (Online)
const filteredStreams = computed(() => {
    let result = streams.value;

    if (searchFilter.value.trim() !== '') {
        const query = searchFilter.value.toLowerCase();
        result = result.filter(s => 
            s.title.toLowerCase().includes(query) || 
            s.channel_name.toLowerCase().includes(query)
        );
    }

    return result;
});

// Filtered Offline Channels
const filteredOfflineChannels = computed(() => {
    let result = offlineChannels.value;

    if (searchFilter.value.trim() !== '') {
        const query = searchFilter.value.toLowerCase();
        result = result.filter(c => 
            c.name.toLowerCase().includes(query) || 
            c.handle.toLowerCase().includes(query)
        );
    }

    return result;
});

// Sidebar Selection State for Online Streams
const hiddenStreamVideoIds = ref([]);

const toggleStreamVisibility = (videoId) => {
    const idx = hiddenStreamVideoIds.value.indexOf(videoId);
    if (idx === -1) {
        hiddenStreamVideoIds.value.push(videoId);
    } else {
        hiddenStreamVideoIds.value.splice(idx, 1);
    }
};

const selectAllStreams = () => {
    hiddenStreamVideoIds.value = [];
};

const deselectAllStreams = () => {
    hiddenStreamVideoIds.value = streams.value.map(s => s.video_id);
};

// Visible streams (not hidden by checkboxes)
const visibleStreams = computed(() => {
    return filteredStreams.value.filter(s => !hiddenStreamVideoIds.value.includes(s.video_id));
});

// YouTube Player Instances
const players = {};
const ytApiReady = ref(false);

// Load YouTube IFrame API
const loadYouTubeAPI = () => {
    if (window.YT) {
        ytApiReady.value = true;
        return;
    }

    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    window.onYouTubeIframeAPIReady = () => {
        ytApiReady.value = true;
        initializeAllPlayers();
    };
};

// Initialize Players for each visible stream
const initializeAllPlayers = () => {
    if (!window.YT) return;

    visibleStreams.value.forEach(stream => {
        const playerElementId = `player-${stream.video_id}`;
        if (!players[stream.video_id] && document.getElementById(playerElementId)) {
            players[stream.video_id] = new window.YT.Player(playerElementId, {
                events: {
                    'onReady': (event) => {
                        // Always start muted as per policy and autoplay requirements
                        event.target.mute();
                        event.target.playVideo();
                    },
                    'onStateChange': (event) => {
                        // Handle state changes if needed
                    }
                }
            });
        }
    });
};

// Cleanup players that are no longer visible
const cleanupPlayers = (oldStreams, newStreams) => {
    const newIds = new Set(newStreams.map(s => s.video_id));
    Object.keys(players).forEach(videoId => {
        if (!newIds.has(videoId)) {
            try {
                if (activeAudioVideoId.value === videoId) {
                    activeAudioVideoId.value = null;
                }
                players[videoId].destroy();
            } catch (e) {
                console.error("Error destroying player:", e);
            }
            delete players[videoId];
        }
    });
};

// Unmute a single player and mute all others
const unmuteStream = (videoId) => {
    Object.keys(players).forEach(id => {
        const player = players[id];
        if (player && typeof player.mute === 'function') {
            if (id === videoId) {
                player.unMute();
                player.setVolume(100);
                activeAudioVideoId.value = videoId;
            } else {
                player.mute();
            }
        }
    });
};

// Mute all streams
const muteAll = () => {
    Object.keys(players).forEach(id => {
        const player = players[id];
        if (player && typeof player.mute === 'function') {
            player.mute();
        }
    });
    activeAudioVideoId.value = null;
};

// Grid class generator based on selection
const gridClasses = computed(() => {
    if (selectedLayout.value === 'grid-2x2') {
        return 'grid grid-cols-1 md:grid-cols-2 gap-4';
    }
    if (selectedLayout.value === 'grid-3x3') {
        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4';
    }
    if (selectedLayout.value === 'focus') {
        return 'flex flex-col lg:flex-row gap-4';
    }
    // Auto layout (default)
    const count = visibleStreams.value.length;
    if (count <= 1) return 'grid grid-cols-1 gap-4';
    if (count === 2) return 'grid grid-cols-1 md:grid-cols-2 gap-4';
    if (count <= 4) return 'grid grid-cols-1 md:grid-cols-2 gap-4';
    return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4';
});

// Watch for changes in visible streams list
watch(visibleStreams, (newVal, oldVal) => {
    // Wait for DOM update
    setTimeout(() => {
        initializeAllPlayers();
        cleanupPlayers(oldVal || [], newVal || []);
    }, 100);
}, { deep: true });

// Sync refs when props update from router.reload
watch(() => props.initialStreams, (newVal) => {
    streams.value = newVal;
}, { deep: true });

watch(() => props.initialOfflineChannels, (newVal) => {
    offlineChannels.value = newVal;
}, { deep: true });

// Lifecycle Hooks
onMounted(() => {
    originUrl.value = typeof window !== 'undefined' ? window.location.origin : '';
    loadYouTubeAPI();
    updateTime();
    setInterval(updateTime, 1000);

    // Periodically reload Inertia props (online & offline lists)
    setInterval(() => {
        router.reload({ only: ['initialStreams', 'initialOfflineChannels'] });
    }, 60000); // Reload every 60 seconds
});
</script>

<template>
    <Head title="Control Room - YouTube Multiview" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-indigo-500 selection:text-white">
        
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md">
            <div class="w-full px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="relative flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 shadow-lg shadow-indigo-500/30">
                        <svg class="h-6 w-6 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12l-6.75 3.75v-7.5L15.75 12z" />
                        </svg>
                        <div class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-red-500"></div>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-white sm:text-xl">OPJ <span class="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Multiview</span></h1>
                        <p class="text-xs text-zinc-400">Automated Live Aggregator</p>
                    </div>
                </div>

                <!-- Clock / Stats / Global Mute -->
                <div class="flex items-center flex-wrap gap-4 sm:gap-6">
                    <!-- Timer / Live Indicator -->
                    <div class="flex items-center gap-2 rounded-full border border-zinc-800 bg-zinc-900/40 px-3 py-1 text-xs">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        </span>
                        <span class="font-mono text-zinc-300">{{ currentTime }}</span>
                    </div>

                    <!-- Counter -->
                    <div class="text-xs text-zinc-400">
                        Active streams: <span class="font-bold text-white">{{ visibleStreams.length }}</span>
                    </div>

                    <!-- Mute All -->
                    <button 
                        @click="muteAll" 
                        class="flex items-center gap-2 rounded-lg bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 px-3 py-1.5 text-xs text-zinc-300 transition"
                        title="Mute all streams"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6L4.5 9H1.5v6h3l4.5 3.75V5.25z" />
                        </svg>
                        Mute All
                    </button>
                </div>

            </div>
        </header>

        <!-- Main Body -->
        <main class="w-full px-4 py-6 sm:px-6 lg:px-8">
            
            <!-- Controls & Filters Panel -->
            <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4 rounded-xl border border-zinc-900 bg-zinc-900/30 p-4">
                
                <!-- Status Tabs (Online / Offline) -->
                <div class="flex items-center gap-2 w-full md:w-auto pb-2 md:pb-0">
                    <button
                        @click="activeTab = 'online'"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-2 border',
                            activeTab === 'online' 
                                ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20' 
                                : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-800'
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Online ({{ filteredStreams.length }})
                    </button>
                    <button
                        @click="activeTab = 'offline'"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-2 border',
                            activeTab === 'offline' 
                                ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20' 
                                : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-800'
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-500"></span>
                        Offline ({{ filteredOfflineChannels.length }})
                    </button>

                    <!-- Sidebar Toggle Button -->
                    <button
                        v-if="activeTab === 'online' && filteredStreams.length > 0"
                        @click="isSidebarOpen = !isSidebarOpen"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border',
                            isSidebarOpen 
                                ? 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' 
                                : 'bg-indigo-600/20 border-indigo-500/30 text-indigo-400 hover:text-indigo-300 hover:bg-indigo-600/30'
                        ]"
                        title="Toggle Sidebar"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v18" />
                        </svg>
                        <span>{{ isSidebarOpen ? 'Sembunyikan Menu' : 'Tampilkan Menu' }}</span>
                    </button>
                </div>

                <!-- Right Controls: Layout & Search -->
                <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                    
                    <!-- Search Input -->
                    <div class="relative w-48 sm:w-60">
                        <input
                            v-model="searchFilter"
                            type="text"
                            :placeholder="activeTab === 'online' ? 'Filter streams...' : 'Filter channels...'"
                            class="w-full rounded-lg bg-zinc-900 border border-zinc-800 px-3 py-1.5 pl-8 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                        <svg class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Layout Presets Selector (Only visible for Online Streams) -->
                    <div v-if="activeTab === 'online'" class="flex items-center rounded-lg border border-zinc-800 bg-zinc-900 p-0.5">
                        <button
                            @click="selectedLayout = 'auto'"
                            :class="['p-1.5 rounded-md transition', selectedLayout === 'auto' ? 'bg-indigo-600 text-white' : 'text-zinc-400 hover:text-white']"
                            title="Auto Fit Grid"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </button>
                        <button
                            @click="selectedLayout = 'grid-2x2'"
                            :class="['p-1.5 rounded-md transition', selectedLayout === 'grid-2x2' ? 'bg-indigo-600 text-white' : 'text-zinc-400 hover:text-white']"
                            title="2x2 Layout"
                        >
                            <span class="font-mono text-xs font-bold px-0.5">2x2</span>
                        </button>
                        <button
                            @click="selectedLayout = 'grid-3x3'"
                            :class="['p-1.5 rounded-md transition', selectedLayout === 'grid-3x3' ? 'bg-indigo-600 text-white' : 'text-zinc-400 hover:text-white']"
                            title="3x3 Layout"
                        >
                            <span class="font-mono text-xs font-bold px-0.5">3x3</span>
                        </button>
                        <button
                            @click="selectedLayout = 'focus'"
                            :class="['p-1.5 rounded-md transition', selectedLayout === 'focus' ? 'bg-indigo-600 text-white' : 'text-zinc-400 hover:text-white']"
                            title="Focus Layout (1 Large, rest mini)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v16.5m16.5-16.5v16.5m-16.5-16.5h16.5m-16.5 16.5h16.5M9 9h6v6H9V9z" />
                            </svg>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Online Tab View -->
            <div v-if="activeTab === 'online'">
                <!-- Empty State -->
                <div v-if="filteredStreams.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/10 py-16 px-4 text-center">
                    <div class="rounded-full bg-zinc-900 border border-zinc-800 p-4 text-zinc-500 mb-4 animate-bounce">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">No Live Streams Active</h3>
                    <p class="mt-1 text-sm text-zinc-500 max-w-sm">No channels are currently live streaming. We sync status automatically.</p>
                </div>

                <!-- Main Content Area with Sidebar -->
                <div v-else class="flex flex-col lg:flex-row gap-6">
                    
                    <!-- Sidebar Left: Channel Selection Panel -->
                    <aside v-if="isSidebarOpen" class="w-full lg:w-64 xl:w-72 flex-shrink-0 flex flex-col gap-4 self-start bg-zinc-900/30 border border-zinc-900 p-4 rounded-xl shadow-xl">
                        <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Filter Channel</h3>
                            <span class="rounded bg-indigo-500/10 px-2 py-0.5 text-[10px] font-bold text-indigo-400 font-mono">
                                {{ visibleStreams.length }}/{{ filteredStreams.length }}
                            </span>
                        </div>

                        <!-- Bulk Actions -->
                        <div class="flex items-center justify-between gap-2 text-[10px]">
                            <button 
                                @click="selectAllStreams" 
                                class="text-indigo-400 hover:text-indigo-300 font-bold transition"
                            >
                                Tampilkan Semua
                            </button>
                            <span class="text-zinc-850">|</span>
                            <button 
                                @click="deselectAllStreams" 
                                class="text-zinc-500 hover:text-zinc-400 font-bold transition"
                            >
                                Sembunyikan Semua
                            </button>
                        </div>

                        <!-- Channels List -->
                        <div class="flex flex-col gap-1.5 max-h-[55vh] overflow-y-auto pr-1">
                            <label 
                                v-for="stream in filteredStreams" 
                                :key="stream.video_id"
                                class="flex items-center gap-3 rounded-lg px-2.5 py-2 hover:bg-zinc-800/20 cursor-pointer transition select-none group border border-transparent hover:border-zinc-800"
                            >
                                <input 
                                    type="checkbox" 
                                    :checked="!hiddenStreamVideoIds.includes(stream.video_id)"
                                    @change="toggleStreamVisibility(stream.video_id)"
                                    class="rounded bg-zinc-950 border-zinc-800 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-zinc-950 h-3.5 w-3.5 transition"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-zinc-300 truncate group-hover:text-white transition">
                                        {{ stream.channel_name }}
                                    </p>
                                    <p class="text-[9px] text-zinc-500 truncate group-hover:text-zinc-400 transition mt-0.5">
                                        {{ stream.title }}
                                    </p>
                                </div>
                                <span class="relative flex h-1.5 w-1.5 flex-shrink-0">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                </span>
                            </label>
                        </div>
                    </aside>

                    <!-- Right Column: Video Grid -->
                    <div class="flex-1 min-w-0">
                        
                        <!-- Empty Grid State (All Hidden) -->
                        <div v-if="visibleStreams.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/10 py-16 px-4 text-center">
                            <div class="rounded-full bg-zinc-900 border border-zinc-800 p-4 text-zinc-500 mb-4">
                                <svg class="h-10 w-10 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.822 7.822L21 21m-2.228-2.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-white">Semua Streaming Ditutup</h3>
                            <p class="mt-1 text-sm text-zinc-500 max-w-sm">Pilih channel di bagian kiri untuk menampilkan kembali video streaming.</p>
                        </div>

                        <!-- Video Grid Displays -->
                        <div v-else>
                            <!-- standard Layout Grids -->
                            <div v-if="selectedLayout !== 'focus'" :class="gridClasses">
                                <div 
                                    v-for="stream in visibleStreams" 
                                    :key="stream.video_id"
                                    class="group relative overflow-hidden rounded-xl border border-zinc-900 bg-zinc-900/40 p-1 flex flex-col transition hover:border-zinc-800 hover:shadow-xl hover:shadow-indigo-500/5"
                                >
                                    <!-- Video Frame Box -->
                                    <div class="relative w-full aspect-video rounded-lg overflow-hidden bg-black">
                                        <iframe
                                            :id="`player-${stream.video_id}`"
                                            :src="`https://www.youtube.com/embed/${stream.video_id}?autoplay=1&mute=1&enablejsapi=1&origin=${originUrl}`"
                                            class="w-full h-full border-0 absolute inset-0"
                                            allow="autoplay; encrypted-media"
                                            allowfullscreen
                                        ></iframe>

                                        <!-- Equalizer Visualizer Overlay when Unmuted -->
                                        <div 
                                            v-if="activeAudioVideoId === stream.video_id" 
                                            class="absolute top-3 right-3 flex items-end gap-0.5 bg-black/60 backdrop-blur-md px-2 py-1.5 rounded-lg border border-indigo-500/30"
                                        >
                                            <span class="h-3 w-1 bg-indigo-500 animate-[bounce_0.8s_infinite]"></span>
                                            <span class="h-4 w-1 bg-indigo-400 animate-[bounce_0.5s_infinite]"></span>
                                            <span class="h-2 w-1 bg-indigo-500 animate-[bounce_0.7s_infinite]"></span>
                                            <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest ml-1.5 font-mono">Audio Active</span>
                                        </div>
                                    </div>

                                    <!-- Card Footer Panel -->
                                    <div class="p-3 flex items-center justify-between gap-3">
                                        <div class="overflow-hidden min-w-0">
                                            <h4 class="text-xs font-semibold text-zinc-100 truncate group-hover:text-white">{{ stream.title }}</h4>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold text-zinc-400 truncate">{{ stream.channel_name }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Audio Control Button -->
                                        <button
                                            @click="unmuteStream(stream.video_id)"
                                            :class="[
                                                'flex-shrink-0 flex h-8 w-8 items-center justify-center rounded-lg border transition',
                                                activeAudioVideoId === stream.video_id 
                                                    ? 'bg-indigo-600 border-indigo-500 text-white shadow-md shadow-indigo-500/20' 
                                                    : 'bg-zinc-800 border-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700'
                                            ]"
                                            :title="activeAudioVideoId === stream.video_id ? 'Muted (Audio is playing)' : 'Unmute / Listen to stream'"
                                        >
                                            <svg v-if="activeAudioVideoId === stream.video_id" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                                            </svg>
                                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l-2.25-2.25M19.5 12l2.25 2.25m-10.5-6L4.5 9H1.5v6h3l4.5 3.75V5.25z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Focus Layout Mode (1 Large Left, List Right) -->
                            <div v-else :class="gridClasses">
                                <!-- Big Focus Video -->
                                <div class="flex-1 lg:max-w-[70%] flex flex-col gap-2 bg-zinc-900/40 p-2 rounded-2xl border border-zinc-900">
                                    <div class="w-full aspect-video rounded-xl overflow-hidden bg-black relative">
                                        <template v-if="visibleStreams.length > 0">
                                            <iframe
                                                :id="`player-${visibleStreams[0].video_id}`"
                                                :src="`https://www.youtube.com/embed/${visibleStreams[0].video_id}?autoplay=1&mute=1&enablejsapi=1&origin=${originUrl}`"
                                                class="w-full h-full border-0 absolute inset-0"
                                                allow="autoplay; encrypted-media"
                                                allowfullscreen
                                            ></iframe>

                                            <div 
                                                v-if="activeAudioVideoId === visibleStreams[0].video_id" 
                                                class="absolute top-4 right-4 flex items-end gap-0.5 bg-black/60 backdrop-blur-md px-2.5 py-1.5 rounded-lg border border-indigo-500/30"
                                            >
                                                <span class="h-3 w-1 bg-indigo-500 animate-[bounce_0.8s_infinite]"></span>
                                                <span class="h-4 w-1 bg-indigo-400 animate-[bounce_0.5s_infinite]"></span>
                                                <span class="h-2 w-1 bg-indigo-500 animate-[bounce_0.7s_infinite]"></span>
                                                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest ml-1.5 font-mono">Audio Active</span>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <div class="p-4 flex items-center justify-between gap-4">
                                        <div>
                                            <h3 class="text-base font-semibold text-white">{{ visibleStreams[0].title }}</h3>
                                            <p class="text-xs text-zinc-400 mt-0.5">{{ visibleStreams[0].channel_name }}</p>
                                        </div>
                                        <button
                                            @click="unmuteStream(visibleStreams[0].video_id)"
                                            :class="[
                                                'flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-xl border transition',
                                                activeAudioVideoId === visibleStreams[0].video_id 
                                                    ? 'bg-indigo-600 border-indigo-500 text-white shadow-md shadow-indigo-500/20' 
                                                    : 'bg-zinc-800 border-zinc-800 text-zinc-400 hover:text-white'
                                            ]"
                                        >
                                            <svg v-if="activeAudioVideoId === visibleStreams[0].video_id" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                                            </svg>
                                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l-2.25-2.25M19.5 12l2.25 2.25" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Right Column Thumbnail Feed -->
                                <div class="flex-1 lg:max-w-[30%] flex flex-col gap-3 overflow-y-auto max-h-[75vh] pr-1">
                                    <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 border-b border-zinc-900 pb-2">Other Streams</h4>
                                    
                                    <div 
                                        v-for="(stream, idx) in visibleStreams.slice(1)" 
                                        :key="stream.video_id"
                                        class="group relative overflow-hidden rounded-xl border border-zinc-900 bg-zinc-900/40 p-1 flex flex-col transition hover:border-zinc-800"
                                    >
                                        <!-- Mini Video Frame -->
                                        <div class="relative w-full aspect-video rounded-lg overflow-hidden bg-black">
                                            <iframe
                                                :id="`player-${stream.video_id}`"
                                                :src="`https://www.youtube.com/embed/${stream.video_id}?autoplay=1&mute=1&enablejsapi=1&origin=${originUrl}`"
                                                class="w-full h-full border-0 absolute inset-0"
                                                allow="autoplay; encrypted-media"
                                                allowfullscreen
                                            ></iframe>

                                            <div 
                                                v-if="activeAudioVideoId === stream.video_id" 
                                                class="absolute top-2 right-2 flex items-end gap-0.5 bg-black/60 backdrop-blur-md px-1.5 py-1 rounded border border-indigo-500/30"
                                            >
                                                <span class="h-2 w-0.5 bg-indigo-500 animate-[bounce_0.8s_infinite]"></span>
                                                <span class="h-3 w-0.5 bg-indigo-400 animate-[bounce_0.5s_infinite]"></span>
                                                <span class="h-1.5 w-0.5 bg-indigo-500 animate-[bounce_0.7s_infinite]"></span>
                                            </div>
                                        </div>

                                        <!-- Mini Info -->
                                        <div class="p-2 flex items-center justify-between gap-2">
                                            <div class="overflow-hidden min-w-0">
                                                <h5 class="text-[11px] font-semibold text-zinc-200 truncate">{{ stream.title }}</h5>
                                                <p class="text-[9px] text-zinc-500 truncate mt-0.5">{{ stream.channel_name }}</p>
                                            </div>
                                            
                                            <button
                                                @click="unmuteStream(stream.video_id)"
                                                :class="[
                                                    'flex-shrink-0 flex h-6 w-6 items-center justify-center rounded-md border transition',
                                                    activeAudioVideoId === stream.video_id 
                                                        ? 'bg-indigo-600 border-indigo-500 text-white' 
                                                        : 'bg-zinc-800 border-zinc-800 text-zinc-400 hover:text-white'
                                                ]"
                                            >
                                                <svg v-if="activeAudioVideoId === stream.video_id" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                                                </svg>
                                                <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l-2.25-2.25M19.5 12l2.25 2.25" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Offline Tab View -->
            <div v-else-if="activeTab === 'offline'">
                <!-- Empty State -->
                <div v-if="filteredOfflineChannels.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/10 py-16 px-4 text-center">
                    <div class="rounded-full bg-zinc-900 border border-zinc-800 p-4 text-zinc-500 mb-4">
                        <svg class="h-10 w-10 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">All Channels are Online</h3>
                    <p class="mt-1 text-sm text-zinc-500 max-w-sm">Every single registered channel is currently broadcasting live stream!</p>
                </div>

                <!-- Offline Channel Grid -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="channel in filteredOfflineChannels" 
                        :key="channel.channel_id"
                        class="rounded-xl border border-zinc-900 bg-zinc-900/20 p-4 flex flex-col items-center justify-between text-center gap-4 transition hover:border-zinc-800 hover:bg-zinc-900/40"
                    >
                        <!-- Channel Avatar Placeholder -->
                        <div class="h-12 w-12 rounded-full bg-zinc-800/80 border border-zinc-700/50 flex items-center justify-center text-zinc-400 text-sm font-bold shadow-inner">
                            {{ channel.name.charAt(0).toUpperCase() }}
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-semibold text-zinc-200">{{ channel.name }}</h4>
                            <p class="text-[10px] text-zinc-500 mt-0.5">{{ channel.handle }}</p>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-zinc-900/60 px-2 py-0.5 text-[9px] font-bold text-zinc-500 border border-zinc-800 uppercase tracking-wider font-mono">
                                Offline
                            </span>
                            <a 
                                :href="`https://www.youtube.com/${channel.handle}`" 
                                target="_blank"
                                class="text-[10px] font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition"
                            >
                                Channel Link
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </main>
        
        <!-- Footer -->
        <footer class="mt-auto border-t border-zinc-900 py-6 text-center text-xs text-zinc-500">
            <p>&copy; 2026 OPJ Multiview Platform. Premium Control Room Interface.</p>
        </footer>

    </div>
</template>

<style>
/* Smooth Bounce customization for Equalizer Animation */
@keyframes bounce {
    0%, 100% {
        transform: scaleY(0.3);
    }
    50% {
        transform: scaleY(1);
    }
}
</style>
