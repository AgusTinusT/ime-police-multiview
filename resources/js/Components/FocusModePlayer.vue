<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "vue-sonner";
import {
    X,
    Scissors,
    RectangleHorizontal,
    Radio,
    Volume2,
    VolumeX,
    Pause,
    Play,
    ExternalLink,
    MessageSquare,
    AlertTriangle,
    Info,
    Video,
    Lock,
    ArrowRight,
    Clock,
} from "@lucide/vue";

import CommunityClipsTab from "@/Components/CommunityClipsTab.vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { DeptBadge, OfficerAvatar } from "@/Components/tactical";

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

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const activeFocusTab = ref("chat");

const computedChatEmbedDomain = computed(() => {
    if (props.chatEmbedDomain) return props.chatEmbedDomain;
    if (typeof window !== "undefined") {
        return window.location.hostname;
    }
    return "localhost";
});

const isTacPopoverOpen = ref(false);

const selectTacChannel = (tacCode) => {
    emit("assign-tac", tacCode);
    isTacPopoverOpen.value = false;
};

const removeTacChannel = () => {
    emit("remove-tac");
    isTacPopoverOpen.value = false;
};

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
    { immediate: true },
);

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

const isStreamUnavailable = ref(false);
const streamErrorCode = ref(null);

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
            (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0)
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
        toast.warning("Harap masukkan judul klip momen aksi!");
        return;
    }

    const startSec = parseTimestamp(newClipStartTime.value);
    const endSec = parseTimestamp(newClipEndTime.value);

    if (endSec <= startSec) {
        toast.warning("Waktu Selesai harus lebih besar dari Waktu Mulai.");
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
            officer_id:
                props.stream?.officer?.id || props.stream?.officer_id || null,
            officer_name:
                props.stream?.officer?.officer_name ||
                props.stream?.officer?.name ||
                null,
            officer_handle: props.stream?.officer?.handle || null,
            creator_name: authorNameFinal,
        });

        if (response.data && response.data.clip) {
            customClipsList.value.unshift(response.data.clip);
        }
    } catch (err) {
        toast.error(
            err.response?.data?.message ||
                "Gagal menyimpan klip momen aksi ke database.",
        );
        return;
    }

    newClipTitle.value = "";
    newClipAuthor.value = authUser.value?.name || "";
    activeFocusTab.value = "clips";
};

const canTrimVideo = computed(() => {
    if (!authUser.value) return false;
    return (
        !!authUser.value.can_trim_video ||
        ["admin", "clipper"].includes(authUser.value.role)
    );
});

const openStudioClipper = () => {
    if (!authUser.value) {
        toast.warning(
            "Akses Studio Clipper Terbatas! Silakan login terlebih dahulu untuk mengakses studio pemotong video.",
        );
        return;
    }

    if (!canTrimVideo.value) {
        toast.warning(
            `Akun Anda (${authUser.value.name}) belum memiliki hak akses Studio Clipper Pro (Diperlukan Role: Clipper atau Admin).`,
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
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-primary underline hover:text-primary/80">$1</a>',
    );
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

const officerAvatar = computed(() => {
    return props.stream?.officer?.avatar_url || "";
});

const youtubeHandle = computed(() => {
    const h = props.stream?.officer?.handle;
    if (!h) return null;
    return h.startsWith("@") ? h : `@${h}`;
});
</script>

<template>
    <div
        class="bg-card rounded-xl border border-border overflow-hidden font-sans shadow-xl"
    >
        <!-- 1. TOP HEADER BAR -->
        <div
            class="bg-background px-3.5 py-2.5 border-b border-border flex items-center justify-between flex-wrap gap-2.5"
        >
            <!-- LEFT SIDE: DEPARTMENT BADGE & STREAM TITLE -->
            <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                <DeptBadge :dept="officerDepartment" class="shrink-0" />

                <h1
                    class="text-xs sm:text-sm font-medium text-foreground truncate max-w-xs sm:max-w-md md:max-w-lg lg:max-w-2xl"
                    :title="stream?.title || 'Siaran Patroli Taktis'"
                >
                    {{ stream?.title || "Siaran Patroli Taktis" }}
                </h1>
            </div>

            <!-- RIGHT SIDE BUTTONS (GRID, PAUSE, AUDIO, TAC) -->
            <div class="flex items-center space-x-1.5 shrink-0">
                <!-- TAC BUTTON WITH INTERACTIVE DROPDOWN POPOVER -->
                <div class="relative">
                    <Button
                        type="button"
                        size="sm"
                        :variant="activeTacChannel ? 'secondary' : 'outline'"
                        @click="isTacPopoverOpen = !isTacPopoverOpen"
                        :class="
                            activeTacChannel
                                ? 'bg-primary/15 text-primary border-primary/50 font-bold'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        class="h-8 px-2.5 gap-1.5 text-xs font-mono"
                        title="Pilih / Kelola Saluran Radio TAC"
                    >
                        <Radio class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">{{
                            activeTacChannel
                                ? activeTacChannel.replace("_", " ")
                                : "Radio TAC"
                        }}</span>
                    </Button>

                    <!-- TAC CHANNEL SELECTION DROPDOWN POPOVER -->
                    <div
                        v-if="isTacPopoverOpen"
                        class="absolute right-0 mt-2 w-72 sm:w-80 bg-popover border border-border rounded-xl p-3 shadow-2xl z-50 space-y-3 font-sans text-xs animate-in fade-in zoom-in-95 duration-150"
                    >
                        <div
                            class="flex items-center justify-between border-b border-border pb-2"
                        >
                            <div class="flex items-center space-x-1.5">
                                <span
                                    class="w-2 h-2 rounded-full bg-success motion-safe:animate-pulse"
                                ></span>
                                <span
                                    class="font-semibold text-foreground font-mono text-xs"
                                    >Radio Taktis TAC</span
                                >
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Tutup Popover TAC"
                                @click="isTacPopoverOpen = false"
                                class="h-6 w-6 text-muted-foreground hover:text-foreground"
                            >
                                <X class="w-3.5 h-3.5" />
                            </Button>
                        </div>

                        <p class="text-xs text-muted-foreground leading-snug">
                            Pilih saluran radio TAC 1 - 10 untuk menghubungkan
                            stream perwira ini:
                        </p>

                        <!-- 10 TAC CHANNELS GRID (2 ROWS X 5 COLS: TAC 1 - TAC 10) -->
                        <div class="grid grid-cols-5 gap-1.5 font-mono text-xs">
                            <button
                                v-for="i in 10"
                                :key="`tac-opt-${i}`"
                                type="button"
                                @click="selectTacChannel(`TAC_${i}`)"
                                :class="
                                    activeTacChannel === `TAC_${i}`
                                        ? 'bg-primary text-primary-foreground border-primary font-bold shadow-xs'
                                        : 'bg-background text-foreground border-border hover:bg-muted'
                                "
                                class="px-1 py-1.5 rounded-sm border transition-colors cursor-pointer flex flex-col items-center justify-center leading-none gap-0.5"
                                :title="`Alokasikan ke Radio TAC ${i}`"
                            >
                                <span
                                    class="text-xs font-bold tracking-tight opacity-75"
                                    >TAC</span
                                >
                                <span
                                    class="text-xs sm:text-sm font-extrabold font-mono"
                                    >{{ i }}</span
                                >
                            </button>
                        </div>

                        <!-- UNASSIGN / REMOVE TAC BUTTON -->
                        <div
                            v-if="activeTacChannel"
                            class="pt-2 border-t border-border"
                        >
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                @click="removeTacChannel"
                                class="w-full h-8 font-mono text-xs font-semibold gap-1.5"
                            >
                                <X class="w-3.5 h-3.5" />
                                <span>Lepas dari Radio TAC</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- AUDIO BUTTON (3rd from right) -->
                <Button
                    type="button"
                    size="sm"
                    :variant="
                        activeAudioVideoId === stream.video_id
                            ? 'secondary'
                            : 'outline'
                    "
                    @click="emit('toggle-audio')"
                    class="h-8 px-2.5 gap-1.5 text-xs font-mono"
                    title="Toggle Audio"
                >
                    <Volume2
                        v-if="activeAudioVideoId === stream.video_id"
                        class="w-3.5 h-3.5 text-success"
                    />
                    <VolumeX v-else class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">{{
                        activeAudioVideoId === stream.video_id
                            ? "Audio"
                            : "Mute"
                    }}</span>
                </Button>

                <!-- PAUSE / PLAY ICON BUTTON (2nd from right) -->
                <Button
                    type="button"
                    size="icon-sm"
                    :variant="isStreamStopped ? 'secondary' : 'outline'"
                    @click="emit('toggle-stop-feed')"
                    :class="isStreamStopped ? 'text-warning' : ''"
                    class="h-8 w-8 shrink-0"
                    :aria-label="
                        isStreamStopped
                            ? 'Putar Video (Play)'
                            : 'Pause Video (Stop Feed)'
                    "
                    :title="
                        isStreamStopped
                            ? 'Putar Video (Play)'
                            : 'Pause Video (Stop Feed)'
                    "
                >
                    <Play v-if="isStreamStopped" class="w-3.5 h-3.5" />
                    <Pause v-else class="w-3.5 h-3.5" />
                </Button>

                <!-- GRID BUTTON (1st / Rightmost) -->
                <Button
                    type="button"
                    size="icon-sm"
                    variant="outline"
                    @click="emit('close-focus')"
                    class="h-8 w-8 shrink-0"
                    aria-label="Kembali ke Mode Grid (Tutup Mode Fokus)"
                    title="Kembali ke Mode Grid (Tutup Mode Fokus)"
                >
                    <X class="w-3.5 h-3.5" />
                </Button>
            </div>
        </div>

        <!-- 2. PEMUTAR VIDEO UTAMA -->
        <div class="relative w-full aspect-video bg-background overflow-hidden">
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
                    class="w-full h-full object-cover opacity-20 blur-xs"
                />
                <div
                    class="absolute inset-0 bg-background/90 flex flex-col items-center justify-center p-6 z-10 space-y-3.5 text-center"
                >
                    <div
                        class="w-12 h-12 rounded-full bg-destructive/15 border border-destructive/40 flex items-center justify-center text-destructive"
                    >
                        <AlertTriangle class="w-6 h-6" />
                    </div>

                    <div class="space-y-1 max-w-md">
                        <span
                            class="text-xs font-mono font-bold text-destructive uppercase tracking-wider"
                        >
                            {{
                                streamErrorCode === 101 ||
                                streamErrorCode === 150
                                    ? "SIARAN MEMBERS-ONLY / PRIVAT"
                                    : "SIARAN TIDAK TERSEDIA"
                            }}
                        </span>
                        <h3 class="text-sm font-bold text-foreground font-mono">
                            {{
                                streamErrorCode === 101 ||
                                streamErrorCode === 150
                                    ? "Akses Terbatas Khusus Anggota Channel"
                                    : "Video YouTube Telah Dihapus atau Tidak Dapat Dimuat"
                            }}
                        </h3>
                        <p
                            class="text-xs text-muted-foreground leading-relaxed font-sans"
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
                        <Button as-child size="sm" class="gap-1.5">
                            <a
                                :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                                target="_blank"
                            >
                                <span>Buka Langsung di YouTube</span>
                                <ExternalLink class="w-3.5 h-3.5" />
                            </a>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="isStreamUnavailable = false"
                        >
                            Muat Ulang
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="emit('close-focus')"
                        >
                            Kembali ke Grid
                        </Button>
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
                    class="absolute inset-0 bg-background/80 flex flex-col items-center justify-center p-4 z-10 space-y-3"
                >
                    <Badge variant="outline" class="font-mono text-xs px-3 py-1">
                        FEED DIHENTIKAN SEMENTARA
                    </Badge>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="emit('toggle-stop-feed')"
                        class="gap-2 text-xs"
                    >
                        <Play class="w-3.5 h-3.5" />
                        <span>Putar Video Kembali</span>
                    </Button>
                </div>
            </template>
        </div>

        <!-- 3. UNIFIED OFFICER PROFILE & ACTION CONTROLS BAR -->
        <div
            class="bg-background px-4 py-3 border-b border-border flex items-center justify-between flex-wrap gap-3"
        >
            <!-- LEFT SIDE: PROFILE FOTO, NAMA OFFICER, YOUTUBE HANDLE & CALLSIGN -->
            <div class="flex items-center space-x-3 min-w-0 flex-1">
                <OfficerAvatar
                    :src="officerAvatar"
                    :name="officerName"
                    :callsign="officerCallsign"
                    :dept="officerDepartment"
                    size="md"
                    class="shrink-0"
                />

                <div class="min-w-0 flex-1">
                    <h2
                        class="text-sm sm:text-base font-bold text-foreground truncate tracking-tight leading-snug"
                        :title="officerName"
                    >
                        {{ officerName }}
                    </h2>

                    <div
                        class="flex items-center space-x-2 text-xs font-mono mt-0.5 truncate"
                    >
                        <Badge
                            v-if="officerCallsign"
                            variant="outline"
                            class="px-1.5 py-0 text-xs font-mono font-bold shrink-0"
                        >
                            {{ officerCallsign }}
                        </Badge>
                        <a
                            v-if="youtubeHandle"
                            :href="`https://www.youtube.com/${youtubeHandle}`"
                            target="_blank"
                            class="text-primary hover:underline truncate"
                            :title="youtubeHandle"
                        >
                            {{ youtubeHandle }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE: ACTION CONTROLS -->
            <div
                class="flex items-center space-x-1.5 flex-wrap gap-y-1.5 shrink-0"
            >
                <!-- Tandai Aksi / Clip Button (Desktop Only) -->
                <Button
                    type="button"
                    size="sm"
                    :variant="
                        activeFocusTab === 'create-clip'
                            ? 'destructive'
                            : 'outline'
                    "
                    @click="
                        activeFocusTab =
                            activeFocusTab === 'create-clip'
                                ? 'clips'
                                : 'create-clip'
                    "
                    class="hidden lg:inline-flex h-8 px-2.5 gap-1.5 font-mono text-xs"
                    title="Tandai Momen Aksi (Buka Form Input di Bawah Video)"
                >
                    <Scissors class="w-3.5 h-3.5" />
                    <span>Tandai Aksi</span>
                </Button>

                <!-- Mode Bioskop (Theater Mode) Button -->
                <Button
                    type="button"
                    size="sm"
                    :variant="isTheaterMode ? 'secondary' : 'outline'"
                    @click="emit('toggle-theater-mode')"
                    class="h-8 px-2.5 gap-1.5 font-mono text-xs"
                    title="Toggle Mode Bioskop / Theater"
                >
                    <RectangleHorizontal class="w-3.5 h-3.5" />
                    <span>Mode Bioskop</span>
                </Button>

                <!-- Live Chat Toggle Button -->
                <Button
                    type="button"
                    size="sm"
                    :variant="isRightChatOpen ? 'secondary' : 'outline'"
                    @click="emit('toggle-right-chat')"
                    class="h-8 px-2.5 gap-1.5 font-mono text-xs"
                    title="Toggle Live Chat"
                >
                    <MessageSquare class="w-3.5 h-3.5" />
                    <span>Live Chat</span>
                </Button>

                <!-- External YouTube Link -->
                <Button
                    as-child
                    size="icon-sm"
                    variant="outline"
                    class="h-8 w-8"
                    title="Buka di YouTube"
                    aria-label="Buka di YouTube"
                >
                    <a
                        :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                        target="_blank"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                </Button>
            </div>
        </div>

        <!-- 4. MOBILE-ONLY LIVE CHAT -->
        <div
            v-if="isRightChatOpen"
            class="block lg:hidden bg-background border-t border-border w-full p-0 overflow-hidden"
        >
            <div class="w-full h-[480px] bg-card relative">
                <iframe
                    :key="`mobile-chat-${stream.video_id}`"
                    :src="`https://www.youtube.com/live_chat?v=${stream.video_id}&embed_domain=${computedChatEmbedDomain}&dark_theme=1`"
                    class="w-full h-full border-0 block"
                    allow="autoplay"
                ></iframe>
            </div>
        </div>

        <!-- 5. TAB SECTOR -->
        <div
            :class="{ 'hidden lg:block': isRightChatOpen }"
            class="bg-card p-4 space-y-4 border-t border-border"
        >
            <!-- TAB BAR NAVIGATION -->
            <div
                class="flex items-center space-x-2 border-b border-border pb-2 overflow-x-auto text-xs font-medium"
            >
                <Button
                    type="button"
                    size="sm"
                    :variant="
                        activeFocusTab === 'description' ||
                        activeFocusTab === 'chat'
                            ? 'secondary'
                            : 'outline'
                    "
                    @click="activeFocusTab = 'description'"
                    class="h-8 px-3.5 gap-2 shrink-0 text-xs"
                >
                    <Info class="w-3.5 h-3.5 text-muted-foreground" />
                    <span>Deskripsi Streamer</span>
                </Button>

                <Button
                    type="button"
                    size="sm"
                    :variant="activeFocusTab === 'clips' ? 'secondary' : 'outline'"
                    @click="activeFocusTab = 'clips'"
                    class="h-8 px-3.5 gap-2 shrink-0 text-xs"
                >
                    <Video class="w-3.5 h-3.5 text-muted-foreground" />
                    <span>Kumpulan Klip Aksi</span>
                </Button>
            </div>

            <!-- TAB CONTENT -->
            <div class="pt-1">
                <!-- DESKRIPSI STREAMER CONTENT -->
                <div
                    v-if="
                        activeFocusTab === 'description' ||
                        activeFocusTab === 'chat'
                    "
                    class="bg-background rounded-lg p-4 border border-border space-y-3 font-mono text-xs text-foreground"
                >
                    <div
                        class="flex items-center justify-between pb-2 border-b border-border text-muted-foreground"
                    >
                        <span class="font-semibold text-foreground"
                            >Deskripsi Resmi YouTube</span
                        >
                        <span>Video ID: {{ stream.video_id }}</span>
                    </div>

                    <div
                        v-if="stream.description || fetchedDescription"
                        class="whitespace-pre-wrap leading-relaxed wrap-break-word max-h-96 overflow-y-auto pr-2 scrollbar-thin select-text text-foreground"
                        v-html="
                            formatDescriptionWithLinks(
                                stream.description || fetchedDescription,
                            )
                        "
                    ></div>
                    <div
                        v-else
                        class="italic text-muted-foreground py-4 text-center"
                    >
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
                    class="bg-background rounded-xl p-4 border border-border space-y-4 font-sans text-xs text-foreground animate-in fade-in duration-200"
                >
                    <div
                        class="flex items-center justify-between pb-3 border-b border-border"
                    >
                        <div class="flex items-center space-x-2">
                            <Scissors class="w-4 h-4 text-muted-foreground" />
                            <span class="font-semibold text-foreground text-xs"
                                >Penandaan Momen Aksi Taktis</span
                            >
                            <span
                                class="text-xs font-mono text-muted-foreground hidden sm:inline"
                                >— Catat stempel waktu tanpa menutup video</span
                            >
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="activeFocusTab = 'clips'"
                            class="h-7 px-2.5 text-xs font-mono gap-1"
                        >
                            <X class="w-3 h-3" />
                            <span>Tutup Form</span>
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <!-- LEFT SIDE: STUDIO CLIPPER PRO INVITATION CARD -->
                        <div
                            class="md:col-span-4 bg-card border border-border rounded-xl p-4 flex flex-col justify-between space-y-4"
                        >
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full bg-primary"
                                        ></span>
                                        <span
                                            class="text-xs font-mono font-semibold text-foreground"
                                            >Studio Clipper Pro</span
                                        >
                                    </div>
                                    <Badge
                                        variant="outline"
                                        class="text-xs font-mono px-2 py-0"
                                    >
                                        BETA
                                    </Badge>
                                </div>

                                <div>
                                    <h4
                                        class="text-xs font-bold text-foreground tracking-tight"
                                    >
                                        Tertarik Jadi Clipper IME Police?
                                    </h4>
                                    <p
                                        class="text-xs text-muted-foreground mt-1 leading-relaxed"
                                    >
                                        Buka fitur pemotong video dengan pilihan
                                        resolusi hingga 1080p dan maksimal 10
                                        menit yang khusus untuk officer dan
                                        clipper IME Police.
                                    </p>
                                </div>
                            </div>

                            <Button
                                type="button"
                                :variant="canTrimVideo ? 'default' : 'secondary'"
                                size="sm"
                                @click="openStudioClipper"
                                class="w-full gap-2 text-xs font-semibold"
                                :title="
                                    canTrimVideo
                                        ? 'Buka Studio Clipper Pro'
                                        : 'Akses Terbatas (Diperlukan Role Clipper / Admin)'
                                "
                            >
                                <Lock
                                    v-if="!canTrimVideo"
                                    class="w-3.5 h-3.5 text-warning"
                                />
                                <span>Akses Studio Clipper</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Button>
                        </div>

                        <!-- RIGHT SIDE: MAIN FORM INPUTS (8 COLS) -->
                        <div
                            class="md:col-span-8 bg-card border border-border rounded-xl p-4 space-y-3"
                        >
                            <div>
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <label
                                        class="block text-foreground font-medium text-xs"
                                        >Judul Momen Aksi / Kejadian *</label
                                    >
                                    <span
                                        class="text-xs font-mono text-muted-foreground"
                                        >{{ newClipTitle.length }}/100</span
                                    >
                                </div>
                                <Input
                                    v-model="newClipTitle"
                                    type="text"
                                    maxlength="100"
                                    placeholder="Contoh: High Speed Pursuit di Freeway & PIT Maneuver"
                                    class="h-8 text-xs"
                                />
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    class="flex items-center justify-between bg-background border border-border rounded-md p-2 flex-wrap gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full bg-live motion-safe:animate-pulse"
                                        ></span>
                                        <span
                                            class="text-xs font-mono text-foreground"
                                            >Waktu Live Stream:
                                            <strong
                                                class="text-primary font-bold font-mono"
                                                >{{
                                                    formattedCurrentPlaybackTime
                                                }}</strong
                                            ></span
                                        >
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        @click="setStartFromCurrentTime"
                                        class="h-7 px-2.5 text-xs font-mono font-semibold gap-1.5"
                                        title="Set Waktu Mulai ke posisi video saat ini"
                                    >
                                        <Clock class="w-3.5 h-3.5" />
                                        <span
                                            >Ambil Waktu Live ({{
                                                formattedCurrentPlaybackTime
                                            }})</span
                                        >
                                    </Button>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end"
                                >
                                    <div class="sm:col-span-4">
                                        <label
                                            class="block text-muted-foreground text-xs font-mono mb-1"
                                            >Durasi Cepat</label
                                        >
                                        <div class="flex items-center gap-1.5">
                                            <Button
                                                type="button"
                                                size="sm"
                                                :variant="
                                                    newClipDurationPreset === 60
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                @click="setClipPreset(60)"
                                                class="flex-1 h-8 text-xs font-mono"
                                            >
                                                1 Min
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                :variant="
                                                    newClipDurationPreset ===
                                                    180
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                @click="setClipPreset(180)"
                                                class="flex-1 h-8 text-xs font-mono"
                                            >
                                                3 Min
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                :variant="
                                                    newClipDurationPreset ===
                                                    300
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                @click="setClipPreset(300)"
                                                class="flex-1 h-8 text-xs font-mono"
                                            >
                                                5 Min
                                            </Button>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-4">
                                        <div
                                            class="flex items-center justify-between mb-1"
                                        >
                                            <label
                                                class="text-muted-foreground text-xs font-mono"
                                                >Mulai (HH:MM:SS)</label
                                            >
                                            <button
                                                @click="setStartFromCurrentTime"
                                                type="button"
                                                class="text-xs text-primary hover:underline font-mono cursor-pointer"
                                            >
                                                Set Live
                                            </button>
                                        </div>
                                        <Input
                                            v-model="newClipStartTime"
                                            type="text"
                                            placeholder="00:00:00"
                                            class="h-8 text-center font-mono font-semibold text-xs"
                                        />
                                    </div>

                                    <div class="sm:col-span-4">
                                        <div
                                            class="flex items-center justify-between mb-1"
                                        >
                                            <label
                                                class="text-muted-foreground text-xs font-mono"
                                                >Selesai (HH:MM:SS)</label
                                            >
                                            <button
                                                @click="setEndFromCurrentTime"
                                                type="button"
                                                class="text-xs text-primary hover:underline font-mono cursor-pointer"
                                            >
                                                Set Live
                                            </button>
                                        </div>
                                        <Input
                                            v-model="newClipEndTime"
                                            type="text"
                                            placeholder="00:01:00"
                                            class="h-8 text-center font-mono font-semibold text-xs"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-2 pt-1 flex-wrap sm:flex-nowrap"
                            >
                                <div class="w-full sm:flex-1">
                                    <Input
                                        v-model="newClipAuthor"
                                        type="text"
                                        maxlength="30"
                                        placeholder="Panggilan Anda (Opsional, Maks 30 Karakter)"
                                        class="h-8 text-xs"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    @click="handleCreateClipSubmit"
                                    class="w-full sm:w-auto h-8 px-4 font-semibold text-xs shrink-0"
                                >
                                    Simpan Klip Momen
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
