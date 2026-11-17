<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    DeptBadge,
    LiveBadge,
    OfficerAvatar,
} from "@/Components/tactical";
import { ExternalLink, MonitorPlay } from "@lucide/vue";

const props = defineProps({
    slide: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["cta-click"]);

const isLiveSpotlight = computed(
    () => props.slide.type === "live_spotlight" || props.slide.slide_type === "live_spotlight",
);

const resolvedAccent = computed(() => {
    const raw = props.slide.accent || (isLiveSpotlight.value ? "live" : "primary");
    if (raw === "danger") return "destructive";
    return raw;
});

const badgeVariant = computed(() => {
    switch (resolvedAccent.value) {
        case "success":
            return "success";
        case "warning":
            return "warning";
        case "info":
            return "info";
        case "live":
            return "live";
        case "destructive":
            return "destructive";
        default:
            return "default";
    }
});

const isInternalUrl = (url) => typeof url === "string" && url.startsWith("/") && !url.startsWith("//");
const isHashUrl = (url) => typeof url === "string" && url.startsWith("#");
</script>

<template>
    <div class="relative h-[480px] sm:h-[540px] lg:h-[620px] w-full overflow-hidden bg-background">
        <!-- Full-Width Background Picture -->
        <picture v-if="slide.image_desktop_url" class="pointer-events-none absolute inset-0 z-0 block size-full">
            <source
                v-if="slide.image_mobile_url"
                media="(max-width: 640px)"
                :srcset="slide.image_mobile_url"
            />
            <img
                :src="slide.image_desktop_url"
                :alt="slide.image_alt || slide.title || 'Sorotan Komando'"
                :loading="index === 0 ? 'eager' : 'lazy'"
                :fetchpriority="index === 0 ? 'high' : 'auto'"
                class="size-full object-cover object-center"
            />
        </picture>

        <!-- Vignette: just enough shadow for text in the lower-left,
             image stays visible to the right and up top. -->
        <div
            class="pointer-events-none absolute inset-0 z-10 bg-linear-to-r from-background from-0% via-background/45 via-45% to-transparent to-75%"
        />
        <div
            class="pointer-events-none absolute inset-0 z-10 bg-linear-to-t from-background/85 from-0% via-background/15 via-45% to-transparent to-70%"
        />

        <!-- Slide content: same left gutter as the catalog rows below. -->
        <!-- Text sits in the lower-left like a billboard, leaving the image to carry the frame. -->
        <div class="relative z-20 flex h-full flex-col justify-end px-3 pb-16 pt-24 sm:px-6 sm:pb-20 lg:px-12 lg:pb-24">
            <div class="hero-copy max-w-xl space-y-4">
                    <!-- Top Badges / Live Spotlight Header -->
                    <div v-if="isLiveSpotlight" class="flex flex-wrap items-center gap-3">
                        <OfficerAvatar
                            :src="slide.live?.avatar_url"
                            :name="slide.live?.officer_name || slide.title"
                            :live="true"
                            size="md"
                        />
                        <div class="flex flex-wrap items-center gap-2">
                            <LiveBadge :viewers="slide.live?.viewers_count" />
                            <DeptBadge v-if="slide.live?.department" :dept="slide.live.department" />
                            <Badge
                                v-if="slide.badge_label"
                                variant="secondary"
                                class="font-mono text-xs uppercase tracking-wider"
                            >
                                {{ slide.badge_label }}
                            </Badge>
                        </div>
                    </div>
                    <div v-else-if="slide.badge_label" class="flex items-center gap-2">
                        <Badge :variant="badgeVariant" class="font-mono text-xs uppercase tracking-wider">
                            {{ slide.badge_label }}
                        </Badge>
                    </div>

                    <!-- Heading & Subtitle -->
                    <h2
                        class="font-heading text-4xl font-bold tracking-tight text-foreground lg:text-5xl line-clamp-2"
                    >
                        {{ slide.title || "SASP COMMAND CENTER" }}
                    </h2>

                    <p
                        v-if="slide.subtitle"
                        class="max-w-xl text-sm sm:text-base text-muted-foreground line-clamp-3"
                    >
                        {{ slide.subtitle }}
                    </p>

                    <!-- CTA Buttons -->
                    <div
                        v-if="
                            (slide.cta_primary?.label && slide.cta_primary?.url) ||
                            (slide.cta_secondary?.label && slide.cta_secondary?.url)
                        "
                        class="flex flex-wrap items-center gap-3 pt-2"
                    >
                        <Button
                            v-if="slide.cta_primary?.label && slide.cta_primary?.url"
                            as-child
                            size="lg"
                            @click="emit('cta-click', 'primary')"
                        >
                            <Link
                                v-if="isInternalUrl(slide.cta_primary.url)"
                                :href="slide.cta_primary.url"
                            >
                                <MonitorPlay class="size-4" />
                                <span>{{ slide.cta_primary.label }}</span>
                            </Link>
                            <a
                                v-else-if="isHashUrl(slide.cta_primary.url)"
                                :href="slide.cta_primary.url"
                            >
                                <MonitorPlay class="size-4" />
                                <span>{{ slide.cta_primary.label }}</span>
                            </a>
                            <a
                                v-else
                                :href="slide.cta_primary.url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>{{ slide.cta_primary.label }}</span>
                                <ExternalLink class="size-4" />
                            </a>
                        </Button>

                        <Button
                            v-if="slide.cta_secondary?.label && slide.cta_secondary?.url"
                            variant="outline"
                            as-child
                            size="lg"
                            class="bg-background/60 backdrop-blur-xs"
                            @click="emit('cta-click', 'secondary')"
                        >
                            <Link
                                v-if="isInternalUrl(slide.cta_secondary.url)"
                                :href="slide.cta_secondary.url"
                            >
                                <span>{{ slide.cta_secondary.label }}</span>
                            </Link>
                            <a
                                v-else-if="isHashUrl(slide.cta_secondary.url)"
                                :href="slide.cta_secondary.url"
                            >
                                <span>{{ slide.cta_secondary.label }}</span>
                            </a>
                            <a
                                v-else
                                :href="slide.cta_secondary.url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>{{ slide.cta_secondary.label }}</span>
                                <ExternalLink class="size-4" />
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
    </div>
</template>


<style scoped>
/* Cross-fade: the outgoing slide's text leaves quickly, the incoming text settles in after the image. */
.hero-copy {
    transition:
        opacity 0.5s ease 0.35s,
        transform 0.5s ease 0.35s;
}
[inert] .hero-copy {
    opacity: 0;
    transform: translateY(8px);
    transition-duration: 0.15s;
    transition-delay: 0s;
}
@media (prefers-reduced-motion: reduce) {
    .hero-copy,
    [inert] .hero-copy {
        transform: none;
        transition: opacity 0.2s ease;
    }
}
</style>
