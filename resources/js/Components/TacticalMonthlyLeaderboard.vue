<script setup>
import { computed } from "vue";

const props = defineProps({
    title: {
        type: String,
        default: "Top Streamer of IME Police",
    },
    subtitle: {
        type: String,
        default:
            "Apresiasi tertinggi bagi para penegak hukum Los Santos yang paling loyal meluangkan jam streaming patroli di YouTube. Jam tayang dihitung otomatis berdasarkan akumulasi durasi siaran status 10-8 (On Duty) bulan ini.",
    },
    leaderboard: {
        type: Object,
        default: () => null,
    },
});

const defaultTopStreamers = [
    {
        rank: 1,
        officer_name: "Commander Arya 'Ghost'",
        streamer_name: "Arya_SWAT",
        callsign: "Special Enforcement Bureau / SWAT",
        badge: "EMAS",
        avatar_url:
            "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=160&h=160&fit=crop&crop=faces",
        total_hours: "184 Jam 15m",
        avg_shift: "6.1j / hari",
        percent: 100,
        is_live: true,
        live_viewers: 890,
        youtube_url: "https://youtube.com",
    },
    {
        rank: 2,
        officer_name: "Sgt. Dimas Wicaksono",
        streamer_name: "Dimas_K9",
        callsign: "K-9 Unit Supervisor",
        badge: "SILVER",
        avatar_url:
            "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=faces",
        total_hours: "158 Jam 40m",
        avg_shift: "5.2j / shift",
        percent: 86,
        is_live: true,
        youtube_url: "https://youtube.com",
    },
    {
        rank: 3,
        officer_name: "Officer Rendy Pratama",
        streamer_name: "Rendy_ASD",
        callsign: "Air Support Division (Pilot 10-9)",
        badge: "BRONZE",
        avatar_url:
            "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=150&h=150&fit=crop&crop=faces",
        total_hours: "142 Jam 10m",
        avg_shift: "4.7j / shift",
        percent: 77,
        is_live: false,
        youtube_url: "https://youtube.com",
    },
];

const defaultRanksFourToSix = [
    {
        rank: 4,
        officer_name: "Det. Maya Safira",
        role: "CID Detective • 118 Jam",
        avatar_url:
            "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop&crop=faces",
        youtube_url: "https://youtube.com",
    },
    {
        rank: 5,
        officer_name: 'Ofc. Budi "Speedy"',
        role: "Traffic Div • 105 Jam",
        avatar_url:
            "https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=80&h=80&fit=crop&crop=faces",
        youtube_url: "https://youtube.com",
    },
    {
        rank: 6,
        officer_name: "Cadet Rian Febrian",
        role: "Academy • 98 Jam",
        avatar_url:
            "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=80&h=80&fit=crop&crop=faces",
        youtube_url: "https://youtube.com",
    },
];

const getDefaultPeriodText = () => {
    const now = new Date();
    const monthNames = [
        "JANUARI",
        "FEBRUARI",
        "MARET",
        "APRIL",
        "MEI",
        "JUNI",
        "JULI",
        "AGUSTUS",
        "SEPTEMBER",
        "OKTOBER",
        "NOVEMBER",
        "DESEMBER",
    ];
    const monthYear = `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
    return `${monthYear}`;
};

const getDefaultResetCycleText = () => {
    const now = new Date();
    const totalDaysInMonth = new Date(
        now.getFullYear(),
        now.getMonth() + 1,
        0,
    ).getDate();
    const daysLeft = Math.max(1, totalDaysInMonth - now.getDate());
    return `Siklus Reset: ${daysLeft} Hari Lagi`;
};

const periodText = computed(() => {
    const raw = props.leaderboard?.period_text || getDefaultPeriodText();
    return raw.replace(/[()]/g, "").trim();
});

const resetCycleText = computed(() => {
    return props.leaderboard?.reset_cycle_text || getDefaultResetCycleText();
});

const topStreamers = computed(() => {
    if (
        props.leaderboard?.top_streamers &&
        props.leaderboard.top_streamers.length > 0
    ) {
        const result = [...props.leaderboard.top_streamers];
        while (result.length < 3) {
            result.push(defaultTopStreamers[result.length]);
        }
        return result;
    }
    return defaultTopStreamers;
});

const ranksFourToSix = computed(() => {
    if (
        props.leaderboard?.ranks_four_to_six &&
        props.leaderboard.ranks_four_to_six.length > 0
    ) {
        return props.leaderboard.ranks_four_to_six;
    }
    return defaultRanksFourToSix;
});

const openChannel = (url) => {
    if (!url) return;
    let targetUrl = url;
    if (
        targetUrl.includes("youtube.com/") &&
        !targetUrl.includes("/channel/") &&
        !targetUrl.includes("/watch?") &&
        !targetUrl.includes("/embed/") &&
        !targetUrl.includes("/live_chat?") &&
        !targetUrl.includes("/@")
    ) {
        targetUrl = targetUrl.replace("youtube.com/", "youtube.com/@");
    }
    window.open(targetUrl, "_blank");
};
</script>

<template>
    <section
        id="officerLeaderboard"
        class="w-full space-y-6 font-sans relative"
    >
        <!-- Section Header (Frameless, Centered Large Title) -->
        <div
            class="text-center py-2 mb-5 sm:mb-12 space-y-2 font-sans max-w-4xl mx-auto"
        >
            <div
                class="inline-flex items-center justify-center gap-1 sm:gap-2 px-2.5 sm:px-3 py-1.5 sm:py-1 rounded-md bg-amber-500/10 border border-amber-500/30 text-amber-400 font-mono text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-center"
            >
                <span>{{ periodText }}</span>
            </div>

            <h2
                class="text-xl sm:text-3xl lg:text-4xl font-bold text-slate-100 tracking-tight uppercase leading-tight"
            >
                {{ title }}
            </h2>
        </div>

        <!-- Podium Top 3 Dedicated Streamers Grid -->
        <div
            class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5 lg:gap-6 items-end"
            id="podiumLeaderboard"
        >
            <!-- Rank 2: Silver Officer -->
            <div
                class="order-2 md:order-1 bg-slate-900 rounded-xl p-3.5 sm:p-5 border border-slate-800 relative hover:border-slate-700 transition-colors"
            >
                <div
                    class="absolute -top-3 left-4 sm:left-5 px-2.5 py-0.5 rounded-md bg-slate-200 text-slate-950 font-mono font-bold text-[9px] sm:text-[10px] uppercase flex items-center gap-1 shrink-0"
                >
                    <svg
                        class="w-3 h-3 text-slate-950 shrink-0"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"
                        />
                    </svg>
                    <span>RANK #2 SILVER</span>
                </div>

                <div
                    class="flex items-center gap-3 sm:gap-3.5 mb-3 sm:mb-4 mt-1"
                >
                    <div class="relative shrink-0">
                        <img
                            :src="topStreamers[1].avatar_url"
                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg object-cover border border-slate-700"
                        />
                        <span
                            :class="
                                topStreamers[1].is_live
                                    ? 'bg-red-500'
                                    : 'bg-slate-600'
                            "
                            class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-slate-900"
                            :title="
                                topStreamers[1].is_live
                                    ? '10-8 Sedang Patroli Live'
                                    : '10-7 Standby / Offline'
                            "
                        ></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3
                            class="font-bold text-xs sm:text-sm text-slate-100 truncate"
                        >
                            {{ topStreamers[1].officer_name }}
                        </h3>
                        <p
                            class="text-[11px] sm:text-xs text-slate-400 font-mono truncate"
                        >
                            {{ topStreamers[1].callsign }}
                        </p>
                        <span
                            v-if="topStreamers[1].is_live"
                            class="inline-flex items-center gap-1 mt-1 text-[9px] sm:text-[10px] font-mono font-semibold text-red-400 bg-red-950/60 px-1.5 sm:px-2 py-0.5 rounded border border-red-900/80"
                        >
                            10-8 LIVE STREAMING
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 mt-1 text-[9px] sm:text-[10px] font-mono font-semibold text-slate-400 bg-slate-950 px-1.5 sm:px-2 py-0.5 rounded border border-slate-800"
                        >
                            10-7 STANDBY
                        </span>
                    </div>
                </div>

                <div
                    class="space-y-1.5 sm:space-y-2 py-2.5 sm:py-3 border-y border-slate-800"
                >
                    <div class="flex justify-between items-baseline font-mono">
                        <span class="text-[11px] sm:text-xs text-slate-400"
                            >Total Durasi Patroli:</span
                        >
                        <strong
                            class="text-xs sm:text-sm font-semibold text-slate-100 font-mono"
                        >
                            {{ topStreamers[1].total_hours }}
                        </strong>
                    </div>
                    <div
                        class="w-full bg-slate-950 h-1.5 sm:h-2 rounded-full overflow-hidden border border-slate-800 hidden sm:block"
                    >
                        <div
                            class="bg-blue-600 h-full rounded-full"
                            :style="{ width: topStreamers[1].percent + '%' }"
                        ></div>
                    </div>
                    <div
                        class="text-[10px] sm:text-[11px] text-slate-400 flex justify-between font-mono"
                    >
                        <span>Rata-rata: {{ topStreamers[1].avg_shift }}</span>
                        <span class="text-blue-400"
                            >{{ topStreamers[1].percent }}% dari #1</span
                        >
                    </div>
                </div>

                <div class="mt-3 sm:mt-4 flex gap-2">
                    <button
                        @click="openChannel(topStreamers[1].youtube_url)"
                        :class="
                            topStreamers[1].is_live
                                ? 'bg-red-600 hover:bg-red-500 text-white'
                                : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'
                        "
                        class="flex-1 py-1.5 sm:py-2 rounded-md font-medium text-[11px] sm:text-xs transition-colors flex items-center justify-center gap-1.5 sm:gap-2 cursor-pointer"
                        :title="
                            topStreamers[1].is_live
                                ? 'Tonton Siaran Live'
                                : 'Kunjungi Kanal YouTube'
                        "
                    >
                        <svg
                            class="w-3.5 h-3.5 fill-current shrink-0"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                            />
                        </svg>
                        <span>{{
                            topStreamers[1].is_live
                                ? "Tonton Siaran"
                                : "Kanal YouTube"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Rank 1: Gold Champion Officer -->
            <div
                class="order-1 md:order-2 bg-slate-900 rounded-xl p-4 sm:p-5 border border-amber-500/40 relative hover:border-amber-400 transition-colors"
            >
                <div
                    class="absolute -top-3 left-1/2 -translate-x-1/2 px-2.5 sm:px-3 py-0.5 rounded-md bg-amber-400 text-slate-950 font-mono font-bold text-[9px] sm:text-[10px] uppercase flex items-center gap-1 sm:gap-1.5 whitespace-nowrap"
                >
                    <svg
                        class="w-3.5 h-3.5 text-slate-950 shrink-0"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"
                        />
                    </svg>
                    <span>#1 CHIEF OF ON-AIR</span>
                </div>

                <div
                    class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-0 mt-1 mb-3 sm:mb-4"
                >
                    <div class="relative sm:mb-2 shrink-0">
                        <img
                            :src="topStreamers[0].avatar_url"
                            class="w-14 h-14 sm:w-16 sm:h-16 rounded-lg object-cover border-2 border-amber-400"
                        />
                        <span
                            class="absolute -bottom-1 -right-1 px-1 sm:px-1.5 py-0.2 rounded bg-amber-400 text-slate-950 text-[8px] sm:text-[9px] font-bold font-mono"
                        >
                            EMAS
                        </span>
                    </div>
                    <div class="min-w-0 flex-1 sm:flex-initial">
                        <h3
                            class="font-bold text-sm sm:text-base text-slate-100 truncate"
                        >
                            {{ topStreamers[0].officer_name }}
                        </h3>
                        <p
                            class="text-[11px] sm:text-xs text-slate-400 font-mono truncate"
                        >
                            {{ topStreamers[0].callsign }}
                        </p>
                        <span
                            v-if="topStreamers[0].is_live"
                            class="mt-1 sm:mt-2 text-[9px] sm:text-[10px] font-mono font-semibold text-red-400 bg-red-950/60 px-2 py-0.5 rounded border border-red-900/80 inline-flex items-center gap-1.5"
                        >
                            10-8 LIVE PATROLI ({{
                                topStreamers[0].live_viewers
                            }})
                        </span>
                        <span
                            v-else
                            class="mt-1 sm:mt-2 text-[9px] sm:text-[10px] font-mono font-semibold text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800 inline-flex items-center gap-1.5"
                        >
                            10-7 STANDBY
                        </span>
                    </div>
                </div>

                <div
                    class="space-y-1.5 sm:space-y-2 py-2.5 sm:py-3 border-y border-slate-800"
                >
                    <div class="flex justify-between items-baseline font-mono">
                        <span class="text-[11px] sm:text-xs text-slate-400"
                            >Rekor Jam Siaran:</span
                        >
                        <strong
                            class="text-xs sm:text-sm font-semibold text-slate-100 font-mono"
                        >
                            {{ topStreamers[0].total_hours }}
                        </strong>
                    </div>
                    <div
                        class="w-full bg-slate-950 h-1.5 sm:h-2 rounded-full overflow-hidden border border-slate-800 hidden sm:block"
                    >
                        <div
                            class="bg-blue-600 h-full rounded-full w-full"
                        ></div>
                    </div>
                    <div
                        class="text-[10px] sm:text-[11px] text-slate-400 flex justify-between font-mono"
                    >
                        <span>Rata-rata: {{ topStreamers[0].avg_shift }}</span>
                        <span class="text-blue-400 font-bold">100% Puncak</span>
                    </div>
                </div>

                <div class="mt-3 sm:mt-4 flex gap-2">
                    <button
                        @click="openChannel(topStreamers[0].youtube_url)"
                        :class="
                            topStreamers[0].is_live
                                ? 'bg-red-600 hover:bg-red-500 text-white'
                                : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'
                        "
                        class="flex-1 py-1.5 sm:py-2 rounded-md font-medium text-[11px] sm:text-xs transition-colors flex items-center justify-center gap-1.5 sm:gap-2 cursor-pointer"
                        :title="
                            topStreamers[0].is_live
                                ? 'Tonton Siaran Juara'
                                : 'Kunjungi Kanal YouTube'
                        "
                    >
                        <svg
                            class="w-3.5 h-3.5 fill-current shrink-0"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                            />
                        </svg>
                        <span>{{
                            topStreamers[0].is_live
                                ? "Tonton Siaran"
                                : "Kanal YouTube"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Rank 3: Bronze Officer -->
            <div
                class="order-3 bg-slate-900 rounded-xl p-3.5 sm:p-5 border border-slate-800 relative hover:border-slate-700 transition-colors"
            >
                <div
                    class="absolute -top-3 left-4 sm:left-5 px-2.5 py-0.5 rounded-md bg-amber-800 text-amber-100 font-mono font-bold text-[9px] sm:text-[10px] uppercase flex items-center gap-1 shrink-0"
                >
                    <svg
                        class="w-3 h-3 text-amber-100 shrink-0"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"
                        />
                    </svg>
                    <span>RANK #3 BRONZE</span>
                </div>

                <div
                    class="flex items-center gap-3 sm:gap-3.5 mb-3 sm:mb-4 mt-1"
                >
                    <div class="relative shrink-0">
                        <img
                            :src="topStreamers[2].avatar_url"
                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg object-cover border border-slate-700"
                        />
                        <span
                            :class="
                                topStreamers[2].is_live
                                    ? 'bg-red-500'
                                    : 'bg-slate-600'
                            "
                            class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-slate-900"
                            :title="
                                topStreamers[2].is_live
                                    ? '10-8 Sedang Patroli Live'
                                    : '10-7 Standby / Offline'
                            "
                        ></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3
                            class="font-bold text-xs sm:text-sm text-slate-100 truncate"
                        >
                            {{ topStreamers[2].officer_name }}
                        </h3>
                        <p
                            class="text-[11px] sm:text-xs text-slate-400 font-mono truncate"
                        >
                            {{ topStreamers[2].callsign }}
                        </p>
                        <span
                            v-if="topStreamers[2].is_live"
                            class="inline-flex items-center gap-1 mt-1 text-[9px] sm:text-[10px] font-mono font-semibold text-red-400 bg-red-950/60 px-1.5 sm:px-2 py-0.5 rounded border border-red-900/80"
                        >
                            10-8 LIVE STREAMING
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 mt-1 text-[9px] sm:text-[10px] font-mono font-semibold text-slate-400 bg-slate-950 px-1.5 sm:px-2 py-0.5 rounded border border-slate-800"
                        >
                            10-7 STANDBY
                        </span>
                    </div>
                </div>

                <div
                    class="space-y-1.5 sm:space-y-2 py-2.5 sm:py-3 border-y border-slate-800"
                >
                    <div class="flex justify-between items-baseline font-mono">
                        <span class="text-[11px] sm:text-xs text-slate-400"
                            >Total Durasi Patroli:</span
                        >
                        <strong
                            class="text-xs sm:text-sm font-semibold text-slate-100 font-mono"
                        >
                            {{ topStreamers[2].total_hours }}
                        </strong>
                    </div>
                    <div
                        class="w-full bg-slate-950 h-1.5 sm:h-2 rounded-full overflow-hidden border border-slate-800 hidden sm:block"
                    >
                        <div
                            class="bg-blue-600 h-full rounded-full"
                            :style="{ width: topStreamers[2].percent + '%' }"
                        ></div>
                    </div>
                    <div
                        class="text-[10px] sm:text-[11px] text-slate-400 flex justify-between font-mono"
                    >
                        <span>Rata-rata: {{ topStreamers[2].avg_shift }}</span>
                        <span class="text-blue-400"
                            >{{ topStreamers[2].percent }}% dari #1</span
                        >
                    </div>
                </div>

                <div class="mt-3 sm:mt-4 flex gap-2">
                    <button
                        @click="openChannel(topStreamers[2].youtube_url)"
                        :class="
                            topStreamers[2].is_live
                                ? 'bg-red-600 hover:bg-red-500 text-white'
                                : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'
                        "
                        class="flex-1 py-1.5 sm:py-2 rounded-md font-medium text-[11px] sm:text-xs transition-colors flex items-center justify-center gap-1.5 sm:gap-2 cursor-pointer"
                        :title="
                            topStreamers[2].is_live
                                ? 'Tonton Siaran Live'
                                : 'Kunjungi Kanal YouTube'
                        "
                    >
                        <svg
                            class="w-3.5 h-3.5 fill-current shrink-0"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                            />
                        </svg>
                        <span>{{
                            topStreamers[2].is_live
                                ? "Tonton Siaran"
                                : "Kanal YouTube"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Ranks 4 - 6 Compact Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 sm:gap-3 pt-1">
            <div
                v-for="item in ranksFourToSix"
                :key="item.rank"
                class="p-2.5 sm:p-3 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-between gap-2.5 sm:gap-3 hover:border-slate-700 transition-colors"
            >
                <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                    <span
                        class="text-[11px] sm:text-xs font-mono font-bold text-slate-400 w-4 sm:w-5 shrink-0"
                    >
                        #{{ item.rank }}
                    </span>
                    <img
                        :src="item.avatar_url"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-md object-cover shrink-0 border border-slate-700"
                    />
                    <div class="min-w-0">
                        <h4
                            class="text-[11px] sm:text-xs font-semibold text-slate-100 truncate"
                        >
                            {{ item.officer_name }}
                        </h4>
                        <p
                            class="text-[9px] sm:text-[10px] text-slate-400 font-mono truncate"
                        >
                            {{ item.role }}
                        </p>
                    </div>
                </div>
                <button
                    @click="openChannel(item.youtube_url)"
                    class="p-1.5 sm:p-2 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition-colors cursor-pointer shrink-0 flex items-center justify-center"
                    title="Buka Channel YouTube"
                >
                    <svg
                        class="w-3.5 h-3.5 fill-current shrink-0"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Additional Leaderboard Info & Cycle Reset -->
        <div
            class="mt-4 sm:mt-6 p-3.5 sm:p-4 rounded-lg bg-slate-900 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 text-[11px] sm:text-xs font-sans"
        >
            <div class="flex items-start gap-2.5 sm:gap-3 max-w-3xl">
                <svg
                    class="w-4 h-4 text-blue-400 shrink-0 mt-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <p class="text-slate-400 leading-relaxed font-normal">
                    {{ subtitle }}
                </p>
            </div>

            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-slate-950 border border-slate-800 text-slate-300 font-mono text-[11px] sm:text-xs shrink-0 w-full sm:w-auto justify-center"
            >
                <svg
                    class="w-3.5 h-3.5 text-cyan-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <span>{{ resetCycleText }}</span>
            </div>
        </div>
    </section>
</template>
