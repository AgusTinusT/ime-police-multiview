<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";

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
    chatEmbedDomain: { type: String, default: "" },
});

const emit = defineEmits([
    "toggle-audio",
    "toggle-stop-feed",
    "toggle-right-chat",
    "toggle-theater-mode",
    "toggle-tac-popover",
    "assign-tac",
    "remove-tac",
    "close-focus",
]);

// Inertia Page User Permission & Auth State
const page = usePage();
const authUser = computed(() => page.props.auth?.user);

// Active Tab in Focus Mode: 'chat' (Live Chat Mobile-First), 'description' (Deskripsi), 'clips' (Kumpulan Aksi), 'create-clip' (Form Input Klip Baru)
const activeFocusTab = ref("chat");

const computedChatEmbedDomain = computed(() => {
    if (props.chatEmbedDomain) return props.chatEmbedDomain;
    if (typeof window !== "undefined") {
        return window.location.hostname;
    }
    return "localhost";
});

// Radio TAC Popover State & Methods
const isTacPopoverOpen = ref(false);

const selectTacChannel = (tacCode) => {
    emit("assign-tac", tacCode);
    isTacPopoverOpen.value = false;
};

const removeTacChannel = () => {
    emit("remove-tac");
    isTacPopoverOpen.value = false;
};

// Inline Clip Creation Form State
const newClipTitle = ref("");
const newClipStartTime = ref("00:00");
const newClipEndTime = ref("01:00");
const newClipAuthor = ref(authUser.value?.name || "");
const newClipDurationPreset = ref(60);

watch(
    () => authUser.value?.name,
    (newName) => {
        if (newName && !newClipAuthor.value) {
            newClipAuthor.value = newName;
        }
    },
    { immediate: true }
);

// Clips list state for Focus Mode (Persisted in localStorage across page refreshes)
// Clips list state for Focus Mode (Fetched from MySQL database via API)
const customClipsList = ref([]);

const fetchCommunityClips = async () => {
    try {
        const response = await axios.get("/api/v1/tac-clips");
        if (response.data && response.data.data) {
            customClipsList.value = response.data.data;
        }
    } catch (e) {
        // Silently fallback if API fetch fails
    }
};

const handleDeleteClip = async (clipId) => {
    try {
        await axios.delete(`/api/v1/tac-clips/${clipId}`);
    } catch (e) {
        // Silently handle backend delete
    }
    customClipsList.value = customClipsList.value.filter(
        (c) => c.id !== clipId,
    );
};

// Stream Availability State (Deleted / Private / Members-Only YouTube video handling)
const isStreamUnavailable = ref(false);
const streamErrorCode = ref(null);

// Live Stream Playback Time Tracking via YouTube postMessage API
const currentPlaybackTime = ref(0);

const formatTimeHelper = (seconds) => {
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

const parseTimestamp = (str) => {
    if (!str) return 0;
    const parts = String(str).trim().split(":");
    if (parts.length === 3) {
        return (
            (parseInt(parts[0], 10) || 0) * 3600 +
            (parseInt(parts[1], 10) || 0) * 60 +
            (parseInt(parts[2], 10) || 0)
        );
    }
    if (parts.length === 2) {
        return (
            (parseInt(parts[0], 10) || 0) * 60 +
            (parseInt(parts[1], 10) || 0)
        );
    }
    return parseInt(str, 10) || 0;
};

const formattedCurrentPlaybackTime = computed(() => {
    return formatTimeHelper(Math.floor(currentPlaybackTime.value || 0));
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

    // YouTube Error Event Code Handling (100 = deleted/not found, 101/150 = members-only/private embed restricted)
    if (
        data &&
        (data.event === "onError" ||
            data.info === 100 ||
            data.info === 101 ||
            data.info === 150)
    ) {
        isStreamUnavailable.value = true;
        streamErrorCode.value = data.info || data.data || 100;
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
    fetchCommunityClips();
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
    const totalSecs = parseTimestamp(newClipStartTime.value);
    const endSecs = totalSecs + seconds;
    newClipEndTime.value = formatTimeHelper(endSecs);
};

const setStartFromCurrentTime = () => {
    const sec = Math.floor(currentPlaybackTime.value || 0);
    newClipStartTime.value = formatTimeHelper(sec);
    setClipPreset(newClipDurationPreset.value);
};

const setEndFromCurrentTime = () => {
    const sec = Math.floor(currentPlaybackTime.value || 0);
    newClipEndTime.value = formatTimeHelper(sec);
};

const handleCreateClipSubmit = async () => {
    if (!newClipTitle.value.trim()) {
        alert("Harap masukkan judul klip momen aksi!");
        return;
    }

    const startSec = parseTimestamp(newClipStartTime.value);
    const endSec = parseTimestamp(newClipEndTime.value);

    if (endSec <= startSec) {
        alert("Waktu Selesai harus lebih besar dari Waktu Mulai.");
        return;
    }

    const authorNameFinal =
        newClipAuthor.value.trim() ||
        authUser.value?.name ||
        "Penonton Komunitas";

    try {
        const response = await axios.post("/api/v1/tac-clips", {
            video_id: props.stream?.video_id || null,
            title: newClipTitle.value.trim(),
            start_seconds: startSec,
            end_seconds: endSec,
            officer_id: props.stream?.officer?.id || props.stream?.officer_id || null,
            officer_name: props.stream?.officer?.officer_name || props.stream?.officer?.name || null,
            officer_handle: props.stream?.officer?.handle || null,
            creator_name: authorNameFinal,
        });

        if (response.data && response.data.clip) {
            customClipsList.value.unshift(response.data.clip);
        }
    } catch (err) {
        alert(err.response?.data?.message || "Gagal menyimpan klip momen aksi ke database.");
        return;
    }

    // Reset & switch to clips tab
    newClipTitle.value = "";
    newClipAuthor.value = authUser.value?.name || "";
    activeFocusTab.value = "clips";
};

// Inertia Page User Permission State
const canTrimVideo = computed(() => {
    if (!authUser.value) return false;
    return (
        !!authUser.value.can_trim_video ||
        ["admin", "clipper"].includes(authUser.value.role)
    );
});

const openStudioClipper = () => {
    if (!authUser.value) {
        alert(
            "🔒 Akses Studio Clipper Terbatas!\n\nFitur Studio Clipper Pro khusus untuk Perwira dan Official Clipper IME Police. Silakan login terlebih dahulu untuk mengakses studio pemotong video.",
        );
        return;
    }

    if (!canTrimVideo.value) {
        alert(
            `🔒 Akses Studio Clipper Terbatas!\n\nAkun Anda (${authUser.value.name}) belum memiliki hak akses Studio Clipper Pro (Diperlukan Role: Clipper atau Admin).\n\nSilakan hubungi Administrator untuk meminta role Clipper.`,
        );
        return;
    }

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
        return "bg-blue-600/30 text-blue-300 border-blue-500/50";
    if (d.includes("BCSO") || d.includes("LSCSD"))
        return "bg-amber-600/30 text-amber-300 border-amber-500/50";
    if (d.includes("SASP"))
        return "bg-teal-600/30 text-teal-300 border-teal-500/50";
    if (d.includes("SAPR") || d.includes("RANGER"))
        return "bg-green-600/30 text-green-300 border-green-500/50";
    return "bg-slate-700/40 text-slate-300 border-slate-600";
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

const officerCallsign = computed(() => {
    return props.stream?.officer?.callsign || "";
});

const officerBadge = computed(() => {
    return props.stream?.officer?.badge_number || "";
});

const officerRank = computed(() => {
    return props.stream?.officer?.rank || "";
});

const officerAvatar = computed(() => {
    return props.stream?.officer?.avatar_url || "";
});

const youtubeHandle = computed(() => {
    const h = props.stream?.officer?.handle;
    if (!h) return null;
    return h.startsWith("@") ? h : `@${h}`;
});

const handleAvatarError = (e) => {
    e.target.src = `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officerCallsign.value || officerName.value || "officer")}`;
};
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
                    class="text-xs sm:text-sm font-medium text-slate-200 truncate max-w-xs sm:max-w-md md:max-w-lg lg:max-w-2xl"
                    :title="stream?.title || 'Siaran Patroli Taktis'"
                >
                    {{ stream?.title || "Siaran Patroli Taktis" }}
                </h1>
            </div>

            <!-- RIGHT SIDE BUTTONS (FROM RIGHT TO LEFT: GRID, PAUSE, AUDIO, TAC) -->
            <div class="flex items-center space-x-1.5 shrink-0">
                <!-- TAC BUTTON WITH INTERACTIVE DROPDOWN POPOVER -->
                <div class="relative">
                    <button
                        @click="isTacPopoverOpen = !isTacPopoverOpen"
                        :class="
                            activeTacChannel
                                ? 'bg-blue-950/90 text-blue-300 border-blue-700/80 font-bold'
                                : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                        "
                        class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer text-xs font-mono"
                        title="Pilih / Kelola Saluran Radio TAC"
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

                    <!-- TAC CHANNEL SELECTION DROPDOWN POPOVER -->
                    <div
                        v-if="isTacPopoverOpen"
                        class="absolute right-0 mt-2 w-72 sm:w-80 bg-slate-900 border border-slate-800 rounded-xl p-3 shadow-2xl z-50 space-y-3 font-sans text-xs animate-in fade-in zoom-in-95 duration-150"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-800 pb-2"
                        >
                            <div class="flex items-center space-x-1.5">
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
                                ></span>
                                <span
                                    class="font-semibold text-slate-200 font-mono text-[11px]"
                                    >Radio Taktis TAC</span
                                >
                            </div>
                            <button
                                @click="isTacPopoverOpen = false"
                                class="text-slate-400 hover:text-slate-200 text-xs font-mono px-1.5 py-0.5 rounded bg-slate-800 cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>

                        <p class="text-[11px] text-slate-400 leading-snug">
                            Pilih saluran radio TAC 1 - 10 untuk menghubungkan
                            stream perwira ini:
                        </p>

                        <!-- 10 TAC CHANNELS GRID (2 ROWS X 5 COLS: TAC 1 - TAC 10) -->
                        <div class="grid grid-cols-5 gap-1.5 font-mono text-xs">
                            <button
                                v-for="i in 10"
                                :key="`tac-opt-${i}`"
                                @click="selectTacChannel(`TAC_${i}`)"
                                :class="
                                    activeTacChannel === `TAC_${i}`
                                        ? 'bg-blue-600 text-white border-blue-500 font-bold shadow'
                                        : 'bg-slate-950 text-slate-300 border-slate-800 hover:bg-slate-800 hover:text-white'
                                "
                                class="px-1 py-1.5 rounded border transition-colors cursor-pointer flex flex-col items-center justify-center leading-none gap-0.5"
                                :title="`Alokasikan ke Radio TAC ${i}`"
                            >
                                <span class="text-[9px] font-bold tracking-tight opacity-75">TAC</span>
                                <span class="text-xs sm:text-sm font-extrabold font-mono">{{ i }}</span>
                            </button>
                        </div>

                        <!-- UNASSIGN / REMOVE TAC BUTTON -->
                        <div
                            v-if="activeTacChannel"
                            class="pt-2 border-t border-slate-800"
                        >
                            <button
                                @click="removeTacChannel"
                                class="w-full py-1.5 bg-rose-950/80 hover:bg-rose-900 text-rose-300 border border-rose-800/80 rounded-md font-mono text-[11px] font-semibold transition-colors text-center cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <span>✕ Lepas dari Radio TAC</span>
                            </button>
                        </div>
                    </div>
                </div>

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

                <!-- PAUSE / PLAY ICON BUTTON (2nd from right) -->
                <button
                    @click="emit('toggle-stop-feed')"
                    :class="
                        isStreamStopped
                            ? 'bg-slate-800 text-amber-400 border-slate-700'
                            : 'bg-slate-900 text-slate-400 hover:bg-slate-800 hover:text-slate-200 border-slate-800'
                    "
                    class="p-1.5 rounded-md border transition-colors flex items-center justify-center shrink-0 cursor-pointer"
                    :title="
                        isStreamStopped
                            ? 'Putar Video (Play)'
                            : 'Pause Video (Stop Feed)'
                    "
                >
                    <img
                        :src="isStreamStopped ? iconPlayAll : iconPause"
                        class="w-3.5 h-3.5 invert opacity-75 hover:opacity-100"
                        :alt="isStreamStopped ? 'Play' : 'Pause'"
                    />
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
            <template v-if="!isStreamStopped && !isStreamUnavailable">
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
            <template v-else-if="isStreamUnavailable">
                <img
                    :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                    :alt="stream.title"
                    class="w-full h-full object-cover opacity-20 blur-sm"
                />
                <div
                    class="absolute inset-0 bg-slate-950/90 flex flex-col items-center justify-center p-6 z-10 space-y-3.5 text-center"
                >
                    <div
                        class="w-12 h-12 rounded-full bg-rose-950/80 border border-rose-800/80 flex items-center justify-center text-rose-400"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>

                    <div class="space-y-1 max-w-md">
                        <span
                            class="text-xs font-mono font-bold text-rose-400 uppercase tracking-wider"
                        >
                            {{
                                streamErrorCode === 101 ||
                                streamErrorCode === 150
                                    ? "🔒 SIARAN MEMBERS-ONLY / PRIVAT"
                                    : "🔴 SIARAN TIDAK TERSEDIA"
                            }}
                        </span>
                        <h3 class="text-sm font-bold text-slate-100 font-mono">
                            {{
                                streamErrorCode === 101 ||
                                streamErrorCode === 150
                                    ? "Akses Terbatas Khusus Anggota Channel"
                                    : "Video YouTube Telah Dihapus atau Tidak Dapat Dimuat"
                            }}
                        </h3>
                        <p
                            class="text-xs text-slate-400 leading-relaxed font-sans"
                        >
                            {{
                                streamErrorCode === 101 ||
                                streamErrorCode === 150
                                    ? "Siaran ini disetting khusus Anggota (Members-Only) oleh streamer. Anda harus membuka langsung di YouTube dengan akun terdaftar keanggotaan."
                                    : "Siaran patroli ini tidak lagi tersedia di YouTube (mungkin telah diakhiri, dihapus, atau dibatasi untuk pemutar embed)."
                            }}
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-2 pt-2 flex-wrap justify-center font-mono text-xs"
                    >
                        <a
                            :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                            target="_blank"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>Buka Langsung di YouTube</span>
                            <img
                                :src="iconExternal"
                                class="w-3.5 h-3.5 invert opacity-90"
                            />
                        </a>
                        <button
                            @click="isStreamUnavailable = false"
                            class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-md transition-colors cursor-pointer"
                        >
                            Muat Ulang
                        </button>
                        <button
                            @click="emit('close-focus')"
                            class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-md transition-colors cursor-pointer"
                        >
                            Kembali ke Grid
                        </button>
                    </div>
                </div>
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
            <!-- LEFT SIDE: PROFILE FOTO, NAMA OFFICER, YOUTUBE HANDLE & CALLSIGN ONLY -->
            <div class="flex items-center space-x-3 min-w-0 flex-1">
                <!-- Foto Profile Avatar -->
                <img
                    :src="
                        officerAvatar ||
                        `https://api.dicebear.com/7.x/bottts/svg?seed=${encodeURIComponent(officerCallsign || officerName || 'officer')}`
                    "
                    :alt="officerName"
                    referrerpolicy="no-referrer"
                    @error="handleAvatarError"
                    class="w-10 h-10 sm:w-11 sm:h-11 rounded-full object-cover border border-slate-700 shrink-0"
                />

                <!-- Info Block: Nama Officer, Callsign & YouTube Handle -->
                <div class="min-w-0 flex-1">
                    <h2
                        class="text-sm sm:text-base font-bold text-slate-100 truncate tracking-tight leading-snug"
                        :title="officerName"
                    >
                        {{ officerName }}
                    </h2>

                    <div
                        class="flex items-center space-x-2 text-xs font-mono mt-0.5 truncate"
                    >
                        <span
                            v-if="officerCallsign"
                            class="px-1.5 py-0.5 text-[10px] font-mono font-bold text-slate-300 bg-slate-900 border border-slate-800 rounded shrink-0"
                        >
                            {{ officerCallsign }}
                        </span>
                        <a
                            v-if="youtubeHandle"
                            :href="`https://www.youtube.com/${youtubeHandle}`"
                            target="_blank"
                            class="text-blue-400 hover:text-blue-300 hover:underline truncate"
                            :title="youtubeHandle"
                        >
                            {{ youtubeHandle }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE: ACTION CONTROLS (Tandai Aksi [Desktop], Mode Bioskop, Live Chat, Buka di YouTube) -->
            <div
                class="flex items-center space-x-1.5 flex-wrap gap-y-1.5 shrink-0"
            >
                <!-- Tandai Aksi / Clip Button (Desktop Only) -->
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
                    class="hidden lg:inline-flex px-2.5 py-1.5 rounded-md border transition-colors items-center gap-1.5 cursor-pointer font-mono text-xs"
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

                <!-- Live Chat Toggle Button (Di Kanan Mode Bioskop) -->
                <button
                    @click="emit('toggle-right-chat')"
                    :class="
                        isRightChatOpen
                            ? 'bg-blue-950 text-blue-300 border-blue-800 font-semibold'
                            : 'bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border-slate-800'
                    "
                    class="px-2.5 py-1.5 rounded-md border transition-colors flex items-center gap-1.5 cursor-pointer font-mono text-xs"
                    title="Toggle Live Chat"
                >
                    <img
                        :src="iconChat"
                        class="w-3.5 h-3.5 invert opacity-70"
                        alt="Live Chat"
                    />
                    <span>Live Chat</span>
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

        <!-- 4. MOBILE-ONLY LIVE CHAT (DIRECTLY BELOW OFFICER PROFILE, EDGE-TO-EDGE, NO PADDING, NO HEADER) -->
        <div
            v-if="isRightChatOpen"
            class="block lg:hidden bg-slate-950 border-t border-slate-800 w-full p-0 overflow-hidden"
        >
            <div
                class="w-full h-[480px] bg-slate-900 relative"
            >
                <iframe
                    :key="`mobile-chat-${stream.video_id}`"
                    :src="`https://www.youtube.com/live_chat?v=${stream.video_id}&embed_domain=${computedChatEmbedDomain}&dark_theme=1`"
                    class="w-full h-full border-0 block"
                    allow="autoplay"
                ></iframe>
            </div>
        </div>

        <!-- 5. TAB SECTOR (ALWAYS VISIBLE ON DESKTOP `lg:block`, TOGGLEABLE ON MOBILE) -->
        <div
            :class="{ 'hidden lg:block': isRightChatOpen }"
            class="bg-slate-900 p-4 space-y-4 border-t border-slate-800"
        >
            <!-- TAB BAR NAVIGATION (2 TABS: DESKRIPSI STREAMER & KUMPULAN AKSI) -->
            <div
                class="flex items-center space-x-2 border-b border-slate-800 pb-2 overflow-x-auto text-xs font-medium"
            >
                <!-- TAB 1: DESKRIPSI STREAMER -->
                <button
                    @click="activeFocusTab = 'description'"
                    :class="
                        activeFocusTab === 'description' || activeFocusTab === 'chat'
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
                    <span>Kumpulan Klip Aksi</span>
                </button>
            </div>

            <!-- TAB CONTENT -->
            <div class="pt-1">
                <!-- DESKRIPSI STREAMER CONTENT -->
                <div
                    v-if="activeFocusTab === 'description' || activeFocusTab === 'chat'"
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
                    :customClips="customClipsList"
                    @delete-clip="handleDeleteClip"
                    @open-create-clip="activeFocusTab = 'create-clip'"
                />

                <!-- TAB INLINE FORM: TANDAI AKSI BARU -->
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
                        <!-- LEFT SIDE: STUDIO CLIPPER PRO INVITATION CARD -->
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
                                        Tertarik Jadi Clipper IME Police?
                                    </h4>
                                    <p
                                        class="text-[11px] text-slate-400 mt-1 leading-relaxed"
                                    >
                                        Buka fitur pemotong video dengan pilihan
                                        resolusi hingga 1080p dan maksimal 10
                                        menit yang khusus untuk officer dan
                                        clipper IME Police.
                                    </p>
                                </div>
                            </div>

                            <button
                                @click="openStudioClipper"
                                :class="
                                    canTrimVideo
                                        ? 'bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white'
                                        : 'bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700'
                                "
                                class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md text-xs font-semibold transition-colors cursor-pointer"
                                :title="
                                    canTrimVideo
                                        ? 'Buka Studio Clipper Pro'
                                        : 'Akses Terbatas (Diperlukan Role Clipper / Admin)'
                                "
                            >
                                <span
                                    v-if="!canTrimVideo"
                                    class="text-amber-400"
                                    >🔒</span
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
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <label
                                        class="block text-slate-300 font-medium text-xs"
                                        >Judul Momen Aksi / Kejadian *</label
                                    >
                                    <span
                                        class="text-[10px] font-mono text-slate-500"
                                        >{{ newClipTitle.length }}/100</span
                                    >
                                </div>
                                <input
                                    v-model="newClipTitle"
                                    type="text"
                                    maxlength="100"
                                    placeholder="Contoh: High Speed Pursuit di Freeway & PIT Maneuver"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-1.5 text-slate-100 focus:border-blue-500 outline-none text-xs transition-colors"
                                />
                            </div>

                            <!-- ROW 2: LIVE TIME QUICK SETTER & PRESET + STEMPEL WAKTU -->
                            <div class="space-y-2.5">
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
                                                >Mulai (HH:MM:SS)</label
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
                                            placeholder="00:00:00"
                                            class="w-full bg-slate-950 border border-slate-800 rounded-md px-2 py-1 text-slate-100 focus:border-blue-500 text-center font-mono font-semibold text-xs transition-colors"
                                        />
                                    </div>

                                    <div class="sm:col-span-4">
                                        <div
                                            class="flex items-center justify-between mb-1"
                                        >
                                            <label
                                                class="text-slate-400 text-[11px] font-mono"
                                                >Selesai (HH:MM:SS)</label
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
                                            placeholder="00:01:00"
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
                                        maxlength="30"
                                        placeholder="Panggilan Anda (Opsional, Maks 30 Karakter)"
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
