<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    DeptBadge,
    LiveBadge,
    OfficerAvatar,
    PageContainer,
    TacticalFrame,
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
</script>

<template>
    <TacticalFrame
        variant="hero"
        :accent="resolvedAccent"
        class="w-full"
        inner-class="hud-corners relative overflow-hidden"
    >
        <!-- /* hero height token */ -->
        <div class="relative h-[420px] sm:h-[460px] lg:h-[520px] w-full overflow-hidden bg-background">
            <!-- Background Picture -->
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

            <!-- Readability Gradient Overlay -->
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-r from-background via-background/75 to-transparent"
            />
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-t from-background/90 via-transparent to-background/20"
            />

            <!-- Slide Content -->
            <PageContainer class="relative z-20 flex h-full flex-col justify-center py-8 sm:py-12">
                <div class="max-w-2xl space-y-4">
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
                        class="font-heading text-4xl font-bold tracking-tight text-foreground lg:text-6xl line-clamp-2"
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
            </PageContainer>
        </div>
    </TacticalFrame>
</template>
