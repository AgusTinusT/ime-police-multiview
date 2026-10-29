<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";
import { Toaster } from "@/Components/ui/sonner";

defineProps({
    showFooter: {
        type: Boolean,
        default: true,
    },
});

const isFullscreen = ref(false);

const handleOpenClipper = (e) => {
    const url = e?.detail?.url || "";
    if (url) {
        router.visit(`/clipper?url=${encodeURIComponent(url)}`);
    } else {
        router.visit("/clipper");
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

onMounted(() => {
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    window.addEventListener("open-clipper-modal", handleOpenClipper);
});

onUnmounted(() => {
    document.removeEventListener("fullscreenchange", handleFullscreenChange);
    window.removeEventListener("open-clipper-modal", handleOpenClipper);
});
</script>

<template>
    <div
        class="min-h-screen bg-background text-foreground font-sans selection:bg-primary selection:text-primary-foreground flex flex-col antialiased"
    >
        <!-- Standardized Tactical Header Bar -->
        <TacticalDashboardHeader />

        <!-- Dynamic Content Area -->
        <main class="flex-1 w-full">
            <slot />
        </main>

        <!-- Standardized Tactical Footer -->
        <TacticalFooter v-if="showFooter" />

        <!-- Floating Tactical Community Chat -->
        <TacticalChatDrawer />

        <Toaster rich-colors position="top-right" />
    </div>
</template>
