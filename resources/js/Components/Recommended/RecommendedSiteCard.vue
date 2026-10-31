<script setup>
import { computed } from "vue";
import { ArrowUpRight } from "@lucide/vue";
import { Badge } from "@/Components/ui/badge";

const props = defineProps({
    site: { type: Object, required: true },
    // Flip poster/content order so stacked cards alternate (zigzag).
    reverse: { type: Boolean, default: false },
});

const backgroundStyle = computed(() => {
    if (!props.site?.background_image_url) return {};
    return {
        backgroundImage: `url('${props.site.background_image_url}')`,
        backgroundPosition: props.reverse ? "left center" : "right center",
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

const hasBadgeInTitle = computed(() => Boolean(props.site?.badge));
</script>

<template>
    <!-- Whole card is one click target going to the site. -->
    <a
        :href="site.url"
        target="_blank"
        rel="noopener noreferrer"
        :class="[
            'group relative block overflow-hidden rounded-2xl border border-border bg-card shadow-sm',
            'transition-all duration-300 hover:border-primary/50 hover:shadow-lg hover:shadow-primary/5 hover:-translate-y-0.5',
        ]"
        :style="backgroundStyle"
    >
        <!-- Scrim fades out the side the hero art is on so text keeps contrast,
             art stays visible. Flipped when `reverse` moves the poster right. -->
        <div
            :class="[
                'absolute inset-0 bg-background/55 pointer-events-none',
                reverse
                    ? 'md:bg-gradient-to-l md:from-background md:via-background/80 md:via-55% md:to-background/0'
                    : 'md:bg-gradient-to-r md:from-background md:via-background/80 md:via-55% md:to-background/0',
            ]"
        />

        <div
            :class="[
                'relative z-10 flex flex-col gap-5 p-5 sm:p-6 md:gap-7 md:p-8',
                reverse ? 'md:flex-row-reverse' : 'md:flex-row',
            ]"
        >
            <!-- Poster -->
            <div class="mx-auto w-full max-w-[200px] shrink-0 md:mx-0 md:w-44 lg:w-48">
                <div class="relative flex h-64 flex-col items-center justify-between overflow-hidden rounded-xl border border-warning/40 bg-card/80 p-3 text-center backdrop-blur-xs shadow-sm">
                    <span class="text-xs font-mono font-bold uppercase tracking-widest text-warning">
                        ★ Website Pilihan
                    </span>

                    <div class="relative size-24 overflow-hidden rounded-full border-2 border-warning/30 bg-muted/40 p-1.5">
                        <img
                            :src="site.poster_image_url"
                            :alt="site.poster_name || site.name"
                            class="size-full object-contain"
                            loading="lazy"
                        />
                    </div>

                    <div class="w-full space-y-0.5">
                        <h4 class="line-clamp-2 font-heading text-xs font-extrabold uppercase leading-tight tracking-wide text-foreground">
                            {{ site.poster_name }}
                        </h4>
                        <p v-if="site.poster_subtitle" class="line-clamp-1 font-mono text-xs text-muted-foreground">
                            {{ site.poster_subtitle }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div :class="['min-w-0 flex-1 space-y-3 text-center', reverse ? 'md:text-right' : 'md:text-left']">
                <!-- Chips -->
                <div :class="['flex flex-wrap items-center gap-1.5', reverse ? 'justify-center md:justify-end' : 'justify-center md:justify-start']">
                    <Badge
                        v-if="site.is_featured"
                        variant="outline"
                        class="border-warning/40 bg-warning/15 font-mono text-xs font-bold uppercase tracking-wider text-warning"
                    >★ Rekomendasi</Badge>
                    <Badge
                        v-for="(chip, idx) in site.chips || []"
                        :key="idx"
                        variant="outline"
                        class="border-border bg-card/60 font-mono text-xs text-muted-foreground backdrop-blur-xs"
                    >{{ chip }}</Badge>
                </div>

                <!-- Title with boxed badge -->
                <h3
                    :class="[
                        'flex flex-wrap items-center gap-2 font-heading text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl lg:text-5xl',
                        reverse ? 'justify-center md:justify-end' : 'justify-center md:justify-start',
                    ]"
                >
                    <span v-if="titlePrefix">{{ titlePrefix }}</span>
                    <span
                        v-if="hasBadgeInTitle"
                        class="inline-block rounded-sm border-2 border-foreground px-2.5 py-0.5 text-2xl uppercase tracking-wide sm:text-3xl lg:text-4xl"
                    >{{ site.badge }}</span>
                </h3>

                <!-- Description -->
                <p
                    :class="[
                        'mx-auto max-w-xl text-sm leading-relaxed text-muted-foreground sm:text-base',
                        reverse ? 'md:ml-auto md:mr-0' : 'md:ml-0 md:mr-auto',
                    ]"
                >{{ site.description }}</p>

                <!-- CTA -->
                <div
                    :class="[
                        'flex items-center gap-2 pt-1 text-sm font-semibold text-primary transition-transform group-hover:translate-x-0.5',
                        reverse ? 'justify-center md:justify-end' : 'justify-center md:justify-start',
                    ]"
                >
                    <span>{{ site.cta_text || "Kunjungi website" }}</span>
                    <ArrowUpRight class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
            </div>
        </div>
    </a>
</template>
