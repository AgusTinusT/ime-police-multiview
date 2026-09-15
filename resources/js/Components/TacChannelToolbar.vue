<script setup>
import { computed } from 'vue';

const props = defineProps({
    selectedDepartment: {
        type: String,
        required: true
    },
    visibleStreamsCount: {
        type: Number,
        default: 0
    },
    remainingSeconds: {
        type: Number,
        default: 0
    },
    iconRadio: {
        type: String,
        default: ''
    },
    iconClock: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['extendTimer', 'disbandChannel']);

const formatRemainingTime = (totalSeconds) => {
    if (totalSeconds <= 0) return '00:00';
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const departmentTitle = computed(() => {
    return props.selectedDepartment ? props.selectedDepartment.replace('_', ' ') : 'KANAL TAKTIS';
});
</script>

<template>
    <div class="mb-4 space-y-2.5 font-sans">
        <!-- Main Channel HUD Toolbar -->
        <div class="bg-gradient-to-r from-amber-950/70 via-slate-900/95 to-slate-950 border border-amber-500/40 rounded-xl p-3 shadow-xl backdrop-blur flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0">
                    <img v-if="iconRadio" :src="iconRadio" class="w-5 h-5 brightness-0 invert opacity-90" alt="Radio" />
                    <span v-else>📻</span>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-bold text-amber-300 font-mono tracking-wide uppercase">
                            KANAL RADIO TAKTIS: {{ departmentTitle }}
                        </h3>
                        <span class="inline-flex items-center gap-1 text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            {{ visibleStreamsCount }} UNIT TERHUBUNG
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5 font-mono">
                        Kanal radio taktis aktif untuk pemantauan POV bersama.
                    </p>
                </div>
            </div>

            <!-- Timer Countdown Badge -->
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-1.5 bg-black/60 border border-amber-500/30 px-3 py-1.5 rounded-lg font-mono">
                    <img v-if="iconClock" :src="iconClock" class="w-3.5 h-3.5 brightness-0 invert opacity-80" alt="Timer" />
                    <span v-else>⏱️</span>
                    <span class="text-[10px] text-slate-400 uppercase">Sisa Waktu:</span>
                    <span 
                        class="text-xs font-bold" 
                        :class="remainingSeconds <= 60 ? 'text-red-400 animate-pulse' : 'text-amber-300'"
                    >
                        {{ formatRemainingTime(remainingSeconds) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Expiration Alert Banner (Triggers in last 60 seconds) -->
        <div 
            v-if="remainingSeconds > 0 && remainingSeconds <= 60"
            class="bg-red-950/95 border-2 border-red-500 rounded-xl p-3.5 shadow-2xl backdrop-blur flex flex-wrap items-center justify-between gap-3 animate-pulse"
        >
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-red-500/30 border border-red-400 flex items-center justify-center text-red-300 text-base font-bold shrink-0">
                    ⚠️
                </div>
                <div>
                    <h4 class="text-xs font-bold text-red-200 font-mono tracking-wide uppercase">
                        KONFIRMASI SITUASI: WAKTU {{ departmentTitle }} TERSISA {{ formatRemainingTime(remainingSeconds) }}!
                    </h4>
                    <p class="text-[11px] text-red-300/80 mt-0.5">
                        Apakah kanal radio ini masih aktif digunakan, atau telah selesai?
                    </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <button 
                    @click="emit('extendTimer', selectedDepartment, 20)"
                    class="px-3.5 py-1.5 bg-red-600 hover:bg-red-500 text-white font-bold text-xs rounded-lg shadow-lg transition flex items-center gap-1.5 font-mono transform hover:scale-105"
                >
                    <span>🔥 Ya, Lanjutkan (+20 Menit)</span>
                </button>
                <button 
                    @click="emit('disbandChannel', selectedDepartment)"
                    class="px-3 py-1.5 bg-black/60 hover:bg-black/90 text-slate-200 font-bold text-xs rounded-lg border border-slate-600 transition flex items-center gap-1.5 font-mono"
                >
                    <span>✓ Situasi Selesai (Bubarkan)</span>
                </button>
            </div>
        </div>
    </div>
</template>
