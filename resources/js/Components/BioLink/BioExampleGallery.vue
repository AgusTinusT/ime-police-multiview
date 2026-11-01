<script setup>
import { computed } from "vue";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/Components/ui/card";
import BioThumbnailFrame from "@/Components/BioLink/BioThumbnailFrame.vue";
import { cn } from "@/lib/utils";
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
    class: {
        type: [String, Object, Array],
        default: "",
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
    <div :class="cn('grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6', props.class)">
        <Card
            v-for="item in themeList"
            :key="item.key"
            class="overflow-hidden border border-border bg-card/80 backdrop-blur-xs flex flex-col justify-between"
        >
            <CardHeader class="p-3.5 pb-2.5 border-b border-border/60">
                <div class="space-y-1">
                    <CardTitle class="text-sm font-bold font-mono text-foreground truncate">
                        {{ item.name }}
                    </CardTitle>
                    <CardDescription class="text-xs text-muted-foreground line-clamp-2">
                        {{ item.description }}
                    </CardDescription>
                </div>
            </CardHeader>

            <CardContent class="p-3.5 pt-3 flex-1 flex flex-col justify-center">
                <!-- Thumbnail Frame (Auto-scaled to fit frame width, pointer-events disabled) -->
                <BioThumbnailFrame
                    :theme-key="item.key"
                    :themes-map="themesMap"
                    :officer="dummyOfficer"
                    :profile="dummyProfile"
                    :links="dummyLinks"
                />
            </CardContent>
        </Card>
    </div>
</template>
