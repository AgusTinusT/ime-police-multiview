<script setup>
const props = defineProps({
    stream: {
        type: Object,
        required: true
    },
    originUrl: {
        type: String,
        default: ''
    },
    chatEmbedDomain: {
        type: String,
        default: 'localhost'
    },
    isPersonal: {
        type: Boolean,
        default: false
    },
    deptBadgeClass: {
        type: String,
        default: 'bg-blue-950 text-blue-300 border-blue-500/40'
    },
    iconPinPlus: {
        type: String,
        default: ''
    },
    iconPinMinus: {
        type: String,
        default: ''
    },
    iconExternal: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['focus', 'togglePersonal', 'openSubscribe', 'toggleChat']);
</script>

<template>
    <div class="bg-slate-950 rounded-2xl overflow-hidden border border-slate-800/90 shadow-xl relative flex flex-col group transition duration-300 hover:border-blue-500/40 font-sans">
        <!-- HUD Header Top Bar -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 px-3 py-2 border-b border-slate-800 flex items-center justify-between gap-1.5">
            <div class="flex items-center space-x-2 min-w-0 flex-1 truncate">
                <span class="px-2 py-0.5 text-[10px] font-black rounded border tracking-wider shrink-0" :class="deptBadgeClass">
                    [{{ stream.officer?.department || 'UNIT' }}] {{ stream.officer?.callsign || 'TAC' }}
                </span>
                <span class="text-xs font-bold text-slate-100 truncate">{{ stream.officer?.officer_name || 'Officer Feed' }}</span>
            </div>

            <div class="flex items-center space-x-1 shrink-0">
                <!-- Focus Stream Button -->
                <button 
                    @click="emit('focus', stream)"
                    class="p-1 rounded-lg bg-slate-900 hover:bg-blue-600 text-slate-300 hover:text-white transition text-xs border border-slate-700"
                    title="Fokuskan Stream Ini"
                >
                    🔍
                </button>

                <!-- Personal Watchlist Toggle Button -->
                <button 
                    @click="emit('togglePersonal', stream.video_id)"
                    class="p-1 rounded-lg transition text-xs border"
                    :class="isPersonal ? 'bg-purple-900/80 text-purple-200 border-purple-500' : 'bg-slate-900 text-slate-300 border-slate-700 hover:text-purple-300'"
                    :title="isPersonal ? 'Hapus dari Watchlist' : 'Tambah ke Personal Watchlist'"
                >
                    <img v-if="isPersonal ? iconPinMinus : iconPinPlus" :src="isPersonal ? iconPinMinus : iconPinPlus" class="w-3.5 h-3.5 invert" alt="" />
                    <span v-else>📌</span>
                </button>
            </div>
        </div>

        <!-- Video Player Iframe Container -->
        <div class="aspect-video bg-black relative overflow-hidden group">
            <iframe 
                :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`" 
                class="w-full h-full border-0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                allowfullscreen
            ></iframe>
        </div>

        <!-- HUD Card Footer -->
        <div class="p-2.5 bg-slate-950 flex items-center justify-between border-t border-slate-900 gap-2">
            <div class="min-w-0 flex-1">
                <h5 class="text-xs font-bold text-slate-200 truncate leading-snug">{{ stream.title }}</h5>
                <p class="text-[10px] text-slate-400 font-mono truncate mt-0.5">{{ stream.officer?.rank || stream.officer?.streamer_name || 'Active Patrol' }}</p>
            </div>

            <div class="flex items-center space-x-1 shrink-0">
                <!-- Subscribe Button -->
                <button 
                    @click="emit('openSubscribe', stream.officer?.channel_id || stream.officer?.handle, stream.officer?.officer_name)"
                    class="px-2 py-1 rounded bg-red-600 hover:bg-red-500 text-white font-mono text-[10px] font-bold shadow"
                >
                    Sub
                </button>

                <!-- YouTube Popout Link -->
                <a 
                    :href="`https://www.youtube.com/watch?v=${stream.video_id}`" 
                    target="_blank"
                    class="p-1 text-slate-400 hover:text-white transition"
                    title="Buka di YouTube"
                >
                    <img v-if="iconExternal" :src="iconExternal" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                    <span v-else>↗</span>
                </a>
            </div>
        </div>
    </div>
</template>
