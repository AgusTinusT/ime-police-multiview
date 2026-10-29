<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { Play, Pause, Volume2, VolumeX, Maximize } from "@lucide/vue";
import { Button } from "@/Components/ui/button";

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
    zoomMobile: {
        type: Boolean,
        default: false,
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

const computedYoutubeId = computed(() => {
    if (props.youtubeId) return props.youtubeId;
    if (!props.src) return "";
    const regExp =
        /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|live|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i;
    const match = props.src.match(regExp);
    return match && match[1] ? match[1] : "";
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
            sendYTCommand("setVolume", [
                volume.value > 0 ? volume.value * 100 : 100,
            ]);
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
    window.addEventListener("message", handleYTMessage);
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
        class="relative w-full h-full rounded-xl overflow-hidden bg-background border border-border group select-none flex items-center justify-center"
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
                    :class="[
                        'border-0 pointer-events-none select-none transition-all duration-300',
                        zoomMobile
                            ? 'w-[220%] h-[220%] max-w-none scale-150 origin-center sm:w-full sm:h-full sm:max-w-full sm:scale-100'
                            : 'w-full h-full max-w-full scale-100',
                    ]"
                    style="pointer-events: none !important; touch-action: none"
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
            class="absolute inset-0 flex items-center justify-center bg-background/50 backdrop-blur-[2px] transition-opacity cursor-pointer z-15"
        >
            <Button
                type="button"
                size="icon"
                aria-label="Putar Video"
                class="w-16 h-16 rounded-full shadow-2xl transform transition hover:scale-110 active:scale-95"
            >
                <Play class="w-8 h-8 fill-current ml-0.5" />
            </Button>
        </div>

        <!-- Floating Tap to Unmute Badge (Visible when video is playing muted) -->
        <div
            v-if="isMuted && isPlaying"
            @click.stop="toggleMute"
            class="absolute top-3 right-3 z-30 bg-card/90 hover:bg-muted border border-border text-destructive px-3 py-1.5 rounded-full text-xs font-mono shadow-xl flex items-center gap-2 cursor-pointer backdrop-blur-md transition transform active:scale-95"
            title="Klik untuk membuka suara"
        >
            <VolumeX class="w-3.5 h-3.5 motion-safe:animate-pulse" />
            <span>Buka Suara</span>
        </div>

        <!-- Custom Player Controls Bar -->
        <div
            class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-background via-background/90 to-transparent p-3 pt-6 space-y-2 transition-opacity duration-300 z-20"
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
                    class="w-full h-1.5 bg-muted rounded-lg appearance-none cursor-pointer focus:outline-hidden accent-primary"
                />
            </div>

            <!-- Controls Row: Play/Pause, Time Display, Volume, Speed, Fullscreen -->
            <div
                class="flex items-center justify-between text-xs font-mono text-foreground flex-wrap gap-2"
            >
                <!-- Left Action Controls -->
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon-sm"
                        @click="togglePlay"
                        class="h-7 w-7"
                        :aria-label="isPlaying ? 'Pause' : 'Play'"
                        :title="isPlaying ? 'Pause' : 'Play'"
                    >
                        <Pause v-if="isPlaying" class="w-3.5 h-3.5" />
                        <Play v-else class="w-3.5 h-3.5 ml-0.5" />
                    </Button>

                    <!-- Time Counter -->
                    <span class="text-xs text-muted-foreground font-mono ml-1">
                        <span class="text-foreground font-bold">{{
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
                        <Button
                            type="button"
                            variant="outline"
                            size="icon-sm"
                            @click="toggleMute"
                            class="h-7 w-7"
                            :aria-label="isMuted ? 'Unmute' : 'Mute'"
                            :title="isMuted ? 'Unmute' : 'Mute'"
                        >
                            <VolumeX
                                v-if="isMuted || volume === 0"
                                class="w-3.5 h-3.5 text-destructive"
                            />
                            <Volume2
                                v-else
                                class="w-3.5 h-3.5 text-success"
                            />
                        </Button>
                        <input
                            type="range"
                            min="0"
                            max="1"
                            step="0.05"
                            :value="isMuted ? 0 : volume"
                            @input="setVolume($event.target.value)"
                            class="w-16 h-1.5 bg-muted rounded-lg appearance-none cursor-pointer accent-primary hidden sm:block"
                        />
                    </div>

                    <!-- Playback Speed Selector -->
                    <select
                        :value="playbackRate"
                        @change="
                            setPlaybackRate(parseFloat($event.target.value))
                        "
                        class="bg-card text-foreground border border-border text-xs font-mono rounded-md px-1.5 py-1 focus:outline-hidden cursor-pointer"
                        title="Kecepatan Putar (Playback Speed)"
                    >
                        <option :value="0.5">0.5x</option>
                        <option :value="1">1.0x</option>
                        <option :value="1.25">1.25x</option>
                        <option :value="1.5">1.5x</option>
                        <option :value="2">2.0x</option>
                    </select>

                    <!-- Fullscreen Button -->
                    <Button
                        type="button"
                        variant="outline"
                        size="icon-sm"
                        @click="toggleFullscreen"
                        class="h-7 w-7"
                        aria-label="Layar Penuh (Fullscreen)"
                        title="Layar Penuh (Fullscreen)"
                    >
                        <Maximize class="w-3.5 h-3.5" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
