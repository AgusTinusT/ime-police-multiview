<script setup>
import { ref } from 'vue';

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
    isMuted: {
        type: Boolean,
        default: true
    },
    tacCode: {
        type: String,
        default: null
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
    },
    iconFocus: {
        type: String,
        default: ''
    },
    iconChat: {
        type: String,
        default: ''
    },
    iconRadio: {
        type: String,
        default: ''
    },
    iconMute: {
        type: String,
        default: ''
    },
    iconUnmute: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['focus', 'togglePersonal', 'openSubscribe', 'toggleChat', 'toggleAudio', 'openTacPopover']);
</script>

<template>
    <div class="bg-slate-950 rounded-2xl border border-slate-800/90 shadow-xl relative flex flex-col group transition-all duration-300 hover:border-blue-500/50 hover:shadow-2xl hover:shadow-blue-950/30 font-sans">
        
        <!-- Header Minimalis Top Bar -->
        <div class="bg-slate-900/95 backdrop-blur-md px-3 py-1.5 border-b border-slate-800/80 flex items-center justify-between gap-1.5 relative z-30 rounded-t-2xl">
            <div class="flex items-center space-x-2 min-w-0 flex-1 truncate">
                <!-- Badge Kesatuan & Callsign -->
                <span class="px-2 py-0.5 text-[10px] font-black rounded border tracking-wider shrink-0" :class="deptBadgeClass">
                    [{{ stream.officer?.department || 'UNIT' }}] {{ stream.officer?.callsign || 'TAC' }}
                </span>

                <!-- Nama Officer dengan Tooltip Informasi Sekunder (Patrol Zone, Badge Number, Rank) -->
                <div class="relative group/tooltip inline-flex items-center min-w-0 flex-1 cursor-help">
                    <span class="text-xs font-bold text-slate-100 truncate border-b border-dashed border-slate-600/40 hover:border-blue-400 transition-colors">
                        {{ stream.officer?.officer_name || 'Officer Feed' }}
                    </span>
                    <!-- Hover Tooltip Overlay -->
                    <div class="absolute top-full left-0 mt-1.5 hidden group-hover/tooltip:flex flex-col gap-0.5 bg-slate-950/95 text-slate-200 text-[10px] font-mono px-2.5 py-1.5 rounded-lg border border-slate-700/80 shadow-2xl backdrop-blur-md whitespace-nowrap z-50 pointer-events-none animate-in fade-in duration-150">
                        <div>🎖️ Badge: <span class="text-slate-100 font-bold">{{ stream.officer?.badge_number || '#000' }}</span></div>
                        <div v-if="stream.officer?.rank" class="text-slate-400">🔰 {{ stream.officer.rank }}</div>
                        <div v-if="stream.title" class="text-slate-400 max-w-[200px] truncate text-[9px] italic mt-0.5 border-t border-slate-800 pt-0.5">{{ stream.title }}</div>
                    </div>
                </div>
            </div>

            <!-- Kontrol Utama Top Header: Audio ON/MUTED & Radio TAC -->
            <div class="flex items-center space-x-1.5 shrink-0">
                <!-- 1-Click Radio TAC Button -->
                <button 
                    @click.stop="emit('openTacPopover', stream.video_id)" 
                    class="px-2 py-0.5 text-[10px] rounded transition flex items-center gap-1 font-mono border"
                    :class="tacCode ? 'text-amber-300 bg-amber-950/80 border-amber-500/60 font-bold shadow-sm' : 'text-slate-400 hover:text-amber-300 bg-slate-800 border-slate-700'"
                    :title="tacCode ? `Terhubung ke ${tacCode.replace('_', ' ')}` : 'Hubungkan ke TAC Radio'"
                >
                    <img v-if="iconRadio" :src="iconRadio" class="w-3 h-3 brightness-0 invert opacity-90" alt="" />
                    <span class="text-[10px]">{{ tacCode ? tacCode.replace('_', ' ') : 'TAC' }}</span>
                </button>

                <!-- Audio Toggle Button -->
                <button 
                    @click.stop="emit('toggleAudio', stream.video_id)" 
                    :class="!isMuted ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/40' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    class="px-2 py-0.5 text-[10px] rounded transition flex items-center gap-1 font-mono"
                    :title="!isMuted ? 'Audio Aktif (Mute)' : 'Nyalakan Audio'"
                >
                    <img v-if="!isMuted ? iconUnmute : iconMute" :src="!isMuted ? iconUnmute : iconMute" class="w-3 h-3 invert" alt="" />
                    <span>{{ !isMuted ? 'ON' : 'MUTED' }}</span>
                </button>
            </div>
        </div>

        <!-- Video Player Container (Takes full vertical body space without static bottom footer) -->
        <div class="aspect-video bg-black relative overflow-hidden rounded-b-2xl flex-1 group/player">
            <iframe 
                :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`" 
                class="w-full h-full border-0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                allowfullscreen
            ></iframe>

            <!-- Floating Action Bar Overlay (Melayang saat Hover kursor) -->
            <div class="absolute bottom-2 left-2 right-2 z-30 opacity-0 group-hover/player:opacity-100 group-hover:opacity-100 transition-all duration-250 transform translate-y-1 group-hover/player:translate-y-0 flex items-center justify-between p-1.5 rounded-xl bg-slate-950/85 backdrop-blur-md border border-slate-700/60 shadow-2xl pointer-events-auto">
                
                <!-- Left Quick Action: Focus & Watchlist Pin -->
                <div class="flex items-center space-x-1.5">
                    <!-- Focus Mode Button -->
                    <button 
                        @click.stop="emit('focus', stream)"
                        class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold shadow transition flex items-center gap-1"
                        title="Fokuskan Video Utama"
                    >
                        <img v-if="iconFocus" :src="iconFocus" class="w-3 h-3 invert" alt="" />
                        <span v-else>🔍</span>
                        <span>Focus</span>
                    </button>

                    <!-- Personal Watchlist Pin Button (Live Streams Only) -->
                    <button 
                        v-if="stream.status === 'LIVE'"
                        @click.stop="emit('togglePersonal', stream.video_id)"
                        class="p-1 rounded-lg transition text-[10px] border"
                        :class="isPersonal ? 'bg-purple-900/80 text-purple-200 border-purple-500' : 'bg-slate-900 text-slate-300 border-slate-700 hover:text-purple-300'"
                        :title="isPersonal ? 'Hapus dari Watchlist' : 'Tambah ke Personal Watchlist'"
                    >
                        <img v-if="isPersonal ? iconPinMinus : iconPinPlus" :src="isPersonal ? iconPinMinus : iconPinPlus" class="w-3.5 h-3.5 invert" alt="" />
                        <span v-else>📌</span>
                    </button>
                </div>

                <!-- Right Quick Action: Live Chat, Subscribe & External Link -->
                <div class="flex items-center space-x-1">
                    <!-- Live Chat Drawer Toggle -->
                    <button 
                        @click.stop="emit('toggleChat', stream.video_id)"
                        class="p-1 rounded-lg bg-slate-900 text-slate-300 hover:text-amber-400 border border-slate-700 transition"
                        title="Buka Live Chat"
                    >
                        <img v-if="iconChat" :src="iconChat" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                        <span v-else>💬</span>
                    </button>

                    <!-- Subscribe Button -->
                    <button 
                        @click.stop="emit('openSubscribe', stream.officer?.handle || stream.officer?.channel_id, stream.officer?.officer_name, stream.video_id, stream.officer?.handle)"
                        class="px-2 py-1 rounded-lg bg-red-600 hover:bg-red-500 text-white font-mono text-[10px] font-bold shadow transition"
                        title="Subscribe tanpa meninggalkan halaman"
                    >
                        Sub
                    </button>

                    <!-- YouTube Popout Link -->
                    <a 
                        :href="`https://www.youtube.com/watch?v=${stream.video_id}`" 
                        target="_blank"
                        class="p-1 text-slate-400 hover:text-white transition"
                        title="Buka di YouTube"
                        @click.stop
                    >
                        <img v-if="iconExternal" :src="iconExternal" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                        <span v-else>↗</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
