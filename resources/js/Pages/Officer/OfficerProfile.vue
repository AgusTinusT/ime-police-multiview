<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
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
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";
import {
    OfficerAvatar,
    EmptyState,
} from "@/Components/tactical";
import {
    AlertTriangle,
    ArrowLeft,
    Award,
    Building2,
    Check,
    Clock,
    FileText,
    Info,
    Loader2,
    Lock,
    Plus,
    RefreshCw,
    Settings,
    ShieldCheck,
} from "@lucide/vue";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
    agenciesMaster: {
        type: Array,
        default: () => [],
    },
    canChangeCallsign: {
        type: Boolean,
        default: true,
    },
    remainingCooldownDays: {
        type: Number,
        default: 0,
    },
});

const page = usePage();

// Active tab inside Officer Profile: 'profile' | 'requests' | 'history'
const activeTab = ref("profile");

// Flash notifications
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
    }, 4500);
};

// Form state for direct profile updates
const form = ref({
    officer_name: props.officer.officer_name || "",
    streamer_name: props.officer.streamer_name || "",
    avatar_url: props.officer.avatar_url || "",
    use_custom_avatar: !!props.officer.avatar_is_custom,
    patrol_zone: props.officer.patrol_zone || "",
    division_id: props.officer.division_id || "",
    callsign: props.officer.callsign || "",
    badge_number: props.officer.badge_number || "",
});

const isSavingProfile = ref(false);

// Filter divisions based on current officer agency
const availableDivisions = computed(() => {
    const currentAgency = props.agenciesMaster.find(
        (a) => a.id === props.officer.agency_id || a.code === props.officer.department
    );
    return currentAgency?.divisions || [];
});

const saveProfile = async () => {
    isSavingProfile.value = true;
    try {
        const payload = {
            officer_name: form.value.officer_name,
            streamer_name: form.value.streamer_name,
            avatar_url: form.value.avatar_url,
            use_custom_avatar: form.value.use_custom_avatar,
            patrol_zone: form.value.patrol_zone,
            division_id: form.value.division_id || null,
        };

        if (props.canChangeCallsign) {
            payload.callsign = form.value.callsign;
            payload.badge_number = form.value.badge_number;
        }

        const res = await axios.put("/api/v1/officer/profile", payload);
        flash(res.data.message || "Profil berhasil diperbarui!");
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal memperbarui profil. Periksa isian formulir.";
        flash(msg, true);
    } finally {
        isSavingProfile.value = false;
    }
};

// Change Request State (Pangkat & Kesatuan)
const isRequestModalOpen = ref(false);
const requestToCancelId = ref(null);
const requestForm = ref({
    field: "rank_id", // 'rank_id' | 'agency_id'
    agency_id: props.officer.agency_id || "",
    new_value: "",
    reason: "",
});
const isSubmittingRequest = ref(false);

// Local list of change requests
const localRequests = ref([...(props.officer.change_requests || [])]);

// Available ranks for the selected agency in the change request modal
const modalRanks = computed(() => {
    const agency = props.agenciesMaster.find(
        (a) => a.id === Number(requestForm.value.agency_id)
    );
    return agency?.ranks || [];
});

const openChangeRequestModal = (field = "rank_id") => {
    requestForm.value = {
        field,
        agency_id: props.officer.agency_id || (props.agenciesMaster[0]?.id ?? ""),
        new_value: "",
        reason: "",
    };
    isRequestModalOpen.value = true;
};

const submitChangeRequest = async () => {
    if (!requestForm.value.new_value) {
        flash("Silakan pilih pangkat atau kesatuan tujuan.", true);
        return;
    }
    if (!requestForm.value.reason || requestForm.value.reason.trim().length < 5) {
        flash("Harap sertakan alasan atau nomor sprin mutasi dinas.", true);
        return;
    }

    isSubmittingRequest.value = true;
    try {
        const res = await axios.post("/api/v1/officer/change-requests", {
            field: requestForm.value.field,
            new_value: requestForm.value.new_value,
            reason: requestForm.value.reason,
        });

        localRequests.value.unshift(res.data.change_request);
        isRequestModalOpen.value = false;
        flash(res.data.message || "Pengajuan mutasi berhasil dikirim ke Komando!");
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal mengirim pengajuan mutasi.";
        flash(msg, true);
    } finally {
        isSubmittingRequest.value = false;
    }
};

const promptCancelRequest = (requestId) => {
    requestToCancelId.value = requestId;
};

const confirmCancelRequest = async () => {
    const requestId = requestToCancelId.value;
    if (!requestId) return;
    requestToCancelId.value = null;

    try {
        await axios.delete(`/api/v1/officer/change-requests/${requestId}`);
        localRequests.value = localRequests.value.filter((r) => r.id !== requestId);
        flash("Pengajuan berhasil dibatalkan.");
    } catch (err) {
        flash(err.response?.data?.message || "Gagal membatalkan pengajuan.", true);
    }
};

// History State
const historyLogs = ref([]);
const isLoadingHistory = ref(false);

const loadHistory = async () => {
    isLoadingHistory.value = true;
    try {
        const res = await axios.get("/api/v1/officer/profile/history");
        historyLogs.value = res.data.history || [];
    } catch {
        historyLogs.value = [];
    } finally {
        isLoadingHistory.value = false;
    }
};

const switchTab = (tab) => {
    activeTab.value = tab;
    if (tab === "history" && historyLogs.value.length === 0) {
        loadHistory();
    }
};
</script>

<template>
    <Head title="Profil Dinas Saya - Tactical Bio-Link" />

    <div class="min-h-screen bg-background text-foreground font-sans selection:bg-primary selection:text-primary-foreground pb-20">
        <!-- TOP NAVIGATION BAR -->
        <header class="sticky top-0 z-40 bg-card/80 backdrop-blur-md border-b border-border px-4 lg:px-8 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Button
                    as-child
                    variant="outline"
                    size="icon"
                    title="Kembali ke Tactical Hub"
                >
                    <Link href="/dashboard/bio-links" aria-label="Kembali ke Tactical Hub">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-sm sm:text-base font-bold font-mono tracking-wider uppercase text-foreground flex items-center gap-2">
                        <span>PROFIL DINAS PERWIRA</span>
                    </h1>
                    <p class="text-xs text-muted-foreground font-mono">
                        Manajemen Kepemilikan & Identitas Perwira Mandiri
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Button as-child variant="outline" size="sm" class="text-xs font-mono text-primary">
                    <Link href="/dashboard/bio-links" class="flex items-center gap-1.5">
                        <ShieldCheck class="w-4 h-4" />
                        <span>Kelola Tautan Bio</span>
                    </Link>
                </Button>
            </div>
        </header>

        <!-- FLASH NOTIFICATION -->
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <Alert
                v-if="statusMessage"
                class="p-3.5 border-success/40 bg-success/10 text-success text-xs font-mono flex items-center gap-2"
            >
                <Check class="w-4 h-4 shrink-0" />
                <AlertDescription>{{ statusMessage }}</AlertDescription>
            </Alert>

            <Alert
                v-if="errorMessage"
                variant="destructive"
                class="p-3.5 text-xs font-mono flex items-center gap-2"
            >
                <AlertTriangle class="w-4 h-4 shrink-0" />
                <AlertDescription>{{ errorMessage }}</AlertDescription>
            </Alert>
        </div>

        <!-- MAIN CONTAINER -->
        <main class="max-w-6xl mx-auto px-4 mt-6">
            <!-- HERO SUMMARY CARD -->
            <Card class="p-6 rounded-2xl bg-card border-border shadow-2xl flex flex-col md:flex-row items-center md:items-start justify-between gap-6 relative overflow-hidden">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    <OfficerAvatar
                        :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                        :name="officer.officer_name"
                        :department="officer.department"
                        size="lg"
                        class="w-20 h-20"
                    />
                    <div class="text-center sm:text-left space-y-1">
                        <div class="flex items-center gap-2 flex-wrap justify-center sm:justify-start">
                            <h2 class="text-xl font-bold font-mono uppercase text-foreground tracking-wide">
                                {{ officer.officer_name }}
                            </h2>
                            <Badge variant="success" class="text-xs font-bold font-mono uppercase">
                                TERVERIFIKASI
                            </Badge>
                            <Badge v-if="officer.avatar_is_custom" variant="info" class="text-xs font-bold font-mono uppercase">
                                AVATAR KUSTOM
                            </Badge>
                        </div>
                        <p class="text-xs text-foreground font-mono">
                            {{ officer.department }} • {{ officer.rank }} • Callsign #{{ officer.callsign }}
                        </p>
                        <p class="text-xs text-muted-foreground font-mono">
                            Streamer: <span class="text-primary font-semibold">{{ officer.streamer_name || "-" }}</span>
                            <span v-if="officer.patrol_zone"> • Sektor: {{ officer.patrol_zone }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <Button
                        variant="outline"
                        @click="openChangeRequestModal('rank_id')"
                        class="border-warning/40 text-warning hover:bg-warning/10 text-xs font-mono font-bold flex items-center gap-2 cursor-pointer"
                    >
                        <Award class="w-4 h-4" />
                        <span>Ajukan Kenaikan Pangkat</span>
                    </Button>
                    <Button
                        variant="outline"
                        @click="openChangeRequestModal('agency_id')"
                        class="border-primary/40 text-primary hover:bg-primary/10 text-xs font-mono font-bold flex items-center gap-2 cursor-pointer"
                    >
                        <Building2 class="w-4 h-4" />
                        <span>Ajukan Mutasi Kesatuan</span>
                    </Button>
                </div>
            </Card>

            <!-- TAB SELECTOR -->
            <div class="mt-6 flex border-b border-border text-xs font-mono">
                <button
                    @click="switchTab('profile')"
                    class="px-5 py-2.5 font-bold transition border-b-2 flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'profile' ? 'border-primary text-primary bg-primary/10' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Settings class="w-4 h-4" />
                    <span>Pengaturan Profil</span>
                </button>
                <button
                    @click="switchTab('requests')"
                    class="px-5 py-2.5 font-bold transition border-b-2 flex items-center gap-2 cursor-pointer relative"
                    :class="activeTab === 'requests' ? 'border-primary text-primary bg-primary/10' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <FileText class="w-4 h-4" />
                    <span>Pengajuan Mutasi & Pangkat</span>
                    <span
                        v-if="localRequests.filter(r => r.status === 'pending').length > 0"
                        class="w-2 h-2 rounded-full bg-warning motion-safe:animate-pulse"
                    ></span>
                </button>
                <button
                    @click="switchTab('history')"
                    class="px-5 py-2.5 font-bold transition border-b-2 flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'history' ? 'border-primary text-primary bg-primary/10' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Clock class="w-4 h-4" />
                    <span>Riwayat Perubahan</span>
                </button>
            </div>

            <!-- TAB 1: PROFILE MANAGEMENT (PRD v3 4.3 4 GROUPS — 4 Cards) -->
            <div v-if="activeTab === 'profile'" class="mt-6 space-y-6">
                <!-- GROUP 1: IDENTITAS KARAKTER & STREAMER (LANGSUNG) -->
                <Card class="p-6 rounded-2xl bg-card border-border shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-border">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-foreground flex items-center gap-2">
                                <span>Grup 1: Identitas Karakter & Streamer</span>
                                <Badge variant="success" class="text-xs font-bold">
                                    LANGSUNG
                                </Badge>
                            </h3>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Perubahan identitas nama dibatasi maksimal 3 kali dalam 30 hari kalender.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Nama Karakter RP <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                v-model="form.officer_name"
                                type="text"
                                class="text-sm font-mono"
                                placeholder="Contoh: Saprudin Mintoro"
                            />
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Nama Streamer / Alias
                            </Label>
                            <Input
                                v-model="form.streamer_name"
                                type="text"
                                class="text-sm font-mono"
                                placeholder="Contoh: Bang Sapru Gaming"
                            />
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Sektor / Zona Patroli Rutin
                            </Label>
                            <Input
                                v-model="form.patrol_zone"
                                type="text"
                                class="text-sm font-mono"
                                placeholder="Contoh: Vinewood Hills & Sandy Shores"
                            />
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Divisi / Satuan Tugas
                            </Label>
                            <select
                                v-model="form.division_id"
                                class="w-full px-3.5 py-2 rounded-xl bg-background border border-input focus:border-primary text-sm font-mono text-foreground outline-hidden"
                            >
                                <option value="">-- Tanpa Penugasan Khusus --</option>
                                <option
                                    v-for="div in availableDivisions"
                                    :key="div.id"
                                    :value="div.id"
                                >
                                    {{ div.name }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2 space-y-2 pt-2 border-t border-border">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Foto Avatar Profil
                            </Label>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <Input
                                    v-model="form.avatar_url"
                                    type="url"
                                    class="flex-1 text-sm font-mono"
                                    placeholder="https://i.imgur.com/..."
                                />
                                <label class="flex items-center gap-2 cursor-pointer select-none text-xs font-mono text-foreground">
                                    <Switch
                                        :model-value="form.use_custom_avatar"
                                        @update:model-value="(val) => (form.use_custom_avatar = val)"
                                    />
                                    <span>Gunakan Avatar Kustom (Kunci dari Overwrite Sync YouTube)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- GROUP 2: PENANDA UNIT (LANGSUNG + CEK UNIK + COOLDOWN 7 HARI) -->
                <Card class="p-6 rounded-2xl bg-card border-border shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-border flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-foreground flex items-center gap-2">
                                <span>Grup 2: Penanda Unit & Callsign</span>
                                <Badge variant="warning" class="text-xs font-bold">
                                    COOLDOWN 7 HARI
                                </Badge>
                            </h3>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Callsign harus unik di seluruh kesatuan aktif dan tunduk pada batas ubah 1x per 7 hari.
                            </p>
                        </div>

                        <Badge v-if="!canChangeCallsign" variant="warning" class="text-xs font-mono flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5" />
                            <span>Cooldown aktif: sisa {{ remainingCooldownDays }} hari</span>
                        </Badge>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Callsign Unit <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                v-model="form.callsign"
                                :disabled="!canChangeCallsign"
                                type="text"
                                class="text-sm font-mono uppercase"
                                placeholder="Contoh: 1-ADAM-12"
                            />
                            <p class="text-xs text-muted-foreground font-mono mt-1">
                                Tidak boleh sama dengan callsign perwira aktif lainnya.
                            </p>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs font-mono text-muted-foreground">
                                Nomor Lencana / Badge
                            </Label>
                            <Input
                                v-model="form.badge_number"
                                :disabled="!canChangeCallsign"
                                type="text"
                                class="text-sm font-mono"
                                placeholder="Contoh: 704"
                            />
                        </div>
                    </div>
                </Card>

                <!-- GROUP 3: PANGKAT & KESATUAN (LEWAT PENGAJUAN KOMANDO) -->
                <Card class="p-6 rounded-2xl bg-card border-border shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-border">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-foreground flex items-center gap-2">
                                <span>Grup 3: Pangkat & Kesatuan</span>
                                <Badge variant="info" class="text-xs font-bold">
                                    LEWAT PENGAJUAN KOMANDO
                                </Badge>
                            </h3>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Kenaikan pangkat atau pindah kesatuan memerlukan verifikasi dan persetujuan Dispatcher / Komando SASP.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-muted/50 border border-border flex items-center justify-between">
                            <div>
                                <span class="text-xs uppercase font-mono text-muted-foreground block">Kesatuan Saat Ini</span>
                                <span class="text-sm font-bold font-mono text-foreground">{{ officer.department || "-" }}</span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openChangeRequestModal('agency_id')"
                                class="text-xs font-mono text-primary cursor-pointer"
                            >
                                Ajukan Mutasi
                            </Button>
                        </div>

                        <div class="p-4 rounded-xl bg-muted/50 border border-border flex items-center justify-between">
                            <div>
                                <span class="text-xs uppercase font-mono text-muted-foreground block">Pangkat Saat Ini</span>
                                <span class="text-sm font-bold font-mono text-foreground">{{ officer.rank || "-" }}</span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openChangeRequestModal('rank_id')"
                                class="text-xs font-mono text-warning cursor-pointer"
                            >
                                Ajukan Kenaikan
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- GROUP 4: IDENTITAS TERKUNCI (READ-ONLY 🔒 — Input disabled + Lock icon) -->
                <Card class="p-6 rounded-2xl bg-card border-border shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-border">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-foreground flex items-center gap-2">
                                <span>Grup 4: Identitas Terkunci</span>
                                <Badge variant="destructive" class="text-xs font-bold flex items-center gap-1">
                                    <Lock class="w-3 h-3" />
                                    <span>LOCKED</span>
                                </Badge>
                            </h3>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Dikunci permanen oleh sistem untuk mencegah pembajakan channel dan broken link publik.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs uppercase font-mono text-muted-foreground flex items-center gap-1.5">
                                <Lock class="w-3.5 h-3.5 text-muted-foreground" />
                                <span>Channel ID YouTube</span>
                            </Label>
                            <Input
                                :model-value="officer.channel_id || '-'"
                                disabled
                                class="text-xs font-mono"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs uppercase font-mono text-muted-foreground flex items-center gap-1.5">
                                <Lock class="w-3.5 h-3.5 text-muted-foreground" />
                                <span>YouTube Custom URL</span>
                            </Label>
                            <Input
                                :model-value="officer.custom_url || '-'"
                                disabled
                                class="text-xs font-mono"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs uppercase font-mono text-muted-foreground flex items-center gap-1.5">
                                <Lock class="w-3.5 h-3.5 text-muted-foreground" />
                                <span>Bio-Link Slug</span>
                            </Label>
                            <Input
                                :model-value="`/@${officer.bio_profile?.slug || officer.slug || '-'}`"
                                disabled
                                class="text-xs font-mono"
                            />
                        </div>
                    </div>

                    <div class="mt-3 p-3 rounded-xl bg-muted/40 border border-border text-xs text-muted-foreground font-mono flex items-center gap-2">
                        <Info class="w-4 h-4 text-primary shrink-0" />
                        <span>Jika Anda mengganti channel YouTube utama Anda, ajukan tiket bantuan ke Admin SASP untuk re-verifikasi.</span>
                    </div>
                </Card>

                <!-- SAVE BUTTON ACTIONS -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <Button
                        @click="saveProfile"
                        :disabled="isSavingProfile"
                        size="lg"
                        class="font-mono font-bold text-xs uppercase tracking-wider cursor-pointer flex items-center gap-2"
                    >
                        <Loader2 v-if="isSavingProfile" class="w-4 h-4 animate-spin" />
                        <span>{{ isSavingProfile ? "Menyimpan Perubahan..." : "Simpan Pembaruan Profil" }}</span>
                    </Button>
                </div>
            </div>

            <!-- TAB 2: CHANGE REQUESTS (PENGAJUAN MUTASI & PANGKAT) -->
            <div v-if="activeTab === 'requests'" class="mt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold font-mono uppercase text-foreground">
                            Riwayat Pengajuan Pangkat & Kesatuan
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Daftar tiket mutasi atau kenaikan pangkat yang pernah diajukan.
                        </p>
                    </div>

                    <Button
                        size="sm"
                        @click="openChangeRequestModal('rank_id')"
                        class="text-xs font-mono font-bold cursor-pointer flex items-center gap-1.5"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Buat Pengajuan Baru</span>
                    </Button>
                </div>

                <EmptyState
                    v-if="localRequests.length === 0"
                    title="Belum Ada Pengajuan"
                    description="Belum ada pengajuan mutasi pangkat atau kesatuan."
                />

                <div v-else class="space-y-3">
                    <Card
                        v-for="req in localRequests"
                        :key="req.id"
                        class="p-4 rounded-xl bg-card border-border flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary" class="text-xs font-bold font-mono uppercase">
                                    {{ req.field === 'rank_id' ? 'Kenaikan Pangkat' : 'Mutasi Kesatuan' }}
                                </Badge>
                                <Badge
                                    :variant="req.status === 'pending' ? 'warning' : req.status === 'approved' ? 'success' : 'destructive'"
                                    class="text-xs font-bold font-mono uppercase"
                                >
                                    {{ req.status }}
                                </Badge>
                                <span class="text-xs text-muted-foreground font-mono">
                                    Diajukan: {{ new Date(req.created_at).toLocaleDateString('id-ID') }}
                                </span>
                            </div>

                            <p class="text-xs font-mono text-foreground">
                                Permintaan ke: <strong class="text-primary">{{ req.new_value }}</strong>
                                <span class="text-muted-foreground"> (Sebelumnya: {{ req.old_value || '-' }})</span>
                            </p>

                            <p class="text-xs text-muted-foreground italic">
                                Alasan: "{{ req.reason }}"
                            </p>

                            <p v-if="req.admin_reason" class="text-xs text-warning font-mono">
                                Catatan Komando: {{ req.admin_reason }}
                            </p>
                        </div>

                        <div v-if="req.status === 'pending'" class="flex items-center gap-2">
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="promptCancelRequest(req.id)"
                                class="text-xs font-mono cursor-pointer"
                            >
                                Batalkan Pengajuan
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- TAB 3: AUDIT HISTORY -->
            <div v-if="activeTab === 'history'" class="mt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold font-mono uppercase text-foreground">
                            Log Audit Perubahan Profil
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Catatan audit keamanan perubahan profil mandiri dan aksi admin.
                        </p>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        @click="loadHistory"
                        :disabled="isLoadingHistory"
                        class="text-xs font-mono cursor-pointer flex items-center gap-1.5"
                    >
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoadingHistory }" />
                        <span>{{ isLoadingHistory ? "Memuat..." : "Segarkan Log" }}</span>
                    </Button>
                </div>

                <div v-if="isLoadingHistory" class="p-8 text-center text-xs font-mono text-muted-foreground">
                    Memuat riwayat audit...
                </div>

                <EmptyState
                    v-else-if="historyLogs.length === 0"
                    title="Belum Ada Riwayat"
                    description="Belum ada riwayat perubahan yang tercatat."
                />

                <div v-else class="space-y-2">
                    <Card
                        v-for="log in historyLogs"
                        :key="log.id"
                        class="p-3.5 rounded-xl bg-card border-border text-xs font-mono flex flex-row items-start justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary" class="text-xs font-bold uppercase text-primary">
                                    {{ log.action }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">
                                    {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                </span>
                            </div>
                            <p class="text-foreground">
                                {{ log.details }}
                            </p>
                        </div>
                    </Card>
                </div>
            </div>
        </main>

        <!-- MODAL: AJUKAN KENAIKAN PANGKAT / MUTASI KESATUAN -->
        <Dialog :open="isRequestModalOpen" @update:open="(val) => (isRequestModalOpen = val)">
            <DialogContent class="sm:max-w-md bg-card border-border space-y-4">
                <DialogHeader class="pb-3 border-b border-border">
                    <DialogTitle class="text-sm font-bold font-mono uppercase text-foreground flex items-center gap-2">
                        <span>{{ requestForm.field === 'rank_id' ? '🎖️ Ajukan Kenaikan Pangkat' : '🏢 Ajukan Mutasi Kesatuan' }}</span>
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-3 font-mono text-xs">
                    <!-- Field Type Toggle -->
                    <div class="space-y-1">
                        <Label class="text-xs text-muted-foreground">Jenis Pengajuan</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                :variant="requestForm.field === 'rank_id' ? 'default' : 'outline'"
                                @click="requestForm.field = 'rank_id'"
                                class="text-xs cursor-pointer"
                            >
                                Pangkat
                            </Button>
                            <Button
                                type="button"
                                :variant="requestForm.field === 'agency_id' ? 'default' : 'outline'"
                                @click="requestForm.field = 'agency_id'"
                                class="text-xs cursor-pointer"
                            >
                                Kesatuan
                            </Button>
                        </div>
                    </div>

                    <!-- Target Selection for Agency Mutasi -->
                    <div v-if="requestForm.field === 'agency_id'" class="space-y-1">
                        <Label class="text-xs text-muted-foreground">Kesatuan Tujuan <span class="text-destructive">*</span></Label>
                        <select
                            v-model="requestForm.new_value"
                            class="w-full px-3 py-2 rounded-xl bg-background border border-input text-foreground outline-hidden focus:border-primary"
                        >
                            <option value="">-- Pilih Kesatuan Tujuan --</option>
                            <option
                                v-for="ag in agenciesMaster"
                                :key="ag.id"
                                :value="ag.code"
                            >
                                {{ ag.name }} ({{ ag.code }})
                            </option>
                        </select>
                    </div>

                    <!-- Target Selection for Rank Kenaikan -->
                    <div v-else class="space-y-1">
                        <Label class="text-xs text-muted-foreground">Pangkat Tujuan <span class="text-destructive">*</span></Label>
                        <select
                            v-model="requestForm.new_value"
                            class="w-full px-3 py-2 rounded-xl bg-background border border-input text-foreground outline-hidden focus:border-primary"
                        >
                            <option value="">-- Pilih Pangkat Tujuan --</option>
                            <option
                                v-for="r in modalRanks"
                                :key="r.id"
                                :value="r.name"
                            >
                                {{ r.name }} (Level {{ r.level }})
                            </option>
                        </select>
                    </div>

                    <!-- Reason Textarea -->
                    <div class="space-y-1">
                        <Label class="text-xs text-muted-foreground">
                            Alasan & Dasar Perintah Dinas <span class="text-destructive">*</span>
                        </Label>
                        <Textarea
                            v-model="requestForm.reason"
                            rows="3"
                            class="text-xs font-mono"
                            placeholder="Contoh: Telah lulus diklat perwira / Nomor Sprin: SP/124/X/2026/SASP"
                        />
                    </div>
                </div>

                <DialogFooter class="pt-3 border-t border-border">
                    <Button
                        variant="outline"
                        @click="isRequestModalOpen = false"
                        class="text-xs font-mono cursor-pointer"
                    >
                        Batal
                    </Button>
                    <Button
                        @click="submitChangeRequest"
                        :disabled="isSubmittingRequest"
                        class="text-xs font-mono font-bold cursor-pointer"
                    >
                        {{ isSubmittingRequest ? "Mengirim..." : "Kirim Pengajuan" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- CANCEL REQUEST ALERT DIALOG -->
        <AlertDialog :open="requestToCancelId !== null" @update:open="(val) => { if (!val) requestToCancelId = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Batalkan Pengajuan Mutasi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin membatalkan pengajuan mutasi ini?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="requestToCancelId = null">Tidak</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmCancelRequest">
                        Ya, Batalkan
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
