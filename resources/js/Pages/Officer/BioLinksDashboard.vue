<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from "@/Components/ui/alert-dialog";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { NativeSelect, NativeSelectOption } from "@/Components/ui/native-select";
import { Switch } from "@/Components/ui/switch";
import TacticalDashboardHeader from "@/Components/TacticalDashboardHeader.vue";
import { Textarea } from "@/Components/ui/textarea";
import {
    OfficerAvatar,
    EmptyState,
} from "@/Components/tactical";
import {
    Coins,
    Eye,
    History,
    Link2,
    MessageCircle,
    MousePointerClick,
    Palette,
    Share2,
    Smartphone,
    Star,
    Users,
    ArrowUp,
    ArrowDown,
    Check,
    Copy,
    ExternalLink,
    Pencil,
    Plus,
    RefreshCw,
    ShieldAlert,
    ShieldCheck,
    Trash2,
    AlertTriangle,
} from "@lucide/vue";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        required: true,
    },
    otherOfficers: {
        type: Array,
        default: () => [],
    },
    themes: {
        type: Object,
        default: () => ({}),
    },
    buttonStyles: {
        type: Array,
        default: () => [],
    },
    allowedDomains: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({ total_views: 0, total_clicks: 0, links_count: 0 }),
    },
});

const page = usePage();
const activeTab = ref("links"); // 'links' | 'appearance' | 'history'

// Copy URL Notification
const copied = ref(false);
const publicUrl = computed(() => `${window.location.origin}/@${props.profile.slug}`);

const copyPublicUrl = async () => {
    try {
        await navigator.clipboard.writeText(publicUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    } catch {
        // fallback
    }
};

// Notification message banner
const statusMessage = ref("");
const errorMessage = ref("");

const flash = (msg, isError = false) => {
    if (isError) {
        errorMessage.value = msg;
        statusMessage.value = "";
    } else {
        statusMessage.value = msg;
        errorMessage.value = "";
    }
    setTimeout(() => {
        statusMessage.value = "";
        errorMessage.value = "";
    }, 4000);
};

// Links List Local Reactive State
const localLinks = ref([...(props.profile.links || [])]);

// Link Modal Form State
const isModalOpen = ref(false);
const editingLinkId = ref(null);
const linkToDelete = ref(null);
const linkForm = ref({
    title: "",
    subtitle: "",
    link_type: "custom",
    url: "",
    recommended_officer_id: "",
    badge_label: "",
    is_highlighted: false,
    icon: "external-link",
});
const isSavingLink = ref(false);

const openAddModal = () => {
    editingLinkId.value = null;
    linkForm.value = {
        title: "",
        subtitle: "",
        link_type: "donation",
        url: "",
        recommended_officer_id: "",
        badge_label: "",
        is_highlighted: false,
        icon: "external-link",
    };
    isModalOpen.value = true;
};

const openEditModal = (link) => {
    editingLinkId.value = link.id;
    linkForm.value = {
        title: link.title,
        subtitle: link.subtitle || "",
        link_type: link.link_type,
        url: link.url || "",
        recommended_officer_id: link.recommended_officer_id || "",
        badge_label: link.badge_label || "",
        is_highlighted: !!link.is_highlighted,
        icon: link.icon || "external-link",
    };
    isModalOpen.value = true;
};

// Password Confirmation State for Donation Links (T-03)
const isPasswordConfirmModalOpen = ref(false);
const confirmPasswordInput = ref("");
const passwordConfirmError = ref("");
const isSubmittingPassword = ref(false);

// Save link (create or update)
const saveLink = async () => {
    isSavingLink.value = true;
    passwordConfirmError.value = "";
    try {
        if (editingLinkId.value) {
            const res = await axios.put(`/api/v1/officer/bio-links/${editingLinkId.value}`, linkForm.value);
            const idx = localLinks.value.findIndex((l) => l.id === editingLinkId.value);
            if (idx !== -1) {
                localLinks.value[idx] = res.data.link;
            }
            flash(res.data.message);
        } else {
            const res = await axios.post("/api/v1/officer/bio-links", linkForm.value);
            localLinks.value.push(res.data.link);
            flash(res.data.message);
        }
        isModalOpen.value = false;
        isPasswordConfirmModalOpen.value = false;
        confirmPasswordInput.value = "";
        delete linkForm.value.password_confirmation_inline;
    } catch (err) {
        if (err.response?.status === 423 || err.response?.data?.error === "password_confirmation_required") {
            isPasswordConfirmModalOpen.value = true;
            passwordConfirmError.value = "Konfirmasi password diperlukan untuk mengamankan link donasi.";
        } else if (err.response?.data?.error === "password_mismatch") {
            passwordConfirmError.value = "Password salah. Silakan coba lagi.";
            isPasswordConfirmModalOpen.value = true;
        } else {
            flash(err.response?.data?.message || "Gagal menyimpan tautan.", true);
        }
    } finally {
        isSavingLink.value = false;
        isSubmittingPassword.value = false;
    }
};

const submitPasswordConfirmation = async () => {
    if (!confirmPasswordInput.value) {
        passwordConfirmError.value = "Masukkan password akun Anda.";
        return;
    }
    isSubmittingPassword.value = true;
    linkForm.value.password_confirmation_inline = confirmPasswordInput.value;
    await saveLink();
};

// Toggle active
const toggleLink = async (link) => {
    try {
        const res = await axios.patch(`/api/v1/officer/bio-links/${link.id}/toggle`);
        link.is_active = res.data.is_active;
        flash(res.data.message);
    } catch (err) {
        flash(err.response?.data?.message || "Gagal mengubah status tautan.", true);
    }
};

// Delete link via AlertDialog
const promptDeleteLink = (link) => {
    linkToDelete.value = link;
};

const confirmDeleteLink = async () => {
    const link = linkToDelete.value;
    if (!link) return;
    linkToDelete.value = null;

    try {
        const res = await axios.delete(`/api/v1/officer/bio-links/${link.id}`);
        localLinks.value = localLinks.value.filter((l) => l.id !== link.id);
        flash(res.data.message);
    } catch (err) {
        flash(err.response?.data?.message || "Gagal menghapus tautan.", true);
    }
};

// Move Link Up / Down
const moveLink = async (index, direction) => {
    const targetIdx = index + direction;
    if (targetIdx < 0 || targetIdx >= localLinks.value.length) return;

    const list = [...localLinks.value];
    const temp = list[index];
    list[index] = list[targetIdx];
    list[targetIdx] = temp;
    localLinks.value = list;

    try {
        const orderedIds = list.map((l) => l.id);
        await axios.post("/api/v1/officer/bio-links/reorder", { ordered_ids: orderedIds });
    } catch (err) {
        flash("Gagal menyimpan urutan.", true);
    }
};

// Appearance Form
const appearanceForm = ref({
    display_title: props.profile.display_title || props.officer.officer_name,
    bio_tagline: props.profile.bio_tagline || "",
    theme_preset: props.profile.theme_preset || "default_glass",
    button_style: props.profile.button_style || "glass_tactical",
    accent_color: props.profile.accent_color || "#06b6d4",
    show_live_stream_banner: props.profile.show_live_stream_banner !== false,
    is_active: props.profile.is_active !== false,
});
const isSavingAppearance = ref(false);

const saveAppearance = async () => {
    isSavingAppearance.value = true;
    try {
        const res = await axios.put("/api/v1/officer/bio-profile", appearanceForm.value);
        flash(res.data.message);
    } catch (err) {
        flash(err.response?.data?.message || "Gagal menyimpan pengaturan tampilan.", true);
    } finally {
        isSavingAppearance.value = false;
    }
};

// Audit History Tab
const historyLogs = ref([]);
const isLoadingHistory = ref(false);

const loadHistory = async () => {
    isLoadingHistory.value = true;
    try {
        const res = await axios.get("/api/v1/officer/bio-links/history");
        historyLogs.value = res.data.logs || [];
    } catch {
        // ignore
    } finally {
        isLoadingHistory.value = false;
    }
};

const onTabChange = (tab) => {
    activeTab.value = tab;
    if (tab === "history" && historyLogs.value.length === 0) {
        loadHistory();
    }
};

// Helper for accepted domains text
const domainHint = computed(() => {
    const t = linkForm.value.link_type;
    if (props.allowedDomains[t]) {
        return "Domain yang diizinkan: " + props.allowedDomains[t].join(", ");
    }
    return "Domain bebas (kecuali denylist terlarang). Gunakan https://";
});

const LINK_TYPES = {
    donation: { label: "Donasi", icon: Coins },
    discord: { label: "Discord", icon: MessageCircle },
    social: { label: "Media sosial", icon: Share2 },
    officer_recommendation: { label: "Rekan patroli", icon: Users },
    custom: { label: "Tautan", icon: Link2 },
};
const linkTypeIcon = (type) => (LINK_TYPES[type] || LINK_TYPES.custom).icon;
const linkTypeLabel = (type) => (LINK_TYPES[type] || LINK_TYPES.custom).label;

// Audit actions look like "bio_link.created"; show them as plain sentences.
const ACTION_LABELS = {
    created: "Tautan ditambahkan",
    updated: "Tautan diubah",
    deleted: "Tautan dihapus",
    toggled: "Visibilitas tautan diubah",
    reordered: "Urutan tautan diubah",
};
const actionLabel = (action = "") => {
    const verb = action.split(".").pop();
    if (ACTION_LABELS[verb]) return ACTION_LABELS[verb];
    const text = action.replace(/[._]/g, " ").trim();
    return text.charAt(0).toUpperCase() + text.slice(1);
};

const rtf = new Intl.RelativeTimeFormat("id", { numeric: "auto" });
const RELATIVE_UNITS = [
    ["year", 31536000],
    ["month", 2592000],
    ["week", 604800],
    ["day", 86400],
    ["hour", 3600],
    ["minute", 60],
];
const relativeTime = (iso) => {
    const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000);
    for (const [unit, size] of RELATIVE_UNITS) {
        if (Math.abs(seconds) >= size) return rtf.format(Math.round(seconds / size), unit);
    }
    return rtf.format(seconds, "second");
};
</script>

<template>
    <Head title="Kelola Tactical Bio-Link — Police Duty" />

    <div class="min-h-screen bg-background text-foreground flex flex-col font-sans selection:bg-primary selection:text-primary-foreground">
        <TacticalDashboardHeader />

        <!-- MAIN CONTENT WORKSPACE -->
        <main class="mx-auto grid w-full max-w-6xl flex-1 grid-cols-1 gap-8 px-4 pb-16 pt-8 sm:px-6 lg:grid-cols-12">
            <!-- LEFT COLUMN: CONTROLS & TABS (7 cols) -->
            <div class="flex flex-col gap-6 lg:col-span-7">
                <!-- Page title -->
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Bio-link saya</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Atur tautan yang tampil di halaman publik Anda.</p>
                    </div>
                    <Button as-child variant="outline" size="sm" class="gap-1.5">
                        <a :href="`/@${profile.slug}`" target="_blank" rel="noopener">
                            <ExternalLink class="size-4" /> Lihat halaman
                        </a>
                    </Button>
                </div>

                <!-- STATUS NOTIFICATION BANNERS -->
                <Alert v-if="statusMessage" class="flex items-center gap-2 border-success/40 bg-success/10 p-3.5 text-sm text-success">
                    <Check class="size-4 shrink-0" />
                    <AlertDescription>{{ statusMessage }}</AlertDescription>
                </Alert>
                <Alert v-if="errorMessage" variant="destructive" class="flex items-center gap-2 p-3.5 text-sm">
                    <AlertTriangle class="size-4 shrink-0" />
                    <AlertDescription>{{ errorMessage }}</AlertDescription>
                </Alert>

                <!-- SUSPENSION LOCKDOWN BANNER -->
                <Alert v-if="profile.is_suspended" class="flex items-start gap-3 border-warning/60 bg-warning/15 p-4 text-warning">
                    <AlertTriangle class="mt-0.5 size-5 shrink-0" />
                    <div class="space-y-1">
                        <AlertTitle class="text-sm font-bold text-warning">Halaman bio-link dinonaktifkan sementara</AlertTitle>
                        <AlertDescription class="leading-relaxed text-foreground">
                            Alasan: <strong>{{ profile.suspension_reason || "Sedang dalam peninjauan moderasi Komando SASP." }}</strong>
                        </AlertDescription>
                        <p class="text-xs text-warning/90">
                            Dasbor dalam mode baca saja. Penambahan dan pengeditan tautan dikunci sampai pembekuan dicabut.
                        </p>
                    </div>
                </Alert>

                <!-- PROFILE SUMMARY -->
                <Card class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <OfficerAvatar
                            :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                            :name="officer.officer_name"
                            size="lg"
                            class="shrink-0"
                        />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-lg font-bold text-foreground">{{ officer.officer_name }}</h2>
                                <Badge variant="info" class="gap-1"><ShieldCheck class="size-3" /> Terverifikasi</Badge>
                            </div>
                            <p class="mt-0.5 text-sm text-muted-foreground">
                                {{ [officer.department, officer.rank].filter(Boolean).join(" · ") }}
                                <span class="font-mono">· {{ officer.callsign }}</span>
                            </p>
                            <div class="mt-2 flex items-center gap-1">
                                <a
                                    :href="`/@${profile.slug}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="truncate font-mono text-sm text-primary hover:underline"
                                >/@{{ profile.slug }}</a>
                                <Button
                                    variant="ghost"
                                    size="icon-xs"
                                    :aria-label="copied ? 'URL tersalin' : 'Salin URL'"
                                    :title="copied ? 'Tersalin' : 'Salin URL'"
                                    @click="copyPublicUrl"
                                >
                                    <Check v-if="copied" class="text-success" />
                                    <Copy v-else />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-6 border-t border-border pt-4 sm:border-l sm:border-t-0 sm:pl-6 sm:pt-0">
                        <div>
                            <p class="flex items-center gap-1 text-xs text-muted-foreground"><Eye class="size-3.5" /> Tayangan</p>
                            <p class="mt-0.5 font-mono text-xl font-bold text-foreground">{{ stats.total_views }}</p>
                        </div>
                        <div>
                            <p class="flex items-center gap-1 text-xs text-muted-foreground"><MousePointerClick class="size-3.5" /> Klik</p>
                            <p class="mt-0.5 font-mono text-xl font-bold text-foreground">{{ stats.total_clicks }}</p>
                        </div>
                    </div>
                </Card>

                <!-- NAVIGATION TABS -->
                <div class="flex items-center gap-6 border-b border-border" role="tablist">
                    <button
                        v-for="tab in [
                            { key: 'links', label: 'Tautan', icon: Link2, count: localLinks.length },
                            { key: 'appearance', label: 'Tampilan & tema', icon: Palette },
                            { key: 'history', label: 'Riwayat', icon: History },
                        ]"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab.key"
                        :class="[
                            '-mb-px flex items-center gap-1.5 border-b-2 pb-2 text-sm font-semibold transition-colors',
                            activeTab === tab.key
                                ? 'border-foreground text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        ]"
                        @click="onTabChange(tab.key)"
                    >
                        <component :is="tab.icon" class="size-4" />
                        {{ tab.label }}
                        <span v-if="tab.count !== undefined" class="text-xs font-normal text-muted-foreground">{{ tab.count }}</span>
                    </button>
                </div>

                <!-- TAB 1: LINKS MANAGEMENT -->
                <div v-show="activeTab === 'links'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ localLinks.length }} dari 30 tautan</span>
                        <Button v-if="!profile.is_suspended" size="sm" class="gap-1.5" @click="openAddModal">
                            <Plus class="size-4" /> Tambah tautan
                        </Button>
                    </div>

                    <!-- LINKS LIST -->
                    <Card v-if="localLinks.length > 0" class="gap-0 divide-y divide-border overflow-hidden py-0">
                        <div
                            v-for="(link, idx) in localLinks"
                            :key="link.id"
                            class="group flex items-center gap-3 px-3 py-3 transition-colors hover:bg-accent/30 sm:px-4"
                        >
                            <!-- Reorder -->
                            <div class="flex shrink-0 flex-col">
                                <Button
                                    variant="ghost"
                                    size="icon-xs"
                                    aria-label="Naikkan urutan tautan"
                                    :disabled="idx === 0 || profile.is_suspended"
                                    class="h-5"
                                    @click="moveLink(idx, -1)"
                                >
                                    <ArrowUp />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-xs"
                                    aria-label="Turunkan urutan tautan"
                                    :disabled="idx === localLinks.length - 1 || profile.is_suspended"
                                    class="h-5"
                                    @click="moveLink(idx, 1)"
                                >
                                    <ArrowDown />
                                </Button>
                            </div>

                            <!-- Type icon -->
                            <div
                                :class="[
                                    'flex size-9 shrink-0 items-center justify-center rounded-md',
                                    link.link_type === 'donation' ? 'bg-warning/15 text-warning' : 'bg-muted text-muted-foreground',
                                ]"
                            >
                                <component :is="linkTypeIcon(link.link_type)" class="size-4" />
                            </div>

                            <!-- Details -->
                            <div :class="['min-w-0 flex-1', !link.is_active && 'opacity-50']">
                                <div class="flex items-center gap-1.5">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ link.title }}</p>
                                    <Star v-if="link.is_highlighted" class="size-3.5 shrink-0 fill-warning text-warning" aria-label="Disorot" />
                                    <Badge v-if="link.badge_label" variant="secondary" class="shrink-0">{{ link.badge_label }}</Badge>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                    {{ linkTypeLabel(link.link_type) }} · {{ link.subtitle || link.url_host || link.url || "Rekan patroli" }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex shrink-0 items-center gap-1">
                                <Switch
                                    :model-value="!!link.is_active"
                                    :disabled="profile.is_suspended"
                                    :aria-label="link.is_active ? 'Sembunyikan tautan' : 'Tampilkan tautan'"
                                    :title="link.is_active ? 'Tampil' : 'Disembunyikan'"
                                    class="mr-1"
                                    @update:model-value="toggleLink(link)"
                                />
                                <Button
                                    v-if="!profile.is_suspended"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Edit tautan"
                                    title="Edit tautan"
                                    @click="openEditModal(link)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    v-if="!profile.is_suspended"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Hapus tautan"
                                    title="Hapus tautan"
                                    class="text-muted-foreground hover:bg-destructive/15 hover:text-destructive"
                                    @click="promptDeleteLink(link)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </div>
                    </Card>

                    <EmptyState
                        v-else
                        title="Belum ada tautan"
                        description="Tambahkan Saweria, Discord, media sosial, atau rekan patroli Anda."
                    />
                </div>

                <!-- TAB 2: APPEARANCE & THEME CONFIG -->
                <div v-show="activeTab === 'appearance'" class="space-y-6">
                    <Card class="gap-4 p-5">
                        <div>
                            <h3 class="text-base font-semibold text-foreground">Profil</h3>
                            <p class="text-sm text-muted-foreground">Nama dan pesan singkat di bagian atas halaman Anda.</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="bio-display-title">Nama tampilan</Label>
                            <Input id="bio-display-title" v-model="appearanceForm.display_title" type="text" placeholder="Contoh: Ofc. John Doe" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="bio-tagline">Pesan singkat</Label>
                            <Textarea
                                id="bio-tagline"
                                v-model="appearanceForm.bio_tagline"
                                rows="2"
                                class="resize-none"
                                placeholder="Motto patroli atau jadwal streaming Anda"
                            />
                            <p class="text-right text-xs text-muted-foreground">{{ (appearanceForm.bio_tagline || "").length }}/255</p>
                        </div>
                    </Card>

                    <Card class="gap-5 p-5">
                        <div>
                            <h3 class="text-base font-semibold text-foreground">Gaya</h3>
                            <p class="text-sm text-muted-foreground">Perubahan langsung terlihat di pratinjau.</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Tema</Label>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2" role="radiogroup">
                                <button
                                    v-for="(theme, key) in themes"
                                    :key="key"
                                    type="button"
                                    role="radio"
                                    :aria-checked="appearanceForm.theme_preset === key"
                                    :class="[
                                        'flex items-start gap-3 rounded-lg border p-3 text-left transition-colors',
                                        appearanceForm.theme_preset === key
                                            ? 'border-primary bg-primary/10'
                                            : 'border-border hover:border-input hover:bg-accent/30',
                                    ]"
                                    @click="appearanceForm.theme_preset = key; appearanceForm.accent_color = theme.accent"
                                >
                                    <span class="mt-0.5 size-4 shrink-0 rounded-full ring-2 ring-border" :style="{ backgroundColor: theme.accent }" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-foreground">{{ theme.name }}</span>
                                        <span class="block text-xs leading-snug text-muted-foreground">{{ theme.description }}</span>
                                    </span>
                                    <Check v-if="appearanceForm.theme_preset === key" class="ml-auto size-4 shrink-0 text-primary" />
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="bio-button-style">Gaya tombol</Label>
                                <NativeSelect id="bio-button-style" v-model="appearanceForm.button_style" class="w-full">
                                    <NativeSelectOption v-for="style in buttonStyles" :key="style" :value="style">
                                        {{ style.replace("_", " ") }}
                                    </NativeSelectOption>
                                </NativeSelect>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="bio-accent">Warna aksen</Label>
                                <div class="flex items-center gap-2">
                                    <input
                                        id="bio-accent"
                                        v-model="appearanceForm.accent_color"
                                        type="color"
                                        aria-label="Pilih warna aksen"
                                        class="size-9 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-0.5"
                                    />
                                    <Input v-model="appearanceForm.accent_color" type="text" aria-label="Kode warna aksen" class="font-mono uppercase" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t border-border pt-4">
                            <div>
                                <p class="text-sm font-medium text-foreground">Banner patroli live</p>
                                <p class="text-xs text-muted-foreground">Muncul otomatis saat siaran YouTube Anda sedang aktif.</p>
                            </div>
                            <Switch
                                :model-value="appearanceForm.show_live_stream_banner"
                                aria-label="Tampilkan banner patroli live"
                                @update:model-value="(val) => (appearanceForm.show_live_stream_banner = val)"
                            />
                        </div>
                    </Card>

                    <div class="flex justify-end">
                        <Button :disabled="isSavingAppearance || profile.is_suspended" @click="saveAppearance">
                            {{ isSavingAppearance ? "Menyimpan…" : "Simpan tampilan" }}
                        </Button>
                    </div>
                </div>

                <!-- TAB 3: AUDIT HISTORY -->
                <div v-show="activeTab === 'history'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Perubahan tautan dan profil Anda</span>
                        <Button variant="ghost" size="sm" class="gap-1.5" :disabled="isLoadingHistory" @click="loadHistory">
                            <RefreshCw :class="['size-4', isLoadingHistory && 'motion-safe:animate-spin']" /> Muat ulang
                        </Button>
                    </div>

                    <Card v-if="historyLogs.length > 0" class="gap-0 divide-y divide-border overflow-hidden py-0">
                        <div v-for="log in historyLogs" :key="log.id" class="flex items-start gap-3 px-4 py-3">
                            <History class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="truncate text-sm font-medium text-foreground">{{ actionLabel(log.action) }}</p>
                                    <time
                                        :datetime="log.created_at"
                                        :title="new Date(log.created_at).toLocaleString('id-ID')"
                                        class="shrink-0 text-xs text-muted-foreground"
                                    >{{ relativeTime(log.created_at) }}</time>
                                </div>
                                <p v-if="log.reason" class="mt-0.5 text-xs text-muted-foreground">{{ log.reason }}</p>
                            </div>
                        </div>
                    </Card>

                    <EmptyState
                        v-else-if="!isLoadingHistory"
                        title="Belum ada riwayat"
                        description="Setiap perubahan tautan atau tampilan akan tercatat di sini."
                    />
                </div>
            </div>

            <!-- RIGHT COLUMN: LIVE PHONE PREVIEW -->
            <div class="flex flex-col items-center lg:sticky lg:top-24 lg:col-span-5 lg:self-start">
                <p class="mb-3 flex items-center gap-1.5 text-sm text-muted-foreground">
                    <Smartphone class="size-4" /> Pratinjau langsung
                </p>

                <div
                    class="relative flex h-[600px] w-full max-w-[340px] flex-col overflow-hidden rounded-[36px] border-[6px] border-muted bg-background shadow-2xl"
                    :style="{ '--preview-accent': appearanceForm.accent_color || 'var(--color-primary)' }"
                >
                    <div class="flex h-6 shrink-0 items-center justify-center">
                        <div class="h-3.5 w-20 rounded-full bg-muted" />
                    </div>

                    <div class="scrollbar-none flex-1 space-y-4 overflow-y-auto px-4 py-4 text-center">
                        <div class="flex flex-col items-center">
                            <OfficerAvatar
                                :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                                :name="officer.officer_name"
                                size="lg"
                                class="mb-2"
                            />
                            <h4 class="text-base font-bold leading-tight text-foreground">
                                {{ appearanceForm.display_title || officer.officer_name }}
                            </h4>
                            <p class="text-xs font-medium" :style="{ color: 'var(--preview-accent)' }">{{ officer.streamer_name }}</p>
                            <p v-if="appearanceForm.bio_tagline" class="mt-1.5 max-w-[240px] text-xs leading-relaxed text-muted-foreground">
                                {{ appearanceForm.bio_tagline }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="l in localLinks.filter((x) => x.is_active)"
                                :key="l.id"
                                class="flex items-center gap-2.5 rounded-lg border bg-card px-3 py-2.5 text-left"
                                :style="l.is_highlighted
                                    ? { borderColor: 'var(--preview-accent)', backgroundColor: 'color-mix(in oklab, var(--preview-accent) 14%, transparent)' }
                                    : { borderColor: 'var(--color-border)' }"
                            >
                                <component :is="linkTypeIcon(l.link_type)" class="size-4 shrink-0 text-muted-foreground" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ l.title }}</p>
                                    <p v-if="l.subtitle" class="truncate text-xs text-muted-foreground">{{ l.subtitle }}</p>
                                </div>
                                <Badge v-if="l.badge_label" variant="secondary" class="shrink-0">{{ l.badge_label }}</Badge>
                            </div>

                            <p v-if="localLinks.filter((x) => x.is_active).length === 0" class="py-8 text-xs text-muted-foreground">
                                Belum ada tautan yang ditampilkan.
                            </p>
                        </div>
                    </div>

                    <div class="flex h-5 shrink-0 items-center justify-center">
                        <div class="h-1 w-24 rounded-full bg-muted" />
                    </div>
                </div>
            </div>
        </main>

        <!-- ADD / EDIT LINK MODAL -->
        <Dialog :open="isModalOpen" @update:open="(val) => (isModalOpen = val)">
            <DialogContent class="gap-5 sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingLinkId ? "Edit tautan" : "Tambah tautan" }}</DialogTitle>
                    <DialogDescription>Tautan tampil sebagai tombol di halaman publik Anda.</DialogDescription>
                </DialogHeader>

                <!-- Link type -->
                <div class="space-y-2">
                    <Label>Jenis tautan</Label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" role="radiogroup">
                        <button
                            v-for="(meta, type) in LINK_TYPES"
                            :key="type"
                            type="button"
                            role="radio"
                            :aria-checked="linkForm.link_type === type"
                            :class="[
                                'flex items-center gap-2 rounded-md border px-3 py-2 text-left text-sm transition-colors',
                                linkForm.link_type === type
                                    ? 'border-primary bg-primary/10 text-foreground'
                                    : 'border-border text-muted-foreground hover:border-input hover:text-foreground',
                            ]"
                            @click="linkForm.link_type = type"
                        >
                            <component :is="meta.icon" class="size-4 shrink-0" />
                            <span class="truncate">{{ meta.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Patrol partner -->
                <div v-if="linkForm.link_type === 'officer_recommendation'" class="space-y-1.5">
                    <Label for="link-partner">Rekan patroli</Label>
                    <NativeSelect id="link-partner" v-model="linkForm.recommended_officer_id" class="w-full">
                        <NativeSelectOption value="">Pilih perwira</NativeSelectOption>
                        <NativeSelectOption v-for="o in otherOfficers" :key="o.id" :value="o.id">
                            {{ o.officer_name }} · {{ o.department }} {{ o.callsign }}
                        </NativeSelectOption>
                    </NativeSelect>
                </div>

                <!-- URL -->
                <div v-else class="space-y-1.5">
                    <Label for="link-url">URL</Label>
                    <Input id="link-url" v-model="linkForm.url" type="url" class="font-mono" placeholder="https://" />
                    <p class="text-xs text-muted-foreground">{{ domainHint }}</p>
                </div>

                <div class="space-y-1.5">
                    <Label for="link-title">Judul tombol</Label>
                    <Input id="link-title" v-model="linkForm.title" type="text" placeholder="Contoh: Dukung patroli via Saweria" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="link-subtitle">Keterangan <span class="font-normal text-muted-foreground">(opsional)</span></Label>
                        <Input id="link-subtitle" v-model="linkForm.subtitle" type="text" placeholder="Contoh: Min. 10rb TTS on" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="link-badge">Badge <span class="font-normal text-muted-foreground">(opsional)</span></Label>
                        <Input id="link-badge" v-model="linkForm.badge_label" type="text" placeholder="Contoh: BARU" />
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 rounded-md border border-border px-3 py-2.5">
                    <div>
                        <p class="text-sm font-medium text-foreground">Sorot tautan ini</p>
                        <p class="text-xs text-muted-foreground">Diberi warna aksen agar lebih menonjol.</p>
                    </div>
                    <Switch
                        :model-value="linkForm.is_highlighted"
                        aria-label="Sorot tautan ini"
                        @update:model-value="(val) => (linkForm.is_highlighted = val)"
                    />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isModalOpen = false">Batal</Button>
                    <Button :disabled="isSavingLink" @click="saveLink">
                        {{ isSavingLink ? "Menyimpan…" : editingLinkId ? "Simpan perubahan" : "Tambah tautan" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- PASSWORD CONFIRMATION MODAL (T-03) -->
        <Dialog :open="isPasswordConfirmModalOpen" @update:open="(val) => (isPasswordConfirmModalOpen = val)">
            <DialogContent class="sm:max-w-sm bg-card border-warning/40 text-left space-y-4">
                <DialogHeader class="border-b border-border pb-2">
                    <DialogTitle class="flex items-center gap-2 text-warning font-mono text-sm font-bold">
                        <ShieldAlert class="w-4 h-4" />
                        <span>KONFIRMASI KEAMANAN AKUN</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs text-muted-foreground font-sans leading-relaxed">
                        Untuk melindungi akun Anda dari pembajakan tautan donasi finansial, silakan masukkan password akun Anda untuk melanjutkan:
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-1">
                    <Input
                        v-model="confirmPasswordInput"
                        type="password"
                        @keyup.enter="submitPasswordConfirmation"
                        placeholder="Masukkan password akun Anda..."
                        class="text-xs font-mono"
                        autofocus
                    />
                    <p v-if="passwordConfirmError" class="text-xs font-mono text-destructive pt-1">
                        {{ passwordConfirmError }}
                    </p>
                </div>

                <DialogFooter class="pt-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="isPasswordConfirmModalOpen = false; confirmPasswordInput = '';"
                        class="text-xs font-mono"
                    >
                        Batal
                    </Button>
                    <Button
                        size="sm"
                        @click="submitPasswordConfirmation"
                        :disabled="isSubmittingPassword"
                        class="font-mono font-bold text-xs uppercase tracking-wider"
                    >
                        {{ isSubmittingPassword ? "Memverifikasi..." : "Konfirmasi & Simpan" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- DELETE LINK ALERT DIALOG -->
        <AlertDialog :open="linkToDelete !== null" @update:open="(val) => { if (!val) linkToDelete = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Tautan?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Hapus tautan '{{ linkToDelete?.title }}' secara permanen?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="linkToDelete = null">Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmDeleteLink">
                        Hapus Permanen
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
