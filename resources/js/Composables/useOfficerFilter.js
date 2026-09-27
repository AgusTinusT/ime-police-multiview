import { ref, watch, onMounted } from "vue";

const STORAGE_KEY = "ime_disabled_officers";
const disabledOfficerKeys = ref([]);

export function useOfficerFilter() {
    onMounted(() => {
        if (typeof window !== "undefined") {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    disabledOfficerKeys.value = JSON.parse(saved);
                }
            } catch (e) {
                console.error("Gagal memuat filter perwira dari localStorage:", e);
            }
        }
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

    const getOfficerKey = (item) => {
        if (!item) return "";
        if (typeof item === "string" || typeof item === "number") {
            return String(item).trim().toLowerCase().replace(/^@/, "");
        }

        const off = item.officer || item;

        // 1. YouTube Channel ID
        if (off.channel_id) return String(off.channel_id).trim().toLowerCase();
        if (item.channel_id) return String(item.channel_id).trim().toLowerCase();

        // 2. YouTube Handle (e.g. "@Ghost_SWAT")
        if (off.handle) return String(off.handle).trim().toLowerCase().replace(/^@/, "");
        if (item.handle) return String(item.handle).trim().toLowerCase().replace(/^@/, "");

        // 3. Officer Name
        if (off.officer_name) return String(off.officer_name).trim().toLowerCase();
        if (item.officer_name) return String(item.officer_name).trim().toLowerCase();

        // 4. Officer ID
        if (off.id) return `officer-${off.id}`;

        // 5. Video ID or Stream ID fallback
        if (item.video_id) return String(item.video_id).trim().toLowerCase();
        if (item.id) return String(item.id).trim().toLowerCase();

        return "";
    };

    const isOfficerDisabled = (officerOrStream) => {
        const key = getOfficerKey(officerOrStream);
        if (!key) return false;
        return disabledOfficerKeys.value.includes(key);
    };

    const toggleOfficerStatus = (officerOrStream) => {
        const key = getOfficerKey(officerOrStream);
        if (!key) return;
        const idx = disabledOfficerKeys.value.indexOf(key);
        if (idx === -1) {
            disabledOfficerKeys.value.push(key);
        } else {
            disabledOfficerKeys.value.splice(idx, 1);
        }
    };

    const enableOfficer = (officerOrStream) => {
        const key = getOfficerKey(officerOrStream);
        if (!key) return;
        const idx = disabledOfficerKeys.value.indexOf(key);
        if (idx !== -1) {
            disabledOfficerKeys.value.splice(idx, 1);
        }
    };

    const disableOfficer = (officerOrStream) => {
        const key = getOfficerKey(officerOrStream);
        if (!key) return;
        if (!disabledOfficerKeys.value.includes(key)) {
            disabledOfficerKeys.value.push(key);
        }
    };

    const resetAllFilters = () => {
        disabledOfficerKeys.value = [];
    };

    return {
        disabledOfficerKeys,
        getOfficerKey,
        isOfficerDisabled,
        toggleOfficerStatus,
        enableOfficer,
        disableOfficer,
        resetAllFilters,
    };
}
