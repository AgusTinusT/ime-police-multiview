<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import { Award, RefreshCw, Check, X, FileCheck2 } from "@lucide/vue";
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
import { EmptyState } from "@/Components/tactical";
import { cn } from "@/lib/utils";

const emit = defineEmits(["toast"]);

const requests = ref([]);
const isLoading = ref(false);
const statusFilter = ref("pending"); // 'pending' | 'approved' | 'rejected' | 'all'

// Review dialog state
const showReviewDialog = ref(false);
const reviewMode = ref("approve"); // 'approve' | 'reject'
const activeRequest = ref(null);
const adminReason = ref("");
const formError = ref("");
const isProcessing = ref(false);

const statusOptions = [
    { key: "pending", label: "Pending" },
    { key: "approved", label: "Disetujui" },
    { key: "rejected", label: "Ditolak" },
    { key: "all", label: "Semua" },
];

const statusBadgeClass = (status) => {
    if (status === "pending") return "bg-warning/15 text-warning border-warning/30";
    if (status === "approved") return "bg-success/15 text-success border-success/30";
    return "bg-destructive/15 text-destructive border-destructive/30";
};

const fetchRequests = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get("/api/v1/admin/officer-change-requests", {
            params: { status: statusFilter.value },
        });
        requests.value = res.data.change_requests || [];
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal memuat daftar pengajuan.", "error");
    } finally {
        isLoading.value = false;
    }
};

const openApproveDialog = (req) => {
    activeRequest.value = req;
    reviewMode.value = "approve";
    adminReason.value = "Disetujui Komando SASP";
    formError.value = "";
    showReviewDialog.value = true;
};

const openRejectDialog = (req) => {
    activeRequest.value = req;
    reviewMode.value = "reject";
    adminReason.value = "Tidak memenuhi persyaratan masa dinas";
    formError.value = "";
    showReviewDialog.value = true;
};

const submitReview = async () => {
    if (!activeRequest.value) return;

    if (reviewMode.value === "reject" && !adminReason.value.trim()) {
        formError.value = "Alasan penolakan wajib diisi.";
        return;
    }

    isProcessing.value = true;
    formError.value = "";

    try {
        if (reviewMode.value === "approve") {
            const res = await axios.post(`/api/v1/admin/officer-change-requests/${activeRequest.value.id}/approve`, {
                reason: adminReason.value.trim() || undefined,
            });
            emit("toast", res.data.message || "Pengajuan berhasil disetujui!", "success");
        } else {
            const res = await axios.post(`/api/v1/admin/officer-change-requests/${activeRequest.value.id}/reject`, {
                reason: adminReason.value.trim(),
            });
            emit("toast", res.data.message || "Pengajuan telah ditolak.", "success");
        }
        showReviewDialog.value = false;
        fetchRequests();
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal memproses pengajuan.";
        formError.value = msg;
        emit("toast", msg, "error");
    } finally {
        isProcessing.value = false;
    }
};

onMounted(() => {
    fetchRequests();
});
</script>

<template>
    <div class="space-y-5 text-xs">
        <!-- HEADER & FILTER BAR -->
        <Card>
            <CardContent class="p-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-foreground uppercase flex items-center gap-2 flex-wrap">
                        <Award class="size-4 text-primary" />
                        <span>Pengajuan Pangkat &amp; Kesatuan (PRD v3)</span>
                        <Badge variant="outline" class="bg-info/15 text-info border-info/30 font-bold">
                            KOMANDO REVIEW
                        </Badge>
                    </h2>
                    <p class="text-muted-foreground text-xs mt-0.5">
                        Tinjau dan verifikasi pengajuan kenaikan pangkat atau mutasi kesatuan dari perwira aktif.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex rounded-lg bg-muted p-1 border border-border gap-1">
                        <Button
                            v-for="st in statusOptions"
                            :key="st.key"
                            size="sm"
                            :variant="statusFilter === st.key ? 'default' : 'ghost'"
                            @click="statusFilter = st.key; fetchRequests()"
                        >
                            {{ st.label }}
                        </Button>
                    </div>

                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="isLoading"
                        @click="fetchRequests"
                    >
                        <RefreshCw :class="cn('size-3.5 mr-1.5', isLoading && 'motion-safe:animate-spin')" />
                        {{ isLoading ? "Memuat..." : "Refresh" }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- TABLE OF REQUESTS -->
        <Card class="overflow-hidden">
            <div v-if="isLoading" class="p-8 text-center text-muted-foreground">
                Memuat data pengajuan...
            </div>

            <div v-else-if="requests.length === 0" class="p-6">
                <EmptyState
                    :icon="FileCheck2"
                    title="Belum Ada Pengajuan"
                    :description="`Tidak ada pengajuan mutasi / pangkat dengan status ${statusFilter}.`"
                />
            </div>

            <div v-else class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Perwira &amp; Kesatuan</TableHead>
                            <TableHead>Jenis &amp; Perubahan</TableHead>
                            <TableHead>Alasan &amp; Nomor Sprin</TableHead>
                            <TableHead>Waktu &amp; Reviewer</TableHead>
                            <TableHead class="text-center">Status &amp; Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="req in requests" :key="req.id">
                            <!-- Officer info -->
                            <TableCell>
                                <div class="font-bold text-foreground text-sm">
                                    {{ req.officer?.officer_name || "Perwira #" + req.officer_id }}
                                </div>
                                <div class="text-xs text-muted-foreground font-mono">
                                    {{ req.officer?.department || "-" }} • Callsign #{{ req.officer?.callsign || "-" }}
                                </div>
                                <div v-if="req.officer?.user" class="text-xs text-muted-foreground">
                                    Akun: {{ req.officer.user.name }} ({{ req.officer.user.email }})
                                </div>
                            </TableCell>

                            <!-- Field & Change details -->
                            <TableCell>
                                <div class="flex items-center gap-1.5 mb-1">
                                    <Badge variant="secondary" class="uppercase font-bold">
                                        {{ req.field === "rank_id" ? "Kenaikan Pangkat" : "Mutasi Kesatuan" }}
                                    </Badge>
                                </div>
                                <div class="text-foreground">
                                    Tujuan: <strong class="text-primary">{{ req.new_value }}</strong>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Sebelumnya: {{ req.old_value || "(belum diatur)" }}
                                </div>
                            </TableCell>

                            <!-- Reason from officer -->
                            <TableCell class="max-w-xs">
                                <p class="text-foreground text-xs italic leading-relaxed">
                                    "{{ req.reason }}"
                                </p>
                                <p v-if="req.admin_reason" class="text-xs text-warning mt-1">
                                    Catatan: {{ req.admin_reason }}
                                </p>
                            </TableCell>

                            <!-- Timestamps & Reviewer -->
                            <TableCell class="text-xs text-muted-foreground font-mono tabular-nums">
                                <div>Diajukan: {{ new Date(req.created_at).toLocaleString("id-ID") }}</div>
                                <div v-if="req.reviewer" class="text-muted-foreground mt-0.5 font-sans">
                                    Direview: {{ req.reviewer.name }}
                                </div>
                            </TableCell>

                            <!-- Status & Actions -->
                            <TableCell class="text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    <Badge
                                        variant="outline"
                                        :class="cn('uppercase font-bold', statusBadgeClass(req.status))"
                                    >
                                        {{ req.status }}
                                    </Badge>

                                    <div v-if="req.status === 'pending'" class="flex items-center gap-1.5 mt-1">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="border-success/40 text-success hover:bg-success/15"
                                            title="Setujui dan mutasikan pangkat/kesatuan perwira ini"
                                            @click="openApproveDialog(req)"
                                        >
                                            <Check class="size-3.5 mr-1" />
                                            Setujui
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="border-destructive/40 text-destructive hover:bg-destructive/15"
                                            title="Tolak pengajuan ini"
                                            @click="openRejectDialog(req)"
                                        >
                                            <X class="size-3.5 mr-1" />
                                            Tolak
                                        </Button>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>

        <!-- REVIEW REASON DIALOG -->
        <Dialog :open="showReviewDialog" @update:open="showReviewDialog = $event">
            <DialogContent class="sm:max-w-md text-xs">
                <DialogHeader>
                    <DialogTitle
                        :class="cn('flex items-center gap-2 uppercase text-sm', reviewMode === 'approve' ? 'text-success' : 'text-destructive')"
                    >
                        <Check v-if="reviewMode === 'approve'" class="size-4" />
                        <X v-else class="size-4" />
                        <span>
                            {{ reviewMode === "approve" ? "Setujui Pengajuan" : "Tolak Pengajuan" }}
                        </span>
                    </DialogTitle>
                    <DialogDescription>
                        {{ reviewMode === "approve" ? "Setujui" : "Tolak" }} pengajuan
                        <strong class="text-foreground">{{ activeRequest?.field === "rank_id" ? "pangkat" : "kesatuan" }}</strong>
                        untuk <strong class="text-primary">{{ activeRequest?.officer?.officer_name }}</strong>
                        (Tujuan: <strong class="text-foreground">{{ activeRequest?.new_value }}</strong>).
                    </DialogDescription>
                </DialogHeader>

                <Alert v-if="formError" variant="destructive">
                    <AlertDescription>{{ formError }}</AlertDescription>
                </Alert>

                <div class="space-y-1.5">
                    <Label for="change-request-reason" class="text-xs uppercase">
                        {{ reviewMode === "approve" ? "Catatan Komando (Opsional)" : "Alasan Penolakan (Wajib)" }}
                        <span v-if="reviewMode === 'reject'" class="text-destructive">*</span>
                    </Label>
                    <Input
                        v-if="reviewMode === 'approve'"
                        id="change-request-reason"
                        v-model="adminReason"
                        type="text"
                        placeholder="Disetujui Komando SASP"
                    />
                    <Textarea
                        v-else
                        id="change-request-reason"
                        v-model="adminReason"
                        rows="3"
                        placeholder="Tidak memenuhi persyaratan masa dinas"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showReviewDialog = false">
                        Batal
                    </Button>
                    <Button
                        :variant="reviewMode === 'approve' ? 'default' : 'destructive'"
                        :disabled="isProcessing"
                        @click="submitReview"
                    >
                        {{
                            isProcessing
                                ? "Memproses..."
                                : reviewMode === "approve"
                                ? "Setujui Pengajuan"
                                : "Tolak Pengajuan"
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
