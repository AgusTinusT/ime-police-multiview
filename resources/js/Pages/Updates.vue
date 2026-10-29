<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { MessageSquare, ArrowRight, Sparkles } from "@lucide/vue";
import { releasesId } from "@/Data/releases.id.js";
import { releasesEn } from "@/Data/releases.en.js";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card, CardContent } from "@/Components/ui/card";
import { Separator } from "@/Components/ui/separator";
import {
    PageContainer,
    PageHeader,
    StatTile,
    EmptyState,
} from "@/Components/tactical";
import { cn } from "@/lib/utils";

const { t, locale } = useI18n();

defineProps({
    appVersion: {
        type: String,
        default: "2.5.0-Pro",
    },
});

// Category Filter State
const selectedCategory = ref("ALL");

const categories = computed(() => [
    { id: "ALL", label: t("updates.filter_all") },
    { id: "FEATURE", label: t("updates.filter_feature") },
    { id: "BUGFIX", label: t("updates.filter_bugfix") },
    { id: "PERFORMANCE", label: t("updates.filter_performance") },
    { id: "UIUX", label: t("updates.filter_uiux") },
]);

const changeBadgeClass = (type) => {
    if (type === "FEATURE") return "bg-info/15 text-info border-info/30";
    if (type === "BUGFIX") return "bg-success/15 text-success border-success/30";
    if (type === "PERFORMANCE") return "bg-warning/15 text-warning border-warning/30";
    return "bg-muted text-muted-foreground border-border";
};

// Release History Dataset (Multi-language Data Modules)
const releases = computed(() =>
    locale.value === "en" ? releasesEn : releasesId,
);

// Filtered Releases computed
const filteredReleases = computed(() => {
    if (selectedCategory.value === "ALL") {
        return releases.value;
    }
    return releases.value
        .map((rel) => {
            const filtered = rel.changes.filter(
                (c) => c.type === selectedCategory.value,
            );
            if (filtered.length === 0) return null;
            return {
                ...rel,
                changes: filtered,
            };
        })
        .filter(Boolean);
});
</script>

<template>
    <Head title="System Updates & Release Notes — IME RP Police Multiview" />

    <TacticalLayout activeTab="updates">
        <PageContainer size="lg" class="py-8 space-y-8">
            <!-- HERO HEADER BANNER -->
            <Card>
                <CardContent class="p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="max-w-prose">
                        <PageHeader
                            eyebrow="SYSTEM CHANGELOG"
                            :title="$t('updates.title')"
                            :subtitle="$t('updates.subtitle')"
                        />
                    </div>

                    <!-- Platform Version Card -->
                    <div class="bg-muted/50 border border-border rounded-xl p-4 shrink-0 flex flex-col items-center justify-center text-center gap-1 md:w-48">
                        <div class="text-xs font-mono font-bold text-muted-foreground uppercase tracking-widest">
                            VERSI SAAT INI
                        </div>
                        <div class="text-2xl font-bold font-mono text-primary tracking-wider">
                            {{ appVersion }}
                        </div>
                        <div class="text-xs text-muted-foreground font-mono mt-0.5">
                            Aktif Seluruh Unit
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- METRICS SUMMARY ROW -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <StatTile label="TOTAL RILIS" value="4 Versi" />
                <StatTile label="VERSI UTAMA" value="v2.4.0-Pro" tone="primary" />
                <StatTile label="HASHTAG RULE" value="Wajib #imepolice" />
                <StatTile label="UPDATE TERAKHIR" value="17 Sep 2026" />
            </div>

            <!-- CATEGORY FILTER TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <Button
                    v-for="cat in categories"
                    :key="cat.id"
                    size="sm"
                    :variant="selectedCategory === cat.id ? 'default' : 'outline'"
                    class="font-mono shrink-0"
                    @click="selectedCategory = cat.id"
                >
                    {{ cat.label }}
                </Button>
            </div>

            <!-- RELEASE TIMELINE SECTION -->
            <div class="space-y-6">
                <div
                    v-for="rel in filteredReleases"
                    :key="rel.version"
                    class="relative pl-6 sm:pl-8 border-l border-border space-y-3 group"
                >
                    <!-- Timeline Marker Dot -->
                    <div
                        :class="
                            cn(
                                'absolute left-[-7px] top-2 size-3.5 rounded-full border transition duration-300',
                                rel.isLatest
                                    ? 'bg-primary border-primary'
                                    : 'bg-muted border-border group-hover:border-primary/50',
                            )
                        "
                    ></div>

                    <!-- Release Header Card -->
                    <Card>
                        <CardContent class="p-5 space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-lg font-bold font-mono text-foreground tracking-tight">
                                        {{ rel.version }}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        :class="
                                            rel.isLatest
                                                ? 'bg-primary/15 text-primary border-primary/30 font-mono uppercase'
                                                : 'font-mono uppercase'
                                        "
                                    >
                                        {{ rel.badge }}
                                    </Badge>
                                </div>
                                <span class="text-xs font-mono text-muted-foreground font-medium">
                                    {{ rel.date }}
                                </span>
                            </div>

                            <Separator />

                            <!-- Summary Paragraph -->
                            <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed max-w-prose">
                                {{ rel.summary }}
                            </p>

                            <!-- Changes List -->
                            <div class="grid grid-cols-1 gap-2.5 pt-1">
                                <div
                                    v-for="(change, cIdx) in rel.changes"
                                    :key="cIdx"
                                    class="bg-muted/40 border border-border rounded-lg p-3.5 space-y-1.5"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-foreground">
                                            {{ change.title }}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            :class="cn('font-mono shrink-0', changeBadgeClass(change.type))"
                                        >
                                            {{ change.tag }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground leading-relaxed max-w-prose">
                                        {{ change.description }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-if="filteredReleases.length === 0">
                    <EmptyState
                        :icon="Sparkles"
                        title="Tidak Ada Catatan Rilis"
                        description="Tidak ada catatan perubahan untuk kategori ini."
                    />
                </div>
            </div>

            <!-- FOOTER CALLOUT BANNER -->
            <Card>
                <CardContent class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-foreground">
                            {{ $t("updates.footer_suggestion") }}
                        </h4>
                    </div>
                    <Button as-child variant="outline" size="sm">
                        <Link href="/feedback">
                            <MessageSquare class="size-3.5 mr-1.5" />
                            <span>{{ $t("updates.footer_btn_report") }}</span>
                            <ArrowRight class="size-3.5 ml-1.5" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </PageContainer>
    </TacticalLayout>
</template>
