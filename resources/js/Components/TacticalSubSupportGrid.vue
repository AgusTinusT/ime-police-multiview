<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    title: {
        type: String,
        default: "ROAD TO 1,000 SUBS",
    },
    subtitle: {
        type: String,
        default:
            "Dukung perwira aktif San Andreas mencapai milestone 1k Subscribers yang aktif live dalam 3 hari terakhir.",
    },
    officers: {
        type: Array,
        default: () => null,
    },
});

// Standardized Department Color Badges Function
const getDeptBadgeClass = (dept) => {
    switch (dept) {
        case "LSPD":
            return "bg-blue-950/80 text-blue-400 border-blue-500/30";
        case "BCSO":
            return "bg-amber-950/80 text-amber-400 border-amber-500/30";
        case "SASP":
            return "bg-cyan-950/80 text-cyan-400 border-cyan-500/30";
        case "SAPR":
        case "PARK RANGER":
            return "bg-emerald-950/80 text-emerald-400 border-emerald-500/30";
        default:
            return "bg-slate-900 text-slate-300 border-slate-700";
    }
};

// Default Data for Active Officers (< 1k Subs) as fallback
const defaultSupportOfficers = [
    {
        id: 1,
        officer_name: "Maya 'Falcon' Lin",
        streamer_name: "Falcon_SAPR",
        callsign: "4-PARK-08",
        department: "SAPR",
        avatar_url:
            "https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@Falcon_SAPR",
    },
    {
        id: 2,
        officer_name: "Ethan 'Ghost' Hunt",
        streamer_name: "Ghost_LSPD",
        callsign: "2-ADAM-15",
        department: "LSPD",
        avatar_url:
            "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@Ghost_LSPD",
    },
    {
        id: 3,
        officer_name: "Samantha 'Raven' Reed",
        streamer_name: "Raven_BCSO",
        callsign: "3-MARY-04",
        department: "BCSO",
        avatar_url:
            "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Raven_BCSO",
    },
    {
        id: 4,
        officer_name: "Lucas 'Storm' Miller",
        streamer_name: "Storm_Trooper",
        callsign: "1-KING-02",
        department: "SASP",
        avatar_url:
            "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Storm_Trooper",
    },
    {
        id: 5,
        officer_name: "Marcus 'Viper' Vance",
        streamer_name: "Viper_LSPD",
        callsign: "2-ADAM-22",
        department: "LSPD",
        avatar_url:
            "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Viper_LSPD",
    },
    {
        id: 6,
        officer_name: "Chloe 'Apex' Bennett",
        streamer_name: "Apex_SAPR",
        callsign: "4-PARK-12",
        department: "SAPR",
        avatar_url:
            "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@Apex_SAPR",
    },
    {
        id: 7,
        officer_name: "Noah 'Titan' Hayes",
        streamer_name: "Titan_BCSO",
        callsign: "3-LINCOLN-09",
        department: "BCSO",
        avatar_url:
            "https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Titan_BCSO",
    },
    {
        id: 8,
        officer_name: "Daniel 'Cobalt' Drake",
        streamer_name: "Cobalt_SASP",
        callsign: "1-KING-05",
        department: "SASP",
        avatar_url:
            "https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=150&q=80",
        is_live_now: false,
        youtube_url: "https://youtube.com/@Cobalt_SASP",
    },
    {
        id: 9,
        officer_name: "Bayu 'Speed' Nugraha",
        streamer_name: "BayuRP_Patrol",
        callsign: "2-BOY-08",
        department: "LSPD",
        avatar_url:
            "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80",
        is_live_now: true,
        youtube_url: "https://youtube.com/@BayuRP_Patrol",
    },
];

// Displaying Officers in Left 8/12 Panel
const displayedOfficers = computed(() => {
    if (props.officers && props.officers.length > 0) {
        return props.officers;
    }
    return defaultSupportOfficers;
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
    <section class="relative w-full font-sans">
        <!-- 2-COLUMN LAYOUT (LEFT 8/12: WIDE 3-COLUMN MATRIX, RIGHT 4/12: COMPACT SIDEBAR) -->
        <div
            class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start relative z-10"
        >
            <!-- LEFT COLUMN (8/12): EXPANDED MONITORING PANEL WITH 3-COLUMN OFFICERS MATRIX -->
            <div class="lg:col-span-8 order-2 lg:order-1">
                <div
                    class="rounded-xl bg-slate-900 border border-slate-800 p-5 space-y-4"
                >
                    <!-- HUD Card Header -->
                    <div
                        class="flex items-center justify-between pb-3 border-b border-slate-800"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="w-4 h-4 text-blue-400 shrink-0 hidden sm:block"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                            <h3
                                class="text-xs font-mono font-semibold text-slate-200 uppercase tracking-wider"
                            >
                                DAFTAR PERWIRA AKTIF (&lt; 1K SUBS)
                            </h3>
                        </div>
                        <span
                            class="hidden sm:inline-block text-[11px] font-mono text-blue-400 bg-blue-950/70 px-2 py-0.5 rounded border border-blue-800/80 font-medium"
                        >
                            LIVE 3 HARI TERAKHIR
                        </span>
                    </div>

                    <!-- 3-Column Officers Matrix -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5"
                    >
                        <div
                            v-for="officer in displayedOfficers"
                            :key="officer.id"
                            class="p-2.5 rounded-lg bg-slate-950 hover:bg-slate-900 border border-slate-800 transition-colors flex items-center justify-between gap-2.5 group"
                        >
                            <!-- Profile Photo & Officer Name -->
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <div
                                    class="relative shrink-0 cursor-pointer"
                                    @click="openChannel(officer.youtube_url)"
                                >
                                    <img
                                        :src="officer.avatar_url"
                                        :alt="officer.officer_name"
                                        class="w-9 h-9 rounded-md object-cover border border-slate-800 group-hover:border-slate-700 transition-colors"
                                    />
                                    <span
                                        v-if="officer.is_live_now"
                                        class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-600 border-2 border-slate-950 rounded-full"
                                        title="Status Live"
                                    ></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h4
                                            @click="
                                                openChannel(officer.youtube_url)
                                            "
                                            class="font-semibold text-xs text-slate-100 leading-tight truncate cursor-pointer hover:text-blue-400 transition-colors"
                                        >
                                            {{ officer.officer_name }}
                                        </h4>
                                    </div>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span
                                            :class="[
                                                'px-1.5 py-0.2 rounded text-[8px] font-mono font-bold border shrink-0',
                                                getDeptBadgeClass(
                                                    officer.department,
                                                ),
                                            ]"
                                        >
                                            {{ officer.department }}
                                        </span>
                                        <span
                                            class="text-[9px] text-blue-400 font-mono truncate"
                                        >
                                            @{{ officer.streamer_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right CTA Button (YouTube Support) -->
                            <button
                                @click="openChannel(officer.youtube_url)"
                                class="px-2.5 py-1.5 bg-red-600 hover:bg-red-500 text-white rounded-md text-[10px] font-medium font-mono transition-colors flex items-center gap-1 cursor-pointer shrink-0"
                            >
                                <svg
                                    class="w-3 h-3 fill-current shrink-0"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                                    />
                                </svg>
                                <span>Support</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom HUD Card Footer -->
                    <div
                        class="pt-3 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] font-mono"
                    >
                        <span class="text-slate-400 flex items-center gap-1.5">
                            <svg
                                class="w-3.5 h-3.5 text-slate-400 inline"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                />
                            </svg>
                            <span
                                >Menampilkan
                                {{ displayedOfficers.length }} perwira
                                aktif</span
                            >
                        </span>
                        <Link
                            href="/officers?sort=subs_asc"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-mono text-xs transition-colors group"
                        >
                            <span>Lihat Semua Perwira</span>
                            <span
                                class="group-hover:translate-x-1 transition-transform"
                                >→</span
                            >
                        </Link>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (4/12): COMPACT VERTICAL TACTICAL SIDEBAR -->
            <div class="lg:col-span-4 order-1 lg:order-2">
                <div class="space-y-4 sticky top-20">
                    <!-- Top Badge Pill -->
                    <div
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-blue-950/70 border border-blue-800/80 text-xs font-mono text-blue-300"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"
                        ></span>
                        <span>SUPPORT BOOST // ROAD TO 1K</span>
                    </div>

                    <!-- Compact Section Header -->
                    <div class="space-y-1.5">
                        <h2
                            class="text-xl font-bold text-slate-100 tracking-tight uppercase leading-tight"
                        >
                            IME POLICE OFFICER <br />
                            <span class="text-blue-500"
                                >ROAD TO 1,000 SUBS</span
                            >
                        </h2>

                        <p
                            class="text-slate-400 text-xs leading-relaxed font-sans font-normal"
                        >
                            {{ subtitle }} Pencapaian 1,000 subscriber dapat
                            membantu officer dalam hal monetisasi dan
                            mendapatkan fitur youtube yang lebih lengkap.
                        </p>
                    </div>

                    <!-- Information Feature Pills & Action Button -->
                    <div class="space-y-2.5 font-mono text-xs">
                        <div
                            class="flex items-center gap-2.5 p-2.5 rounded-lg bg-slate-950 border border-slate-800"
                        >
                            <svg
                                class="w-4 h-4 text-blue-400 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0-6v3m0 14v3m9-10h-3M6 12H3m15.364-6.364l-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0l-2.121-2.121M7.757 7.757L5.636 5.636"
                                />
                            </svg>
                            <div>
                                <strong
                                    class="text-slate-200 block font-sans text-xs"
                                    >Perwira Aktif</strong
                                >
                                <span
                                    class="text-slate-400 text-[10px] font-sans font-normal"
                                    >Aktif live streaming dalam 3 hari
                                    terakhir</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2.5 p-2.5 rounded-lg bg-slate-950 border border-slate-800"
                        >
                            <svg
                                class="w-4 h-4 text-emerald-400 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>
                            <div>
                                <strong
                                    class="text-slate-200 block font-sans text-xs"
                                    >Dukungan Komunitas Gratis</strong
                                >
                                <span
                                    class="text-slate-400 text-[10px] font-sans font-normal"
                                    >1-klik untuk buka YouTube perwira.</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
