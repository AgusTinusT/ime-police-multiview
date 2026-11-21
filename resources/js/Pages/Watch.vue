<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ExternalLink, LayoutGrid, MessageSquareOff, Scissors } from "@lucide/vue";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import WatchMomenDialog from "@/Components/WatchMomenDialog.vue";
import WatchMomenList from "@/Components/WatchMomenList.vue";
import WatchSideItem from "@/Components/WatchSideItem.vue";
import { useWatchPlayer } from "@/Composables/useWatchPlayer";
import { DeptBadge, LiveBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";

const props = defineProps({
    stream: { type: Object, required: true },
    liveStreams: { type: Array, default: () => [] },
    replays: { type: Array, default: () => [] },
    clips: { type: Array, default: () => [] },
    officerClips: { type: Array, default: () => [] },
    startAt: { type: Number, default: 0 },
    highlightClipId: { type: Number, default: null },
});

const REFRESH_MS = 60_000;

const { isOfficerDisabled } = useOfficerFilter();

const officer = computed(() => props.stream.officer || null);
const officerName = computed(
    () => officer.value?.officer_name || officer.value?.streamer_name || "Officer",
);

// enablejsapi lets the page read the playhead (Clip) and seek to Momen Aksi.
const playerSrc = computed(
    () =>
        `https://www.youtube.com/embed/${props.stream.video_id}?autoplay=1&playsinline=1&rel=0&modestbranding=1&enablejsapi=1` +
        (props.startAt ? `&start=${props.startAt}` : "") +
        `&origin=${encodeURIComponent(window.location.origin)}`,
);
const playerEl = ref(null);
const { getCurrentTime, seekTo } = useWatchPlayer(playerEl);

// Momen Aksi: local copy so a freshly saved clip shows up without a reload.
const momentClips = ref([...props.clips]);
watch(() => props.clips, (v) => {
    momentClips.value = [...v];
});
const activeClipId = ref(props.highlightClipId);
watch(() => props.highlightClipId, (v) => {
    activeClipId.value = v;
});

const playClip = (clip) => {
    activeClipId.value = clip.id;
    seekTo(clip.start_seconds);
    playerEl.value?.scrollIntoView?.({ block: "nearest", behavior: "smooth" });
};

const clipOpen = ref(false);
const markAt = ref(0);
const openClip = () => {
    markAt.value = getCurrentTime();
    clipOpen.value = true;
};
const onClipCreated = (clip) => {
    momentClips.value = [...momentClips.value, clip].sort((a, b) => a.start_seconds - b.start_seconds);
    activeClipId.value = clip.id;
};
// Description: collapse only when it would actually be clipped. Short but
// multi-line text ("Main game o3o\n\nBisa donasi sini\nhttps://...") used to lose
// its last lines to line-clamp-3 with no "Show more" button, since the toggle
// only looked at length.
const DESC_CLAMP_LINES = 3;
const descCollapsible = computed(() => {
    const text = props.stream.description || "";
    return text.length > 160 || text.split("\n").length > DESC_CLAMP_LINES;
});
// Plain-text links (donation pages, socials) become clickable; everything else stays text.
const URL_RE = /(https?:\/\/[^\s<>"']+)/g;
const descParts = computed(() =>
    (props.stream.description || "")
        .split(URL_RE)
        .filter((part) => part !== "")
        .map((part) => (/^https?:\/\//.test(part) ? { text: part, url: part } : { text: part, url: null })),
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
    mq =window.matchMedia("(min-width: 1024px)");
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
                <!-- Sticks just under the floating header (h-16 / sm:76px) on phones -->
                <div class="sticky top-16 z-20 bg-background sm:top-[76px] lg:static lg:overflow-hidden lg:rounded-xl">
                    <div class="aspect-video w-full">
                        <iframe
                            :key="stream.video_id"
                            ref="playerEl"
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
                            <Button size="sm" variant="secondary" class="rounded-full" @click="openClip">
                                <Scissors /> {{ $t('watch_clips.clip') }}
                            </Button>
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
                            :class="descCollapsible && !descOpen ? 'line-clamp-3' : ''"
                        ><template v-for="(part, i) in descParts" :key="i"><a
                            v-if="part.url"
                            :href="part.url"
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            class="text-primary hover:underline"
                        >{{ part.text }}</a><template v-else>{{ part.text }}</template></template></p>
                        <button
                            v-if="descCollapsible"
                            type="button"
                            class="mt-1 text-xs font-semibold text-foreground hover:underline"
                            @click="descOpen = !descOpen"
                        >
                            {{ descOpen ? $t('watch.show_less') : $t('watch.show_more') }}
                        </button>
                    </div>

                    <!-- Desktop: Momen Aksi under the description -->
                    <div v-if="isDesktop" class="mt-6 grid gap-6 xl:grid-cols-2">
                        <section>
                            <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch_clips.moments_here') }}</h2>
                            <WatchMomenList v-if="momentClips.length" :clips="momentClips" :active-id="activeClipId" @seek="playClip" />
                            <p v-else class="text-sm text-muted-foreground">{{ $t('watch_clips.empty') }}</p>
                        </section>
                        <section v-if="officerClips.length">
                            <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch_clips.moments_officer', { name: officerName }) }}</h2>
                            <WatchMomenList :clips="officerClips" :same-video="false" />
                        </section>
                    </div>
                </div>

                <!-- Phones & tablets: tabs under the player -->
                <Tabs v-if="!isDesktop" :default-value="highlightClipId || momentClips.length ? 'moments' : 'more'" class="mt-4 px-3 pb-24 sm:px-4">
                    <TabsList class="w-full">
                        <TabsTrigger value="more" class="flex-1">{{ $t('watch.tab_more') }}</TabsTrigger>
                        <TabsTrigger value="moments" class="flex-1">
                            {{ $t('watch_clips.tab_moments') }}<span v-if="momentClips.length" class="ml-1 text-muted-foreground">{{ momentClips.length }}</span>
                        </TabsTrigger>
                        <TabsTrigger value="chat" class="flex-1">{{ $t('watch.tab_chat') }}</TabsTrigger>
                    </TabsList>
                    <TabsContent value="moments" class="mt-3 space-y-5">
                        <section>
                            <WatchMomenList v-if="momentClips.length" :clips="momentClips" :active-id="activeClipId" @seek="playClip" />
                            <p v-else class="text-sm text-muted-foreground">{{ $t('watch_clips.empty') }}</p>
                        </section>
                        <section v-if="officerClips.length">
                            <h2 class="mb-2 text-sm font-bold text-foreground">{{ $t('watch_clips.moments_officer', { name: officerName }) }}</h2>
                            <WatchMomenList :clips="officerClips" :same-video="false" />
                        </section>
                    </TabsContent>
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

        <WatchMomenDialog
            v-model:open="clipOpen"
            :stream="stream"
            :mark-at="markAt"
            :get-current-time="getCurrentTime"
            :seek-to="seekTo"
            @created="onClipCreated"
        />
    </TacticalLayout>
</template>
