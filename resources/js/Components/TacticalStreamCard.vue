<script setup>
/**
 * ==============================================================================
 * TACTICAL STREAM CARD - COMPONENT EXTRACTION (LEVEL 1 REFACTORING)
 * ==============================================================================
 * 
 * Sub-komponen independen untuk menampilkan 1 kartu perwira patroli (stream).
 * Digunakan baik di Multiview Grid Utama maupun di Supporting Units (Focus Mode).
 */
import { computed } from "vue";

// SVG Icon Assets
import iconPlayAll from "@/Components/Icons/play-full-svgrepo-com.svg";
import iconPause from "@/Components/Icons/controller-paus-svgrepo-com.svg";
import iconMute from "@/Components/Icons/audio-off-svgrepo-com.svg";
import iconUnmute from "@/Components/Icons/audio-svgrepo-com.svg";
import iconFocus from "@/Components/Icons/focus-point-round-844-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";
import iconChat from "@/Components/Icons/chat-svgrepo-com.svg";
import iconChatRemove from "@/Components/Icons/chat-remove-svgrepo-com.svg";

const props = defineProps({
    stream: { type: Object, required: true },
    activeAudioVideoId: { type: [String, Number], default: null },
    activeChatVideoId: { type: [String, Number], default: null },
    originUrl: { type: String, default: "" },
    customOrderRank: { type: Number, default: 0 },
    isVideoPlaying: { type: Boolean, default: false },
});

const emit = defineEmits([
    "toggle-audio",
    "toggle-stop-feed",
    "set-focus-stream",
    "handle-chat-click",
    "toggle-officer-status",
    "update:selectedLayout",
]);

const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD"))
        return "bg-blue-900/60 text-blue-200 border-blue-700/80";
    if (d.includes("BCSO") || d.includes("LSCSD"))
        return "bg-amber-900/60 text-amber-200 border-amber-700/80";
    if (d.includes("SASP"))
        return "bg-teal-900/60 text-teal-200 border-teal-700/80";
    if (d.includes("SAPR") || d.includes("RANGER"))
        return "bg-emerald-900/60 text-emerald-200 border-emerald-700/80";
    return "bg-slate-800 text-slate-300 border-slate-700";
};
</script>

<template>
    <div
        :class="[
            customOrderRank > 0
                ? 'border-amber-500/40 shadow-md shadow-amber-950/20'
                : 'border-slate-800/80 hover:border-slate-700',
            activeChatVideoId === stream.video_id
                ? 'border-amber-500/60 shadow-md shadow-amber-950/30'
                : '',
        ]"
        class="bg-slate-950 rounded-xl overflow-hidden border transition flex flex-col relative group"
    >
        <!-- HEADER HUD -->
        <div
            class="bg-slate-900 px-3 py-1.5 flex items-center justify-between border-b border-slate-800 z-10"
        >
            <div class="flex items-center space-x-2 min-w-0">
                <span
                    class="px-1.5 py-0.5 text-[11px] font-black rounded border tracking-wider shrink-0 opacity-90"
                    :class="getDeptBadgeClass(stream.officer?.department)"
                >
                    {{ stream.officer?.department || "UNIT" }}
                </span>
                <span
                    class="text-xs font-semibold text-slate-200 block truncate opacity-90"
                >
                    {{ stream.officer?.officer_name }}
                </span>
            </div>
            <div class="flex items-center space-x-1 shrink-0">
                <!-- Audio Switch Button -->
                <button
                    @click="emit('toggle-audio', stream.video_id)"
                    :class="
                        activeAudioVideoId === stream.video_id
                            ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40 font-bold'
                            : 'text-slate-400 hover:text-slate-200 bg-slate-900/60 border-slate-800'
                    "
                    class="px-2 py-1 rounded-md transition font-mono border text-[10px] flex items-center gap-1.5 cursor-pointer"
                    :title="
                        activeAudioVideoId === stream.video_id
                            ? 'Mute Audio'
                            : 'Play Audio'
                    "
                >
                    <img
                        :src="
                            activeAudioVideoId === stream.video_id
                                ? iconUnmute
                                : iconMute
                        "
                        class="w-3 h-3 invert opacity-80"
                    />
                    <span>{{
                        activeAudioVideoId === stream.video_id
                            ? "Mute"
                            : "Audio"
                    }}</span>
                </button>
            </div>
        </div>

        <!-- VIDEO DISPLAY: IFRAME OR POSTER THUMBNAIL -->
        <div
            class="relative w-full aspect-video bg-black overflow-hidden flex-1 group/thumb"
        >
            <template v-if="isVideoPlaying">
                <iframe
                    :key="`grid-iframe-${stream.video_id}`"
                    :id="`yt-bodycam-${stream.video_id}`"
                    class="w-full h-full border-0 pointer-events-auto"
                    :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                ></iframe>
                <button
                    @click="emit('toggle-stop-feed', stream.video_id)"
                    class="absolute top-2 left-2 bg-black/80 hover:bg-red-900/80 text-white text-[10px] px-2 py-0.5 rounded border border-white/20 z-20 font-mono cursor-pointer"
                    title="Tutup Video / Stop Feed"
                >
                    ✕ Stop Feed
                </button>
            </template>
            <template v-else>
                <img
                    :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                    :alt="stream.title"
                    class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 transition duration-300 cursor-pointer"
                    loading="lazy"
                    @click="emit('set-focus-stream', stream.video_id)"
                />
                <div
                    class="absolute inset-0 bg-slate-950/70 flex flex-col items-center justify-center p-3 pointer-events-none"
                >
                    <div
                        class="absolute top-2 left-2 flex items-center space-x-1 font-mono text-[9px] text-red-400 bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800"
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"
                        ></span>
                        <span>10-8 LIVE</span>
                    </div>

                    <!-- Desktop Hover Overlay Controls (Hidden on Mobile) -->
                    <div
                        class="hidden sm:flex items-center space-x-2 opacity-0 group-hover/thumb:opacity-100 transition-all duration-300 transform scale-95 group-hover/thumb:scale-100 pointer-events-auto"
                    >
                        <button
                            @click.stop="emit('toggle-stop-feed', stream.video_id)"
                            :class="
                                isVideoPlaying
                                    ? 'text-amber-400 border-slate-700'
                                    : 'text-emerald-400 border-slate-700'
                            "
                            class="bg-slate-800 hover:bg-slate-700 border px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <img
                                :src="isVideoPlaying ? iconPause : iconPlayAll"
                                class="w-3.5 h-3.5 invert opacity-90"
                            />
                            <span>{{ isVideoPlaying ? "Stop" : "Play" }}</span>
                        </button>
                        <button
                            @click.stop="emit('set-focus-stream', stream.video_id)"
                            class="bg-slate-800 hover:bg-slate-700 text-blue-400 border border-slate-700 px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <img
                                :src="iconFocus"
                                class="w-3.5 h-3.5 invert opacity-90"
                            />
                            <span>Focus</span>
                        </button>
                    </div>

                    <!-- Mobile Direct Tap Focus Badge (< sm) -->
                    <div
                        @click.stop="emit('set-focus-stream', stream.video_id)"
                        class="sm:hidden flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-blue-600/90 hover:bg-blue-500 text-white font-bold text-xs shadow-xl border border-blue-400/40 cursor-pointer pointer-events-auto animate-pulse"
                    >
                        <img
                            :src="iconFocus"
                            class="w-3.5 h-3.5 invert"
                            alt=""
                        />
                        <span>Ketuk untuk Fokus</span>
                    </div>
                </div>
            </template>

            <!-- Desktop Floating Hover Overlay Bar (Hidden on mobile) -->
            <div
                class="hidden sm:flex absolute bottom-2 left-2 right-2 opacity-0 group-hover/thumb:opacity-100 transition-all duration-200 bg-slate-900 px-2.5 py-1.5 rounded-md border border-slate-800 items-center justify-between z-20 pointer-events-auto text-[11px] text-slate-300 gap-1.5"
            >
                <div
                    class="flex items-center space-x-1.5 min-w-0 flex-1 truncate"
                >
                    <span
                        v-if="stream.officer?.callsign"
                        class="hidden sm:inline-block px-1.5 py-0.5 rounded bg-slate-800/90 text-slate-300 border border-slate-700/60 font-mono text-[9px] font-semibold tracking-wider shrink-0"
                    >
                        {{ stream.officer.callsign }}
                    </span>
                    <span
                        v-if="stream.officer?.rank"
                        class="font-mono text-[10px] text-slate-300 truncate"
                    >
                        {{ stream.officer.rank }}
                    </span>
                </div>
                <div class="flex items-center space-x-1 shrink-0">
                    <button
                        @click.stop="emit('toggle-officer-status', stream)"
                        class="px-2 py-0.5 bg-slate-900/90 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/80 rounded-md text-[10px] font-mono font-semibold transition shrink-0 cursor-pointer flex items-center gap-1"
                        title="Sembunyikan perwira ini dari grid"
                    >
                        <span>Sembunyikan</span>
                    </button>
                    <button
                        @click="
                            emit('update:selectedLayout', 'focus');
                            emit('set-focus-stream', stream.video_id);
                        "
                        class="p-1 hover:text-blue-400 text-slate-300 rounded hover:bg-slate-800 transition cursor-pointer"
                        title="Focus"
                    >
                        <img
                            :src="iconFocus"
                            class="w-3.5 h-3.5 invert opacity-80 hover:opacity-100"
                        />
                    </button>
                    <button
                        @click="emit('handle-chat-click', stream.video_id)"
                        :class="
                            activeChatVideoId === stream.video_id
                                ? 'bg-slate-800 text-blue-400 border border-slate-700'
                                : 'text-slate-300 hover:text-blue-400 hover:bg-slate-800'
                        "
                        class="p-1 rounded transition flex items-center justify-center cursor-pointer"
                    >
                        <img
                            :src="
                                activeChatVideoId === stream.video_id
                                    ? iconChatRemove
                                    : iconChat
                            "
                            class="w-3.5 h-3.5 invert opacity-90"
                        />
                    </button>
                    <a
                        :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                        target="_blank"
                        class="p-1 hover:text-white text-slate-300 rounded hover:bg-slate-800 transition"
                    >
                        <img
                            :src="iconExternal"
                            class="w-3.5 h-3.5 invert opacity-80 hover:opacity-100"
                        />
                    </a>
                </div>
            </div>
        </div>

        <!-- PERMANENT MOBILE ACTION CARD FOOTER (sm:hidden) - OPTION 1 -->
        <div
            class="sm:hidden bg-slate-900 border-t border-slate-800/80 px-3 py-2 flex items-center justify-between gap-2 z-10"
        >
            <div class="flex items-center space-x-1.5 min-w-0 flex-1">
                <span
                    v-if="stream.officer?.callsign"
                    class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700/60 font-mono text-[10px] font-semibold shrink-0"
                >
                    {{ stream.officer.callsign }}
                </span>
                <span class="font-mono text-xs text-slate-300 truncate">
                    {{
                        stream.officer?.rank ||
                        stream.officer?.officer_name ||
                        stream.title
                    }}
                </span>
            </div>
            <div class="flex items-center space-x-1.5 shrink-0">
                <!-- Play / Stop Toggle Button -->
                <button
                    @click.stop="emit('toggle-stop-feed', stream.video_id)"
                    :class="
                        isVideoPlaying
                            ? 'text-amber-400 border-slate-700'
                            : 'text-emerald-400 border-slate-700'
                    "
                    class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 border rounded-md text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95"
                >
                    <img
                        :src="isVideoPlaying ? iconPause : iconPlayAll"
                        class="w-3 h-3 invert"
                    />
                    <span>{{ isVideoPlaying ? "Stop" : "Play" }}</span>
                </button>

                <!-- Focus Button -->
                <button
                    @click.stop="emit('set-focus-stream', stream.video_id)"
                    class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded-md text-xs font-bold transition flex items-center gap-1 cursor-pointer active:scale-95 shadow-sm"
                >
                    <img :src="iconFocus" class="w-3.5 h-3.5 invert" />
                    <span>Fokus</span>
                </button>

                <!-- Chat Button -->
                <button
                    @click.stop="emit('handle-chat-click', stream.video_id)"
                    :class="
                        activeChatVideoId === stream.video_id
                            ? 'bg-amber-600 text-white border-amber-500'
                            : 'bg-slate-800 text-slate-300 border-slate-700'
                    "
                    class="p-1.5 rounded-md border transition flex items-center justify-center cursor-pointer active:scale-95"
                    title="Live Chat"
                >
                    <img :src="iconChat" class="w-3.5 h-3.5 invert" />
                </button>
            </div>
        </div>
    </div>
</template>
