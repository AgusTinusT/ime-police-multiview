<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import iconAllUnits from "@/Components/Icons/category-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";
import iconExternal from "@/Components/Icons/link-external-svgrepo-com.svg";

const props = defineProps({
    allActiveStreams: { type: Array, default: () => [] },
    trendingStreams: { type: Array, default: () => [] },
    recentReplayStreams: { type: Array, default: () => [] },
    activePersonalStreams: { type: Array, default: () => [] },
    lspdCatalogStreams: { type: Array, default: () => [] },
    saspCatalogStreams: { type: Array, default: () => [] },
    bcsoCatalogStreams: { type: Array, default: () => [] },
    saprCatalogStreams: { type: Array, default: () => [] },
    specialOpsCatalogStreams: { type: Array, default: () => [] },
    isSyncingFeeds: { type: Boolean, default: false },
    tacChannels: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "play-stream-in-focus",
    "trigger-manual-sync",
    "update:activeTab",
]);

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

// Dynamic swimlane sections configuration
const sections = computed(() => [
    {
        id: "row-trending",
        title: "Patroli Populer (Trending Live)",
        isTrending: true,
        streams: props.trendingStreams || [],
    },
    {
        id: "row-replays",
        title: "Rekaman Patroli",
        isTrending: false,
        streams: props.recentReplayStreams || [],
    },
    {
        id: "row-personal",
        title: "Personal Watchlist",
        isTrending: false,
        streams: props.activePersonalStreams || [],
    },
    {
        id: "row-lspd",
        title: "Los Santos Police Department",
        isTrending: false,
        streams: props.lspdCatalogStreams || [],
    },
    {
        id: "row-bcso",
        title: "Blaine County Sheriff's Office",
        isTrending: false,
        streams: props.bcsoCatalogStreams || [],
    },
    {
        id: "row-sasp",
        title: "San Andreas State Police",
        isTrending: false,
        streams: props.saspCatalogStreams || [],
    },
    {
        id: "row-sapr",
        title: "San Andreas Park Rangers",
        isTrending: false,
        streams: props.saprCatalogStreams || [],
    },
    {
        id: "row-specops",
        title: "Special Operations & SWAT",
        isTrending: false,
        streams: props.specialOpsCatalogStreams || [],
    },
]);

// Horizontal Scroll Helper for Swimlanes
const scrollRow = (rowId, direction = "right") => {
    const el = document.getElementById(rowId);
    if (!el) return;
    const scrollAmount = direction === "left" ? -650 : 650;
    el.scrollBy({ left: scrollAmount, behavior: "smooth" });
};

// Department Badge Class Mapper
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
    <!-- MODE A: NETFLIX-STYLE POLICE CINEMA & DISCOVERY HUB -->
    <div class="space-y-8 pb-16 animate-in fade-in duration-300 w-full">
        <!-- Standby Banner (Only when all streams are off-duty / empty) -->
        <div
            v-if="allActiveStreams.length === 0"
            class="relative rounded-2xl overflow-hidden border border-slate-800/80 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-8 text-center flex flex-col items-center justify-center gap-3"
        >
            <div
                class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-1"
            >
                <img
                    :src="iconAllUnits"
                    class="w-7 h-7 invert opacity-80"
                    alt=""
                />
            </div>
            <h3 class="text-lg font-bold text-slate-100">
                SELURUH KESATUAN SEDANG 10-7 (OFF-DUTY)
            </h3>
            <p class="text-xs text-slate-400 max-w-md">
                Belum ada siaran langsung patroli atau video rekaman yang termuat.
                Anda dapat menyinkronkan feed terbaru atau melihat daftar nama
                petugas di tab 10-7 Roster.
            </p>
            <div class="flex items-center gap-2 mt-2">
                <button
                    @click="emit('trigger-manual-sync')"
                    :disabled="isSyncingFeeds"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition flex items-center gap-2"
                >
                    <img
                        :src="iconRefresh"
                        class="w-3.5 h-3.5 invert"
                        :class="{ 'animate-spin': isSyncingFeeds }"
                    />
                    <span>{{
                        isSyncingFeeds
                            ? "Menyinkronkan..."
                            : "Cek Live Sekarang"
                    }}</span>
                </button>
                <button
                    @click="emit('update:activeTab', '10-7')"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition"
                >
                    Lihat 10-7 Roster
                </button>
            </div>
        </div>

        <!-- DYNAMIC SWIMLANE SECTIONS -->
        <template v-for="section in sections" :key="section.id">
            <section v-if="section.streams.length > 0" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center space-x-2">
                        <span
                            v-if="section.isTrending"
                            class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"
                        ></span>
                        <h3
                            class="text-sm sm:text-base md:text-lg font-bold text-slate-100 tracking-wide"
                        >
                            {{ section.title }}
                        </h3>
                    </div>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <button
                            @click="scrollRow(section.id, 'left')"
                            class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group"
                            title="Geser Kiri"
                        >
                            <svg
                                class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </button>
                        <button
                            @click="scrollRow(section.id, 'right')"
                            class="p-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-md transition-all duration-200 transform active:scale-95 flex items-center justify-center group"
                            title="Geser Kanan"
                        >
                            <svg
                                class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <div
                    :id="section.id"
                    class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 px-1 focus:outline-none"
                >
                    <div
                        v-for="stream in section.streams"
                        :key="`${section.id}-${stream.video_id}`"
                        class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer"
                        @click="handleStreamCardClick(stream)"
                    >
                        <div
                            class="aspect-video bg-slate-950 rounded-xl overflow-hidden relative border border-slate-800/80 group-hover:border-slate-600 group-hover:shadow-2xl group-hover:shadow-black/60 transition-all duration-300 transform group-hover:scale-[1.02] flex flex-col justify-between p-2.5"
                        >
                            <img
                                :src="
                                    stream.thumbnail ||
                                    `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`
                                "
                                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none"
                                loading="lazy"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/60 pointer-events-none"
                            ></div>
                            <div
                                class="relative z-10 flex items-center justify-between gap-1.5 pointer-events-auto"
                            >
                                <span
                                    v-if="stream.status === 'LIVE'"
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white font-mono text-[10px] font-black tracking-wider shadow"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"
                                    ></span>
                                    <span>LIVE</span>
                                    <span v-if="stream.viewers_count"
                                        >({{ stream.viewers_count }})</span
                                    >
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900/90 text-slate-300 font-mono text-[10px] font-medium tracking-wider shadow border border-slate-700/80"
                                >
                                    <span>{{
                                        formatRelativeTimeLabel(
                                            stream.streamed_at,
                                        )
                                    }}</span>
                                </span>
                                <div class="flex items-center gap-1">
                                    <span
                                        v-if="getStreamTac(stream.video_id)"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500 text-black border border-amber-300 shadow"
                                        >{{
                                            getStreamTac(
                                                stream.video_id,
                                            ).replace("_", " ")
                                        }}</span
                                    >
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border"
                                        :class="
                                            getDeptBadgeClass(
                                                stream.officer?.department,
                                            )
                                        "
                                        >{{
                                            stream.officer?.department || "UNIT"
                                        }}</span
                                    >
                                </div>
                            </div>
                            <div
                                class="relative z-10 flex items-center justify-between gap-2 pointer-events-auto"
                            >
                                <span
                                    class="text-xs font-bold text-white truncate drop-shadow"
                                    >{{
                                        stream.officer?.officer_name ||
                                        "Officer"
                                    }}</span
                                >
                                <div
                                    class="flex items-center space-x-1 shrink-0 bg-black/50 backdrop-blur-sm p-1 rounded-lg border border-white/10"
                                    @click.stop
                                >
                                    <a
                                        :href="getStreamYoutubeUrl(stream)"
                                        target="_blank"
                                        class="p-1 text-slate-400 hover:text-white transition"
                                        title="Buka di YouTube"
                                        @click.stop
                                    >
                                        <img
                                            :src="iconExternal"
                                            class="w-3 h-3 invert opacity-80"
                                        />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 px-1">
                            <h4
                                class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors line-clamp-2 leading-snug"
                            >
                                {{ stream.title }}
                            </h4>
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>
