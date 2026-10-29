<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { Head } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import {
    PageContainer,
    PageHeader,
    OfficerAvatar,
    DeptBadge,
    LiveBadge,
    EmptyState,
} from "@/Components/tactical";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "@/Components/ui/card";
import {
    Tabs,
    TabsList,
    TabsTrigger,
    TabsContent,
} from "@/Components/ui/tabs";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Progress } from "@/Components/ui/progress";
import { cn } from "@/lib/utils";
import {
    Calendar,
    Clock,
    Crown,
    ExternalLink,
    Medal,
    Play,
    Target,
    Trophy,
    Users,
    Video,
} from "@lucide/vue";

const { t } = useI18n();

const props = defineProps({
    ranking: {
        type: Object,
        required: true,
        default: () => ({
            duty_month: [],
            duty_last_month: [],
            subscribers: [],
            road_to_1k: [],
            recently_on_duty: [],
        }),
    },
    period_text: {
        type: Object,
        default: () => ({ current: "", previous: "" }),
    },
    currentPeriodText: {
        type: String,
        default: "",
    },
    previousPeriodText: {
        type: String,
        default: "",
    },
});

const VALID_TABS = [
    "duty_month",
    "duty_last_month",
    "subscribers",
    "road_to_1k",
    "recently_on_duty",
];

const getTabFromUrl = () => {
    if (typeof window === "undefined") return "duty_month";
    const params = new URLSearchParams(window.location.search);
    const tab = params.get("tab");
    return VALID_TABS.includes(tab) ? tab : "duty_month";
};

const activeTab = ref(getTabFromUrl());

const setActiveTab = (tab) => {
    if (!VALID_TABS.includes(tab)) return;
    activeTab.value = tab;
    if (typeof window !== "undefined") {
        const url = new URL(window.location.href);
        if (tab === "duty_month") {
            url.searchParams.delete("tab");
        } else {
            url.searchParams.set("tab", tab);
        }
        window.history.pushState({ tab }, "", url.toString());
    }
};

const handlePopState = () => {
    activeTab.value = getTabFromUrl();
};

onMounted(() => {
    window.addEventListener("popstate", handlePopState);
});

onUnmounted(() => {
    window.removeEventListener("popstate", handlePopState);
});

const currentList = computed(() => props.ranking?.[activeTab.value] || []);
const topThree = computed(() => currentList.value.slice(0, 3));
const ranksFourPlus = computed(() => currentList.value.slice(3));

const activePeriodText = computed(() => {
    if (activeTab.value === "duty_last_month") {
        return props.previousPeriodText || props.period_text?.previous || "";
    }
    return props.currentPeriodText || props.period_text?.current || "";
});

const formatDate = (isoString) => {
    if (!isoString) return "-";
    try {
        const date = new Date(isoString);
        return date.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch {
        return isoString;
    }
};
</script>

<template>
    <Head :title="t('ranking.title')" />

    <TacticalLayout activeTab="ranking">
        <PageContainer>
            <!-- Page Header -->
            <PageHeader
                :title="t('ranking.title')"
                :description="t('ranking.subtitle')"
            >
                <template #actions>
                    <div
                        v-if="activePeriodText"
                        class="flex items-center gap-2 rounded-md border border-border bg-card/60 px-3 py-1.5 text-xs font-mono text-muted-foreground"
                    >
                        <Calendar class="size-3.5 text-primary" />
                        <span>{{ activePeriodText }}</span>
                    </div>
                </template>
            </PageHeader>

            <!-- Navigation Tabs -->
            <Tabs :model-value="activeTab" @update:model-value="setActiveTab" class="w-full space-y-6">
                <div class="overflow-x-auto pb-1 scrollbar-none">
                    <TabsList class="inline-flex h-10 items-center justify-start rounded-lg bg-muted p-1 text-muted-foreground font-mono text-xs">
                        <TabsTrigger value="duty_month" class="px-3 py-1.5 gap-1.5 whitespace-nowrap">
                            <Clock class="size-3.5" />
                            <span>{{ t("ranking.tabs.duty_month") }}</span>
                        </TabsTrigger>
                        <TabsTrigger value="duty_last_month" class="px-3 py-1.5 gap-1.5 whitespace-nowrap">
                            <Calendar class="size-3.5" />
                            <span>{{ t("ranking.tabs.duty_last_month") }}</span>
                        </TabsTrigger>
                        <TabsTrigger value="subscribers" class="px-3 py-1.5 gap-1.5 whitespace-nowrap">
                            <Users class="size-3.5" />
                            <span>{{ t("ranking.tabs.subscribers") }}</span>
                        </TabsTrigger>
                        <TabsTrigger value="road_to_1k" class="px-3 py-1.5 gap-1.5 whitespace-nowrap">
                            <Target class="size-3.5" />
                            <span>{{ t("ranking.tabs.road_to_1k") }}</span>
                        </TabsTrigger>
                        <TabsTrigger value="recently_on_duty" class="px-3 py-1.5 gap-1.5 whitespace-nowrap">
                            <Video class="size-3.5" />
                            <span>{{ t("ranking.tabs.recently_on_duty") }}</span>
                        </TabsTrigger>
                    </TabsList>
                </div>

                <!-- Tab Content Panel -->
                <TabsContent :value="activeTab" class="mt-0 focus-visible:outline-hidden space-y-8">
                    <!-- Empty State -->
                    <EmptyState
                        v-if="currentList.length === 0"
                        :title="t(`ranking.empty.${activeTab}`)"
                        :description="t('ranking.subtitle')"
                        :icon="Trophy"
                    />

                    <!-- Content with Data -->
                    <div v-else class="space-y-8">
                        <!-- Top 3 Podium Section -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-end gap-4 pt-2">
                            <!-- Rank 2: Silver (Left on Desktop, 2nd on Mobile) -->
                            <Card
                                v-if="topThree[1]"
                                class="order-2 sm:order-1 relative flex flex-col justify-between border-border bg-card/80 p-4 sm:p-5 hover:border-input transition-colors"
                            >
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <Badge variant="outline" class="border-rank-silver text-rank-silver gap-1 font-mono text-xs">
                                            <Medal class="size-3.5 text-rank-silver" />
                                            <span>{{ (activeTab === 'duty_month' || activeTab === 'duty_last_month') ? t('leaderboard.rank_2_silver') : t('ranking.podium.rank_2') }}</span>
                                        </Badge>
                                        <span class="font-mono text-xs font-bold text-muted-foreground">#2</span>
                                    </div>

                                    <div class="flex flex-col items-center text-center space-y-2">
                                        <OfficerAvatar
                                            :src="topThree[1].avatar_url"
                                            :name="topThree[1].officer_name"
                                            size="lg"
                                        />
                                        <div class="w-full min-w-0">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <h3 class="text-sm sm:text-base font-bold text-foreground truncate">
                                                    {{ topThree[1].officer_name }}
                                                </h3>
                                                <LiveBadge v-if="topThree[1].is_live" />
                                            </div>
                                            <div class="flex items-center justify-center gap-1.5 mt-1">
                                                <DeptBadge :dept="topThree[1].department" />
                                                <span class="text-xs text-muted-foreground font-mono truncate">
                                                    {{ topThree[1].callsign }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Value Metric -->
                                    <div v-if="activeTab === 'road_to_1k'" class="space-y-1.5 pt-2">
                                        <div class="flex items-center justify-between text-xs font-mono">
                                            <span class="text-muted-foreground">Target</span>
                                            <span class="font-bold text-primary">{{ topThree[1].value_label }}</span>
                                        </div>
                                        <Progress :model-value="Math.min(100, (topThree[1].value / 1000) * 100)" class="h-2" />
                                    </div>
                                    <div v-else-if="activeTab === 'duty_month' || activeTab === 'duty_last_month'" class="text-center pt-2">
                                        <div class="text-xl font-bold font-mono text-foreground">
                                            {{ topThree[1].value_label }}
                                        </div>
                                        <div class="flex items-center justify-center gap-2 text-xs text-muted-foreground font-mono mt-0.5">
                                            <span v-if="topThree[1].avg_shift">{{ topThree[1].avg_shift }}</span>
                                            <span v-if="topThree[1].percent !== undefined">· {{ topThree[1].percent }}% dari #1</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-center pt-2">
                                        <div class="text-xl font-bold font-mono text-foreground">
                                            {{ topThree[1].value_label }}
                                        </div>
                                        <div class="text-xs text-muted-foreground font-mono mt-0.5">
                                            {{ activeTab === 'recently_on_duty' ? formatDate(topThree[1].value) : 'YouTube' }}
                                        </div>
                                    </div>
                                </div>

                                <Button as-child variant="outline" size="sm" class="w-full mt-4 gap-1.5">
                                    <a :href="topThree[1].youtube_url" target="_blank" rel="noopener noreferrer">
                                        <Play class="size-3.5 fill-current" />
                                        <span>YouTube</span>
                                    </a>
                                </Button>
                            </Card>

                            <!-- Rank 1: Gold / Chief of On-Air (Center & Taller on Desktop, 1st on Mobile) -->
                            <Card
                                v-if="topThree[0]"
                                class="order-1 sm:order-2 relative flex flex-col justify-between border-primary/60 bg-card p-5 sm:p-6 sm:-mt-4 shadow-lg shadow-primary/5 hover:border-primary transition-colors"
                            >
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <Badge class="bg-primary text-primary-foreground font-mono text-xs gap-1">
                                            <Crown class="size-3.5 fill-current" />
                                            <span>{{ (activeTab === 'duty_month' || activeTab === 'duty_last_month') ? t('leaderboard.rank_1_gold') : t('ranking.podium.rank_1') }}</span>
                                        </Badge>
                                        <span class="font-mono text-xs font-bold text-primary">#1</span>
                                    </div>

                                    <div class="flex flex-col items-center text-center space-y-2">
                                        <div class="relative">
                                            <OfficerAvatar
                                                :src="topThree[0].avatar_url"
                                                :name="topThree[0].officer_name"
                                                size="lg"
                                                class="size-16 sm:size-20 border-2 border-primary"
                                            />
                                            <div class="absolute -top-2 -right-2 rounded-full bg-primary p-1 text-primary-foreground shadow-sm">
                                                <Crown class="size-3.5 fill-current" />
                                            </div>
                                        </div>
                                        <div class="w-full min-w-0">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <h3 class="text-base sm:text-lg font-bold text-foreground truncate">
                                                    {{ topThree[0].officer_name }}
                                                </h3>
                                                <LiveBadge v-if="topThree[0].is_live" />
                                            </div>
                                            <div class="flex items-center justify-center gap-1.5 mt-1">
                                                <DeptBadge :dept="topThree[0].department" />
                                                <span class="text-xs text-muted-foreground font-mono truncate">
                                                    {{ topThree[0].callsign }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Value Metric -->
                                    <div v-if="activeTab === 'road_to_1k'" class="space-y-1.5 pt-2">
                                        <div class="flex items-center justify-between text-xs font-mono">
                                            <span class="text-muted-foreground">Target</span>
                                            <span class="font-bold text-primary">{{ topThree[0].value_label }}</span>
                                        </div>
                                        <Progress :model-value="Math.min(100, (topThree[0].value / 1000) * 100)" class="h-2" />
                                    </div>
                                    <div v-else-if="activeTab === 'duty_month' || activeTab === 'duty_last_month'" class="text-center pt-2">
                                        <div class="text-2xl font-bold font-mono text-primary">
                                            {{ topThree[0].value_label }}
                                        </div>
                                        <div class="flex items-center justify-center gap-2 text-xs text-muted-foreground font-mono mt-0.5">
                                            <span v-if="topThree[0].avg_shift">{{ topThree[0].avg_shift }}</span>
                                            <span>· 100% Puncak</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-center pt-2">
                                        <div class="text-2xl font-bold font-mono text-primary">
                                            {{ topThree[0].value_label }}
                                        </div>
                                        <div class="text-xs text-muted-foreground font-mono mt-0.5">
                                            {{ activeTab === 'recently_on_duty' ? formatDate(topThree[0].value) : 'YouTube' }}
                                        </div>
                                    </div>
                                </div>

                                <Button as-child size="sm" class="w-full mt-4 gap-1.5">
                                    <a :href="topThree[0].youtube_url" target="_blank" rel="noopener noreferrer">
                                        <Play class="size-3.5 fill-current" />
                                        <span>Tonton Kanal YouTube</span>
                                    </a>
                                </Button>
                            </Card>

                            <!-- Rank 3: Bronze (Right on Desktop, 3rd on Mobile) -->
                            <Card
                                v-if="topThree[2]"
                                class="order-3 sm:order-3 relative flex flex-col justify-between border-border bg-card/80 p-4 sm:p-5 hover:border-input transition-colors"
                            >
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <Badge variant="outline" class="border-rank-bronze text-rank-bronze gap-1 font-mono text-xs">
                                            <Medal class="size-3.5 text-rank-bronze" />
                                            <span>{{ (activeTab === 'duty_month' || activeTab === 'duty_last_month') ? t('leaderboard.rank_3_bronze') : t('ranking.podium.rank_3') }}</span>
                                        </Badge>
                                        <span class="font-mono text-xs font-bold text-muted-foreground">#3</span>
                                    </div>

                                    <div class="flex flex-col items-center text-center space-y-2">
                                        <OfficerAvatar
                                            :src="topThree[2].avatar_url"
                                            :name="topThree[2].officer_name"
                                            size="lg"
                                        />
                                        <div class="w-full min-w-0">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <h3 class="text-sm sm:text-base font-bold text-foreground truncate">
                                                    {{ topThree[2].officer_name }}
                                                </h3>
                                                <LiveBadge v-if="topThree[2].is_live" />
                                            </div>
                                            <div class="flex items-center justify-center gap-1.5 mt-1">
                                                <DeptBadge :dept="topThree[2].department" />
                                                <span class="text-xs text-muted-foreground font-mono truncate">
                                                    {{ topThree[2].callsign }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Value Metric -->
                                    <div v-if="activeTab === 'road_to_1k'" class="space-y-1.5 pt-2">
                                        <div class="flex items-center justify-between text-xs font-mono">
                                            <span class="text-muted-foreground">Target</span>
                                            <span class="font-bold text-primary">{{ topThree[2].value_label }}</span>
                                        </div>
                                        <Progress :model-value="Math.min(100, (topThree[2].value / 1000) * 100)" class="h-2" />
                                    </div>
                                    <div v-else-if="activeTab === 'duty_month' || activeTab === 'duty_last_month'" class="text-center pt-2">
                                        <div class="text-xl font-bold font-mono text-foreground">
                                            {{ topThree[2].value_label }}
                                        </div>
                                        <div class="flex items-center justify-center gap-2 text-xs text-muted-foreground font-mono mt-0.5">
                                            <span v-if="topThree[2].avg_shift">{{ topThree[2].avg_shift }}</span>
                                            <span v-if="topThree[2].percent !== undefined">· {{ topThree[2].percent }}% dari #1</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-center pt-2">
                                        <div class="text-xl font-bold font-mono text-foreground">
                                            {{ topThree[2].value_label }}
                                        </div>
                                        <div class="text-xs text-muted-foreground font-mono mt-0.5">
                                            {{ activeTab === 'recently_on_duty' ? formatDate(topThree[2].value) : 'YouTube' }}
                                        </div>
                                    </div>
                                </div>

                                <Button as-child variant="outline" size="sm" class="w-full mt-4 gap-1.5">
                                    <a :href="topThree[2].youtube_url" target="_blank" rel="noopener noreferrer">
                                        <Play class="size-3.5 fill-current" />
                                        <span>YouTube</span>
                                    </a>
                                </Button>
                            </Card>
                        </div>

                        <!-- Ranks 4+ Table (Desktop) & Cards (Mobile) -->
                        <div v-if="ranksFourPlus.length > 0" class="space-y-4 pt-4">
                            <h2 class="text-sm font-mono font-bold tracking-wider uppercase text-muted-foreground">
                                Peringkat Lanjutan (#4 - #{{ currentList.length }})
                            </h2>

                            <!-- Desktop Table (sm:table) -->
                            <Card class="hidden sm:block overflow-hidden">
                                <Table>
                                    <TableHeader>
                                        <TableRow class="hover:bg-transparent">
                                            <TableHead class="w-14 font-mono text-xs">
                                                {{ t("ranking.columns.rank") }}
                                            </TableHead>
                                            <TableHead class="font-mono text-xs">
                                                {{ t("ranking.columns.officer") }}
                                            </TableHead>
                                            <TableHead class="font-mono text-xs">
                                                {{ t("ranking.columns.department") }}
                                            </TableHead>
                                            <TableHead class="font-mono text-xs">
                                                {{ (activeTab === 'duty_month' || activeTab === 'duty_last_month') ? t('ranking.columns.hours') : activeTab === 'subscribers' ? t('ranking.columns.subscribers') : activeTab === 'road_to_1k' ? t('ranking.columns.progress') : t('ranking.columns.last_duty') }}
                                            </TableHead>
                                            <TableHead class="w-28 text-right font-mono text-xs">
                                                {{ t("ranking.columns.channel") }}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="(item, index) in ranksFourPlus"
                                            :key="item.id"
                                            class="hover:bg-muted/40 transition-colors"
                                        >
                                            <!-- Rank Number -->
                                            <TableCell class="font-mono font-bold text-xs text-muted-foreground">
                                                #{{ index + 4 }}
                                            </TableCell>

                                            <!-- Officer Name & Avatar -->
                                            <TableCell>
                                                <div class="flex items-center gap-3">
                                                    <OfficerAvatar
                                                        :src="item.avatar_url"
                                                        :name="item.officer_name"
                                                        size="sm"
                                                    />
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-xs font-semibold text-foreground truncate">
                                                                {{ item.officer_name }}
                                                            </span>
                                                            <LiveBadge v-if="item.is_live" />
                                                        </div>
                                                        <p class="text-xs text-muted-foreground font-mono truncate">
                                                            {{ item.callsign }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </TableCell>

                                            <!-- Department & Rank -->
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <DeptBadge :dept="item.department" />
                                                    <span v-if="item.rank" class="text-xs text-muted-foreground font-mono">
                                                        {{ item.rank }}
                                                    </span>
                                                </div>
                                            </TableCell>

                                            <!-- Metric / Value -->
                                            <TableCell>
                                                <div v-if="activeTab === 'road_to_1k'" class="flex items-center gap-3 max-w-xs">
                                                    <Progress :model-value="Math.min(100, (item.value / 1000) * 100)" class="h-2 flex-1" />
                                                    <span class="font-mono text-xs font-bold text-primary shrink-0">
                                                        {{ item.value_label }}
                                                    </span>
                                                </div>
                                                <div v-else-if="activeTab === 'duty_month' || activeTab === 'duty_last_month'" class="space-y-0.5">
                                                    <span class="font-mono text-xs font-bold text-foreground">
                                                        {{ item.value_label }}
                                                    </span>
                                                    <p v-if="item.avg_shift" class="text-xs text-muted-foreground font-mono">
                                                        {{ item.avg_shift }}
                                                    </p>
                                                </div>
                                                <div v-else class="space-y-0.5">
                                                    <span class="font-mono text-xs font-bold text-foreground">
                                                        {{ item.value_label }}
                                                    </span>
                                                    <p v-if="activeTab === 'recently_on_duty'" class="text-xs text-muted-foreground font-mono">
                                                        {{ formatDate(item.value) }}
                                                    </p>
                                                </div>
                                            </TableCell>

                                            <!-- YouTube Action Button -->
                                            <TableCell class="text-right">
                                                <Button
                                                    as-child
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 px-2.5 text-xs gap-1.5"
                                                    title="Buka Channel YouTube"
                                                >
                                                    <a :href="item.youtube_url" target="_blank" rel="noopener noreferrer">
                                                        <Play class="size-3 fill-current" />
                                                        <span>YouTube</span>
                                                    </a>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </Card>

                            <!-- Mobile Compact List (sm:hidden) -->
                            <div class="grid grid-cols-1 gap-2.5 sm:hidden">
                                <Card
                                    v-for="(item, index) in ranksFourPlus"
                                    :key="item.id"
                                    class="p-3 flex items-center justify-between gap-3 hover:border-input transition-colors"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="font-mono font-bold text-xs text-muted-foreground w-6 shrink-0">
                                            #{{ index + 4 }}
                                        </span>
                                        <OfficerAvatar
                                            :src="item.avatar_url"
                                            :name="item.officer_name"
                                            size="sm"
                                        />
                                        <div class="min-w-0 space-y-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <h4 class="text-xs font-semibold text-foreground truncate">
                                                    {{ item.officer_name }}
                                                </h4>
                                                <LiveBadge v-if="item.is_live" />
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <DeptBadge :dept="item.department" />
                                                <span class="font-mono text-xs font-bold text-primary">
                                                    {{ item.value_label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <Button
                                        as-child
                                        variant="outline"
                                        size="icon"
                                        class="size-8 shrink-0"
                                        aria-label="Buka Channel YouTube"
                                        title="Buka Channel YouTube"
                                    >
                                        <a :href="item.youtube_url" target="_blank" rel="noopener noreferrer">
                                            <Play class="size-3.5 fill-current" />
                                        </a>
                                    </Button>
                                </Card>
                            </div>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </PageContainer>
    </TacticalLayout>
</template>
