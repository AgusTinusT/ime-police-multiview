import { ref, computed } from "vue";

export const defaultTacChannels = [
    {
        id: 1,
        code: "TAC_1",
        name: "TAC 1",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 2,
        code: "TAC_2",
        name: "TAC 2",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 3,
        code: "TAC_3",
        name: "TAC 3",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 4,
        code: "TAC_4",
        name: "TAC 4",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 5,
        code: "TAC_5",
        name: "TAC 5",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 6,
        code: "TAC_6",
        name: "TAC 6",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 7,
        code: "TAC_7",
        name: "TAC 7",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 8,
        code: "TAC_8",
        name: "TAC 8",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 9,
        code: "TAC_9",
        name: "TAC 9",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
    {
        id: 10,
        code: "TAC_10",
        name: "TAC 10",
        video_ids: [],
        expires_at: null,
        remaining_seconds: 0,
        is_active: false,
        unit_count: 0,
    },
];

export function useTacChannelManager(options = {}) {
    const {
        initialTacChannels = [],
        allActiveStreams = null,
        selectedDepartment = null,
        customShowToast = null,
    } = options;

    const tacChannels = ref(
        initialTacChannels && initialTacChannels.length > 0
            ? initialTacChannels
            : defaultTacChannels,
    );

    const activeTacPopoverVideoId = ref(null);
    const tacticalToast = ref(null);

    const showTacticalToast = (message, type = "info") => {
        if (typeof customShowToast === "function") {
            customShowToast(message, type);
        }
        tacticalToast.value = { message, type };
        setTimeout(() => {
            if (tacticalToast.value?.message === message) {
                tacticalToast.value = null;
            }
        }, 3500);
    };

    const resolveActiveStreams = () => {
        if (typeof allActiveStreams === "function") return allActiveStreams();
        if (allActiveStreams && allActiveStreams.value) return allActiveStreams.value;
        return Array.isArray(allActiveStreams) ? allActiveStreams : [];
    };

    const resolveSelectedDepartment = () => {
        if (typeof selectedDepartment === "function") return selectedDepartment();
        if (selectedDepartment && selectedDepartment.value) return selectedDepartment.value;
        return selectedDepartment || "ALL";
    };

    const fetchTacChannels = async () => {
        try {
            const res = await fetch("/api/v1/tac", {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === "success" && data.data) {
                    tacChannels.value = data.data;
                }
            }
        } catch (e) {
            console.warn("TAC sync failed:", e);
        }
    };

    const getStreamTac = (videoId) => {
        if (!videoId) return null;
        const ch = tacChannels.value.find(
            (c) => c.video_ids && c.video_ids.includes(videoId),
        );
        return ch ? ch.code : null;
    };

    const getTacChannel = (tacCode) => {
        return tacChannels.value.find((c) => c.code === tacCode) || null;
    };

    const getTacUnitCount = (tacCode) => {
        const ch = getTacChannel(tacCode);
        return ch && ch.video_ids ? ch.video_ids.length : 0;
    };

    const getTacRemainingSeconds = (tacCode) => {
        const ch = getTacChannel(tacCode);
        return ch ? ch.remaining_seconds || 0 : 0;
    };

    const isTacDepartment = (deptId) => {
        return typeof deptId === "string" && deptId.startsWith("TAC_");
    };

    const formatRemainingTime = (seconds) => {
        if (!seconds || seconds <= 0) return "00:00";
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
    };

    const getTacStreams = (tacCode) => {
        const ch = getTacChannel(tacCode);
        if (!ch || !ch.video_ids || ch.video_ids.length === 0) return [];
        const ids = ch.video_ids.map((id) => String(id).trim());
        const streams = resolveActiveStreams();
        return streams.filter((s) => ids.includes(String(s.video_id).trim()));
    };

    const assignStreamToTac = async (tacCode, videoId) => {
        if (!tacCode || !videoId) return;
        const cleanVideoId = String(videoId).trim();

        tacChannels.value.forEach((ch) => {
            if (
                ch.code !== tacCode &&
                ch.video_ids &&
                ch.video_ids.includes(cleanVideoId)
            ) {
                ch.video_ids = ch.video_ids.filter((id) => id !== cleanVideoId);
                ch.unit_count = ch.video_ids.length;
                if (ch.unit_count === 0) {
                    ch.remaining_seconds = 0;
                    ch.is_active = false;
                }
            }
        });

        const targetCh = getTacChannel(tacCode);
        if (targetCh) {
            if (!targetCh.video_ids) targetCh.video_ids = [];
            if (!targetCh.video_ids.includes(cleanVideoId)) {
                targetCh.video_ids.push(cleanVideoId);
            }
            targetCh.unit_count = targetCh.video_ids.length;
            if (!targetCh.remaining_seconds || targetCh.remaining_seconds <= 0) {
                targetCh.remaining_seconds = 1800; // 30 mins
            }
            targetCh.is_active = true;
        }

        showTacticalToast(
            `Unit berhasil dimasukkan ke ${tacCode.replace("_", " ")} (30 Menit)`,
        );

        try {
            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";
            const res = await fetch("/api/v1/tac/assign", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    tac_code: tacCode,
                    video_id: cleanVideoId,
                }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === "success" && data.data) {
                    tacChannels.value = data.data;
                }
            } else {
                console.error(
                    "TAC Assign API Error:",
                    res.status,
                    await res.text(),
                );
            }
        } catch (e) {
            console.error("Failed to assign stream to TAC:", e);
        }
    };

    const removeStreamFromTac = async (videoId, tacCode = null) => {
        if (!videoId) return;
        const cleanVideoId = String(videoId).trim();

        tacChannels.value.forEach((ch) => {
            if (!tacCode || ch.code === tacCode) {
                if (ch.video_ids && ch.video_ids.includes(cleanVideoId)) {
                    ch.video_ids = ch.video_ids.filter((id) => id !== cleanVideoId);
                    ch.unit_count = ch.video_ids.length;
                    if (ch.unit_count === 0) {
                        ch.remaining_seconds = 0;
                        ch.is_active = false;
                    }
                }
            }
        });

        showTacticalToast("Unit dilepas dari Tactical Radio");

        try {
            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";
            const res = await fetch("/api/v1/tac/remove", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    video_id: cleanVideoId,
                    tac_code: tacCode,
                }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === "success" && data.data) {
                    tacChannels.value = data.data;
                }
            } else {
                console.error(
                    "TAC Remove API Error:",
                    res.status,
                    await res.text(),
                );
            }
        } catch (e) {
            console.error("Failed to remove stream from TAC:", e);
        }
    };

    const extendTacTimer = async (tacCode, minutes = 20) => {
        if (!tacCode) return;

        const targetCh = getTacChannel(tacCode);
        if (targetCh) {
            targetCh.remaining_seconds =
                (targetCh.remaining_seconds || 0) + minutes * 60;
        }

        showTacticalToast(
            `Waktu situasi ${tacCode.replace("_", " ")} diperpanjang +${minutes} menit`,
        );

        try {
            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";
            const res = await fetch("/api/v1/tac/extend", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    tac_code: tacCode,
                    minutes: minutes,
                }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === "success" && data.data) {
                    tacChannels.value = data.data;
                }
            } else {
                console.error(
                    "TAC Extend API Error:",
                    res.status,
                    await res.text(),
                );
            }
        } catch (e) {
            console.error("Failed to extend TAC timer:", e);
        }
    };

    const disbandTacChannel = async (tacCode) => {
        if (!tacCode) return;

        if (
            !confirm(
                `Apakah Anda yakin ingin mengosongkan / membubarkan kanal ${tacCode.replace("_", " ")} untuk seluruh penonton?`,
            )
        ) {
            return;
        }

        const targetCh = getTacChannel(tacCode);
        if (targetCh) {
            targetCh.video_ids = [];
            targetCh.unit_count = 0;
            targetCh.remaining_seconds = 0;
            targetCh.is_active = false;
            targetCh.expires_at = null;
        }

        showTacticalToast(
            `Kanal ${tacCode.replace("_", " ")} telah dibubarkan / dikosongkan`,
        );

        try {
            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";
            const res = await fetch("/api/v1/tac/clear", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    tac_code: tacCode,
                }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === "success" && data.data) {
                    tacChannels.value = data.data;
                }
            } else {
                console.error("TAC Clear API Error:", res.status, await res.text());
            }
        } catch (e) {
            console.error("Failed to disband TAC channel:", e);
        }
    };

    const expiringTacChannel = computed(() => {
        const currentDept = resolveSelectedDepartment();
        return tacChannels.value.find(
            (c) =>
                c.video_ids &&
                c.video_ids.length > 0 &&
                c.remaining_seconds > 0 &&
                c.remaining_seconds <= 60 &&
                currentDept !== c.code,
        );
    });

    const tickTacTimers = () => {
        let hasExpired = false;
        tacChannels.value.forEach((ch) => {
            if (ch.remaining_seconds > 0) {
                ch.remaining_seconds -= 1;
                if (ch.remaining_seconds <= 0) {
                    ch.remaining_seconds = 0;
                    ch.is_active = false;
                    ch.video_ids = [];
                    ch.unit_count = 0;
                    hasExpired = true;
                }
            }
        });
        if (hasExpired) {
            fetchTacChannels();
        }
    };

    return {
        tacChannels,
        defaultTacChannels,
        activeTacPopoverVideoId,
        expiringTacChannel,
        tacticalToast,
        showTacticalToast,
        fetchTacChannels,
        getStreamTac,
        getTacChannel,
        getTacUnitCount,
        getTacRemainingSeconds,
        isTacDepartment,
        formatRemainingTime,
        getTacStreams,
        assignStreamToTac,
        removeStreamFromTac,
        extendTacTimer,
        disbandTacChannel,
        tickTacTimers,
    };
}
