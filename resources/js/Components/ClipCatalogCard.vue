<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Heart } from "@lucide/vue";
import { Badge } from "@/Components/ui/badge";

const props = defineProps({
    clip: {
        type: Object,
        required: true,
    },
});

const durationText = computed(() => {
    const start = Number(props.clip.start_seconds) || 0;
    const end = Number(props.clip.end_seconds) || 0;
    const diff = Math.max(0, end - start);
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    return `${m}:${s.toString().padStart(2, "0")}`;
});

const thumbUrl = computed(() => {
    return `https://i.ytimg.com/vi/${props.clip.video_id}/mqdefault.jpg`;
});
</script>

<template>
    <Link
        href="/action-clips"
        class="catalog-card group relative block w-52 shrink-0 snap-start rounded-md outline-none focus-visible:ring-3 focus-visible:ring-ring/50 sm:w-64"
    >
        <div class="relative aspect-video overflow-hidden rounded-md bg-muted">
            <img
                :src="thumbUrl"
                :alt="clip.title"
                loading="lazy"
                class="size-full object-cover"
            />
            <Badge
                variant="secondary"
                class="absolute bottom-2 right-2 text-xs font-mono"
            >
                {{ durationText }}
            </Badge>
        </div>

        <div class="px-1 pt-2">
            <p class="line-clamp-1 text-sm font-semibold text-foreground">
                {{ clip.title }}
            </p>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground truncate">
                <span>{{ clip.officer_name || "Perwira" }}</span>
                <span>·</span>
                <span>oleh {{ clip.creator_name || "Komunitas" }}</span>
                <span>·</span>
                <span class="inline-flex items-center gap-0.5">
                    <Heart class="size-3 text-destructive fill-destructive" />
                    <span>{{ clip.likes_count || 0 }} suka</span>
                </span>
            </div>
        </div>
    </Link>
</template>

<style scoped>
.catalog-card {
    transition: transform 0.25s ease 0.15s;
}
.catalog-card img {
    transition: box-shadow 0.25s ease 0.15s;
}
@media (hover: hover) and (prefers-reduced-motion: no-preference) {
    .catalog-card:hover {
        z-index: 10;
        transform: scale(1.06);
    }
    .catalog-card:hover img {
        box-shadow: 0 12px 30px color-mix(in oklab, var(--color-background) 80%, transparent);
    }
}
.catalog-card:focus-visible {
    z-index: 10;
}
</style>

