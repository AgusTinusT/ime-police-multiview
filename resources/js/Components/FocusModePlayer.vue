<script setup>
import { ref, computed, nextTick } from "vue";

import OfficerDossierTab from "@/Components/OfficerDossierTab.vue";
import CommunityClipsTab from "@/Components/CommunityClipsTab.vue";
import CreateActionClipModal from "@/Components/CreateActionClipModal.vue";

import iconCloseBold from "@/Components/Icons/close-bold-svgrepo-com.svg";
import iconCut from "@/Components/Icons/cut-svgrepo-com.svg";
import iconTheater from "@/Components/Icons/rectangle-o-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-tac-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/audio-svgrepo-com.svg";
import iconMute from "@/Components/Icons/audio-off-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconChat from "@/Components/Icons/chat-svgrepo-com.svg";
import iconChatRemove from "@/Components/Icons/chat-remove-svgrepo-com.svg";
import iconPlayAll from "@/Components/Icons/play-full-svgrepo-com.svg";

const props = defineProps({
    stream: { type: Object, required: true },
    originUrl: { type: String, default: "" },
    activeAudioVideoId: { type: String, default: null },
    isStreamStopped: { type: Boolean, default: false },
    isRightChatOpen: { type: Boolean, default: true },
    isTheaterMode: { type: Boolean, default: false },
    activeTacChannel: { type: String, default: null },
    isPersonalStream: { type: Boolean, default: false },
    fetchedDescription: { type: String, default: "" },
});

const emit = defineEmits([
    "toggle-audio",
    "toggle-stop-feed",
    "toggle-right-chat",
    "toggle-theater-mode",
    "toggle-tac-popover",
    "toggle-personal-stream",
    "close-focus",
]);

// Active Tab in Focus Mode: 'dossier' (Kesan & Lore), 'clips' (Kumpulan Aksi), 'description' (Deskripsi Streamer)
const activeFocusTab = ref("dossier");
const dossierTabRef = ref(null);

// Modal state for Action Clip
const isClipModalOpen = ref(false);

const handleClipSubmit = (data) => {
    alert(`Klip Momen Aksi "${data.title}" (${data.startTime} - ${data.endTime}) berhasil ditandai.`);
};

const formatDescriptionWithLinks = (text) => {
    if (!text) return "";
    const escaped = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
    const urlPattern = /(https?:\/\/[^\s]+)/g;
    return escaped.replace(
        urlPattern,
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline hover:text-blue-300">$1</a>'
    );
};
</script>

<template>
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden font-sans">
        <!-- 1. STREAM TITLE & HEADER BAR -->
        <div class="bg-slate-900 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between gap-3">
            <div class="flex items-center space-x-2.5 min-w-0">
                <span class="px-2 py-0.5 text-[11px] font-mono font-medium rounded border bg-slate-950 text-slate-300 border-slate-800 shrink-0">
                    {{ stream?.officer?.department || "UNIT" }}
                </span>
                <h1 class="text-xs sm:text-sm font-medium text-slate-200 truncate">
                    {{ stream?.title || "Siaran Patroli Taktis" }}
                </h1>
            </div>

            <!-- EXIT FOCUS / KEMBALI KE MODE GRID BUTTON -->
            <button
                @click="emit('close-focus')"
                class="p-1.5 rounded-md border transition-colors flex items-center justify-center bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border-slate-800 shrink-0 cursor-pointer"
                title="Kembali ke Mode Grid (Tutup Mode Fokus)"
            >
                <img :src="iconCloseBold" class="w-3.5 h-3.5 invert opacity-75 hover:opacity-100" alt="Kembali ke Grid" />
            </button>
        </div>

        <!-- 2. PEMUTAR VIDEO UTAMA -->
        <div class="relative w-full aspect-video bg-slate-950 overflow-hidden">
            <template v-if="!isStreamStopped">
                <iframe
                    :key="`primary-focus-player-${stream.video_id}`"
                    :id="`yt-bodycam-${stream.video_id}`"
                    class="w-full h-full border-0"
                    :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=hd1080&origin=${originUrl}`"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                ></iframe>
            </template>
            <template v-else>
                <img
                    :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                    :alt="stream.title"
                    class="w-full h-full object-cover opacity-50"
                />
                <div class="absolute inset-0 bg-slate-950/80 flex flex-col items-center justify-center p-4 z-10 space-y-3">
                    <span class="text-xs font-mono text-slate-400 bg-slate-900 border border-slate-800 px-3 py-1 rounded-md">
                        FEED DIHENTIKAN SEMENTARA
                    </span>
                    <button
                        @click="emit('toggle-stop-feed')"
                        class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-md text-xs font-medium transition-colors flex items-center gap-2"
                    >
                        <img :src="iconPlayAll" class="w-3.5 h-3.5 invert opacity-70" />
                        <span>Putar Video Kembali</span>
                    </button>
                </div>
            </template>
        </div>

        <!-- 3. ACTION BAR (Below Video Player) -->
        <div class="bg-slate-950 px-4 py-2 border-b border-slate-800 flex items-center justify-between flex-wrap gap-2 text-xs font-medium">
            <!-- LEFT SIDE CONTROLS -->
            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1.5">
                <!-- Audio Toggle Button -->
                <button
                    @click="emit('toggle-audio')"
                    :class="activeAudioVideoId === stream.video_id ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer"
                    title="Toggle Audio"
                >
                    <img :src="activeAudioVideoId === stream.video_id ? iconUnmute : iconMute" class="w-3.5 h-3.5 invert opacity-70" />
                    <span>{{ activeAudioVideoId === stream.video_id ? 'Audio' : 'Mute' }}</span>
                </button>

                <!-- Radio TAC Button -->
                <button
                    @click="emit('toggle-tac-popover')"
                    :class="activeTacChannel ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer"
                    title="Radio TAC Channel"
                >
                    <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-70" />
                    <span>{{ activeTacChannel ? activeTacChannel.replace('_', ' ') : 'Radio TAC' }}</span>
                </button>

                <!-- Tandai Aksi / Clip Button -->
                <button
                    @click="isClipModalOpen = true"
                    class="px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border border-slate-800 hover:border-slate-700 rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                    title="Tandai Momen Aksi (Clip)"
                >
                    <img :src="iconCut" class="w-3.5 h-3.5 invert opacity-70" alt="Clip" />
                    <span>Tandai Aksi</span>
                </button>

                <!-- Mode Bioskop (Theater Mode) Button -->
                <button
                    @click="emit('toggle-theater-mode')"
                    :class="isTheaterMode ? 'bg-slate-800 text-slate-100 border-slate-700 font-semibold' : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer"
                    title="Toggle Mode Bioskop / Theater"
                >
                    <img :src="iconTheater" class="w-3.5 h-3.5 invert opacity-70" alt="Mode Bioskop" />
                    <span>Mode Bioskop</span>
                </button>
            </div>

            <!-- RIGHT SIDE CONTROLS -->
            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1.5">
                <!-- Watchlist Button -->
                <button
                    @click="emit('toggle-personal-stream')"
                    :class="isPersonalStream ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="p-1.5 rounded-md border transition-colors flex items-center justify-center cursor-pointer"
                    :title="isPersonalStream ? 'Hapus dari Watchlist' : 'Tambah ke Watchlist'"
                >
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </button>

                <!-- External YouTube Link -->
                <a
                    :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                    target="_blank"
                    class="p-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 rounded-md border border-slate-800 transition-colors flex items-center justify-center"
                    title="Buka di YouTube"
                >
                    <img :src="iconExternal" class="w-3.5 h-3.5 invert opacity-70" />
                </a>

                <!-- Live Chat Toggle -->
                <button
                    @click="emit('toggle-right-chat')"
                    :class="isRightChatOpen ? 'bg-slate-800 text-slate-200 border-slate-700' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border-slate-800'"
                    class="p-1.5 rounded-md border transition-colors flex items-center justify-center cursor-pointer"
                    :title="isRightChatOpen ? 'Sembunyikan Chat' : 'Tampilkan Chat'"
                >
                    <img :src="isRightChatOpen ? iconChatRemove : iconChat" class="w-3.5 h-3.5 invert opacity-70" />
                </button>

                <!-- Kembali ke Mode Grid Button -->
                <button
                    @click="emit('close-focus')"
                    class="p-1.5 rounded-md border transition-colors flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800 shrink-0 cursor-pointer"
                    title="Kembali ke Mode Grid (Tutup Mode Fokus)"
                >
                    <img :src="iconCloseBold" class="w-3.5 h-3.5 invert opacity-75 hover:opacity-100" alt="Kembali ke Grid" />
                </button>
            </div>
        </div>

        <!-- 4. TAB SECTOR BELOW VIDEO PLAYER -->
        <div class="bg-slate-900 p-4 space-y-4 border-t border-slate-800">
            <!-- TAB BAR NAVIGATION -->
            <div class="flex items-center space-x-2 border-b border-slate-800 pb-2 overflow-x-auto text-xs font-medium">
                <button
                    @click="activeFocusTab = 'dossier'"
                    :class="activeFocusTab === 'dossier' ? 'border-slate-700 text-slate-100 bg-slate-800 font-semibold' : 'border-slate-800 text-slate-400 hover:text-slate-200 bg-slate-950'"
                    class="px-3 py-1.5 rounded-md border transition-colors flex items-center gap-2 shrink-0 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Kesan & Dossier Taktis</span>
                </button>

                <button
                    @click="activeFocusTab = 'clips'"
                    :class="activeFocusTab === 'clips' ? 'border-slate-700 text-slate-100 bg-slate-800 font-semibold' : 'border-slate-800 text-slate-400 hover:text-slate-200 bg-slate-950'"
                    class="px-3.5 py-1.5 rounded-md border transition-colors flex items-center gap-2 shrink-0 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span>Kumpulan Aksi & Shorts</span>
                </button>

                <button
                    @click="activeFocusTab = 'description'"
                    :class="activeFocusTab === 'description' ? 'border-slate-700 text-slate-100 bg-slate-800 font-semibold' : 'border-slate-800 text-slate-400 hover:text-slate-200 bg-slate-950'"
                    class="px-3.5 py-1.5 rounded-md border transition-colors flex items-center gap-2 shrink-0 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Deskripsi Streamer</span>
                </button>
            </div>

            <!-- TAB CONTENT -->
            <div class="pt-1">
                <OfficerDossierTab
                    v-if="activeFocusTab === 'dossier'"
                    ref="dossierTabRef"
                    :officer="stream.officer"
                    :stream="stream"
                />

                <CommunityClipsTab
                    v-else-if="activeFocusTab === 'clips'"
                    :officer="stream.officer"
                    :stream="stream"
                    @open-create-clip="isClipModalOpen = true"
                />

                <div v-else-if="activeFocusTab === 'description'" class="bg-slate-950 rounded-lg p-4 border border-slate-800 space-y-3 font-mono text-xs text-slate-300">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-400">
                        <span class="font-semibold text-slate-200">Deskripsi Resmi YouTube</span>
                        <span>Video ID: {{ stream.video_id }}</span>
                    </div>

                    <div
                        v-if="stream.description || fetchedDescription"
                        class="whitespace-pre-wrap leading-relaxed break-words max-h-96 overflow-y-auto pr-2 scrollbar-thin select-text text-slate-300"
                        v-html="formatDescriptionWithLinks(stream.description || fetchedDescription)"
                    ></div>
                    <div v-else class="italic text-slate-500 py-4 text-center">
                        Tidak ada deskripsi tambahan dari streamer untuk siaran ini.
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DIALOGS -->
        <CreateActionClipModal
            :isOpen="isClipModalOpen"
            :stream="stream"
            @close="isClipModalOpen = false"
            @submit="handleClipSubmit"
        />
    </div>
</template>
