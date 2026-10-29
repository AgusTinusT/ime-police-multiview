<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
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
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";
import {
    OfficerAvatar,
    StatTile,
    EmptyState,
} from "@/Components/tactical";
import {
    ArrowLeft,
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
</script>

<template>
    <Head title="Kelola Tactical Bio-Link — Police Duty" />

    <div class="min-h-screen bg-background text-foreground flex flex-col font-sans selection:bg-primary selection:text-primary-foreground">
        <!-- TOP HEADER BAR -->
        <header class="border-b border-border bg-card/80 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Button as-child variant="ghost" size="sm" class="font-mono font-bold text-xs uppercase text-primary">
                    <Link href="/" class="flex items-center gap-1.5">
                        <ArrowLeft class="w-4 h-4" />
                        <span>SASP Command</span>
                    </Link>
                </Button>
                <span class="text-muted-foreground font-mono">/</span>
                <span class="text-xs font-mono font-bold uppercase text-foreground tracking-wider">
                    Tactical Bio-Link Hub
                </span>
            </div>

            <!-- Officer Profile Seal -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-xl bg-muted border border-border font-mono text-xs">
                    <span class="w-2 h-2 rounded-full bg-success"></span>
                    <span class="font-bold text-foreground">{{ officer.officer_name }}</span>
                    <span class="text-muted-foreground">#{{ officer.callsign }}</span>
                </div>

                <Button as-child size="sm" class="font-mono font-bold text-xs uppercase tracking-wider">
                    <a
                        :href="`/@${profile.slug}`"
                        target="_blank"
                        class="flex items-center gap-1.5"
                    >
                        <span>Buka Bio</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                </Button>
            </div>
        </header>

        <!-- MAIN CONTENT WORKSPACE -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT COLUMN: CONTROLS & TABS (7 cols) -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <!-- STATUS NOTIFICATION BANNERS -->
                <Alert v-if="statusMessage" class="p-3.5 border-success/40 bg-success/10 text-success text-xs font-mono flex items-center gap-2">
                    <Check class="w-4 h-4 shrink-0" />
                    <AlertDescription>{{ statusMessage }}</AlertDescription>
                </Alert>
                <Alert v-if="errorMessage" variant="destructive" class="p-3.5 text-xs font-mono flex items-center gap-2">
                    <AlertTriangle class="w-4 h-4 shrink-0" />
                    <AlertDescription>{{ errorMessage }}</AlertDescription>
                </Alert>

                <!-- SUSPENSION LOCKDOWN BANNER -->
                <Alert
                    v-if="profile.is_suspended"
                    class="p-4 rounded-2xl bg-warning/15 border-2 border-warning/60 text-warning text-xs shadow-xl flex items-start gap-3"
                >
                    <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <AlertTitle class="font-bold font-mono uppercase text-warning text-sm">
                            HALAMAN BIO-LINK DINONAKTIFKAN SEMENTARA (SUSPENDED)
                        </AlertTitle>
                        <AlertDescription class="leading-relaxed text-foreground">
                            Alasan: <strong>{{ profile.suspension_reason || "Sedang dalam peninjauan moderasi Komando SASP." }}</strong>
                        </AlertDescription>
                        <p class="text-xs text-warning/90">
                            Dasbor saat ini dalam mode read-only. Penambahan dan pengeditan tautan dikunci sampai pembekuan dicabut.
                        </p>
                    </div>
                </Alert>

                <!-- HERO SUMMARY CARD -->
                <Card class="p-5 rounded-2xl bg-card border-border shadow-xl flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <OfficerAvatar
                            :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                            :name="officer.officer_name"
                            :department="officer.department"
                            size="lg"
                        />
                        <div class="text-left">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-base font-bold font-mono uppercase text-foreground">
                                    {{ officer.officer_name }}
                                </h2>
                                <Badge variant="info" class="font-mono text-xs font-bold uppercase">
                                    VERIFIED
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground font-mono mt-0.5">
                                {{ officer.department }} • {{ officer.rank }} • Callsign #{{ officer.callsign }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <code class="px-2 py-1 rounded-sm bg-muted border border-border text-primary font-mono text-xs select-all">
                                    /@{{ profile.slug }}
                                </code>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    @click="copyPublicUrl"
                                    class="h-7 text-xs font-mono"
                                >
                                    <Check v-if="copied" class="w-3.5 h-3.5 mr-1 text-success" />
                                    <Copy v-else class="w-3.5 h-3.5 mr-1" />
                                    {{ copied ? "Tersalin" : "Salin URL" }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- STATS BADGES -->
                    <div class="grid grid-cols-2 gap-2 text-center w-full sm:w-auto">
                        <StatTile
                            label="Tayangan"
                            :value="stats.total_views"
                        />
                        <StatTile
                            label="Klik Tautan"
                            :value="stats.total_clicks"
                        />
                    </div>
                </Card>

                <!-- NAVIGATION TABS -->
                <div class="flex items-center gap-2 border-b border-border pb-2 font-mono text-xs font-bold">
                    <Button
                        :variant="activeTab === 'links' ? 'default' : 'ghost'"
                        size="sm"
                        @click="onTabChange('links')"
                        class="rounded-xl flex items-center gap-2"
                    >
                        <span>🔗 Daftar Tautan</span>
                        <Badge :variant="activeTab === 'links' ? 'secondary' : 'outline'" class="text-xs">
                            {{ localLinks.length }}
                        </Badge>
                    </Button>

                    <Button
                        :variant="activeTab === 'appearance' ? 'default' : 'ghost'"
                        size="sm"
                        @click="onTabChange('appearance')"
                        class="rounded-xl flex items-center gap-2"
                    >
                        <span>🎨 Tampilan & Tema</span>
                    </Button>

                    <Button
                        :variant="activeTab === 'history' ? 'default' : 'ghost'"
                        size="sm"
                        @click="onTabChange('history')"
                        class="rounded-xl flex items-center gap-2"
                    >
                        <span>📜 Riwayat Audit</span>
                    </Button>
                </div>

                <!-- TAB 1: LINKS MANAGEMENT -->
                <div v-show="activeTab === 'links'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono uppercase text-muted-foreground">
                            Kelola Tautan (Maks. 30)
                        </span>
                        <Button
                            v-if="!profile.is_suspended"
                            size="sm"
                            @click="openAddModal"
                            class="font-mono font-bold text-xs uppercase tracking-wider flex items-center gap-1.5"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Tambah Tautan</span>
                        </Button>
                    </div>

                    <!-- LINKS LIST -->
                    <div v-if="localLinks.length > 0" class="space-y-2.5">
                        <Card
                            v-for="(link, idx) in localLinks"
                            :key="link.id"
                            class="p-3.5 rounded-2xl bg-card border-border hover:border-primary/40 transition flex flex-row items-center justify-between gap-3 shadow-md"
                            :class="{ 'opacity-60': !link.is_active }"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Reorder Buttons -->
                                <div class="flex flex-col gap-0.5 shrink-0">
                                    <Button
                                        variant="outline"
                                        size="icon-sm"
                                        aria-label="Naikkan urutan tautan"
                                        @click="moveLink(idx, -1)"
                                        :disabled="idx === 0 || profile.is_suspended"
                                        class="w-6 h-5 rounded-sm p-0"
                                    >
                                        <ArrowUp class="w-3 h-3" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="icon-sm"
                                        aria-label="Turunkan urutan tautan"
                                        @click="moveLink(idx, 1)"
                                        :disabled="idx === localLinks.length - 1 || profile.is_suspended"
                                        class="w-6 h-5 rounded-sm p-0"
                                    >
                                        <ArrowDown class="w-3 h-3" />
                                    </Button>
                                </div>

                                <!-- Icon & Type -->
                                <div class="w-9 h-9 rounded-xl bg-muted border border-border flex items-center justify-center text-base shrink-0">
                                    <span v-if="link.link_type === 'donation'">🪙</span>
                                    <span v-else-if="link.link_type === 'officer_recommendation'">🚓</span>
                                    <span v-else-if="link.link_type === 'discord'">💬</span>
                                    <span v-else-if="link.link_type === 'social'">▶️</span>
                                    <span v-else>🔗</span>
                                </div>

                                <!-- Details -->
                                <div class="min-w-0 text-left">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold font-mono text-foreground truncate">
                                            {{ link.title }}
                                        </h4>
                                        <Badge
                                            :variant="link.link_type === 'donation' ? 'warning' : 'secondary'"
                                            class="text-xs font-mono font-bold uppercase"
                                        >
                                            {{ link.link_type }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground font-mono truncate mt-0.5">
                                        {{ link.subtitle || link.url || 'Rekan patroli' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Active Toggle -->
                                <Button
                                    :variant="link.is_active ? 'outline' : 'secondary'"
                                    size="sm"
                                    @click="toggleLink(link)"
                                    :disabled="profile.is_suspended"
                                    class="text-xs font-mono font-bold"
                                    :class="link.is_active ? 'border-success/40 text-success bg-success/10' : 'text-muted-foreground'"
                                    :title="link.is_active ? 'Tautan Aktif' : 'Tautan Disembunyikan'"
                                >
                                    {{ link.is_active ? "Aktif" : "Mati" }}
                                </Button>

                                <Button
                                    v-if="!profile.is_suspended"
                                    variant="outline"
                                    size="icon-sm"
                                    aria-label="Edit tautan"
                                    @click="openEditModal(link)"
                                    title="Edit Tautan"
                                >
                                    <Pencil class="w-3.5 h-3.5" />
                                </Button>

                                <Button
                                    v-if="!profile.is_suspended"
                                    variant="destructive"
                                    size="icon-sm"
                                    aria-label="Hapus tautan"
                                    @click="promptDeleteLink(link)"
                                    title="Hapus Tautan"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </Card>
                    </div>

                    <EmptyState
                        v-else
                        title="Belum Ada Tautan"
                        description="Klik '+ Tambah Tautan' untuk menambahkan Saweria, Discord, atau rekan patroli."
                    />
                </div>

                <!-- TAB 2: APPEARANCE & THEME CONFIG -->
                <Card v-show="activeTab === 'appearance'" class="space-y-5 p-5 rounded-2xl bg-card border-border">
                    <h3 class="text-sm font-bold font-mono uppercase text-foreground">
                        Kustomisasi Tampilan Bio-Link
                    </h3>

                    <!-- Display Title -->
                    <div class="space-y-1.5 text-left">
                        <Label class="text-xs font-mono text-muted-foreground uppercase">Judul Profil / Display Name</Label>
                        <Input
                            v-model="appearanceForm.display_title"
                            type="text"
                            class="text-xs font-mono"
                            placeholder="Contoh: Ofc. John Doe"
                        />
                    </div>

                    <!-- Tagline -->
                    <div class="space-y-1.5 text-left">
                        <Label class="text-xs font-mono text-muted-foreground uppercase">Bio Tagline / Pesan Singkat (Maks. 255)</Label>
                        <Textarea
                            v-model="appearanceForm.bio_tagline"
                            rows="2"
                            class="text-xs font-sans resize-none"
                            placeholder="Tuliskan motto patroli atau info jadwal streaming Anda..."
                        />
                    </div>

                    <!-- Theme Presets Grid -->
                    <div class="space-y-2 text-left">
                        <Label class="text-xs font-mono text-muted-foreground uppercase">Preset Tema Departemen</Label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div
                                v-for="(theme, key) in themes"
                                :key="key"
                                @click="appearanceForm.theme_preset = key; appearanceForm.accent_color = theme.accent"
                                class="p-3 rounded-xl border cursor-pointer transition relative"
                                :class="appearanceForm.theme_preset === key ? 'border-primary bg-primary/10 ring-1 ring-primary/50' : 'border-border bg-muted/40 hover:border-primary/40'"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold font-mono text-foreground">{{ theme.name }}</span>
                                    <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: theme.accent }"></span>
                                </div>
                                <p class="text-xs text-muted-foreground leading-snug">{{ theme.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Button Style -->
                    <div class="space-y-1.5 text-left">
                        <Label class="text-xs font-mono text-muted-foreground uppercase">Gaya Tombol (Button Style)</Label>
                        <select
                            v-model="appearanceForm.button_style"
                            class="w-full px-3 py-2 rounded-xl bg-background border border-input text-xs font-mono text-foreground focus:border-primary outline-hidden"
                        >
                            <option v-for="style in buttonStyles" :key="style" :value="style">
                                {{ style.replace('_', ' ').toUpperCase() }}
                            </option>
                        </select>
                    </div>

                    <!-- Accent Color Picker -->
                    <div class="space-y-1.5 text-left">
                        <Label class="text-xs font-mono text-muted-foreground uppercase">Warna Aksen Taktis (Hex)</Label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="appearanceForm.accent_color"
                                type="color"
                                class="w-10 h-10 rounded-xl cursor-pointer bg-transparent border-0 p-0"
                            />
                            <Input
                                v-model="appearanceForm.accent_color"
                                type="text"
                                class="w-36 text-xs font-mono uppercase"
                            />
                        </div>
                    </div>

                    <!-- Live Stream Banner Toggle -->
                    <div class="flex items-center justify-between pt-2 border-t border-border">
                        <div>
                            <div class="text-xs font-bold font-mono text-foreground">Tampilkan Banner Patroli Live</div>
                            <div class="text-xs text-muted-foreground">Muncul otomatis ketika siaran YouTube Anda sedang aktif.</div>
                        </div>
                        <Switch
                            :model-value="appearanceForm.show_live_stream_banner"
                            @update:model-value="(val) => (appearanceForm.show_live_stream_banner = val)"
                        />
                    </div>

                    <Button
                        @click="saveAppearance"
                        :disabled="isSavingAppearance || profile.is_suspended"
                        class="w-full font-mono font-bold text-xs uppercase tracking-wider"
                    >
                        {{ isSavingAppearance ? "Menyimpan..." : "Simpan Tampilan Profil" }}
                    </Button>
                </Card>

                <!-- TAB 3: AUDIT HISTORY -->
                <div v-show="activeTab === 'history'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono uppercase text-muted-foreground">
                            Log Perubahan Tautan & Profil Anda
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="loadHistory"
                            class="text-xs font-mono text-primary"
                        >
                            <RefreshCw class="w-3.5 h-3.5 mr-1" />
                            Muat Ulang
                        </Button>
                    </div>

                    <div v-if="historyLogs.length > 0" class="space-y-2">
                        <Card
                            v-for="log in historyLogs"
                            :key="log.id"
                            class="p-3 rounded-xl bg-card border-border text-xs text-left font-mono"
                        >
                            <div class="flex items-center justify-between text-xs text-muted-foreground mb-1">
                                <span class="font-bold text-primary uppercase">{{ log.action }}</span>
                                <span>{{ new Date(log.created_at).toLocaleString() }}</span>
                            </div>
                            <p class="text-foreground text-xs font-sans">
                                {{ log.reason || "Perubahan sistem / konfigurasi." }}
                            </p>
                        </Card>
                    </div>

                    <EmptyState
                        v-else-if="!isLoadingHistory"
                        title="Belum Ada Riwayat"
                        description="Belum ada riwayat aktivitas yang tercatat."
                    />
                </div>
            </div>

            <!-- RIGHT COLUMN: INTERACTIVE REAL-TIME MOBILE PREVIEW FRAME (5 cols) -->
            <div class="lg:col-span-5 flex flex-col items-center sticky top-20">
                <div class="text-xs font-mono text-muted-foreground uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <span>📱 PRATINJAU LANGSUNG MOBILE (380px)</span>
                </div>

                <!-- Realistic Smartphone Mockup -->
                <div class="w-full max-w-[360px] h-[640px] rounded-[40px] bg-background border-[6px] border-border shadow-2xl relative overflow-hidden flex flex-col ring-1 ring-border">
                    <!-- Notch / Speaker grill -->
                    <div class="w-full h-6 bg-background flex items-center justify-center shrink-0 z-20">
                        <div class="w-20 h-3.5 bg-card rounded-full flex items-center justify-end px-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary/80"></span>
                        </div>
                    </div>

                    <!-- Scrollable Preview Viewport -->
                    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4 text-center scrollbar-none font-sans text-xs">
                        <!-- Mini Hero -->
                        <div class="flex flex-col items-center">
                            <OfficerAvatar
                                :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                                :name="officer.officer_name"
                                :department="officer.department"
                                size="lg"
                                class="mb-2"
                            />
                            <Badge variant="info" class="text-xs font-mono font-bold mb-1">
                                🛡️ VERIFIED OFFICER
                            </Badge>
                            <h4 class="text-sm font-bold font-mono uppercase text-foreground leading-tight">
                                {{ appearanceForm.display_title || officer.officer_name }}
                            </h4>
                            <p class="text-xs text-primary font-mono">
                                {{ officer.streamer_name }}
                            </p>
                            <p v-if="appearanceForm.bio_tagline" class="text-xs text-muted-foreground mt-1 max-w-[240px] leading-relaxed">
                                {{ appearanceForm.bio_tagline }}
                            </p>
                        </div>

                        <!-- Mini Links List -->
                        <div class="space-y-2 pt-2">
                            <div
                                v-for="l in localLinks.filter((x) => x.is_active)"
                                :key="l.id"
                                class="p-2.5 rounded-xl bg-card border border-border flex items-center justify-between text-left shadow-xs"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs">
                                        <span v-if="l.link_type === 'donation'">🪙</span>
                                        <span v-else-if="l.link_type === 'officer_recommendation'">🚓</span>
                                        <span v-else-if="l.link_type === 'discord'">💬</span>
                                        <span v-else>🔗</span>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold font-mono text-foreground truncate">{{ l.title }}</div>
                                        <div v-if="l.subtitle" class="text-xs text-muted-foreground truncate">{{ l.subtitle }}</div>
                                    </div>
                                </div>
                                <span class="text-xs text-primary">→</span>
                            </div>

                            <div v-if="localLinks.filter((x) => x.is_active).length === 0" class="py-8 text-xs text-muted-foreground font-mono">
                                (Belum ada tautan aktif)
                            </div>
                        </div>

                        <div class="text-xs text-muted-foreground font-mono pt-4 border-t border-border">
                            Police Duty Multiview • policeduty.sasp-motionliferp.com
                        </div>
                    </div>

                    <!-- Home Bar -->
                    <div class="w-full h-4 bg-background flex items-center justify-center shrink-0 z-20">
                        <div class="w-24 h-1 bg-muted rounded-full"></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ADD / EDIT LINK MODAL -->
        <Dialog :open="isModalOpen" @update:open="(val) => (isModalOpen = val)">
            <DialogContent class="sm:max-w-md bg-card border-border text-left space-y-4">
                <DialogHeader class="border-b border-border pb-3">
                    <DialogTitle class="text-sm font-bold font-mono uppercase text-foreground">
                        {{ editingLinkId ? "Edit Tautan" : "Tambah Tautan Baru" }}
                    </DialogTitle>
                </DialogHeader>

                <!-- Link Type Selector -->
                <div class="space-y-1 text-left">
                    <Label class="text-xs font-mono text-muted-foreground uppercase">Tipe Tautan</Label>
                    <select
                        v-model="linkForm.link_type"
                        class="w-full px-3 py-2 rounded-xl bg-background border border-input text-xs font-mono text-foreground focus:border-primary outline-hidden"
                    >
                        <option value="donation">🪙 Donasi (Saweria, Bagibagi, Trakteer, Sociabuzz)</option>
                        <option value="discord">💬 Komunitas Discord</option>
                        <option value="officer_recommendation">🚓 Rekan Patroli (Squad Officer)</option>
                        <option value="social">▶️ Media Sosial (YouTube, TikTok, IG, X, Twitch)</option>
                        <option value="custom">🌐 Tautan Kustom</option>
                    </select>
                </div>

                <!-- Squad Partner Selector (If officer_recommendation) -->
                <div v-if="linkForm.link_type === 'officer_recommendation'" class="space-y-1 text-left">
                    <Label class="text-xs font-mono text-muted-foreground uppercase">Pilih Perwira Rekanan</Label>
                    <select
                        v-model="linkForm.recommended_officer_id"
                        class="w-full px-3 py-2 rounded-xl bg-background border border-input text-xs font-mono text-foreground focus:border-primary outline-hidden"
                    >
                        <option value="">-- Pilih Perwira Patroli --</option>
                        <option v-for="o in otherOfficers" :key="o.id" :value="o.id">
                            {{ o.officer_name }} ({{ o.streamer_name }}) [{{ o.department }} #{{ o.callsign }}]
                        </option>
                    </select>
                </div>

                <!-- URL Input (If not officer_recommendation) -->
                <div v-else class="space-y-1 text-left">
                    <Label class="text-xs font-mono text-muted-foreground uppercase">URL Tautan (HTTPS)</Label>
                    <Input
                        v-model="linkForm.url"
                        type="url"
                        class="text-xs font-mono"
                        placeholder="https://..."
                    />
                    <p class="text-xs text-primary font-mono leading-tight mt-1">
                        {{ domainHint }}
                    </p>
                </div>

                <!-- Title -->
                <div class="space-y-1 text-left">
                    <Label class="text-xs font-mono text-muted-foreground uppercase">Judul Tombol</Label>
                    <Input
                        v-model="linkForm.title"
                        type="text"
                        class="text-xs font-mono"
                        placeholder="Contoh: Saweria Donasi Taktis"
                    />
                </div>

                <!-- Subtitle -->
                <div class="space-y-1 text-left">
                    <Label class="text-xs font-mono text-muted-foreground uppercase">Sub-Teks (Opsional)</Label>
                    <Input
                        v-model="linkForm.subtitle"
                        type="text"
                        class="text-xs font-mono"
                        placeholder="Contoh: Min 10k TTS On / Komunitas Resmi"
                    />
                </div>

                <!-- Badge Label -->
                <div class="space-y-1 text-left">
                    <Label class="text-xs font-mono text-muted-foreground uppercase">Badge Promo (Opsional)</Label>
                    <Input
                        v-model="linkForm.badge_label"
                        type="text"
                        class="text-xs font-mono"
                        placeholder="Contoh: NEW, REKOMENDASI, 24/7"
                    />
                </div>

                <!-- Highlighted Toggle -->
                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs font-mono text-foreground">Beri Efek Glowing / Highlight</span>
                    <Switch
                        :model-value="linkForm.is_highlighted"
                        @update:model-value="(val) => (linkForm.is_highlighted = val)"
                    />
                </div>

                <!-- Modal Actions -->
                <DialogFooter class="pt-3 border-t border-border">
                    <Button
                        variant="outline"
                        @click="isModalOpen = false"
                        class="text-xs font-mono"
                    >
                        Batal
                    </Button>
                    <Button
                        @click="saveLink"
                        :disabled="isSavingLink"
                        class="font-mono font-bold text-xs uppercase tracking-wider"
                    >
                        {{ isSavingLink ? "Menyimpan..." : "Simpan Tautan" }}
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
