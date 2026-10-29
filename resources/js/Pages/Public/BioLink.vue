<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import PublicLinkButton from "@/Components/BioLink/PublicLinkButton.vue";
import SrtPursuitBackdrop from "@/Components/BioLink/Themes/SrtPursuitBackdrop.vue";
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
    Share2,
    Check,
    ShieldCheck,
    AlertTriangle,
    Radio,
    Play,
    Coins,
    Eye,
    Link2,
    MessageCircle,
    Users,
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
        type: [Array, Object],
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

const initialLinks = Array.isArray(props.links)
    ? props.links
    : Object.values(props.links || {});

// Reactive state for polling updates (T-09)
const officerState = ref({ ...props.officer });
const linksState = ref([...initialLinks]);

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

// Themes with their own visual layer. ?theme=<key> previews one on any profile
// without saving it (visual only), so a look can be tried before it's assigned.
const VISUAL_THEMES = { srt_pursuit: { accent: "#c026d3" } };
const previewTheme = typeof window !== "undefined" ? new URLSearchParams(window.location.search).get("theme") : null;
const activeTheme = computed(() => (VISUAL_THEMES[previewTheme] ? previewTheme : props.profile?.theme_preset));
const isSrt = computed(() => activeTheme.value === "srt_pursuit");

// Dynamic accent styling via CSS variable --primary
const accentColor = computed(() =>
    (VISUAL_THEMES[previewTheme] && VISUAL_THEMES[previewTheme].accent) || props.profile?.accent_color || "hsl(217 91% 60%)",
);

// Button style class mapper using semantic tokens
const buttonStyleClass = computed(() => {
    // SRT: hard left edge in the accent colour, like a pursuit livery stripe.
    if (isSrt.value) {
        return "rounded-l-none rounded-r-lg border-l-4 border-y-border border-r-border border-l-primary bg-background/70 backdrop-blur-sm hover:bg-primary/10";
    }
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
        <!-- BACKGROUND: theme layer or the default tactical grid -->
        <SrtPursuitBackdrop v-if="isSrt" :live="!!officerState.is_live" />
        <template v-else>
            <div class="fixed inset-0 pointer-events-none opacity-25 hud-grid z-0"></div>
            <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-2xl h-96 bg-linear-to-b from-primary/15 via-primary/5 to-transparent blur-3xl pointer-events-none z-0"></div>
        </template>

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
                        <span class="text-sm font-semibold">Police Duty</span>
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
                <!-- Avatar (SRT: big unit wordmark behind it, like the stream thumbnails) -->
                <div class="relative mb-3.5">
                    <span
                        v-if="isSrt"
                        class="srt-wordmark pointer-events-none absolute left-1/2 top-1/2 -z-10 -translate-x-1/2 -translate-y-1/2 select-none font-heading text-9xl font-extrabold italic leading-none text-foreground/15"
                        aria-hidden="true"
                    >SRT</span>
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
                        class="gap-1"
                    >
                        <ShieldCheck class="size-3.5" />
                        <span>Terverifikasi</span>
                        <span class="font-mono text-muted-foreground">{{ officer.callsign }}</span>
                    </Badge>

                    <Badge
                        v-else-if="profile.is_unclaimed"
                        variant="warning"
                        class="gap-1"
                    >
                        <AlertTriangle class="size-3.5" />
                        <span>Belum diklaim</span>
                    </Badge>

                    <!-- Agency / Department Tag -->
                    <DeptBadge
                        :department="officer.agency || officer.department || 'POLICE'"
                        :label="`${officer.agency || officer.department || 'POLICE'} • ${officer.rank || 'Officer'}`"
                    />
                </div>

                <!-- Names -->
                <h1
                    :class="isSrt
                        ? 'mb-0.5 font-heading text-3xl font-extrabold uppercase italic leading-none tracking-tight text-foreground'
                        : 'mb-0.5 text-2xl font-bold tracking-tight text-foreground'"
                >
                    {{ officer.officer_name }}
                </h1>
                <p :class="['mb-2 text-sm font-medium text-primary', isSrt && 'font-heading text-base italic tracking-wide']">
                    {{ officer.streamer_name }}
                </p>

                <!-- Tagline / Bio -->
                <p
                    v-if="profile.bio_tagline"
                    class="max-w-sm text-sm leading-relaxed text-muted-foreground"
                >
                    {{ profile.bio_tagline }}
                </p>
            </div>

            <!-- LIVE PATROL STREAM CARD (IF ACTIVE & ENABLED) -->
            <Card
                v-if="officerState.is_live && profile.show_live_stream_banner && officerState.live_stream"
                class="w-full mb-6 p-4 rounded-2xl bg-card border-live/40 shadow-xl relative overflow-hidden group"
            >
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <LiveBadge label="Sedang patroli" />
                    <span v-if="officerState.live_stream.viewer_count" class="flex items-center gap-1 text-xs text-muted-foreground">
                        <Eye class="size-3.5" /> {{ Number(officerState.live_stream.viewer_count).toLocaleString("id-ID") }} menonton
                    </span>
                </div>

                <h4 class="mb-3 line-clamp-2 text-sm font-semibold leading-snug text-foreground">
                    {{ officerState.live_stream.title }}
                </h4>

                <div class="flex items-center gap-2">
                    <Button
                        as-child
                        class="min-h-10 flex-1 bg-live text-live-foreground hover:bg-live/90"
                    >
                        <a :href="`/multiview?v=${officerState.live_stream.video_id}`">
                            <Radio class="size-4" />
                            <span>Tonton di Multiview</span>
                        </a>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="min-h-10 px-3"
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
            <div v-else class="w-full space-y-6">
                <!-- 1. Support / donation -->
                <section v-if="donationLinks.length > 0" class="space-y-2">
                    <h2 class="flex items-center gap-1.5 px-1 text-xs font-semibold text-muted-foreground">
                        <Coins class="size-3.5 text-warning" /> Dukungan & donasi
                    </h2>
                    <template v-for="link in donationLinks" :key="link.id">
                        <!-- Featured donation: the page's single TacticalFrame -->
                        <TacticalFrame v-if="link.id === firstHighlightedDonationId" variant="panel" accent="warning">
                            <PublicLinkButton :link="link" tone="warning" framed />
                        </TacticalFrame>
                        <PublicLinkButton v-else :link="link" tone="warning" :style-class="buttonStyleClass" />
                    </template>
                </section>

                <!-- 2. Patrol partners -->
                <section v-if="partnerLinks.length > 0" class="space-y-2">
                    <h2 class="flex items-center gap-1.5 px-1 text-xs font-semibold text-muted-foreground">
                        <Users class="size-3.5 text-primary" /> Rekan patroli
                    </h2>
                    <div
                        v-for="link in partnerLinks"
                        v-show="link.recommended_officer"
                        :key="link.id"
                        :class="['flex items-center gap-3 border p-3', buttonStyleClass]"
                    >
                        <OfficerAvatar
                            :src="link.recommended_officer?.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=partner'"
                            :name="link.recommended_officer?.officer_name || 'Officer'"
                            :live="link.recommended_officer?.is_live"
                            size="md"
                            class="shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5">
                                <span class="truncate text-sm font-semibold text-foreground">{{ link.recommended_officer?.officer_name }}</span>
                                <LiveBadge v-if="link.recommended_officer?.is_live" class="shrink-0" />
                            </p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <DeptBadge v-if="link.recommended_officer?.department" :dept="link.recommended_officer.department" />
                                <span class="truncate font-mono">{{ link.recommended_officer?.callsign }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <Button
                                v-if="link.recommended_officer?.is_live && link.recommended_officer?.active_stream?.video_id"
                                as-child
                                size="sm"
                                class="bg-live text-live-foreground hover:bg-live/90"
                            >
                                <a :href="`/multiview?v=${link.recommended_officer.active_stream.video_id}`">Tonton</a>
                            </Button>
                            <Button v-if="link.recommended_officer?.bio_slug" as-child variant="outline" size="sm">
                                <a :href="`/@${link.recommended_officer.bio_slug}`">Bio</a>
                            </Button>
                        </div>
                    </div>
                </section>

                <!-- 3. Community & social -->
                <section v-if="socialLinks.length > 0" class="space-y-2">
                    <h2 class="flex items-center gap-1.5 px-1 text-xs font-semibold text-muted-foreground">
                        <MessageCircle class="size-3.5 text-info" /> Komunitas & media sosial
                    </h2>
                    <PublicLinkButton v-for="link in socialLinks" :key="link.id" :link="link" tone="info" :style-class="buttonStyleClass" />
                </section>

                <!-- 4. Other links -->
                <section v-if="customLinks.length > 0" class="space-y-2">
                    <h2 class="flex items-center gap-1.5 px-1 text-xs font-semibold text-muted-foreground">
                        <Link2 class="size-3.5" /> Tautan lainnya
                    </h2>
                    <PublicLinkButton v-for="link in customLinks" :key="link.id" :link="link" :style-class="buttonStyleClass" />
                </section>

                <EmptyState
                    v-if="linksState.length === 0"
                    title="Belum ada tautan"
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
                    <span class="font-semibold">Police Duty Multiview</span>
                </Link>
                <p class="text-xs text-muted-foreground/80">
                    SASP Tactical Command Center • policeduty.sasp-motionliferp.com
                </p>
            </footer>
        </div>
    </div>
</template>
