<script setup>
import {
    ArrowUpRight,
    AtSign,
    Camera,
    Coffee,
    Coins,
    Gift,
    Link2,
    MessageCircle,
    Music2,
    PlaySquare,
    Tv,
    Zap,
} from "@lucide/vue";
import { computed } from "vue";

import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";

// One public bio-link button. Used by every link section on /@slug so they
// share layout: icon, title (up to two lines), badge + subtitle, arrow.
const props = defineProps({
    link: { type: Object, required: true },
    // 'warning' (support/donation), 'info' (community & social), 'muted' (other)
    tone: { type: String, default: "muted" },
    // Officer-chosen button style from the bio-link settings.
    styleClass: { type: String, default: "" },
    // Drawn inside a TacticalFrame, which already provides the border.
    framed: { type: Boolean, default: false },
});

const icon = computed(() => {
    const { link_type: type = "", title = "", url = "" } = props.link;
    const text = `${title} ${url}`.toLowerCase();
    if (type === "donation") {
        if (text.includes("trakteer")) return Coffee;
        if (text.includes("bagibagi")) return Gift;
        if (text.includes("sociabuzz")) return Zap;
        return Coins;
    }
    // Public pages only get a tracking redirect_url, so match on the title as well as the url.
    if (type === "discord" || text.includes("discord")) return MessageCircle;
    if (text.includes("youtube") || text.includes("youtu.be")) return PlaySquare;
    if (text.includes("tiktok")) return Music2;
    if (text.includes("instagram")) return Camera;
    if (text.includes("twitch")) return Tv;
    if (text.includes("twitter") || /\bx\.com\b/.test(text)) return AtSign;
    return Link2;
});

const toneIcon = {
    warning: "bg-warning/15 text-warning",
    info: "bg-info/15 text-info",
    muted: "bg-muted text-muted-foreground",
};
</script>

<template>
    <a
        :href="link.redirect_url"
        target="_blank"
        rel="noopener noreferrer nofollow ugc"
        :class="cn(
            'group flex min-h-14 w-full items-center gap-3 p-3 text-left outline-none transition-colors focus-visible:ring-3 focus-visible:ring-ring/50 active:scale-[0.99]',
            framed ? 'hover:bg-warning/10' : cn('border', styleClass),
            !framed && link.is_highlighted && 'border-primary/60 bg-primary/10',
        )"
    >
        <span :class="cn('flex size-10 shrink-0 items-center justify-center rounded-lg', toneIcon[tone] || toneIcon.muted)">
            <component :is="icon" class="size-5" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="line-clamp-2 text-sm font-semibold leading-snug text-foreground">{{ link.title }}</span>
            <span v-if="link.badge_label || link.subtitle" class="mt-0.5 flex min-w-0 items-center gap-1.5">
                <Badge v-if="link.badge_label" :variant="tone === 'warning' ? 'warning' : 'secondary'" class="shrink-0">
                    {{ link.badge_label }}
                </Badge>
                <span v-if="link.subtitle" class="truncate text-xs text-muted-foreground">{{ link.subtitle }}</span>
            </span>
        </span>

        <ArrowUpRight class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-foreground" />
    </a>
</template>
