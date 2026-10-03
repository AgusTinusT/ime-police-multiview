<script setup>
/**
 * ==============================================================================
 * TAC CHANNEL NOTIFICATION TOAST - COMPONENT EXTRACTION (LEVEL 1 REFACTORING)
 * ==============================================================================
 * 
 * Sub-komponen floating toast untuk menampilkan peringatan kadaluarsa TAC channel 
 * & pesan notifikasi aksi taktis di pojok kanan bawah layar.
 */
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";

defineProps({
    expiringTacChannel: { type: Object, default: null },
    tacticalToast: { type: Object, default: null },
    formatRemainingTime: { type: Function, required: true },
});

const emit = defineEmits([
    "extend-timer",
    "disband-channel",
    "select-department",
]);
</script>

<template>
    <!-- FLOATING TAC CHANNEL EXPIRATION WARNING ALERT -->
    <div
        v-if="expiringTacChannel"
        class="fixed bottom-20 md:bottom-16 right-4 z-[60] bg-slate-950/95 border border-amber-500/80 shadow-2xl rounded-lg px-4 py-3 flex items-center space-x-3 text-xs max-w-sm animate-in slide-in-from-bottom duration-300 pointer-events-auto backdrop-blur-md"
    >
        <div
            class="w-8 h-8 rounded-full bg-amber-500/20 border border-amber-500/40 flex items-center justify-center shrink-0"
        >
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
        </div>
        <div class="flex-1 min-w-0">
            <div class="font-bold text-amber-300 font-mono text-[11px] truncate">
                {{ expiringTacChannel.name }} ({{
                    expiringTacChannel.video_ids?.length || 0
                }}
                Units) tersisa
                {{
                    formatRemainingTime(
                        expiringTacChannel.remaining_seconds,
                    )
                }}
            </div>
            <div class="text-[10px] text-slate-400 truncate">
                Apakah situasi masih berlangsung?
            </div>
        </div>
        <div class="flex items-center space-x-1.5 shrink-0">
            <button
                @click="emit('extend-timer', expiringTacChannel.code, 20)"
                class="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-950 font-bold text-[10px] rounded-md font-mono transition-colors shadow-none cursor-pointer"
            >
                +20m
            </button>
            <button
                @click="emit('disband-channel', expiringTacChannel.code)"
                class="px-2.5 py-1 bg-rose-950/60 hover:bg-rose-900/70 text-rose-400 hover:text-rose-300 text-[10px] rounded-md border border-rose-900/80 font-mono transition-colors shadow-none cursor-pointer"
            >
                Bubarkan
            </button>
            <button
                @click="emit('select-department', expiringTacChannel.code)"
                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 hover:border-slate-600 text-[10px] rounded-md font-mono transition-colors shadow-none cursor-pointer"
            >
                Buka
            </button>
        </div>
    </div>

    <!-- FLOATING TACTICAL ACTION TOAST -->
    <div
        v-if="tacticalToast"
        class="fixed bottom-20 md:bottom-16 right-4 z-[60] bg-slate-900 border border-amber-800 rounded-md px-4 py-2.5 flex items-center space-x-2.5 text-xs font-mono text-amber-300 animate-in slide-in-from-bottom duration-200 pointer-events-auto"
    >
        <img
            :src="iconRadio"
            class="w-4 h-4 brightness-0 invert opacity-90 shrink-0"
            alt=""
        />
        <span>{{ tacticalToast.message }}</span>
    </div>
</template>
