<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        required: true,
    },
    links: {
        type: Array,
        default: () => [],
    },
    isSuspended: {
        type: Boolean,
        default: false,
    },
    isUnclaimed: {
        type: Boolean,
        default: false,
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});

// Toast / Copy notification
const copied = ref(false);
const copyCurrentUrl = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch {
        // fallback
    }
};

// Group links by type
const donationLinks = computed(() =>
    props.links.filter((l) => l.link_type === "donation"),
);
const partnerLinks = computed(() =>
    props.links.filter((l) => l.link_type === "officer_recommendation"),
);
const socialLinks = computed(() =>
    props.links.filter((l) => ["social", "discord"].includes(l.link_type)),
);
const customLinks = computed(() =>
    props.links.filter((l) => l.link_type === "custom"),
);

// Dynamic accent styling
const accentColor = computed(() => props.profile?.accent_color || "#06b6d4");

// Icon mapper for social & donations
const getLinkIcon = (link) => {
    const t = link.link_type;
    const title = (link.title || "").toLowerCase();
    const url = (link.url || "").toLowerCase();

    if (t === "donation") {
        if (title.includes("saweria") || url.includes("saweria")) return "🪙";
        if (title.includes("trakteer") || url.includes("trakteer")) return "☕";
        if (title.includes("bagibagi") || url.includes("bagibagi")) return "🎁";
        if (title.includes("sociabuzz") || url.includes("sociabuzz")) return "⚡";
        return "💳";
    }
    if (t === "discord" || url.includes("discord")) return "💬";
    if (url.includes("youtube.com") || url.includes("youtu.be")) return "▶️";
    if (url.includes("tiktok.com")) return "🎵";
    if (url.includes("instagram.com")) return "📷";
    if (url.includes("x.com") || url.includes("twitter.com")) return "✖️";
    if (url.includes("twitch.tv")) return "🟣";
    return "🔗";
};

// Button style class mapper
const buttonStyleClass = computed(() => {
    const s = props.profile?.button_style || "glass_tactical";
    switch (s) {
        case "rounded_edge":
            return "rounded-full border border-white/10 hover:border-cyan-400/50 bg-slate-900/80 backdrop-blur-md";
        case "sharp_tactical":
            return "rounded-none border-l-4 border border-slate-700/80 bg-slate-950/90";
        case "solid_bold":
            return "rounded-xl bg-slate-800 hover:bg-slate-700 border-b-2 border-slate-600";
        case "outlined_glow":
            return "rounded-xl border border-cyan-500/50 bg-slate-950/60 shadow-[0_0_15px_rgba(6,182,212,0.15)]";
        case "glass_tactical":
        default:
            return "rounded-2xl border border-white/10 hover:border-cyan-400/50 bg-slate-900/60 backdrop-blur-xl shadow-lg";
    }
});
</script>

<template>
    <Head>
        <title>{{ meta.title || `${officer.officer_name} — Tactical Bio Hub` }}</title>
        <meta name="description" :content="meta.description || ''" />
    </Head>

    <div
        class="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center px-4 py-8 relative overflow-x-hidden selection:bg-cyan-500 selection:text-black font-sans"
        :style="{ '--accent-col': accentColor }"
    >
        <!-- TACTICAL BACKGROUND GRID & SCANLINES -->
        <div class="fixed inset-0 pointer-events-none opacity-20 bg-[radial-gradient(#0ea5e9_1px,transparent_1px)] [background-size:24px_24px] z-0"></div>
        <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-2xl h-96 bg-gradient-to-b from-cyan-600/15 via-blue-600/5 to-transparent blur-3xl pointer-events-none z-0"></div>

        <!-- MAIN CONTAINER (MOBILE FIRST 440px) -->
        <div class="w-full max-w-md relative z-10 flex flex-col items-center">
            
            <!-- TOP COMMAND BAR -->
            <header class="w-full flex items-center justify-between mb-6 pb-3 border-b border-white/10 text-xs font-mono">
                <Link
                    href="/"
                    class="flex items-center gap-2 text-slate-400 hover:text-white transition group"
                >
                    <span class="text-cyan-400 group-hover:-translate-x-0.5 transition-transform">←</span>
                    <span class="tracking-wider uppercase font-bold text-[11px]">SASP Command</span>
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        @click="copyCurrentUrl"
                        class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-cyan-400/60 text-slate-300 hover:text-white text-[11px] font-mono flex items-center gap-1.5 transition active:scale-95 shadow-sm"
                        title="Bagikan Tautan"
                    >
                        <span>{{ copied ? "✓ Tersalin" : "🔗 Bagikan" }}</span>
                    </button>
                </div>
            </header>

            <!-- OFFICER PROFILE HERO -->
            <div class="w-full flex flex-col items-center text-center mb-6">
                <!-- Avatar with Animated Tactical Border -->
                <div class="relative mb-3.5 group">
                    <div
                        class="w-24 h-24 rounded-full p-1 bg-gradient-to-tr from-cyan-500 via-blue-600 to-amber-500 shadow-xl relative"
                        :style="{ borderColor: accentColor }"
                    >
                        <img
                            :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                            :alt="officer.officer_name"
                            class="w-full h-full rounded-full object-cover bg-slate-900 border-2 border-slate-950"
                        />
                    </div>

                    <!-- Live pulsating badge on avatar -->
                    <div
                        v-if="officer.is_live"
                        class="absolute -bottom-1 -right-1 px-2 py-0.5 rounded-full bg-red-600 text-white font-mono text-[9px] font-black uppercase tracking-wider flex items-center gap-1 shadow-lg ring-2 ring-slate-950 animate-pulse"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        LIVE
                    </div>
                </div>

                <!-- Verified Badge & Department Seal -->
                <div class="flex flex-wrap items-center justify-center gap-1.5 mb-2">
                    <!-- VERIFIED SEAL -->
                    <div
                        v-if="profile.is_verified"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black font-mono tracking-wider uppercase bg-cyan-950/80 text-cyan-300 border border-cyan-500/50 shadow-sm"
                    >
                        <span>🛡️ VERIFIED OFFICER</span>
                        <span class="text-slate-400">#{{ officer.callsign }}</span>
                    </div>

                    <div
                        v-else-if="profile.is_unclaimed"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-mono tracking-wider uppercase bg-amber-950/80 text-amber-300 border border-amber-500/40"
                    >
                        <span>⚠️ BELUM DIKLAIM</span>
                    </div>

                    <!-- Agency / Department Tag -->
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-mono tracking-wider uppercase bg-slate-800 text-slate-300 border border-slate-700">
                        <span>{{ officer.department || 'POLICE' }}</span>
                        <span>•</span>
                        <span>{{ officer.rank || 'Officer' }}</span>
                    </div>
                </div>

                <!-- Names -->
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-wide font-mono uppercase mb-0.5">
                    {{ officer.officer_name }}
                </h1>
                <p class="text-xs text-cyan-300/90 font-mono tracking-wider uppercase mb-2">
                    {{ officer.streamer_name }}
                </p>

                <!-- Tagline / Bio -->
                <p
                    v-if="profile.bio_tagline"
                    class="text-xs text-slate-300 max-w-sm font-sans leading-relaxed px-4 py-1.5 rounded-xl bg-slate-900/40 border border-white/5"
                >
                    {{ profile.bio_tagline }}
                </p>
            </div>

            <!-- LIVE PATROL STREAM CARD (IF ACTIVE & ENABLED) -->
            <div
                v-if="officer.is_live && profile.show_live_stream_banner && officer.live_stream"
                class="w-full mb-6 p-4 rounded-2xl bg-gradient-to-r from-red-950/50 via-slate-900 to-slate-900 border border-red-500/40 shadow-xl relative overflow-hidden group"
            >
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-2.5 w-2.5 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                        </span>
                        <span class="text-[11px] font-black font-mono tracking-wider uppercase text-red-400">
                            SEDANG PATROLI (LIVE NOW)
                        </span>
                    </div>

                    <span
                        v-if="officer.live_stream.viewer_count"
                        class="text-[10px] font-mono text-slate-400 bg-slate-950/80 px-2 py-0.5 rounded-md border border-white/5"
                    >
                        👁️ {{ Number(officer.live_stream.viewer_count).toLocaleString() }} penonton
                    </span>
                </div>

                <h4 class="text-xs font-bold text-slate-100 line-clamp-2 mb-3 leading-snug">
                    {{ officer.live_stream.title }}
                </h4>

                <div class="flex items-center gap-2">
                    <a
                        :href="`/multiview?streams=${officer.live_stream.video_id}`"
                        class="flex-1 py-2 px-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-mono text-xs font-bold text-center uppercase tracking-wider transition shadow-md flex items-center justify-center gap-1.5 active:scale-98"
                    >
                        <span>📡 Tonton di Multiview</span>
                    </a>
                    <a
                        :href="`https://www.youtube.com/watch?v=${officer.live_stream.video_id}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-mono text-xs font-semibold text-center transition border border-white/10"
                        title="Buka di YouTube"
                    >
                        ▶️
                    </a>
                </div>
            </div>

            <!-- SUSPENDED NOTICE (KILL-SWITCH TRIGGERED) -->
            <div
                v-if="isSuspended"
                class="w-full p-6 rounded-2xl bg-amber-950/40 border-2 border-amber-500/60 text-center mb-6 shadow-2xl backdrop-blur-xl"
            >
                <div class="text-3xl mb-2">⚠️</div>
                <h3 class="text-sm font-black text-amber-300 font-mono uppercase tracking-wider mb-2">
                    HALAMAN DINONAKTIFKAN SEMENTARA
                </h3>
                <p class="text-xs text-amber-200/90 leading-relaxed font-sans mb-3">
                    {{ profile.suspension_reason || "Halaman bio ini sedang dalam peninjauan moderasi Komando Kepolisian SASP." }}
                </p>
                <div class="text-[10px] text-amber-400/70 font-mono">
                    STATUS PROTOKOL: LEVEL 2 LOCKDOWN
                </div>
            </div>

            <!-- UNCLAIMED ROSTER NOTICE -->
            <div
                v-else-if="isUnclaimed"
                class="w-full p-5 rounded-2xl bg-blue-950/40 border border-blue-500/40 text-center mb-6 shadow-xl backdrop-blur-xl"
            >
                <div class="text-2xl mb-1.5">📋</div>
                <h3 class="text-xs font-bold text-blue-200 font-mono uppercase tracking-wider mb-1.5">
                    PROFIL DINAS KEPOLISIAN RESMI
                </h3>
                <p class="text-xs text-slate-300 leading-relaxed font-sans mb-3">
                    Halaman ini dikelola langsung dari Roster Komando SASP. Jika Anda adalah perwira pemilik kanal ini, masuk ke akun Anda untuk mengklaim.
                </p>
                <Link
                    href="/login"
                    class="inline-block py-2 px-4 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-black font-mono font-black text-xs uppercase tracking-wider transition shadow-md"
                >
                    Klaim Kepemilikan Akun
                </Link>
            </div>

            <!-- NORMAL CONTENT: MODULAR LINK BLOCKS -->
            <div v-else class="w-full space-y-4">
                
                <!-- 1. DONATION / SUPPORT LINKS -->
                <section v-if="donationLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1">
                        <span class="text-[10px] font-black font-mono tracking-widest text-amber-400 uppercase">
                            🪙 DUKUNGAN & DONASI
                        </span>
                        <div class="flex-1 h-px bg-gradient-to-r from-amber-500/30 to-transparent"></div>
                    </div>

                    <a
                        v-for="link in donationLinks"
                        :key="link.id"
                        :href="link.redirect_url"
                        target="_blank"
                        rel="noopener noreferrer nofollow ugc"
                        class="w-full p-3.5 flex items-center justify-between gap-3 transition-all duration-200 group active:scale-98"
                        :class="[
                            buttonStyleClass,
                            link.is_highlighted
                                ? 'ring-1 ring-amber-400/60 bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900'
                                : 'hover:border-amber-400/50'
                        ]"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-xl shrink-0 p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 group-hover:scale-110 transition-transform">
                                {{ getLinkIcon(link) }}
                            </span>
                            <div class="min-w-0 text-left">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-black text-white font-mono tracking-wide truncate group-hover:text-amber-300 transition-colors">
                                        {{ link.title }}
                                    </h3>
                                    <span
                                        v-if="link.badge_label"
                                        class="px-1.5 py-0.2 rounded text-[9px] font-black font-mono uppercase bg-amber-500 text-black shrink-0"
                                    >
                                        {{ link.badge_label }}
                                    </span>
                                </div>
                                <p v-if="link.subtitle" class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ link.subtitle }}
                                </p>
                            </div>
                        </div>

                        <span class="text-xs text-amber-400 font-mono shrink-0 group-hover:translate-x-1 transition-transform">
                            →
                        </span>
                    </a>
                </section>

                <!-- 2. SQUAD PATROL PARTNERS (LIVE RECOMMENDATIONS) -->
                <section v-if="partnerLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1 pt-1">
                        <span class="text-[10px] font-black font-mono tracking-widest text-cyan-400 uppercase">
                            🚓 REKAN PATROLI (SQUAD)
                        </span>
                        <div class="flex-1 h-px bg-gradient-to-r from-cyan-500/30 to-transparent"></div>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5">
                        <div
                            v-for="link in partnerLinks"
                            :key="link.id"
                            v-show="link.recommended_officer"
                            class="p-3 rounded-2xl bg-slate-900/70 border border-white/10 hover:border-cyan-400/50 transition-all flex items-center justify-between gap-3 shadow-md backdrop-blur-md"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="relative shrink-0">
                                    <img
                                        :src="link.recommended_officer?.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=partner'"
                                        class="w-10 h-10 rounded-full object-cover bg-slate-950 border border-slate-700"
                                        alt=""
                                    />
                                    <span
                                        v-if="link.recommended_officer?.is_live"
                                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-red-500 ring-2 ring-slate-900 animate-ping"
                                    ></span>
                                    <span
                                        v-if="link.recommended_officer?.is_live"
                                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-red-500 ring-2 ring-slate-900"
                                    ></span>
                                </div>

                                <div class="min-w-0 text-left">
                                    <div class="flex items-center gap-1.5">
                                        <h4 class="text-xs font-bold text-slate-100 font-mono truncate">
                                            {{ link.recommended_officer?.officer_name }}
                                        </h4>
                                        <span
                                            v-if="link.recommended_officer?.is_live"
                                            class="px-1 rounded text-[8px] font-black font-mono uppercase bg-red-600 text-white shrink-0"
                                        >
                                            LIVE
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-mono truncate">
                                        {{ link.recommended_officer?.department }} • #{{ link.recommended_officer?.callsign }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <a
                                    v-if="link.recommended_officer?.is_live && link.recommended_officer?.active_stream?.video_id"
                                    :href="`/multiview?streams=${link.recommended_officer.active_stream.video_id}`"
                                    class="px-2 py-1 rounded-lg bg-red-600 hover:bg-red-500 text-white font-mono text-[10px] font-bold uppercase transition"
                                    title="Tonton Siaran"
                                >
                                    Tonton
                                </a>
                                <a
                                    v-if="link.recommended_officer?.bio_slug"
                                    :href="`/@${link.recommended_officer.bio_slug}`"
                                    class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 font-mono text-[10px] font-bold uppercase transition border border-white/5"
                                    title="Buka Bio"
                                >
                                    Bio
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. COMMUNITY & SOCIAL LINKS -->
                <section v-if="socialLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1 pt-1">
                        <span class="text-[10px] font-black font-mono tracking-widest text-blue-400 uppercase">
                            💬 KOMUNITAS & MEDIA SOSIAL
                        </span>
                        <div class="flex-1 h-px bg-gradient-to-r from-blue-500/30 to-transparent"></div>
                    </div>

                    <a
                        v-for="link in socialLinks"
                        :key="link.id"
                        :href="link.redirect_url"
                        target="_blank"
                        rel="noopener noreferrer nofollow ugc"
                        class="w-full p-3.5 flex items-center justify-between gap-3 transition-all duration-200 group active:scale-98"
                        :class="buttonStyleClass"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-xl shrink-0 p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 group-hover:scale-110 transition-transform">
                                {{ getLinkIcon(link) }}
                            </span>
                            <div class="min-w-0 text-left">
                                <h3 class="text-xs font-bold text-slate-100 font-mono tracking-wide truncate group-hover:text-cyan-300 transition-colors">
                                    {{ link.title }}
                                </h3>
                                <p v-if="link.subtitle" class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ link.subtitle }}
                                </p>
                            </div>
                        </div>

                        <span class="text-xs text-slate-500 font-mono shrink-0 group-hover:text-cyan-400 group-hover:translate-x-1 transition-all">
                            →
                        </span>
                    </a>
                </section>

                <!-- 4. CUSTOM LINKS -->
                <section v-if="customLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1 pt-1">
                        <span class="text-[10px] font-black font-mono tracking-widest text-slate-400 uppercase">
                            🌐 TAUTAN LAINNYA
                        </span>
                        <div class="flex-1 h-px bg-gradient-to-r from-slate-500/30 to-transparent"></div>
                    </div>

                    <a
                        v-for="link in customLinks"
                        :key="link.id"
                        :href="link.redirect_url"
                        target="_blank"
                        rel="noopener noreferrer nofollow ugc"
                        class="w-full p-3.5 flex items-center justify-between gap-3 transition-all duration-200 group active:scale-98"
                        :class="buttonStyleClass"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-lg shrink-0 p-2 rounded-xl bg-slate-800 text-slate-300 border border-white/10 group-hover:scale-110 transition-transform">
                                🔗
                            </span>
                            <div class="min-w-0 text-left">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold text-slate-100 font-mono tracking-wide truncate group-hover:text-cyan-300 transition-colors">
                                        {{ link.title }}
                                    </h3>
                                    <span
                                        v-if="link.badge_label"
                                        class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono uppercase bg-cyan-950 text-cyan-300 border border-cyan-500/30 shrink-0"
                                    >
                                        {{ link.badge_label }}
                                    </span>
                                </div>
                                <p v-if="link.subtitle" class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ link.subtitle }}
                                </p>
                            </div>
                        </div>

                        <span class="text-xs text-slate-500 font-mono shrink-0 group-hover:text-cyan-400 group-hover:translate-x-1 transition-all">
                            →
                        </span>
                    </a>
                </section>

                <!-- EMPTY STATE -->
                <div
                    v-if="links.length === 0"
                    class="py-12 text-center text-slate-500 font-mono text-xs border border-dashed border-slate-800 rounded-2xl"
                >
                    Perwira ini belum menambahkan tautan publik.
                </div>
            </div>

            <!-- WATERMARK FOOTER -->
            <footer class="w-full mt-12 pt-6 border-t border-white/10 flex flex-col items-center text-center gap-2 text-slate-500 font-mono text-[11px]">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-slate-400 hover:text-cyan-400 transition"
                >
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    <span class="font-bold tracking-wider uppercase">POLICE DUTY MULTIVIEW</span>
                </Link>
                <p class="text-[10px] text-slate-600">
                    SASP Tactical Command Center • policeduty.sasp-motionliferp.com
                </p>
            </footer>

        </div>
    </div>
</template>
