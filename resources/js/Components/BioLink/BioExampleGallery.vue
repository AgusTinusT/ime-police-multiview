<script setup>
import { computed } from "vue";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import BioLinkView from "@/Components/BioLink/BioLinkView.vue";
import {
    dummyOfficer,
    dummyProfile,
    dummyLinks,
    fallbackThemePresets,
} from "@/lib/bioExampleData";

const props = defineProps({
    themes: {
        type: Object,
        default: () => ({}),
    },
    limit: {
        type: Number,
        default: null,
    },
});

const themesMap = computed(() => {
    if (props.themes && Object.keys(props.themes).length > 0) {
        return props.themes;
    }
    return fallbackThemePresets;
});

const themeList = computed(() => {
    const entries = Object.entries(themesMap.value).map(([key, theme]) => ({
        key,
        ...theme,
    }));

    if (props.limit && props.limit > 0) {
        return entries.slice(0, props.limit);
    }
    return entries;
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card
            v-for="item in themeList"
            :key="item.key"
            class="overflow-hidden border border-border bg-card/80 backdrop-blur-xs flex flex-col justify-between"
        >
            <CardHeader class="p-4 pb-3 border-b border-border/60">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <CardTitle class="text-sm font-bold font-mono text-foreground truncate">
                            {{ item.name }}
                        </CardTitle>
                        <CardDescription class="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                            {{ item.description }}
                        </CardDescription>
                    </div>
                    <Badge variant="outline" class="font-mono text-xs uppercase shrink-0">
                        {{ item.backdrop || "preset" }}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent class="p-4 pt-3 flex-1">
                <!-- Thumbnail Frame (Preview Only, pointer-events disabled) -->
                <div class="relative h-96 overflow-hidden rounded-xl border border-border/80 bg-background/90 shadow-inner select-none pointer-events-none">
                    <div class="w-[440px] max-w-none origin-top-left scale-75 transform-gpu">
                        <BioLinkView
                            :officer="dummyOfficer"
                            :profile="dummyProfile"
                            :links="dummyLinks"
                            :themes="themesMap"
                            :theme-key="item.key"
                            contained
                        />
                    </div>
                    <!-- Subtle glare & border overlay -->
                    <div class="absolute inset-0 rounded-xl ring-1 ring-inset ring-white/5 pointer-events-none" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>

