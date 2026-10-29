<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
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
import {
    Tabs,
    TabsList,
    TabsTrigger,
    TabsContent,
} from "@/Components/ui/tabs";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import {
    AlertTriangle,
    Check,
    Copy,
    Loader2,
    ShieldCheck,
    Zap,
    FileText,
} from "@lucide/vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "claimed"]);

const isLoading = ref(false);
const unclaimedOfficers = ref([]);
const myClaims = ref([]);
const searchQuery = ref("");

// Mode: 'youtube' (primary) | 'manual' (fallback)
const claimMode = ref("youtube");

// Form state
const selectedOfficerId = ref("");
const proofDiscordTag = ref("");
const notes = ref("");
const isSubmitting = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

// YouTube token verification state
const issuedToken = ref("");
const activeYouTubeClaim = ref(null);
const isCheckingYouTube = ref(false);
const copiedToken = ref(false);
const claimToCancelId = ref(null);

const activeClaim = computed(() => {
    return myClaims.value.find((c) => c.status === "pending") || null;
});

const filteredOfficers = computed(() => {
    if (!searchQuery.value) return unclaimedOfficers.value;
    const q = searchQuery.value.toLowerCase();
    return unclaimedOfficers.value.filter(
        (o) =>
            o.officer_name.toLowerCase().includes(q) ||
            o.callsign.toLowerCase().includes(q) ||
            o.department.toLowerCase().includes(q) ||
            (o.streamer_name && o.streamer_name.toLowerCase().includes(q))
    );
});

const loadData = async () => {
    isLoading.value = true;
    errorMessage.value = "";
    try {
        const [officersRes, claimsRes] = await Promise.all([
            axios.get("/api/v1/officers/unclaimed"),
            axios.get("/api/v1/officer-claims/mine"),
        ]);
        unclaimedOfficers.value = officersRes.data.officers || [];
        myClaims.value = claimsRes.data.claims || [];

        const pendingYt = myClaims.value.find((c) => c.status === "pending" && c.verification_method === "youtube_token");
        if (pendingYt) {
            activeYouTubeClaim.value = pendingYt;
            claimMode.value = "youtube";
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal memuat data perwira.";
    } finally {
        isLoading.value = false;
    }
};

// 1. YouTube automated claim: generate secret token
const requestYouTubeToken = async () => {
    if (!selectedOfficerId.value) {
        errorMessage.value = "Silakan pilih profil perwira yang ingin Anda klaim.";
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const res = await axios.post("/api/v1/officer-claims/youtube", {
            officer_id: selectedOfficerId.value,
        });

        issuedToken.value = res.data.token;
        activeYouTubeClaim.value = res.data.claim;
        successMessage.value = res.data.message || "Token verifikasi berhasil dibuat.";
        await loadData();
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal membuat token verifikasi YouTube.";
    } finally {
        isSubmitting.value = false;
    }
};

// 2. YouTube automated claim: verify token in description
const checkYouTubeVerification = async () => {
    const claim = activeYouTubeClaim.value || activeClaim.value;
    if (!claim) return;

    isCheckingYouTube.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const res = await axios.post(`/api/v1/officer-claims/${claim.id}/verify-youtube`, {
            token: issuedToken.value || undefined,
        });

        successMessage.value = res.data.message || "Verifikasi berhasil! Profil perwira telah terhubung.";
        activeYouTubeClaim.value = null;
        issuedToken.value = "";
        await loadData();
        emit("claimed");
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Pengecekan gagal. Pastikan token telah tersimpan di deskripsi channel.";
    } finally {
        isCheckingYouTube.value = false;
    }
};

// Copy token helper
const copyToken = async () => {
    if (!issuedToken.value) return;
    try {
        await navigator.clipboard.writeText(issuedToken.value);
        copiedToken.value = true;
        setTimeout(() => {
            copiedToken.value = false;
        }, 2500);
    } catch {
        // Fallback
    }
};

// 3. Manual Admin claim
const submitManualClaim = async () => {
    if (!selectedOfficerId.value) {
        errorMessage.value = "Silakan pilih profil perwira yang ingin Anda klaim.";
        return;
    }
    if (!proofDiscordTag.value.trim()) {
        errorMessage.value = "Discord tag wajib diisi untuk verifikasi identitas perwira.";
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const res = await axios.post("/api/v1/officer-claims", {
            officer_id: selectedOfficerId.value,
            proof_discord_tag: proofDiscordTag.value.trim(),
            notes: notes.value.trim() || null,
        });

        successMessage.value = res.data.message || "Pengajuan klaim berhasil dikirim.";
        selectedOfficerId.value = "";
        proofDiscordTag.value = "";
        notes.value = "";
        await loadData();
        emit("claimed");
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal mengajukan klaim perwira.";
    } finally {
        isSubmitting.value = false;
    }
};

const promptCancelClaim = (claimId) => {
    claimToCancelId.value = claimId;
};

const confirmCancelClaim = async () => {
    const claimId = claimToCancelId.value;
    if (!claimId) return;
    claimToCancelId.value = null;
    try {
        await axios.delete(`/api/v1/officer-claims/${claimId}`);
        activeYouTubeClaim.value = null;
        issuedToken.value = "";
        await loadData();
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal membatalkan klaim.";
    }
};

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            loadData();
        }
    }
);

onMounted(() => {
    if (props.show) {
        loadData();
    }
});
</script>

<template>
    <Dialog :open="show" @update:open="(val) => { if (!val) emit('close'); }">
        <DialogContent class="sm:max-w-lg bg-card border-border text-foreground font-sans space-y-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <DialogHeader class="border-b border-border pb-3 text-left">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary border border-primary/20">
                        <ShieldCheck class="w-5 h-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-sm font-bold font-mono uppercase text-foreground tracking-wider">
                            Klaim Kepemilikan Profil Perwira
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground font-sans">
                            SASP Self-Service Ownership & Verification (PRD v3)
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <!-- Messages -->
            <Alert
                v-if="errorMessage"
                variant="destructive"
                class="p-3 text-xs font-mono flex items-center gap-2"
            >
                <AlertTriangle class="w-4 h-4 shrink-0" />
                <AlertDescription>{{ errorMessage }}</AlertDescription>
            </Alert>

            <Alert
                v-if="successMessage"
                class="p-3 border-success/40 bg-success/10 text-success text-xs font-mono flex items-center gap-2"
            >
                <Check class="w-4 h-4 shrink-0" />
                <AlertDescription>{{ successMessage }}</AlertDescription>
            </Alert>

            <!-- Existing Pending YouTube Token Claim Waiting for Verification -->
            <Card
                v-if="activeYouTubeClaim && activeYouTubeClaim.status === 'pending'"
                class="p-4 rounded-xl bg-muted/40 border-primary/40 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <Badge variant="info" class="font-mono text-xs font-bold">
                        ⚡ VERIFIKASI YOUTUBE AKTIF
                    </Badge>
                    <span class="text-xs text-muted-foreground font-mono">
                        Berlaku 24 Jam
                    </span>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-foreground font-mono">
                        {{ activeYouTubeClaim.officer?.officer_name }} ({{ activeYouTubeClaim.officer?.callsign }})
                    </h4>
                    <p class="text-xs text-muted-foreground font-sans mt-0.5">
                        Channel ID: <span class="text-primary font-mono">{{ activeYouTubeClaim.officer?.channel_id }}</span>
                    </p>
                </div>

                <!-- Token Display Box -->
                <div v-if="issuedToken" class="p-3 bg-card border border-primary/30 rounded-xl space-y-1.5">
                    <div class="text-xs uppercase font-mono text-primary font-bold">
                        Kode Token Anda (Tempel di Deskripsi YouTube):
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <code class="text-xs font-mono text-warning font-bold tracking-wider select-all break-all">
                            {{ issuedToken }}
                        </code>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="copyToken"
                            class="text-xs font-mono font-bold shrink-0"
                        >
                            <Check v-if="copiedToken" class="w-3.5 h-3.5 mr-1 text-success" />
                            <Copy v-else class="w-3.5 h-3.5 mr-1" />
                            {{ copiedToken ? "Tersalin!" : "Salin" }}
                        </Button>
                    </div>
                </div>

                <div class="text-xs text-muted-foreground space-y-1 font-sans bg-card/60 p-3 rounded-lg border border-border">
                    <p class="font-bold text-foreground text-xs">Instruksi Langkah Mudah:</p>
                    <ol class="list-decimal list-inside space-y-0.5 text-xs text-muted-foreground">
                        <li>Buka channel YouTube Anda (atau deskripsi siaran live aktif).</li>
                        <li>Tempelkan kode token di atas pada bagian <strong class="text-foreground">Tentang (About)</strong> atau Deskripsi.</li>
                        <li>Simpan perubahan di YouTube, lalu tekan tombol <strong class="text-foreground">Periksa Sekarang</strong> di bawah.</li>
                    </ol>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-border">
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="promptCancelClaim(activeYouTubeClaim.id)"
                        class="text-xs font-mono text-destructive hover:text-destructive"
                    >
                        Batalkan Klaim
                    </Button>
                    <Button
                        @click="checkYouTubeVerification"
                        :disabled="isCheckingYouTube"
                        class="font-mono font-bold text-xs uppercase tracking-wider flex items-center gap-1.5"
                    >
                        <Loader2 v-if="isCheckingYouTube" class="w-3.5 h-3.5 animate-spin" />
                        <span>{{ isCheckingYouTube ? "Memeriksa..." : "Periksa Sekarang →" }}</span>
                    </Button>
                </div>
            </Card>

            <!-- Existing Pending Manual Claim -->
            <Card
                v-else-if="activeClaim && activeClaim.status === 'pending' && activeClaim.verification_method === 'manual_admin'"
                class="p-4 rounded-xl bg-muted/40 border-warning/40 space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <Badge variant="warning" class="font-mono text-xs font-bold">
                        ⏳ REVIEW MANUAL ADMIN
                    </Badge>
                    <span class="text-xs text-muted-foreground font-mono">
                        {{ new Date(activeClaim.created_at).toLocaleDateString() }}
                    </span>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-foreground font-mono">
                        {{ activeClaim.officer?.officer_name }} ({{ activeClaim.officer?.callsign }})
                    </h4>
                    <p class="text-xs text-muted-foreground font-sans mt-0.5">
                        Discord Tag: <span class="text-primary font-mono">{{ activeClaim.proof_discord_tag }}</span>
                    </p>
                    <p v-if="activeClaim.notes" class="text-xs text-muted-foreground italic mt-0.5">
                        "{{ activeClaim.notes }}"
                    </p>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-border">
                    <span class="text-xs text-muted-foreground">
                        Admin akan memverifikasi di saluran Discord SASP.
                    </span>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="promptCancelClaim(activeClaim.id)"
                        class="text-xs font-mono text-destructive hover:text-destructive"
                    >
                        Batalkan Pengajuan
                    </Button>
                </div>
            </Card>

            <!-- Form: Submit New Claim (if not already pending) -->
            <div v-else-if="!isLoading" class="space-y-4">
                <Tabs v-model="claimMode" class="w-full space-y-4">
                    <TabsList class="grid w-full grid-cols-2 font-mono text-xs">
                        <TabsTrigger value="youtube" class="flex items-center gap-1.5 font-bold">
                            <Zap class="w-3.5 h-3.5" />
                            <span>Token YouTube</span>
                            <Badge variant="secondary" class="text-xs uppercase px-1.5 py-0 font-mono">Utama</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="manual" class="flex items-center gap-1.5 font-bold">
                            <FileText class="w-3.5 h-3.5" />
                            <span>Review Admin</span>
                            <Badge variant="outline" class="text-xs uppercase px-1.5 py-0 font-mono">Cadangan</Badge>
                        </TabsTrigger>
                    </TabsList>

                    <!-- Search & Select Officer -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-mono uppercase text-muted-foreground">
                            Pilih Profil Perwira Dinas Anda
                        </Label>
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama perwira, callsign, atau departemen..."
                            class="text-xs font-mono"
                        />
                        <select
                            v-model="selectedOfficerId"
                            class="w-full px-3 py-2 rounded-xl bg-background border border-input text-xs font-mono text-foreground focus:border-primary outline-hidden"
                        >
                            <option value="">-- Pilih Perwira Dari Roster ({{ filteredOfficers.length }} tersedia) --</option>
                            <option v-for="o in filteredOfficers" :key="o.id" :value="o.id">
                                {{ o.officer_name }} ({{ o.streamer_name }}) • [{{ o.department }} #{{ o.callsign }}]
                            </option>
                        </select>
                    </div>

                    <!-- MODE 1: YOUTUBE VERIFICATION EXPLANATION & ACTION -->
                    <TabsContent value="youtube" class="space-y-3 p-3.5 bg-muted/40 rounded-xl border border-primary/20 mt-0">
                        <div class="text-xs text-muted-foreground space-y-1.5 leading-relaxed font-sans">
                            <p class="font-bold text-primary flex items-center gap-1.5">
                                <Zap class="w-3.5 h-3.5" />
                                <span>Cara Kerja Verifikasi Otomatis:</span>
                            </p>
                            <p>
                                Sistem akan menerbitkan kode token acak rahasia. Anda cukup menempelkannya sementara di deskripsi channel YouTube yang terdaftar. Sistem akan memvalidasi secara otomatis tanpa menunggu approval admin.
                            </p>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-border">
                            <Button
                                variant="outline"
                                @click="$emit('close')"
                                class="text-xs font-mono"
                            >
                                Tutup
                            </Button>
                            <Button
                                @click="requestYouTubeToken"
                                :disabled="isSubmitting"
                                class="font-mono font-bold text-xs uppercase tracking-wider"
                            >
                                {{ isSubmitting ? "Menerbitkan Token..." : "Dapatkan Token Verifikasi →" }}
                            </Button>
                        </div>
                    </TabsContent>

                    <!-- MODE 2: MANUAL ADMIN FORM -->
                    <TabsContent value="manual" class="space-y-3 mt-0">
                        <div class="space-y-1">
                            <Label class="text-xs font-mono uppercase text-muted-foreground">
                                Discord Tag Perwira <span class="text-primary">*</span>
                            </Label>
                            <Input
                                v-model="proofDiscordTag"
                                type="text"
                                placeholder="Contoh: username#1234 atau username"
                                class="text-xs font-mono"
                            />
                            <p class="text-xs text-muted-foreground font-sans">
                                Admin SASP akan menghubungi Discord ini untuk memvalidasi identitas perwira jika channel tidak dapat diakses.
                            </p>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs font-mono uppercase text-muted-foreground">
                                Catatan / Keterangan (Opsional)
                            </Label>
                            <Textarea
                                v-model="notes"
                                rows="2"
                                placeholder="Alasan memakai verifikasi manual..."
                                class="text-xs font-mono"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                            <Button
                                variant="outline"
                                @click="$emit('close')"
                                class="text-xs font-mono"
                            >
                                Tutup
                            </Button>
                            <Button
                                @click="submitManualClaim"
                                :disabled="isSubmitting"
                                class="font-mono font-bold text-xs uppercase tracking-wider"
                            >
                                {{ isSubmitting ? "Mengirim..." : "Kirim Review Manual" }}
                            </Button>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Loading Spinner -->
            <div v-else class="py-8 text-center text-muted-foreground font-mono text-xs">
                <Loader2 class="w-5 h-5 animate-spin mx-auto mb-2 text-primary" />
                Memuat data perwira...
            </div>
        </DialogContent>
    </Dialog>

    <AlertDialog :open="claimToCancelId !== null" @update:open="(val) => { if (!val) claimToCancelId = null; }">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Batalkan Pengajuan Klaim?</AlertDialogTitle>
                <AlertDialogDescription>
                    Apakah Anda yakin ingin membatalkan pengajuan klaim ini?
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="claimToCancelId = null">Tidak</AlertDialogCancel>
                <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmCancelClaim">
                    Ya, Batalkan
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
