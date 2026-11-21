import { onUnmounted, ref, watch } from "vue";

const API_SRC = "https://www.youtube.com/iframe_api";
let apiPromise = null;

// Load the YouTube IFrame API once per page session.
const loadApi = () => {
    if (typeof window === "undefined") return Promise.reject(new Error("no window"));
    if (window.YT?.Player) return Promise.resolve(window.YT);
    if (apiPromise) return apiPromise;

    apiPromise = new Promise((resolve) => {
        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
            if (typeof previous === "function") previous();
            resolve(window.YT);
        };
        if (!document.querySelector(`script[src="${API_SRC}"]`)) {
            const tag = document.createElement("script");
            tag.src = API_SRC;
            tag.async = true;
            document.head.appendChild(tag);
        }
    });
    return apiPromise;
};

/**
 * Wraps the watch page's single embed (rendered with enablejsapi=1) so the page
 * can read the playhead for clipping and jump to TacClip timestamps.
 */
export function useWatchPlayer(iframeRef) {
    const isReady = ref(false);
    let player = null;

    const destroy = () => {
        isReady.value = false;
        try {
            player?.destroy?.();
        } catch {
            // iframe already gone
        }
        player = null;
    };

    const bind = async (el) => {
        destroy();
        if (!el) return;
        try {
            const YT = await loadApi();
            if (iframeRef.value !== el) return; // navigated away meanwhile
            player = new YT.Player(el, {
                events: {
                    onReady: () => {
                        isReady.value = true;
                    },
                },
            });
        } catch {
            isReady.value = false;
        }
    };

    watch(iframeRef, (el) => bind(el), { immediate: true, flush: "post" });
    onUnmounted(destroy);

    const getCurrentTime = () => {
        try {
            return Math.max(0, Math.floor(player?.getCurrentTime?.() || 0));
        } catch {
            return 0;
        }
    };

    const getDuration = () => {
        try {
            return Math.floor(player?.getDuration?.() || 0);
        } catch {
            return 0;
        }
    };

    const seekTo = (seconds) => {
        try {
            player?.seekTo?.(Math.max(0, Number(seconds) || 0), true);
            player?.playVideo?.();
            return true;
        } catch {
            return false;
        }
    };

    return { isReady, getCurrentTime, getDuration, seekTo };
}
