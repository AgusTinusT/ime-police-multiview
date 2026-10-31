<script setup>
import { computed } from "vue";
import { ArrowUpRight } from "@lucide/vue";
import { Badge } from "@/Components/ui/badge";

const props = defineProps({
    site: { type: Object, required: true },
});

const backgroundStyle = computed(() => {
    if (!props.site?.background_image_url) return {};
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

const hasBadgeInTitle = computed(() => Boolean(props.site?.badge));
</script>

<template>
    <!-- Fixed-width tile sits in the CatalogRow horizontal scroll track. -->
    <a
        :href="site.url"
        target="_blank"
        rel="noopener noreferrer"
        class="group relative block w-[88vw] max-w-[700px] shrink-0 snap-start overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition-all duration-300 hover:border-primary/50 hover:shadow-lg hover:shadow-primary/5 hover:-translate-y-0.5 sm:w-[680px]"
        :style="backgroundStyle"
    >
        <!-- Scrim keeps text readable while leaving most of the hero art visible on the right. -->
        <div
            class="pointer-events-none absolute inset-0 bg-background/55 sm:bg-gradient-to-r sm:from-background sm:via-background/70 sm:via-40% sm:to-background/0"
        />

        <!-- Text column is capped so hero art has room to breathe on wider cards. -->
        <div class="relative z-10 flex gap-4 p-4 sm:gap-5 sm:p-5 sm:pr-10 md:w-[62%]">
            <!-- Poster: image fills the whole tile, labels float on scrims -->
            <div class="shrink-0">
                <div class="relative h-56 w-32 overflow-hidden rounded-xl border border-warning/40 shadow-sm sm:h-60 sm:w-36">
                    <img
                        :src="site.poster_image_url"
                        :alt="site.poster_name || site.name"
                        class="absolute inset-0 size-full object-cover"
                        loading="lazy"
                    />

                    <!-- Top and bottom scrims keep the gold label and caption readable -->
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-black/80 to-transparent" />
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/85 to-transparent" />

                    <span class="absolute inset-x-0 top-2 text-center text-xs font-mono font-bold uppercase tracking-widest text-warning">
                        ★ Pilihan
                    </span>

                    <div class="absolute inset-x-0 bottom-0 space-y-0.5 p-2.5 text-center">
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
            <div class="min-w-0 flex-1 space-y-2.5 text-left">
                <!-- Chips -->
                <div class="flex flex-wrap gap-1.5">
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

                <!-- Title + boxed badge -->
                <h3 class="flex flex-wrap items-center gap-1.5 font-heading text-xl font-extrabold uppercase tracking-tight text-foreground sm:text-2xl">
                    <span v-if="titlePrefix">{{ titlePrefix }}</span>
                    <span
                        v-if="hasBadgeInTitle"
                        class="inline-block rounded-sm border-2 border-foreground px-1.5 py-0.5 text-lg uppercase tracking-wide sm:text-xl"
                    >{{ site.badge }}</span>
                </h3>

                <!-- Description, capped at 3 lines so cards keep uniform height -->
                <p class="line-clamp-3 text-sm leading-relaxed text-muted-foreground">
                    {{ site.description }}
                </p>

                <!-- CTA affordance (card is the link) -->
                <div class="flex items-center gap-1.5 pt-1 text-sm font-semibold text-primary">
                    <span>{{ site.cta_text || "Kunjungi website" }}</span>
                    <ArrowUpRight class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
            </div>
        </div>
    </a>
</template>
