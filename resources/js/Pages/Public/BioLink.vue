<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card } from "@/Components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import {
    OfficerAvatar,
    LiveBadge,
    DeptBadge,
    TacticalFrame,
    EmptyState,
} from "@/Components/tactical";
import {
    ArrowLeft,
    ArrowRight,
    Share2,
    Check,
    ShieldCheck,
    AlertTriangle,
    Radio,
    Play,
    ExternalLink,
} from "@lucide/vue";

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

// Reactive state for polling updates (T-09)
const officerState = ref({ ...props.officer });
const linksState = ref([...props.links]);

// Polling live telemetry every 60 seconds without full reload
let pollTimer = null;
const fetchLiveStatus = async () => {
    if (props.isSuspended || props.isUnclaimed || !props.profile?.slug) return;
    try {
        const res = await axios.get(`/api/v1/bio-links/${props.profile.slug}/live-status`);
        if (res.data) {
            officerState.value.is_live = res.data.is_live;
            officerState.value.live_stream = res.data.live_stream;

            if (res.data.recommended_streams) {
                linksState.value.forEach((l) => {
                    if (l.link_type === "officer_recommendation" && res.data.recommended_streams[l.id] && l.recommended_officer) {
                        const rec = res.data.recommended_streams[l.id];
                        l.recommended_officer.is_live = rec.is_live;
                        l.recommended_officer.active_stream = rec.active_stream;
                    }
                });
            }
        }
    } catch {
        // Silently skip if network error occurs during background poll
    }
};

onMounted(() => {
    pollTimer = setInterval(fetchLiveStatus, 60000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
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

// Group links by type using reactive linksState
const donationLinks = computed(() =>
    linksState.value.filter((l) => l.link_type === "donation"),
);
const firstHighlightedDonationId = computed(() => {
    const found = donationLinks.value.find((l) => l.is_highlighted);
    return found ? found.id : null;
});
const partnerLinks = computed(() =>
    linksState.value.filter((l) => l.link_type === "officer_recommendation"),
);
const socialLinks = computed(() =>
    linksState.value.filter((l) => ["social", "discord"].includes(l.link_type)),
);
const customLinks = computed(() =>
    linksState.value.filter((l) => l.link_type === "custom"),
);

// Dynamic accent styling via CSS variable --primary
const accentColor = computed(() => props.profile?.accent_color || "hsl(217 91% 60%)");

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

// Button style class mapper using semantic tokens
const buttonStyleClass = computed(() => {
    const s = props.profile?.button_style || "glass_tactical";
    switch (s) {
        case "rounded_edge":
            return "rounded-full border-border hover:border-primary/60 bg-card/80 backdrop-blur-md";
        case "sharp_tactical":
            return "rounded-none border-l-4 border-border bg-card/90";
        case "solid_bold":
            return "rounded-xl bg-muted hover:bg-muted/80 border-b-2 border-border";
        case "outlined_glow":
            return "rounded-xl border-primary/50 bg-card/60 shadow-sm";
        case "glass_tactical":
        default:
            return "rounded-2xl border-border hover:border-primary/60 bg-card/70 backdrop-blur-xl shadow-lg";
    }
});
</script>

<template>
    <Head>
        <title>{{ meta.title || `${officer.officer_name} — Tactical Bio Hub` }}</title>
        <meta name="description" :content="meta.description || ''" />
    </Head>

    <div
        class="min-h-screen bg-background text-foreground flex flex-col items-center px-4 py-8 relative overflow-x-hidden selection:bg-primary selection:text-primary-foreground font-sans"
        :style="{ '--primary': accentColor, '--accent-col': accentColor }"
    >
        <!-- TACTICAL BACKGROUND GRID -->
        <div class="fixed inset-0 pointer-events-none opacity-25 hud-grid z-0"></div>
        <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-2xl h-96 bg-linear-to-b from-primary/15 via-primary/5 to-transparent blur-3xl pointer-events-none z-0"></div>

        <!-- MAIN CONTAINER (MOBILE FIRST 440px) -->
        <div class="w-full max-w-md relative z-10 flex flex-col items-center">
            <!-- TOP COMMAND BAR -->
            <header class="w-full flex items-center justify-between mb-6 pb-3 border-b border-border text-xs font-mono">
                <Button
                    as-child
                    variant="ghost"
                    class="min-h-10 px-3 text-muted-foreground hover:text-foreground font-mono"
                >
                    <Link href="/" class="flex items-center gap-2 group">
                        <ArrowLeft class="w-4 h-4 text-primary group-hover:-translate-x-0.5 transition-transform" />
                        <span class="tracking-wider uppercase font-bold text-xs">SASP Command</span>
                    </Link>
                </Button>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        @click="copyCurrentUrl"
                        class="min-h-10 px-3.5 rounded-lg text-xs font-mono flex items-center gap-1.5"
                        title="Bagikan Tautan"
                    >
                        <Check v-if="copied" class="w-3.5 h-3.5 text-success" />
                        <Share2 v-else class="w-3.5 h-3.5 text-primary" />
                        <span>{{ copied ? "Tersalin" : "Bagikan" }}</span>
                    </Button>
                </div>
            </header>

            <!-- OFFICER PROFILE HERO -->
            <div class="w-full flex flex-col items-center text-center mb-6">
                <!-- Avatar -->
                <div class="relative mb-3.5">
                    <OfficerAvatar
                        :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                        :name="officer.officer_name"
                        :department="officer.agency || officer.department"
                        :live="officerState.is_live"
                        size="lg"
                        class="w-24 h-24"
                    />
                    <div
                        v-if="officerState.is_live"
                        class="absolute -bottom-1 -right-1"
                    >
                        <LiveBadge />
                    </div>
                </div>

                <!-- Verified Badge & Department Seal -->
                <div class="flex flex-wrap items-center justify-center gap-1.5 mb-2">
                    <!-- VERIFIED SEAL -->
                    <Badge
                        v-if="profile.is_verified"
                        variant="info"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-mono tracking-wider uppercase"
                    >
                        <ShieldCheck class="w-3.5 h-3.5" />
                        <span>VERIFIED OFFICER</span>
                        <span class="text-muted-foreground">#{{ officer.callsign }}</span>
                    </Badge>

                    <Badge
                        v-else-if="profile.is_unclaimed"
                        variant="warning"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-mono tracking-wider uppercase"
                    >
                        <AlertTriangle class="w-3.5 h-3.5" />
                        <span>BELUM DIKLAIM</span>
                    </Badge>

                    <!-- Agency / Department Tag -->
                    <DeptBadge
                        :department="officer.agency || officer.department || 'POLICE'"
                        :label="`${officer.agency || officer.department || 'POLICE'} • ${officer.rank || 'Officer'}`"
                    />
                </div>

                <!-- Names -->
                <h1 class="text-xl sm:text-2xl font-bold text-foreground tracking-wide font-mono uppercase mb-0.5">
                    {{ officer.officer_name }}
                </h1>
                <p class="text-xs text-primary font-mono tracking-wider uppercase mb-2">
                    {{ officer.streamer_name }}
                </p>

                <!-- Tagline / Bio -->
                <p
                    v-if="profile.bio_tagline"
                    class="text-xs text-muted-foreground max-w-sm font-sans leading-relaxed px-4 py-1.5 rounded-xl bg-card/60 border border-border"
                >
                    {{ profile.bio_tagline }}
                </p>
            </div>

            <!-- LIVE PATROL STREAM CARD (IF ACTIVE & ENABLED) -->
            <Card
                v-if="officerState.is_live && profile.show_live_stream_banner && officerState.live_stream"
                class="w-full mb-6 p-4 rounded-2xl bg-card border-live/40 shadow-xl relative overflow-hidden group"
            >
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                        <LiveBadge label="SEDANG PATROLI (LIVE NOW)" />
                    </div>

                    <Badge
                        v-if="officerState.live_stream.viewer_count"
                        variant="outline"
                        class="text-xs font-mono text-muted-foreground"
                    >
                        👁️ {{ Number(officerState.live_stream.viewer_count).toLocaleString() }} penonton
                    </Badge>
                </div>

                <h4 class="text-xs font-bold text-foreground line-clamp-2 mb-3 leading-snug">
                    {{ officerState.live_stream.title }}
                </h4>

                <div class="flex items-center gap-2">
                    <Button
                        as-child
                        class="flex-1 min-h-10 rounded-xl bg-live hover:bg-live/90 text-foreground font-mono text-xs font-bold uppercase tracking-wider"
                    >
                        <a :href="`/multiview?streams=${officerState.live_stream.video_id}`">
                            <Radio class="w-4 h-4 mr-1.5" />
                            <span>Tonton di Multiview</span>
                        </a>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="min-h-10 px-3 rounded-xl font-mono text-xs font-semibold"
                    >
                        <a
                            :href="`https://www.youtube.com/watch?v=${officerState.live_stream.video_id}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Buka di YouTube"
                            title="Buka di YouTube"
                        >
                            <Play class="w-4 h-4" />
                        </a>
                    </Button>
                </div>
            </Card>

            <!-- SUSPENDED NOTICE (KILL-SWITCH TRIGGERED) -->
            <Alert
                v-if="isSuspended"
                class="w-full p-6 rounded-2xl bg-warning/15 border-2 border-warning/60 text-center mb-6 shadow-2xl block"
            >
                <div class="text-3xl mb-2">⚠️</div>
                <AlertTitle class="text-sm font-bold text-warning font-mono uppercase tracking-wider mb-2">
                    HALAMAN DINONAKTIFKAN SEMENTARA
                </AlertTitle>
                <AlertDescription class="text-xs text-foreground leading-relaxed font-sans mb-3">
                    {{ profile.suspension_reason || "Halaman bio ini sedang dalam peninjauan moderasi Komando Kepolisian SASP." }}
                </AlertDescription>
                <div class="text-xs text-warning font-mono">
                    STATUS PROTOKOL: LEVEL 2 LOCKDOWN
                </div>
            </Alert>

            <!-- UNCLAIMED ROSTER NOTICE -->
            <Card
                v-else-if="isUnclaimed"
                class="w-full p-5 rounded-2xl bg-info/10 border-info/40 text-center mb-6 shadow-xl"
            >
                <div class="text-2xl mb-1.5">📋</div>
                <h3 class="text-xs font-bold text-info font-mono uppercase tracking-wider mb-1.5">
                    PROFIL DINAS KEPOLISIAN RESMI
                </h3>
                <p class="text-xs text-muted-foreground leading-relaxed font-sans mb-3">
                    Halaman ini dikelola langsung dari Roster Komando SASP. Jika Anda adalah perwira pemilik kanal ini, masuk ke akun Anda untuk mengklaim.
                </p>
                <Button as-child class="min-h-10 font-mono font-bold text-xs uppercase tracking-wider">
                    <Link href="/login">
                        Klaim Kepemilikan Akun
                    </Link>
                </Button>
            </Card>

            <!-- NORMAL CONTENT: MODULAR LINK BLOCKS -->
            <div v-else class="w-full space-y-4">
                <!-- 1. DONATION / SUPPORT LINKS -->
                <section v-if="donationLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1">
                        <span class="text-xs font-bold font-mono tracking-widest text-warning uppercase">
                            🪙 DUKUNGAN & DONASI
                        </span>
                        <div class="flex-1 h-px bg-linear-to-r from-warning/30 to-transparent"></div>
                    </div>

                    <template v-for="link in donationLinks" :key="link.id">
                        <!-- Featured Donation Link: max 1 TacticalFrame -->
                        <TacticalFrame
                            v-if="link.id === firstHighlightedDonationId"
                            variant="panel"
                            accent="warning"
                        >
                            <Button
                                as-child
                                variant="ghost"
                                class="w-full h-auto min-h-14 p-3.5 flex items-center justify-between gap-3 rounded-none hover:bg-warning/10 group"
                            >
                                <a
                                    :href="link.redirect_url"
                                    target="_blank"
                                    rel="noopener noreferrer nofollow ugc"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-xl shrink-0 p-2 rounded-xl bg-warning/15 text-warning border border-warning/30 group-hover:scale-110 transition-transform">
                                            {{ getLinkIcon(link) }}
                                        </span>
                                        <div class="min-w-0 text-left">
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-xs font-bold text-foreground font-mono tracking-wide truncate group-hover:text-warning transition-colors">
                                                    {{ link.title }}
                                                </h3>
                                                <Badge
                                                    v-if="link.badge_label"
                                                    variant="warning"
                                                    class="text-xs font-bold font-mono uppercase shrink-0"
                                                >
                                                    {{ link.badge_label }}
                                                </Badge>
                                            </div>
                                            <p v-if="link.subtitle" class="text-xs text-muted-foreground truncate mt-0.5">
                                                {{ link.subtitle }}
                                            </p>
                                        </div>
                                    </div>

                                    <ArrowRight class="w-4 h-4 text-warning shrink-0 group-hover:translate-x-1 transition-transform" />
                                </a>
                            </Button>
                        </TacticalFrame>

                        <Button
                            v-else
                            as-child
                            variant="outline"
                            class="w-full h-auto min-h-14 p-3.5 flex items-center justify-between gap-3 transition-all duration-200 group active:scale-98"
                            :class="[
                                buttonStyleClass,
                                link.is_highlighted
                                    ? 'ring-1 ring-warning/60 bg-warning/10'
                                    : 'hover:border-warning/50',
                            ]"
                        >
                            <a
                                :href="link.redirect_url"
                                target="_blank"
                                rel="noopener noreferrer nofollow ugc"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-xl shrink-0 p-2 rounded-xl bg-warning/10 text-warning border border-warning/20 group-hover:scale-110 transition-transform">
                                        {{ getLinkIcon(link) }}
                                    </span>
                                    <div class="min-w-0 text-left">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-xs font-bold text-foreground font-mono tracking-wide truncate group-hover:text-warning transition-colors">
                                                {{ link.title }}
                                            </h3>
                                            <Badge
                                                v-if="link.badge_label"
                                                variant="warning"
                                                class="text-xs font-bold font-mono uppercase shrink-0"
                                            >
                                                {{ link.badge_label }}
                                            </Badge>
                                        </div>
                                        <p v-if="link.subtitle" class="text-xs text-muted-foreground truncate mt-0.5">
                                            {{ link.subtitle }}
                                        </p>
                                    </div>
                                </div>

                                <ArrowRight class="w-4 h-4 text-warning shrink-0 group-hover:translate-x-1 transition-transform" />
                            </a>
                        </Button>
                    </template>
                </section>

                <!-- 2. SQUAD PATROL PARTNERS (LIVE RECOMMENDATIONS) -->
                <section v-if="partnerLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1 pt-1">
                        <span class="text-xs font-bold font-mono tracking-widest text-primary uppercase">
                            🚓 REKAN PATROLI (SQUAD)
                        </span>
                        <div class="flex-1 h-px bg-linear-to-r from-primary/30 to-transparent"></div>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5">
                        <Card
                            v-for="link in partnerLinks"
                            :key="link.id"
                            v-show="link.recommended_officer"
                            class="p-3 rounded-2xl bg-card/80 border-border hover:border-primary/50 transition-all flex items-center justify-between gap-3 shadow-md backdrop-blur-md"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <OfficerAvatar
                                    :src="link.recommended_officer?.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=partner'"
                                    :name="link.recommended_officer?.officer_name || 'Officer'"
                                    :department="link.recommended_officer?.department"
                                    :live="link.recommended_officer?.is_live"
                                    size="md"
                                />

                                <div class="min-w-0 text-left">
                                    <div class="flex items-center gap-1.5">
                                        <h4 class="text-xs font-bold text-foreground font-mono truncate">
                                            {{ link.recommended_officer?.officer_name }}
                                        </h4>
                                        <LiveBadge
                                            v-if="link.recommended_officer?.is_live"
                                            class="shrink-0"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground font-mono truncate">
                                        {{ link.recommended_officer?.department }} • #{{ link.recommended_officer?.callsign }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <Button
                                    v-if="link.recommended_officer?.is_live && link.recommended_officer?.active_stream?.video_id"
                                    as-child
                                    size="sm"
                                    class="min-h-10 px-3 rounded-lg bg-live hover:bg-live/90 text-foreground font-mono text-xs font-bold uppercase"
                                >
                                    <a
                                        :href="`/multiview?streams=${link.recommended_officer.active_stream.video_id}`"
                                        title="Tonton Siaran"
                                    >
                                        Tonton
                                    </a>
                                </Button>
                                <Button
                                    v-if="link.recommended_officer?.bio_slug"
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="min-h-10 px-3 rounded-lg text-primary font-mono text-xs font-bold uppercase"
                                >
                                    <a
                                        :href="`/@${link.recommended_officer.bio_slug}`"
                                        title="Buka Bio"
                                    >
                                        Bio
                                    </a>
                                </Button>
                            </div>
                        </Card>
                    </div>
                </section>

                <!-- 3. COMMUNITY & SOCIAL LINKS -->
                <section v-if="socialLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1 pt-1">
                        <span class="text-xs font-bold font-mono tracking-widest text-info uppercase">
                            💬 KOMUNITAS & MEDIA SOSIAL
                        </span>
                        <div class="flex-1 h-px bg-linear-to-r from-info/30 to-transparent"></div>
                    </div>

                    <Button
                        v-for="link in socialLinks"
                        :key="link.id"
                        as-child
                        variant="outline"
                        class="w-full h-auto min-h-14 p-3.5 flex items-center justify-between gap-3 transition-all duration-200 group active:scale-98"
                        :class="buttonStyleClass"
                    >
                        <a
                            :href="link.redirect_url"
                            target="_blank"
                            rel="noopener noreferrer nofollow ugc"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-xl shrink-0 p-2 rounded-xl bg-info/10 text-info border border-info/20 group-hover:scale-110 transition-transform">
                                    {{ getLinkIcon(link) }}
                                </span>
                                <div class="min-w-0 text-left">
                                    <h3 class="text-xs font-bold text-foreground font-mono tracking-wide truncate group-hover:text-primary transition-colors">
                                        {{ link.title }}
                                    </h3>
                                    <p v-if="link.subtitle" class="text-xs text-muted-foreground truncate mt-0.5">
                                        {{ link.subtitle }}
                                    </p>
                                </div>
                            </div>

                            <ArrowRight class="w-4 h-4 text-muted-foreground shrink-0 group-hover:text-primary group-hover:translate-x-1 transition-all" />
                        </a>
                    </Button>
                </section>

                <!-- 4. CUSTOM LINKS -->
                <section v-if="customLinks.length > 0" class="w-full space-y-2.5">
                    <div class="flex items-center gap-2 px-1 pt-1">
                        <span class="text-xs font-bold font-mono tracking-widest text-muted-foreground uppercase">
                            🌐 TAUTAN LAINNYA
                        </span>
                        <div class="flex-1 h-px bg-linear-to-r from-border to-transparent"></div>
                    </div>

                    <Button
                        v-for="link in customLinks"
                        :key="link.id"
                        as-child
                        variant="outline"
                        class="w-full h-auto min-h-14 p-3.5 flex items-center justify-between gap-3 transition-all duration-200 group active:scale-98"
                        :class="buttonStyleClass"
                    >
                        <a
                            :href="link.redirect_url"
                            target="_blank"
                            rel="noopener noreferrer nofollow ugc"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-lg shrink-0 p-2 rounded-xl bg-muted text-foreground border border-border group-hover:scale-110 transition-transform">
                                    🔗
                                </span>
                                <div class="min-w-0 text-left">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xs font-bold text-foreground font-mono tracking-wide truncate group-hover:text-primary transition-colors">
                                            {{ link.title }}
                                        </h3>
                                        <Badge
                                            v-if="link.badge_label"
                                            variant="secondary"
                                            class="text-xs font-bold font-mono uppercase shrink-0"
                                        >
                                            {{ link.badge_label }}
                                        </Badge>
                                    </div>
                                    <p v-if="link.subtitle" class="text-xs text-muted-foreground truncate mt-0.5">
                                        {{ link.subtitle }}
                                    </p>
                                </div>
                            </div>

                            <ExternalLink class="w-4 h-4 text-muted-foreground shrink-0 group-hover:text-primary group-hover:translate-x-1 transition-all" />
                        </a>
                    </Button>
                </section>

                <!-- EMPTY STATE -->
                <EmptyState
                    v-if="links.length === 0"
                    title="Belum Ada Tautan Publik"
                    description="Perwira ini belum menambahkan tautan publik."
                />
            </div>

            <!-- WATERMARK FOOTER -->
            <footer class="w-full mt-12 pt-6 border-t border-border flex flex-col items-center text-center gap-2 text-muted-foreground font-mono text-xs">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 min-h-10 text-muted-foreground hover:text-primary transition"
                >
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    <span class="font-bold tracking-wider uppercase">POLICE DUTY MULTIVIEW</span>
                </Link>
                <p class="text-xs text-muted-foreground/80">
                    SASP Tactical Command Center • policeduty.sasp-motionliferp.com
                </p>
            </footer>
        </div>
    </div>
</template>
