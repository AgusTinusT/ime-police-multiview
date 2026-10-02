<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    officer: {
        type: Object,
        default: () => ({}),
    },
    stream: {
        type: Object,
        default: () => ({}),
    },
    customClips: {
        type: Array,
        default: null,
    },
});

const emit = defineEmits(["open-create-clip", "play-clip", "delete-clip"]);

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

// Initial community action clips fallback (Empty list, no mock/sample clips)
const defaultClips = ref([]);

const clips = computed(() => {
    return props.customClips !== null ? props.customClips : defaultClips.value;
});

// Active Filter Scope: 'officer' (Semua Klip Officer) vs 'stream' (Siaran Ini)
const clipScopeFilter = ref("officer");

// Search Filter
const searchQuery = ref("");

// Pagination / Lazy Loading State
const visibleCount = ref(12);

const loadMore = () => {
    visibleCount.value += 12;
};

// Reset pagination when filter or search changes
watch([clipScopeFilter, searchQuery], () => {
    visibleCount.value = 12;
});

const currentVideoId = computed(() => {
    return (props.stream?.video_id || props.officer?.video_id || "").toString().trim();
});

const currentOfficerId = computed(() => {
    return (props.officer?.id || props.officer?.officer_id || props.stream?.officer?.id || props.stream?.officer_id || "").toString().trim();
});

const currentOfficerName = computed(() => {
    return (props.officer?.officer_name || props.officer?.name || props.stream?.officer?.officer_name || props.stream?.officer?.name || "").toString().trim().toLowerCase();
});

const currentOfficerHandle = computed(() => {
    const h = (props.officer?.handle || props.stream?.officer?.handle || "").toString().trim().toLowerCase();
    return h.startsWith("@") ? h.slice(1) : h;
});

const isClipMatchingOfficer = (c) => {
    if (currentVideoId.value && c.video_id && String(c.video_id).trim() === currentVideoId.value) {
        return true;
    }
    if (currentOfficerId.value && c.officer_id && String(c.officer_id).trim() === currentOfficerId.value) {
        return true;
    }
    if (currentOfficerName.value && c.officer_name && String(c.officer_name).trim().toLowerCase() === currentOfficerName.value) {
        return true;
    }
    if (currentOfficerHandle.value && c.officer_handle && String(c.officer_handle).trim().toLowerCase().replace(/^@/, '') === currentOfficerHandle.value) {
        return true;
    }
    // Fallback: if clip has no specific video_id or officer_id attached
    if (!c.video_id && !c.officer_id) {
        return true;
    }
    return false;
};

const isClipMatchingStream = (c) => {
    return !!(currentVideoId.value && c.video_id && String(c.video_id).trim() === currentVideoId.value);
};

const streamClipsCount = computed(() => {
    if (!currentVideoId.value) return 0;
    return clips.value.filter(isClipMatchingStream).length;
});

const officerClipsCount = computed(() => {
    return clips.value.filter(isClipMatchingOfficer).length;
});

const filteredClips = computed(() => {
    let list = clips.value;

    if (clipScopeFilter.value === "stream") {
        list = list.filter(isClipMatchingStream);
    } else {
        list = list.filter(isClipMatchingOfficer);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (c) =>
                (c.title || "").toLowerCase().includes(q) ||
                (c.creator_name || "").toLowerCase().includes(q)
        );
    }

    return list;
});

const paginatedClips = computed(() => {
    return filteredClips.value.slice(0, visibleCount.value);
});

const hasMoreClips = computed(() => {
    return visibleCount.value < filteredClips.value.length;
});

// IntersectionObserver for automatic lazy loading on scroll
const sentinelRef = ref(null);
let observer = null;

onMounted(() => {
    if ("IntersectionObserver" in window) {
        observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && hasMoreClips.value) {
                    loadMore();
                }
            },
            { rootMargin: "250px" }
        );
        if (sentinelRef.value) {
            observer.observe(sentinelRef.value);
        }
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});

const formatTime = (seconds) => {
    if (isNaN(seconds) || seconds === null || seconds < 0) return "00:00";
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    const pad = (num) => String(num).padStart(2, "0");
    if (h > 0) {
        return `${h}:${pad(m)}:${pad(s)}`;
    }
    return `${pad(m)}:${pad(s)}`;
};

const canDeleteClip = (clip) => {
    if (!authUser.value) return false;
    const isUserAdmin = authUser.value.role === "admin" || !!authUser.value.is_admin;
    if (isUserAdmin) return true;
    if (clip.user_id && clip.user_id === authUser.value.id) return true;
    if (
        clip.creator_name &&
        authUser.value.name &&
        clip.creator_name.trim().toLowerCase() === authUser.value.name.trim().toLowerCase()
    ) {
        return true;
    }
    return false;
};

const deleteClip = (clipId) => {
    const clipTarget = clips.value.find((c) => c.id === clipId);
    if (clipTarget && !canDeleteClip(clipTarget)) {
        alert("Anda tidak memiliki izin untuk menghapus klip ini!");
        return;
    }
    if (confirm("Apakah Anda yakin ingin menghapus klip momen aksi ini?")) {
        emit("delete-clip", clipId);
        if (props.customClips === null) {
            defaultClips.value = defaultClips.value.filter((c) => c.id !== clipId);
        }
    }
};

const activePlayingClip = ref(null);

const playClipModal = (clip) => {
    activePlayingClip.value = clip;
    emit("play-clip", clip);
};
</script>

<template>
    <div class="space-y-4 font-sans">
        <!-- HEADER & TOOLBAR -->
        <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-800 pb-3">
            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                <h3 class="text-xs font-medium text-slate-200 uppercase tracking-tight font-mono flex items-center gap-2">
                    <span>Kumpulan Klip Aksi</span>
                    <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-400 border border-slate-800 font-mono text-[10px]">
                        {{ filteredClips.length }} Klip
                    </span>
                </h3>

                <!-- FILTER SCOPE TOGGLE BUTTONS (OFFICER VS SIARAN INI) -->
                <div class="flex items-center bg-slate-950 border border-slate-800 rounded-md p-0.5 font-mono text-[11px]">
                    <button
                        @click="clipScopeFilter = 'officer'"
                        :class="clipScopeFilter === 'officer' ? 'bg-blue-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 rounded transition-colors cursor-pointer flex items-center gap-1"
                        title="Tampilkan semua klip dari officer ini (seluruh riwayat video)"
                    >
                        <span>Semua Klip Officer</span>
                        <span class="text-[10px] opacity-80">({{ officerClipsCount }})</span>
                    </button>
                    <button
                        @click="clipScopeFilter = 'stream'"
                        :class="clipScopeFilter === 'stream' ? 'bg-blue-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2.5 py-1 rounded transition-colors cursor-pointer flex items-center gap-1"
                        title="Hanya tampilkan klip dari siaran video yang sedang diputar"
                    >
                        <span>Siaran Ini</span>
                        <span class="text-[10px] opacity-80">({{ streamClipsCount }})</span>
                    </button>
                </div>
            </div>

            <!-- SEARCH INPUT & TANDAI AKSI / CREATE CLIP BUTTON -->
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <input
                    v-model="searchQuery"
                    type="text"
                    maxlength="50"
                    placeholder="Cari klip atau pembuat..."
                    class="bg-slate-950 border border-slate-800 rounded-md px-2.5 py-1 text-slate-200 text-xs placeholder:text-slate-500 outline-none focus:border-blue-500 transition-colors w-full sm:w-44"
                />

                <button
                    @click="emit('open-create-clip')"
                    class="px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 rounded-md text-xs font-mono transition-colors flex items-center gap-1.5 cursor-pointer shrink-0"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Klip Baru</span>
                </button>
            </div>
        </div>

        <!-- ACTION CLIPS GRID (SHOW IF CLIPS EXIST) -->
        <div v-if="filteredClips.length > 0" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="clip in paginatedClips"
                    :key="clip.id"
                    class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl overflow-hidden transition-colors flex flex-col group"
                >
                    <!-- THUMBNAIL AREA -->
                    <div class="relative aspect-video bg-slate-950 overflow-hidden cursor-pointer" @click="playClipModal(clip)">
                        <img
                            :src="`https://i.ytimg.com/vi/${clip.video_id}/hqdefault.jpg`"
                            :alt="clip.title"
                            class="w-full h-full object-cover opacity-75 group-hover:opacity-100 transition-opacity"
                        />

                        <!-- DELETE CLIP BUTTON (ONLY VISIBLE IF AUTHORIZED: OWNER OR ADMIN) -->
                        <div v-if="canDeleteClip(clip)" class="absolute top-2 right-2 z-20">
                            <button
                                @click.stop="deleteClip(clip.id)"
                                class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-400 hover:text-red-400 bg-slate-950/90 hover:bg-slate-950 border border-slate-800 hover:border-slate-700 transition-colors flex items-center gap-1 cursor-pointer"
                                title="Hapus Klip"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Hapus</span>
                            </button>
                        </div>

                        <!-- DURATION OVERLAY -->
                        <div class="absolute bottom-2 right-2 bg-slate-950/90 px-2 py-0.5 rounded font-mono text-[10px] text-slate-300 border border-slate-800 z-10">
                            {{ formatTime(clip.start_seconds) }} - {{ formatTime(clip.end_seconds) }}
                        </div>

                        <!-- PLAY BUTTON OVERLAY -->
                        <div class="absolute inset-0 bg-slate-950/30 group-hover:bg-slate-950/10 transition-colors flex items-center justify-center">
                            <div class="w-9 h-9 rounded bg-slate-900/90 hover:bg-slate-800 border border-slate-700 text-slate-200 flex items-center justify-center">
                                <svg class="w-4 h-4 fill-current ml-0.5" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENT DETAILS -->
                    <div class="p-3.5 flex flex-col justify-between flex-1 space-y-2.5">
                        <div>
                            <h4
                                class="text-xs font-medium text-slate-200 group-hover:text-slate-100 transition-colors line-clamp-2 leading-relaxed cursor-pointer break-words"
                                @click="playClipModal(clip)"
                                :title="clip.title"
                            >
                                {{ clip.title }}
                            </h4>
                        </div>

                        <!-- CLEAN FOOTER: CREATOR & CREATED AT -->
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] font-mono text-slate-400">
                            <span class="truncate max-w-[130px] sm:max-w-[160px]" :title="`Ditandai oleh: ${clip.creator_name}`">
                                Ditandai oleh: <strong class="text-slate-300 font-semibold">{{ clip.creator_name }}</strong>
                            </span>
                            <span class="shrink-0 ml-1">{{ clip.created_at }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LAZY LOADING SENTINEL & LOAD MORE BUTTON -->
            <div v-if="hasMoreClips" class="pt-4 flex flex-col items-center justify-center space-y-2">
                <button
                    @click="loadMore"
                    class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-700 rounded-lg text-xs font-mono transition-colors flex items-center gap-2 cursor-pointer shadow"
                >
                    <svg class="w-3.5 h-3.5 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Muat Lebih Banyak Klip (+12 — Tersisa {{ filteredClips.length - visibleCount }})</span>
                </button>
                <div ref="sentinelRef" class="h-4 w-full"></div>
            </div>
        </div>

        <!-- RICH EMPTY STATE (SHOW IF CLIPS IS EMPTY) -->
        <div v-else class="p-10 text-center bg-slate-950 rounded-xl border border-slate-800 flex flex-col items-center justify-center space-y-3">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400">
                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <h4 class="text-sm font-bold text-slate-200 font-mono">BELUM ADA KLIP MOMEN AKSI</h4>
                <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                    {{ searchQuery ? 'Tidak ada klip yang cocok dengan kata kunci pencarian Anda.' : (clipScopeFilter === 'stream' ? 'Belum ada klip momen aksi yang ditandai khusus untuk siaran ini.' : 'Belum ada klip momen aksi yang ditandai untuk perwira ini.') }}
                </p>
            </div>
            <button
                @click="emit('open-create-clip')"
                class="mt-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 rounded-lg text-xs font-mono font-semibold transition-colors flex items-center gap-2 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tandai Momen Aksi Pertama</span>
            </button>
        </div>

        <!-- MODAL EMBED PLAYER -->
        <div v-if="activePlayingClip" class="fixed inset-0 z-50 bg-slate-950/80 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-3xl w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center space-x-2 truncate">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-300 bg-slate-950 border border-slate-800">
                            CLIP
                        </span>
                        <h3 class="text-xs font-medium text-slate-200 truncate font-mono">{{ activePlayingClip.title }}</h3>
                    </div>
                    <button @click="activePlayingClip = null" class="text-slate-400 hover:text-white text-xs font-mono px-2 py-1 rounded bg-slate-800 cursor-pointer">✕ Close</button>
                </div>

                <div class="relative aspect-video w-full bg-slate-950 rounded-lg overflow-hidden border border-slate-800">
                    <iframe
                        :src="`https://www.youtube.com/embed/${activePlayingClip.video_id}?autoplay=1&start=${activePlayingClip.start_seconds}&end=${activePlayingClip.end_seconds}&rel=0`"
                        class="w-full h-full border-0"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>

                <div class="flex items-center justify-between text-xs font-mono text-slate-400 pt-1">
                    <span>Momen Aksi: {{ formatTime(activePlayingClip.start_seconds) }} - {{ formatTime(activePlayingClip.end_seconds) }}</span>
                    <a :href="`https://www.youtube.com/watch?v=${activePlayingClip.video_id}&t=${activePlayingClip.start_seconds}s`" target="_blank" class="text-slate-300 hover:underline">Buka di YouTube</a>
                </div>
            </div>
        </div>
    </div>
</template>
