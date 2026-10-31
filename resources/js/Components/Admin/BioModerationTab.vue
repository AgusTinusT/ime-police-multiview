<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import {
    Link2,
    Ban,
    ScrollText,
    Search,
    RefreshCw,
    Check,
    Flag,
    X,
    Trash2,
    ShieldAlert,
    UserX,
    UserCheck,
    ChevronLeft,
    ChevronRight,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
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

const emit = defineEmits(["toast"]);

const subTab = ref("links"); // 'links' | 'domains' | 'audit'

// 1. LINKS MODERATION STATE
const links = ref([]);
const linksPagination = ref({});
const isLoadingLinks = ref(false);
const linkStatusFilter = ref("all");
const linkTypeFilter = ref("all");
const linkSearch = ref("");

// Link action modals
const showModerateModal = ref(false);
const activeLink = ref(null);
const targetStatus = ref("approved");
const moderationReason = ref("");
const alsoBanDomain = ref(false);
const isProcessing = ref(false);

// Shared destructive reason dialog (replaces prompt() for deleteLink, suspendBio, banUser)
const showReasonDialog = ref(false);
const reasonDialogConfig = ref({
    type: "", // 'delete_link' | 'suspend_bio' | 'ban_user'
    title: "",
    description: "",
    reason: "",
    payload: null,
});
const isSubmittingReasonAction = ref(false);

// 2. DOMAIN BAN STATE
const banDomainInput = ref("");
const banDomainReason = ref("");
const isBanningDomain = ref(false);

// 3. AUDIT LOGS STATE
const auditLogs = ref([]);
const auditPagination = ref({});
const isLoadingAudit = ref(false);
const auditActionFilter = ref("all");

const moderationBadgeClass = (status) => {
    if (status === "approved") return "bg-success/15 text-success border-success/30";
    if (status === "flagged") return "bg-warning/15 text-warning border-warning/30";
    if (status === "banned") return "bg-destructive/15 text-destructive border-destructive/30";
    return "bg-muted text-muted-foreground border-border";
};

// Fetch Links
const fetchLinks = async (page = 1) => {
    isLoadingLinks.value = true;
    try {
        const res = await axios.get("/api/v1/admin/bio-links", {
            params: {
                status: linkStatusFilter.value,
                link_type: linkTypeFilter.value,
                search: linkSearch.value,
                page,
            },
        });
        links.value = res.data.links?.data || [];
        linksPagination.value = {
            current_page: res.data.links?.current_page || 1,
            last_page: res.data.links?.last_page || 1,
            total: res.data.links?.total || 0,
        };
    } catch (err) {
        emit("toast", "Gagal memuat link: " + (err.response?.data?.message || err.message), "error");
    } finally {
        isLoadingLinks.value = false;
    }
};

const openModerateModal = (link, status) => {
    activeLink.value = link;
    targetStatus.value = status;
    moderationReason.value = status === "approved" ? "Terverifikasi aman oleh Admin" : "";
    alsoBanDomain.value = false;
    showModerateModal.value = true;
};

const handleModerate = async () => {
    if (!activeLink.value) return;
    if (targetStatus.value === "banned" && (!moderationReason.value || moderationReason.value.trim().length < 3)) {
        emit("toast", "Alasan pemblokiran wajib diisi.", "error");
        return;
    }

    isProcessing.value = true;
    try {
        const res = await axios.patch(`/api/v1/admin/bio-links/${activeLink.value.id}/moderate`, {
            status: targetStatus.value,
            reason: moderationReason.value,
            ban_domain: alsoBanDomain.value,
        });

        emit("toast", res.data.message || "Status moderasi link berhasil diubah.", "success");
        showModerateModal.value = false;
        fetchLinks(linksPagination.value.current_page);
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal mengubah status moderasi.", "error");
    } finally {
        isProcessing.value = false;
    }
};

const deleteLink = (link) => {
    reasonDialogConfig.value = {
        type: "delete_link",
        title: "Hapus Paksa Tautan",
        description: `Masukkan alasan menghapus paksa tautan "${link.title}":`,
        reason: "Pelanggaran SOP Kepolisian",
        payload: link,
    };
    showReasonDialog.value = true;
};

const toggleSuspendProfile = async (profile) => {
    if (!profile) return;
    const isCurrentlySuspended = !!profile.is_suspended;
    const targetSuspended = !isCurrentlySuspended;

    if (targetSuspended) {
        reasonDialogConfig.value = {
            type: "suspend_bio",
            title: "Bekukan Halaman Bio (Kill-Switch)",
            description: `Masukkan alasan pembekuan halaman bio /@${profile.slug}:`,
            reason: "Pelanggaran SOP Kepolisian",
            payload: profile,
        };
        showReasonDialog.value = true;
        return;
    }

    try {
        const res = await axios.patch(`/api/v1/admin/bio-profiles/${profile.id}/suspend`, {
            is_suspended: false,
            reason: "Pencabutan suspend oleh admin",
        });
        profile.is_suspended = false;
        emit("toast", res.data.message || "Suspend bio-link dicabut.", "success");
        fetchLinks(linksPagination.value.current_page);
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal mengubah status suspend bio-link.", "error");
    }
};

const toggleBanUser = async (user) => {
    if (!user) return;
    const isBanned = user.status === "banned";
    const targetUserStatus = isBanned ? "active" : "banned";

    if (!isBanned) {
        reasonDialogConfig.value = {
            type: "ban_user",
            title: "Blokir (Ban) Akun User",
            description: `Masukkan alasan pemblokiran akun user ${user.name}:`,
            reason: "Pelanggaran Akun",
            payload: user,
        };
        showReasonDialog.value = true;
        return;
    }

    try {
        const res = await axios.patch(`/api/v1/admin/users/${user.id}/status`, {
            status: targetUserStatus,
            reason: "Pemulihan akun oleh admin",
        });
        user.status = targetUserStatus;
        emit("toast", res.data.message || "Ban akun dicabut.", "success");
        fetchLinks(linksPagination.value.current_page);
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal mengubah status akun user.", "error");
    }
};

const confirmReasonDialogAction = async () => {
    const { type, reason, payload } = reasonDialogConfig.value;
    if (!reason || !reason.trim()) {
        emit("toast", "Alasan tindakan wajib diisi.", "error");
        return;
    }

    isSubmittingReasonAction.value = true;
    try {
        if (type === "delete_link") {
            const res = await axios.delete(`/api/v1/admin/bio-links/${payload.id}`, {
                data: { reason: reason.trim() },
            });
            emit("toast", res.data.message || "Tautan berhasil dihapus permanen.", "success");
        } else if (type === "suspend_bio") {
            const res = await axios.patch(`/api/v1/admin/bio-profiles/${payload.id}/suspend`, {
                is_suspended: true,
                reason: reason.trim(),
            });
            payload.is_suspended = true;
            emit("toast", res.data.message || "Bio-link dibekukan.", "success");
        } else if (type === "ban_user") {
            const res = await axios.patch(`/api/v1/admin/users/${payload.id}/status`, {
                status: "banned",
                reason: reason.trim(),
            });
            payload.status = "banned";
            emit("toast", res.data.message || "Akun user berhasil di-ban.", "success");
        }
        showReasonDialog.value = false;
        fetchLinks(linksPagination.value.current_page);
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal memproses tindakan.", "error");
    } finally {
        isSubmittingReasonAction.value = false;
    }
};

// Global Domain Ban
const submitBanDomain = async () => {
    if (!banDomainInput.value.trim() || !banDomainReason.value.trim()) {
        emit("toast", "Domain host dan alasan pemblokiran wajib diisi.", "error");
        return;
    }

    isBanningDomain.value = true;
    try {
        const res = await axios.post("/api/v1/admin/link-denylist", {
            host: banDomainInput.value.trim(),
            reason: banDomainReason.value.trim(),
        });

        emit("toast", res.data.message || "Domain berhasil dimasukkan ke denylist global.", "success");
        banDomainInput.value = "";
        banDomainReason.value = "";
        fetchLinks();
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal memblokir domain.", "error");
    } finally {
        isBanningDomain.value = false;
    }
};

// Fetch Audit Logs
const fetchAuditLogs = async (page = 1) => {
    isLoadingAudit.value = true;
    try {
        const res = await axios.get("/api/v1/admin/audit-logs", {
            params: {
                action: auditActionFilter.value,
                page,
            },
        });
        auditLogs.value = res.data.logs?.data || [];
        auditPagination.value = {
            current_page: res.data.logs?.current_page || 1,
            last_page: res.data.logs?.last_page || 1,
            total: res.data.logs?.total || 0,
        };
    } catch (err) {
        emit("toast", "Gagal memuat log audit: " + (err.response?.data?.message || err.message), "error");
    } finally {
        isLoadingAudit.value = false;
    }
};

const onSubTabChange = (tab) => {
    subTab.value = tab;
    if (tab === "links") fetchLinks(1);
    if (tab === "audit") fetchAuditLogs(1);
};

onMounted(() => {
    fetchLinks(1);
});
</script>

<template>
    <div class="space-y-4 text-xs">
        <SectionHeader
            title="Moderasi bio-link"
            description="Kelola tautan publik dan daftar domain terlarang."
        />
        <!-- SUB-NAVIGATION BAR -->
        <Card>
            <CardContent class="p-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <Button
                        size="sm"
                        :variant="subTab === 'links' ? 'default' : 'outline'"
                        @click="onSubTabChange('links')"
                    >
                        <Link2 class="size-3.5 mr-1.5" />
                        Moderasi Tautan Global
                    </Button>
                    <Button
                        size="sm"
                        :variant="subTab === 'domains' ? 'default' : 'outline'"
                        @click="onSubTabChange('domains')"
                    >
                        <Ban class="size-3.5 mr-1.5" />
                        Denylist Domain
                    </Button>
                    <Button
                        size="sm"
                        :variant="subTab === 'audit' ? 'default' : 'outline'"
                        @click="onSubTabChange('audit')"
                    >
                        <ScrollText class="size-3.5 mr-1.5" />
                        Log Audit Keamanan
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- 1. LINKS MODERATION VIEW -->
        <div v-show="subTab === 'links'" class="space-y-3">
            <!-- Filter Toolbar -->
            <Card>
                <CardContent class="p-3.5 flex flex-wrap items-center gap-3 justify-between">
                    <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                        <Input
                            v-model="linkSearch"
                            type="text"
                            placeholder="Cari judul tautan, URL, host..."
                            @keyup.enter="fetchLinks(1)"
                        />
                        <Button variant="outline" class="shrink-0" @click="fetchLinks(1)">
                            <Search class="size-3.5 mr-1.5" />
                            Cari
                        </Button>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Status Filter -->
                        <select
                            v-model="linkStatusFilter"
                            aria-label="Filter status moderasi"
                            class="h-9 bg-background border border-input text-foreground rounded-md px-2.5 text-xs focus:outline-hidden focus:ring-2 focus:ring-ring"
                            @change="fetchLinks(1)"
                        >
                            <option value="all">Semua Status</option>
                            <option value="approved">Approved</option>
                            <option value="flagged">Flagged</option>
                            <option value="banned">Banned</option>
                        </select>

                        <!-- Type Filter -->
                        <select
                            v-model="linkTypeFilter"
                            aria-label="Filter tipe tautan"
                            class="h-9 bg-background border border-input text-foreground rounded-md px-2.5 text-xs focus:outline-hidden focus:ring-2 focus:ring-ring"
                            @change="fetchLinks(1)"
                        >
                            <option value="all">Semua Tipe</option>
                            <option value="donation">Donasi</option>
                            <option value="discord">Discord</option>
                            <option value="officer_recommendation">Squad Mate</option>
                            <option value="social">Social</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </CardContent>
            </Card>

            <!-- Table -->
            <Card class="overflow-hidden">
                <div v-if="isLoadingLinks" class="py-12 text-center text-muted-foreground">
                    Memuat tautan...
                </div>
                <div v-else-if="links.length === 0" class="p-6">
                    <EmptyState
                        :icon="Link2"
                        title="Tautan Tidak Ditemukan"
                        description="Tidak ada tautan bio-link yang sesuai dengan filter pencarian ini."
                    />
                </div>
                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tautan / Profil</TableHead>
                                <TableHead>Tipe &amp; URL Host</TableHead>
                                <TableHead>Status Moderasi</TableHead>
                                <TableHead class="text-right">Aksi Moderasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="link in links" :key="link.id">
                                <TableCell>
                                    <div class="font-bold text-foreground text-sm">
                                        {{ link.title }}
                                    </div>
                                    <div class="text-xs text-muted-foreground truncate max-w-sm">
                                        <a
                                            :href="link.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="hover:text-primary underline"
                                        >
                                            {{ link.url }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-primary/90 font-mono mt-0.5">
                                        Profil: /@{{ link.profile?.slug }} • {{ link.profile?.officer?.officer_name }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge variant="secondary" class="uppercase font-mono">
                                        {{ link.link_type }}
                                    </Badge>
                                    <div class="text-xs text-muted-foreground font-mono mt-1">
                                        {{ link.url_host || "-" }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="cn('uppercase font-bold', moderationBadgeClass(link.moderation_status))"
                                    >
                                        {{ link.moderation_status }}
                                    </Badge>
                                    <div
                                        v-if="link.moderation_reason"
                                        class="text-xs text-muted-foreground italic mt-1 max-w-xs"
                                    >
                                        "{{ link.moderation_reason }}"
                                    </div>
                                </TableCell>

                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        <Button
                                            v-if="link.moderation_status !== 'approved'"
                                            size="sm"
                                            variant="outline"
                                            class="border-success/40 text-success hover:bg-success/15"
                                            @click="openModerateModal(link, 'approved')"
                                        >
                                            <Check class="size-3.5 mr-1" />
                                            Approve
                                        </Button>
                                        <Button
                                            v-if="link.moderation_status !== 'flagged'"
                                            size="sm"
                                            variant="outline"
                                            class="border-warning/40 text-warning hover:bg-warning/15"
                                            @click="openModerateModal(link, 'flagged')"
                                        >
                                            <Flag class="size-3.5 mr-1" />
                                            Flag
                                        </Button>
                                        <Button
                                            v-if="link.moderation_status !== 'banned'"
                                            size="sm"
                                            variant="outline"
                                            class="border-destructive/40 text-destructive hover:bg-destructive/15"
                                            @click="openModerateModal(link, 'banned')"
                                        >
                                            <X class="size-3.5 mr-1" />
                                            Ban
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="outline"
                                            class="size-8 text-muted-foreground hover:text-destructive"
                                            aria-label="Hapus paksa tautan"
                                            title="Hapus Paksa"
                                            @click="deleteLink(link)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                        <Button
                                            v-if="link.profile"
                                            size="sm"
                                            variant="outline"
                                            :class="
                                                link.profile.is_suspended
                                                    ? 'border-warning/40 text-warning hover:bg-warning/15'
                                                    : 'border-destructive/40 text-destructive hover:bg-destructive/15'
                                            "
                                            :title="
                                                link.profile.is_suspended
                                                    ? 'Cabut pembekuan halaman bio ini'
                                                    : 'Bekukan halaman bio profil ini (Kill Switch)'
                                            "
                                            @click="toggleSuspendProfile(link.profile)"
                                        >
                                            <ShieldAlert class="size-3.5 mr-1" />
                                            {{ link.profile.is_suspended ? "Unsuspend Bio" : "Suspend Bio" }}
                                        </Button>
                                        <Button
                                            v-if="link.profile?.officer?.user"
                                            size="sm"
                                            variant="outline"
                                            :class="
                                                link.profile.officer.user.status === 'banned'
                                                    ? 'border-success/40 text-success hover:bg-success/15'
                                                    : 'text-foreground'
                                            "
                                            :title="
                                                link.profile.officer.user.status === 'banned'
                                                    ? 'Cabut ban user pemilik'
                                                    : 'Ban akun user pemilik link ini'
                                            "
                                            @click="toggleBanUser(link.profile.officer.user)"
                                        >
                                            <UserCheck
                                                v-if="link.profile.officer.user.status === 'banned'"
                                                class="size-3.5 mr-1"
                                            />
                                            <UserX v-else class="size-3.5 mr-1" />
                                            {{ link.profile.officer.user.status === "banned" ? "Unban User" : "Ban User" }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Links Pagination -->
                <div
                    v-if="linksPagination.last_page > 1"
                    class="p-3 bg-muted/40 border-t border-border flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span>Total {{ linksPagination.total }} tautan</span>
                    <div class="flex items-center gap-1.5">
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="linksPagination.current_page <= 1"
                            @click="fetchLinks(linksPagination.current_page - 1)"
                        >
                            <ChevronLeft class="size-3.5 mr-1" />
                            Prev
                        </Button>
                        <span class="px-2 font-bold text-foreground font-mono tabular-nums">
                            {{ linksPagination.current_page }} / {{ linksPagination.last_page }}
                        </span>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="linksPagination.current_page >= linksPagination.last_page"
                            @click="fetchLinks(linksPagination.current_page + 1)"
                        >
                            Next
                            <ChevronRight class="size-3.5 ml-1" />
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- 2. DOMAIN BAN VIEW -->
        <div v-show="subTab === 'domains'" class="space-y-4">
            <Card class="max-w-2xl">
                <CardContent class="p-5 space-y-4">
                    <h3 class="text-sm font-bold text-foreground uppercase flex items-center gap-2">
                        <Ban class="size-4 text-destructive" />
                        <span>Masukkan Domain ke Denylist Global</span>
                    </h3>
                    <p class="text-muted-foreground text-xs leading-relaxed">
                        Domain yang dimasukkan ke denylist akan otomatis dinonaktifkan di seluruh profil bio-link perwira, dan tidak dapat ditambahkan kembali oleh perwira manapun (termasuk subdomain terkait).
                    </p>

                    <div class="space-y-1.5">
                        <Label for="ban-domain-host" class="text-xs uppercase">Nama Host / Domain</Label>
                        <Input
                            id="ban-domain-host"
                            v-model="banDomainInput"
                            type="text"
                            placeholder="Contoh: phishing-site.xyz atau judi-online.com"
                            class="font-mono"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="ban-domain-reason" class="text-xs uppercase">
                            Alasan Pemblokiran <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="ban-domain-reason"
                            v-model="banDomainReason"
                            type="text"
                            placeholder="Contoh: Terdeteksi URL scam donasi / phishing kredensial"
                        />
                    </div>

                    <Button
                        variant="destructive"
                        :disabled="isBanningDomain"
                        @click="submitBanDomain"
                    >
                        <Ban class="size-3.5 mr-1.5" />
                        {{ isBanningDomain ? "Memblokir Domain..." : "Blokir Domain Global" }}
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- 3. AUDIT LOGS VIEW -->
        <div v-show="subTab === 'audit'" class="space-y-3">
            <Card>
                <CardContent class="p-3 flex items-center justify-between">
                    <span class="text-muted-foreground text-xs uppercase font-bold">
                        Catatan Audit Aksi Sensitif (Append-Only)
                    </span>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="fetchAuditLogs(auditPagination.current_page)"
                    >
                        <RefreshCw :class="cn('size-3.5 mr-1.5', isLoadingAudit && 'motion-safe:animate-spin')" />
                        Refresh
                    </Button>
                </CardContent>
            </Card>

            <Card class="overflow-hidden">
                <div v-if="isLoadingAudit" class="py-12 text-center text-muted-foreground">
                    Memuat log audit...
                </div>
                <div v-else-if="auditLogs.length === 0" class="p-6">
                    <EmptyState
                        :icon="ScrollText"
                        title="Belum Ada Log Audit"
                        description="Belum ada riwayat aksi sensitif yang tercatat."
                    />
                </div>
                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Waktu</TableHead>
                                <TableHead>Aktor / Admin</TableHead>
                                <TableHead>Aksi</TableHead>
                                <TableHead>Subjek</TableHead>
                                <TableHead>Alasan &amp; Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="log in auditLogs" :key="log.id" class="text-xs">
                                <TableCell class="text-muted-foreground whitespace-nowrap font-mono tabular-nums">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </TableCell>
                                <TableCell class="font-bold text-primary">
                                    {{ log.actor?.name || "System" }}
                                </TableCell>
                                <TableCell class="font-mono font-bold text-warning">
                                    {{ log.action }}
                                </TableCell>
                                <TableCell class="text-muted-foreground font-mono">
                                    {{ log.subject_type }} #{{ log.subject_id }}
                                </TableCell>
                                <TableCell class="text-foreground">
                                    {{ log.reason || "-" }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Audit Pagination -->
                <div
                    v-if="auditPagination.last_page > 1"
                    class="p-3 bg-muted/40 border-t border-border flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span>Total {{ auditPagination.total }} entri audit</span>
                    <div class="flex items-center gap-1.5">
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="auditPagination.current_page <= 1"
                            @click="fetchAuditLogs(auditPagination.current_page - 1)"
                        >
                            <ChevronLeft class="size-3.5 mr-1" />
                            Prev
                        </Button>
                        <span class="px-2 font-bold text-foreground font-mono tabular-nums">
                            {{ auditPagination.current_page }} / {{ auditPagination.last_page }}
                        </span>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="auditPagination.current_page >= auditPagination.last_page"
                            @click="fetchAuditLogs(auditPagination.current_page + 1)"
                        >
                            Next
                            <ChevronRight class="size-3.5 ml-1" />
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- MODERATE MODAL -->
        <Dialog :open="showModerateModal" @update:open="showModerateModal = $event">
            <DialogContent class="sm:max-w-md text-xs">
                <DialogHeader>
                    <DialogTitle class="uppercase text-sm">
                        Moderasi Tautan: {{ targetStatus.toUpperCase() }}
                    </DialogTitle>
                    <DialogDescription>
                        Perbarui status moderasi tautan publik perwira.
                    </DialogDescription>
                </DialogHeader>

                <div class="text-xs text-muted-foreground space-y-1 p-3 rounded-lg bg-muted/50 border border-border">
                    <p>Judul: <strong class="text-foreground">{{ activeLink?.title }}</strong></p>
                    <p class="truncate">URL: <span class="text-primary font-mono">{{ activeLink?.url }}</span></p>
                </div>

                <div class="space-y-1.5">
                    <Label for="moderate-link-reason" class="text-xs uppercase">Alasan Moderasi</Label>
                    <Input
                        id="moderate-link-reason"
                        v-model="moderationReason"
                        type="text"
                        placeholder="Contoh: Link disetujui / Link terindikasi penipuan"
                    />
                </div>

                <div v-if="targetStatus === 'banned'" class="flex items-center gap-2.5 pt-1">
                    <Switch
                        id="banDomainChk"
                        :checked="alsoBanDomain"
                        @update:checked="alsoBanDomain = $event"
                    />
                    <Label for="banDomainChk" class="text-xs text-destructive cursor-pointer">
                        Blokir juga seluruh domain ({{ activeLink?.url_host }}) secara global
                    </Label>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showModerateModal = false">
                        Batal
                    </Button>
                    <Button :disabled="isProcessing" @click="handleModerate">
                        {{ isProcessing ? "Menyimpan..." : "Simpan Moderasi" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- DESTRUCTIVE REASON DIALOG (Delete Link / Suspend Bio / Ban User) -->
        <Dialog :open="showReasonDialog" @update:open="showReasonDialog = $event">
            <DialogContent class="sm:max-w-md text-xs">
                <DialogHeader>
                    <DialogTitle class="text-destructive uppercase text-sm">
                        {{ reasonDialogConfig.title }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ reasonDialogConfig.description }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-1.5">
                    <Label for="destructive-action-reason" class="text-xs uppercase">
                        Alasan Tindakan <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="destructive-action-reason"
                        v-model="reasonDialogConfig.reason"
                        rows="3"
                        placeholder="Masukkan alasan tindakan komando..."
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showReasonDialog = false">
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="isSubmittingReasonAction"
                        @click="confirmReasonDialogAction"
                    >
                        {{ isSubmittingReasonAction ? "Memproses..." : "Konfirmasi Tindakan" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
