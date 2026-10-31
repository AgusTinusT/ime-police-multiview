<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowRight,
    CheckCircle2,
    Coins,
    ExternalLink,
    HelpCircle,
    Megaphone,
    Palette,
    ShieldCheck,
    Sparkles,
    Users,
} from "@lucide/vue";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { PageContainer } from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import BioExampleGallery from "@/Components/BioLink/BioExampleGallery.vue";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";

const props = defineProps({
    themes: {
        type: Object,
        default: () => ({}),
    },
    exampleSlug: {
        type: String,
        default: null,
    },
    isOfficer: {
        type: Boolean,
        default: false,
    },
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
});

const showOfficerClaimModal = ref(false);

const handleOfficerCta = () => {
    if (!props.isAuthenticated) {
        window.location.href = "/login?redirect=/bio-link";
        return;
    }
    if (props.isOfficer) {
        window.location.href = "/settings?tab=officer";
        return;
    }
    showOfficerClaimModal.value = true;
};

const scrollToGallery = () => {
    const el = document.getElementById("theme-gallery");
    if (el) {
        el.scrollIntoView({ behavior: "smooth" });
    }
};

const faqItems = [
    {
        id: "faq-1",
        q: "Apakah saya harus bayar?",
        a: "Tidak. Seluruh fitur bio-link resmi ini gratis untuk seluruh perwira kepolisian.",
    },
    {
        id: "faq-2",
        q: "Berapa lama proses verifikasi?",
        a: "Sesudah token YouTube dicocokkan, otomatis selesai dalam beberapa menit.",
    },
    {
        id: "faq-3",
        q: "Apa yang terjadi kalau saya pensiun?",
        a: "Halaman kamu ditandai 'Belum diklaim' dan link dinonaktifkan sementara untuk menjaga integritas data.",
    },
    {
        id: "faq-4",
        q: "Bisakah saya ganti slug /@?",
        a: "Bisa melalui persetujuan Admin SASP, maksimal sekali per 90 hari.",
    },
];

const verifyPoints = [
    "Hanya kamu yang bisa mengubah bio di /@kamu — tidak ada yang bisa mengaku.",
    "Lambang perwira resmi tampil di halaman, menandakan ini akun asli.",
    "Daftar rekan patroli otomatis tertaut ke officer lain, bukan link acak.",
];
</script>

<template>
    <Head title="Tactical Bio-Link Hub — Promosi Fitur" />

    <TacticalLayout>
        <PageContainer class="space-y-16 py-10 sm:py-16">
            <!-- 1. HERO SECTION -->
            <section class="text-center max-w-3xl mx-auto space-y-6 pt-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-primary/30 bg-primary/10 text-primary font-mono text-xs font-semibold tracking-wider uppercase">
                    <Sparkles class="size-3.5" />
                    <span>Officer Tactical Bio-Link Hub</span>
                </div>

                <h1 class="font-heading text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-foreground leading-[1.1]">
                    {{ $t("biolink_promo.hero_title") }}
                </h1>

                <p class="text-base sm:text-lg text-muted-foreground leading-relaxed max-w-2xl mx-auto">
                    {{ $t("biolink_promo.hero_subtitle") }}
                </p>

                <!-- 2. TWO MAIN CTAs -->
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Button
                        size="lg"
                        class="w-full sm:w-auto font-mono text-xs sm:text-sm font-bold tracking-wide gap-2 shadow-sm"
                        @click="handleOfficerCta"
                    >
                        <span>{{ $t("biolink_promo.cta_officer") }}</span>
                        <ArrowRight class="size-4" />
                    </Button>

                    <Button
                        variant="outline"
                        size="lg"
                        class="w-full sm:w-auto font-mono text-xs sm:text-sm font-bold tracking-wide gap-2"
                        @click="scrollToGallery"
                    >
                        <span>{{ $t("biolink_promo.cta_example") }}</span>
                    </Button>
                </div>

                <div v-if="exampleSlug" class="pt-1">
                    <a
                        :href="`/@${exampleSlug}`"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-xs font-mono text-muted-foreground hover:text-primary transition"
                    >
                        <span>Buka /@{{ exampleSlug }}</span>
                        <ExternalLink class="size-3" />
                    </a>
                </div>
            </section>

            <!-- 3. THREE COLUMNS FEATURES -->
            <section class="space-y-8">
                <div class="text-center max-w-xl mx-auto space-y-2">
                    <h2 class="font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                        {{ $t("biolink_promo.features_title") }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Semua elemen penting streaming dan patroli dirancang rapi dalam satu layar.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Feature 1: Donation -->
                    <Card class="p-6 border-border bg-card/80 backdrop-blur-xs flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="size-10 rounded-lg bg-warning/15 border border-warning/30 flex items-center justify-center text-warning">
                                <Coins class="size-5" />
                            </div>
                            <h3 class="font-heading font-extrabold text-base text-foreground">
                                {{ $t("biolink_promo.features_donation_title") }}
                            </h3>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                {{ $t("biolink_promo.features_donation_desc") }}
                            </p>
                        </div>
                        <div class="font-mono text-xs text-muted-foreground/80 pt-2 border-t border-border">
                            Saweria • Trakteer • BagiBagi • Tako • Sociabuzz
                        </div>
                    </Card>

                    <!-- Feature 2: Socials -->
                    <Card class="p-6 border-border bg-card/80 backdrop-blur-xs flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="size-10 rounded-lg bg-info/15 border border-info/30 flex items-center justify-center text-info">
                                <Megaphone class="size-5" />
                            </div>
                            <h3 class="font-heading font-extrabold text-base text-foreground">
                                {{ $t("biolink_promo.features_social_title") }}
                            </h3>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                {{ $t("biolink_promo.features_social_desc") }}
                            </p>
                        </div>
                        <div class="font-mono text-xs text-muted-foreground/80 pt-2 border-t border-border">
                            Instagram • TikTok • YouTube • Discord • X
                        </div>
                    </Card>

                    <!-- Feature 3: Patrol Partners -->
                    <Card class="p-6 border-border bg-card/80 backdrop-blur-xs flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="size-10 rounded-lg bg-success/15 border border-success/30 flex items-center justify-center text-success">
                                <Users class="size-5" />
                            </div>
                            <h3 class="font-heading font-extrabold text-base text-foreground">
                                {{ $t("biolink_promo.features_partners_title") }}
                            </h3>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                {{ $t("biolink_promo.features_partners_desc") }}
                            </p>
                        </div>
                        <div class="font-mono text-xs text-muted-foreground/80 pt-2 border-t border-border">
                            Status Siaga On-Duty • Tombol Tonton Langsung
                        </div>
                    </Card>
                </div>
            </section>

            <!-- 4. THEME GALLERY -->
            <section id="theme-gallery" class="space-y-8 scroll-mt-20">
                <div class="text-center max-w-xl mx-auto space-y-2">
                    <div class="inline-flex items-center gap-1.5 text-primary text-xs font-mono font-semibold uppercase tracking-wider">
                        <Palette class="size-3.5" />
                        <span>Kustomisasi Tampilan</span>
                    </div>
                    <h2 class="font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                        {{ $t("biolink_promo.themes_title") }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ $t("biolink_promo.themes_subtitle") }}
                    </p>
                </div>

                <BioExampleGallery :themes="themes" />
            </section>

            <!-- 5. WHY VERIFY SECTION -->
            <section class="max-w-3xl mx-auto">
                <Card class="p-6 sm:p-8 border-border bg-card/90 shadow-md">
                    <div class="flex flex-col sm:flex-row items-start gap-5">
                        <div class="size-12 rounded-xl bg-primary/15 border border-primary/30 flex items-center justify-center text-primary shrink-0">
                            <ShieldCheck class="size-6" />
                        </div>
                        <div class="space-y-4 flex-1">
                            <div>
                                <h3 class="font-heading text-xl font-extrabold text-foreground">
                                    {{ $t("biolink_promo.verify_title") }}
                                </h3>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Verifikasi memastikan perlindungan identitas perwira dan mencegah duplikasi akun palsu.
                                </p>
                            </div>

                            <ul class="space-y-3 pt-2">
                                <li
                                    v-for="(point, idx) in verifyPoints"
                                    :key="idx"
                                    class="flex items-start gap-3 text-sm text-foreground/90 leading-relaxed"
                                >
                                    <CheckCircle2 class="size-4 text-success shrink-0 mt-0.5" />
                                    <span>{{ point }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- 6. FAQ SECTION -->
            <section class="max-w-2xl mx-auto space-y-6">
                <div class="text-center space-y-2">
                    <div class="inline-flex items-center gap-1.5 text-muted-foreground text-xs font-mono uppercase tracking-wider">
                        <HelpCircle class="size-3.5" />
                        <span>FAQ</span>
                    </div>
                    <h2 class="font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                        {{ $t("biolink_promo.faq_title") }}
                    </h2>
                </div>

                <Accordion type="single" collapsible class="w-full">
                    <AccordionItem
                        v-for="item in faqItems"
                        :key="item.id"
                        :value="item.id"
                        class="border-b border-border"
                    >
                        <AccordionTrigger class="text-left font-mono text-sm font-semibold hover:no-underline py-4">
                            {{ item.q }}
                        </AccordionTrigger>
                        <AccordionContent class="text-xs sm:text-sm text-muted-foreground leading-relaxed pb-4">
                            {{ item.a }}
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </section>

            <!-- 7. FOOTER CLOSING CTA BANNER -->
            <section class="max-w-4xl mx-auto">
                <Card class="p-8 sm:p-12 text-center border-border bg-gradient-to-b from-card via-card/90 to-background shadow-lg space-y-6">
                    <div class="space-y-2 max-w-xl mx-auto">
                        <h2 class="font-heading text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-foreground">
                            {{ $t("biolink_promo.footer_cta_title") }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Klaim profil dinasmu sekarang dan hadirkan pengalaman bio link profesional bagi para penonton stream patrolimu.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                        <Button
                            size="lg"
                            class="w-full sm:w-auto font-mono text-xs sm:text-sm font-bold tracking-wide gap-2 shadow-sm"
                            @click="handleOfficerCta"
                        >
                            <span>{{ $t("biolink_promo.cta_officer") }}</span>
                            <ArrowRight class="size-4" />
                        </Button>

                        <Button
                            variant="outline"
                            size="lg"
                            class="w-full sm:w-auto font-mono text-xs sm:text-sm font-bold tracking-wide gap-2"
                            @click="scrollToGallery"
                        >
                            <span>{{ $t("biolink_promo.cta_example") }}</span>
                        </Button>
                    </div>
                </Card>
            </section>
        </PageContainer>

        <!-- Officer Claim Modal -->
        <OfficerClaimModal
            :show="showOfficerClaimModal"
            @close="showOfficerClaimModal = false"
        />
    </TacticalLayout>
</template>

