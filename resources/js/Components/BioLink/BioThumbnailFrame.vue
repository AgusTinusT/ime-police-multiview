<script setup>
import { ref, computed } from "vue";
import { useElementSize } from "@vueuse/core";
import BioLinkView from "@/Components/BioLink/BioLinkView.vue";

const props = defineProps({
    themeKey: {
        type: String,
        required: true,
    },
    themesMap: {
        type: Object,
        required: true,
    },
    officer: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        required: true,
    },
    links: {
        type: Array,
        required: true,
    },
});

const frameRef = ref(null);
const { width } = useElementSize(frameRef, { width: 280, height: 384 });

const BASE_WIDTH = 380;

const scale = computed(() => {
    const currentWidth = width.value > 0 ? width.value : 280;
    return currentWidth / BASE_WIDTH;
});
</script>

<template>
    <div
        ref="frameRef"
        class="relative h-96 w-full overflow-hidden rounded-xl border border-border/80 bg-background/90 shadow-inner select-none pointer-events-none"
    >
        <div
            :style="{
                width: `${BASE_WIDTH}px`,
                transform: `scale(${scale})`,
                transformOrigin: 'top left',
            }"
            class="max-w-none transform-gpu"
        >
            <BioLinkView
                :officer="officer"
                :profile="profile"
                :links="links"
                :themes="themesMap"
                :theme-key="themeKey"
                contained
            />
        </div>
        <!-- Subtle glare & border overlay -->
        <div class="absolute inset-0 rounded-xl ring-1 ring-inset ring-white/5 pointer-events-none" />
    </div>
</template>
