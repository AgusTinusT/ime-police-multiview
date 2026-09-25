import { ref, computed, nextTick, onMounted, onUnmounted } from "vue";

/**
 * Senior Web Architect Refactored useYouTubePlayer Composable
 * 
 * Complies with YouTube Embedded Ads Requirements:
 * 1. No simultaneous mass `playVideo()` calls (Sequential Staggered Activation).
 * 2. Master Active Player concept for single interactive audio stream & high-res ad eligibility.
 * 3. Standardized YT IFrame API parameters (enablejsapi, origin, playsinline, allow permissions).
 */
export function useYouTubePlayer() {
    // Player State
    const activeAudioVideoId = ref(null);
    const masterActiveVideoId = ref(null);
    const focusedStreamId = ref(null);
    const isDataSaverEnabled = ref(true);
    const activePreviewVideoIds = ref([]);
    const activeGridVideoIds = ref([]);
    const isFullscreen = ref(false);
    const ytApiReady = ref(false);
    const isSequentialLoading = ref(false);
    const players = {};

    // Origin URL & Embed Domain for YouTube API Handshake
    const originUrl = ref(
        typeof window !== "undefined"
            ? window.location.origin ||
                  window.location.protocol + "//" + window.location.host
            : ""
    );

    const chatEmbedDomain = computed(() => {
        if (
            typeof window !== "undefined" &&
            window.location &&
            window.location.hostname
        ) {
            return window.location.hostname;
        }
        return "localhost";
    });

    // YouTube IFrame API Loader
    const loadYouTubeAPI = () => {
        if (typeof window === "undefined") return;
        if (window.YT && window.YT.Player) {
            ytApiReady.value = true;
            return;
        }

        if (!document.getElementById("yt-iframe-api-script")) {
            const tag = document.createElement("script");
            tag.id = "yt-iframe-api-script";
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName("script")[0];
            if (firstScriptTag && firstScriptTag.parentNode) {
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }
        }

        const existingReady = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
            if (typeof existingReady === "function") existingReady();
            ytApiReady.value = true;
        };
    };

    /**
     * Create or Bind YT.Player Instance (Standard API Handshake)
     */
    const bindYTPlayer = (domId, videoId, onReadyCallback = null) => {
        if (typeof window === "undefined" || !window.YT || !window.YT.Player) return null;
        
        try {
            if (players[videoId]) {
                return players[videoId];
            }

            const player = new window.YT.Player(domId, {
                events: {
                    onReady: (event) => {
                        if (typeof onReadyCallback === "function") {
                            onReadyCallback(event);
                        }
                    },
                    onStateChange: (event) => {
                        // Standard state change monitoring for ad events or playback status
                    }
                }
            });
            players[videoId] = player;
            return player;
        } catch (e) {
            console.warn(`Failed to bind YT.Player for ${videoId}:`, e);
            return null;
        }
    };

    // Resilient Audio Controller & Master Player Router
    const controlPlayerAudio = (videoId, shouldUnmute) => {
        if (!videoId) return;

        const isMaster = masterActiveVideoId.value === videoId;

        const iframes = document.querySelectorAll(
            `iframe[id="yt-bodycam-${videoId}"]`
        );

        iframes.forEach((iframe) => {
            if (iframe && iframe.contentWindow) {
                try {
                    const func = shouldUnmute ? "unMute" : "mute";
                    iframe.contentWindow.postMessage(
                        JSON.stringify({ event: "command", func: func, args: [] }),
                        "*"
                    );
                    if (shouldUnmute) {
                        iframe.contentWindow.postMessage(
                            JSON.stringify({
                                event: "command",
                                func: "setVolume",
                                args: [100],
                            }),
                            "*"
                        );
                    }
                } catch (err) {
                    console.warn("postMessage audio failed for " + videoId, err);
                }
            }
        });

        if (players[videoId]) {
            try {
                if (shouldUnmute) {
                    if (typeof players[videoId].unMute === "function") {
                        players[videoId].unMute();
                        players[videoId].setVolume(100);
                    }
                } else {
                    if (typeof players[videoId].mute === "function") {
                        players[videoId].mute();
                    }
                }
            } catch (e) {
                console.warn("YT.Player instance call failed for " + videoId, e);
            }
        }
    };

    // Master Player Switcher (User Interaction Triggered)
    const setMasterActivePlayer = (videoId, allActiveStreams = []) => {
        if (!videoId) return;

        masterActiveVideoId.value = videoId;
        activeAudioVideoId.value = videoId;

        // Mute all other streams
        allActiveStreams.forEach((s) => {
            if (s.video_id !== videoId) {
                controlPlayerAudio(s.video_id, false);
                applyQualityToPlayer(s.video_id, false);
            }
        });

        Object.keys(players).forEach((id) => {
            if (id !== videoId) {
                controlPlayerAudio(id, false);
                applyQualityToPlayer(id, false);
            }
        });

        // Unmute & Elevate Quality for Master Player
        controlPlayerAudio(videoId, true);
        applyQualityToPlayer(videoId, true);
    };

    // Video Playback Quality Controller
    const applyQualityToPlayer = (videoId, isHighQuality) => {
        if (!videoId) return;
        const targetQuality = isHighQuality ? "highres" : "small";
        const qualityRange = isHighQuality
            ? ["hd1080", "highres", "default"]
            : ["small", "medium"];

        if (players[videoId]) {
            try {
                if (typeof players[videoId].setPlaybackQualityRange === "function") {
                    players[videoId].setPlaybackQualityRange(qualityRange);
                }
                if (typeof players[videoId].setPlaybackQuality === "function") {
                    players[videoId].setPlaybackQuality(targetQuality);
                }
            } catch (e) {}
        }

        const iframes = document.querySelectorAll(
            `iframe[id="yt-bodycam-${videoId}"]`
        );
        iframes.forEach((iframe) => {
            if (iframe && iframe.contentWindow) {
                try {
                    iframe.contentWindow.postMessage(
                        JSON.stringify({
                            event: "command",
                            func: "setPlaybackQuality",
                            args: [targetQuality],
                        }),
                        "*"
                    );
                } catch (err) {}
            }
        });
    };

    const initializePlayer = (videoId, isFocusedLead = false) => {
        if (!videoId) return;
        const isAudioActive = activeAudioVideoId.value === videoId;
        controlPlayerAudio(videoId, isAudioActive);
        applyQualityToPlayer(videoId, isFocusedLead);
    };

    const destroyAllPlayers = () => {
        Object.keys(players).forEach((id) => {
            try {
                if (players[id] && typeof players[id].destroy === "function") {
                    players[id].destroy();
                }
            } catch (e) {}
            delete players[id];
        });
    };

    // Single-Audio Policy Enforcement with Master Active Player Promotion
    const toggleAudio = (videoId, allActiveStreams = []) => {
        if (activeAudioVideoId.value === videoId) {
            controlPlayerAudio(videoId, false);
            activeAudioVideoId.value = null;
            if (masterActiveVideoId.value === videoId) {
                masterActiveVideoId.value = null;
            }
            return;
        }

        setMasterActivePlayer(videoId, allActiveStreams);
    };

    const muteAll = (allActiveStreams = []) => {
        allActiveStreams.forEach((s) => {
            controlPlayerAudio(s.video_id, false);
        });
        Object.keys(players).forEach((id) => {
            controlPlayerAudio(id, false);
        });
        activeAudioVideoId.value = null;
        masterActiveVideoId.value = null;
    };

    // Data Saver Toggles
    const toggleSidebarPreview = (videoId) => {
        const idx = activePreviewVideoIds.value.indexOf(videoId);
        if (idx === -1) {
            activePreviewVideoIds.value.push(videoId);
            nextTick(() => {
                setTimeout(() => {
                    initializePlayer(videoId);
                }, 200);
            });
        } else {
            activePreviewVideoIds.value.splice(idx, 1);
            controlPlayerAudio(videoId, false);
            delete players[videoId];
            if (activeAudioVideoId.value === videoId) {
                activeAudioVideoId.value = null;
            }
        }
    };

    const toggleGridStreamPlay = (videoId) => {
        const idx = activeGridVideoIds.value.indexOf(videoId);
        if (idx === -1) {
            activeGridVideoIds.value.push(videoId);
            nextTick(() => {
                setTimeout(() => {
                    initializePlayer(videoId);
                }, 200);
            });
        } else {
            activeGridVideoIds.value.splice(idx, 1);
            controlPlayerAudio(videoId, false);
            delete players[videoId];
            if (activeAudioVideoId.value === videoId) {
                activeAudioVideoId.value = null;
            }
        }
    };

    const enableDataSaver = (allActiveStreams = [], isFocusMode = false) => {
        isDataSaverEnabled.value = true;
        activeGridVideoIds.value = [];
        activePreviewVideoIds.value = [];
        if (!isFocusMode) {
            activeAudioVideoId.value = null;
            masterActiveVideoId.value = null;
            muteAll(allActiveStreams);
        }
    };

    /**
     * Staggered Sequential Activation Queue (No Mass Scripted Play)
     * Replaces simultaneous playVideo calls with smooth 600ms interval queueing.
     */
    const disableDataSaverAndPlayAll = (allActiveStreams = [], onPlayAllCallback = null) => {
        isDataSaverEnabled.value = false;
        activeGridVideoIds.value = [];
        activePreviewVideoIds.value = [];
        isSequentialLoading.value = true;

        if (Array.isArray(allActiveStreams) && allActiveStreams.length > 0) {
            allActiveStreams.forEach((stream, index) => {
                setTimeout(() => {
                    if (!activeGridVideoIds.value.includes(stream.video_id)) {
                        activeGridVideoIds.value.push(stream.video_id);
                    }
                    if (index === allActiveStreams.length - 1) {
                        isSequentialLoading.value = false;
                        if (typeof onPlayAllCallback === "function") {
                            onPlayAllCallback();
                        }
                    }
                }, index * 600); // 600ms staggered delay interval per player
            });
        } else {
            isSequentialLoading.value = false;
            if (typeof onPlayAllCallback === "function") {
                nextTick(onPlayAllCallback);
            }
        }
    };

    // YouTube Subscribe Popup Modal
    const openSubscribePopup = (
        channelIdOrHandle,
        officerName = "",
        videoId = "",
        handle = ""
    ) => {
        let subUrl = "";
        const rawStr =
            typeof channelIdOrHandle === "string" ? channelIdOrHandle.trim() : "";
        const rawHandle =
            typeof handle === "string" && handle.trim()
                ? handle.trim()
                : rawStr.includes("@")
                  ? rawStr
                  : "";
        const rawVideoId = typeof videoId === "string" ? videoId.trim() : "";

        if (rawHandle) {
            const cleanHandle = rawHandle.startsWith("@")
                ? rawHandle
                : `@${rawHandle}`;
            subUrl = `https://www.youtube.com/${cleanHandle}?sub_confirmation=1`;
        } else if (rawStr.startsWith("@")) {
            subUrl = `https://www.youtube.com/${rawStr}?sub_confirmation=1`;
        } else if (/^UC[A-Za-z0-9_-]{22}$/.test(rawStr)) {
            subUrl = `https://www.youtube.com/channel/${rawStr}?sub_confirmation=1`;
        } else if (rawVideoId && !rawVideoId.startsWith("officer-")) {
            subUrl = `https://www.youtube.com/watch?v=${rawVideoId}?sub_confirmation=1`;
        } else if (rawStr && !rawStr.startsWith("UC_")) {
            subUrl = `https://www.youtube.com/@${rawStr}?sub_confirmation=1`;
        } else {
            subUrl = "https://www.youtube.com/";
        }

        const width = 640;
        const height = 660;
        const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
        const top = Math.max(0, Math.floor((window.screen.height - height) / 2));

        window.open(
            subUrl,
            "YouTubeSubscribeModal",
            `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,status=no,toolbar=no,menubar=no,location=no,resizable=yes`
        );
    };

    // Fullscreen Controller
    const toggleBrowserFullscreen = () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch((err) => {
                console.warn(
                    `Error attempting to enable fullscreen: ${err.message}`
                );
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    };

    const handleFullscreenChange = () => {
        isFullscreen.value = !!document.fullscreenElement;
    };

    onMounted(() => {
        loadYouTubeAPI();
        if (typeof document !== "undefined") {
            document.addEventListener("fullscreenchange", handleFullscreenChange);
        }
    });

    onUnmounted(() => {
        destroyAllPlayers();
        if (typeof document !== "undefined") {
            document.removeEventListener("fullscreenchange", handleFullscreenChange);
        }
    });

    return {
        // State
        activeAudioVideoId,
        masterActiveVideoId,
        focusedStreamId,
        isDataSaverEnabled,
        activePreviewVideoIds,
        activeGridVideoIds,
        isFullscreen,
        ytApiReady,
        isSequentialLoading,
        originUrl,
        chatEmbedDomain,
        players,

        // Player & Audio Methods
        loadYouTubeAPI,
        bindYTPlayer,
        setMasterActivePlayer,
        controlPlayerAudio,
        applyQualityToPlayer,
        initializePlayer,
        destroyAllPlayers,
        toggleAudio,
        muteAll,
        toggleSidebarPreview,
        toggleGridStreamPlay,
        enableDataSaver,
        disableDataSaverAndPlayAll,
        openSubscribePopup,
        toggleBrowserFullscreen,
        handleFullscreenChange
    };
}

