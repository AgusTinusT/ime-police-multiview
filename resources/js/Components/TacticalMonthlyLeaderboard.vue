<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { LiveBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Clock, Crown, Info, Medal, Play } from "@lucide/vue";

const { t } = useI18n();

const props = defineProps({
    title: {
        type: String,
        default: "",
    },
    subtitle: {
        type: String,
        default: "",
    },
    leaderboard: {
        type: Object,
        default: () => null,
    },
});

const displayTitle = computed(() => props.title || t("leaderboard.title"));
const displaySubtitle = computed(
    () => props.subtitle || t("leaderboard.subtitle"),
);

const defaultTopStreamers = [
    {
        rank: 1,
        officer_name: "Commander Arya 'Ghost'",
        streamer_name: "Arya_SWAT",
        callsign: "Special Enforcement Bureau / SWAT",
        badge: "EMAS",
        avatar_url:
            "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=160&h=160&fit=crop&crop=faces",
        total_hours: "184 Jam 15m",
        avg_shift: "6.1j / hari",
        percent: 100,
        is_live: true,
        live_viewers: 890,
        youtube_url: "https://youtube.com",
    },
    {
        rank: 2,
        officer_name: "Sgt. Dimas Wicaksono",
        streamer_name: "Dimas_K9",
        callsign: "K-9 Unit Supervisor",
        badge: "SILVER",
        avatar_url:
            "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=faces",
        total_hours: "158 Jam 40m",
        avg_shift: "5.2j / shift",
        percent: 86,
        is_live: true,
        youtube_url: "https://youtube.com",
    },
    {
        rank: 3,
        officer_name: "Officer Rendy Pratama",
        streamer_name: "Rendy_ASD",
        callsign: "Air Support Division (Pilot 10-9)",
        badge: "BRONZE",
        avatar_url:
            "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=150&h=150&fit=crop&crop=faces",
        total_hours: "142 Jam 10m",
        avg_shift: "4.7j / shift",
        percent: 77,
        is_live: false,
        youtube_url: "https://youtube.com",
    },
];

const defaultRanksFourToSix = [
    {
        rank: 4,
        officer_name: "Det. Maya Safira",
        role: "CID Detective • 118 Jam",
        avatar_url:
            "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop&crop=faces",
        youtube_url: "https://youtube.com",
    },
    {
        rank: 5,
        officer_name: 'Ofc. Budi "Speedy"',
        role: "Traffic Div • 105 Jam",
        avatar_url:
            "https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=80&h=80&fit=crop&crop=faces",
        youtube_url: "https://youtube.com",
    },
    {
        rank: 6,
        officer_name: "Cadet Rian Febrian",
        role: "Academy • 98 Jam",
        avatar_url:
            "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=80&h=80&fit=crop&crop=faces",
        youtube_url: "https://youtube.com",
    },
];

const getDefaultPeriodText = () => {
    const now = new Date();
    const monthNames = [
        "JANUARI",
        "FEBRUARI",
        "MARET",
        "APRIL",
        "MEI",
        "JUNI",
        "JULI",
        "AGUSTUS",
        "SEPTEMBER",
        "OKTOBER",
        "NOVEMBER",
        "DESEMBER",
    ];
    const monthYear = `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
    return `${monthYear}`;
};

const getDefaultResetCycleText = () => {
    const now = new Date();
    const totalDaysInMonth = new Date(
        now.getFullYear(),
        now.getMonth() + 1,
        0,
    ).getDate();
    const daysLeft = Math.max(1, totalDaysInMonth - now.getDate());
    return t("leaderboard.reset_cycle", { days: daysLeft });
};

const periodText = computed(() => {
    const raw = props.leaderboard?.period_text || getDefaultPeriodText();
    return raw.replace(/[()]/g, "").trim();
});

const resetCycleText = computed(() => {
    return props.leaderboard?.reset_cycle_text || getDefaultResetCycleText();
});

const topStreamers = computed(() => {
    if (
        props.leaderboard?.top_streamers &&
        props.leaderboard.top_streamers.length > 0
    ) {
        const result = [...props.leaderboard.top_streamers];
        while (result.length < 3) {
            result.push(defaultTopStreamers[result.length]);
        }
        return result;
    }
    return defaultTopStreamers;
});

const ranksFourToSix = computed(() => {
    if (
        props.leaderboard?.ranks_four_to_six &&
        props.leaderboard.ranks_four_to_six.length > 0
    ) {
        return props.leaderboard.ranks_four_to_six;
    }
    return defaultRanksFourToSix;
});

const openChannel = (url) => {
    if (!url) return;
    let targetUrl = url;
    if (
        targetUrl.includes("youtube.com/") &&
        !targetUrl.includes("/channel/") &&
        !targetUrl.includes("/watch?") &&
        !targetUrl.includes("/embed/") &&
        !targetUrl.includes("/live_chat?") &&
        !targetUrl.includes("/@")
    ) {
        targetUrl = targetUrl.replace("youtube.com/", "youtube.com/@");
    }
    window.open(targetUrl, "_blank");
};
</script>

<template>
    <section
        id="officerLeaderboard"
        class="w-full space-y-6 font-sans relative"
    >
        <!-- Section Header -->
        <div
            class="text-center py-2 mb-5 sm:mb-12 space-y-2 font-sans max-w-4xl mx-auto"
        >
            <Badge
                variant="warning"
                class="font-mono text-xs font-semibold uppercase tracking-wider"
            >
                {{ periodText }}
            </Badge>

            <h2
                class="text-xl sm:text-3xl lg:text-4xl font-bold text-foreground tracking-tight uppercase leading-tight"
            >
                {{ displayTitle }}
            </h2>
        </div>

        <!-- Podium Top 3 Dedicated Streamers Grid -->
        <div
            class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5 lg:gap-6 items-end"
            id="podiumLeaderboard"
        >
            <!-- Rank 2: Silver Officer -->
            <Card
                class="order-2 md:order-1 p-3.5 sm:p-5 relative hover:border-input transition-colors gap-0"
            >
                <Badge
                    variant="secondary"
                    class="absolute -top-3 left-4 sm:left-5 font-mono font-bold text-xs uppercase gap-1"
                >
                    <Medal class="size-3 shrink-0 text-warning" />
                    <span>RANK #2 SILVER</span>
                </Badge>

                <div
                    class="flex items-center gap-3 sm:gap-3.5 mb-3 sm:mb-4 mt-1"
                >
                    <OfficerAvatar
                        :src="topStreamers[1].avatar_url"
                        :name="topStreamers[1].officer_name"
                        :live="topStreamers[1].is_live"
                        size="lg"
                    />
                    <div class="min-w-0 flex-1">
                        <h3
                            class="font-bold text-xs sm:text-sm text-foreground truncate"
                        >
                            {{ topStreamers[1].officer_name }}
                        </h3>
                        <p
                            class="text-xs text-muted-foreground font-mono truncate"
                        >
                            {{ topStreamers[1].callsign }}
                        </p>
                        <div class="mt-1">
                            <LiveBadge
                                v-if="topStreamers[1].is_live"
                                label="10-8 LIVE STREAMING"
                            />
                            <Badge
                                v-else
                                variant="outline"
                                class="font-mono text-xs"
                            >
                                10-7 STANDBY
                            </Badge>
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-1.5 sm:space-y-2 py-2.5 sm:py-3 border-y border-border"
                >
                    <div class="flex justify-between items-baseline font-mono">
                        <span class="text-xs text-muted-foreground"
                            >Total Durasi Patroli:</span
                        >
                        <strong
                            class="text-xs sm:text-sm font-semibold text-foreground font-mono"
                        >
                            {{ topStreamers[1].total_hours }}
                        </strong>
                    </div>
                    <div
                        class="w-full bg-background h-1.5 sm:h-2 rounded-full overflow-hidden border border-border hidden sm:block"
                    >
                        <div
                            class="bg-primary h-full rounded-full"
                            :style="{ width: topStreamers[1].percent + '%' }"
                        ></div>
                    </div>
                    <div
                        class="text-xs text-muted-foreground flex justify-between font-mono"
                    >
                        <span>Rata-rata: {{ topStreamers[1].avg_shift }}</span>
                        <span class="text-primary"
                            >{{ topStreamers[1].percent }}% dari #1</span
                        >
                    </div>
                </div>

                <div class="mt-3 sm:mt-4 flex gap-2">
                    <Button
                        type="button"
                        :variant="
                            topStreamers[1].is_live ? 'destructive' : 'secondary'
                        "
                        size="sm"
                        class="flex-1 text-xs font-medium gap-1.5"
                        :title="
                            topStreamers[1].is_live
                                ? 'Tonton Siaran Live'
                                : 'Kunjungi Kanal YouTube'
                        "
                        @click="openChannel(topStreamers[1].youtube_url)"
                    >
                        <Play class="size-3.5 fill-current shrink-0" />
                        <span>{{
                            topStreamers[1].is_live
                                ? "Tonton Siaran"
                                : "Kanal YouTube"
                        }}</span>
                    </Button>
                </div>
            </Card>

            <!-- Rank 1: Gold Champion Officer -->
            <Card
                class="order-1 md:order-2 p-4 sm:p-5 border-warning/40 relative hover:border-warning transition-colors gap-0"
            >
                <Badge
                    variant="warning"
                    class="absolute -top-3 left-1/2 -translate-x-1/2 font-mono font-bold text-xs uppercase gap-1.5 whitespace-nowrap"
                >
                    <Crown class="size-3.5 shrink-0" />
                    <span>#1 CHIEF OF ON-AIR</span>
                </Badge>

                <div
                    class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-0 mt-1 mb-3 sm:mb-4"
                >
                    <div class="relative sm:mb-2 shrink-0">
                        <OfficerAvatar
                            :src="topStreamers[0].avatar_url"
                            :name="topStreamers[0].officer_name"
                            :live="topStreamers[0].is_live"
                            size="lg"
                        />
                        <Badge
                            variant="warning"
                            class="absolute -bottom-1 -right-1 px-1.5 py-0 text-xs font-bold font-mono"
                        >
                            EMAS
                        </Badge>
                    </div>
                    <div class="min-w-0 flex-1 sm:flex-initial">
                        <h3
                            class="font-bold text-sm sm:text-base text-foreground truncate"
                        >
                            {{ topStreamers[0].officer_name }}
                        </h3>
                        <p
                            class="text-xs text-muted-foreground font-mono truncate"
                        >
                            {{ topStreamers[0].callsign }}
                        </p>
                        <div class="mt-1 sm:mt-2">
                            <LiveBadge
                                v-if="topStreamers[0].is_live"
                                label="10-8 LIVE PATROLI"
                                :viewers="topStreamers[0].live_viewers"
                            />
                            <Badge
                                v-else
                                variant="outline"
                                class="font-mono text-xs"
                            >
                                10-7 STANDBY
                            </Badge>
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-1.5 sm:space-y-2 py-2.5 sm:py-3 border-y border-border"
                >
                    <div class="flex justify-between items-baseline font-mono">
                        <span class="text-xs text-muted-foreground"
                            >Rekor Jam Siaran:</span
                        >
                        <strong
                            class="text-xs sm:text-sm font-semibold text-warning font-mono"
                        >
                            {{ topStreamers[0].total_hours }}
                        </strong>
                    </div>
                    <div
                        class="w-full bg-background h-1.5 sm:h-2 rounded-full overflow-hidden border border-border hidden sm:block"
                    >
                        <div
                            class="bg-primary h-full rounded-full w-full"
                        ></div>
                    </div>
                    <div
                        class="text-xs text-muted-foreground flex justify-between font-mono"
                    >
                        <span>Rata-rata: {{ topStreamers[0].avg_shift }}</span>
                        <span class="text-primary font-bold">100% Puncak</span>
                    </div>
                </div>

                <div class="mt-3 sm:mt-4 flex gap-2">
                    <Button
                        type="button"
                        :variant="
                            topStreamers[0].is_live ? 'destructive' : 'secondary'
                        "
                        size="sm"
                        class="flex-1 text-xs font-medium gap-1.5"
                        :title="
                            topStreamers[0].is_live
                                ? 'Tonton Siaran Juara'
                                : 'Kunjungi Kanal YouTube'
                        "
                        @click="openChannel(topStreamers[0].youtube_url)"
                    >
                        <Play class="size-3.5 fill-current shrink-0" />
                        <span>{{
                            topStreamers[0].is_live
                                ? "Tonton Siaran"
                                : "Kanal YouTube"
                        }}</span>
                    </Button>
                </div>
            </Card>

            <!-- Rank 3: Bronze Officer -->
            <Card
                class="order-3 p-3.5 sm:p-5 relative hover:border-input transition-colors gap-0"
            >
                <Badge
                    variant="warning"
                    class="absolute -top-3 left-4 sm:left-5 font-mono font-bold text-xs uppercase gap-1"
                >
                    <Medal class="size-3 shrink-0" />
                    <span>RANK #3 BRONZE</span>
                </Badge>

                <div
                    class="flex items-center gap-3 sm:gap-3.5 mb-3 sm:mb-4 mt-1"
                >
                    <OfficerAvatar
                        :src="topStreamers[2].avatar_url"
                        :name="topStreamers[2].officer_name"
                        :live="topStreamers[2].is_live"
                        size="lg"
                    />
                    <div class="min-w-0 flex-1">
                        <h3
                            class="font-bold text-xs sm:text-sm text-foreground truncate"
                        >
                            {{ topStreamers[2].officer_name }}
                        </h3>
                        <p
                            class="text-xs text-muted-foreground font-mono truncate"
                        >
                            {{ topStreamers[2].callsign }}
                        </p>
                        <div class="mt-1">
                            <LiveBadge
                                v-if="topStreamers[2].is_live"
                                label="10-8 LIVE STREAMING"
                            />
                            <Badge
                                v-else
                                variant="outline"
                                class="font-mono text-xs"
                            >
                                10-7 STANDBY
                            </Badge>
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-1.5 sm:space-y-2 py-2.5 sm:py-3 border-y border-border"
                >
                    <div class="flex justify-between items-baseline font-mono">
                        <span class="text-xs text-muted-foreground"
                            >Total Durasi Patroli:</span
                        >
                        <strong
                            class="text-xs sm:text-sm font-semibold text-foreground font-mono"
                        >
                            {{ topStreamers[2].total_hours }}
                        </strong>
                    </div>
                    <div
                        class="w-full bg-background h-1.5 sm:h-2 rounded-full overflow-hidden border border-border hidden sm:block"
                    >
                        <div
                            class="bg-primary h-full rounded-full"
                            :style="{ width: topStreamers[2].percent + '%' }"
                        ></div>
                    </div>
                    <div
                        class="text-xs text-muted-foreground flex justify-between font-mono"
                    >
                        <span>Rata-rata: {{ topStreamers[2].avg_shift }}</span>
                        <span class="text-primary"
                            >{{ topStreamers[2].percent }}% dari #1</span
                        >
                    </div>
                </div>

                <div class="mt-3 sm:mt-4 flex gap-2">
                    <Button
                        type="button"
                        :variant="
                            topStreamers[2].is_live ? 'destructive' : 'secondary'
                        "
                        size="sm"
                        class="flex-1 text-xs font-medium gap-1.5"
                        :title="
                            topStreamers[2].is_live
                                ? 'Tonton Siaran Live'
                                : 'Kunjungi Kanal YouTube'
                        "
                        @click="openChannel(topStreamers[2].youtube_url)"
                    >
                        <Play class="size-3.5 fill-current shrink-0" />
                        <span>{{
                            topStreamers[2].is_live
                                ? "Tonton Siaran"
                                : "Kanal YouTube"
                        }}</span>
                    </Button>
                </div>
            </Card>
        </div>

        <!-- Ranks 4 - 6: Desktop Table & Mobile Card List -->
        <Card class="hidden md:block p-0 overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-20 font-mono text-xs">
                            PERINGKAT
                        </TableHead>
                        <TableHead class="font-mono text-xs">PERWIRA</TableHead>
                        <TableHead class="font-mono text-xs">
                            DIVISI & DURASI
                        </TableHead>
                        <TableHead class="text-right font-mono text-xs">
                            KANAL
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in ranksFourToSix" :key="item.rank">
                        <TableCell
                            class="font-mono font-bold text-muted-foreground text-xs"
                        >
                            #{{ item.rank }}
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2.5">
                                <OfficerAvatar
                                    :src="item.avatar_url"
                                    :name="item.officer_name"
                                    size="sm"
                                />
                                <span
                                    class="text-xs font-semibold text-foreground"
                                >
                                    {{ item.officer_name }}
                                </span>
                            </div>
                        </TableCell>
                        <TableCell
                            class="text-xs text-muted-foreground font-mono"
                        >
                            {{ item.role }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-7 px-2.5 text-xs gap-1.5"
                                title="Buka Channel YouTube"
                                @click="openChannel(item.youtube_url)"
                            >
                                <Play class="size-3 fill-current" />
                                <span>YouTube</span>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>

        <!-- Mobile Card List (md:hidden) -->
        <div class="grid grid-cols-1 gap-2.5 md:hidden pt-1">
            <Card
                v-for="item in ranksFourToSix"
                :key="item.rank"
                class="p-2.5 flex-row items-center justify-between gap-2.5 hover:border-input transition-colors"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span
                        class="text-xs font-mono font-bold text-muted-foreground w-5 shrink-0"
                    >
                        #{{ item.rank }}
                    </span>
                    <OfficerAvatar
                        :src="item.avatar_url"
                        :name="item.officer_name"
                        size="sm"
                    />
                    <div class="min-w-0">
                        <h4
                            class="text-xs font-semibold text-foreground truncate"
                        >
                            {{ item.officer_name }}
                        </h4>
                        <p
                            class="text-xs text-muted-foreground font-mono truncate"
                        >
                            {{ item.role }}
                        </p>
                    </div>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-8 shrink-0"
                    aria-label="Buka Channel YouTube"
                    title="Buka Channel YouTube"
                    @click="openChannel(item.youtube_url)"
                >
                    <Play class="size-3.5 fill-current" />
                </Button>
            </Card>
        </div>

        <!-- Additional Leaderboard Info & Cycle Reset -->
        <Card
            class="mt-4 sm:mt-6 p-3.5 sm:p-4 flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 text-xs font-sans"
        >
            <div class="flex items-start gap-2.5 sm:gap-3 max-w-3xl">
                <Info class="size-4 text-primary shrink-0 mt-0.5" />
                <p class="text-muted-foreground leading-relaxed font-normal">
                    {{ displaySubtitle }}
                </p>
            </div>

            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-background border border-border text-foreground/80 font-mono text-xs shrink-0 w-full sm:w-auto justify-center"
            >
                <Clock class="size-3.5 text-info" />
                <span>{{ resetCycleText }}</span>
            </div>
        </Card>
    </section>
</template>
