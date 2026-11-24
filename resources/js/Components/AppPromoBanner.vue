<script setup>
import { ref, onMounted } from "vue";
import { X } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import ytChatOverlayIcon from "@/Components/Icons/yt-chat-overlay.png";

const STORAGE_KEY = "ime_yt_chat_overlay_dismissed_until";
const DISMISS_TTL_MS = 7 * 24 * 60 * 60 * 1000;
const STORE_URL =
    "https://apps.microsoft.com/detail/9NK2ZFN4Z9Z8?hl=en-us&gl=ID&ocid=pdpshare";

const isVisible = ref(false);

onMounted(() => {
    try {
        const until = Number(localStorage.getItem(STORAGE_KEY) || 0);
        isVisible.value = !until || Date.now() > until;
    } catch {
        isVisible.value = true;
    }
});

const dismiss = () => {
    isVisible.value = false;
    try {
        localStorage.setItem(STORAGE_KEY, String(Date.now() + DISMISS_TTL_MS));
    } catch {
        // Ignore storage errors in restricted browsing contexts.
    }
};
</script>

<template>
    <div
        v-if="isVisible"
        role="region"
        aria-label="Promosi aplikasi YT Chat Overlay"
        class="relative z-40 border-b border-border bg-card"
    >
        <div
            class="mx-auto flex max-w-(--breakpoint-2xl) flex-col gap-2.5 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:px-4 lg:px-8"
        >
            <!-- Top Row (Mobile) / Left Side (Desktop) -->
            <div class="flex items-center justify-between gap-2.5 sm:justify-start sm:gap-3 min-w-0">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Tutup banner promosi aplikasi"
                    class="hidden sm:inline-flex shrink-0 text-muted-foreground hover:text-foreground"
                    @click="dismiss"
                >
                    <X class="size-4" />
                </Button>

                <div class="flex items-center gap-2.5 min-w-0">
                    <img
                        :src="ytChatOverlayIcon"
                        alt="YT Chat Overlay"
                        class="size-9 shrink-0 rounded-lg object-cover"
                    />
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs sm:text-sm font-semibold text-foreground">
                                YT Chat Overlay
                            </span>
                            <Badge variant="secondary" class="text-xs">
                                Windows
                            </Badge>
                        </div>
                        <p class="hidden sm:block truncate text-xs text-muted-foreground">
                            Baca live chat YouTube di atas game tanpa monitor kedua • Gratis
                        </p>
                    </div>
                </div>

                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Tutup banner promosi aplikasi"
                    class="sm:hidden shrink-0 text-muted-foreground hover:text-foreground"
                    @click="dismiss"
                >
                    <X class="size-4" />
                </Button>
            </div>

            <!-- Bottom Row (Mobile): non-truncated description + CTA -->
            <div class="flex items-center justify-between gap-3 sm:hidden">
                <p class="text-xs leading-snug text-muted-foreground">
                    Live chat YouTube di atas game tanpa monitor kedua • Gratis
                </p>
                <Button as-child size="sm" class="shrink-0">
                    <a :href="STORE_URL" target="_blank" rel="noopener noreferrer">
                        <svg class="size-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <rect x="1" y="1" width="6.5" height="6.5" rx="0.5" />
                            <rect x="8.5" y="1" width="6.5" height="6.5" rx="0.5" />
                            <rect x="1" y="8.5" width="6.5" height="6.5" rx="0.5" />
                            <rect x="8.5" y="8.5" width="6.5" height="6.5" rx="0.5" />
                        </svg>
                        <span>Dapatkan</span>
                    </a>
                </Button>
            </div>

            <!-- CTA (Desktop) -->
            <Button as-child size="sm" class="hidden sm:inline-flex shrink-0">
                <a :href="STORE_URL" target="_blank" rel="noopener noreferrer">
                    <svg class="size-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <rect x="1" y="1" width="6.5" height="6.5" rx="0.5" />
                        <rect x="8.5" y="1" width="6.5" height="6.5" rx="0.5" />
                        <rect x="1" y="8.5" width="6.5" height="6.5" rx="0.5" />
                        <rect x="8.5" y="8.5" width="6.5" height="6.5" rx="0.5" />
                    </svg>
                    <span>Microsoft Store</span>
                </a>
            </Button>
        </div>
    </div>
</template>
