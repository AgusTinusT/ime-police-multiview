<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import { AlertTriangle, CheckCircle2, ExternalLink } from "@lucide/vue";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { EmptyState, PageContainer, PageHeader } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import PlatformIcon from "@/Components/BioLink/PlatformIcon.vue";
import { PLATFORM_KEYS } from "@/Components/BioLink/platformIcons";
import { contrast, ensureContrast, isNearLiveRed, parseHex } from "@/lib/color";

// QA page for the bio-link theme contract: each preset rendered on a real
// profile through ?theme=<key>, next to automatic colour checks.
const props = defineProps({
    themes: { type: Object, default: () => ({}) },
    sampleSlug: { type: String, default: null },
});

const DEFAULT_CARD = "#0f172a";
const fmt = (n) => n.toFixed(1);

const rows = computed(() =>
    Object.entries(props.themes).map(([key, t]) => {
        const card = parseHex(t.surface) ? t.surface : DEFAULT_CARD;
        const accent = t.accent || "#3b82f6";
        const accent2 = t.accent_2 || "#f59e0b";
        const accentContrast = contrast(accent, card);
        const accent2Contrast = contrast(accent2, card);
        const issues = [];
        if (accentContrast < 4.5) {
            issues.push(`Aksen ${fmt(accentContrast)}:1 di atas kartu — dinaikkan otomatis ke ${ensureContrast(accent, card)}`);
        }
        if (accent2Contrast < 4.5) {
            issues.push(`Aksen 2 ${fmt(accent2Contrast)}:1 — dinaikkan otomatis ke ${ensureContrast(accent2, card)}`);
        }
        if (contrast(accent, accent2) < 1.4) {
            issues.push("Aksen & aksen 2 terlalu mirip — donasi tidak menonjol");
        }
        if (isNearLiveRed(accent) || isNearLiveRed(accent2)) {
            issues.push("Terlalu dekat dengan merah LIVE — status siaran bisa tertukar");
        }
        return { key, ...t, card, accent, accent2, accentContrast, accent2Contrast, issues };
    }),
);
</script>

<template>
    <Head title="Bio-Link Themes" />

    <TacticalLayout>
        <PageContainer class="space-y-8 overflow-x-hidden py-8 lg:py-12">
            <PageHeader
                eyebrow="BIO-LINK // THEME QA"
                title="Pratinjau Tema Bio-Link"
                description="Setiap preset di config/biolinks.php dirender pada profil nyata. Cek warna, kontras, dan semua jenis tautan sebelum tema dipakai officer."
            />

            <!-- S-04 Platform Icons (13 keys) -->
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-card p-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Ikon Platform:
                </span>
                <div
                    v-for="key in PLATFORM_KEYS"
                    :key="key"
                    class="flex items-center gap-1.5 rounded-md border border-border bg-muted/40 px-2.5 py-1 text-xs text-foreground"
                >
                    <PlatformIcon :platform="key" class="size-4 text-primary" />
                    <span class="capitalize">{{ key }}</span>
                </div>
            </div>

            <EmptyState
                v-if="!sampleSlug"
                title="Belum ada profil contoh"
                description="Tambahkan ?slug=<slug> di URL atau buat profil bio-link yang sudah diklaim."
            />

            <div v-else class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <Card v-for="row in rows" :key="row.key" class="gap-4 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-foreground">{{ row.name }}</h2>
                            <p class="font-mono text-xs text-muted-foreground">{{ row.key }}</p>
                        </div>
                        <Button as-child variant="outline" size="sm">
                            <a :href="`/@${sampleSlug}?theme=${row.key}`" target="_blank" rel="noopener">
                                <ExternalLink class="size-4" /> Buka
                            </a>
                        </Button>
                    </div>

                    <!-- Token swatches -->
                    <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <span class="size-4 rounded-sm border border-border" :style="{ background: row.accent }" />
                            Aksen · {{ fmt(row.accentContrast) }}:1
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-4 rounded-sm border border-border" :style="{ background: row.accent2 }" />
                            Aksen 2 · {{ fmt(row.accent2Contrast) }}:1
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-4 rounded-sm border border-border" :style="{ background: row.card }" />
                            Kartu
                        </span>
                        <Badge variant="secondary">{{ row.backdrop || "grid" }}</Badge>
                    </div>

                    <!-- Automatic checks -->
                    <ul v-if="row.issues.length" class="space-y-1 text-xs text-warning">
                        <li v-for="issue in row.issues" :key="issue" class="flex gap-1.5">
                            <AlertTriangle class="mt-0.5 size-3.5 shrink-0" /> {{ issue }}
                        </li>
                    </ul>
                    <p v-else class="flex items-center gap-1.5 text-xs text-success">
                        <CheckCircle2 class="size-3.5" /> Lolos semua pemeriksaan warna
                    </p>

                    <!-- The real page, mobile width -->
                    <div class="overflow-hidden rounded-lg border border-border">
                        <iframe
                            :src="`/@${sampleSlug}?theme=${row.key}`"
                            :title="`Pratinjau ${row.name}`"
                            loading="lazy"
                            class="block h-[640px] w-full bg-background"
                        />
                    </div>
                </Card>
            </div>
        </PageContainer>
    </TacticalLayout>
</template>
