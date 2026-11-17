<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import {
    Link2,
    Ban,
    ScrollText,
    Search,
    RefreshCw,
    RotateCcw,
    Check,
    Flag,
    X,
    Trash2,
    ShieldAlert,
    UserX,
    UserCheck,
    MoreHorizontal,
    ChevronLeft,
    ChevronRight,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
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

const hasActiveLinkFilters = computed(() => {
    return (
        !!linkSearch.value.trim() ||
        linkStatusFilter.value !== "all" ||
        linkTypeFilter.value !== "all"
    );
});

const resetLinkFilters = () => {
    linkSearch.value = "";
    linkStatusFilter.value = "all";
    linkTypeFilter.value = "all";
    fetchLinks(1);
};

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

const onRefreshCurrent = () => {
    if (subTab.value === "links") fetchLinks(linksPagination.value.current_page || 1);
    if (subTab.value === "audit") fetchAuditLogs(auditPagination.value.current_page || 1);
};

onMounted(() => {
    fetchLinks(1);
});
</script>

<template>
    <div class="space-y-4 text-xs font-mono">
        <SectionHeader
            title="Moderasi bio-link"
            description="Kelola tautan publik dan daftar domain terlarang."
        >
            <template #actions>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <Button
                        size="sm"
                        :variant="subTab === 'links' ? 'default' : 'outline'"
                        class="gap-1.5 font-mono text-xs h-8"
                        @click="onSubTabChange('links')"
                    >
                        <Link2 class="size-3.5" />
                        <span>Tautan</span>
                    </Button>
                    <Button
                        size="sm"
                        :variant="subTab === 'domains' ? 'default' : 'outline'"
                        class="gap-1.5 font-mono text-xs h-8"
                        @click="onSubTabChange('domains')"
                    >
                        <Ban class="size-3.5" />
                        <span>Denylist Domain</span>
                    </Button>
                    <Button
                        size="sm"
                        :variant="subTab === 'audit' ? 'default' : 'outline'"
                        class="gap-1.5 font-mono text-xs h-8"
                        @click="onSubTabChange('audit')"
                    >
                        <ScrollText class="size-3.5" />
                        <span>Log Audit</span>
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        class="h-8 gap-1.5 font-mono text-xs"
                        :disabled="isLoadingLinks || isLoadingAudit"
                        @click="onRefreshCurrent"
                    >
                        <RefreshCw :class="cn('size-3.5', (isLoadingLinks || isLoadingAudit) && 'motion-safe:animate-spin')" />
                        <span class="hidden sm:inline">Refresh</span>
                    </Button>
                </div>
            </template>
        </SectionHeader>

        <!-- 1. LINKS MODERATION VIEW -->
        <div v-show="subTab === 'links'" class="space-y-4">
            <!-- Filter & Search Toolbar -->
            <Card class="p-3.5">
                <div class="flex flex-wrap items-center gap-3 justify-between">
                    <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                        <div class="relative flex-1">
                            <Search class="size-3.5 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
                            <Input
                                v-model="linkSearch"
                                type="text"
                                placeholder="Cari judul tautan, URL, host, atau streamer..."
                                class="pl-9 h-9 text-xs font-mono"
                                @keyup.enter="fetchLinks(1)"
                            />
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-9 shrink-0 gap-1.5 font-mono"
                            @click="fetchLinks(1)"
                        >
                            <Search class="size-3.5" />
                            <span>Cari</span>
                        </Button>
                        <Button
                            v-if="hasActiveLinkFilters"
                            variant="ghost"
                            size="sm"
                            class="h-9 shrink-0 text-muted-foreground hover:text-foreground gap-1.5 font-mono"
                            title="Reset filter & pencarian"
                            @click="resetLinkFilters"
                        >
                            <RotateCcw class="size-3.5" />
                            <span class="hidden sm:inline">Reset</span>
                        </Button>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Status Filter -->
                        <select
                            v-model="linkStatusFilter"
                            aria-label="Filter status moderasi"
                            class="h-9 rounded-md border border-input bg-background px-2.5 text-xs font-mono text-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
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
                            class="h-9 rounded-md border border-input bg-background px-2.5 text-xs font-mono text-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            @change="fetchLinks(1)"
                        >
                            <option value="all">Semua Tipe</option>
                            <option value="donation">Donasi</option>
                            <option value="discord">Discord</option>
                            <option value="officer_recommendation">Squad Mate</option>
                            <option value="social">Social</option>
                            <option value="custom">Custom</option>
                        </select>

                        <div class="text-xs text-muted-foreground font-mono whitespace-nowrap pl-2 border-l border-border hidden md:block">
                            Total: <strong class="text-foreground">{{ linksPagination.total || 0 }}</strong>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Table -->
            <Card class="overflow-hidden">
                <div v-if="isLoadingLinks" class="py-12 text-center text-muted-foreground font-mono">
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
                            <TableRow class="bg-muted/40 uppercase text-xs font-mono">
                                <TableHead>Tautan / Profil</TableHead>
                                <TableHead>Tipe &amp; Host</TableHead>
                                <TableHead>Status Moderasi</TableHead>
                                <TableHead class="text-right">Aksi Moderasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="link in links" :key="link.id">
                                <TableCell>
                                    <div class="font-bold text-foreground text-sm font-sans">
                                        {{ link.title }}
                                    </div>
                                    <div class="text-xs text-muted-foreground truncate max-w-sm font-mono mt-0.5">
                                        <a
                                            :href="link.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="hover:text-primary underline"
                                        >
                                            {{ link.url }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-primary/90 font-mono mt-1">
                                        Profil: /@{{ link.profile?.slug }} • {{ link.profile?.officer?.officer_name }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge variant="secondary" class="uppercase font-mono text-xs">
                                        {{ link.link_type }}
                                    </Badge>
                                    <div class="text-xs text-muted-foreground font-mono mt-1">
                                        {{ link.url_host || "-" }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="cn('uppercase font-bold text-xs', moderationBadgeClass(link.moderation_status))"
                                    >
                                        {{ link.moderation_status }}
                                    </Badge>
                                    <div
                                        v-if="link.moderation_reason"
                                        class="text-xs text-muted-foreground italic mt-1 max-w-xs font-sans"
                                    >
                                        "{{ link.moderation_reason }}"
                                    </div>
                                </TableCell>

                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Primary Moderation Quick Buttons -->
                                        <Button
                                            v-if="link.moderation_status !== 'approved'"
                                            size="sm"
                                            variant="outline"
                                            class="h-7 px-2 text-xs border-success/40 text-success hover:bg-success/15 font-mono"
                                            @click="openModerateModal(link, 'approved')"
                                        >
                                            <Check class="size-3 mr-1" />
                                            <span>Approve</span>
                                        </Button>
                                        <Button
                                            v-if="link.moderation_status !== 'flagged'"
                                            size="sm"
                                            variant="outline"
                                            class="h-7 px-2 text-xs border-warning/40 text-warning hover:bg-warning/15 font-mono"
                                            @click="openModerateModal(link, 'flagged')"
                                        >
                                            <Flag class="size-3 mr-1" />
                                            <span>Flag</span>
                                        </Button>
                                        <Button
                                            v-if="link.moderation_status !== 'banned'"
                                            size="sm"
                                            variant="outline"
                                            class="h-7 px-2 text-xs border-destructive/40 text-destructive hover:bg-destructive/15 font-mono"
                                            @click="openModerateModal(link, 'banned')"
                                        >
                                            <X class="size-3 mr-1" />
                                            <span>Ban</span>
                                        </Button>

                                        <!-- Advanced / Destructive Command Actions Dropdown -->
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    class="size-7 text-muted-foreground hover:text-foreground"
                                                    title="Aksi Komando Lanjutan"
                                                >
                                                    <MoreHorizontal class="size-3.5" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-52 font-mono text-xs">
                                                <DropdownMenuItem
                                                    class="text-destructive gap-2 cursor-pointer"
                                                    @click="deleteLink(link)"
                                                >
                                                    <Trash2 class="size-3.5" />
                                                    <span>Hapus Paksa Tautan</span>
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator v-if="link.profile" />

                                                <DropdownMenuItem
                                                    v-if="link.profile"
                                                    class="gap-2 cursor-pointer"
                                                    @click="toggleSuspendProfile(link.profile)"
                                                >
                                                    <ShieldAlert
                                                        class="size-3.5"
                                                        :class="link.profile.is_suspended ? 'text-warning' : 'text-destructive'"
                                                    />
                                                    <span>
                                                        {{ link.profile.is_suspended ? "Cabut Suspend Bio" : "Bekukan Bio (Kill-Switch)" }}
                                                    </span>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="link.profile?.officer?.user"
                                                    class="gap-2 cursor-pointer"
                                                    @click="toggleBanUser(link.profile.officer.user)"
                                                >
                                                    <UserCheck
                                                        v-if="link.profile.officer.user.status === 'banned'"
                                                        class="size-3.5 text-success"
                                                    />
                                                    <UserX v-else class="size-3.5 text-destructive" />
                                                    <span>
                                                        {{ link.profile.officer.user.status === "banned" ? "Unban User Pemilik" : "Ban User Pemilik" }}
                                                    </span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Links Pagination -->
                <div
                    v-if="linksPagination.last_page > 1"
                    class="p-3 bg-muted/40 border-t border-border flex items-center justify-between text-xs text-muted-foreground font-mono"
                >
                    <span>Total {{ linksPagination.total }} tautan</span>
                    <div class="flex items-center gap-1.5">
                        <Button
                            size="sm"
                            variant="outline"
                            class="h-7 px-2 font-mono text-xs"
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
                            class="h-7 px-2 font-mono text-xs"
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
        <div v-show="subTab === 'domains'" class="space-y-4 font-mono">
            <Card class="max-w-3xl">
                <CardHeader class="pb-3 border-b border-border/50">
                    <div class="flex items-center gap-2 text-destructive font-bold text-sm uppercase">
                        <Ban class="size-4" />
                        <CardTitle class="text-sm font-bold uppercase font-mono">
                            Denylist Domain Global
                        </CardTitle>
                    </div>
                    <CardDescription class="text-xs font-mono">
                        Domain yang dimasukkan ke denylist otomatis dinonaktifkan di seluruh profil bio-link perwira, dan diblokir secara permanen dari penambahan baru (termasuk seluruh subdomain terkait).
                    </CardDescription>
                </CardHeader>

                <CardContent class="p-5 space-y-4 font-mono text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="ban-domain-host" class="text-xs uppercase font-bold">
                                Nama Host / Domain <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="ban-domain-host"
                                v-model="banDomainInput"
                                type="text"
                                placeholder="Contoh: judi-online.com atau scam-donate.xyz"
                                class="font-mono text-xs"
                                @keyup.enter="submitBanDomain"
                            />
                            <p class="text-xs text-muted-foreground">
                                Cukup masukkan nama domain atau URL, skema (https://) dan www otomatis dibersihkan.
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="ban-domain-reason" class="text-xs uppercase font-bold">
                                Alasan Pemblokiran <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="ban-domain-reason"
                                v-model="banDomainReason"
                                type="text"
                                placeholder="Contoh: Scam donasi / Phishing kredensial"
                                class="text-xs"
                                @keyup.enter="submitBanDomain"
                            />
                            <p class="text-xs text-muted-foreground">
                                Alasan akan dicatat ke log audit keamanan dan riwayat moderasi.
                            </p>
                        </div>
                    </div>

                    <div class="pt-3 flex flex-wrap items-center justify-between gap-3 border-t border-border/50">
                        <div class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <ShieldAlert class="size-3.5 text-warning shrink-0" />
                            <span>Tindakan berlaku instan di seluruh sistem (Global Enforcement Level 3).</span>
                        </div>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="isBanningDomain || !banDomainInput.trim() || !banDomainReason.trim()"
                            class="gap-1.5 font-bold font-mono"
                            @click="submitBanDomain"
                        >
                            <Ban class="size-3.5" :class="{ 'motion-safe:animate-spin': isBanningDomain }" />
                            <span>{{ isBanningDomain ? "Memblokir Domain..." : "Blokir Domain Global" }}</span>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- 3. AUDIT LOGS VIEW -->
        <div v-show="subTab === 'audit'" class="space-y-4 font-mono">
            <Card class="p-3.5">
                <div class="flex flex-wrap items-center justify-between gap-3 font-mono">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-muted-foreground uppercase font-bold">Filter Aksi:</span>
                        <select
                            v-model="auditActionFilter"
                            aria-label="Filter aksi audit"
                            class="h-9 rounded-md border border-input bg-background px-2.5 text-xs font-mono text-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            @change="fetchAuditLogs(1)"
                        >
                            <option value="all">Semua Aksi</option>
                            <option value="bio_link.moderated">Moderasi Tautan</option>
                            <option value="bio_link.force_deleted">Hapus Paksa Tautan</option>
                            <option value="domain.banned">Blokir Domain Global</option>
                            <option value="bio_profile.suspended">Bekukan Profil Bio</option>
                            <option value="bio_profile.unsuspended">Buka Pembekuan Bio</option>
                            <option value="user.banned">Ban Akun User</option>
                            <option value="user.unbanned">Unban Akun User</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground font-mono">
                            Total: <strong class="text-foreground">{{ auditPagination.total || 0 }}</strong> log
                        </span>
                        <Button
                            size="sm"
                            variant="outline"
                            class="h-9 gap-1.5 font-mono text-xs"
                            :disabled="isLoadingAudit"
                            @click="fetchAuditLogs(auditPagination.current_page || 1)"
                        >
                            <RefreshCw :class="cn('size-3.5', isLoadingAudit && 'motion-safe:animate-spin')" />
                            <span>Refresh</span>
                        </Button>
                    </div>
                </div>
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
                            <TableRow class="bg-muted/40 uppercase text-xs font-mono">
                                <TableHead>Waktu</TableHead>
                                <TableHead>Aktor / Admin</TableHead>
                                <TableHead>Aksi</TableHead>
                                <TableHead>Subjek</TableHead>
                                <TableHead>Alasan &amp; Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="log in auditLogs" :key="log.id" class="text-xs font-mono">
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
                                <TableCell class="text-foreground font-sans">
                                    {{ log.reason || "-" }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Audit Pagination -->
                <div
                    v-if="auditPagination.last_page > 1"
                    class="p-3 bg-muted/40 border-t border-border flex items-center justify-between text-xs text-muted-foreground font-mono"
                >
                    <span>Total {{ auditPagination.total }} entri audit</span>
                    <div class="flex items-center gap-1.5">
                        <Button
                            size="sm"
                            variant="outline"
                            class="h-7 px-2 font-mono text-xs"
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
                            class="h-7 px-2 font-mono text-xs"
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
