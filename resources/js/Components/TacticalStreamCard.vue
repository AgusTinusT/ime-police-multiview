<script setup>
import {
    DeptBadge,
    LiveBadge,
    OfficerAvatar,
} from "@/Components/tactical";
import { AspectRatio } from "@/Components/ui/aspect-ratio";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { cn } from "@/lib/utils";
import {
    Crosshair,
    ExternalLink,
    EyeOff,
    MessageSquare,
    MessageSquareOff,
    Pause,
    Play,
    Volume2,
    VolumeX,
    X,
} from "@lucide/vue";

defineProps({
    stream: { type: Object, required: true },
    activeAudioVideoId: { type: [String, Number], default: null },
    activeChatVideoId: { type: [String, Number], default: null },
    originUrl: { type: String, default: "" },
    customOrderRank: { type: Number, default: 0 },
    isVideoPlaying: { type: Boolean, default: false },
});

const emit = defineEmits([
    "toggle-audio",
    "toggle-stop-feed",
    "set-focus-stream",
    "handle-chat-click",
    "toggle-officer-status",
    "update:selectedLayout",
]);
</script>

<template>
    <Card
        :class="
            cn(
                'overflow-hidden border transition flex flex-col relative group py-0 gap-0 bg-background',
                customOrderRank > 0
                    ? 'border-warning/40'
                    : 'border-border hover:border-input',
                activeChatVideoId === stream.video_id ? 'border-warning/60' : '',
            )
        "
    >
        <!-- HEADER HUD -->
        <div
            class="bg-card px-3 py-1.5 flex items-center justify-between border-b border-border z-10"
        >
            <div class="flex items-center space-x-2 min-w-0">
                <DeptBadge :dept="stream.officer?.department || 'SASP'" />
                <OfficerAvatar
                    :src="stream.officer?.avatar_url"
                    :name="stream.officer?.officer_name"
                    size="sm"
                    class="size-5 shrink-0"
                />
                <span
                    class="text-xs font-semibold text-foreground block truncate"
                >
                    {{ stream.officer?.officer_name }}
                </span>
            </div>
            <div class="flex items-center space-x-1 shrink-0">
                <!-- Audio Switch Button -->
                <Button
                    type="button"
                    :variant="
                        activeAudioVideoId === stream.video_id
                            ? 'default'
                            : 'outline'
                    "
                    size="sm"
                    class="h-6 px-2 text-xs font-mono gap-1.5"
                    :title="
                        activeAudioVideoId === stream.video_id
                            ? 'Mute Audio'
                            : 'Play Audio'
                    "
                    @click="emit('toggle-audio', stream.video_id)"
                >
                    <Volume2
                        v-if="activeAudioVideoId === stream.video_id"
                        class="size-3"
                    />
                    <VolumeX v-else class="size-3" />
                    <span>{{
                        activeAudioVideoId === stream.video_id
                            ? "Mute"
                            : "Audio"
                    }}</span>
                </Button>
            </div>
        </div>

        <!-- VIDEO DISPLAY: IFRAME OR POSTER THUMBNAIL -->
        <div class="relative w-full bg-background overflow-hidden flex-1 group/thumb">
            <AspectRatio :ratio="16 / 9">
                <template v-if="isVideoPlaying">
                    <iframe
                        :key="`grid-iframe-${stream.video_id}`"
                        :id="`yt-bodycam-${stream.video_id}`"
                        class="w-full h-full border-0 pointer-events-auto"
                        :src="`https://www.youtube.com/embed/${stream.video_id}?enablejsapi=1&autoplay=1&mute=1&controls=1&rel=0&playsinline=1&vq=small&origin=${originUrl}`"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="absolute top-2 left-2 h-6 px-2 text-xs font-mono z-20 gap-1"
                        title="Tutup Video / Stop Feed"
                        @click="emit('toggle-stop-feed', stream.video_id)"
                    >
                        <X class="size-3" />
                        <span>Stop Feed</span>
                    </Button>
                </template>
                <template v-else>
                    <img
                        :src="`https://i.ytimg.com/vi/${stream.video_id}/hqdefault.jpg`"
                        :alt="stream.title"
                        class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 transition duration-300 cursor-pointer"
                        loading="lazy"
                        @click="emit('set-focus-stream', stream.video_id)"
                    />
                    <div
                        class="absolute inset-0 bg-background/70 flex flex-col items-center justify-center p-3 pointer-events-none"
                    >
                        <div class="absolute top-2 left-2">
                            <LiveBadge :viewers="stream.viewers_count" />
                        </div>

                        <!-- Desktop Hover Overlay Controls (Hidden on Mobile) -->
                        <div
                            class="hidden sm:flex items-center space-x-2 opacity-0 group-hover/thumb:opacity-100 transition-opacity duration-200 pointer-events-auto"
                        >
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                                @click.stop="
                                    emit('toggle-stop-feed', stream.video_id)
                                "
                            >
                                <Pause v-if="isVideoPlaying" class="size-3.5" />
                                <Play v-else class="size-3.5" />
                                <span>{{
                                    isVideoPlaying ? "Stop" : "Play"
                                }}</span>
                            </Button>
                            <Button
                                type="button"
                                variant="default"
                                size="sm"
                                class="text-xs font-bold gap-1.5"
                                @click.stop="
                                    emit('set-focus-stream', stream.video_id)
                                "
                            >
                                <Crosshair class="size-3.5" />
                                <span>Focus</span>
                            </Button>
                        </div>

                        <!-- Mobile Direct Tap Focus Badge (< sm) -->
                        <Button
                            type="button"
                            size="sm"
                            class="sm:hidden rounded-full font-bold text-xs gap-1.5 pointer-events-auto motion-safe:animate-pulse"
                            @click.stop="
                                emit('set-focus-stream', stream.video_id)
                            "
                        >
                            <Crosshair class="size-3.5" />
                            <span>Ketuk untuk Fokus</span>
                        </Button>
                    </div>
                </template>

                <!-- Desktop Floating Hover Overlay Bar (Hidden on mobile) -->
                <div
                    class="hidden sm:flex absolute bottom-2 left-2 right-2 opacity-0 group-hover/thumb:opacity-100 transition-opacity duration-200 bg-card/95 px-2.5 py-1.5 rounded-md border border-border items-center justify-between z-20 pointer-events-auto text-xs text-foreground/80 gap-1.5"
                >
                    <div
                        class="flex items-center space-x-1.5 min-w-0 flex-1 truncate"
                    >
                        <Badge
                            v-if="stream.officer?.callsign"
                            variant="secondary"
                            class="font-mono text-xs shrink-0"
                        >
                            {{ stream.officer.callsign }}
                        </Badge>
                        <span
                            v-if="stream.officer?.rank"
                            class="font-mono text-xs text-foreground/80 truncate"
                        >
                            {{ stream.officer.rank }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-1 shrink-0">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-6 px-2 text-xs font-mono gap-1"
                            title="Sembunyikan perwira ini dari grid"
                            @click.stop="emit('toggle-officer-status', stream)"
                        >
                            <EyeOff class="size-3" />
                            <span>Sembunyikan</span>
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-6"
                            aria-label="Focus stream"
                            title="Focus"
                            @click="
                                emit('update:selectedLayout', 'focus');
                                emit('set-focus-stream', stream.video_id);
                            "
                        >
                            <Crosshair class="size-3.5" />
                        </Button>
                        <Button
                            type="button"
                            :variant="
                                activeChatVideoId === stream.video_id
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            size="icon"
                            class="size-6"
                            aria-label="Toggle Live Chat"
                            title="Live Chat"
                            @click="emit('handle-chat-click', stream.video_id)"
                        >
                            <MessageSquareOff
                                v-if="activeChatVideoId === stream.video_id"
                                class="size-3.5"
                            />
                            <MessageSquare v-else class="size-3.5" />
                        </Button>
                        <Button
                            as-child
                            variant="ghost"
                            size="icon"
                            class="size-6"
                            aria-label="Buka di YouTube"
                            title="Buka di YouTube"
                        >
                            <a
                                :href="`https://www.youtube.com/watch?v=${stream.video_id}`"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink class="size-3.5" />
                            </a>
                        </Button>
                    </div>
                </div>
            </AspectRatio>
        </div>

        <!-- PERMANENT MOBILE ACTION CARD FOOTER (sm:hidden) -->
        <div
            class="sm:hidden bg-card border-t border-border px-3 py-2 flex items-center justify-between gap-2 z-10"
        >
            <div class="flex items-center space-x-1.5 min-w-0 flex-1">
                <Badge
                    v-if="stream.officer?.callsign"
                    variant="secondary"
                    class="font-mono text-xs shrink-0"
                >
                    {{ stream.officer.callsign }}
                </Badge>
                <span class="font-mono text-xs text-foreground/80 truncate">
                    {{
                        stream.officer?.rank ||
                        stream.officer?.officer_name ||
                        stream.title
                    }}
                </span>
            </div>
            <div class="flex items-center space-x-1.5 shrink-0">
                <!-- Play / Stop Toggle Button -->
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    class="h-7 px-2.5 text-xs font-bold gap-1"
                    @click.stop="emit('toggle-stop-feed', stream.video_id)"
                >
                    <Pause v-if="isVideoPlaying" class="size-3" />
                    <Play v-else class="size-3" />
                    <span>{{ isVideoPlaying ? "Stop" : "Play" }}</span>
                </Button>

                <!-- Focus Button -->
                <Button
                    type="button"
                    size="sm"
                    class="h-7 px-2.5 text-xs font-bold gap-1"
                    @click.stop="emit('set-focus-stream', stream.video_id)"
                >
                    <Crosshair class="size-3.5" />
                    <span>Fokus</span>
                </Button>

                <!-- Chat Button -->
                <Button
                    type="button"
                    :variant="
                        activeChatVideoId === stream.video_id
                            ? 'default'
                            : 'outline'
                    "
                    size="icon"
                    class="size-7"
                    aria-label="Live Chat"
                    title="Live Chat"
                    @click.stop="emit('handle-chat-click', stream.video_id)"
                >
                    <MessageSquare class="size-3.5" />
                </Button>
            </div>
        </div>
    </Card>
</template>
