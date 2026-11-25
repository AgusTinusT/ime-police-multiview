<script setup>
import { Link } from "@inertiajs/vue3";
import { ArrowRight, BadgeCheck, Heart, Trophy } from "@lucide/vue";
import { computed } from "vue";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";

const props = defineProps({
    highlight: {
        type: Object,
        required: true,
    },
});

const rupiah = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(n);

const goal = computed(() => props.highlight?.goal || null);
const topSupporters = computed(() => props.highlight?.top_supporters || []);
const extraCount = computed(() => Number(props.highlight?.extra_supporters_count || 0));
const supportUrl = computed(() => props.highlight?.support_url || "/dukung");

const collectedLabel = computed(() => {
    const g = goal.value;
    if (!g || typeof g.collected !== "number" || g.collected <= 0) {
        return null;
    }
    if (typeof g.target === "number" && g.target > 0) {
        return `${rupiah(g.collected)} / ${rupiah(g.target)} (${g.percent ?? 0}%)`;
    }
    return `${rupiah(g.collected)} terkumpul`;
});
</script>

<template>
    <section
        aria-label="Dukungan komunitas hadiah patroli"
        class="px-3 sm:px-6 lg:px-12"
    >
        <Card class="border-border bg-card/80 py-0">
            <CardContent
                class="flex flex-col gap-3.5 p-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="min-w-0 flex-1 space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary" class="gap-1.5 text-primary">
                            <Trophy class="size-3.5 shrink-0" />
                            <span>{{ goal?.title || "Dukungan Komunitas" }}</span>
                        </Badge>
                        <span
                            v-if="collectedLabel"
                            class="font-mono text-xs font-semibold text-success"
                        >
                            {{ collectedLabel }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">
                            Terbuka untuk dukungan komunitas
                        </span>
                    </div>

                    <p
                        v-if="goal?.description"
                        class="line-clamp-2 text-xs text-muted-foreground sm:line-clamp-1"
                    >
                        {{ goal.description }}
                    </p>

                    <div
                        v-if="goal?.target && typeof goal.percent === 'number'"
                        class="max-w-md pt-0.5"
                    >
                        <Progress :model-value="goal.percent" class="h-1.5" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:shrink-0">
                    <Link
                        v-for="(s, idx) in topSupporters"
                        :key="`${s.rank ?? 'm'}-${s.name}-${idx}`"
                        :href="supportUrl"
                        class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-border bg-background/80 px-2.5 py-1 text-xs transition-colors hover:border-primary/40"
                    >
                        <span
                            v-if="s.rank"
                            class="font-mono font-bold text-primary"
                        >
                            #{{ s.rank }}
                        </span>
                        <Heart
                            v-else
                            class="size-3 shrink-0 fill-current text-live"
                        />
                        <span class="max-w-32 truncate font-semibold text-foreground">
                            {{ s.name }}
                        </span>
                        <BadgeCheck
                            v-if="s.is_verified"
                            class="size-3 shrink-0 text-info"
                            aria-label="Akun BagiBagi terverifikasi"
                        />
                        <span
                            v-if="typeof s.amount === 'number' && s.amount > 0"
                            class="shrink-0 font-mono text-success"
                        >
                            {{ rupiah(s.amount) }}
                        </span>
                    </Link>

                    <Badge
                        v-if="extraCount > 0"
                        variant="secondary"
                        class="text-xs"
                    >
                        +{{ extraCount }} lainnya
                    </Badge>

                    <Button as-child size="sm" class="rounded-full">
                        <Link :href="supportUrl">
                            Ikut Dukung
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </section>
</template>
