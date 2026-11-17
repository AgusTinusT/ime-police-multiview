<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import {
    ExternalLink,
    Zap,
    ShieldCheck,
    Mic,
    Video,
    LayoutGrid,
    Code2,
    Heart,
    MessageSquare,
    Info,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card, CardContent } from "@/Components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import {
    PageContainer,
    PageHeader,
    SectionHeader,
    TacticalFrame,
    DeptBadge,
} from "@/Components/tactical";

// Icons & Branding
import iconBcsoColor from "@/Components/Icons/LSCSD_256.jpg";
import iconLspdColor from "@/Components/Icons/LSPD_256.jpg";
import iconSaprColor from "@/Components/Icons/SAPR_256.png";
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";

useI18n();

defineProps({
    deptStats: {
        type: Object,
        default: () => ({}),
    },
    appVersion: {
        type: String,
        default: "2.4.0",
    },
});

const departments = [
    {
        num: "01. DEPT",
        code: "LSPD",
        title: "LOS SANTOS POLICE DEPT",
        desc: "Markas Mission Row, membawahi patroli & penegakan hukum perkotaan Los Santos.",
        role: "City Patrol",
        logo: iconLspdColor,
    },
    {
        num: "02. DEPT",
        code: "BCSO",
        title: "BLAINE COUNTY SHERIFF",
        desc: "Markas Paleto Bay & Sandy Shores, menjaga wilayah pedesaan & gurun.",
        role: "County Patrol",
        logo: iconBcsoColor,
    },
    {
        num: "03. DEPT",
        code: "SASP",
        title: "SAN ANDREAS STATE POLICE",
        desc: "Divisi negara bagian, pengawasan jalan tol & operasi taktis antar-wilayah.",
        role: "State Troopers",
        logo: logoSaspColor,
    },
    {
        num: "04. DEPT",
        code: "SAPR",
        title: "SAN ANDREAS PARK RANGER",
        desc: "Pengawasan cagar alam, cagar hutan Mt. Chiliad, & perairan San Andreas.",
        role: "Park Rangers",
        logo: iconSaprColor,
    },
];

const techStack = [
    "Laravel 13 / PHP 8.3",
    "Vue 3 Composition API",
    "Inertia.js v2",
    "Tailwind v4 + shadcn-vue",
    "Vite Bundler",
];
</script>

<template>
    <TacticalLayout activeTab="about">
        <Head title="Tentang Platform — IME Police Multiview" />

        <PageContainer class="py-8 sm:py-12 space-y-14">
            <!-- 1. PAGE HEADER & SPLIT HERO: STATEMENT VS QUICK FACTS (1 TacticalFrame) -->
            <section v-reveal class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                <!-- Left Statement Column -->
                <div class="lg:col-span-7 space-y-5">
                    <PageHeader
                        :eyebrow="$t('about.badge_tag')"
                        :title="$t('about.hero_statement')"
                        :subtitle="$t('about.hero_description')"
                    />

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <Button
                            as="a"
                            href="https://imeroleplay.com"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <span>Website Resmi imeroleplay.com</span>
                            <ExternalLink class="size-3.5 ml-1.5" />
                        </Button>
                        <Button
                            as="a"
                            href="https://discord.gg/imeroleplay"
                            target="_blank"
                            rel="noopener noreferrer"
                            variant="outline"
                        >
                            <span>Discord IME RP</span>
                        </Button>
                    </div>
                </div>

                <!-- Right Quick Glance Board (1 TacticalFrame variant="panel") -->
                <TacticalFrame
                    variant="panel"
                    accent="primary"
                    class="lg:col-span-5"
                    inner-class="p-5 sm:p-6 space-y-5"
                >
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-2">
                            <span class="size-2 rounded-full bg-primary"></span>
                            RINGKASAN SISTEM MULTIVIEW
                        </h3>
                        <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10">
                            v{{ appVersion }}
                        </Badge>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-md bg-muted text-primary border border-border shrink-0 mt-0.5">
                                <Zap class="size-4" />
                            </div>
                            <div class="space-y-0.5 max-w-prose">
                                <div class="text-xs font-display font-bold uppercase text-foreground tracking-wider">
                                    KECEPATAN TANPA BLOATWARE
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    Dirancang ringan tanpa pelacak komersial yang membebani memori browser Anda saat memutar banyak stream.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-md bg-muted text-primary border border-border shrink-0 mt-0.5">
                                <ShieldCheck class="size-4" />
                            </div>
                            <div class="space-y-0.5 max-w-prose">
                                <div class="text-xs font-display font-bold uppercase text-foreground tracking-wider">
                                    SINGLE AUDIO POLICY &amp; FOCUS MODE
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    Menghindari tumpang-tindih suara antar unit dengan pengelolaan audio otomatis 1-stream aktif.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="p-2 rounded-md bg-muted text-primary border border-border shrink-0 mt-0.5">
                                <Mic class="size-4" />
                            </div>
                            <div class="space-y-0.5 max-w-prose">
                                <div class="text-xs font-display font-bold uppercase text-foreground tracking-wider">
                                    RADIO TAC &amp; AUTO DETECT #IMEPOLICE
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    Sistem mendeteksi siaran live hashtag <code class="text-primary font-mono font-bold">#imepolice</code> otomatis dan mengelompokkannya ke TAC 1 - 10.
                                </p>
                            </div>
                        </div>
                    </div>
                </TacticalFrame>
            </section>

            <!-- 2. KEPOLISIAN TERDAFTAR (HORIZONTAL TILES) -->
            <section v-reveal class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-border pb-5">
                    <SectionHeader
                        eyebrow="KESATUAN OPERASIONAL"
                        title="4 KESATUAN KEPOLISIAN TERDAFTAR"
                    />
                    <p class="text-xs text-muted-foreground max-w-prose leading-relaxed">
                        Seluruh unit patroli dari berbagai divisi kepolisian IME Roleplay terintegrasi dalam satu layar komando.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card
                        v-for="dept in departments"
                        :key="dept.code"
                        class="group hover:border-primary/40 hover:-translate-y-1 hover:shadow-md transition-all duration-300"
                    >
                        <CardContent class="p-5 flex flex-col justify-between h-full space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <Badge variant="secondary" class="font-mono uppercase">
                                        {{ dept.num }}
                                    </Badge>
                                    <DeptBadge :dept="dept.code" />
                                </div>
                                <div class="size-14 rounded-md bg-muted border border-border p-2 shrink-0 flex items-center justify-center">
                                    <img
                                        :src="dept.logo"
                                        class="w-full h-full object-contain rounded-sm"
                                        :alt="dept.code"
                                    />
                                </div>
                                <div class="max-w-prose">
                                    <h3 class="font-display font-bold text-base uppercase text-foreground group-hover:text-primary transition">
                                        {{ dept.title }}
                                    </h3>
                                    <p class="text-xs text-muted-foreground mt-1 leading-relaxed">
                                        {{ dept.desc }}
                                    </p>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-border flex items-center justify-between text-xs font-mono text-muted-foreground">
                                <span>KODE: {{ dept.code }}</span>
                                <span class="text-foreground font-semibold">{{ dept.role }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- 3. WORKFLOW STORY (STEPPER TIMELINE) -->
            <section v-reveal class="space-y-8">
                <SectionHeader
                    eyebrow="WORKFLOW STORY"
                    title="ALUR PEMANTAUAN TAKTIS MULTIVIEW"
                />

                <div class="space-y-6 relative before:absolute before:inset-0 before:left-6 sm:before:left-7 before:w-0.5 before:bg-border">
                    <!-- Step 1 -->
                    <div class="relative flex items-start gap-5 sm:gap-7 group">
                        <div class="size-12 sm:size-14 rounded-xl bg-card border border-border flex items-center justify-center text-primary shrink-0 z-10 group-hover:border-primary/40 transition">
                            <Video class="size-5 sm:size-6" />
                        </div>
                        <Card class="flex-1 group-hover:border-primary/40 transition">
                            <CardContent class="p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <h3 class="font-display font-bold text-base sm:text-lg uppercase text-foreground tracking-wider">
                                        01. AUTO DETECT HASHTAG #IMEPOLICE
                                    </h3>
                                    <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10 w-fit">
                                        MODUL INTI
                                    </Badge>
                                </div>
                                <p class="text-xs sm:text-sm text-muted-foreground mt-2 leading-relaxed max-w-prose">
                                    Setiap perwira yang sedang siaran live di YouTube dengan hashtag
                                    <code class="bg-muted px-1.5 py-0.5 rounded-sm text-primary border border-border font-mono">#imepolice</code>
                                    akan otomatis terdeteksi dan masuk ke layar pemantauan tanpa perlu input manual.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative flex items-start gap-5 sm:gap-7 group">
                        <div class="size-12 sm:size-14 rounded-xl bg-card border border-border flex items-center justify-center text-primary shrink-0 z-10 group-hover:border-primary/40 transition">
                            <LayoutGrid class="size-5 sm:size-6" />
                        </div>
                        <Card class="flex-1 group-hover:border-primary/40 transition">
                            <CardContent class="p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <h3 class="font-display font-bold text-base sm:text-lg uppercase text-foreground tracking-wider">
                                        02. MULTI-GRID CCTV &amp; SINGLE AUDIO FOCUS
                                    </h3>
                                    <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10 w-fit">
                                        UI PRESISI
                                    </Badge>
                                </div>
                                <p class="text-xs sm:text-sm text-muted-foreground mt-2 leading-relaxed max-w-prose">
                                    Komando dan penonton dapat beralih grid layout (2x2, 3x3, 4x4) atau menggunakan Focus Mode layar utama dengan pengelolaan 1 audio aktif agar tidak terjadi tabrakan suara antar unit.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative flex items-start gap-5 sm:gap-7 group">
                        <div class="size-12 sm:size-14 rounded-xl bg-card border border-border flex items-center justify-center text-primary shrink-0 z-10 group-hover:border-primary/40 transition">
                            <Mic class="size-5 sm:size-6" />
                        </div>
                        <Card class="flex-1 group-hover:border-primary/40 transition">
                            <CardContent class="p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <h3 class="font-display font-bold text-base sm:text-lg uppercase text-foreground tracking-wider">
                                        03. SQUAD KOMUNITAS &amp; PRESET MULTIVIEW
                                    </h3>
                                    <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10 w-fit">
                                        CUSTOM SQUADS
                                    </Badge>
                                </div>
                                <p class="text-xs sm:text-sm text-muted-foreground mt-2 leading-relaxed max-w-prose">
                                    Kelompokkan perwira favorit ke dalam Squad kustom atau jelajahi preset squad komunitas untuk langsung memantau beberapa POV perwira dalam satu klik.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <!-- 4. DEVELOPER & TECH STACK (SPLIT DEVELOPER SHOWCASE) -->
            <Card v-reveal>
                <CardContent class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <!-- Left Developer Profile Column -->
                        <div class="lg:col-span-6 space-y-4">
                            <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10 gap-1.5">
                                <Code2 class="size-3.5" />
                                SYSTEM ARCHITECT &amp; DEVELOPER
                            </Badge>

                            <div class="space-y-1">
                                <h2 class="text-2xl sm:text-3xl font-display font-bold uppercase text-foreground tracking-wide">
                                    GUSTI AIDAN
                                    <span class="text-primary font-mono text-sm font-semibold">(@AgusTinusT)</span>
                                </h2>
                                <p class="text-xs font-mono text-muted-foreground">
                                    Fullstack Developer • IME Roleplay Police Enthusiast
                                </p>
                            </div>

                            <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed max-w-prose">
                                Platform ini murni proyek nirlaba komunitas buat mendampingi teman-teman perwira &amp; penonton di IME Roleplay. Setiap milidetik waktu respons dan setiap piksel tata letak diperhitungkan agar pengguna dapat memantau patroli dengan efisien tanpa gangguan visual.
                            </p>

                            <!-- Action Links -->
                            <div class="pt-2 flex flex-wrap items-center gap-3">
                                <Button
                                    as="a"
                                    href="https://bagibagi.co/Gusti-Aidan"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Dukung developer via BagiBagi"
                                    class="font-mono font-bold text-xs"
                                >
                                    <Heart class="size-3.5 mr-1.5" />
                                    <span>TRAKTIR KOPI (BAGIBAGI)</span>
                                </Button>

                                <Button
                                    as="a"
                                    href="https://discord.com/users/572766938275643402"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    variant="outline"
                                    class="font-mono font-bold text-xs"
                                >
                                    <span>DISCORD DEV</span>
                                </Button>

                                <Button
                                    as-child
                                    variant="outline"
                                    class="font-mono font-semibold text-xs uppercase"
                                >
                                    <Link href="/feedback">
                                        <MessageSquare class="size-3.5 mr-1.5" />
                                        <span>KRITIK &amp; MASUKAN</span>
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <!-- Right Tech Philosophy & Platform Info -->
                        <div class="lg:col-span-6 bg-muted/40 border border-border rounded-xl p-6 space-y-4 font-mono text-xs text-muted-foreground">
                            <div class="text-xs font-bold uppercase tracking-wider text-primary border-b border-border pb-2">
                                TEKNOLOGI &amp; FONDASI PLATFORM
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="tech in techStack"
                                    :key="tech"
                                    variant="secondary"
                                    class="font-mono text-xs"
                                >
                                    {{ tech }}
                                </Badge>
                            </div>

                            <div class="space-y-1.5 pt-2 border-t border-border">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-muted-foreground">Domain Resmi:</span>
                                    <span class="text-foreground font-bold">policeduty.sasp-motionliferp.com</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-muted-foreground">Komunitas Server:</span>
                                    <span class="text-primary font-bold">IME Roleplay FiveM Indonesia</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-muted-foreground">Status Lisensi:</span>
                                    <span class="text-foreground">Community Fan-Made (Fair Use)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Important Disclaimer regarding YouTube monetization ads -->
            <Alert class="border-warning/30 bg-warning/10 text-warning">
                <Info class="size-4 text-warning" />
                <AlertTitle class="font-display font-bold uppercase text-sm tracking-wider">
                    Catatan Mengenai Iklan YouTube:
                </AlertTitle>
                <AlertDescription class="text-xs text-muted-foreground leading-relaxed max-w-prose">
                    Iklan yang mungkin muncul saat memutar video di platform ini diputar langsung oleh
                    <strong class="text-foreground">YouTube / platform siaran resmi</strong> (sesuai aturan monetisasi channel perwira). Situs IME Police Multiview ini
                    <strong class="text-foreground">100% bebas dari iklan komersial pihak ketiga</strong>.
                </AlertDescription>
            </Alert>
        </PageContainer>
    </TacticalLayout>
</template>
