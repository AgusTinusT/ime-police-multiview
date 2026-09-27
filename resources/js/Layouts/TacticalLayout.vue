<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import TacticalFooter from "@/Components/TacticalFooter.vue";
import TacticalChatDrawer from "@/Components/TacticalChatDrawer.vue";

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
        class="min-h-screen bg-[#070b12] text-slate-100 font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased"
    >
        <!-- Standardized Tactical Header Bar -->
        <TacticalDashboardHeader />

        <!-- Dynamic Content Area -->
        <main class="flex-1 w-full">
            <slot />
        </main>

        <!-- Standardized Tactical Footer -->
        <TacticalFooter />

        <!-- Floating Tactical Community Chat -->
        <TacticalChatDrawer />
    </div>
</template>
