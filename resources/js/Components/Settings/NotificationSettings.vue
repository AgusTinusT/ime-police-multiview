<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import {
    Bell,
    BellRing,
    CheckCircle2,
    ExternalLink,
    FlaskConical,
    Link2,
    MessageCircle,
    Search,
    Send,
    Smartphone,
    Trash2,
    TriangleAlert,
    Unlink,
    UserMinus,
    UserPlus,
} from "@lucide/vue";
import { toast } from "vue-sonner";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Separator } from "@/Components/ui/separator";
import { Switch } from "@/Components/ui/switch";
import { DeptBadge, OfficerAvatar } from "@/Components/tactical";
import { cn } from "@/lib/utils";
import { usePushNotifications } from "@/Composables/usePushNotifications";
import { useOfficerSubscriptions } from "@/Composables/useOfficerSubscriptions";

const props = defineProps({
    rosterOfficers: {
        type: Array,
        default: () => [],
    },
});

const {
    isSupported: pushSupported,
    serverEnabled: pushServerEnabled,
    isSubscribed: pushSubscribed,
    permission: pushPermission,
    isBusy: pushBusy,
    syncState: syncPushState,
    togglePush,
} = usePushNotifications();

const { subscribedIds } = useOfficerSubscriptions();

const loadingSubs = ref(true);
const subscriptions = ref([]);
const devices = ref([]);
const sendingTest = ref(false);
const busyOfficerIds = ref(new Set());
const searchQuery = ref("");
const selectedDept = ref("ALL");

const discordStatus = ref({
    oauth_enabled: false,
    bot_enabled: false,
    channel_enabled: false,
    invite_url: null,
    connected: false,
    discord_id: null,
    discord_username: null,
    discord_avatar_url: null,
    discord_connected_at: null,
    dm_failed_reason: null,
});
const loadingDiscord = ref(true);
const sendingDiscordTest = ref(false);
const disconnectingDiscord = ref(false);

const departments = ["ALL", "LSPD", "BCSO", "SASP", "SAPR"];

const loadSubscriptions = async () => {
    loadingSubs.value = true;
    try {
        const { data } = await axios.get("/api/v1/subscriptions");
        subscriptions.value = Array.isArray(data?.subscriptions)
            ? data.subscriptions
            : [];
        subscribedIds.clear();
        for (const sub of subscriptions.value) {
            subscribedIds.add(Number(sub.officer_id));
        }
    } catch {
        toast.error("Gagal memuat daftar perwira yang diikuti.");
    } finally {
        loadingSubs.value = false;
    }
};

const loadDevices = async () => {
    try {
        const { data } = await axios.get("/api/v1/push-subscriptions");
        devices.value = Array.isArray(data?.devices) ? data.devices : [];
    } catch {
        // Silent fail
    }
};

const loadDiscordStatus = async () => {
    loadingDiscord.value = true;
    try {
        const { data } = await axios.get("/api/v1/discord/status");
        if (data && typeof data === "object") {
            discordStatus.value = { ...discordStatus.value, ...data };
        }
    } catch {
        // Silent fail
    } finally {
        loadingDiscord.value = false;
    }
};

const handleConnectDiscord = () => {
    window.location.href = "/auth/discord/redirect";
};

const handleTestDiscordDm = async () => {
    if (sendingDiscordTest.value) return;
    sendingDiscordTest.value = true;
    try {
        const { data } = await axios.post("/api/v1/discord/test-dm");
        toast.success(data?.message || "DM uji coba berhasil dikirim ke Discord kamu.");
        await loadDiscordStatus();
    } catch (err) {
        toast.error(
            err?.response?.data?.message || "Gagal mengirim DM uji coba Discord.",
        );
        await loadDiscordStatus();
    } finally {
        sendingDiscordTest.value = false;
    }
};

const handleDisconnectDiscord = async () => {
    if (disconnectingDiscord.value) return;
    disconnectingDiscord.value = true;
    try {
        const { data } = await axios.delete("/api/v1/discord/disconnect");
        toast.info(data?.message || "Tautan akun Discord diputuskan.");
        await loadDiscordStatus();
    } catch {
        toast.error("Gagal memutuskan tautan akun Discord.");
    } finally {
        disconnectingDiscord.value = false;
    }
};

const handleDiscordOAuthFeedback = () => {
    if (typeof window === "undefined") return;
    const url = new URL(window.location.href);
    const status = url.searchParams.get("discord");
    if (!status) return;

    const messages = {
        connected: () => toast.success("Akun Discord berhasil dihubungkan untuk DM live!"),
        cancelled: () => toast.info("Penautan akun Discord dibatalkan."),
        conflict: () => toast.error("Akun Discord ini sudah terhubung ke akun lain."),
        invalid_state: () => toast.error("Sesi OAuth Discord tidak valid. Silakan coba lagi."),
        unconfigured: () => toast.error("Integrasi Discord OAuth belum aktif di server."),
        error: () => toast.error("Gagal menautkan akun Discord. Silakan coba lagi."),
    };
    (messages[status] || messages.error)();

    url.searchParams.delete("discord");
    window.history.replaceState({}, "", url.pathname + url.search + url.hash);
};

const handleTogglePush = async (nextVal) => {
    await togglePush(nextVal);
    await loadDevices();
};

const handleSendTest = async () => {
    if (sendingTest.value) return;
    sendingTest.value = true;
    try {
        const { data } = await axios.post("/api/v1/push-subscriptions/test");
        toast.success(data?.message || "Notifikasi uji coba berhasil dikirim.");
    } catch (err) {
        toast.error(
            err?.response?.data?.message ||
                "Gagal mengirim notifikasi uji coba.",
        );
    } finally {
        sendingTest.value = false;
    }
};

const removeDevice = async (deviceId) => {
    try {
        await axios.delete(`/api/v1/push-subscriptions/${deviceId}`);
        devices.value = devices.value.filter((d) => d.id !== deviceId);
        toast.info("Perangkat dihapus dari daftar push.");
        await syncPushState();
    } catch {
        toast.error("Gagal menghapus perangkat.");
    }
};

const setOfficerBusy = (id, state) => {
    const next = new Set(busyOfficerIds.value);
    if (state) next.add(id);
    else next.delete(id);
    busyOfficerIds.value = next;
};

const updateChannelPrefs = async (sub, patch) => {
    const id = Number(sub.officer_id);
    if (busyOfficerIds.value.has(id)) return;
    setOfficerBusy(id, true);

    const payload = {
        notify_in_app:
            patch.notify_in_app !== undefined
                ? patch.notify_in_app
                : sub.notify_in_app,
        notify_web_push:
            patch.notify_web_push !== undefined
                ? patch.notify_web_push
                : sub.notify_web_push,
        notify_discord_dm:
            patch.notify_discord_dm !== undefined
                ? patch.notify_discord_dm
                : (sub.notify_discord_dm ?? true),
    };

    try {
        await axios.post(`/api/v1/officers/${id}/subscribe`, payload);
        sub.notify_in_app = payload.notify_in_app;
        sub.notify_web_push = payload.notify_web_push;
        sub.notify_discord_dm = payload.notify_discord_dm;
        toast.success("Preferensi notifikasi diperbarui.");
    } catch {
        toast.error("Gagal memperbarui preferensi notifikasi.");
    } finally {
        setOfficerBusy(id, false);
    }
};

const followOfficer = async (officer) => {
    const id = Number(officer.id);
    if (busyOfficerIds.value.has(id)) return;
    setOfficerBusy(id, true);
    try {
        const { data } = await axios.post(`/api/v1/officers/${id}/subscribe`, {
            notify_in_app: true,
            notify_web_push: true,
            notify_discord_dm: true,
        });
        subscribedIds.add(id);
        const existing = subscriptions.value.find((s) => Number(s.officer_id) === id);
        if (!existing) {
            subscriptions.value.unshift({
                id: data?.subscription?.id ?? id,
                officer_id: id,
                notify_in_app: true,
                notify_web_push: true,
                notify_discord_dm: true,
                officer,
            });
        }
        toast.success(`Mengikuti notifikasi live ${officer.officer_name}.`);
    } catch (err) {
        toast.error(
            err?.response?.data?.message || "Gagal mengikuti perwira.",
        );
    } finally {
        setOfficerBusy(id, false);
    }
};

const unfollowOfficer = async (officerId, officerName = "") => {
    const id = Number(officerId);
    if (busyOfficerIds.value.has(id)) return;
    setOfficerBusy(id, true);
    try {
        await axios.delete(`/api/v1/officers/${id}/subscribe`);
        subscribedIds.delete(id);
        subscriptions.value = subscriptions.value.filter(
            (s) => Number(s.officer_id) !== id,
        );
        toast.info(
            officerName
                ? `Berhenti mengikuti ${officerName}.`
                : "Berhenti mengikuti perwira.",
        );
    } catch {
        toast.error("Gagal berhenti mengikuti perwira.");
    } finally {
        setOfficerBusy(id, false);
    }
};

const followedIdSet = computed(
    () => new Set(subscriptions.value.map((s) => Number(s.officer_id))),
);

const filteredRoster = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return props.rosterOfficers.filter((o) => {
        if (selectedDept.value !== "ALL" && o.department !== selectedDept.value) {
            return false;
        }
        if (!q) return true;
        return (
            (o.officer_name || "").toLowerCase().includes(q) ||
            (o.callsign || "").toLowerCase().includes(q) ||
            (o.streamer_name || "").toLowerCase().includes(q) ||
            (o.department || "").toLowerCase().includes(q)
        );
    });
});

const pushStatusMeta = computed(() => {
    if (!pushSupported.value) {
        return { variant: "secondary", label: "Tidak Didukung" };
    }
    if (!pushServerEnabled.value) {
        return { variant: "secondary", label: "Belum Aktif di Server" };
    }
    if (pushPermission.value === "denied") {
        return { variant: "warning", label: "Izin Diblokir Browser" };
    }
    if (pushSubscribed.value) {
        return { variant: "success", label: "Aktif di Perangkat Ini" };
    }
    return { variant: "outline", label: "Nonaktif" };
});

const summarizeUserAgent = (ua) => {
    if (!ua) return "Browser tidak dikenal";
    const browser = ua.includes("Edg/")
        ? "Microsoft Edge"
        : ua.includes("Chrome/")
          ? "Chrome"
          : ua.includes("Firefox/")
            ? "Firefox"
            : ua.includes("Safari/")
              ? "Safari"
              : "Browser";
    const os = ua.includes("Android")
        ? "Android"
        : ua.includes("iPhone") || ua.includes("iPad")
          ? "iOS"
          : ua.includes("Windows")
            ? "Windows"
            : ua.includes("Mac OS")
              ? "macOS"
              : ua.includes("Linux")
                ? "Linux"
                : "";
    return os ? `${browser} (${os})` : browser;
};

const discordStatusMeta = computed(() => {
    if (!discordStatus.value.oauth_enabled && !discordStatus.value.bot_enabled) {
        return { variant: "secondary", label: "Belum Aktif di Server" };
    }
    if (discordStatus.value.connected && discordStatus.value.dm_failed_reason) {
        return { variant: "warning", label: "DM Diblokir" };
    }
    if (discordStatus.value.connected) {
        return { variant: "success", label: "Terhubung" };
    }
    return { variant: "outline", label: "Belum Terhubung" };
});

onMounted(async () => {
    handleDiscordOAuthFeedback();
    await Promise.all([
        syncPushState(),
        loadSubscriptions(),
        loadDevices(),
        loadDiscordStatus(),
    ]);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Beta / Experimental Notice -->
        <div class="flex items-start gap-3 rounded-xl border border-warning/40 bg-warning/10 p-4">
            <FlaskConical class="mt-0.5 size-4 shrink-0 text-warning" />
            <div class="space-y-1 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-foreground">
                        Fitur Notifikasi Live Masih Tahap Beta / Uji Coba
                    </p>
                    <Badge variant="warning" class="font-mono text-xs">
                        BETA
                    </Badge>
                </div>
                <p class="text-muted-foreground leading-relaxed">
                    Notifikasi Web Push, PWA, dan Discord DM/Channel sedang dalam pengembangan dan uji coba aktif di produksi. Tampilan atau perilaku pengiriman dapat berubah sewaktu-waktu seiring penyempurnaan.
                </p>
            </div>
        </div>

        <!-- 1. Web Push & Devices Card -->
        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="space-y-1">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Smartphone class="size-4 text-primary" />
                            <span>Web Push Browser & Perangkat</span>
                        </CardTitle>
                        <CardDescription>
                            Terima notifikasi langsung di HP atau desktop saat perwira yang Anda ikuti mulai live patroli.
                        </CardDescription>
                    </div>
                    <Badge :variant="pushStatusMeta.variant" class="gap-1">
                        <CheckCircle2 v-if="pushSubscribed" class="size-3.5" />
                        <TriangleAlert v-else-if="pushPermission === 'denied'" class="size-3.5" />
                        <span>{{ pushStatusMeta.label }}</span>
                    </Badge>
                </div>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-muted/30 p-4">
                    <div class="space-y-0.5">
                        <p class="text-sm font-semibold text-foreground">
                            Aktifkan Web Push pada perangkat ini
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Memerlukan izin notifikasi browser (HTTPS). Gratis dan tanpa aplikasi tambahan.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            v-if="devices.length > 0 && pushServerEnabled"
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="sendingTest"
                            class="gap-1.5"
                            @click="handleSendTest"
                        >
                            <Send class="size-3.5" />
                            <span>{{ sendingTest ? "Mengirim..." : "Tes Notifikasi" }}</span>
                        </Button>
                        <Switch
                            :model-value="pushSubscribed"
                            :disabled="!pushSupported || !pushServerEnabled || pushBusy || pushPermission === 'denied'"
                            aria-label="Aktifkan Web Push pada perangkat ini"
                            @update:model-value="handleTogglePush"
                        />
                    </div>
                </div>

                <div v-if="devices.length > 0" class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Perangkat Terdaftar ({{ devices.length }})
                    </p>
                    <div class="divide-y divide-border rounded-lg border border-border">
                        <div
                            v-for="device in devices"
                            :key="device.id"
                            class="flex items-center justify-between gap-3 px-3.5 py-2.5 text-xs"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-foreground">
                                    {{ summarizeUserAgent(device.user_agent) }}
                                </p>
                                <p class="text-muted-foreground">
                                    Terdaftar: {{ device.created_at ? new Date(device.created_at).toLocaleDateString("id-ID") : "-" }}
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-destructive"
                                aria-label="Hapus perangkat push"
                                title="Hapus perangkat push"
                                @click="removeDevice(device.id)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 1b. Discord DM Personal Card -->
        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="space-y-1">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <MessageCircle class="size-4 text-primary" />
                            <span>Notifikasi Discord DM Personal</span>
                            <Badge variant="warning" class="font-mono text-xs">
                                BETA
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            Terima Direct Message langsung dari Bot SASP saat perwira yang Anda ikuti mulai live — tetap masuk meski browser ditutup.
                        </CardDescription>
                    </div>
                    <Badge :variant="discordStatusMeta.variant" class="gap-1">
                        <CheckCircle2 v-if="discordStatus.connected && !discordStatus.dm_failed_reason" class="size-3.5" />
                        <TriangleAlert v-else-if="discordStatus.dm_failed_reason" class="size-3.5" />
                        <span>{{ discordStatusMeta.label }}</span>
                    </Badge>
                </div>
            </CardHeader>

            <CardContent class="space-y-3">
                <div v-if="loadingDiscord" class="py-4 text-xs text-muted-foreground">
                    Memuat status koneksi Discord...
                </div>

                <div
                    v-else-if="!discordStatus.connected"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-muted/30 p-4"
                >
                    <div class="space-y-0.5">
                        <p class="text-sm font-semibold text-foreground">
                            Tautkan akun Discord Anda
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Pastikan Anda sudah bergabung di Server Discord komunitas agar Bot dapat mengirim DM pribadi.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            v-if="discordStatus.invite_url"
                            as="a"
                            :href="discordStatus.invite_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            variant="outline"
                            size="sm"
                            class="gap-1.5"
                        >
                            <ExternalLink class="size-3.5" />
                            <span>Gabung Server</span>
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="!discordStatus.oauth_enabled"
                            class="gap-1.5"
                            @click="handleConnectDiscord"
                        >
                            <Link2 class="size-3.5" />
                            <span>Hubungkan Discord</span>
                        </Button>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-muted/30 p-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <img
                                v-if="discordStatus.discord_avatar_url"
                                :src="discordStatus.discord_avatar_url"
                                :alt="discordStatus.discord_username || 'Discord Avatar'"
                                class="size-9 shrink-0 rounded-full border border-border object-cover"
                            />
                            <div
                                v-else
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary"
                            >
                                DC
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">
                                    {{ discordStatus.discord_username || "Akun Discord" }}
                                </p>
                                <p class="truncate font-mono text-xs text-muted-foreground">
                                    ID: {{ discordStatus.discord_id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="!discordStatus.bot_enabled || sendingDiscordTest"
                                class="gap-1.5"
                                @click="handleTestDiscordDm"
                            >
                                <Send class="size-3.5" />
                                <span>{{ sendingDiscordTest ? "Mengirim..." : "Tes Kirim DM" }}</span>
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                :disabled="disconnectingDiscord"
                                class="gap-1.5 text-muted-foreground hover:text-destructive"
                                @click="handleDisconnectDiscord"
                            >
                                <Unlink class="size-3.5" />
                                <span>Putuskan</span>
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="discordStatus.dm_failed_reason"
                        class="rounded-lg border border-warning/40 bg-warning/10 p-3.5 space-y-2 text-xs"
                    >
                        <p class="font-semibold text-warning">
                            Pengiriman DM Discord Terhalang
                        </p>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ discordStatus.dm_failed_reason }}
                        </p>
                        <div v-if="discordStatus.invite_url" class="pt-1">
                            <Button
                                as="a"
                                :href="discordStatus.invite_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                variant="outline"
                                size="sm"
                                class="h-7 gap-1.5 text-xs"
                            >
                                <ExternalLink class="size-3.5" />
                                <span>Gabung Server Discord Komunitas</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 2. Followed Officers Card -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between gap-2">
                    <div class="space-y-1">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <BellRing class="size-4 text-primary" />
                            <span>Perwira yang Diikuti</span>
                        </CardTitle>
                        <CardDescription>
                            Atur kanal notifikasi (Lonceng In-App, Web Push, atau Discord DM) untuk setiap perwira yang Anda ikuti.
                        </CardDescription>
                    </div>
                    <Badge variant="secondary" class="font-mono">
                        {{ subscriptions.length }} diikuti
                    </Badge>
                </div>
            </CardHeader>

            <CardContent>
                <div
                    v-if="loadingSubs"
                    class="py-8 text-center text-xs text-muted-foreground"
                >
                    Memuat daftar langganan...
                </div>

                <div
                    v-else-if="subscriptions.length === 0"
                    class="rounded-lg border border-dashed border-border p-6 text-center space-y-1"
                >
                    <p class="text-sm font-medium text-foreground">
                        Belum ada perwira yang diikuti
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Pilih perwira di daftar bawah ini untuk mulai menerima notifikasi saat mereka live patroli.
                    </p>
                </div>

                <div v-else class="divide-y divide-border rounded-lg border border-border">
                    <div
                        v-for="sub in subscriptions"
                        :key="sub.officer_id"
                        class="flex flex-col gap-3 p-3.5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <OfficerAvatar
                                :src="sub.officer?.avatar_url"
                                :name="sub.officer?.officer_name || 'Officer'"
                                size="sm"
                                class="shrink-0"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">
                                    {{ sub.officer?.officer_name || `Officer #${sub.officer_id}` }}
                                </p>
                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <DeptBadge v-if="sub.officer?.department" :dept="sub.officer.department" />
                                    <span v-if="sub.officer?.callsign" class="font-mono">{{ sub.officer.callsign }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer">
                                <Switch
                                    :model-value="sub.notify_in_app"
                                    :disabled="busyOfficerIds.has(Number(sub.officer_id))"
                                    aria-label="Notifikasi In-App"
                                    @update:model-value="(v) => updateChannelPrefs(sub, { notify_in_app: v })"
                                />
                                <span>Lonceng</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer">
                                <Switch
                                    :model-value="sub.notify_web_push"
                                    :disabled="busyOfficerIds.has(Number(sub.officer_id))"
                                    aria-label="Notifikasi Web Push"
                                    @update:model-value="(v) => updateChannelPrefs(sub, { notify_web_push: v })"
                                />
                                <span>Push</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer">
                                <Switch
                                    :model-value="sub.notify_discord_dm ?? true"
                                    :disabled="busyOfficerIds.has(Number(sub.officer_id))"
                                    aria-label="Notifikasi Discord DM"
                                    @update:model-value="(v) => updateChannelPrefs(sub, { notify_discord_dm: v })"
                                />
                                <span>Discord</span>
                            </label>

                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                :disabled="busyOfficerIds.has(Number(sub.officer_id))"
                                class="h-8 px-2 text-xs text-muted-foreground hover:text-destructive"
                                @click="unfollowOfficer(sub.officer_id, sub.officer?.officer_name)"
                            >
                                <UserMinus class="size-3.5" />
                                <span>Berhenti</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 3. Browse & Follow Officers Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Bell class="size-4 text-primary" />
                    <span>Pilih Perwira untuk Diikuti</span>
                </CardTitle>
                <CardDescription>
                    Cari perwira berdasarkan nama, callsign, atau departemen lalu klik Ikuti.
                </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama perwira atau callsign..."
                            class="pl-9"
                        />
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <Button
                            v-for="dept in departments"
                            :key="dept"
                            type="button"
                            size="sm"
                            :variant="selectedDept === dept ? 'default' : 'outline'"
                            class="h-8 px-2.5 font-mono text-xs"
                            @click="selectedDept = dept"
                        >
                            {{ dept === "ALL" ? "Semua" : dept }}
                        </Button>
                    </div>
                </div>

                <Separator />

                <div
                    v-if="filteredRoster.length === 0"
                    class="py-6 text-center text-xs text-muted-foreground"
                >
                    Tidak ada perwira yang cocok dengan pencarian Anda.
                </div>

                <div v-else class="grid gap-2.5 sm:grid-cols-2 max-h-96 overflow-y-auto pr-1">
                    <div
                        v-for="officer in filteredRoster"
                        :key="officer.id"
                        :class="
                            cn(
                                'flex items-center justify-between gap-3 rounded-lg border p-3 transition-colors',
                                followedIdSet.has(Number(officer.id))
                                    ? 'border-primary/40 bg-primary/5'
                                    : 'border-border bg-card',
                            )
                        "
                    >
                        <div class="flex min-w-0 items-center gap-2.5">
                            <OfficerAvatar
                                :src="officer.avatar_url"
                                :name="officer.officer_name"
                                size="sm"
                                class="shrink-0"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-foreground">
                                    {{ officer.officer_name }}
                                </p>
                                <p class="truncate font-mono text-xs text-muted-foreground">
                                    {{ officer.department }} · {{ officer.callsign || "-" }}
                                </p>
                            </div>
                        </div>

                        <Button
                            v-if="followedIdSet.has(Number(officer.id))"
                            type="button"
                            variant="secondary"
                            size="sm"
                            :disabled="busyOfficerIds.has(Number(officer.id))"
                            class="h-8 shrink-0 gap-1 text-xs"
                            @click="unfollowOfficer(officer.id, officer.officer_name)"
                        >
                            <BellRing class="size-3.5 text-primary" />
                            <span>Mengikuti</span>
                        </Button>
                        <Button
                            v-else
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="busyOfficerIds.has(Number(officer.id))"
                            class="h-8 shrink-0 gap-1 text-xs"
                            @click="followOfficer(officer)"
                        >
                            <UserPlus class="size-3.5" />
                            <span>Ikuti</span>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
