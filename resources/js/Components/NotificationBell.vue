<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Bell, BellRing, CheckCheck, Download, Settings, Smartphone, X } from "@lucide/vue";
import { toast } from "vue-sonner";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Switch } from "@/Components/ui/switch";
import { Separator } from "@/Components/ui/separator";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { OfficerAvatar } from "@/Components/tactical";
import PwaIosInstallDialog from "@/Components/PwaIosInstallDialog.vue";
import { cn } from "@/lib/utils";
import { usePushNotifications } from "@/Composables/usePushNotifications";
import { usePwaInstall } from "@/Composables/usePwaInstall";

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const open = ref(false);
const loading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);
let pollTimer = null;

const {
    isSupported: pushSupported,
    serverEnabled: pushServerEnabled,
    isSubscribed: pushSubscribed,
    permission: pushPermission,
    isBusy: pushBusy,
    syncState: syncPushState,
    togglePush,
} = usePushNotifications();

const {
    isInstalled: pwaInstalled,
    isIosSafari,
    shouldShowNudge: showPwaNudge,
    triggerInstall: triggerPwaInstall,
    dismissNudge: dismissPwaNudge,
} = usePwaInstall();

const iosDialogOpen = ref(false);

const handlePwaInstallClick = async () => {
    if (isIosSafari.value) {
        open.value = false;
        iosDialogOpen.value = true;
        return;
    }

    const accepted = await triggerPwaInstall();
    if (accepted) {
        toast.success("Aplikasi berhasil dipasang di perangkat ini.");
    }
};

const fetchNotifications = async () => {
    if (!isAuthenticated.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get("/api/v1/notifications");
        unreadCount.value = Number(data?.unread_count || 0);
        notifications.value = Array.isArray(data?.notifications)
            ? data.notifications
            : [];
    } catch {
        // Silent background error
    } finally {
        loading.value = false;
    }
};

const markAllRead = async () => {
    if (unreadCount.value === 0) return;
    try {
        await axios.post("/api/v1/notifications/read-all");
        unreadCount.value = 0;
        notifications.value = notifications.value.map((n) => ({
            ...n,
            read_at: n.read_at || new Date().toISOString(),
        }));
        toast.success("Semua notifikasi ditandai dibaca.");
    } catch {
        toast.error("Gagal menandai notifikasi dibaca.");
    }
};

const openNotification = async (item) => {
    if (!item.read_at) {
        try {
            const { data } = await axios.post(`/api/v1/notifications/${item.id}/read`);
            item.read_at = new Date().toISOString();
            unreadCount.value = Number(data?.unread_count ?? Math.max(0, unreadCount.value - 1));
        } catch {
            // Continue navigation even if mark-read fails
        }
    }

    open.value = false;
    const url = item.data?.url || "/dashboard";
    router.visit(url);
};

const formatRelativeTime = (isoString) => {
    if (!isoString) return "";
    const diffSec = Math.max(0, Math.floor((Date.now() - new Date(isoString).getTime()) / 1000));
    if (diffSec < 60) return "Baru saja";
    if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m lalu`;
    if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}j lalu`;
    return `${Math.floor(diffSec / 86400)}h lalu`;
};

const pushHint = computed(() => {
    if (isIosSafari.value && !pushSupported.value) return "Perlu instal ke Layar Utama (iOS).";
    if (!pushSupported.value) return "Browser tidak mendukung push.";
    if (!pushServerEnabled.value) return "Push server belum dikonfigurasi.";
    if (pushPermission.value === "denied") return "Izin notifikasi diblokir browser.";
    return pushSubscribed.value
        ? pwaInstalled.value
            ? "Aktif di aplikasi perangkat ini."
            : "Aktif di perangkat ini."
        : "Terima notifikasi real-time di perangkat ini.";
});

watch(open, (isOpen) => {
    if (isOpen && isAuthenticated.value) {
        fetchNotifications();
        syncPushState();
    }
});

onMounted(() => {
    if (isAuthenticated.value) {
        fetchNotifications();
        pollTimer = setInterval(fetchNotifications, 60000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <Popover v-if="isAuthenticated" v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="relative size-10 rounded-full text-foreground/80 hover:bg-foreground/10 hover:text-foreground"
                aria-label="Notifikasi"
                title="Notifikasi"
            >
                <BellRing v-if="unreadCount > 0" class="size-5 text-primary" />
                <Bell v-else class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-live px-1 font-mono text-xs font-extrabold text-primary-foreground"
                >
                    {{ unreadCount > 9 ? "9+" : unreadCount }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent
            align="end"
            :side-offset="10"
            class="w-80 sm:w-96 p-0 overflow-hidden"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <Bell class="size-4 text-primary" />
                    <h3 class="text-sm font-bold text-foreground">Notifikasi</h3>
                    <Badge v-if="unreadCount > 0" variant="secondary" class="font-mono text-xs">
                        {{ unreadCount }}
                    </Badge>
                </div>
                <Button
                    v-if="unreadCount > 0"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-xs text-muted-foreground hover:text-foreground"
                    @click="markAllRead"
                >
                    <CheckCheck class="size-3.5" />
                    <span>Tandai dibaca</span>
                </Button>
            </div>

            <Separator />

            <!-- Web Push Toggle -->
            <div class="flex items-center justify-between gap-3 bg-muted/40 px-4 py-2.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Smartphone class="size-4 shrink-0 text-primary" />
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-foreground">
                            Web Push Browser
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ pushHint }}
                        </p>
                    </div>
                </div>
                <Switch
                    :model-value="pushSubscribed"
                    :disabled="!pushSupported || !pushServerEnabled || pushBusy || pushPermission === 'denied'"
                    aria-label="Aktifkan Web Push Browser"
                    @update:model-value="togglePush"
                />
            </div>

            <!-- PWA Install Nudge -->
            <template v-if="showPwaNudge">
                <Separator />
                <div class="bg-primary/10 px-4 py-2.5 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 space-y-0.5">
                            <p class="text-xs font-semibold text-foreground">
                                {{ isIosSafari ? "Aktifkan Push di iPhone / iPad" : "Pasang sebagai Aplikasi (PWA)" }}
                            </p>
                            <p class="text-xs text-muted-foreground leading-snug">
                                {{
                                    isIosSafari
                                        ? "Safari iOS mewajibkan Add to Home Screen untuk menerima notifikasi push."
                                        : "Notifikasi tetap masuk meski browser ditutup."
                                }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-6 shrink-0 text-muted-foreground hover:text-foreground"
                            aria-label="Tutup ajakan instal aplikasi"
                            @click="dismissPwaNudge"
                        >
                            <X class="size-3.5" />
                        </Button>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        class="h-7 gap-1.5 text-xs"
                        @click="handlePwaInstallClick"
                    >
                        <Download class="size-3.5" />
                        <span>{{ isIosSafari ? "Lihat Cara Instal" : "Instal Aplikasi" }}</span>
                    </Button>
                </div>
            </template>

            <Separator />

            <!-- Notification List -->
            <div class="max-h-80 overflow-y-auto divide-y divide-border">
                <div
                    v-if="loading && notifications.length === 0"
                    class="px-4 py-8 text-center text-xs text-muted-foreground"
                >
                    Memuat notifikasi...
                </div>

                <div
                    v-else-if="notifications.length === 0"
                    class="px-4 py-8 text-center space-y-1"
                >
                    <p class="text-sm font-medium text-foreground">Belum ada notifikasi</p>
                    <p class="text-xs text-muted-foreground">
                        Pembaruan penting dan siaran live perwira yang kamu ikuti akan muncul di sini.
                    </p>
                </div>

                <button
                    v-for="item in notifications"
                    :key="item.id"
                    type="button"
                    :class="
                        cn(
                            'flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-accent/60 cursor-pointer',
                            !item.read_at && 'bg-primary/5',
                        )
                    "
                    @click="openNotification(item)"
                >
                    <OfficerAvatar
                        :src="item.data?.avatar_url"
                        :name="item.data?.officer_name || 'Officer'"
                        :live="true"
                        size="sm"
                        class="shrink-0 mt-0.5"
                    />
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-bold text-foreground">
                                {{ item.data?.officer_name || "Perwira" }}
                                <span
                                    v-if="item.data?.callsign"
                                    class="font-mono font-normal text-muted-foreground"
                                >
                                    · {{ item.data.callsign }}
                                </span>
                            </p>
                            <span class="shrink-0 text-xs text-muted-foreground">
                                {{ formatRelativeTime(item.created_at) }}
                            </span>
                        </div>
                        <p class="line-clamp-2 text-xs text-muted-foreground">
                            {{ item.data?.title || "Mulai siaran langsung patroli!" }}
                        </p>
                    </div>
                    <span
                        v-if="!item.read_at"
                        class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <Separator />

            <!-- Footer: Manage followed officers & devices in Settings -->
            <div class="bg-muted/30 px-3 py-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="w-full justify-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                    @click="open = false; router.visit('/settings?tab=notifications')"
                >
                    <Settings class="size-3.5" />
                    <span>Kelola Perwira & Notifikasi</span>
                </Button>
            </div>
        </PopoverContent>
    </Popover>

    <PwaIosInstallDialog v-model:open="iosDialogOpen" />
</template>
