<script setup>
import { Head, Link } from "@inertiajs/vue3";
import {
    BadgeCheck,
    Bug,
    Calendar,
    ExternalLink,
    GitPullRequest,
    HandHeart,
    Heart,
    MessageCircle,
    Server,
    Share2,
    Sparkles,
    Target,
    Trophy,
} from "@lucide/vue";
import { computed } from "vue";

import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { PageContainer, PageHeader } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";

const props = defineProps({
    donateUrl: { type: String, default: null },
    githubUrl: { type: String, default: null },
    discordUrl: { type: String, default: null },
    updatedAt: { type: String, default: null },
    leaderboardSynced: { type: Boolean, default: false },
    monthlyCosts: { type: Array, default: () => [] },
    monthlyTotal: { type: Number, default: null },
    costsNote: { type: String, default: null },
    goals: { type: Array, default: () => [] },
    perks: { type: Array, default: () => [] },
    supporters: { type: Array, default: () => [] },
});

const hasSupporterAmounts = computed(() =>
    props.supporters.some((s) => typeof s.amount === "number" && s.amount > 0),
);

const rupiah = (n) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(n);
const PENDING = "Segera diumumkan";

const updatedLabel = computed(() => {
    if (!props.updatedAt) return null;
    const d = new Date(`${props.updatedAt}T00:00:00`);
    return Number.isNaN(d.getTime())
        ? props.updatedAt
        : d.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
});

const STATUS = {
    active: { label: "Sedang dikumpulkan", variant: "default", icon: Target },
    planned: { label: "Berikutnya", variant: "secondary", icon: Calendar },
    funded: { label: "Tercapai", variant: "success", icon: BadgeCheck },
};
const GOAL_ICON = { "event-top-patrol": Trophy, "storage-tacclips": Server };

const PERK_ICON = { heart: Heart, badge: BadgeCheck, discord: MessageCircle, sparkles: Sparkles };

const otherWays = computed(() =>
    [
        props.githubUrl && {
            icon: GitPullRequest,
            title: "Kontribusi di GitHub",
            description: "Kirim pull request, perbaiki bug, atau beri bintang pada repositori. Proyek ini terbuka untuk kontributor.",
            href: props.githubUrl,
            external: true,
            cta: "Buka repositori",
        },
        {
            icon: Bug,
            title: "Laporkan bug & beri saran",
            description: "Masukan yang jelas sama berharganya dengan donasi: bantu kami tahu apa yang rusak atau kurang.",
            href: "/feedback",
            external: false,
            cta: "Kirim masukan",
        },
        props.discordUrl && {
            icon: MessageCircle,
            title: "Gabung Discord",
            description: "Diskusi fitur baru, ikut uji coba beta, dan bantu sesama pengguna.",
            href: props.discordUrl,
            external: true,
            cta: "Masuk Discord",
        },
        {
            icon: Share2,
            title: "Bagikan & dukung perwira",
            description: "Ajak teman memakai multiview, dan subscribe channel perwira favoritmu di Road to 1K.",
            href: "/officers",
            external: false,
            cta: "Direktori perwira",
        },
    ].filter(Boolean),
);
</script>

<template>
    <Head title="Dukung IME Police Duty" />

    <TacticalLayout>
        <PageContainer size="lg" class="space-y-12 px-4 pb-16 pt-8 sm:px-6 sm:pt-10">
            <!-- Hero -->
            <section class="space-y-5">
                <PageHeader
                    eyebrow="Dukungan"
                    title="Dukung IME Police Duty"
                    description="Platform ini proyek komunitas nirlaba tanpa iklan. Pengeluarannya kami tampilkan secara terbuka di bawah, beserta ke mana setiap dukungan dipakai."
                />
                <div class="flex flex-wrap items-center gap-3">
                    <Button v-if="donateUrl" as-child size="lg" class="rounded-full">
                        <a :href="donateUrl" target="_blank" rel="noopener noreferrer">
                            <Heart class="fill-current" /> Donasi via BagiBagi
                        </a>
                    </Button>
                    <Button v-if="githubUrl" as-child size="lg" variant="outline" class="rounded-full">
                        <a :href="githubUrl" target="_blank" rel="noopener noreferrer">
                            <GitPullRequest /> Kontribusi di GitHub
                        </a>
                    </Button>
                    <span v-if="updatedLabel" class="text-xs text-muted-foreground">Angka diperbarui {{ updatedLabel }}</span>
                </div>
            </section>

            <!-- Supporter wall -->
            <section class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="flex items-center gap-2 text-xl font-bold text-foreground">
                            <HandHeart class="size-5 text-live" /> Dinding Supporter
                        </h2>
                        <p class="text-sm text-muted-foreground">Terima kasih untuk semua yang sudah mendukung.</p>
                    </div>
                    <Badge v-if="leaderboardSynced" variant="secondary" class="gap-1">
                        <BadgeCheck class="size-3 text-success" /> Tersinkron dari BagiBagi
                    </Badge>
                </div>

                <div v-if="supporters.length && hasSupporterAmounts" class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="(s, idx) in supporters"
                        :key="`${s.rank ?? 'm'}-${s.name}-${idx}`"
                        class="flex items-center justify-between gap-3 rounded-xl border border-border bg-card px-4 py-3"
                        :class="s.rank && s.rank <= 3 && 'border-primary/30'"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                v-if="s.rank"
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-bold"
                                :class="s.rank <= 3 ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground'"
                            >
                                <Trophy v-if="s.rank === 1" class="size-4" />
                                <template v-else>#{{ s.rank }}</template>
                            </span>
                            <span
                                v-else
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-live/10 text-live"
                            >
                                <Heart class="size-4 fill-current" />
                            </span>

                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate text-sm font-semibold text-foreground">{{ s.name }}</span>
                                    <BadgeCheck
                                        v-if="s.is_verified"
                                        class="size-3.5 shrink-0 text-info"
                                        aria-label="Akun BagiBagi terverifikasi"
                                    />
                                </div>
                                <p v-if="s.note" class="truncate text-xs text-muted-foreground">{{ s.note }}</p>
                            </div>
                        </div>

                        <span v-if="typeof s.amount === 'number' && s.amount > 0" class="shrink-0 font-mono text-sm font-semibold text-success">
                            {{ rupiah(s.amount) }}
                        </span>
                    </div>
                </div>

                <div v-else-if="supporters.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="(s, idx) in supporters"
                        :key="`${s.name}-${idx}`"
                        class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1.5 text-sm"
                    >
                        <Heart class="size-3.5 fill-current text-live" />
                        <span class="font-semibold text-foreground">{{ s.name }}</span>
                        <span v-if="s.note" class="text-xs text-muted-foreground">· {{ s.note }}</span>
                    </span>
                </div>

                <div v-else class="rounded-xl border border-dashed border-border p-6 text-center">
                    <p class="text-sm text-muted-foreground">Belum ada nama di sini. Jadilah pendukung pertama!</p>
                    <Button v-if="donateUrl" as-child size="sm" class="mt-3 rounded-full">
                        <a :href="donateUrl" target="_blank" rel="noopener noreferrer"><Heart class="fill-current" /> Donasi sekarang</a>
                    </Button>
                </div>
            </section>

            <!-- Monthly costs -->
            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-foreground">Biaya operasional bulanan</h2>
                    <p class="text-sm text-muted-foreground">Biaya rutin agar situs, sinkronisasi live, dan penyimpanan tetap berjalan.</p>
                </div>
                <Card class="py-0">
                    <CardContent class="divide-y divide-border p-0">
                        <div
                            v-for="cost in monthlyCosts"
                            :key="cost.label"
                            class="flex items-start justify-between gap-4 px-4 py-3 sm:px-5"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-foreground">{{ cost.label }}</p>
                                <p v-if="cost.note" class="text-xs text-muted-foreground">{{ cost.note }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1 text-right">
                                <Badge v-if="cost.sponsor" variant="success" class="gap-1">
                                    <HandHeart class="size-3" /> Disponsori {{ cost.sponsor }}
                                </Badge>
                                <template v-else>
                                    <span
                                        class="font-mono text-sm"
                                        :class="cost.amount === null ? 'text-muted-foreground' : 'font-semibold text-foreground'"
                                    >
                                        {{ cost.amount === null ? PENDING : `${rupiah(cost.amount)}/bln` }}
                                    </span>
                                    <Badge v-if="cost.covered_by" variant="secondary" class="gap-1">
                                        <BadgeCheck class="size-3" /> {{ cost.covered_by }}
                                    </Badge>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 bg-muted/40 px-4 py-3 sm:px-5">
                            <span class="text-sm font-bold text-foreground">Total per bulan</span>
                            <span class="font-mono text-sm font-bold" :class="monthlyTotal === null ? 'text-muted-foreground' : 'text-primary'">
                                {{ monthlyTotal === null ? PENDING : `${rupiah(monthlyTotal)}/bln` }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
                <p
                    v-if="costsNote"
                    class="flex items-start gap-2 rounded-xl border border-live/30 bg-live/10 px-4 py-3 text-sm text-foreground"
                >
                    <Heart class="mt-0.5 size-4 shrink-0 fill-current text-live" />
                    <span>{{ costsNote }}</span>
                </p>
            </section>

            <!-- Funding goals -->
            <section v-if="goals.length" class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-foreground">Target berikutnya</h2>
                    <p class="text-sm text-muted-foreground">Ke mana dukungan tambahan akan dipakai, berurutan dari yang sedang dikumpulkan.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <Card
                        v-for="goal in goals"
                        :key="goal.key"
                        class="gap-0 py-0"
                        :class="goal.status === 'active' && 'border-primary/40 ring-1 ring-primary/20'"
                    >
                        <CardContent class="flex h-full flex-col gap-3 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <component :is="GOAL_ICON[goal.key] || Target" class="size-5" />
                                </span>
                                <Badge :variant="STATUS[goal.status].variant" class="gap-1">
                                    <component :is="STATUS[goal.status].icon" class="size-3" />
                                    {{ STATUS[goal.status].label }}
                                </Badge>
                            </div>
                            <h3 class="text-base font-bold text-foreground">{{ goal.title }}</h3>
                            <p class="flex-1 text-sm text-muted-foreground">{{ goal.description }}</p>
                            <div class="space-y-1.5">
                                <template v-if="goal.target">
                                    <Progress :model-value="goal.percent" class="h-2" />
                                    <div class="flex items-baseline justify-between gap-2 font-mono text-xs">
                                        <span class="font-semibold text-foreground">{{ rupiah(goal.collected) }}</span>
                                        <span class="text-muted-foreground">dari {{ rupiah(goal.target) }} · {{ goal.percent }}%</span>
                                    </div>
                                </template>
                                <p v-else class="font-mono text-xs text-muted-foreground">Target dana: {{ PENDING }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- Perks -->
            <section v-if="perks.length" class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-foreground">Yang didapat pendukung</h2>
                    <p class="text-sm text-muted-foreground">Ucapan terima kasih kecil untuk kamu yang ikut menjaga platform ini.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="perk in perks"
                        :key="perk.title"
                        class="flex gap-3 rounded-xl border border-border bg-card p-4"
                    >
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-live/10 text-live">
                            <component :is="PERK_ICON[perk.icon] || Heart" class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-foreground">{{ perk.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ perk.description }}</p>
                            <p v-if="perk.who" class="mt-1.5 text-xs font-medium text-live">{{ perk.who }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Other ways -->
            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-foreground">Cara lain mendukung</h2>
                    <p class="text-sm text-muted-foreground">Tidak harus dengan uang: waktu dan keahlianmu juga sangat membantu.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <component
                        :is="way.external ? 'a' : Link"
                        v-for="way in otherWays"
                        :key="way.title"
                        :href="way.href"
                        v-bind="way.external ? { target: '_blank', rel: 'noopener noreferrer' } : {}"
                        class="group flex gap-3 rounded-xl border border-border bg-card p-4 transition-colors hover:border-primary/40 hover:bg-accent/40"
                    >
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground group-hover:bg-primary/10 group-hover:text-primary">
                            <component :is="way.icon" class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-foreground">{{ way.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ way.description }}</p>
                            <p class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-primary">
                                {{ way.cta }} <ExternalLink v-if="way.external" class="size-3" />
                            </p>
                        </div>
                    </component>
                </div>
            </section>
        </PageContainer>
    </TacticalLayout>
</template>
