<script setup>
import { Button } from "@/Components/ui/button";

defineProps({
    isMobileLandscape: {
        type: Boolean,
        default: false,
    },
    showLandscapeControls: {
        type: Boolean,
        default: false,
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["toggle-fullscreen"]);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isMobileLandscape && showLandscapeControls"
            class="fixed top-3 right-3 z-100 flex items-center gap-2 bg-background/85 backdrop-blur-md border border-border rounded-full px-3.5 py-1.5 text-xs font-mono text-foreground animate-in fade-in duration-200"
        >
            <span
                class="w-2 h-2 rounded-full bg-success motion-safe:animate-pulse"
            ></span>
            <span class="text-xs font-bold text-foreground/80">
                LANDSCAPE MODE
            </span>
            <Button
                type="button"
                size="sm"
                class="h-6 px-2.5 rounded-full text-xs font-bold"
                @click.stop="emit('toggle-fullscreen')"
            >
                {{ isFullscreen ? "Keluar Fullscreen" : "Fullscreen" }}
            </Button>
        </div>
    </Teleport>
</template>
