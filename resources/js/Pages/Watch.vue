<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ExternalLink, LayoutGrid, MessageSquareOff } from "@lucide/vue";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import WatchSideItem from "@/Components/WatchSideItem.vue";
import { DeptBadge, LiveBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";

const props = defineProps({
    stream: { type: Object, required: true },
    liveStreams: { type: Array, default: () => [] },
    replays: { type: Array, default: () => [] },
});

const REFRESH_MS = 60_000;

const { isOfficerDisabled } = useOfficerFilter();

const officer = computed(() => props.stream.officer || null);
const officerName = computed(
    () => officer.value?.officer_name || officer.value?.streamer_name || "Officer",
);

const origin = ref("");
const playerSrc = computed(
    () =>
        `https://www.youtube.com/embed/${props.stream.video_id}?autoplay=1&playsinline=1&rel=0&modestbranding=1` +
        (origin.value ? `&origin=${encodeURIComponent(origin.value)}` : ""),
);
const chatSrc = computed(() => props.stream.live_chat_url || "");
const youtubeWatchUrl = computed(() => `https://www.youtube.com/watch?v=${props.stream.video_id}`);
const channelUrl = computed(() => {
    const o = officer.value;
    if (!o) return null;
    if (o.youtube_url) return o.youtube_url;
    const handle = (o.handle || "").trim();
    if (handle) return `https://www.youtube.com/${handle.startsWith("@") ? handle : `@${handle}`}`;
    if (o.channel_id && !o.channel_id.startsWith("UC_")) return `https://www.youtube.com/channel/${o.channel_id}`;
    return null;
});

const otherLive = computed(() => props.liveStreams.filter((s) => !isOfficerDisabled(s)));
const otherReplays = computed(() => props.replays.filter((r) => !isOfficerDisabled(r)));

const descOpen = ref(false);
watch(() => props.stream.video_id, () => {
    descOpen.value = false;
});

// Desktop shows chat + "up next" in a sidebar; phones get tabs under the player.
// Rendering only one of them keeps a single chat iframe on the page.
const isDesktop = ref(false);
let mq = null;
const syncMq = () => {
    isDesktop.value = !!mq?.matches;
};

let refreshTimer = null;

onMounted(() => {
    origin.value = window.location.origin;
    mq = window.matchMedia("(min-width: 1024px)");
    syncMq();
    mq.addEventListener("change", syncMq);
    // Keep the "more live" list fresh without touching the player.
    refreshTimer = setInterval(() => {
        if (document.visibilityState === "visible") {
            router.reload({ only: ["liveStreams"] });
        }
    }, REFRESH_MS);
});

onUnmounted(() => {
    mq?.removeEventListener("change", syncMq);
    clearInterval(refreshTimer);
});
</script>

<template>
    <Head :title="stream.title">
        <meta name="description" :content="`Tonton ${officerName} — ${stream.title}`" />
    </Head>

    <TacticalLayout :show-footer="false">
        <div class="mx-auto w-full max-w-[1800px] lg:grid lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-6 lg:px-6 lg:py-6">
            <!-- Primary column -->
            <div class="min-w-0">
                <div class="sticky top-0 z-20 bg-black lg:static lg:overflow-hidden lg:rounded-xl">
                    <div class="aspect-video w-full">
                        <iframe
                            :key="stream.video_id"
                            :src="playerSrc"
                            :title="stream.title"
                            class="size-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        />
                    </div>
                </div>

                <div class="px-3 pt-3 sm:px-4 lg:px-0">
                    <h1 class="line-clamp-2 text-lg font-bold leading-snug text-foreground sm:text-xl">
                        {{ stream.title }}
                    </h1>

                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <OfficerAvatar
                                :src="officer?.avatar_url"
                                :name="officerName"
                                :live="stream.is_live"
                            />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-foreground">{{ officerName }}</p>
                                <p class="flex items-center gap-1.5 truncate text-xs text-muted-foreground">
                                    <DeptBadge v-if="officer?.department" :dept="officer.department" />
                                    <span v-if="officer?.callsign" class="font-mono">{{ officer.callsign }}</span>
                                    <span v-if="officer?.subscriber_count_text">· {{ officer.subscriber_count_text }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button v-if="channelUrl" as-child size="sm" class="rounded-full">
                                <a :href="channelUrl" target="_blank" rel="noopener">{{ $t('watch.channel') }}</a>
                            </Button>
                            <Button as-child size="sm" variant="secondary" class="rounded-full">
                                <Link :href="`/multiview?v=${stream.video_id}`">
                                    <LayoutGrid /> {{ $t('watch.open_multiview') }}
                                </Link>
                            </Button>
                            <Button as-child size="sm" variant="secondary" class="rounded-full">
                                <a :href="youtubeWatchUrl" target="_blank" rel="noopener">
                                    <ExternalLink /> {{ $t('watch.open_youtube') }}
                                </a>
                            </Button>
                        </div>
                    </div>

                    <!-- Description box, YouTube style -->
                    <div class="mt-3 rounded-xl bg-muted/60 p-3 text-sm">
                        <div class="flex flex-wrap items-center gap-2 font-semibold text-foreground">
                            <template v-if="stream.is_live">
                                <LiveBadge />
                                <span>{{ $t('watch.watching', { count: stream.viewers_count || 0 }) }}</span>
                            </template>
                            <template v-else>
                                <Badge variant="secondary">{{ $t('watch.replay') }}</Badge>
                                <span v-if="stream.streamed_at" class="text-muted-foreground">{{ stream.streamed_at }}</span>
                            </template>
                            <span v-if="officer?.rank" class="text-muted-foreground">· {{ officer.rank }}</span>
                        </div>
                        <p
                            v-if="stream.description"
                            class="mt-2 whitespace-pre-line break-words text-muted-foreground"
                            :class="descOpen ? '' : 'line-clamp-3'"
                        >{{ stream.description }}</p>
                        <button
                            v-if="stream.description && stream.description.length > 160"
                            type="button"
                            class="mt-1 text-xs font-semibold text-foreground hover:underline"
                            @click="descOpen = !descOpen"
                        >
                            {{ descOpen ? $t('watch.show_less') : $t('watch.show_more') }}
                        </button>
                    </div>
                </div>

                <!-- Phones & tablets: tabs under the player -->
                <Tabs v-if="!isDesktop" default-value="more" class="mt-4 px-3 pb-24 sm:px-4">
                    <TabsList class="w-full">
                        <TabsTrigger value="more" class="flex-1">{{ $t('watch.tab_more') }}</TabsTrigger>
                        <TabsTrigger value="chat" class="flex-1">{{ $t('watch.tab_chat') }}</TabsTrigger>
                    </TabsList>
                    <TabsContent value="chat" class="mt-3">
                        <iframe
                            v-if="chatSrc"
                            :key="`chat-${stream.video_id}`"
                            :src="chatSrc"
                            :title="$t('watch.chat_title')"
                            class="h-[60dvh] w-full rounded-xl border border-border bg-card"
                        />
                        <div v-else class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                            <MessageSquareOff class="size-5" />
                            {{ $t('watch.chat_unavailable') }}
                        </div>
                    </TabsContent>
                    <TabsContent value="more" class="mt-3 space-y-5">
                        <section v-if="otherLive.length">
                            <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch.more_live') }}</h2>
                            <div class="space-y-1">
                                <WatchSideItem v-for="s in otherLive" :key="`m-live-${s.video_id}`" :stream="s" live />
                            </div>
                        </section>
                        <section v-if="otherReplays.length">
                            <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch.recent_replays') }}</h2>
                            <div class="space-y-1">
                                <WatchSideItem v-for="r in otherReplays" :key="`m-rep-${r.video_id}`" :stream="r" />
                            </div>
                        </section>
                        <p v-if="!otherLive.length && !otherReplays.length" class="text-sm text-muted-foreground">
                            {{ $t('watch.nothing_else') }}
                        </p>
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Desktop sidebar: chat on top, up next below -->
            <aside v-if="isDesktop" class="min-w-0 space-y-5">
                <div class="overflow-hidden rounded-xl border border-border bg-card">
                    <div class="border-b border-border px-3 py-2 text-sm font-semibold text-foreground">
                        {{ $t('watch.chat_title') }}
                    </div>
                    <iframe
                        v-if="chatSrc"
                        :key="`chat-${stream.video_id}`"
                        :src="chatSrc"
                        :title="$t('watch.chat_title')"
                        class="h-[520px] w-full"
                    />
                    <div v-else class="flex flex-col items-center gap-2 p-6 text-center text-sm text-muted-foreground">
                        <MessageSquareOff class="size-5" />
                        {{ $t('watch.chat_unavailable') }}
                    </div>
                </div>

                <section v-if="otherLive.length">
                    <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch.more_live') }}</h2>
                    <div class="space-y-1">
                        <WatchSideItem v-for="s in otherLive" :key="`d-live-${s.video_id}`" :stream="s" live />
                    </div>
                </section>
                <section v-if="otherReplays.length">
                    <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch.recent_replays') }}</h2>
                    <div class="space-y-1">
                        <WatchSideItem v-for="r in otherReplays" :key="`d-rep-${r.video_id}`" :stream="r" />
                    </div>
                </section>
                <p v-if="!otherLive.length && !otherReplays.length" class="text-sm text-muted-foreground">
                    {{ $t('watch.nothing_else') }}
                </p>
            </aside>
        </div>
    </TacticalLayout>
</template>
