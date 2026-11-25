import { computed, ref } from "vue";

const DISMISS_STORAGE_KEY = "pwa_install_dismissed_at";
const DISMISS_COOLDOWN_MS = 7 * 24 * 60 * 60 * 1000; // 7 hari

const deferredPrompt = ref(null);
const isInstalled = ref(false);
const isIosDevice = ref(false);
const isDismissed = ref(false);
let initialized = false;

function detectStandalone() {
    if (typeof window === "undefined") return false;
    return (
        window.matchMedia?.("(display-mode: standalone)")?.matches === true ||
        window.navigator?.standalone === true
    );
}

function detectIos() {
    if (typeof navigator === "undefined") return false;
    const ua = navigator.userAgent || "";
    const isStandardIos = /iPhone|iPad|iPod/i.test(ua);
    const isIpadOsDesktop =
        navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1;
    return isStandardIos || isIpadOsDesktop;
}

function readDismissState() {
    if (typeof window === "undefined") return false;
    try {
        const raw = window.localStorage.getItem(DISMISS_STORAGE_KEY);
        if (!raw) return false;
        const ts = Number(raw);
        if (!Number.isFinite(ts)) return false;
        return Date.now() - ts < DISMISS_COOLDOWN_MS;
    } catch {
        return false;
    }
}

function initListeners() {
    if (initialized || typeof window === "undefined") return;
    initialized = true;

    isInstalled.value = detectStandalone();
    isIosDevice.value = detectIos();
    isDismissed.value = readDismissState();

    window.addEventListener("beforeinstallprompt", (event) => {
        event.preventDefault();
        deferredPrompt.value = event;
    });

    window.addEventListener("appinstalled", () => {
        isInstalled.value = true;
        deferredPrompt.value = null;
    });

    const mediaQuery = window.matchMedia?.("(display-mode: standalone)");
    if (mediaQuery?.addEventListener) {
        mediaQuery.addEventListener("change", (event) => {
            if (event.matches) {
                isInstalled.value = true;
                deferredPrompt.value = null;
            }
        });
    }
}

export function usePwaInstall() {
    initListeners();

    const canPromptInstall = computed(
        () => !isInstalled.value && !!deferredPrompt.value,
    );

    const isIosSafari = computed(
        () => !isInstalled.value && isIosDevice.value,
    );

    const shouldShowNudge = computed(
        () =>
            !isInstalled.value &&
            !isDismissed.value &&
            (canPromptInstall.value || isIosSafari.value),
    );

    const triggerInstall = async () => {
        const promptEvent = deferredPrompt.value;
        if (!promptEvent) return false;

        try {
            await promptEvent.prompt();
            const choice = await promptEvent.userChoice;
            deferredPrompt.value = null;
            if (choice?.outcome === "accepted") {
                isInstalled.value = true;
                return true;
            }
            return false;
        } catch {
            deferredPrompt.value = null;
            return false;
        }
    };

    const dismissNudge = () => {
        isDismissed.value = true;
        if (typeof window === "undefined") return;
        try {
            window.localStorage.setItem(
                DISMISS_STORAGE_KEY,
                String(Date.now()),
            );
        } catch {
            // ignore storage errors
        }
    };

    return {
        isInstalled,
        canPromptInstall,
        isIosSafari,
        shouldShowNudge,
        triggerInstall,
        dismissNudge,
    };
}
