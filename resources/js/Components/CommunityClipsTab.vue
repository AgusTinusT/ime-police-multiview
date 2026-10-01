<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    officer: {
        type: Object,
        default: () => ({}),
    },
    stream: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["open-create-clip", "play-clip"]);

// Mock initial community action clips (Subtle Flat UI)
const clips = ref([
    {
        id: 1,
        title: "PIT Maneuver Sempurna Saat Kejaran Supercar di Highway 68",
        action_category: "PURSUIT_1080",
        start_seconds: 615,
        end_seconds: 660,
        duration_seconds: 45,
        views_count: 1420,
        likes_count: 185,
        is_liked: true,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "ClipMaster_RP",
        created_at: "3 jam lalu",
    },
    {
        id: 2,
        title: "Baku Tembak Sengit di Paleto Bank - Respon Cepat Unit SAS",
        action_category: "SHOOTOUT_1099",
        start_seconds: 1200,
        end_seconds: 1290,
        duration_seconds: 90,
        views_count: 2890,
        likes_count: 312,
        is_liked: false,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "TacticalLover",
        created_at: "6 jam lalu",
    },
    {
        id: 3,
        title: "Kucing Nyangkut di Mobil Patroli & Dialog Suspek",
        action_category: "FUNNY",
        start_seconds: 3400,
        end_seconds: 3450,
        duration_seconds: 50,
        views_count: 4500,
        likes_count: 620,
        is_liked: true,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "FunnyMoments_GTA",
        created_at: "1 hari lalu",
    },
    {
        id: 4,
        title: "Pendaratan Darurat Helikopter SASP Saat Operasi Badai",
        action_category: "TACTICAL_OPS",
        start_seconds: 2150,
        end_seconds: 2210,
        duration_seconds: 60,
        views_count: 980,
        likes_count: 140,
        is_liked: false,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "AirSupportFan",
        created_at: "2 hari lalu",
    },
]);

const activeCategoryFilter = ref("ALL");

const filteredClips = computed(() => {
    if (activeCategoryFilter.value === "ALL") return clips.value;
    return clips.value.filter(
        (c) => c.action_category === activeCategoryFilter.value
    );
});

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
};

const getCategoryLabel = (category) => {
    switch (category) {
        case "PURSUIT_1080":
            return "Kejaran 10-80";
        case "SHOOTOUT_1099":
            return "Baku Tembak 10-99";
        case "FUNNY":
            return "Momen Lucu";
        case "ARREST":
            return "Penangkapan";
        case "TACTICAL_OPS":
            return "Operasi Khusus";
        default:
            return "Aksi Taktis";
    }
};

const toggleLikeClip = (clip) => {
    clip.is_liked = !clip.is_liked;
    if (clip.is_liked) {
        clip.likes_count++;
    } else {
        clip.likes_count--;
    }
};

const deleteClip = (clipId) => {
    if (confirm("Apakah Anda yakin ingin menghapus klip momen aksi ini?")) {
        clips.value = clips.value.filter((c) => c.id !== clipId);
    }
};

const activePlayingClip = ref(null);

const playClipModal = (clip) => {
    activePlayingClip.value = clip;
    emit("play-clip", clip);
};
</script>

<template>
    <div class="space-y-5 font-sans">
        <!-- HEADER & TOOLBAR -->
        <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-800 pb-3">
            <div class="flex items-center space-x-2">
                <h3 class="text-xs font-medium text-slate-200 uppercase tracking-tight font-mono flex items-center gap-2">
                    <span>Kumpulan Aksi & Shorts</span>
                    <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-400 border border-slate-800 font-mono text-[10px]">
                        {{ clips.length }} Klip
                    </span>
                </h3>
            </div>

            <!-- UNIFIED NEUTRAL CATEGORY FILTERS -->
            <div class="flex items-center space-x-1.5 overflow-x-auto text-xs font-mono">
                <button
                    @click="activeCategoryFilter = 'ALL'"
                    :class="activeCategoryFilter === 'ALL' ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1 rounded-md border transition-colors shrink-0"
                >
                    Semua
                </button>
                <button
                    @click="activeCategoryFilter = 'PURSUIT_1080'"
                    :class="activeCategoryFilter === 'PURSUIT_1080' ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1 rounded-md border transition-colors shrink-0"
                >
                    Kejaran 10-80
                </button>
                <button
                    @click="activeCategoryFilter = 'SHOOTOUT_1099'"
                    :class="activeCategoryFilter === 'SHOOTOUT_1099' ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1 rounded-md border transition-colors shrink-0"
                >
                    Baku Tembak
                </button>
                <button
                    @click="activeCategoryFilter = 'FUNNY'"
                    :class="activeCategoryFilter === 'FUNNY' ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1 rounded-md border transition-colors shrink-0"
                >
                    Momen Lucu
                </button>
                <button
                    @click="activeCategoryFilter = 'TACTICAL_OPS'"
                    :class="activeCategoryFilter === 'TACTICAL_OPS' ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1 rounded-md border transition-colors shrink-0"
                >
                    Ops Khusus
                </button>
            </div>
        </div>

        <!-- ACTION CLIPS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="clip in filteredClips"
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

                    <!-- CATEGORY BADGE OVERLAY -->
                    <div class="absolute top-2 left-2 z-10">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-300 bg-slate-950/90 border border-slate-800">
                            {{ getCategoryLabel(clip.action_category) }}
                        </span>
                    </div>

                    <!-- ADMIN DELETE CLIP BUTTON -->
                    <div class="absolute top-2 right-2 z-20">
                        <button
                            @click.stop="deleteClip(clip.id)"
                            class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-400 hover:text-red-400 bg-slate-950/90 hover:bg-slate-950 border border-slate-800 hover:border-slate-700 transition-colors flex items-center gap-1 cursor-pointer"
                            title="Hapus Klip (Akses Admin)"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>

                    <!-- DURATION OVERLAY -->
                    <div class="absolute bottom-2 right-2 bg-slate-950/90 px-2 py-0.5 rounded font-mono text-[10px] text-slate-400 border border-slate-800 z-10">
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
                        <h4 class="text-xs font-medium text-slate-200 group-hover:text-slate-100 transition-colors line-clamp-2 leading-relaxed cursor-pointer" @click="playClipModal(clip)">
                            {{ clip.title }}
                        </h4>
                        <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 mt-2">
                            <span>Oleh: <strong class="text-slate-400">{{ clip.creator_name }}</strong></span>
                            <span>{{ clip.created_at }}</span>
                        </div>
                    </div>

                    <!-- LIKES & VIEWS FOOTER -->
                    <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs font-mono">
                        <span class="text-[10px] text-slate-500">{{ clip.views_count }} views</span>
                        <button
                            @click="toggleLikeClip(clip)"
                            :class="clip.is_liked ? 'text-slate-200 bg-slate-800 border-slate-700' : 'text-slate-400 hover:text-slate-200 bg-slate-950 border-slate-800'"
                            class="px-2 py-0.5 rounded border transition-colors flex items-center gap-1 cursor-pointer"
                        >
                            <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ clip.likes_count }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="filteredClips.length === 0" class="p-8 text-center bg-slate-900 rounded-xl border border-slate-800 text-slate-400 text-xs font-mono">
            Belum ada klip momen aksi untuk kategori ini.
        </div>

        <!-- MODAL EMBED PLAYER -->
        <div v-if="activePlayingClip" class="fixed inset-0 z-50 bg-slate-950/80 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-3xl w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center space-x-2 truncate">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-300 bg-slate-950 border border-slate-800">CLIP</span>
                        <h3 class="text-xs font-medium text-slate-200 truncate font-mono">{{ activePlayingClip.title }}</h3>
                    </div>
                    <button @click="activePlayingClip = null" class="text-slate-400 hover:text-white text-xs font-mono px-2 py-1 rounded bg-slate-800">✕ Close</button>
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
