<script setup>
defineProps({
    isMobileLandscape: {
        type: Boolean,
        default: false,
    },
    showLandscapeControls: {
        type: Boolean,
        default: false,
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["toggle-fullscreen"]);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isMobileLandscape && showLandscapeControls"
            class="fixed top-3 right-3 z-[100] flex items-center gap-2 bg-slate-950/85 backdrop-blur-md border border-slate-800 rounded-full px-3.5 py-1.5 shadow-2xl text-xs font-mono text-slate-200 animate-in fade-in duration-200"
        >
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[10px] font-bold text-slate-300">LANDSCAPE MODE</span>
            <button
                @click.stop="emit('toggle-fullscreen')"
                class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white font-bold text-[10px] rounded-full transition cursor-pointer shadow-sm"
            >
                {{ isFullscreen ? 'Keluar Fullscreen' : 'Fullscreen' }}
            </button>
        </div>
    </Teleport>
</template>
