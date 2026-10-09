<template>
    <div class="space-y-3 font-sans select-none">
        <!-- Quick Jump Preset Duration Section -->
        <div class="space-y-1.5">
            <div
                class="flex items-center justify-between text-xs flex-wrap gap-1"
            >
                <span class="text-xs font-mono font-semibold text-slate-300"
                    >Tambah Durasi Klip:</span
                >
            </div>
            <div
                class="grid grid-cols-5 gap-1.5 font-mono text-xs font-semibold"
            >
                <button
                    v-for="preset in [15, 30, 60, 180, 300]"
                    :key="preset"
                    @click="applyPreset(preset)"
                    type="button"
                    class="py-2 px-1 min-h-[36px] rounded-lg bg-[#0a1224] hover:bg-[#121e36] text-slate-200 border border-[#192742] transition font-bold cursor-pointer active:scale-95 text-center flex items-center justify-center shadow-sm"
                    :title="`Atur Waktu Selesai otomatis +${preset >= 60 ? preset / 60 + ' menit' : preset + ' detik'} dari Waktu Mulai`"
                >
                    +{{ preset >= 60 ? preset / 60 + "m" : preset + "s" }}
                </button>
            </div>
        </div>

        <!-- Fine-Tuning Nudge Stepper Controls for HP/Mobile -->
        <div class="flex flex-col gap-2 text-xs font-mono pt-1">
            <!-- IN Marker Nudge Steppers -->
            <div
                class="bg-[#09101f] border border-[#16233d] rounded-xl px-3 py-2.5 flex items-center justify-between flex-wrap gap-2"
            >
                <span class="text-xs font-mono font-semibold text-slate-300"
                    >Waktu Mulai (IN):</span
                >
                <div class="grid grid-cols-4 gap-1.5 flex-1 max-w-xs">
                    <button
                        @click="nudgeStart(-5)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        -5s
                    </button>
                    <button
                        @click="nudgeStart(-1)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        -1s
                    </button>
                    <button
                        @click="nudgeStart(1)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        +1s
                    </button>
                    <button
                        @click="nudgeStart(5)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        +5s
                    </button>
                </div>
            </div>

            <!-- OUT Marker Nudge Steppers -->
            <div
                class="bg-[#09101f] border border-[#16233d] rounded-xl px-3 py-2.5 flex items-center justify-between flex-wrap gap-2"
            >
                <span class="text-xs font-mono font-semibold text-[#38bdf8]"
                    >Waktu Selesai (OUT):</span
                >
                <div class="grid grid-cols-4 gap-1.5 flex-1 max-w-xs">
                    <button
                        @click="nudgeEnd(-5)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        -5s
                    </button>
                    <button
                        @click="nudgeEnd(-1)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        -1s
                    </button>
                    <button
                        @click="nudgeEnd(1)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        +1s
                    </button>
                    <button
                        @click="nudgeEnd(5)"
                        type="button"
                        class="py-2 px-1 min-h-[36px] bg-[#0d1629] border border-[#1c2c4b] rounded-lg text-slate-200 font-bold hover:bg-[#162442] active:scale-95 transition text-center cursor-pointer shadow-sm"
                    >
                        +5s
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from "vue";

const props = defineProps({
    duration: {
        type: Number,
        default: 3600,
    },
    currentTime: {
        type: Number,
        default: 0,
    },
    startTime: {
        type: Number,
        default: 10,
    },
    endTime: {
        type: Number,
        default: 40,
    },
    maxAllowedDuration: {
        type: Number,
        default: 600, // 10 mins
    },
});

const emit = defineEmits(["update:startTime", "update:endTime", "seek"]);

const trackRef = ref(null);
const draggingHandle = ref(null);

const zoomMode = ref("full"); // 'full', '30m', '10m'

const localStart = ref(props.startTime);
const localEnd = ref(props.endTime);

watch(
    () => props.startTime,
    (v) => {
        localStart.value = v;
    },
);
watch(
    () => props.endTime,
    (v) => {
        localEnd.value = v;
    },
);

const effectiveDuration = computed(() => {
    return Math.max(props.duration || 3600, localEnd.value + 60);
});

// Zoom Window calculations
const windowStart = computed(() => {
    if (zoomMode.value === "10m") {
        return Math.max(0, localStart.value - 300);
    }
    if (zoomMode.value === "30m") {
        return Math.max(0, localStart.value - 900);
    }
    return 0;
});

const windowEnd = computed(() => {
    if (zoomMode.value === "10m") {
        return Math.min(effectiveDuration.value, windowStart.value + 600);
    }
    if (zoomMode.value === "30m") {
        return Math.min(effectiveDuration.value, windowStart.value + 1800);
    }
    return effectiveDuration.value;
});

const windowSpan = computed(() => {
    return Math.max(1, windowEnd.value - windowStart.value);
});

const startPercent = computed(() => {
    return ((localStart.value - windowStart.value) / windowSpan.value) * 100;
});

const endPercent = computed(() => {
    return ((localEnd.value - windowStart.value) / windowSpan.value) * 100;
});

const currentPercent = computed(() => {
    return ((props.currentTime - windowStart.value) / windowSpan.value) * 100;
});

const selectedDurationSec = computed(() => {
    return Math.max(0, localEnd.value - localStart.value);
});

function formatSecondsToTimestamp(secs) {
    if (isNaN(secs) || secs < 0) return "00:00:00";
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = Math.floor(secs % 60);
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(h)}:${pad(m)}:${pad(s)}`;
}

function getTimeFromEvent(e) {
    if (!trackRef.value) return 0;
    const rect = trackRef.value.getBoundingClientRect();
    const clientX =
        e.touches && e.touches.length > 0 ? e.touches[0].clientX : e.clientX;
    const offsetX = Math.max(0, Math.min(rect.width, clientX - rect.left));
    const ratio = offsetX / rect.width;
    return Math.round(windowStart.value + ratio * windowSpan.value);
}

function onTrackClick(e) {
    if (draggingHandle.value) return;
    const clickedSec = getTimeFromEvent(e);
    emit("seek", clickedSec);
}

function applyPreset(presetSecs) {
    let targetEnd = localStart.value + presetSecs;
    if (targetEnd > effectiveDuration.value) {
        targetEnd = effectiveDuration.value;
    }
    localEnd.value = targetEnd;
    emit("update:endTime", localEnd.value);
}

function nudgeStart(deltaSecs) {
    const minAllowedStart = Math.max(
        0,
        localEnd.value - props.maxAllowedDuration,
    );
    const maxAllowedStart = localEnd.value - 1;
    const newStart = Math.max(
        minAllowedStart,
        Math.min(localStart.value + deltaSecs, maxAllowedStart),
    );
    localStart.value = newStart;
    emit("update:startTime", newStart);
    emit("seek", newStart);
}

function nudgeEnd(deltaSecs) {
    const minAllowedEnd = localStart.value + 1;
    const maxAllowedEnd = Math.min(
        effectiveDuration.value,
        localStart.value + props.maxAllowedDuration,
    );
    const newEnd = Math.max(
        minAllowedEnd,
        Math.min(localEnd.value + deltaSecs, maxAllowedEnd),
    );
    localEnd.value = newEnd;
    emit("update:endTime", newEnd);
    emit("seek", newEnd);
}

function startDrag(handleType, e) {
    draggingHandle.value = handleType;

    const onMove = (moveEvent) => {
        if (!draggingHandle.value) return;
        const targetSec = getTimeFromEvent(moveEvent);

        if (draggingHandle.value === "in") {
            const minAllowedStart = Math.max(
                0,
                localEnd.value - props.maxAllowedDuration,
            );
            const maxAllowedStart = localEnd.value - 1;
            const newStart = Math.max(
                minAllowedStart,
                Math.min(targetSec, maxAllowedStart),
            );
            localStart.value = newStart;
            emit("update:startTime", newStart);
            emit("seek", newStart);
        } else if (draggingHandle.value === "out") {
            const minAllowedEnd = localStart.value + 1;
            const maxAllowedEnd = Math.min(
                effectiveDuration.value,
                localStart.value + props.maxAllowedDuration,
            );
            const newEnd = Math.max(
                minAllowedEnd,
                Math.min(targetSec, maxAllowedEnd),
            );
            localEnd.value = newEnd;
            emit("update:endTime", newEnd);
            emit("seek", newEnd);
        }
    };

    const onEnd = () => {
        draggingHandle.value = null;
        window.removeEventListener("mousemove", onMove);
        window.removeEventListener("mouseup", onEnd);
        window.removeEventListener("touchmove", onMove);
        window.removeEventListener("touchend", onEnd);
    };

    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", onEnd);
    window.addEventListener("touchmove", onMove, { passive: false });
    window.addEventListener("touchend", onEnd);
}

onUnmounted(() => {
    draggingHandle.value = null;
});
</script>
