<script setup>
import { ref, computed, defineAsyncComponent } from "vue";
import { Link } from "@inertiajs/vue3";
import PublicLinkButton from "@/Components/BioLink/PublicLinkButton.vue";
import { ensureContrast, parseHex, readableOn } from "@/lib/color";
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
import VerifiedBadge from "@/Components/BioLink/VerifiedBadge.vue";
import {
    ArrowLeft,
    Share2,
    Check,
    AlertTriangle,
    Radio,
    Play,
    Eye,
    Users,
} from "@lucide/vue";

// The visible bio-link page. Rendered by Pages/Public/BioLink.vue for visitors
// and by the officer dashboard as its live preview, so both always match.
const props = defineProps({
    officer: { type: Object, required: true },
    profile: { type: Object, required: true },
    // In display order (sort_order); recommendations are split out below.
    links: { type: Array, default: () => [] },
    // config('biolinks.theme_presets'): the theme contract, keyed by preset
    themes: { type: Object, default: () => ({}) },
    // Show this preset as designed (?theme=<key>), ignoring the officer's accent.
    themeKey: { type: String, default: null },
    isSuspended: { type: Boolean, default: false },
    isUnclaimed: { type: Boolean, default: false },
    // Rendered inside another page (dashboard preview): the page fills its box
    // instead of the viewport, and links don't navigate.
    contained: { type: Boolean, default: false },
});

// Toast / Copy notification
const copied = ref(false);
const copyProfileUrl = async () => {
    try {
        await navigator.clipboard.writeText(`${window.location.origin}/@${props.profile.slug}`);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch {
        // fallback
    }
};

// One list in the officer's own order; patrol partners are officers, not URLs,
// so they get their own card after it.
const urlLinks = computed(() => props.links.filter((l) => l.link_type !== "officer_recommendation"));
const partnerLinks = computed(() =>
    props.links.filter((l) => l.link_type === "officer_recommendation" && l.recommended_officer),
);
// The first support link gets the page's single TacticalFrame, wherever it sits.
const featuredDonationId = computed(() => urlLinks.value.find((l) => l.link_type === "donation")?.id ?? null);

// Theme contract (config/biolinks.php theme_presets).
// Each backdrop is its own chunk: a visitor only downloads the active theme's layer.
const lazy = (load) => defineAsyncComponent(load);
const BACKDROPS = {
    glass_radar: lazy(() => import("@/Components/BioLink/Themes/GlassRadarBackdrop.vue")),
    trooper_highway: lazy(() => import("@/Components/BioLink/Themes/TrooperHighwayBackdrop.vue")),
    metro_skyline: lazy(() => import("@/Components/BioLink/Themes/MetroSkylineBackdrop.vue")),
    county_star: lazy(() => import("@/Components/BioLink/Themes/CountyStarBackdrop.vue")),
    ranger_forest: lazy(() => import("@/Components/BioLink/Themes/RangerForestBackdrop.vue")),
    stealth_carbon: lazy(() => import("@/Components/BioLink/Themes/StealthCarbonBackdrop.vue")),
    srt_pursuit: lazy(() => import("@/Components/BioLink/Themes/SrtPursuitBackdrop.vue")),
    swat_nightops: lazy(() => import("@/Components/BioLink/Themes/SwatNightOpsBackdrop.vue")),
};
const isPreview = computed(() => !!(props.themeKey && props.themes?.[props.themeKey]));
const theme = computed(() => props.themes?.[isPreview.value ? props.themeKey : props.profile?.theme_preset] || {});
const backdrop = computed(() => BACKDROPS[theme.value.backdrop] || null);
const HEADINGS = {
    pursuit: "font-heading text-3xl font-extrabold uppercase italic leading-none tracking-tight",
    // Wide caps, like the lettering on a tactical vest.
    stencil: "font-heading text-3xl font-bold uppercase leading-none tracking-widest",
    default: "text-2xl font-bold tracking-tight",
};
const headingClass = computed(() => HEADINGS[theme.value.heading] || HEADINGS.default);

// "Ofc. E'ido Ertachta" already contains "E'ido Ertachta" — don't repeat it.
const normalizeName = (s) => (s || "").toLowerCase().replace(/[^a-z0-9]/g, "");
const showStreamerName = computed(() => {
    const streamer = normalizeName(props.officer?.streamer_name);
    return streamer !== "" && !normalizeName(props.officer?.officer_name).includes(streamer);
});

// Theme tokens → CSS variables. Every link component reads only these, and the
// accents are lifted until readable on the card, so no theme can make text vanish.
const DEFAULT_ACCENT = "#3b82f6";
const DEFAULT_CARD = "#0f172a";
const themeStyle = computed(() => {
    const card = parseHex(theme.value.surface) ? theme.value.surface : DEFAULT_CARD;
    // A preview shows the theme as designed; otherwise the officer's own accent wins.
    const rawAccent = (isPreview.value ? theme.value.accent : props.profile?.accent_color) || theme.value.accent || DEFAULT_ACCENT;
    const accent = ensureContrast(parseHex(rawAccent) ? rawAccent : DEFAULT_ACCENT, card);
    const rawHighlight = parseHex(theme.value.accent_2) ? theme.value.accent_2 : "#f59e0b";
    const highlight = ensureContrast(rawHighlight, card);
    const vars = {
        "--primary": accent,
        "--primary-foreground": readableOn(accent),
        "--ring": accent,
        "--highlight": highlight,
        "--highlight-foreground": readableOn(highlight),
    };
    if (card !== DEFAULT_CARD) vars["--card"] = card;
    return vars;
});

// Button style class mapper using semantic tokens (a theme may force one)
const buttonStyleClass = computed(() => {
    const s = theme.value.button_style || props.profile?.button_style || "glass_tactical";
    switch (s) {
        // Hard left edge in the accent colour, like a pursuit livery stripe.
        case "pursuit_stripe":
            return "rounded-l-none rounded-r-lg border-l-4 border-y-border border-r-border border-l-primary bg-background/70 backdrop-blur-sm hover:bg-primary/10";
        // Square, solid panels like a plate carrier; the accent shows on hover/focus.
        case "plate_carrier":
            return "rounded-sm border-border border-t-2 border-t-primary/50 bg-card hover:border-primary/60 hover:bg-primary/5";
        case "rounded_edge":
            return "rounded-full border-border hover:border-primary/60 bg-card/80 backdrop-blur-md";
        // Square panel with an accent edge on the left.
        case "sharp_tactical":
            return "rounded-none border-border border-l-4 border-l-primary bg-card/90 hover:bg-primary/10";
        // Accent-tinted block with a heavier bottom edge.
        case "solid_bold":
            return "rounded-xl border-transparent border-b-4 border-b-primary/70 bg-primary/20 hover:bg-primary/30";
        // Accent outline with a soft glow around it.
        case "outlined_glow":
            return "rounded-xl border-primary/80 bg-background/40 shadow-[0_0_14px_-3px_var(--primary)] hover:shadow-[0_0_22px_-2px_var(--primary)]";
        case "glass_tactical":
        default:
            return "rounded-2xl border-border hover:border-primary/60 bg-card/70 backdrop-blur-xl shadow-lg";
    }
});

// In the dashboard preview a click must not leave the editor.
const blockNavigation = (event) => {
    if (props.contained && event.target.closest("a")) event.preventDefault();
};
</script>

<template>
    <!-- contain:paint makes the backdrops' fixed layers fill this box, not the viewport -->
    <div
        :class="[
            'bg-background text-foreground flex flex-col items-center px-4 py-8 relative overflow-x-hidden selection:bg-primary selection:text-primary-foreground font-sans',
            contained ? 'min-h-full [contain:paint]' : 'min-h-screen',
        ]"
        :style="themeStyle"
        @click.capture="blockNavigation"
    >
        <!-- BACKGROUND: theme layer or the default tactical grid -->
        <component :is="backdrop" v-if="backdrop" :live="!!officer.is_live" />
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
                        @click="copyProfileUrl"
                        class="min-h-10 px-3.5 rounded-lg text-xs font-mono flex items-center gap-1.5"
                        title="Bagikan Tautan"
                    >
                        <Check v-if="copied" class="w-3.5 h-3.5 text-success" />
                        <Share2 v-else class="w-3.5 h-3.5 text-primary" />
                        <span>{{ copied ? "Tersalin" : "Bagikan" }}</span>
                    </Button>
                </div>
            </header>

            <!-- OFFICER PROFILE HERO: avatar → name (+verified) → one meta line → tagline -->
            <div class="w-full flex flex-col items-center text-center mb-8">
                <div class="relative mb-4">
                    <OfficerAvatar
                        :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                        :name="officer.officer_name"
                        :department="officer.agency || officer.department"
                        :live="officer.is_live"
                        size="lg"
                        class="w-24 h-24"
                    />
                    <div
                        v-if="officer.is_live"
                        class="absolute -bottom-1 -right-1"
                    >
                        <LiveBadge />
                    </div>
                </div>

                <!-- Name + verified mark -->
                <h1
                    :class="[
                        'flex items-center justify-center gap-1.5 text-foreground',
                        headingClass,
                    ]"
                >
                    <span>{{ officer.officer_name }}</span>
                    <VerifiedBadge
                        v-if="profile.is_verified"
                        class="size-5 shrink-0"
                    />
                </h1>

                <!-- Streamer name only when it isn't already part of the officer name -->
                <p v-if="showStreamerName" class="mt-1 text-sm text-muted-foreground">
                    {{ officer.streamer_name }}
                </p>

                <!-- One meta line: department · rank · callsign -->
                <div class="mt-2.5 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                    <DeptBadge :department="officer.department || 'POLICE'" />
                    <span>{{ officer.rank || "Officer" }}</span>
                    <span aria-hidden="true">·</span>
                    <span class="font-mono">{{ officer.callsign }}</span>
                </div>

                <Badge
                    v-if="profile.is_unclaimed"
                    variant="warning"
                    class="mt-2.5 gap-1"
                >
                    <AlertTriangle class="size-3.5" />
                    <span>Belum diklaim</span>
                </Badge>

                <!-- Tagline / Bio -->
                <p
                    v-if="profile.bio_tagline"
                    class="mt-3 max-w-sm text-sm leading-relaxed text-muted-foreground"
                >
                    {{ profile.bio_tagline }}
                </p>
            </div>

            <!-- LIVE PATROL STREAM CARD (IF ACTIVE & ENABLED) -->
            <Card
                v-if="officer.is_live && profile.show_live_stream_banner && officer.live_stream"
                class="w-full mb-6 p-4 rounded-2xl bg-card border-live/40 shadow-xl relative overflow-hidden group"
            >
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <LiveBadge label="Sedang patroli" />
                    <span v-if="officer.live_stream.viewer_count" class="flex items-center gap-1 text-xs text-muted-foreground">
                        <Eye class="size-3.5" /> {{ Number(officer.live_stream.viewer_count).toLocaleString("id-ID") }} menonton
                    </span>
                </div>

                <h4 class="mb-3 line-clamp-2 text-sm font-semibold leading-snug text-foreground">
                    {{ officer.live_stream.title }}
                </h4>

                <div class="flex items-center gap-2">
                    <Button
                        as-child
                        class="min-h-10 flex-1 bg-live text-live-foreground hover:bg-live/90"
                    >
                        <a :href="`/multiview?v=${officer.live_stream.video_id}`">
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
                            :href="`https://www.youtube.com/watch?v=${officer.live_stream.video_id}`"
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

            <!-- NORMAL CONTENT -->
            <div v-else class="w-full space-y-6">
                <!-- Links, exactly in the order the officer set; support links stand out by colour -->
                <section v-if="urlLinks.length > 0" class="space-y-2" aria-label="Tautan">
                    <template v-for="link in urlLinks" :key="link.id">
                        <TacticalFrame v-if="link.id === featuredDonationId" variant="panel" accent="highlight">
                            <PublicLinkButton :link="link" tone="highlight" framed />
                        </TacticalFrame>
                        <PublicLinkButton
                            v-else
                            :link="link"
                            :tone="link.link_type === 'donation' ? 'highlight' : 'default'"
                            :style-class="buttonStyleClass"
                        />
                    </template>
                </section>

                <!-- Patrol partners -->
                <section v-if="partnerLinks.length > 0" class="space-y-2">
                    <h2 class="flex items-center gap-1.5 px-1 text-xs font-semibold text-muted-foreground">
                        <Users class="size-3.5 text-primary" /> Rekan patroli
                    </h2>
                    <div
                        v-for="link in partnerLinks"
                        :key="link.id"
                        :class="['flex items-center gap-3 border p-3', buttonStyleClass]"
                    >
                        <OfficerAvatar
                            :src="link.recommended_officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=partner'"
                            :name="link.recommended_officer.officer_name || 'Officer'"
                            :live="link.recommended_officer.is_live"
                            size="md"
                            class="shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5">
                                <span class="truncate text-sm font-semibold text-foreground">{{ link.recommended_officer.officer_name }}</span>
                                <LiveBadge v-if="link.recommended_officer.is_live" class="shrink-0" />
                            </p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <DeptBadge v-if="link.recommended_officer.department" :dept="link.recommended_officer.department" />
                                <span class="truncate font-mono">{{ link.recommended_officer.callsign }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <Button
                                v-if="link.recommended_officer.is_live && link.recommended_officer.active_stream?.video_id"
                                as-child
                                size="sm"
                                class="bg-live text-live-foreground hover:bg-live/90"
                            >
                                <a :href="`/multiview?v=${link.recommended_officer.active_stream.video_id}`">Tonton</a>
                            </Button>
                            <Button v-if="link.recommended_officer.bio_slug" as-child variant="outline" size="sm">
                                <a :href="`/@${link.recommended_officer.bio_slug}`">Bio</a>
                            </Button>
                        </div>
                    </div>
                </section>

                <EmptyState
                    v-if="urlLinks.length === 0 && partnerLinks.length === 0"
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
