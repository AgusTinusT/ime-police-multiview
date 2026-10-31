<script setup>
import { computed } from "vue";
import { ExternalLink } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
});

const backgroundStyle = computed(() => {
    if (!props.site?.background_image_url) {
        return {};
    }
    return {
        backgroundImage: `url('${props.site.background_image_url}')`,
        backgroundPosition: "right center",
        backgroundRepeat: "no-repeat",
        backgroundSize: "cover",
    };
});

const titlePrefix = computed(() => {
    if (!props.site?.name) return "";
    if (props.site.badge && props.site.name.includes(props.site.badge)) {
        return props.site.name.replace(props.site.badge, "").trim();
    }
    return props.site.name;
});

const hasBadgeInTitle = computed(() => {
    return Boolean(props.site?.badge);
});
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition-all"
        :style="backgroundStyle"
    >
        <!-- Overlay to guarantee contrast against background art -->
        <div class="absolute inset-0 bg-background/85 md:bg-gradient-to-r md:from-background md:via-background/90 md:to-background/60 pointer-events-none" />

        <!-- Card Content Body -->
        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-center gap-6 lg:gap-8 p-6 sm:p-8">
            <!-- Left: Poster Column -->
            <div class="w-full max-w-[240px] md:w-56 shrink-0 flex flex-col items-center">
                <div class="w-full h-80 rounded-xl border border-warning/40 bg-card/90 backdrop-blur-xs flex flex-col items-center justify-between p-4 text-center shadow-md">
                    <!-- Poster Top Header -->
                    <span class="text-xs font-mono font-bold text-warning uppercase tracking-widest">
                        ★ WEBSITE PILIHAN
                    </span>

                    <!-- Poster Center Graphic -->
                    <div class="relative size-32 rounded-full overflow-hidden border-2 border-warning/30 bg-muted/60 p-2 flex items-center justify-center">
                        <img
                            :src="site.poster_image_url"
                            :alt="site.poster_name || site.name"
                            class="w-full h-full object-contain"
                            loading="lazy"
                        />
                    </div>

                    <!-- Poster Bottom Meta -->
                    <div class="space-y-1 w-full">
                        <h4 class="font-heading font-extrabold text-xs sm:text-sm text-foreground uppercase tracking-wide leading-tight line-clamp-2">
                            {{ site.poster_name }}
                        </h4>
                        <p v-if="site.poster_subtitle" class="text-xs font-mono text-muted-foreground line-clamp-1">
                            {{ site.poster_subtitle }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: Details Column -->
            <div class="flex-1 min-w-0 space-y-4 text-center md:text-left">
                <!-- Chips Bar -->
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                    <Badge
                        v-if="site.is_featured"
                        variant="outline"
                        class="bg-warning/15 text-warning border-warning/40 font-mono text-xs font-bold tracking-wider uppercase"
                    >
                        ★ REKOMENDASI
                    </Badge>
                    <Badge
                        v-for="(chip, idx) in (site.chips || [])"
                        :key="idx"
                        variant="outline"
                        class="font-mono text-xs text-muted-foreground border-border bg-card/60 backdrop-blur-xs"
                    >
                        {{ chip }}
                    </Badge>
                </div>

                <!-- Big Title with Boxed Badge -->
                <h3 class="font-heading text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-foreground flex flex-wrap items-center justify-center md:justify-start gap-2.5">
                    <span v-if="titlePrefix">{{ titlePrefix }}</span>
                    <span
                        v-if="hasBadgeInTitle"
                        class="inline-block border-2 border-foreground px-3 py-0.5 rounded-sm uppercase tracking-wide text-2xl sm:text-3xl lg:text-4xl"
                    >
                        {{ site.badge }}
                    </span>
                </h3>

                <!-- Description -->
                <p class="text-sm sm:text-base text-muted-foreground max-w-xl leading-relaxed mx-auto md:mx-0">
                    {{ site.description }}
                </p>

                <!-- Action Button -->
                <div class="pt-2">
                    <Button
                        as-child
                        variant="destructive"
                        size="lg"
                        class="w-full sm:w-auto font-mono font-bold text-xs sm:text-sm tracking-wide gap-2 shadow-sm"
                    >
                        <a :href="site.url" target="_blank" rel="noopener noreferrer">
                            <span>{{ site.cta_text || "Kunjungi website" }}</span>
                            <ExternalLink class="size-4" />
                        </a>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

