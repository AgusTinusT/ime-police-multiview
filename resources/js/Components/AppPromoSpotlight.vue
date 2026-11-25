<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Check, Heart } from "@lucide/vue";
import { Card, CardContent } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { PageContainer } from "@/Components/tactical";
import ytChatOverlayIcon from "@/Components/Icons/yt-chat-overlay.png";
import {
    YT_CHAT_OVERLAY_STORE_URL,
    YT_CHAT_OVERLAY_THANKED_OFFICERS,
} from "@/Data/appPromo";

const FEATURES = [
    "Transparan & Click-Through",
    "Tanpa Login & API Key",
    "Ringan di Background",
];

const activeOfficerIndex = ref(0);
let rotationTimer = null;

const currentOfficer = computed(
    () =>
        YT_CHAT_OVERLAY_THANKED_OFFICERS[
            activeOfficerIndex.value % YT_CHAT_OVERLAY_THANKED_OFFICERS.length
        ]
);

const startRotation = () => {
    stopRotation();
    if (YT_CHAT_OVERLAY_THANKED_OFFICERS.length <= 1) return;
    rotationTimer = setInterval(() => {
        activeOfficerIndex.value =
            (activeOfficerIndex.value + 1) %
            YT_CHAT_OVERLAY_THANKED_OFFICERS.length;
    }, 3500);
};

const stopRotation = () => {
    if (rotationTimer) {
        clearInterval(rotationTimer);
        rotationTimer = null;
    }
};

onMounted(() => startRotation());
onUnmounted(() => stopRotation());
</script>

<template>
    <section
        aria-label="Rekomendasi aplikasi YT Chat Overlay"
        class="relative z-10 pb-10 sm:pb-12"
        @mouseenter="stopRotation"
        @mouseleave="startRotation"
    >
        <PageContainer>
            <Card class="overflow-hidden border-border bg-card/80">
                <CardContent
                    class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Left: Icon + Info + Shoutout -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <img
                            :src="ytChatOverlayIcon"
                            alt="YT Chat Overlay"
                            class="size-14 shrink-0 rounded-2xl object-cover"
                        />

                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold text-foreground sm:text-lg">
                                    YT Chat Overlay
                                </h2>
                                <Badge variant="secondary" class="text-xs">
                                    Windows • Gratis
                                </Badge>
                                <Badge
                                    v-if="currentOfficer"
                                    variant="outline"
                                    class="border-success/25 bg-success/15 text-success text-xs overflow-hidden"
                                >
                                    <Heart class="size-3 shrink-0 fill-current" />
                                    <Transition
                                        mode="out-in"
                                        enter-active-class="motion-safe:transition-all motion-safe:duration-300 motion-safe:ease-out"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="motion-safe:transition-all motion-safe:duration-200 motion-safe:ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-1"
                                    >
                                        <span :key="currentOfficer" class="inline-block">
                                            Terima kasih {{ currentOfficer }}
                                        </span>
                                    </Transition>
                                </Badge>
                            </div>

                            <p class="max-w-2xl text-xs leading-relaxed text-muted-foreground sm:text-sm">
                                Tampilkan live chat YouTube langsung di atas game tanpa perlu monitor kedua.
                                Cukup masukkan @channel sekali dan chat otomatis muncul saat Anda live.
                            </p>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 pt-0.5">
                                <span
                                    v-for="feature in FEATURES"
                                    :key="feature"
                                    class="inline-flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <Check class="size-3.5 text-success shrink-0" />
                                    {{ feature }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: CTA -->
                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col lg:items-end">
                        <Button as-child size="default" class="w-full sm:w-auto">
                            <a
                                :href="YT_CHAT_OVERLAY_STORE_URL"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <svg
                                    class="size-4 shrink-0"
                                    viewBox="0 0 16 16"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <rect x="1" y="1" width="6.5" height="6.5" rx="0.5" />
                                    <rect x="8.5" y="1" width="6.5" height="6.5" rx="0.5" />
                                    <rect x="1" y="8.5" width="6.5" height="6.5" rx="0.5" />
                                    <rect x="8.5" y="8.5" width="6.5" height="6.5" rx="0.5" />
                                </svg>
                                <span>Dapatkan di Microsoft Store</span>
                            </a>
                        </Button>
                        <span class="text-center text-xs text-muted-foreground lg:text-right">
                            Tersedia resmi untuk Windows 10/11
                        </span>
                    </div>
                </CardContent>
            </Card>
        </PageContainer>
    </section>
</template>
