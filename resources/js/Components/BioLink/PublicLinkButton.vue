<script setup>
import { ArrowUpRight, Coins } from "@lucide/vue";

import PlatformIcon from "@/Components/BioLink/PlatformIcon.vue";
import { cn } from "@/lib/utils";

// One public bio-link button: platform icon, name, arrow. Used by the public
// page and the dashboard preview (both through BioLinkView).
defineProps({
    link: { type: Object, required: true },
    // 'highlight' (support/donation, the theme's accent_2) or 'default' (theme accent).
    // Categories don't get their own hues, so any theme stays two-colour.
    tone: { type: String, default: "default" },
    // Officer-chosen button style from the bio-link settings.
    styleClass: { type: String, default: "" },
    // Drawn inside a TacticalFrame, which already provides the border.
    framed: { type: Boolean, default: false },
});

const toneIcon = {
    highlight: "bg-highlight/15 text-highlight",
    default: "bg-muted text-primary",
};
</script>

<template>
    <a
        :href="link.redirect_url"
        target="_blank"
        rel="noopener noreferrer nofollow ugc"
        :class="cn(
            'group flex min-h-14 w-full items-center gap-3 p-3 text-left outline-none transition-colors focus-visible:ring-3 focus-visible:ring-ring/50 active:scale-[0.99]',
            framed ? 'hover:bg-highlight/10' : cn('border', styleClass),
        )"
    >
        <span :class="cn('flex size-10 shrink-0 items-center justify-center rounded-lg', toneIcon[tone] || toneIcon.default)">
            <Coins v-if="!link.platform && link.link_type === 'donation'" class="size-5" aria-hidden="true" />
            <PlatformIcon v-else :platform="link.platform || 'custom'" class="size-5" />
        </span>

        <span class="line-clamp-2 min-w-0 flex-1 text-sm font-semibold leading-snug text-foreground">{{ link.title }}</span>

        <ArrowUpRight class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-foreground" />
    </a>
</template>
