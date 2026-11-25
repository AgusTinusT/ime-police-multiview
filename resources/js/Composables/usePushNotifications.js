import { ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { usePwaInstall } from "./usePwaInstall";

function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, "+")
        .replace(/_/g, "/");
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

const isSupported = ref(false);
const serverEnabled = ref(false);
const publicKey = ref("");
const isSubscribed = ref(false);
const permission = ref("default");
const isBusy = ref(false);
const initialized = ref(false);

export function usePushNotifications() {
    const syncState = async () => {
        if (typeof window === "undefined") return;

        isSupported.value =
            "serviceWorker" in navigator &&
            "PushManager" in window &&
            "Notification" in window;

        if (!isSupported.value) {
            initialized.value = true;
            return;
        }

        permission.value = Notification.permission;

        try {
            const { data } = await axios.get("/api/v1/push-subscriptions/config");
            serverEnabled.value = !!data?.enabled;
            publicKey.value = data?.public_key || "";
        } catch {
            serverEnabled.value = false;
        }

        try {
            const registration = await navigator.serviceWorker.getRegistration();
            const existing = registration
                ? await registration.pushManager.getSubscription()
                : null;
            isSubscribed.value = !!existing;

            // Ensure browser's active subscription is always synced to the server DB
            // (prevents UI showing "Active" when DB row was removed or never saved).
            if (existing && serverEnabled.value && Notification.permission === "granted") {
                const json = existing.toJSON();
                if (json?.endpoint && json?.keys?.p256dh && json?.keys?.auth) {
                    await axios.post("/api/v1/push-subscriptions", {
                        endpoint: json.endpoint,
                        keys: {
                            p256dh: json.keys.p256dh,
                            auth: json.keys.auth,
                        },
                        content_encoding: "aes128gcm",
                    }).catch(() => {});
                }
            }
        } catch {
            isSubscribed.value = false;
        } finally {
            initialized.value = true;
        }
    };

    const enablePush = async () => {
        if (!isSupported.value) {
            toast.error("Browser ini tidak mendukung Web Push Notification.");
            return false;
        }
        if (!serverEnabled.value || !publicKey.value) {
            toast.error("Web Push belum diaktifkan pada server.");
            return false;
        }

        isBusy.value = true;
        try {
            const result = await Notification.requestPermission();
            permission.value = result;

            if (result !== "granted") {
                toast.warning(
                    "Izin notifikasi browser ditolak. Aktifkan di pengaturan browser jika ingin menerima alert.",
                );
                return false;
            }

            const registration = await navigator.serviceWorker.register(
                "/service-worker",
                { scope: "/", updateViaCache: "none" },
            );
            try {
                await registration.update();
            } catch {
                // ignore update errors offline
            }
            await navigator.serviceWorker.ready;

            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey.value),
                });
            }

            const json = subscription.toJSON();
            await axios.post("/api/v1/push-subscriptions", {
                endpoint: json.endpoint,
                keys: {
                    p256dh: json.keys?.p256dh,
                    auth: json.keys?.auth,
                },
                content_encoding: "aes128gcm",
            });

            isSubscribed.value = true;
            const { canPromptInstall, triggerInstall } = usePwaInstall();
            if (canPromptInstall.value) {
                toast.success(
                    "Notifikasi push aktif! Pasang aplikasi agar alert tetap masuk saat browser ditutup.",
                    {
                        duration: 8000,
                        action: {
                            label: "Instal Aplikasi",
                            onClick: () => {
                                triggerInstall();
                            },
                        },
                    },
                );
            } else {
                toast.success("Notifikasi push browser aktif di perangkat ini.");
            }
            return true;
        } catch (err) {
            const msg =
                err?.response?.data?.message ||
                "Gagal mengaktifkan notifikasi push browser.";
            toast.error(msg);
            return false;
        } finally {
            isBusy.value = false;
        }
    };

    const disablePush = async () => {
        if (!isSupported.value) return;

        isBusy.value = true;
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            const subscription = registration
                ? await registration.pushManager.getSubscription()
                : null;

            if (subscription) {
                const endpoint = subscription.endpoint;
                await subscription.unsubscribe();
                await axios.delete("/api/v1/push-subscriptions", {
                    data: { endpoint },
                });
            }

            isSubscribed.value = false;
            toast.info("Notifikasi push browser dinonaktifkan di perangkat ini.");
        } catch {
            toast.error("Gagal menonaktifkan notifikasi push browser.");
        } finally {
            isBusy.value = false;
        }
    };

    const togglePush = async (nextValue) => {
        if (nextValue) {
            return enablePush();
        }
        await disablePush();
        return false;
    };

    return {
        isSupported,
        serverEnabled,
        isSubscribed,
        permission,
        isBusy,
        initialized,
        syncState,
        enablePush,
        disablePush,
        togglePush,
    };
}
