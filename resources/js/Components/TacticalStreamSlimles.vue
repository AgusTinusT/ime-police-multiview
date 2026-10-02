<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";

const props = defineProps({
    allActiveStreams: { type: Array, default: () => [] },
    trendingStreams: { type: Array, default: () => [] },
    recentReplayStreams: { type: Array, default: () => [] },
    isSyncingFeeds: { type: Boolean, default: false },
    tacChannels: { type: Array, default: () => [] },
});

const emit = defineEmits(["play-stream-in-focus", "trigger-manual-sync"]);

// Filter tabs state: 'ALL' | 'LIVE' | 'REPLAY'
const activeFilter = ref("ALL");

const parseRelativeTimeToSeconds = (str) => {
    if (!str) return 999999999;
    const text = String(str).toLowerCase().trim();
    if (
        text.includes("baru saja") ||
        text.includes("just now") ||
        text.includes("live") ||
        text.includes("sedang tayang")
    )
        return 0;

    const numMatch = text.match(/(\d+)/);
    const num = numMatch ? parseInt(numMatch[1], 10) : 1;
    const isIndonesian = text.includes("lalu");

    if (
        text.includes("tahun") ||
        text.includes("year") ||
        text.match(/\b\d+\s*(?:y|thn|th)\b/)
    )
        return num * 31536000;
    if (
        text.includes("bulan") ||
        text.includes("month") ||
        text.match(/\b\d+\s*(?:mo|bln)\b/)
    )
        return num * 2592000;
    if (
        text.includes("minggu") ||
        text.includes("week") ||
        text.match(/\b\d+\s*w\b/)
    )
        return num * 604800;
    if (
        text.includes("hari") ||
        text.includes("day") ||
        (isIndonesian && text.match(/\b\d+\s*h\b/)) ||
        (!isIndonesian && text.match(/\b\d+\s*d\b/))
    )
        return num * 86400;
    if (
        text.includes("jam") ||
        text.includes("hour") ||
        (isIndonesian && text.match(/\b\d+\s*j\b/)) ||
        (!isIndonesian && text.match(/\b\d+\s*h\b/))
    )
        return num * 3600;
    if (
        text.includes("menit") ||
        text.includes("minute") ||
        text.includes("min") ||
        text.match(/\b\d+\s*m\b/)
    )
        return num * 60;
    if (
        text.includes("detik") ||
        text.includes("second") ||
        text.includes("sec") ||
        text.match(/\b\d+\s*s\b/)
    )
        return num;

    return 999999999;
};

// Combine Live & Recent Replays into a single array (Replays sorted newest first)
const combinedStreams = computed(() => {
    const liveItems = (props.allActiveStreams || []).map((s) => ({
        ...s,
        is_live_item: true,
    }));
    const replayItems = [...(props.recentReplayStreams || [])]
        .sort((a, b) => {
            const timeA = parseRelativeTimeToSeconds(
                a.streamed_at || a.incident_code,
            );
            const timeB = parseRelativeTimeToSeconds(
                b.streamed_at || b.incident_code,
            );
            return timeA - timeB;
        })
        .map((s) => ({
            ...s,
            is_live_item: false,
        }));

    if (activeFilter.value === "LIVE") return liveItems;
    if (activeFilter.value === "REPLAY") return replayItems;

    // Show Live streams first, then Replay streams
    return [...liveItems, ...replayItems];
});

const liveCount = computed(() => (props.allActiveStreams || []).length);
const replayCount = computed(() => (props.recentReplayStreams || []).length);

const handleStreamCardClick = (stream) => {
    emit("play-stream-in-focus", stream);
    if (!stream) return;

    if (
        stream.is_officer_card ||
        (typeof stream.video_id === "string" &&
            stream.video_id.startsWith("officer-"))
    ) {
        const handle = stream.officer?.handle
            ? stream.officer.handle.replace(/^@/, "")
            : "";
        if (handle) {
            window.open(`https://www.youtube.com/@${handle}`, "_blank");
        }
        return;
    }

    if (stream.video_id) {
        const dept = stream.officer?.department || "ALL";
        router.visit(
            `/multiview?focus=${encodeURIComponent(stream.video_id)}&dept=${encodeURIComponent(dept)}`,
        );
    }
};

// Horizontal Scroll Helper
const scrollRow = (rowId, direction = "right") => {
    const el = document.getElementById(rowId);
    if (!el) return;
    const scrollAmount = direction === "left" ? -650 : 650;
    el.scrollBy({ left: scrollAmount, behavior: "smooth" });
};

// Standardized Department Color Badges Function
const getDeptBadgeClass = (dept) => {
    const d = (dept || "").toUpperCase();
    if (d.includes("LSPD"))
        return "bg-blue-950/90 text-blue-300 border-blue-500/40";
    if (d.includes("BCSO") || d.includes("LSCSD"))
        return "bg-amber-950/90 text-amber-300 border-amber-500/40";
    if (d.includes("SASP"))
        return "bg-cyan-950/90 text-cyan-300 border-cyan-500/40";
    if (d.includes("SAPR") || d.includes("RANGER"))
        return "bg-emerald-950/90 text-emerald-300 border-emerald-500/40";
    return "bg-slate-900 text-slate-300 border-slate-700";
};

// Relative Time Label Helper
const formatRelativeTimeLabel = (dateStr) => {
    if (!dateStr) return "REPLAY";
    return String(dateStr);
};

// YouTube Link Helper
const getStreamYoutubeUrl = (stream) => {
    if (!stream) return "#";
    if (
        stream.is_officer_card ||
        (typeof stream.video_id === "string" &&
            stream.video_id.startsWith("officer-"))
    ) {
        const handle = stream.officer?.handle
            ? stream.officer.handle.replace(/^@/, "")
            : "";
        return handle ? `https://www.youtube.com/@${handle}` : "#";
    }
    return `https://www.youtube.com/watch?v=${stream.video_id}`;
};

// TAC Department Check Helper
const getStreamTac = (videoId) => {
    if (!videoId) return null;
    const targetId = String(videoId).trim();
    for (const channel of props.tacChannels || []) {
        if (
            channel.video_ids &&
            channel.video_ids.map(String).includes(targetId)
        ) {
            return channel.name || channel.code;
        }
    }
    return null;
};
</script>

<template>
    <div class="w-full space-y-4 font-sans">
        <!-- Section Header Bar (Clean Flat Title & Filter Tabs) -->
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3.5 border-b border-slate-800"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <span
                        class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"
                    ></span>
                    <h2
                        class="text-base sm:text-lg font-bold text-slate-100 tracking-tight uppercase"
                    >
                        DISPATCH FEED: PATROLI LIVE & REKAMAN
                    </h2>
                </div>
                <p class="text-xs text-slate-400 font-normal hidden md:block">
                    Saluran pemantauan gabungan siaran langsung 10-8 (On Duty)
                    dan arsip rekaman patroli perwira San Andreas.
                </p>
            </div>

            <!-- Filter Pills & Sync Button (Flat Styling) -->
            <div class="hidden md:flex flex-wrap items-center gap-2">
                <!-- Filter Pills -->
                <div
                    class="inline-flex p-1 rounded-md bg-slate-900 border border-slate-800 text-xs font-mono"
                >
                    <button
                        @click="activeFilter = 'ALL'"
                        :class="[
                            'px-3 py-1 rounded-md transition-colors cursor-pointer font-medium',
                            activeFilter === 'ALL'
                                ? 'bg-blue-600 text-white'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        Semuanya ({{ liveCount + replayCount }})
                    </button>
                    <button
                        @click="activeFilter = 'LIVE'"
                        :class="[
                            'px-3 py-1 rounded-md transition-colors cursor-pointer flex items-center gap-1.5 font-medium',
                            activeFilter === 'LIVE'
                                ? 'bg-red-600 text-white'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        <span>Live ({{ liveCount }})</span>
                    </button>
                    <button
                        @click="activeFilter = 'REPLAY'"
                        :class="[
                            'px-3 py-1 rounded-md transition-colors cursor-pointer flex items-center gap-1.5 font-medium',
                            activeFilter === 'REPLAY'
                                ? 'bg-slate-800 text-slate-200 border border-slate-700'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        <span>Rekaman ({{ replayCount }})</span>
                    </button>
                </div>

                <!-- Sync Live Button -->
                <button
                    @click="emit('trigger-manual-sync')"
                    :disabled="isSyncingFeeds"
                    class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-200 text-xs font-mono rounded-md border border-slate-800 transition-colors flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                >
                    <img
                        :src="iconRefresh"
                        class="w-3.5 h-3.5 invert opacity-80"
                        :class="{ 'animate-spin': isSyncingFeeds }"
                    />
                    <span>{{ isSyncingFeeds ? "Syncing..." : "Sync" }}</span>
                </button>
            </div>
        </div>

        <!-- SINGLE UNIFIED HORIZONTAL SLIDER ROW -->
        <div class="space-y-3">
            <!-- Row Slider Controls & Counter -->
            <div class="flex items-center justify-between px-0.5">
                <span
                    class="text-xs font-mono text-slate-400 uppercase tracking-wider"
                >
                    Menampilkan {{ combinedStreams.length }} Tayangan Patroli
                </span>

                <div
                    v-if="combinedStreams.length > 0"
                    class="flex items-center space-x-1 shrink-0"
                >
                    <button
                        @click="scrollRow('row-unified-dispatch', 'left')"
                        class="p-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border border-slate-800 transition-colors flex items-center justify-center cursor-pointer"
                        title="Geser Kiri"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>
                    <button
                        @click="scrollRow('row-unified-dispatch', 'right')"
                        class="p-1.5 rounded-md bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border border-slate-800 transition-colors flex items-center justify-center cursor-pointer"
                        title="Geser Kanan"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="combinedStreams.length === 0"
                class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center flex flex-col items-center justify-center gap-2 text-slate-400"
            >
                <div
                    class="p-3 rounded-full bg-slate-950 border border-slate-800 text-blue-400 mb-1"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 010-7.778M12 20a9.99 9.99 0 000-16m3.889 3.596a5.5 5.5 0 010 7.778M12 12h.01"
                        />
                    </svg>
                </div>
                <p class="text-xs font-mono font-semibold text-slate-200">
                    Tidak ada siaran atau rekaman patroli yang ditemukan.
                </p>
                <p class="text-[11px] text-slate-400">
                    Coba ubah filter atau tekan tombol Sync Live Feeds.
                </p>
            </div>

            <!-- UNIFIED SINGLE ROW SLIDER -->
            <div
                v-else
                id="row-unified-dispatch"
                class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-1 focus:outline-none"
            >
                <div
                    v-for="stream in combinedStreams"
                    :key="`dispatch-${stream.video_id}`"
                    class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                    @click="handleStreamCardClick(stream)"
                >
                    <div
                        class="aspect-video bg-slate-900 rounded-xl overflow-hidden relative border border-slate-800 transition-colors flex flex-col justify-between p-3"
                        :class="[
                            stream.status === 'LIVE' || stream.is_live_item
                                ? 'group-hover:border-red-600/80'
                                : 'group-hover:border-blue-600/80',
                        ]"
                    >
                        <img
                            :src="
                                stream.thumbnail ||
                                `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`
                            "
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 pointer-events-none"
                            loading="lazy"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-slate-950/60 pointer-events-none"
                        ></div>

                        <!-- Card Top Badges -->
                        <div
                            class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto"
                        >
                            <!-- Live vs Replay Badge -->
                            <span
                                v-if="
                                    stream.status === 'LIVE' ||
                                    stream.is_live_item
                                "
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-semibold tracking-wider"
                            >
                                <span>LIVE</span>
                                <span v-if="stream.viewers_count"
                                    >({{ stream.viewers_count }})</span
                                >
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-900 text-slate-300 font-mono text-[10px] border border-slate-800 tracking-wider"
                            >
                                <span>{{
                                    formatRelativeTimeLabel(stream.streamed_at)
                                }}</span>
                            </span>

                            <!-- TAC & Department Badges -->
                            <div class="flex items-center gap-1">
                                <span
                                    v-if="getStreamTac(stream.video_id)"
                                    class="px-1.5 py-0.5 rounded-md text-[10px] font-mono font-bold bg-amber-500 text-slate-950"
                                >
                                    {{
                                        getStreamTac(stream.video_id).replace(
                                            "_",
                                            " ",
                                        )
                                    }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium border"
                                    :class="
                                        getDeptBadgeClass(
                                            stream.officer?.department,
                                        )
                                    "
                                >
                                    {{ stream.officer?.department || "UNIT" }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Bottom Officer Name & YouTube Button -->
                        <div
                            class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <img
                                    v-if="stream.officer?.avatar_url"
                                    :src="stream.officer.avatar_url"
                                    class="w-6 h-6 rounded-full object-cover border border-slate-700 shrink-0"
                                />
                                <span
                                    class="text-xs font-semibold text-slate-100 truncate"
                                >
                                    {{
                                        stream.officer?.officer_name ||
                                        "Officer"
                                    }}
                                </span>
                            </div>
                            <a
                                :href="getStreamYoutubeUrl(stream)"
                                target="_blank"
                                class="p-1.5 text-slate-400 hover:text-slate-100 bg-slate-900/90 rounded-md border border-slate-800 transition-colors shrink-0"
                                :class="[
                                    stream.status === 'LIVE' ||
                                    stream.is_live_item
                                        ? 'hover:bg-red-600 hover:border-red-600'
                                        : 'hover:bg-blue-600 hover:border-blue-600',
                                ]"
                                title="Buka di YouTube"
                                @click.stop
                            >
                                <img
                                    :src="iconExternal"
                                    class="w-3.5 h-3.5 invert opacity-80"
                                />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
