<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";

const props = defineProps({
    src: {
        type: String,
        default: "",
    },
    youtubeId: {
        type: String,
        default: "",
    },
    poster: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "",
    },
    autoplay: {
        type: Boolean,
        default: false,
    },
    muted: {
        type: Boolean,
        default: false,
    },
    startSeconds: {
        type: Number,
        default: 0,
    },
    endSeconds: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["timeupdate", "ended", "muted-change"]);

const videoRef = ref(null);
const containerRef = ref(null);
const ytIframeRef = ref(null);

const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const isMuted = ref(props.muted);

watch(
    () => props.muted,
    (newVal) => {
        isMuted.value = newVal;
        if (computedYoutubeId.value) {
            sendYTCommand(newVal ? "mute" : "unMute");
        }
    },
);
const playbackRate = ref(1);
const isFullscreen = ref(false);
const showControls = ref(true);

let controlsTimeout = null;
let ytPollTimer = null;
const iframeId = `yt-custom-player-${Math.random().toString(36).substring(2, 9)}`;

// Extract YouTube ID if src is a YouTube URL
const computedYoutubeId = computed(() => {
    if (props.youtubeId) return props.youtubeId;
    if (!props.src) return "";
    const regExp =
        /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = props.src.match(regExp);
    return match && match[2].length === 11 ? match[2] : "";
});

const effectiveStart = computed(() => props.startSeconds || 0);

const isSegmentMode = computed(() => {
    return props.endSeconds > 0 && props.endSeconds > effectiveStart.value;
});

const segmentDuration = computed(() => {
    if (isSegmentMode.value) {
        return Math.max(1, props.endSeconds - effectiveStart.value);
    }
    return duration.value || 0;
});

const displayCurrentTime = computed(() => {
    if (isSegmentMode.value) {
        return Math.max(
            0,
            Math.min(
                currentTime.value - effectiveStart.value,
                segmentDuration.value,
            ),
        );
    }
    return currentTime.value;
});

const displayDuration = computed(() => {
    if (isSegmentMode.value) {
        return segmentDuration.value;
    }
    return duration.value;
});

const handleScrubInput = (val) => {
    const scrubVal = parseFloat(val) || 0;
    if (isSegmentMode.value) {
        const targetAbs = effectiveStart.value + scrubVal;
        seek(targetAbs);
    } else {
        seek(scrubVal);
    }
};

// Format seconds into HH:MM:SS or MM:SS
const formatTime = (sec) => {
    if (isNaN(sec) || sec === null || sec < 0) return "00:00";
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = Math.floor(sec % 60);

    const pad = (num) => String(num).padStart(2, "0");

    if (h > 0) {
        return `${pad(h)}:${pad(m)}:${pad(s)}`;
    }
    return `${pad(m)}:${pad(s)}`;
};

const sendYTCommand = (func, args = []) => {
    if (ytIframeRef.value && ytIframeRef.value.contentWindow) {
        try {
            ytIframeRef.value.contentWindow.postMessage(
                JSON.stringify({
                    event: "command",
                    func: func,
                    args: args,
                }),
                "*",
            );
        } catch (e) {}
    }
};

const togglePlay = () => {
    if (computedYoutubeId.value) {
        if (isPlaying.value) {
            sendYTCommand("pauseVideo");
            isPlaying.value = false;
        } else {
            sendYTCommand("playVideo");
            isPlaying.value = true;
        }
        return;
    }

    if (!videoRef.value) return;
    if (videoRef.value.paused) {
        videoRef.value.play();
    } else {
        videoRef.value.pause();
    }
};

const onPlay = () => {
    isPlaying.value = true;
};

const onPause = () => {
    isPlaying.value = false;
};

const onTimeUpdate = () => {
    if (videoRef.value) {
        currentTime.value = videoRef.value.currentTime;
        emit("timeupdate", currentTime.value);
    }
};

const onLoadedMetadata = () => {
    if (videoRef.value) {
        duration.value = videoRef.value.duration;
    }
};

const seek = (seconds) => {
    const target = Math.max(0, Math.min(seconds, duration.value || 999999));
    currentTime.value = target;
    emit("timeupdate", target);

    if (computedYoutubeId.value) {
        sendYTCommand("seekTo", [target, true]);
    } else if (videoRef.value) {
        videoRef.value.currentTime = target;
    }
};

const seekRelative = (delta) => {
    seek(currentTime.value + delta);
};

const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (computedYoutubeId.value) {
        sendYTCommand(isMuted.value ? "mute" : "unMute");
        if (!isMuted.value) {
            sendYTCommand("setVolume", [volume.value > 0 ? volume.value * 100 : 100]);
        }
    } else if (videoRef.value) {
        videoRef.value.muted = isMuted.value;
    }
};

const setVolume = (val) => {
    volume.value = parseFloat(val);
    isMuted.value = volume.value === 0;

    if (computedYoutubeId.value) {
        sendYTCommand("setVolume", [volume.value * 100]);
        if (volume.value === 0) sendYTCommand("mute");
        else sendYTCommand("unMute");
    } else if (videoRef.value) {
        videoRef.value.volume = volume.value;
        videoRef.value.muted = isMuted.value;
    }
};

const setPlaybackRate = (rate) => {
    playbackRate.value = rate;
    if (computedYoutubeId.value) {
        sendYTCommand("setPlaybackRate", [rate]);
    } else if (videoRef.value) {
        videoRef.value.playbackRate = rate;
    }
};

const toggleFullscreen = () => {
    if (!containerRef.value) return;
    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen().catch((err) => {
            console.warn("Fullscreen request error:", err);
        });
    } else {
        document.exitFullscreen().catch((err) => {
            console.warn("Exit fullscreen error:", err);
        });
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

const handleMouseMove = () => {
    showControls.value = true;
    clearTimeout(controlsTimeout);
    if (isPlaying.value) {
        controlsTimeout = setTimeout(() => {
            showControls.value = false;
        }, 3000);
    }
};

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
            currentTime.value = info.currentTime;
            emit("timeupdate", currentTime.value);
            if (
                isSegmentMode.value &&
                props.endSeconds &&
                currentTime.value >= props.endSeconds - 0.5
            ) {
                seek(effectiveStart.value);
                if (computedYoutubeId.value) sendYTCommand("playVideo");
            }
        }
        if (info && typeof info.duration === "number" && info.duration > 0) {
            duration.value = info.duration;
        }
        if (info && typeof info.playerState === "number") {
            if (info.playerState === 1) {
                isPlaying.value = true;
            } else if (info.playerState === 2 || info.playerState === 0) {
                isPlaying.value = false;
                if (isSegmentMode.value && info.playerState === 0) {
                    seek(effectiveStart.value);
                    if (computedYoutubeId.value) sendYTCommand("playVideo");
                }
            }
        }
    }
};

onMounted(() => {
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    window.addEventListener("message", handleYTMessage);

    ytPollTimer = setInterval(() => {
        if (computedYoutubeId.value && ytIframeRef.value) {
            try {
                ytIframeRef.value.contentWindow.postMessage(
                    JSON.stringify({
                        event: "listening",
                        id: 1,
                        channel: "widget",
                    }),
                    "*",
                );
                sendYTCommand("getCurrentTime");
                sendYTCommand("getDuration");
            } catch (e) {}
        }
    }, 350);

    if (props.autoplay && videoRef.value) {
        videoRef.value.play().catch(() => {});
    }
});

onUnmounted(() => {
    document.removeEventListener("fullscreenchange", handleFullscreenChange);
    window.removeEventListener("message", handleYTMessage);
    clearInterval(ytPollTimer);
    clearTimeout(controlsTimeout);
});

watch(
    () => [props.src, props.youtubeId],
    () => {
        currentTime.value = 0;
        isPlaying.value = false;
    },
);

defineExpose({
    seek,
    seekRelative,
    togglePlay,
    currentTime,
    duration,
    isPlaying,
});
</script>

<template>
    <div
        ref="containerRef"
        class="relative w-full h-full rounded-xl overflow-hidden bg-black border border-slate-800 group select-none flex items-center justify-center"
        @mousemove="handleMouseMove"
        @mouseleave="showControls = false"
    >
        <!-- YouTube iFrame Background (Portrait Zoomed on Mobile, 16:9 on Desktop) -->
        <template v-if="computedYoutubeId">
            <div
                class="absolute inset-0 overflow-hidden flex items-center justify-center pointer-events-none"
            >
                <iframe
                    ref="ytIframeRef"
                    :id="iframeId"
                    :src="`https://www.youtube-nocookie.com/embed/${computedYoutubeId}?enablejsapi=1&controls=0&disablekb=1&modestbranding=1&showinfo=0&rel=0&iv_load_policy=3&fs=0&playsinline=1&autoplay=${autoplay ? 1 : 0}&mute=${isMuted ? 1 : 0}${startSeconds ? '&start=' + startSeconds : ''}${endSeconds ? '&end=' + endSeconds : ''}`"
                    class="w-[220%] h-[220%] max-w-none scale-150 origin-center sm:w-full sm:h-full sm:max-w-full sm:scale-100 border-0 pointer-events-none select-none transition-all duration-300"
                    style="pointer-events: none !important; touch-action: none;"
                    allow="
                        accelerometer;
                        autoplay;
                        clipboard-write;
                        encrypted-media;
                        gyroscope;
                        picture-in-picture;
                    "
                ></iframe>
            </div>
            <!-- Transparent Click Capture Layer over YouTube iframe -->
            <div
                class="absolute inset-0 z-10 cursor-pointer"
                @click="togglePlay"
                @touchstart.prevent="togglePlay"
            ></div>
        </template>

        <!-- HTML5 Native Video Tag fallback for MP4 -->
        <template v-else>
            <video
                ref="videoRef"
                :src="src"
                :poster="poster"
                class="w-full h-full object-contain cursor-pointer"
                @click="togglePlay"
                @play="onPlay"
                @pause="onPause"
                @timeupdate="onTimeUpdate"
                @loadedmetadata="onLoadedMetadata"
                @ended="$emit('ended')"
                playsinline
            ></video>
        </template>

        <!-- Big Center Play Button Overlay (Visible when paused) -->
        <div
            v-if="!isPlaying"
            @click="togglePlay"
            class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-[2px] transition-opacity cursor-pointer z-15"
        >
            <button
                class="w-16 h-16 rounded-full bg-blue-600/90 hover:bg-blue-500 text-white flex items-center justify-center shadow-2xl shadow-blue-500/50 transform transition hover:scale-110 active:scale-95 cursor-pointer border border-blue-400/40"
            >
                <svg class="w-8 h-8 fill-current ml-1" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
            </button>
        </div>

        <!-- Floating Tap to Unmute Badge (Visible when video is playing muted) -->
        <div
            v-if="isMuted && isPlaying"
            @click.stop="toggleMute"
            class="absolute top-3 right-3 z-30 bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 text-rose-400 hover:text-rose-300 px-3 py-1.5 rounded-full text-xs font-mono shadow-xl flex items-center gap-2 cursor-pointer backdrop-blur-md transition transform active:scale-95"
            title="Klik untuk membuka suara"
        >
            <svg class="w-3.5 h-3.5 fill-current animate-pulse" viewBox="0 0 24 24">
                <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73 4.27 3zM12 4L9.91 6.09 12 8.18V4z" />
            </svg>
            <span>Buka Suara 🔇</span>
        </div>

        <!-- Custom Player Controls Bar -->
        <div
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-slate-950 via-slate-950/90 to-transparent p-3 pt-6 space-y-2 transition-opacity duration-300 z-20"
            :class="
                showControls || !isPlaying
                    ? 'opacity-100'
                    : 'opacity-0 pointer-events-none'
            "
            @click.stop
        >
            <!-- Custom Scrubber Progress Bar -->
            <div
                class="relative w-full h-2 flex items-center cursor-pointer group/slider"
            >
                <input
                    type="range"
                    min="0"
                    :max="displayDuration || 100"
                    step="0.1"
                    :value="displayCurrentTime"
                    @input="handleScrubInput($event.target.value)"
                    class="w-full h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer focus:outline-none accent-blue-500"
                />
            </div>

            <!-- Controls Row: Play/Pause, Time Display, Volume, Speed, Fullscreen -->
            <div
                class="flex items-center justify-between text-xs font-mono text-slate-200 flex-wrap gap-2"
            >
                <!-- Left Action Controls -->
                <div class="flex items-center gap-2">
                    <button
                        @click="togglePlay"
                        class="p-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 transition cursor-pointer"
                        :title="isPlaying ? 'Pause' : 'Play'"
                    >
                        <svg
                            v-if="isPlaying"
                            class="w-4 h-4 fill-current"
                            viewBox="0 0 24 24"
                        >
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                        </svg>
                        <svg
                            v-else
                            class="w-4 h-4 fill-current ml-0.5"
                            viewBox="0 0 24 24"
                        >
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </button>

                    <!-- Time Counter -->
                    <span class="text-[11px] text-slate-400 font-mono ml-1">
                        <span class="text-slate-100 font-bold">{{
                            formatTime(displayCurrentTime)
                        }}</span>
                        <span> / </span>
                        <span>{{ formatTime(displayDuration) }}</span>
                    </span>
                </div>

                <!-- Right Action Controls -->
                <div class="flex items-center gap-2">
                    <!-- Volume Controls -->
                    <div class="flex items-center gap-1.5 group/vol">
                        <button
                            @click="toggleMute"
                            class="p-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition cursor-pointer"
                            :title="isMuted ? 'Unmute' : 'Mute'"
                        >
                            <svg
                                v-if="isMuted || volume === 0"
                                class="w-4 h-4 fill-current text-rose-400"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73 4.27 3zM12 4L9.91 6.09 12 8.18V4z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4 fill-current text-emerald-400"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"
                                />
                            </svg>
                        </button>
                        <input
                            type="range"
                            min="0"
                            max="1"
                            step="0.05"
                            :value="isMuted ? 0 : volume"
                            @input="setVolume($event.target.value)"
                            class="w-16 h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-blue-500 hidden sm:block"
                        />
                    </div>

                    <!-- Playback Speed Selector -->
                    <select
                        :value="playbackRate"
                        @change="
                            setPlaybackRate(parseFloat($event.target.value))
                        "
                        class="bg-slate-900 text-slate-300 border border-slate-800 text-[11px] font-mono rounded-md px-1.5 py-1 focus:outline-none cursor-pointer"
                        title="Kecepatan Putar (Playback Speed)"
                    >
                        <option :value="0.5">0.5x</option>
                        <option :value="1">1.0x</option>
                        <option :value="1.25">1.25x</option>
                        <option :value="1.5">1.5x</option>
                        <option :value="2">2.0x</option>
                    </select>

                    <!-- Fullscreen Button -->
                    <button
                        @click="toggleFullscreen"
                        class="p-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition cursor-pointer"
                        title="Layar Penuh (Fullscreen)"
                    >
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
