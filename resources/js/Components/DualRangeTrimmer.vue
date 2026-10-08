<template>
    <div class="space-y-3 font-sans select-none">
        <!-- Header Info, Zoom Level & Presets -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <!-- Left: Timeline Markers -->
            <div class="flex items-center gap-2 font-mono">
                <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-300 border border-slate-800 text-[10px]">
                    IN: <strong class="text-white">{{ formatSecondsToTimestamp(localStart) }}</strong>
                </span>
                <span class="text-slate-600">➔</span>
                <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-300 border border-slate-800 text-[10px]">
                    OUT: <strong class="text-white">{{ formatSecondsToTimestamp(localEnd) }}</strong>
                </span>
                <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-900/50 font-bold text-[10px]">
                    DURASI: {{ selectedDurationSec }}s
                </span>
            </div>

            <!-- Right: Zoom Level Selector & Presets -->
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-mono">
                <!-- Zoom Level Toggle -->
                <div class="flex items-center bg-slate-950 border border-slate-800 rounded-md p-0.5">
                    <button
                        @click="zoomMode = 'full'"
                        type="button"
                        class="px-2 py-0.5 rounded text-[10px] font-bold transition cursor-pointer"
                        :class="zoomMode === 'full' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                    >
                        🌐 Full
                    </button>
                    <button
                        @click="zoomMode = '30m'"
                        type="button"
                        class="px-2 py-0.5 rounded text-[10px] font-bold transition cursor-pointer"
                        :class="zoomMode === '30m' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                    >
                        🔍 30m
                    </button>
                    <button
                        @click="zoomMode = '10m'"
                        type="button"
                        class="px-2 py-0.5 rounded text-[10px] font-bold transition cursor-pointer"
                        :class="zoomMode === '10m' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                    >
                        🔍 10m
                    </button>
                </div>

                <!-- Presets -->
                <div class="flex items-center gap-1">
                    <button
                        v-for="preset in [15, 30, 60, 180, 300]"
                        :key="preset"
                        @click="applyPreset(preset)"
                        type="button"
                        class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition font-bold cursor-pointer text-[10px]"
                    >
                        +{{ preset >= 60 ? (preset / 60) + 'm' : preset + 's' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Interactive Timeline Track Bar -->
        <div
            ref="trackRef"
            @click="onTrackClick"
            class="relative h-11 bg-slate-950 border border-slate-800 rounded-xl overflow-hidden cursor-pointer shadow-inner touch-none flex items-center"
        >
            <!-- Timeline Scale Background Grid -->
            <div class="absolute inset-0 flex justify-between px-2 opacity-15 pointer-events-none">
                <div v-for="i in 10" :key="i" class="h-full w-px bg-slate-400"></div>
            </div>

            <!-- Selected Range Highlight Fill -->
            <div
                v-if="endPercent > 0 && startPercent < 100"
                class="absolute h-full bg-blue-600/20 border-y border-blue-500/40 transition-all duration-75"
                :style="{
                    left: `${Math.max(0, startPercent)}%`,
                    width: `${Math.min(100 - Math.max(0, startPercent), Math.max(0, endPercent - startPercent))}%`
                }"
            ></div>

            <!-- Playhead Position Line -->
            <div
                v-if="currentPercent >= 0 && currentPercent <= 100"
                class="absolute top-0 bottom-0 w-0.5 bg-blue-400 z-20 shadow-sm pointer-events-none"
                :style="{ left: `${currentPercent}%` }"
            >
                <div class="w-2 h-2 -ml-0.75 bg-blue-400 rotate-45 rounded-xs"></div>
            </div>

            <!-- IN Handle -->
            <div
                v-if="startPercent >= -5 && startPercent <= 105"
                @mousedown.stop.prevent="startDrag('in', $event)"
                @touchstart.stop.prevent="startDrag('in', $event)"
                class="absolute top-0 bottom-0 w-5 -ml-2.5 z-30 cursor-ew-resize flex items-center justify-center group"
                :style="{ left: `${startPercent}%` }"
            >
                <div class="w-3.5 h-9 bg-slate-200 hover:bg-white rounded-md shadow-md border border-slate-400 flex flex-col items-center justify-center gap-0.5 transition group-hover:scale-105">
                    <div class="w-0.5 h-2.5 bg-slate-700 rounded-full"></div>
                    <div class="w-0.5 h-2.5 bg-slate-700 rounded-full"></div>
                </div>
                <!-- Handle Tooltip -->
                <div class="absolute -top-7 bg-slate-950 border border-slate-800 text-slate-200 px-1.5 py-0.5 rounded text-[9px] font-mono font-bold shadow whitespace-nowrap opacity-0 group-hover:opacity-100 transition">
                    IN: {{ formatSecondsToTimestamp(localStart) }}
                </div>
            </div>

            <!-- OUT Handle -->
            <div
                v-if="endPercent >= -5 && endPercent <= 105"
                @mousedown.stop.prevent="startDrag('out', $event)"
                @touchstart.stop.prevent="startDrag('out', $event)"
                class="absolute top-0 bottom-0 w-5 -ml-2.5 z-30 cursor-ew-resize flex items-center justify-center group"
                :style="{ left: `${endPercent}%` }"
            >
                <div class="w-3.5 h-9 bg-blue-500 hover:bg-blue-400 rounded-md shadow-md border border-blue-300 flex flex-col items-center justify-center gap-0.5 transition group-hover:scale-105">
                    <div class="w-0.5 h-2.5 bg-blue-950 rounded-full"></div>
                    <div class="w-0.5 h-2.5 bg-blue-950 rounded-full"></div>
                </div>
                <!-- Handle Tooltip -->
                <div class="absolute -top-7 bg-slate-950 border border-slate-800 text-blue-300 px-1.5 py-0.5 rounded text-[9px] font-mono font-bold shadow whitespace-nowrap opacity-0 group-hover:opacity-100 transition">
                    OUT: {{ formatSecondsToTimestamp(localEnd) }}
                </div>
            </div>
        </div>

        <!-- Fine-Tuning Nudge Stepper Controls for HP/Mobile -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] font-mono pt-1">
            <!-- IN Marker Nudge Steppers -->
            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                <span class="text-slate-400 font-semibold text-[10px]">Waktu Mulai (IN):</span>
                <div class="flex items-center gap-1">
                    <button @click="nudgeStart(-5)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">-5s</button>
                    <button @click="nudgeStart(-1)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">-1s</button>
                    <button @click="nudgeStart(1)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">+1s</button>
                    <button @click="nudgeStart(5)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">+5s</button>
                </div>
            </div>

            <!-- OUT Marker Nudge Steppers -->
            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                <span class="text-blue-400 font-semibold text-[10px]">Waktu Selesai (OUT):</span>
                <div class="flex items-center gap-1">
                    <button @click="nudgeEnd(-5)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">-5s</button>
                    <button @click="nudgeEnd(-1)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">-1s</button>
                    <button @click="nudgeEnd(1)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">+1s</button>
                    <button @click="nudgeEnd(5)" type="button" class="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-[10px] font-bold transition cursor-pointer">+5s</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from 'vue';

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
    }
});

const emit = defineEmits(['update:startTime', 'update:endTime', 'seek']);

const trackRef = ref(null);
const draggingHandle = ref(null);

const zoomMode = ref('full'); // 'full', '30m', '10m'

const localStart = ref(props.startTime);
const localEnd = ref(props.endTime);

watch(() => props.startTime, (v) => { localStart.value = v; });
watch(() => props.endTime, (v) => { localEnd.value = v; });

const effectiveDuration = computed(() => {
    return Math.max(props.duration || 3600, localEnd.value + 60);
});

// Zoom Window calculations
const windowStart = computed(() => {
    if (zoomMode.value === '10m') {
        return Math.max(0, localStart.value - 300);
    }
    if (zoomMode.value === '30m') {
        return Math.max(0, localStart.value - 900);
    }
    return 0;
});

const windowEnd = computed(() => {
    if (zoomMode.value === '10m') {
        return Math.min(effectiveDuration.value, windowStart.value + 600);
    }
    if (zoomMode.value === '30m') {
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
    if (isNaN(secs) || secs < 0) return '00:00:00';
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = Math.floor(secs % 60);
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(h)}:${pad(m)}:${pad(s)}`;
}

function getTimeFromEvent(e) {
    if (!trackRef.value) return 0;
    const rect = trackRef.value.getBoundingClientRect();
    const clientX = e.touches && e.touches.length > 0 ? e.touches[0].clientX : e.clientX;
    const offsetX = Math.max(0, Math.min(rect.width, clientX - rect.left));
    const ratio = offsetX / rect.width;
    return Math.round(windowStart.value + (ratio * windowSpan.value));
}

function onTrackClick(e) {
    if (draggingHandle.value) return;
    const clickedSec = getTimeFromEvent(e);
    emit('seek', clickedSec);
}

function applyPreset(presetSecs) {
    let targetEnd = localStart.value + presetSecs;
    if (targetEnd > effectiveDuration.value) {
        targetEnd = effectiveDuration.value;
    }
    localEnd.value = targetEnd;
    emit('update:endTime', localEnd.value);
}

function nudgeStart(deltaSecs) {
    const minAllowedStart = Math.max(0, localEnd.value - props.maxAllowedDuration);
    const maxAllowedStart = localEnd.value - 1;
    const newStart = Math.max(minAllowedStart, Math.min(localStart.value + deltaSecs, maxAllowedStart));
    localStart.value = newStart;
    emit('update:startTime', newStart);
    emit('seek', newStart);
}

function nudgeEnd(deltaSecs) {
    const minAllowedEnd = localStart.value + 1;
    const maxAllowedEnd = Math.min(effectiveDuration.value, localStart.value + props.maxAllowedDuration);
    const newEnd = Math.max(minAllowedEnd, Math.min(localEnd.value + deltaSecs, maxAllowedEnd));
    localEnd.value = newEnd;
    emit('update:endTime', newEnd);
    emit('seek', newEnd);
}

function startDrag(handleType, e) {
    draggingHandle.value = handleType;

    const onMove = (moveEvent) => {
        if (!draggingHandle.value) return;
        const targetSec = getTimeFromEvent(moveEvent);

        if (draggingHandle.value === 'in') {
            const minAllowedStart = Math.max(0, localEnd.value - props.maxAllowedDuration);
            const maxAllowedStart = localEnd.value - 1;
            const newStart = Math.max(minAllowedStart, Math.min(targetSec, maxAllowedStart));
            localStart.value = newStart;
            emit('update:startTime', newStart);
            emit('seek', newStart);
        } else if (draggingHandle.value === 'out') {
            const minAllowedEnd = localStart.value + 1;
            const maxAllowedEnd = Math.min(effectiveDuration.value, localStart.value + props.maxAllowedDuration);
            const newEnd = Math.max(minAllowedEnd, Math.min(targetSec, maxAllowedEnd));
            localEnd.value = newEnd;
            emit('update:endTime', newEnd);
            emit('seek', newEnd);
        }
    };

    const onEnd = () => {
        draggingHandle.value = null;
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onEnd);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);
    };

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onEnd);
    window.addEventListener('touchmove', onMove, { passive: false });
    window.addEventListener('touchend', onEnd);
}

onUnmounted(() => {
    draggingHandle.value = null;
});
</script>
