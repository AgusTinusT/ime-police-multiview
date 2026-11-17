<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import {
    ShieldCheck,
    RefreshCw,
    Check,
    X,
    ChevronLeft,
    ChevronRight,
    Clock,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { EmptyState, SectionHeader } from "@/Components/tactical";
import { cn } from "@/lib/utils";

const emit = defineEmits(["toast", "updated"]);

const claims = ref([]);
const pagination = ref({});
const isLoading = ref(false);
const statusFilter = ref("pending");

// Action modal states
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const activeClaim = ref(null);
const actionReason = ref("");
const isProcessing = ref(false);
const formError = ref("");

const statusOptions = [
    { key: "pending", label: "Menunggu Review" },
    { key: "approved", label: "Disetujui" },
    { key: "rejected", label: "Ditolak" },
    { key: "all", label: "Semua Status" },
];

const statusBadgeClass = (status) => {
    if (status === "pending") return "bg-warning/15 text-warning border-warning/30";
    if (status === "approved") return "bg-success/15 text-success border-success/30";
    if (status === "rejected") return "bg-destructive/15 text-destructive border-destructive/30";
    return "bg-muted text-muted-foreground border-border";
};

const fetchClaims = async (page = 1) => {
    isLoading.value = true;
    try {
        const res = await axios.get("/api/v1/admin/officer-claims", {
            params: {
                status: statusFilter.value,
                page,
            },
        });
        claims.value = res.data.claims?.data || [];
        pagination.value = {
            current_page: res.data.claims?.current_page || 1,
            last_page: res.data.claims?.last_page || 1,
            total: res.data.claims?.total || 0,
        };
    } catch (err) {
        emit("toast", "Gagal memuat antrean klaim: " + (err.response?.data?.message || err.message), "error");
    } finally {
        isLoading.value = false;
    }
};

const openApproveModal = (claim) => {
    activeClaim.value = claim;
    actionReason.value = "Disetujui setelah verifikasi Discord kepolisian";
    formError.value = "";
    showApproveModal.value = true;
};

const handleApprove = async () => {
    if (!activeClaim.value) return;
    isProcessing.value = true;
    formError.value = "";

    try {
        const res = await axios.post(`/api/v1/admin/officer-claims/${activeClaim.value.id}/approve`, {
            reason: actionReason.value || null,
        });

        emit("toast", res.data.message || "Klaim berhasil disetujui.", "success");
        showApproveModal.value = false;
        fetchClaims(pagination.value.current_page);
        emit("updated");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal menyetujui klaim.";
    } finally {
        isProcessing.value = false;
    }
};

const openRejectModal = (claim) => {
    activeClaim.value = claim;
    actionReason.value = "";
    formError.value = "";
    showRejectModal.value = true;
};

const handleReject = async () => {
    if (!activeClaim.value) return;
    if (!actionReason.value || actionReason.value.trim().length < 5) {
        formError.value = "Alasan penolakan wajib diisi (minimal 5 karakter).";
        return;
    }

    isProcessing.value = true;
    formError.value = "";

    try {
        const res = await axios.post(`/api/v1/admin/officer-claims/${activeClaim.value.id}/reject`, {
            reason: actionReason.value.trim(),
        });

        emit("toast", res.data.message || "Pengajuan klaim telah ditolak.", "success");
        showRejectModal.value = false;
        fetchClaims(pagination.value.current_page);
        emit("updated");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal menolak klaim.";
    } finally {
        isProcessing.value = false;
    }
};

const changeStatus = (newStatus) => {
    statusFilter.value = newStatus;
    fetchClaims(1);
};

onMounted(() => {
    fetchClaims();
});

defineExpose({
    fetchClaims,
});
</script>

<template>
    <div class="space-y-4 text-xs">
        <SectionHeader
            title="Klaim profil officer"
            description="Verifikasi identitas streamer dan ikat ke profil dinas."
        >
            <template #actions>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <Button
                        v-for="st in statusOptions"
                        :key="st.key"
                        size="sm"
                        :variant="statusFilter === st.key ? 'default' : 'outline'"
                        @click="changeStatus(st.key)"
                    >
                        {{ st.label }}
                    </Button>

                    <Button
                        size="icon"
                        variant="outline"
                        aria-label="Muat ulang klaim"
                        title="Muat Ulang"
                        @click="fetchClaims(pagination.current_page)"
                    >
                        <RefreshCw :class="cn('size-4', isLoading && 'motion-safe:animate-spin')" />
                    </Button>
                </div>
            </template>
        </SectionHeader>

        <!-- CLAIMS TABLE -->
        <Card class="overflow-hidden">
            <div v-if="isLoading" class="py-12 text-center text-muted-foreground">
                Memuat antrean klaim...
            </div>

            <div v-else-if="claims.length === 0" class="p-6">
                <EmptyState
                    :icon="Clock"
                    title="Antrean Klaim Kosong"
                    description="Tidak ada pengajuan klaim dalam kategori ini."
                />
            </div>

            <div v-else class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Perwira Roster</TableHead>
                            <TableHead>User Pengaju</TableHead>
                            <TableHead>Discord Tag</TableHead>
                            <TableHead>Catatan Pengaju</TableHead>
                            <TableHead>Status &amp; Waktu</TableHead>
                            <TableHead class="text-right">Aksi Komando</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="claim in claims" :key="claim.id">
                            <!-- Officer Roster Info -->
                            <TableCell>
                                <div class="font-bold text-foreground text-sm">
                                    {{ claim.officer?.officer_name || "-" }}
                                </div>
                                <div class="text-muted-foreground text-xs font-mono">
                                    {{ claim.officer?.streamer_name }} • [{{ claim.officer?.department }} #{{ claim.officer?.callsign }}]
                                </div>
                            </TableCell>

                            <!-- User Info -->
                            <TableCell>
                                <div class="font-bold text-primary">
                                    {{ claim.user?.name || "-" }}
                                </div>
                                <div class="text-muted-foreground text-xs">
                                    {{ claim.user?.email }}
                                </div>
                            </TableCell>

                            <!-- Discord Tag -->
                            <TableCell class="font-mono font-bold text-warning">
                                {{ claim.proof_discord_tag || "-" }}
                            </TableCell>

                            <!-- Notes -->
                            <TableCell class="text-foreground max-w-xs truncate text-xs">
                                {{ claim.notes || "-" }}
                            </TableCell>

                            <!-- Status Badge -->
                            <TableCell>
                                <Badge
                                    variant="outline"
                                    :class="cn('uppercase tracking-wider font-bold', statusBadgeClass(claim.status))"
                                >
                                    {{ claim.status }}
                                </Badge>
                                <div class="text-xs text-muted-foreground mt-1 font-mono tabular-nums">
                                    {{ new Date(claim.created_at).toLocaleString() }}
                                </div>
                                <div v-if="claim.review_reason" class="text-xs text-muted-foreground italic mt-0.5">
                                    "{{ claim.review_reason }}"
                                </div>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-right">
                                <div v-if="claim.status === 'pending'" class="flex items-center justify-end gap-1.5">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-success/40 text-success hover:bg-success/15"
                                        @click="openApproveModal(claim)"
                                    >
                                        <Check class="size-3.5 mr-1" />
                                        Setujui
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-destructive/40 text-destructive hover:bg-destructive/15"
                                        @click="openRejectModal(claim)"
                                    >
                                        <X class="size-3.5 mr-1" />
                                        Tolak
                                    </Button>
                                </div>
                                <div v-else class="text-muted-foreground text-xs italic">
                                    Oleh: {{ claim.reviewer?.name || "Admin" }}
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="p-3 bg-muted/40 border-t border-border flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>Total {{ pagination.total }} pengajuan</span>
                <div class="flex items-center gap-1.5">
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="pagination.current_page <= 1"
                        @click="fetchClaims(pagination.current_page - 1)"
                    >
                        <ChevronLeft class="size-3.5 mr-1" />
                        Prev
                    </Button>
                    <span class="px-2 font-bold text-foreground font-mono tabular-nums">
                        {{ pagination.current_page }} / {{ pagination.last_page }}
                    </span>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="pagination.current_page >= pagination.last_page"
                        @click="fetchClaims(pagination.current_page + 1)"
                    >
                        Next
                        <ChevronRight class="size-3.5 ml-1" />
                    </Button>
                </div>
            </div>
        </Card>

        <!-- APPROVE MODAL -->
        <Dialog :open="showApproveModal" @update:open="showApproveModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-success uppercase">
                        <Check class="size-4" />
                        Konfirmasi Persetujuan Klaim
                    </DialogTitle>
                    <DialogDescription>
                        Anda akan menghubungkan akun user <strong class="text-foreground">{{ activeClaim?.user?.name }}</strong> dengan profil dinas perwira <strong class="text-primary">{{ activeClaim?.officer?.officer_name }} ({{ activeClaim?.officer?.callsign }})</strong>.
                    </DialogDescription>
                </DialogHeader>

                <Alert v-if="formError" variant="destructive">
                    <AlertDescription>{{ formError }}</AlertDescription>
                </Alert>

                <div class="space-y-1.5">
                    <Label for="approve-claim-reason" class="text-xs uppercase">Catatan / Alasan Persetujuan (Opsional)</Label>
                    <Input
                        id="approve-claim-reason"
                        v-model="actionReason"
                        type="text"
                        placeholder="Contoh: Terverifikasi via voice channel kepolisian"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showApproveModal = false">
                        Batal
                    </Button>
                    <Button :disabled="isProcessing" @click="handleApprove">
                        {{ isProcessing ? "Menyetujui..." : "Setujui & Bind Perwira" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- REJECT MODAL -->
        <Dialog :open="showRejectModal" @update:open="showRejectModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive uppercase">
                        <X class="size-4" />
                        Penolakan Klaim Perwira
                    </DialogTitle>
                    <DialogDescription>
                        Pengajuan klaim dari <strong class="text-foreground">{{ activeClaim?.user?.name }}</strong> untuk <strong class="text-primary">{{ activeClaim?.officer?.officer_name }}</strong> akan ditolak.
                    </DialogDescription>
                </DialogHeader>

                <Alert v-if="formError" variant="destructive">
                    <AlertDescription>{{ formError }}</AlertDescription>
                </Alert>

                <div class="space-y-1.5">
                    <Label for="reject-claim-reason" class="text-xs uppercase">
                        Alasan Penolakan <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="reject-claim-reason"
                        v-model="actionReason"
                        rows="3"
                        placeholder="Contoh: Tag Discord tidak merespon / Bukan pemilik kanal resmi"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showRejectModal = false">
                        Batal
                    </Button>
                    <Button variant="destructive" :disabled="isProcessing" @click="handleReject">
                        {{ isProcessing ? "Menolak..." : "Tolak Pengajuan" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
