<script setup>
/**
 * ==============================================================================
 * SPESIFIKASI DATA & CONTRACT INTEGRASI - FOCUS MODE PLAYER
 * ==============================================================================
 * 
 * 1. PROPS DIBUTUHKAN DARI PARENT / BACKEND (Inertia Props):
 *    - stream: {
 *        video_id: String (Wajib - ID Video YouTube, misal 'dQw4w9WgXcQ'),
 *        title: String (Judul Siaran Patroli / Live Stream),
 *        description: String (Deskripsi Resmi Streamer dari Scraper/API),
 *        officer: {
 *          name / officer_name: String (Nama Perwira),
 *          department: String ('LSPD' | 'BCSO' | 'LSCSD' | 'SASP' | 'SAPR'),
 *          handle: String (YouTube Handle, misal '@officer_name')
 *        }
 *      }
 *    - originUrl: String (Origin URL aplikasi untuk YouTube Embed API)
 *    - fetchedDescription: String (Fallback/Lazy-fetched stream description)
 *    - activeAudioVideoId / isStreamStopped / isRightChatOpen / isTheaterMode / activeTacChannel
 * 
 * 2. PAYLOAD UNTUK FORM PENANDAAN KLIP BARU (POST /api/clips):
 *    - video_id: String (ID YouTube stream)
 *    - title: String (Judul Klip Aksi / Kejadian Taktis)
 *    - start_seconds: Number (Waktu Mulai dalam Detik, konversi dari MM:SS)
 *    - end_seconds: Number (Waktu Selesai dalam Detik, konversi dari MM:SS)
 *    - creator_name: String (Nama Penanda Klip / Optional)
 * 
 * ==============================================================================
 * CATATAN INTEGRASI:
 * Setelah integrasi API backend (POST /api/clips & GET /api/streams/{id}) 
 * ini sudah selesai dikerjakan, HAPUS BLOK KOMENTAR INI.
 * ==============================================================================
 */
import { ref, computed, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";

import CommunityClipsTab from "@/Components/CommunityClipsTab.vue";

import iconLspd from "@/Components/Icons/LSPD_HD.svg";
import iconBcso from "@/Components/Icons/Logo_LSCSD.svg";
import iconSasp from "@/Components/Icons/SASP_HD.svg";
import iconSapr from "@/Components/Icons/ranger_logo.svg";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";

import iconCloseBold from "@/Components/Icons/close-bold-svgrepo-com.svg";
import iconCut from "@/Components/Icons/cut-svgrepo-com.svg";
import iconTheater from "@/Components/Icons/rectangle-o-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-tac-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/audio-svgrepo-com.svg";
import iconMute from "@/Components/Icons/audio-off-svgrepo-com.svg";
import iconPause from "@/Components/Icons/controller-paus-svgrepo-com.svg";
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
    fetchedDescription: { type: String, default: "" },
});

const emit = defineEmits([
    "toggle-audio",
    "toggle-stop-feed",
    "toggle-right-chat",
    "toggle-theater-mode",
    "toggle-tac-popover",
    "close-focus",
]);

// Active Tab in Focus Mode: 'description' (Primary / Default), 'clips' (Kumpulan Aksi), 'create-clip' (Form Input Klip Baru)
const activeFocusTab = ref("description");

// Inline Clip Creation Form State
const newClipTitle = ref("");
const newClipStartTime = ref("00:00");
const newClipEndTime = ref("01:00");
const newClipAuthor = ref("");
const newClipDurationPreset = ref(60);

// Live Stream Playback Time Tracking via YouTube postMessage API
const currentPlaybackTime = ref(0);

const formattedCurrentPlaybackTime = computed(() => {
    const sec = Math.floor(currentPlaybackTime.value || 0);
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
});

const handleYTMessage = (event) => {
    if (!event.data) return;
    let data = event.data;
    if (typeof data === "string") {
        try {
            data = JSON.parse(data);
        } catch (e) {
            return;
        }
    }
    if (data && (data.event === "infoDelivery" || data.info)) {
        const info = data.info || data;
        if (info && typeof info.currentTime === "number") {
            currentPlaybackTime.value = info.currentTime;
        }
    }
};

let ytTimePollInterval = null;

onMounted(() => {
    window.addEventListener("message", handleYTMessage);
    ytTimePollInterval = setInterval(() => {
        const iframe = document.getElementById(
            `yt-bodycam-${props.stream?.video_id}`,
        );
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage(
                    JSON.stringify({
                        event: "listening",
                        id: 1,
                        channel: "widget",
                    }),
                    "*",
                );
                iframe.contentWindow.postMessage(
                    JSON.stringify({
                        event: "command",
                        func: "getCurrentTime",
                        args: [],
                    }),
                    "*",
                );
            } catch (e) {}
        }
    }, 500);
});

onUnmounted(() => {
    window.removeEventListener("message", handleYTMessage);
    if (ytTimePollInterval) clearInterval(ytTimePollInterval);
});

const setClipPreset = (seconds) => {
    newClipDurationPreset.value = seconds;
    const parts = newClipStartTime.value.split(":");
    let totalSecs = 0;
    if (parts.length === 2) {
        totalSecs = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }
    const endSecs = totalSecs + seconds;
    const em = Math.floor(endSecs / 60);
    const es = Math.floor(endSecs % 60);
    newClipEndTime.value = `${em.toString().padStart(2, "0")}:${es.toString().padStart(2, "0")}`;
};

const setStartFromCurrentTime = () => {
    const sec = Math.floor(currentPlaybackTime.value || 0);
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    newClipStartTime.value = `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
    setClipPreset(newClipDurationPreset.value);
};

const setEndFromCurrentTime = () => {
    const sec = Math.floor(currentPlaybackTime.value || 0);
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    newClipEndTime.value = `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
};

const handleCreateClipSubmit = () => {
    if (!newClipTitle.value.trim()) {
        alert("Harap masukkan judul klip momen aksi!");
        return;
    }

    alert(
        `Klip Momen Aksi "${newClipTitle.value}" (${newClipStartTime.value} - ${newClipEndTime.value}) berhasil ditandai.`,
    );

    // Reset & switch to clips tab
    newClipTitle.value = "";
    activeFocusTab.value = "clips";
};

const openStudioClipper = () => {
    if (props.stream?.video_id) {
        const targetUrl = `https://www.youtube.com/watch?v=${props.stream.video_id}`;
        router.visit(`/clipper?url=${encodeURIComponent(targetUrl)}`);
    }
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
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline hover:text-blue-300">$1</a>',
    );
};

const getDeptIcon = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD")) return iconLspd;
    if (d.includes("BCSO") || d.includes("LSCSD")) return iconBcso;
    if (d.includes("SASP")) return iconSasp;
    if (d.includes("SAPR") || d.includes("RANGER")) return iconSapr;
    return iconAllUnits;
};

const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD"))
        return "bg-blue-950/80 text-blue-300 border-blue-800/80";
    if (d.includes("BCSO") || d.includes("LSCSD"))
        return "bg-amber-950/80 text-amber-300 border-amber-800/80";
    if (d.includes("SASP"))
        return "bg-teal-950/80 text-teal-300 border-teal-800/80";
    if (d.includes("SAPR") || d.includes("RANGER"))
        return "bg-emerald-950/80 text-emerald-300 border-emerald-800/80";
    return "bg-slate-900 text-slate-300 border-slate-800";
};

const officerName = computed(() => {
    return (
        props.stream?.officer?.officer_name ||
        props.stream?.officer?.name ||
        "Perwira"
    );
});

const officerDepartment = computed(() => {
    return props.stream?.officer?.department || "UNIT";
});

const youtubeHandle = computed(() => {
    const h = props.stream?.officer?.handle;
    if (!h) return null;
    return h.startsWith("@") ? h : `@${h}`;
});
</script>

<template>
    <div
        class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden font-sans shadow-xl"
    >
        <!-- 1. TOP HEADER BAR (Department Badge + Stream Title & Control Buttons: TAC, Audio, Pause, Grid) -->
        <div
            class="bg-slate-950 px-3.5 py-2.5 border-b border-slate-800 flex items-center justify-between flex-wrap gap-2.5"
        >
            <!-- LEFT SIDE: DEPARTMENT BADGE & STREAM TITLE -->
            <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                <!-- Department Badge at left of title -->
                <span
                    class="px-2 py-0.5 text-[10px] font-mono font-bold rounded border tracking-wider shrink-0"
                    :class="getDeptBadgeClass(officerDepartment)"
                >
                    {{ officerDepartment }}
                </span>

                <h1
                    class="text-xs sm:text-sm font-medium text-slate-200 truncate"
                    :title="stream?.title"
                >
                    {{ stream?.title || "Siaran Patroli Taktis" }}
                </h1>
            </div>

            <!-- RIGHT SIDE BUTTONS (FROM RIGHT TO LEFT: GRID, PAUSE, AUDIO, TAC) -->
            <div class="flex items-center space-x-1.5 shrink-0">
                <!-- TAC BUTTON (4th from right / 1st from left of group) -->
                <button
                    @click="emit('toggle-tac-popover')"
                    :class="
                        activeTacChannel
                            ? 'bg-slate-800 text-slate-100 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer text-xs font-mono"
                    title="Radio TAC Channel"
                >
                    <img
                        :src="iconRadio"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="TAC"
                    />
                    <span class="hidden sm:inline">{{
                        activeTacChannel
                            ? activeTacChannel.replace("_", " ")
                            : "Radio TAC"
                    }}</span>
                </button>

                <!-- AUDIO BUTTON (3rd from right) -->
                <button
                    @click="emit('toggle-audio')"
                    :class="
                        activeAudioVideoId === stream.video_id
                            ? 'bg-slate-800 text-slate-100 border-slate-700'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer text-xs font-mono"
                    title="Toggle Audio"
                >
                    <img
                        :src="
                            activeAudioVideoId === stream.video_id
                                ? iconUnmute
                                : iconMute
                        "
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Audio"
                    />
                    <span class="hidden sm:inline">{{
                        activeAudioVideoId === stream.video_id
                            ? "Audio"
                            : "Mute"
                    }}</span>
                </button>

                <!-- PAUSE BUTTON (2nd from right) -->
                <button
                    @click="emit('toggle-stop-feed')"
                    :class="
                        isStreamStopped
                            ? 'bg-slate-800 text-amber-400 border-slate-700 font-bold'
                            : 'bg-slate-900 text-slate-400 hover:bg-slate-800 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer text-xs font-mono"
                    :title="
                        isStreamStopped
                            ? 'Putar Video (Play)'
                            : 'Pause Video (Stop Feed)'
                    "
                >
                    <img
                        :src="isStreamStopped ? iconPlayAll : iconPause"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Pause"
                    />
                    <span class="hidden sm:inline">{{
                        isStreamStopped ? "Play" : "Pause"
                    }}</span>
                </button>

                <!-- GRID BUTTON (1st / Rightmost) -->
                <button
                    @click="emit('close-focus')"
                    class="p-1.5 rounded-md border transition-colors flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border-slate-800 shrink-0 cursor-pointer"
                    title="Kembali ke Mode Grid (Tutup Mode Fokus)"
                >
                    <img
                        :src="iconCloseBold"
                        class="w-3.5 h-3.5 invert opacity-75 hover:opacity-100"
                        alt="Kembali ke Grid"
                    />
                </button>
            </div>
        </div>

        <!-- 2. PEMUTAR VIDEO UTAMA -->
        <div class="relative w-full aspect-video bg-slate-950 overflow-hidden">
            <template v-if="!isStreamStopped">
                <iframe
                    :key="`primary-focus-player-${stream.video_id}`"
                    :id="`yt-bodycam-${stream.video_id}`"
                    class="w-full h-full border-0"
                    :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=hd1080&origin=${originUrl}`"
                    allow="
                        accelerometer;
                        autoplay;
                        clipboard-write;
                        encrypted-media;
                        gyroscope;
                        picture-in-picture;
                        web-share;
                    "
                    allowfullscreen
                ></iframe>
            </template>
            <template v-else>
                <img
                    :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                    :alt="stream.title"
                    class="w-full h-full object-cover opacity-50"
                />
                <div
                    class="absolute inset-0 bg-slate-950/80 flex flex-col items-center justify-center p-4 z-10 space-y-3"
                >
                    <span
                        class="text-xs font-mono text-slate-400 bg-slate-900 border border-slate-800 px-3 py-1 rounded-md"
                    >
                        FEED DIHENTIKAN SEMENTARA
                    </span>
                    <button
                        @click="emit('toggle-stop-feed')"
                        class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-md text-xs font-medium transition-colors flex items-center gap-2"
                    >
                        <img
                            :src="iconPlayAll"
                            class="w-3.5 h-3.5 invert opacity-70"
                        />
                        <span>Putar Video Kembali</span>
                    </button>
                </div>
            </template>
        </div>

        <!-- 3. UNIFIED OFFICER PROFILE & ACTION CONTROLS BAR (DI BAWAH VIDEO STREAMING) -->
        <div
            class="bg-slate-950 px-4 py-3 border-b border-slate-800 flex items-center justify-between flex-wrap gap-3"
        >
            <!-- LEFT SIDE: PROFILE ICON, OFFICER NAME, YOUTUBE HANDLE ONLY -->
            <div class="flex items-center space-x-3 min-w-0">
                <!-- Profile / Department Icon -->
                <div
                    class="w-9 h-9 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center shrink-0 p-1.5"
                >
                    <img
                        :src="getDeptIcon(officerDepartment)"
                        class="w-full h-full object-contain invert opacity-90"
                        :alt="officerDepartment"
                    />
                </div>

                <!-- Officer Name & YouTube Handle -->
                <div class="min-w-0 flex-1">
                    <h2
                        class="text-sm font-bold text-slate-100 truncate tracking-tight leading-none"
                    >
                        {{ officerName }}
                    </h2>
                    <div
                        v-if="youtubeHandle"
                        class="text-xs text-slate-400 mt-1 font-mono truncate"
                    >
                        <a
                            :href="`https://www.youtube.com/${youtubeHandle}`"
                            target="_blank"
                            class="text-blue-400 hover:text-blue-300 hover:underline truncate"
                        >
                            {{ youtubeHandle }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE: ACTION CONTROLS (Tandai Aksi, Mode Bioskop, Live Chat, Buka di YouTube) -->
            <div
                class="flex items-center space-x-1.5 flex-wrap gap-y-1.5 shrink-0"
            >
                <!-- Tandai Aksi / Clip Button -->
                <button
                    @click="
                        activeFocusTab =
                            activeFocusTab === 'create-clip'
                                ? 'clips'
                                : 'create-clip'
                    "
                    :class="
                        activeFocusTab === 'create-clip'
                            ? 'bg-rose-950 text-rose-300 border-rose-800 font-semibold'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer font-mono text-xs"
                    title="Tandai Momen Aksi (Buka Form Input di Bawah Video)"
                >
                    <img
                        :src="iconCut"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Clip"
                    />
                    <span>Tandai Aksi</span>
                </button>

                <!-- Mode Bioskop (Theater Mode) Button -->
                <button
                    @click="emit('toggle-theater-mode')"
                    :class="
                        isTheaterMode
                            ? 'bg-slate-800 text-slate-100 border-slate-700 font-semibold'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer font-mono text-xs"
                    title="Toggle Mode Bioskop / Theater"
                >
                    <img
                        :src="iconTheater"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Mode Bioskop"
                    />
                    <span>Mode Bioskop</span>
                </button>

                <!-- Live Chat Toggle Button (Right to the right side of Mode Bioskop) -->
                <button
                    @click="emit('toggle-right-chat')"
                    :class="
                        isRightChatOpen
                            ? 'bg-slate-800 text-slate-100 border-slate-700 font-semibold'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer font-mono text-xs"
                    :title="
                        isRightChatOpen ? 'Sembunyikan Chat' : 'Tampilkan Chat'
                    "
                >
                    <img
                        :src="isRightChatOpen ? iconChatRemove : iconChat"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Toggle Chat"
                    />
                    <span>Chat</span>
                </button>

                <!-- External YouTube Link -->
                <a
                    :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                    target="_blank"
                    class="p-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 rounded-md border border-slate-800 transition-colors flex items-center justify-center"
                    title="Buka di YouTube"
                >
                    <img
                        :src="iconExternal"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Buka di YouTube"
                    />
                </a>
            </div>
        </div>

        <!-- 4. TAB SECTOR BELOW VIDEO PLAYER & OFFICER PROFILE -->
        <div class="bg-slate-900 p-4 space-y-4 border-t border-slate-800">
            <!-- TAB BAR NAVIGATION (ONLY 2 TABS: DESKRIPSI STREAMER & KUMPULAN AKSI & SHORTS) -->
            <div
                class="flex items-center space-x-2 border-b border-slate-800 pb-2 overflow-x-auto text-xs font-medium"
            >
                <!-- TAB 1: DESKRIPSI STREAMER (PRIMARY) -->
                <button
                    @click="activeFocusTab = 'description'"
                    :class="
                        activeFocusTab === 'description'
                            ? 'border-slate-700 text-slate-100 bg-slate-800 font-semibold'
                            : 'border-slate-800 text-slate-400 hover:text-slate-200 bg-slate-950'
                    "
                    class="px-3.5 py-1.5 rounded-md border transition-colors flex items-center gap-2 shrink-0 cursor-pointer"
                >
                    <svg
                        class="w-3.5 h-3.5 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>Deskripsi Streamer</span>
                </button>

                <!-- TAB 2: KUMPULAN AKSI & SHORTS -->
                <button
                    @click="activeFocusTab = 'clips'"
                    :class="
                        activeFocusTab === 'clips'
                            ? 'border-slate-700 text-slate-100 bg-slate-800 font-semibold'
                            : 'border-slate-800 text-slate-400 hover:text-slate-200 bg-slate-950'
                    "
                    class="px-3.5 py-1.5 rounded-md border transition-colors flex items-center gap-2 shrink-0 cursor-pointer"
                >
                    <svg
                        class="w-3.5 h-3.5 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                    </svg>
                    <span>Kumpulan Aksi & Shorts</span>
                </button>
            </div>

            <!-- TAB CONTENT -->
            <div class="pt-1">
                <!-- DESKRIPSI STREAMER CONTENT -->
                <div
                    v-if="activeFocusTab === 'description'"
                    class="bg-slate-950 rounded-lg p-4 border border-slate-800 space-y-3 font-mono text-xs text-slate-300"
                >
                    <div
                        class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-400"
                    >
                        <span class="font-semibold text-slate-200"
                            >Deskripsi Resmi YouTube</span
                        >
                        <span>Video ID: {{ stream.video_id }}</span>
                    </div>

                    <div
                        v-if="stream.description || fetchedDescription"
                        class="whitespace-pre-wrap leading-relaxed break-words max-h-96 overflow-y-auto pr-2 scrollbar-thin select-text text-slate-300"
                        v-html="
                            formatDescriptionWithLinks(
                                stream.description || fetchedDescription,
                            )
                        "
                    ></div>
                    <div v-else class="italic text-slate-500 py-4 text-center">
                        Tidak ada deskripsi tambahan dari streamer untuk siaran
                        ini.
                    </div>
                </div>

                <!-- KUMPULAN AKSI & SHORTS CONTENT -->
                <CommunityClipsTab
                    v-else-if="activeFocusTab === 'clips'"
                    :officer="stream.officer"
                    :stream="stream"
                    @open-create-clip="activeFocusTab = 'create-clip'"
                />

                <!-- TAB INLINE FORM: TANDAI AKSI BARU (FLAT MINIMAL TAILWIND DESIGN FROM PANDUAN.HTML) -->
                <div
                    v-else-if="activeFocusTab === 'create-clip'"
                    class="bg-slate-950 rounded-xl p-4 border border-slate-800 space-y-4 font-sans text-xs text-slate-300 animate-in fade-in duration-200"
                >
                    <!-- COMPACT HEADER -->
                    <div
                        class="flex items-center justify-between pb-3 border-b border-slate-800"
                    >
                        <div class="flex items-center space-x-2">
                            <img
                                :src="iconCut"
                                class="w-4 h-4 invert opacity-80"
                                alt=""
                            />
                            <span class="font-semibold text-slate-100 text-xs"
                                >Penandaan Momen Aksi Taktis</span
                            >
                            <span
                                class="text-[11px] font-mono text-slate-400 hidden sm:inline"
                                >— Catat stempel waktu tanpa menutup video</span
                            >
                        </div>
                        <button
                            @click="activeFocusTab = 'clips'"
                            class="text-slate-400 hover:text-slate-200 text-xs font-mono px-2.5 py-1 rounded bg-slate-900 border border-slate-800 hover:border-slate-700 transition-colors cursor-pointer"
                        >
                            ✕ Tutup Form
                        </button>
                    </div>

                    <!-- 2-COLUMN GRID: LEFT SIDE = STUDIO CLIPPER PRO CARD, RIGHT SIDE = FORM INPUTS -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <!-- LEFT SIDE: STUDIO CLIPPER PRO INVITATION CARD (4 COLS - FLAT MINIMAL DESIGNS FROM PANDUAN.HTML) -->
                        <div
                            class="md:col-span-4 bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between space-y-4"
                        >
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full bg-blue-500"
                                        ></span>
                                        <span
                                            class="text-xs font-mono font-semibold text-slate-300"
                                            >Studio Clipper Pro</span
                                        >
                                    </div>
                                    <span
                                        class="text-[10px] font-mono bg-slate-950 border border-slate-800 text-slate-400 px-2 py-0.5 rounded"
                                    >
                                        BETA
                                    </span>
                                </div>

                                <div>
                                    <h4
                                        class="text-xs font-bold text-slate-100 tracking-tight"
                                    >
                                        Tertarik Jadi Official Clipper?
                                    </h4>
                                    <p
                                        class="text-[11px] text-slate-400 mt-1 leading-relaxed"
                                    >
                                        Buka fitur pemotong video presisi
                                        tinggi, tambahkan efek taktis, dan
                                        kompilasi momen siaran terbaik di studio
                                        khusus.
                                    </p>
                                </div>
                            </div>

                            <button
                                @click="openStudioClipper"
                                class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-xs font-semibold transition-colors cursor-pointer"
                            >
                                <span>Akses Studio Clipper</span>
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- RIGHT SIDE: MAIN FORM INPUTS (8 COLS) -->
                        <div
                            class="md:col-span-8 bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3"
                        >
                            <!-- ROW 1: JUDUL MOMEN AKSI -->
                            <div>
                                <label
                                    class="block text-slate-300 font-medium mb-1 text-xs"
                                    >Judul Momen Aksi / Kejadian *</label
                                >
                                <input
                                    v-model="newClipTitle"
                                    type="text"
                                    placeholder="Contoh: High Speed Pursuit di Freeway & PIT Maneuver"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs transition-colors"
                                />
                            </div>

                            <!-- ROW 2: LIVE TIME QUICK SETTER & PRESET + STEMPEL WAKTU -->
                            <div class="space-y-2.5">
                                <!-- QUICK SETTER BUTTON FROM STREAM PLAYBACK -->
                                <div
                                    class="flex items-center justify-between bg-slate-950 border border-slate-800 rounded-md p-2 flex-wrap gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full bg-red-500 animate-pulse"
                                        ></span>
                                        <span
                                            class="text-xs font-mono text-slate-300"
                                            >Waktu Live Stream:
                                            <strong
                                                class="text-blue-400 font-bold font-mono"
                                                >{{
                                                    formattedCurrentPlaybackTime
                                                }}</strong
                                            ></span
                                        >
                                    </div>
                                    <button
                                        @click="setStartFromCurrentTime"
                                        type="button"
                                        class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-md text-[11px] font-mono font-semibold transition-colors flex items-center gap-1.5 cursor-pointer"
                                        title="Set Waktu Mulai ke posisi video saat ini"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <span
                                            >Ambil Waktu Live ({{
                                                formattedCurrentPlaybackTime
                                            }})</span
                                        >
                                    </button>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end"
                                >
                                    <div class="sm:col-span-4">
                                        <label
                                            class="block text-slate-400 text-[11px] font-mono mb-1"
                                            >Durasi Cepat</label
                                        >
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                @click="setClipPreset(60)"
                                                :class="
                                                    newClipDurationPreset === 60
                                                        ? 'bg-blue-600 text-white border-blue-600 font-semibold'
                                                        : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-800 hover:text-slate-200'
                                                "
                                                class="flex-1 py-1 rounded-md border text-xs font-mono transition-colors cursor-pointer text-center"
                                            >
                                                1 Min
                                            </button>
                                            <button
                                                @click="setClipPreset(180)"
                                                :class="
                                                    newClipDurationPreset ===
                                                    180
                                                        ? 'bg-blue-600 text-white border-blue-600 font-semibold'
                                                        : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-800 hover:text-slate-200'
                                                "
                                                class="flex-1 py-1 rounded-md border text-xs font-mono transition-colors cursor-pointer text-center"
                                            >
                                                3 Min
                                            </button>
                                            <button
                                                @click="setClipPreset(300)"
                                                :class="
                                                    newClipDurationPreset ===
                                                    300
                                                        ? 'bg-blue-600 text-white border-blue-600 font-semibold'
                                                        : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-800 hover:text-slate-200'
                                                "
                                                class="flex-1 py-1 rounded-md border text-xs font-mono transition-colors cursor-pointer text-center"
                                            >
                                                5 Min
                                            </button>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-4">
                                        <div
                                            class="flex items-center justify-between mb-1"
                                        >
                                            <label
                                                class="text-slate-400 text-[11px] font-mono"
                                                >Mulai (MM:SS)</label
                                            >
                                            <button
                                                @click="setStartFromCurrentTime"
                                                type="button"
                                                class="text-[10px] text-blue-400 hover:text-blue-300 font-mono underline cursor-pointer"
                                            >
                                                Set Live
                                            </button>
                                        </div>
                                        <input
                                            v-model="newClipStartTime"
                                            type="text"
                                            placeholder="00:00"
                                            class="w-full bg-slate-950 border border-slate-800 rounded-md px-2 py-1 text-slate-100 focus:border-blue-500 text-center font-mono font-semibold text-xs transition-colors"
                                        />
                                    </div>

                                    <div class="sm:col-span-4">
                                        <div
                                            class="flex items-center justify-between mb-1"
                                        >
                                            <label
                                                class="text-slate-400 text-[11px] font-mono"
                                                >Selesai (MM:SS)</label
                                            >
                                            <button
                                                @click="setEndFromCurrentTime"
                                                type="button"
                                                class="text-[10px] text-blue-400 hover:text-blue-300 font-mono underline cursor-pointer"
                                            >
                                                Set Live
                                            </button>
                                        </div>
                                        <input
                                            v-model="newClipEndTime"
                                            type="text"
                                            placeholder="00:30"
                                            class="w-full bg-slate-950 border border-slate-800 rounded-md px-2 py-1 text-slate-100 focus:border-blue-500 text-center font-mono font-semibold text-xs transition-colors"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 3: NAMA PEMBUAT & TOMBOL SIMPAN -->
                            <div
                                class="flex items-center gap-2 pt-1 flex-wrap sm:flex-nowrap"
                            >
                                <div class="w-full sm:flex-1">
                                    <input
                                        v-model="newClipAuthor"
                                        type="text"
                                        placeholder="Panggilan Anda (Opsional)"
                                        class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 text-xs focus:border-blue-500 outline-none transition-colors"
                                    />
                                </div>
                                <button
                                    @click="handleCreateClipSubmit"
                                    class="w-full sm:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-semibold rounded-md text-xs transition-colors shrink-0 cursor-pointer"
                                >
                                    Simpan Klip Momen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
