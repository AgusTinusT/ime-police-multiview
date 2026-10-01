<script setup>
/**
 * ==============================================================================
 * SPESIFIKASI DATA & CONTRACT INTEGRASI - KUMPULAN AKSI & SHORTS TAB
 * ==============================================================================
 * 
 * 1. DATA API DB DIBUTUHKAN (GET /api/streams/{video_id}/clips atau GET /api/clips):
 *    Array dari objek klip dengan struktur schema berikut:
 *    clips: [
 *      {
 *        id: Number/String (ID unik klip di database),
 *        video_id: String (ID Video YouTube terkait),
 *        title: String (Judul Klip Momen Aksi),
 *        start_seconds: Number (Detik mulai tayangan),
 *        end_seconds: Number (Detik selesai tayangan),
 *        duration_seconds: Number (Total durasi klip dalam detik),
 *        creator_name: String (Nama Pembuat Klip / Officer / Viewer),
 *        created_at: String (Format relatif / Waktu pembuatan, misal '3 jam lalu')
 *      }
 *    ]
 * 
 * 2. ENDPOINT API YANG DIPERLUKAN:
 *    - GET /api/streams/{video_id}/clips  : Mengambil daftar klip aksi untuk siaran ini
 *    - POST /api/clips                    : Menyimpan klip aksi baru dari form Focus Mode
 *    - DELETE /api/clips/{id}             : Menghapus klip aksi (Admin / Author)
 * 
 * ==============================================================================
 * CATATAN INTEGRASI:
 * Jika integrasi API Backend (Laravel Controller + Migration DB Clips) ini 
 * sudah selesai dikerjakan, HAPUS BLOK KOMENTAR INI.
 * ==============================================================================
 */
import { ref } from "vue";

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

// Mock initial community action clips
const clips = ref([
    {
        id: 1,
        title: "PIT Maneuver Sempurna Saat Kejaran Supercar di Highway 68",
        start_seconds: 615,
        end_seconds: 660,
        duration_seconds: 45,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "ClipMaster_RP",
        created_at: "3 jam lalu",
    },
    {
        id: 2,
        title: "Baku Tembak Sengit di Paleto Bank - Respon Cepat Unit SAS",
        start_seconds: 1200,
        end_seconds: 1290,
        duration_seconds: 90,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "TacticalLover",
        created_at: "6 jam lalu",
    },
    {
        id: 3,
        title: "Kucing Nyangkut di Mobil Patroli & Dialog Suspek",
        start_seconds: 3400,
        end_seconds: 3450,
        duration_seconds: 50,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "FunnyMoments_GTA",
        created_at: "1 hari lalu",
    },
    {
        id: 4,
        title: "Pendaratan Darurat Helikopter SASP Saat Operasi Badai",
        start_seconds: 2150,
        end_seconds: 2210,
        duration_seconds: 60,
        thumbnail_url: null,
        video_id: props.stream?.video_id || "dQw4w9WgXcQ",
        creator_name: "AirSupportFan",
        created_at: "2 hari lalu",
    },
]);

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
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
    <div class="space-y-4 font-sans">
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

            <!-- TANDAI AKSI / CREATE CLIP BUTTON -->
            <button
                @click="emit('open-create-clip')"
                class="px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 rounded-md text-xs font-mono transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Klip Baru</span>
            </button>
        </div>

        <!-- ACTION CLIPS GRID (SHOW IF CLIPS EXIST) -->
        <div v-if="clips.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="clip in clips"
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

                    <!-- ADMIN DELETE CLIP BUTTON -->
                    <div class="absolute top-2 right-2 z-20">
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
                        <h4 class="text-xs font-medium text-slate-200 group-hover:text-slate-100 transition-colors line-clamp-2 leading-relaxed cursor-pointer" @click="playClipModal(clip)">
                            {{ clip.title }}
                        </h4>
                    </div>

                    <!-- CLEAN FOOTER: CREATOR & CREATED AT -->
                    <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] font-mono text-slate-400">
                        <span>Ditandai oleh: <strong class="text-slate-300 font-semibold">{{ clip.creator_name }}</strong></span>
                        <span>{{ clip.created_at }}</span>
                    </div>
                </div>
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
                    Belum ada klip momen aksi yang ditandai untuk siaran ini. Mulai tandai momen menarik atau kejadian taktis sekarang.
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
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-300 bg-slate-950 border border-slate-800">CLIP</span>
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
