<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
    DeptBadge,
    EmptyState,
    LiveBadge,
    OfficerAvatar,
    SectionHeader,
} from "@/Components/tactical";
import { AspectRatio } from "@/Components/ui/aspect-ratio";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { parseRelativeTimeToSeconds } from "@/Utils/timeHelpers";
import {
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    Radio,
    RefreshCw,
} from "@lucide/vue";

const props = defineProps({
    allActiveStreams: { type: Array, default: () => [] },
    trendingStreams: { type: Array, default: () => [] },
    recentReplayStreams: { type: Array, default: () => [] },
    isSyncingFeeds: { type: Boolean, default: false },
    tacChannels: { type: Array, default: () => [] },
    totalLiveViewers: { type: Number, default: 0 },
    totalOfficers: { type: Number, default: 0 },
});

const emit = defineEmits(["play-stream-in-focus", "trigger-manual-sync"]);

// Filter tabs state: 'ALL' | 'LIVE' | 'REPLAY'
const activeFilter = ref("ALL");

const combinedStreams = computed(() => {
    const liveItems = (props.allActiveStreams || []).map((s) => ({
        ...s,
        is_live_item: true,
    }));
    const replayItems = [...(props.recentReplayStreams || [])]
        .sort((a, b) => {
            const timeA = parseRelativeTimeToSeconds(
                a.streamed_at || a.incident_code,
            );
            const timeB = parseRelativeTimeToSeconds(
                b.streamed_at || b.incident_code,
            );
            return timeA - timeB;
        })
        .map((s) => ({
            ...s,
            is_live_item: false,
        }));

    if (activeFilter.value === "LIVE") return liveItems;
    if (activeFilter.value === "REPLAY") return replayItems;

    return [...liveItems, ...replayItems];
});

const liveCount = computed(() => (props.allActiveStreams || []).length);
const replayCount = computed(() => (props.recentReplayStreams || []).length);

const handleStreamCardClick = (stream) => {
    emit("play-stream-in-focus", stream);
    if (!stream) return;

    if (
        stream.is_officer_card ||
        (typeof stream.video_id === "string" &&
            stream.video_id.startsWith("officer-"))
    ) {
        const handle = stream.officer?.handle
            ? stream.officer.handle.replace(/^@/, "")
            : "";
        if (handle) {
            window.open(`https://www.youtube.com/@${handle}`, "_blank");
        }
        return;
    }

    if (stream.video_id) {
        const dept = stream.officer?.department || "ALL";
        router.visit(
            `/multiview?focus=${encodeURIComponent(stream.video_id)}&dept=${encodeURIComponent(dept)}`,
        );
    }
};

const scrollRow = (rowId, direction = "right") => {
    const el = document.getElementById(rowId);
    if (!el) return;
    const scrollAmount = direction === "left" ? -650 : 650;
    el.scrollBy({ left: scrollAmount, behavior: "smooth" });
};

const formatRelativeTimeLabel = (dateStr) => {
    if (!dateStr) return "REPLAY";
    return String(dateStr);
};

const getStreamYoutubeUrl = (stream) => {
    if (!stream) return "#";
    if (
        stream.is_officer_card ||
        (typeof stream.video_id === "string" &&
            stream.video_id.startsWith("officer-"))
    ) {
        const handle = stream.officer?.handle
            ? stream.officer.handle.replace(/^@/, "")
            : "";
        return handle ? `https://www.youtube.com/@${handle}` : "#";
    }
    return `https://www.youtube.com/watch?v=${stream.video_id}`;
};

const getStreamTac = (videoId) => {
    if (!videoId) return null;
    const targetId = String(videoId).trim();
    for (const channel of props.tacChannels || []) {
        if (
            channel.video_ids &&
            channel.video_ids.map(String).includes(targetId)
        ) {
            return channel.name || channel.code;
        }
    }
    return null;
};
</script>

<template>
    <div class="w-full space-y-4 font-sans">
        <!-- Section Header Bar -->
        <div class="pb-3.5 border-b border-border">
            <SectionHeader
                :title="$t('stream_slimles.dispatch_feed_title')"
                :description="$t('stream_slimles.dispatch_feed_subtitle')"
                :count="liveCount + replayCount"
            >
                <template #actions>
                    <div class="hidden md:flex flex-wrap items-center gap-2">
                        <!-- Live Telemetry Status Badge -->
                        <div
                            v-if="totalLiveViewers > 0"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-live/10 text-live border border-live/25 font-mono text-xs font-semibold"
                        >
                            <span class="size-1.5 rounded-full bg-live motion-safe:animate-ping" />
                            <span>{{ Number(totalLiveViewers).toLocaleString() }} Watching</span>
                        </div>

                        <!-- Filter Pills -->
                        <div
                            class="inline-flex p-1 rounded-md bg-card border border-border text-xs font-mono gap-1"
                        >
                            <Button
                                type="button"
                                :variant="
                                    activeFilter === 'ALL' ? 'default' : 'ghost'
                                "
                                size="sm"
                                class="h-7 px-3 text-xs font-medium"
                                @click="activeFilter = 'ALL'"
                            >
                                {{
                                    $t("stream_slimles.all_filter", {
                                        count: liveCount + replayCount,
                                    })
                                }}
                            </Button>
                            <Button
                                type="button"
                                :variant="
                                    activeFilter === 'LIVE'
                                        ? 'destructive'
                                        : 'ghost'
                                "
                                size="sm"
                                class="h-7 px-3 text-xs font-medium"
                                @click="activeFilter = 'LIVE'"
                            >
                                {{
                                    $t("stream_slimles.live_filter", {
                                        count: liveCount,
                                    })
                                }}
                            </Button>
                            <Button
                                type="button"
                                :variant="
                                    activeFilter === 'REPLAY'
                                        ? 'secondary'
                                        : 'ghost'
                                "
                                size="sm"
                                class="h-7 px-3 text-xs font-medium"
                                @click="activeFilter = 'REPLAY'"
                            >
                                {{
                                    $t("stream_slimles.replay_filter", {
                                        count: replayCount,
                                    })
                                }}
                            </Button>
                        </div>

                        <!-- Sync Live Button -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="isSyncingFeeds"
                            class="text-xs font-mono gap-2"
                            @click="emit('trigger-manual-sync')"
                        >
                            <RefreshCw
                                class="size-3.5"
                                :class="{ 'animate-spin': isSyncingFeeds }"
                            />
                            <span>{{
                                isSyncingFeeds ? $t("common.syncing") : "Sync"
                            }}</span>
                        </Button>
                    </div>
                </template>
            </SectionHeader>
        </div>

        <!-- SINGLE UNIFIED HORIZONTAL SLIDER ROW -->
        <div class="space-y-3">
            <!-- Row Slider Controls & Counter -->
            <div class="flex items-center justify-between px-0.5">
                <span
                    class="text-xs font-mono text-muted-foreground uppercase tracking-wider"
                >
                    {{
                        $t("stream_slimles.showing_count", {
                            count: combinedStreams.length,
                        })
                    }}
                </span>

                <div
                    v-if="combinedStreams.length > 0"
                    class="flex items-center space-x-1 shrink-0"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        class="size-7"
                        aria-label="Geser Kiri"
                        title="Geser Kiri"
                        @click="scrollRow('row-unified-dispatch', 'left')"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        class="size-7"
                        aria-label="Geser Kanan"
                        title="Geser Kanan"
                        @click="scrollRow('row-unified-dispatch', 'right')"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="combinedStreams.length === 0"
                :icon="Radio"
                :title="$t('stream_slimles.empty_title')"
                :description="$t('stream_slimles.empty_subtitle')"
            />

            <!-- UNIFIED SINGLE ROW SLIDER -->
            <div
                v-else
                id="row-unified-dispatch"
                class="flex gap-4 overflow-x-auto scroll-smooth snap-x scrollbar-none py-2 focus:outline-hidden"
            >
                <Card
                    v-for="stream in combinedStreams"
                    :key="`dispatch-${stream.video_id}`"
                    class="w-[280px] sm:w-[320px] md:w-[350px] shrink-0 snap-start group flex flex-col cursor-pointer py-0 gap-0 overflow-hidden border-border hover:border-input hover:-translate-y-1 hover:shadow-lg transition-all duration-300"
                    @click="handleStreamCardClick(stream)"
                >
                    <AspectRatio
                        :ratio="16 / 9"
                        class="bg-card relative overflow-hidden"
                    >
                        <img
                            :src="
                                stream.thumbnail ||
                                `https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`
                            "
                            :alt="
                                stream.officer?.officer_name ||
                                stream.title ||
                                'Officer Stream'
                            "
                            class="absolute inset-0 w-full h-full object-cover pointer-events-none"
                            loading="lazy"
                        />
                        <div
                            class="absolute inset-0 bg-linear-to-t from-background via-background/40 to-background/60 pointer-events-none"
                        ></div>

                        <div
                            class="relative z-10 h-full flex flex-col justify-between p-3"
                        >
                            <!-- Card Top Badges -->
                            <div
                                class="flex items-center justify-between gap-1.5 pointer-events-auto"
                            >
                                <!-- Live vs Replay Badge -->
                                <LiveBadge
                                    v-if="
                                        stream.status === 'LIVE' ||
                                        stream.is_live_item
                                    "
                                    :viewers="stream.viewers_count"
                                />
                                <Badge
                                    v-else
                                    variant="secondary"
                                    class="font-mono text-xs tracking-wider"
                                >
                                    {{
                                        formatRelativeTimeLabel(
                                            stream.streamed_at,
                                        )
                                    }}
                                </Badge>

                                <!-- TAC & Department Badges -->
                                <div class="flex items-center gap-1">
                                    <Badge
                                        v-if="getStreamTac(stream.video_id)"
                                        variant="warning"
                                        class="font-mono font-bold text-xs"
                                    >
                                        {{
                                            getStreamTac(
                                                stream.video_id,
                                            ).replace("_", " ")
                                        }}
                                    </Badge>
                                    <DeptBadge
                                        :dept="
                                            stream.officer?.department || 'SASP'
                                        "
                                    />
                                </div>
                            </div>

                            <!-- Card Bottom Officer Name & YouTube Button -->
                            <div
                                class="flex items-center justify-between gap-2 pointer-events-auto"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <OfficerAvatar
                                        :src="stream.officer?.avatar_url"
                                        :name="
                                            stream.officer?.officer_name ||
                                            'Officer'
                                        "
                                        :live="
                                            stream.status === 'LIVE' ||
                                            stream.is_live_item
                                        "
                                        size="sm"
                                        class="size-6 shrink-0"
                                    />
                                    <span
                                        class="text-xs font-semibold text-foreground truncate"
                                    >
                                        {{
                                            stream.officer?.officer_name ||
                                            "Officer"
                                        }}
                                    </span>
                                </div>
                                <Button
                                    as-child
                                    variant="secondary"
                                    size="icon"
                                    class="size-7 shrink-0"
                                    aria-label="Buka di YouTube"
                                    title="Buka di YouTube"
                                    @click.stop
                                >
                                    <a
                                        :href="getStreamYoutubeUrl(stream)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <ExternalLink class="size-3.5" />
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </AspectRatio>
                </Card>
            </div>
        </div>
    </div>
</template>
