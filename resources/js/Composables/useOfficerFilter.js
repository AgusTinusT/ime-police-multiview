import { ref, watch, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "vue-sonner";

const STORAGE_KEY = "ime_disabled_officers";
const disabledOfficerKeys = ref([]);
let isInitialized = false;

export function useOfficerFilter() {
    const getOfficerId = (item) => {
        if (!item) return null;
        if (typeof item === "number" && item > 0) return item;
        const off = item.officer || item;
        if (off.id && Number(off.id) > 0) return Number(off.id);
        if (item.officer_id && Number(item.officer_id) > 0) return Number(item.officer_id);
        return null;
    };

    const getOfficerKey = (item) => {
        if (!item) return "";
        if (typeof item === "string" || typeof item === "number") {
            return String(item).trim().toLowerCase().replace(/^@/, "");
        }

        const off = item.officer || item;

        // 1. Numeric Officer ID
        if (off.id) return Number(off.id);
        if (item.officer_id) return Number(item.officer_id);

        // 2. YouTube Channel ID
        if (off.channel_id) return String(off.channel_id).trim().toLowerCase();
        if (item.channel_id) return String(item.channel_id).trim().toLowerCase();

        // 3. YouTube Handle (e.g. "@Ghost_SWAT")
        if (off.handle) return String(off.handle).trim().toLowerCase().replace(/^@/, "");
        if (item.handle) return String(item.handle).trim().toLowerCase().replace(/^@/, "");

        // 4. Officer Name
        if (off.officer_name) return String(off.officer_name).trim().toLowerCase();
        if (item.officer_name) return String(item.officer_name).trim().toLowerCase();

        // 5. Video ID or Stream ID fallback
        if (item.video_id) return String(item.video_id).trim().toLowerCase();
        if (item.id) return String(item.id).trim().toLowerCase();

        return "";
    };

    const isOfficerDisabled = (officerOrStream) => {
        if (!officerOrStream) return false;
        const off = officerOrStream.officer || officerOrStream;
        const id = Number(off.id || officerOrStream.officer_id || (typeof officerOrStream === "number" ? officerOrStream : null));
        const channelId = String(off.channel_id || officerOrStream.channel_id || "").trim().toLowerCase();
        const handle = String(off.handle || officerOrStream.handle || "").trim().toLowerCase().replace(/^@/, "");
        const name = String(off.officer_name || officerOrStream.officer_name || "").trim().toLowerCase();

        return disabledOfficerKeys.value.some((k) => {
            if (id && (k === id || Number(k) === id || k === `officer-${id}`)) {
                return true;
            }
            if (typeof k === "string") {
                const lk = k.toLowerCase().replace(/^@/, "");
                if (channelId && lk === channelId) return true;
                if (handle && lk === handle) return true;
                if (name && lk === name) return true;
            }
            return false;
        });
    };

    const syncWithServerIfNeeded = async () => {
        let authUser = null;
        try {
            const page = usePage();
            authUser = page?.props?.auth?.user;
        } catch {
            // Not in Inertia component context
        }

        if (!authUser) return;

        const serverBlockedIds = Array.isArray(authUser.blocked_officer_ids)
            ? authUser.blocked_officer_ids.map(Number)
            : [];

        // 1. Merge server blocked IDs into local state
        let updated = false;
        serverBlockedIds.forEach((id) => {
            if (!disabledOfficerKeys.value.includes(id)) {
                disabledOfficerKeys.value.push(id);
                updated = true;
            }
        });

        // 2. Identify local numeric IDs not on server yet
        const localNumericIds = disabledOfficerKeys.value
            .map(Number)
            .filter((n) => !isNaN(n) && n > 0 && !serverBlockedIds.includes(n));

        if (localNumericIds.length > 0) {
            try {
                const res = await axios.post("/api/v1/user/blocked-officers/sync", {
                    officer_ids: localNumericIds,
                });
                if (res.data?.blocked_officer_ids) {
                    authUser.blocked_officer_ids = res.data.blocked_officer_ids;
                }
            } catch (err) {
                console.error("Gagal sinkronisasi perwira yang diblokir ke akun:", err);
            }
        }

        if (updated && typeof window !== "undefined") {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(disabledOfficerKeys.value));
            } catch {
                // ignore storage error
            }
        }
    };

    onMounted(() => {
        if (typeof window !== "undefined" && !isInitialized) {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (Array.isArray(parsed)) {
                        disabledOfficerKeys.value = parsed;
                    }
                }
            } catch (e) {
                console.error("Gagal memuat filter perwira dari localStorage:", e);
            }
            isInitialized = true;
        }
        syncWithServerIfNeeded();
    });

    watch(
        disabledOfficerKeys,
        (newVal) => {
            if (typeof window !== "undefined") {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
                } catch (e) {
                    console.error("Gagal menyimpan filter perwira ke localStorage:", e);
                }
            }
        },
        { deep: true }
    );

    const getAuthUser = () => {
        try {
            return usePage()?.props?.auth?.user || null;
        } catch {
            return null;
        }
    };

    const disableOfficer = async (officerOrStream, showNotification = false) => {
        const id = getOfficerId(officerOrStream);
        const key = id || getOfficerKey(officerOrStream);
        if (!key) return;

        if (!disabledOfficerKeys.value.includes(key)) {
            disabledOfficerKeys.value.push(key);
        }

        const off = officerOrStream.officer || officerOrStream;
        const name = off.officer_name || off.streamer_name || "Perwira";

        // Sync with account if logged in and numeric id available
        const user = getAuthUser();
        if (user && id) {
            try {
                await axios.post("/api/v1/user/blocked-officers", { officer_id: id });
                if (!Array.isArray(user.blocked_officer_ids)) {
                    user.blocked_officer_ids = [];
                }
                if (!user.blocked_officer_ids.includes(id)) {
                    user.blocked_officer_ids.push(id);
                }
            } catch (err) {
                console.error("Gagal menyimpan blokir perwira ke akun:", err);
            }
        }

        if (showNotification) {
            toast.info(`${name} disembunyikan dari feed Anda.`, {
                action: {
                    label: "Urungkan",
                    onClick: () => enableOfficer(officerOrStream, true),
                },
            });
        }
    };

    const enableOfficer = async (officerOrStream, showNotification = false) => {
        const id = getOfficerId(officerOrStream);
        const key = id || getOfficerKey(officerOrStream);
        if (!key) return;

        // Remove numeric ID and any string key representation
        disabledOfficerKeys.value = disabledOfficerKeys.value.filter((k) => {
            if (id && (k === id || Number(k) === id || k === `officer-${id}`)) {
                return false;
            }
            if (typeof key === "string" && String(k).toLowerCase() === key.toLowerCase()) {
                return false;
            }
            return true;
        });

        const off = officerOrStream.officer || officerOrStream;
        const name = off.officer_name || off.streamer_name || "Perwira";

        // Sync with account if logged in and numeric id available
        const user = getAuthUser();
        if (user && id) {
            try {
                await axios.delete(`/api/v1/user/blocked-officers/${id}`);
                if (Array.isArray(user.blocked_officer_ids)) {
                    user.blocked_officer_ids = user.blocked_officer_ids.filter((i) => i !== id);
                }
            } catch (err) {
                console.error("Gagal menghapus blokir perwira di akun:", err);
            }
        }

        if (showNotification) {
            toast.success(`${name} ditampilkan kembali.`);
        }
    };

    const toggleOfficerStatus = (officerOrStream) => {
        if (isOfficerDisabled(officerOrStream)) {
            enableOfficer(officerOrStream, true);
        } else {
            disableOfficer(officerOrStream, true);
        }
    };

    const resetAllFilters = async (showNotification = false) => {
        disabledOfficerKeys.value = [];

        const user = getAuthUser();
        if (user) {
            try {
                await axios.delete("/api/v1/user/blocked-officers/all");
                user.blocked_officer_ids = [];
            } catch (err) {
                console.error("Gagal reset blokir perwira di akun:", err);
            }
        }

        if (showNotification) {
            toast.success("Semua perwira ditampilkan kembali.");
        }
    };

    return {
        disabledOfficerKeys,
        getOfficerKey,
        getOfficerId,
        isOfficerDisabled,
        toggleOfficerStatus,
        enableOfficer,
        disableOfficer,
        hideOfficer: (item) => disableOfficer(item, true),
        unhideOfficer: (item) => enableOfficer(item, true),
        resetAllFilters,
        syncWithServer: syncWithServerIfNeeded,
    };
}
