<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import axios from "axios";
import { toast as sonnerToast } from "vue-sonner";
import OfficerClaimsTab from "@/Components/Admin/OfficerClaimsTab.vue";
import BioModerationTab from "@/Components/Admin/BioModerationTab.vue";
import AdminChangeRequestsTab from "@/Components/Admin/AdminChangeRequestsTab.vue";
import HeroSlidesTab from "@/Components/Admin/HeroSlidesTab.vue";
import SquadsTab from "@/Components/Admin/SquadsTab.vue";
import RecommendedSitesTab from "@/Components/Admin/RecommendedSitesTab.vue";
import OfficerBindingModal from "@/Components/Admin/OfficerBindingModal.vue";
import OfficerDetailSheet from "@/Components/Admin/OfficerDetailSheet.vue";
import {
    PageContainer,
    PageHeader,
    SectionHeader,
    TacticalFrame,
    StatTile,
    DeptBadge,
    OfficerAvatar,
    LiveBadge,
    EmptyState,
} from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Switch } from "@/Components/ui/switch";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import { Checkbox } from "@/Components/ui/checkbox";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
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
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Award,
    Lock,
    Search,
    X,
    Plus,
    RefreshCw,
    Clock,
    Pencil,
    Trash2,
    MoreHorizontal,
    UserCheck,
    UserX,
    Link2,
    ShieldAlert,
    Ban,
    Radio,
} from "@lucide/vue";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_officers: 0,
            active_officers: 0,
            live_streams: 0,
            lspd: 0,
            bcso: 0,
            sasp: 0,
            sapr: 0,
            pending_claims: 0,
        }),
    },
    agenciesMaster: {
        type: Array,
        default: () => [],
    },
    usersList: {
        type: Array,
        default: () => [],
    },
    auth: {
        type: Object,
        default: () => ({ user: null }),
    },
});

// Tactical Bio-Link & Officer Binding Modal State (T-05)
const showBindingModal = ref(false);
const bindingMode = ref("bind"); // 'bind' | 'unbind' | 'slug'
const bindingOfficer = ref(null);

const openBindModal = (officer) => {
    bindingOfficer.value = officer;
    bindingMode.value = "bind";
    showBindingModal.value = true;
};

const openUnbindModal = (officer) => {
    bindingOfficer.value = officer;
    bindingMode.value = "unbind";
    showBindingModal.value = true;
};

const openSlugModal = (officer) => {
    bindingOfficer.value = officer;
    bindingMode.value = "slug";
    showBindingModal.value = true;
};

const reasonDialog = ref({
    show: false,
    mode: "suspend", // 'suspend' | 'ban'
    officer: null,
    reason: "",
    isSubmitting: false,
});

const toggleSuspendProfile = async (officer) => {
    if (!officer?.bio_profile) return;
    const isCurrentlySuspended = !!officer.bio_profile.is_suspended;
    const targetSuspended = !isCurrentlySuspended;

    if (targetSuspended) {
        reasonDialog.value = {
            show: true,
            mode: "suspend",
            officer,
            reason: "Pelanggaran SOP Kepolisian",
            isSubmitting: false,
        };
        return;
    }

    try {
        const res = await axios.patch(`/api/v1/admin/bio-profiles/${officer.bio_profile.id}/suspend`, {
            is_suspended: false,
            reason: "Pencabutan suspend oleh admin",
        });
        officer.bio_profile.is_suspended = false;
        officer.bio_profile.suspension_reason = null;
        showToast(res.data.message || "Suspend bio-link dicabut.", "success");
    } catch (err) {
        showToast(err.response?.data?.message || "Gagal mengubah status suspend bio-link.", "error");
    }
};

const toggleBanUser = async (officer) => {
    if (!officer?.user) return;
    const isBanned = officer.user.status === 'banned';

    if (!isBanned) {
        reasonDialog.value = {
            show: true,
            mode: "ban",
            officer,
            reason: "Pelanggaran Akun",
            isSubmitting: false,
        };
        return;
    }

    try {
        const res = await axios.patch(`/api/v1/admin/users/${officer.user.id}/status`, {
            status: "active",
            reason: "Pemulihan akun oleh admin",
        });
        officer.user.status = "active";
        showToast(res.data.message || "Ban akun dicabut.", "success");
    } catch (err) {
        showToast(err.response?.data?.message || "Gagal mengubah status akun user.", "error");
    }
};

const submitReasonDialog = async () => {
    const { mode, officer, reason } = reasonDialog.value;
    if (!officer) return;
    if (!reason || !reason.trim()) {
        showToast(
            mode === "suspend"
                ? "Alasan pembekuan wajib diisi."
                : "Alasan pemblokiran akun wajib diisi.",
            "error",
        );
        return;
    }
    reasonDialog.value.isSubmitting = true;
    try {
        if (mode === "suspend") {
            const res = await axios.patch(
                `/api/v1/admin/bio-profiles/${officer.bio_profile.id}/suspend`,
                {
                    is_suspended: true,
                    reason: reason.trim(),
                },
            );
            officer.bio_profile.is_suspended = true;
            officer.bio_profile.suspension_reason = reason.trim();
            showToast(res.data.message || "Bio-link dibekukan.", "success");
        } else {
            const res = await axios.patch(
                `/api/v1/admin/users/${officer.user.id}/status`,
                {
                    status: "banned",
                    reason: reason.trim(),
                },
            );
            officer.user.status = "banned";
            showToast(
                res.data.message || "Akun user berhasil di-ban.",
                "success",
            );
        }
        reasonDialog.value.show = false;
    } catch (err) {
        showToast(
            err.response?.data?.message || "Gagal memproses tindakan moderasi.",
            "error",
        );
    } finally {
        reasonDialog.value.isSubmitting = false;
    }
};

// State
const officers = ref([]);
const agenciesList = ref(props.agenciesMaster || []);
const isLoading = ref(false);
const searchQuery = ref("");
const selectedDept = ref("ALL");
const selectedStatus = ref("ALL"); // ALL, ACTIVE, DISABLED

// Action states (loading spinners)
const isSyncingStreams = ref(false);
const isSyncingSubs = ref(false);
const isCheckingChannel = ref(false);
const isSavingOfficer = ref(false);

// Toast Notification via Sonner
const showToast = (message, type = "success") => {
    if (type === "error") {
        sonnerToast.error(message);
    } else if (type === "info") {
        sonnerToast.info(message);
    } else {
        sonnerToast.success(message);
    }
};

// Modal State: Officer
const showModal = ref(false);
const isEditMode = ref(false);
const showDeleteConfirm = ref(false);
const officerToDelete = ref(null);

// Duty Time Adjustment Modal State
const showDutyModal = ref(false);
const dutyOfficer = ref(null);
const dutyAction = ref("add"); // 'add', 'subtract', 'set'
const dutyMinutesInput = ref(60);
const isAdjustingDuty = ref(false);

const openDutyModal = (officer) => {
    dutyOfficer.value = officer;
    dutyAction.value = "add";
    dutyMinutesInput.value = 60;
    showDutyModal.value = true;
};

const handleAdjustDuty = async () => {
    if (!dutyOfficer.value) return;
    isAdjustingDuty.value = true;
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await axios.patch(
            `/api/v1/officers/${dutyOfficer.value.id}/adjust-duty`,
            {
                action: dutyAction.value,
                minutes: dutyMinutesInput.value,
            },
            {
                headers: { "X-CSRF-TOKEN": csrfToken },
            },
        );
        if (res.data && res.data.status === "success") {
            showToast(res.data.message, "success");
            dutyOfficer.value.monthly_duty_minutes =
                res.data.monthly_duty_minutes;
            showDutyModal.value = false;
            fetchOfficers();
        } else {
            showToast(
                res.data?.message || "Gagal mengubah waktu patroli",
                "error",
            );
        }
    } catch (e) {
        showToast(
            "Gagal mengubah waktu patroli: " +
                (e.response?.data?.message || e.message),
            "error",
        );
    } finally {
        isAdjustingDuty.value = false;
    }
};

const toggleHashtagBypass = async (officer) => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await axios.patch(
            `/api/v1/officers/${officer.id}/toggle-hashtag-bypass`,
            {},
            {
                headers: { "X-CSRF-TOKEN": csrfToken },
            },
        );
        if (res.data && res.data.status === "success") {
            officer.bypass_hashtag_check = res.data.bypass_hashtag_check;
            showToast(res.data.message, "success");
        } else {
            showToast(
                res.data?.message || "Gagal mengubah status bypass hashtag",
                "error",
            );
        }
    } catch (e) {
        showToast(
            "Gagal mengubah status bypass hashtag: " +
                (e.response?.data?.message || e.message),
            "error",
        );
    }
};

const formatDutyHours = (minutes) => {
    const min = parseInt(minutes || 0);
    const hrs = (min / 60).toFixed(1);
    return `${hrs} Jam (${min} mnt)`;
};

const formatDutyCompact = (minutes) => {
    const m = parseInt(minutes || 0, 10);
    const h = Math.floor(m / 60);
    const remM = m % 60;
    return `${h}j ${remM}m`;
};

// Live = has an ActiveStream with status LIVE (eager-loaded by the API).
// `duty_status` is an admin-entered label (defaults to "10-8"), not a live signal.
const isOfficerLive = (officer) => Boolean(officer?.active_stream);

const selectedDetailOfficer = ref(null);
const showDetailSheet = ref(false);

const openOfficerDetail = (officer) => {
    selectedDetailOfficer.value = officer;
    showDetailSheet.value = true;
};

const form = ref({
    id: null,
    handle: "",
    streamer_name: "",
    officer_name: "",
    callsign: "",
    badge_number: "",
    department: "LSPD",
    rank: "Officer",
    agency_id: null,
    rank_id: null,
    division_id: null,
    duty_status: "10-8 (On-Duty)",
    station: "Central",
    channel_id: "",
    avatar_url: "",
    avatar_is_custom: false,
    avatar_hidden: false,
    is_active: true,
    monthly_duty_minutes: 0,
    bypass_hashtag_check: false,
});

// Available Ranks & Divisions based on selected Agency in Form
const availableRanksForSelectedAgency = computed(() => {
    if (!form.value.agency_id) {
        const matched = agenciesList.value.find(
            (a) => a.agency_code === form.value.department,
        );
        return matched ? matched.ranks || [] : [];
    }
    const agency = agenciesList.value.find((a) => a.id == form.value.agency_id);
    return agency ? agency.ranks || [] : [];
});

const availableDivisionsForSelectedAgency = computed(() => {
    if (!form.value.agency_id) {
        const matched = agenciesList.value.find(
            (a) => a.agency_code === form.value.department,
        );
        return matched ? matched.divisions || [] : [];
    }
    const agency = agenciesList.value.find((a) => a.id == form.value.agency_id);
    return agency ? agency.divisions || [] : [];
});

const onAgencyChange = () => {
    const agency = agenciesList.value.find((a) => a.id == form.value.agency_id);
    if (agency) {
        form.value.department = agency.agency_code;
        if (agency.ranks && agency.ranks.length > 0) {
            form.value.rank_id = agency.ranks[0].id;
            form.value.rank = agency.ranks[0].rank_title;
        } else {
            form.value.rank_id = null;
        }
        if (agency.divisions && agency.divisions.length > 0) {
            form.value.division_id = agency.divisions[0].id;
        } else {
            form.value.division_id = null;
        }
    }
};

const onRankChange = () => {
    const ranks = availableRanksForSelectedAgency.value;
    const rankObj = ranks.find((r) => r.id == form.value.rank_id);
    if (rankObj) {
        form.value.rank = rankObj.rank_title;
    }
};

// Fetch Agencies Master List
const fetchAgencies = async () => {
    try {
        const res = await fetch("/api/v1/agencies");
        const json = await res.json();
        if (json.status === "success") {
            agenciesList.value = json.data || [];
        }
    } catch (e) {
        console.warn("Failed to load agencies:", e);
    }
};

// Fetch Officers List
const fetchOfficers = async () => {
    isLoading.value = true;
    try {
        const res = await fetch("/api/v1/officers");
        const json = await res.json();
        if (json.status === "success") {
            officers.value = json.data || [];
        }
    } catch (e) {
        showToast("Failed to load officer data: " + e.message, "error");
    } finally {
        isLoading.value = false;
    }
};

// Tabs State & URL sync (UX-07)
const VALID_TABS = [
    "officers",
    "agencies",
    "certifications",
    "announcements",
    "users",
    "claims",
    "moderation",
    "requests",
    "hero",
    "squads",
];

const getTabFromUrl = () => {
    if (typeof window === "undefined") return "officers";
    const params = new URLSearchParams(window.location.search);
    const tab = params.get("tab");
    return VALID_TABS.includes(tab) ? tab : "officers";
};

const activeTab = ref(getTabFromUrl());

const setActiveTab = (tab) => {
    if (!VALID_TABS.includes(tab)) return;
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    if (typeof window !== "undefined") {
        const url = new URL(window.location.href);
        if (tab === "officers") {
            url.searchParams.delete("tab");
        } else {
            url.searchParams.set("tab", tab);
        }
        window.history.pushState({ tab }, "", url.toString());
    }
};

const handlePopState = () => {
    activeTab.value = getTabFromUrl();
};

// Modal & Form State: Agencies, Ranks, Divisions CRUD
const showAgencyModal = ref(false);
const isEditAgency = ref(false);
const agencyForm = ref({
    id: null,
    agency_code: "",
    agency_name: "",
    jurisdiction: "Statewide",
    badge_logo_url: "",
});

const showRankModal = ref(false);
const isEditRank = ref(false);
const rankForm = ref({
    id: null,
    agency_id: null,
    rank_title: "",
    level: 1,
    base_salary: 5000,
});

const showDivisionModal = ref(false);
const isEditDivision = ref(false);
const divisionForm = ref({
    id: null,
    agency_id: null,
    division_name: "",
    division_code: "",
});

// Agency CRUD Handlers
const openAddAgencyModal = () => {
    isEditAgency.value = false;
    agencyForm.value = {
        id: null,
        agency_code: "",
        agency_name: "",
        jurisdiction: "Statewide",
        badge_logo_url: "",
    };
    showAgencyModal.value = true;
};
const openEditAgencyModal = (agency) => {
    isEditAgency.value = true;
    agencyForm.value = { ...agency };
    showAgencyModal.value = true;
};
const saveAgency = async () => {
    const isEdit = isEditAgency.value;
    const url = isEdit
        ? `/api/v1/agencies/${agencyForm.value.id}`
        : "/api/v1/agencies";
    const method = isEdit ? "PUT" : "POST";
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch(url, {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify(agencyForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message, "success");
            showAgencyModal.value = false;
            fetchAgencies();
            fetchOfficers();
        } else {
            showToast(json.message || "Error saving agency", "error");
        }
    } catch (e) {
        showToast("Request failed: " + e.message, "error");
    }
};
const confirmDialog = ref({
    show: false,
    title: "",
    description: "",
    onConfirm: null,
});

const openConfirmDialog = (title, description, onConfirm) => {
    confirmDialog.value = {
        show: true,
        title,
        description,
        onConfirm,
    };
};

const executeConfirmDialog = async () => {
    const fn = confirmDialog.value.onConfirm;
    confirmDialog.value.show = false;
    if (typeof fn === "function") {
        await fn();
    }
};

const deleteAgency = (id) => {
    openConfirmDialog(
        "Hapus Instansi?",
        "Hapus instansi ini beserta seluruh pangkat dan divisinya? Tindakan ini tidak dapat dibatalkan.",
        async () => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const res = await fetch(`/api/v1/agencies/${id}`, {
                    method: "DELETE",
                    headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    showToast(json.message, "success");
                    fetchAgencies();
                    fetchOfficers();
                }
            } catch (e) {
                showToast("Error deleting agency", "error");
            }
        },
    );
};

// Rank CRUD Handlers
const openAddRankModal = (agencyId) => {
    isEditRank.value = false;
    rankForm.value = {
        id: null,
        agency_id: agencyId,
        rank_title: "",
        level: 1,
        base_salary: 5000,
    };
    showRankModal.value = true;
};
const openEditRankModal = (rank) => {
    isEditRank.value = true;
    rankForm.value = { ...rank };
    showRankModal.value = true;
};
const saveRank = async () => {
    const isEdit = isEditRank.value;
    const url = isEdit ? `/api/v1/ranks/${rankForm.value.id}` : "/api/v1/ranks";
    const method = isEdit ? "PUT" : "POST";
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch(url, {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify(rankForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message, "success");
            showRankModal.value = false;
            fetchAgencies();
        } else {
            showToast(json.message || "Error saving rank", "error");
        }
    } catch (e) {
        showToast("Request failed: " + e.message, "error");
    }
};
const deleteRank = (id) => {
    openConfirmDialog(
        "Hapus Pangkat?",
        "Apakah Anda yakin ingin menghapus pangkat ini dari struktur instansi?",
        async () => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const res = await fetch(`/api/v1/ranks/${id}`, {
                    method: "DELETE",
                    headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    showToast(json.message, "success");
                    fetchAgencies();
                }
            } catch (e) {
                showToast("Error deleting rank", "error");
            }
        },
    );
};

// Division CRUD Handlers
const openAddDivisionModal = (agencyId) => {
    isEditDivision.value = false;
    divisionForm.value = {
        id: null,
        agency_id: agencyId,
        division_name: "",
        division_code: "",
    };
    showDivisionModal.value = true;
};
const saveDivision = async () => {
    const isEdit = isEditDivision.value;
    const url = isEdit
        ? `/api/v1/divisions/${divisionForm.value.id}`
        : "/api/v1/divisions";
    const method = isEdit ? "PUT" : "POST";
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch(url, {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify(divisionForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message, "success");
            showDivisionModal.value = false;
            fetchAgencies();
        } else {
            showToast(json.message || "Error saving division", "error");
        }
    } catch (e) {
        showToast("Request failed: " + e.message, "error");
    }
};
const deleteDivision = (id) => {
    openConfirmDialog(
        "Hapus Sub-Divisi?",
        "Apakah Anda yakin ingin menghapus sub-divisi kerja ini?",
        async () => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const res = await fetch(`/api/v1/divisions/${id}`, {
                    method: "DELETE",
                    headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    showToast(json.message, "success");
                    fetchAgencies();
                }
            } catch (e) {
                showToast("Error deleting division", "error");
            }
        },
    );
};

// Certifications Handlers
const certOfficerId = ref(null);
const certType = ref("");
const certSearchQuery = ref("");
const showAddCertModal = ref(false);
const isSubmittingCert = ref(false);

const openAddCertModal = (officerId = null) => {
    certOfficerId.value = officerId || null;
    certType.value = "";
    showAddCertModal.value = true;
};

const certifiedOfficers = computed(() => {
    return (officers.value || []).filter(
        (o) => o.certifications && o.certifications.length > 0
    );
});

const filteredCertifiedOfficers = computed(() => {
    if (!certSearchQuery.value.trim()) {
        return certifiedOfficers.value;
    }
    const q = certSearchQuery.value.trim().toLowerCase();
    return certifiedOfficers.value.filter((o) => {
        const nameMatch = (o.officer_name || "").toLowerCase().includes(q);
        const callsignMatch = (o.callsign || "").toLowerCase().includes(q);
        const deptMatch = (o.department || o.agency?.agency_code || "").toLowerCase().includes(q);
        const certMatch = (o.certifications || []).some((c) =>
            (c.cert_type || "").toLowerCase().includes(q)
        );
        return nameMatch || callsignMatch || deptMatch || certMatch;
    });
});

const totalCertificationsCount = computed(() => {
    return certifiedOfficers.value.reduce(
        (acc, o) => acc + (o.certifications ? o.certifications.length : 0),
        0
    );
});

const addCertification = async () => {
    if (!certOfficerId.value || !certType.value.trim()) {
        showToast("Pilih petugas dan masukkan jenis kualifikasi!", "error");
        return;
    }
    isSubmittingCert.value = true;
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch("/api/v1/certifications", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify({
                officer_id: certOfficerId.value,
                cert_type: certType.value.trim(),
            }),
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message || "Kualifikasi berhasil ditambahkan.", "success");
            certType.value = "";
            showAddCertModal.value = false;
            fetchOfficers();
        } else {
            showToast(json.message || "Gagal menambahkan kualifikasi.", "error");
        }
    } catch (e) {
        showToast("Failed to add certification", "error");
    } finally {
        isSubmittingCert.value = false;
    }
};

const deleteCertification = (id, certName = "") => {
    openConfirmDialog(
        "Hapus Kualifikasi?",
        `Apakah Anda yakin ingin menghapus kualifikasi ${certName ? `"${certName}"` : "ini"}?`,
        async () => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const res = await fetch(`/api/v1/certifications/${id}`, {
                    method: "DELETE",
                    headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    showToast(json.message || "Kualifikasi dihapus.", "success");
                    fetchOfficers();
                } else {
                    showToast(json.message || "Gagal menghapus kualifikasi.", "error");
                }
            } catch (e) {
                showToast("Failed to delete certification", "error");
            }
        }
    );
};

// Announcements State & Logic
const announcements = ref([]);
const isAnnouncementsLoading = ref(false);
const showAnnouncementModal = ref(false);
const isEditAnnouncement = ref(false);
const isSavingAnnouncement = ref(false);

const announcementForm = ref({
    id: null,
    type: "info",
    title: "",
    message: "",
    action_text: "",
    action_url: "",
    icon: "",
    image_url: "",
    is_active: true,
});

const fetchAnnouncements = async () => {
    isAnnouncementsLoading.value = true;
    try {
        const res = await fetch("/api/v1/announcements");
        const json = await res.json();
        if (json.status === "success") {
            announcements.value = json.data || [];
        }
    } catch (e) {
        showToast("Failed to load announcements: " + e.message, "error");
    } finally {
        isAnnouncementsLoading.value = false;
    }
};

const openAddAnnouncementModal = () => {
    isEditAnnouncement.value = false;
    announcementForm.value = {
        id: null,
        type: "info",
        title: "",
        message: "",
        action_text: "",
        action_url: "",
        icon: "",
        image_url: "",
        is_active: true,
    };
    showAnnouncementModal.value = true;
};

const openEditAnnouncementModal = (promo) => {
    isEditAnnouncement.value = true;
    announcementForm.value = { ...promo };
    showAnnouncementModal.value = true;
};

const saveAnnouncement = async () => {
    isSavingAnnouncement.value = true;
    const isEdit = isEditAnnouncement.value;
    const url = isEdit
        ? `/api/v1/announcements/${announcementForm.value.id}`
        : "/api/v1/announcements";
    const method = isEdit ? "PUT" : "POST";
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(announcementForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message, "success");
            showAnnouncementModal.value = false;
            fetchAnnouncements();
        } else {
            showToast(json.message || "Validation error", "error");
        }
    } catch (e) {
        showToast("Request failed: " + e.message, "error");
    } finally {
        isSavingAnnouncement.value = false;
    }
};

const deleteAnnouncement = (id) => {
    openConfirmDialog(
        "Hapus Pengumuman?",
        "Yakin ingin menghapus pengumuman / alert ini?",
        async () => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const res = await fetch(`/api/v1/announcements/${id}`, {
                    method: "DELETE",
                    headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    showToast("Announcement deleted.", "success");
                    fetchAnnouncements();
                }
            } catch (e) {
                showToast("Error deleting announcement.", "error");
            }
        },
    );
};

const toggleAnnouncement = async (id) => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch(`/api/v1/announcements/${id}/toggle`, {
            method: "PATCH",
            headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
        });
        if (res.ok) {
            showToast("Status updated.", "success");
            fetchAnnouncements();
        }
    } catch (e) {
        showToast("Error updating status.", "error");
    }
};

// Filtered Officers List
const filteredOfficers = computed(() => {
    return officers.value.filter((officer) => {
        // Department Filter
        if (selectedDept.value !== "ALL") {
            if (selectedDept.value === "SAPR") {
                if (
                    officer.department !== "SAPR" &&
                    officer.department !== "PARK RANGER"
                )
                    return false;
            } else if (officer.department !== selectedDept.value) {
                return false;
            }
        }

        // Status Filter
        if (selectedStatus.value === "ACTIVE" && !officer.is_active) {
            return false;
        }
        if (selectedStatus.value === "DISABLED" && officer.is_active) {
            return false;
        }

        // Search Query
        if (searchQuery.value.trim() !== "") {
            const query = searchQuery.value.toLowerCase();
            const matchName =
                officer.officer_name &&
                officer.officer_name.toLowerCase().includes(query);
            const matchStreamer =
                officer.streamer_name &&
                officer.streamer_name.toLowerCase().includes(query);
            const matchCallsign =
                officer.callsign &&
                officer.callsign.toLowerCase().includes(query);
            const matchBadge =
                officer.badge_number &&
                officer.badge_number.toLowerCase().includes(query);
            const matchHandle =
                officer.handle && officer.handle.toLowerCase().includes(query);
            const matchRank =
                officer.rank && officer.rank.toLowerCase().includes(query);
            return (
                matchName ||
                matchStreamer ||
                matchCallsign ||
                matchBadge ||
                matchHandle ||
                matchRank
            );
        }

        return true;
    });
});

// Department Counts
const deptCounts = computed(() => {
    const list = officers.value;
    return {
        ALL: list.length,
        LSPD: list.filter((o) => o.department === "LSPD").length,
        BCSO: list.filter((o) => o.department === "BCSO").length,
        SASP: list.filter((o) => o.department === "SASP").length,
        SAPR: list.filter(
            (o) => o.department === "SAPR" || o.department === "PARK RANGER",
        ).length,
        active: list.filter((o) => o.is_active).length,
        inactive: list.filter((o) => !o.is_active).length,
    };
});

// Department Colors (Token-based)
const getDeptBadgeClass = (dept) => {
    switch (dept) {
        case "LSPD":
            return "bg-dept-lspd/15 text-dept-lspd border-dept-lspd/40";
        case "BCSO":
            return "bg-dept-bcso/15 text-dept-bcso border-dept-bcso/40";
        case "SASP":
            return "bg-dept-sasp/15 text-dept-sasp border-dept-sasp/40";
        case "SAPR":
        case "PARK RANGER":
            return "bg-dept-sapr/15 text-dept-sapr border-dept-sapr/40";
        default:
            return "bg-muted text-muted-foreground border-border";
    }
};

// Open Modal for Add Officer
const openAddModal = () => {
    isEditMode.value = false;
    const defaultAgency =
        agenciesList.value.find((a) => a.agency_code === "LSPD") ||
        agenciesList.value[0];
    form.value = {
        id: null,
        handle: "",
        streamer_name: "",
        officer_name: "",
        callsign: "",
        badge_number: "",
        department: defaultAgency ? defaultAgency.agency_code : "LSPD",
        rank: "Officer",
        agency_id: defaultAgency ? defaultAgency.id : null,
        rank_id:
            defaultAgency && defaultAgency.ranks?.length
                ? defaultAgency.ranks[0].id
                : null,
        division_id:
            defaultAgency && defaultAgency.divisions?.length
                ? defaultAgency.divisions[0].id
                : null,
        duty_status: "10-8 (On-Duty)",
        station: "Central",
        channel_id: "",
        avatar_url: "",
        avatar_is_custom: false,
        avatar_hidden: false,
        is_active: true,
        monthly_duty_minutes: 0,
        bypass_hashtag_check: false,
    };
    showModal.value = true;
};

// Open Modal for Edit Officer
const openEditModal = (officer) => {
    isEditMode.value = true;
    form.value = {
        id: officer.id,
        handle: officer.handle || "",
        streamer_name: officer.streamer_name || "",
        officer_name: officer.officer_name || "",
        callsign: officer.callsign || "",
        badge_number: officer.badge_number || "",
        department: officer.department || "LSPD",
        rank: officer.rank || "Officer",
        agency_id: officer.agency_id || null,
        rank_id: officer.rank_id || null,
        division_id: officer.division_id || null,
        duty_status: officer.duty_status || "10-8 (On-Duty)",
        station: officer.station || "Central",
        channel_id: officer.channel_id || "",
        avatar_url: officer.avatar_url || "",
        avatar_is_custom: Boolean(officer.avatar_is_custom),
        avatar_hidden: Boolean(officer.avatar_hidden),
        is_active: Boolean(officer.is_active),
        monthly_duty_minutes: officer.monthly_duty_minutes || 0,
        bypass_hashtag_check: Boolean(officer.bypass_hashtag_check),
    };
    showModal.value = true;
};

// Verify / Auto-Fetch YouTube Channel
const verifyYouTubeChannel = async () => {
    if (!form.value.handle.trim()) {
        showToast(
            "Please enter a YouTube handle or URL first (e.g. @channelname)",
            "error",
        );
        return;
    }

    isCheckingChannel.value = true;
    try {
        const res = await axios.post("/admin/api/check-channel", {
            handle: form.value.handle,
        });

        const json = res.data;
        if (json.status === "success" && json.data) {
            const data = json.data;
            if (data.channel_id) form.value.channel_id = data.channel_id;
            if (data.handle) form.value.handle = data.handle;
            if (data.streamer_name && !form.value.streamer_name)
                form.value.streamer_name = data.streamer_name;
            if (data.avatar_url) {
                form.value.avatar_url = data.avatar_url;
                form.value.avatar_is_custom = false;
            }
            showToast(
                `Channel verified: ${data.streamer_name || data.handle} (${data.subscriber_count_text || "Subscribers OK"})`,
                "success",
            );
        } else {
            showToast(json.message || "YouTube channel not found.", "error");
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast(
                "Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).",
                "error",
            );
        } else {
            showToast(
                "Failed to verify channel: " +
                    (e.response?.data?.message || e.message),
                "error",
            );
        }
    } finally {
        isCheckingChannel.value = false;
    }
};

// Save Officer (Create or Update)
const saveOfficer = async () => {
    if (
        !form.value.officer_name.trim() ||
        !form.value.callsign.trim() ||
        !form.value.handle.trim()
    ) {
        showToast(
            "Officer Name, Callsign, and YouTube Handle are required!",
            "error",
        );
        return;
    }

    isSavingOfficer.value = true;
    const url = isEditMode.value
        ? `/api/v1/officers/${form.value.id}`
        : "/api/v1/officers";
    const method = isEditMode.value ? "put" : "post";

    try {
        const res = await axios[method](url, form.value);
        const json = res.data;

        if (json.status === "success") {
            showToast(
                json.message ||
                    (isEditMode.value
                        ? "Officer updated successfully!"
                        : "Officer added successfully!"),
                "success",
            );
            showModal.value = false;
            fetchOfficers();
        } else {
            showToast(json.message || "Failed to save officer data.", "error");
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast(
                "Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).",
                "error",
            );
        } else {
            showToast(
                "An error occurred while saving: " +
                    (e.response?.data?.message || e.message),
                "error",
            );
        }
    } finally {
        isSavingOfficer.value = false;
    }
};

// Toggle Officer Active Status
const toggleOfficerStatus = async (officer) => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch(`/api/v1/officers/${officer.id}/toggle`, {
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            officer.is_active = json.is_active;
            showToast(json.message, "success");
        } else {
            showToast(
                json.message || "Failed to update officer status.",
                "error",
            );
        }
    } catch (e) {
        showToast("Failed to update status: " + e.message, "error");
    }
};

// Delete Officer
const confirmDeleteOfficer = (officer) => {
    officerToDelete.value = officer;
    showDeleteConfirm.value = true;
};

const executeDeleteOfficer = async () => {
    if (!officerToDelete.value) return;
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    try {
        const res = await fetch(`/api/v1/officers/${officerToDelete.value.id}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message || "Data perwira berhasil dihapus.", "success");
            showDeleteConfirm.value = false;
            if (selectedDetailOfficer.value?.id === officerToDelete.value.id) {
                showDetailSheet.value = false;
                selectedDetailOfficer.value = null;
            }
            officerToDelete.value = null;
            fetchOfficers();
        } else {
            showToast(json.message || "Gagal menghapus perwira.", "error");
        }
    } catch (e) {
        showToast("Gagal menghapus perwira: " + e.message, "error");
    }
};

// Trigger Live Stream Sync
const triggerSyncStreams = async () => {
    isSyncingStreams.value = true;
    try {
        const res = await axios.post("/admin/api/sync-streams");
        const json = res.data;
        if (json.status === "success") {
            showToast(json.message, "success");
        } else {
            showToast(json.message || "Stream sync failed.", "error");
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast(
                "Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).",
                "error",
            );
        } else {
            showToast(
                "Failed to sync live streams: " +
                    (e.response?.data?.message || e.message),
                "error",
            );
        }
    } finally {
        isSyncingStreams.value = false;
    }
};

// Trigger Subscriber Count Sync
const triggerSyncSubs = async () => {
    isSyncingSubs.value = true;
    try {
        const res = await axios.post("/admin/api/sync-subscribers");
        const json = res.data;
        if (json.status === "success") {
            showToast(json.message, "success");
            fetchOfficers();
        } else {
            showToast(json.message || "Failed to sync subscribers.", "error");
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast(
                "Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).",
                "error",
            );
        } else {
            showToast(
                "Failed to sync subscribers: " +
                    (e.response?.data?.message || e.message),
                "error",
            );
        }
    } finally {
        isSyncingSubs.value = false;
    }
};

// Logout handler

// Registered Users Management
const usersList = ref([]);
const isLoadingUsers = ref(false);
const userSearchQuery = ref("");
const showUserModal = ref(false);
const isEditUser = ref(false);
const isSavingUser = ref(false);

const userForm = ref({
    id: null,
    name: "",
    email: "",
    password: "",
    role: "member",
    clipper_tier: "free",
});

const onUserRoleChange = () => {
    if (userForm.value.role === "officer" || userForm.value.role === "admin") {
        userForm.value.clipper_tier = "ultimate";
    } else if (userForm.value.role === "clipper") {
        userForm.value.clipper_tier = "creator";
    }
};

const openAddUserModal = () => {
    isEditUser.value = false;
    userForm.value = {
        id: null,
        name: "",
        email: "",
        password: "",
        role: "member",
        clipper_tier: "free",
    };
    showUserModal.value = true;
};

const openEditUserModal = (user) => {
    isEditUser.value = true;
    userForm.value = {
        id: user.id,
        name: user.name || "",
        email: user.email || "",
        password: "",
        role: user.role || "member",
        clipper_tier: user.clipper_tier || "free",
    };
    showUserModal.value = true;
};

const saveUser = async () => {
    if (!userForm.value.name.trim() || !userForm.value.email.trim()) {
        showToast("Nama dan Email wajib diisi!", "error");
        return;
    }
    if (!isEditUser.value && !userForm.value.password.trim()) {
        showToast("Password wajib diisi untuk akun baru!", "error");
        return;
    }

    isSavingUser.value = true;
    const isEdit = isEditUser.value;
    const url = isEdit
        ? `/api/v1/admin/users/${userForm.value.id}`
        : "/api/v1/admin/users";
    const method = isEdit ? "PUT" : "POST";
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            credentials: "same-origin",
            body: JSON.stringify(userForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            showToast(json.message, "success");
            showUserModal.value = false;
            fetchUsers();
        } else {
            showToast(
                json.message || "Gagal menyimpan data akun member.",
                "error",
            );
        }
    } catch (e) {
        showToast("Request failed: " + e.message, "error");
    } finally {
        isSavingUser.value = false;
    }
};

const fetchUsers = async () => {
    isLoadingUsers.value = true;
    try {
        const res = await fetch("/api/v1/admin/users", {
            headers: { Accept: "application/json" },
        });
        const json = await res.json();
        if (res.ok && json.status === "success") {
            usersList.value = json.data || [];
        }
    } catch (e) {
        console.warn("Failed to fetch registered users:", e);
    } finally {
        isLoadingUsers.value = false;
    }
};

const filteredUsers = computed(() => {
    if (!userSearchQuery.value) return usersList.value;
    const q = userSearchQuery.value.toLowerCase().trim();
    return usersList.value.filter(
        (u) =>
            u.name?.toLowerCase().includes(q) ||
            u.email?.toLowerCase().includes(q),
    );
});

const handleDeleteUser = (id, name) => {
    openConfirmDialog(
        "Hapus Akun Member?",
        `Apakah Anda yakin ingin menghapus akun member "${name}"? Tindakan ini tidak dapat dibatalkan.`,
        async () => {
            try {
                const csrfToken =
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "";
                const res = await fetch(`/api/v1/admin/users/${id}`, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    credentials: "same-origin",
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    showToast("Akun member berhasil dihapus.", "success");
                    fetchUsers();
                } else {
                    showToast(json.message || "Gagal menghapus user.", "error");
                }
            } catch (e) {
                showToast("Gagal menghapus user: " + e.message, "error");
            }
        },
    );
};

onMounted(() => {
    window.addEventListener("popstate", handlePopState);
    fetchAgencies();
    fetchOfficers();
    fetchAnnouncements();
    fetchUsers();
});

onUnmounted(() => {
    window.removeEventListener("popstate", handlePopState);
});
</script>

<template>
    <Head title="Admin Command Hub - Tactical Police Multiview" />

    <AdminLayout
        :current-tab="activeTab"
        :badges="{ claims: stats.pending_claims }"
        :user-name="auth.user?.name || ''"
        @update:current-tab="setActiveTab"
    >
        <PageContainer size="full" class="space-y-6">
            <!-- Overview (header + unit summary) only on the roster; other sections get the layout's title bar. -->
            <PageHeader
                v-if="activeTab === 'officers'"
                eyebrow="DISPATCHER PORTAL"
                title="Tactical Admin Hub"
                subtitle="Police Unit Roster Management, Master Agencies, Ranks & YouTube Stream Synchronization"
            >
                <template #actions>
                    <Badge variant="outline" class="font-mono text-xs px-3 py-1">
                        Logged in as:
                        <strong class="text-primary font-bold ml-1">{{
                            auth.user?.name || "Admin"
                        }}</strong>
                    </Badge>
                </template>
            </PageHeader>

            <!-- RINGKASAN STATTILE DENGAN 1 TACTICALFRAME (MAKS. 2 PER LAYAR) -->
            <TacticalFrame v-if="activeTab === 'officers'" variant="panel" accent="primary" inner-class="p-4 sm:p-5">
                <div
                    class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 sm:gap-4"
                >
                    <StatTile label="Total Units" :value="deptCounts.ALL" />
                    <StatTile
                        label="Active Units"
                        :value="deptCounts.active"
                        delta="Online Roster"
                        delta-type="up"
                    />
                    <StatTile label="LSPD" :value="deptCounts.LSPD" />
                    <StatTile label="BCSO" :value="deptCounts.BCSO" />
                    <StatTile label="SASP" :value="deptCounts.SASP" />
                    <StatTile label="SAPR" :value="deptCounts.SAPR" />
                    <StatTile
                        label="Disabled"
                        :value="deptCounts.inactive"
                        delta="Off Roster"
                        delta-type="down"
                    />
                </div>
            </TacticalFrame>


            <!-- TAB 1: OFFICERS -->
            <div v-if="activeTab === 'officers'" class="space-y-6">
                <!-- ACTION CONTROL BAR -->
                <Card class="p-3 sm:p-4 gap-0 py-0">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                        <!-- Search & Filters -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 min-w-0">
                            <!-- Search Input -->
                            <div class="relative w-full sm:w-72 lg:w-80 shrink-0">
                                <Search class="size-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <Input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search officer name, callsign, badge, handle..."
                                    class="pl-9 pr-8 text-xs sm:text-sm h-9"
                                />
                                <Button
                                    v-if="searchQuery"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Hapus pencarian"
                                    @click="searchQuery = ''"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 size-6 text-muted-foreground hover:text-foreground"
                                >
                                    <X class="size-3.5" />
                                </Button>
                            </div>

                            <!-- Department Tabs Filter -->
                            <div class="flex items-center gap-1 bg-muted/50 p-1 rounded-md border border-border shrink-0 overflow-x-auto">
                                <Button
                                    v-for="dept in ['ALL', 'LSPD', 'BCSO', 'SASP', 'SAPR']"
                                    :key="dept"
                                    size="sm"
                                    :variant="selectedDept === dept ? 'default' : 'ghost'"
                                    @click="selectedDept = dept"
                                    class="h-7 px-2.5 text-xs font-mono font-bold"
                                >
                                    {{ dept }}
                                </Button>
                            </div>

                            <!-- Status Filter -->
                            <div class="shrink-0 w-full sm:w-[150px]">
                                <Select v-model="selectedStatus">
                                    <SelectTrigger class="h-9 text-xs font-mono">
                                        <SelectValue placeholder="All Statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ALL">All Statuses</SelectItem>
                                        <SelectItem value="ACTIVE">Active Only</SelectItem>
                                        <SelectItem value="DISABLED">Disabled Only</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Action Buttons: Sync & Add -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="triggerSyncStreams"
                                :disabled="isSyncingStreams"
                                class="font-mono text-xs gap-1.5"
                            >
                                <RefreshCw class="size-3.5" :class="{ 'motion-safe:animate-spin': isSyncingStreams }" />
                                <span>{{ isSyncingStreams ? "Syncing..." : "Sync Live Streams" }}</span>
                            </Button>

                            <Button
                                variant="outline"
                                size="sm"
                                @click="triggerSyncSubs"
                                :disabled="isSyncingSubs"
                                class="font-mono text-xs gap-1.5 text-success border-success/40 hover:bg-success/10"
                            >
                                <RefreshCw class="size-3.5" :class="{ 'motion-safe:animate-spin': isSyncingSubs }" />
                                <span>{{ isSyncingSubs ? "Syncing Subs..." : "Sync Subscribers" }}</span>
                            </Button>

                            <Button
                                size="sm"
                                @click="openAddModal"
                                class="font-mono text-xs font-bold gap-1.5"
                            >
                                <Plus class="size-4" />
                                <span>Add Officer</span>
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- OFFICERS TABLE -->
                <Card class="overflow-hidden">
                    <div class="p-4 border-b border-border flex items-center justify-between text-xs text-muted-foreground font-mono">
                        <div>
                            Showing
                            <strong class="text-foreground">{{ filteredOfficers.length }}</strong>
                            of {{ officers.length }} total units
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="fetchOfficers"
                            class="h-7 text-xs font-mono gap-1.5"
                        >
                            <RefreshCw class="size-3.5" />
                            <span>Refresh Data</span>
                        </Button>
                    </div>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 font-mono text-xs uppercase">
                                    <TableHead>Perwira</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Duty Bulan Ini</TableHead>
                                    <TableHead>Akun</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody class="text-xs">
                                <TableRow
                                    v-for="officer in filteredOfficers"
                                    :key="officer.id"
                                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                                    :class="{ 'opacity-60 bg-muted/20': !officer.is_active }"
                                    @click="openOfficerDetail(officer)"
                                >
                                    <!-- 1. Perwira: Avatar + Nama + Callsign, Dept, Rank -->
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <OfficerAvatar
                                                :src="officer.avatar_url || `https://api.dicebear.com/7.x/bottts/svg?seed=${officer.callsign}`"
                                                :name="officer.officer_name"
                                                :callsign="officer.callsign"
                                                :department="officer.agency?.agency_code || officer.department"
                                                size="md"
                                            />
                                            <div class="min-w-0">
                                                <div class="font-semibold text-foreground text-sm truncate">
                                                    {{ officer.officer_name }}
                                                </div>
                                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-mono flex-wrap">
                                                    <span>{{ officer.callsign }}</span>
                                                    <span>·</span>
                                                    <DeptBadge :department="officer.agency?.agency_code || officer.department" />
                                                    <span>·</span>
                                                    <span class="truncate">{{ officer.rank_relation?.rank_title || officer.rank || "Officer" }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- 2. Status: LiveBadge / 10-7 + Switch Aktif -->
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <LiveBadge v-if="isOfficerLive(officer)" />
                                            <Badge v-else variant="secondary" class="font-mono text-xs">
                                                10-7
                                            </Badge>
                                            <Switch
                                                :model-value="Boolean(officer.is_active)"
                                                :aria-label="`Status aktif ${officer.officer_name}`"
                                                @click.stop
                                                @update:model-value="toggleOfficerStatus(officer)"
                                            />
                                        </div>
                                    </TableCell>

                                    <!-- 3. Duty bulan ini -->
                                    <TableCell class="font-mono text-sm tabular-nums text-foreground">
                                        {{ formatDutyCompact(officer.monthly_duty_minutes) }}
                                    </TableCell>

                                    <!-- 4. Akun: Link2 hijau + slug / "-" -->
                                    <TableCell class="font-mono text-xs">
                                        <div v-if="officer.user || officer.bio_profile?.slug" class="inline-flex items-center gap-1">
                                            <Link2 class="size-3.5 text-success shrink-0" />
                                            <a
                                                :href="`/@${officer.bio_profile?.slug || (officer.handle || '').replace('@', '')}`"
                                                target="_blank"
                                                class="text-foreground hover:underline font-bold truncate max-w-[140px]"
                                                :title="`/@${officer.bio_profile?.slug || (officer.handle || '').replace('@', '')}`"
                                                @click.stop
                                            >
                                                /@{{ officer.bio_profile?.slug || (officer.handle || '').replace('@', '') }}
                                            </a>
                                        </div>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- 5. Actions via DropdownMenu -->
                                    <TableCell class="text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    :aria-label="`Aksi untuk ${officer.officer_name}`"
                                                >
                                                    <MoreHorizontal class="size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-56 font-mono text-xs">
                                                <DropdownMenuLabel class="text-xs">{{ officer.callsign }} — {{ officer.officer_name }}</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="openOfficerDetail(officer)" class="gap-2 cursor-pointer font-semibold text-primary">
                                                    <Radio class="size-3.5" />
                                                    <span>Detail</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="toggleHashtagBypass(officer)" class="gap-2 cursor-pointer">
                                                    <Lock class="size-3.5" />
                                                    <span>Hashtag bypass: {{ officer.bypass_hashtag_check ? "ON" : "OFF" }}</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="openEditModal(officer)" class="gap-2 cursor-pointer">
                                                    <Pencil class="size-3.5" />
                                                    <span>Edit Officer</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="openDutyModal(officer)" class="gap-2 cursor-pointer">
                                                    <Clock class="size-3.5" />
                                                    <span>Adjust Duty Time</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    v-if="!officer.user"
                                                    @click="openBindModal(officer)"
                                                    class="gap-2 cursor-pointer text-primary"
                                                >
                                                    <UserCheck class="size-3.5" />
                                                    <span>Bind User</span>
                                                </DropdownMenuItem>
                                                <template v-else>
                                                    <DropdownMenuItem @click="openSlugModal(officer)" class="gap-2 cursor-pointer">
                                                        <Link2 class="size-3.5" />
                                                        <span>Ubah Slug Bio-Link</span>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem @click="openUnbindModal(officer)" class="gap-2 cursor-pointer text-warning">
                                                        <UserX class="size-3.5" />
                                                        <span>Unbind User</span>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="officer.bio_profile"
                                                        @click="toggleSuspendProfile(officer)"
                                                        class="gap-2 cursor-pointer text-warning"
                                                    >
                                                        <ShieldAlert class="size-3.5" />
                                                        <span>{{ officer.bio_profile.is_suspended ? "Unsuspend Bio-Link" : "Suspend Bio-Link" }}</span>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        @click="toggleBanUser(officer)"
                                                        class="gap-2 cursor-pointer text-destructive"
                                                    >
                                                        <Ban class="size-3.5" />
                                                        <span>{{ officer.user.status === "banned" ? "Unban User" : "Ban User" }}</span>
                                                    </DropdownMenuItem>
                                                </template>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    @click="confirmDeleteOfficer(officer)"
                                                    class="gap-2 cursor-pointer text-destructive"
                                                >
                                                    <Trash2 class="size-3.5" />
                                                    <span>Hapus Officer</span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
            <!-- END TAB 1 -->

            <!-- TAB 2: AGENCIES & RANKS STRUCTURE -->
            <div v-if="activeTab === 'agencies'" class="space-y-6 font-mono text-xs">
                <SectionHeader
                    title="Instansi & pangkat"
                    description="Kelola instansi (SASP, LSPD, BCSO, SAPR), tingkatan pangkat, dan sub-divisi."
                >
                    <template #actions>
                        <Button size="sm" @click="openAddAgencyModal" class="gap-1.5">
                            <Plus class="size-4" />
                            <span>Tambah instansi</span>
                        </Button>
                    </template>
                </SectionHeader>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card v-for="agency in agenciesList" :key="agency.id">
                        <CardHeader class="pb-3 border-b border-border flex flex-row items-center justify-between space-y-0">
                            <div class="flex items-center gap-3">
                                <DeptBadge :department="agency.agency_code" />
                                <div>
                                    <CardTitle class="text-sm font-bold text-foreground">
                                        {{ agency.agency_name }}
                                    </CardTitle>
                                    <CardDescription class="text-xs font-mono">
                                        Yurisdiksi: <strong class="text-primary">{{ agency.jurisdiction }}</strong>
                                    </CardDescription>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Button variant="outline" size="sm" @click="openEditAgencyModal(agency)" class="h-7 px-2.5 text-xs">
                                    <Pencil class="size-3 mr-1" />
                                    <span>Edit</span>
                                </Button>
                                <Button variant="destructive" size="sm" @click="deleteAgency(agency.id)" class="h-7 px-2.5 text-xs">
                                    <Trash2 class="size-3 mr-1" />
                                    <span>Hapus</span>
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-4 space-y-4">
                            <!-- Ranks List -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs text-muted-foreground font-bold border-b border-border pb-1.5">
                                    <span>TINGKATAN PANGKAT (LEVEL 1-10)</span>
                                    <Button variant="link" size="sm" @click="openAddRankModal(agency.id)" class="h-auto p-0 text-xs">
                                        + Tambah Pangkat
                                    </Button>
                                </div>
                                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                    <div
                                        v-for="r in agency.ranks"
                                        :key="r.id"
                                        class="p-2 bg-muted/40 border border-border rounded-md flex items-center justify-between"
                                    >
                                        <div>
                                            <span class="font-bold text-foreground">{{ r.rank_title }}</span>
                                            <span class="text-xs text-muted-foreground ml-2">
                                                Level {{ r.level }} • ${{ Number(r.base_salary).toLocaleString() }}/duty
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                :aria-label="`Edit pangkat ${r.rank_title}`"
                                                @click="openEditRankModal(r)"
                                                class="size-7 text-muted-foreground hover:text-foreground"
                                            >
                                                <Pencil class="size-3.5" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                :aria-label="`Hapus pangkat ${r.rank_title}`"
                                                @click="deleteRank(r.id)"
                                                class="size-7 text-destructive hover:text-destructive"
                                            >
                                                <X class="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Divisions List -->
                            <div class="space-y-2 pt-2 border-t border-border">
                                <div class="flex items-center justify-between text-xs text-muted-foreground font-bold border-b border-border pb-1.5">
                                    <span>SUB-DIVISI KERJA</span>
                                    <Button variant="link" size="sm" @click="openAddDivisionModal(agency.id)" class="h-auto p-0 text-xs">
                                        + Tambah Divisi
                                    </Button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="d in agency.divisions"
                                        :key="d.id"
                                        variant="outline"
                                        class="px-2.5 py-1 gap-1.5 text-xs font-mono"
                                    >
                                        <span class="font-bold text-foreground">{{ d.division_name }}</span>
                                        <span class="text-primary">({{ d.division_code || "-" }})</span>
                                        <button
                                            type="button"
                                            :aria-label="`Hapus divisi ${d.division_name}`"
                                            @click="deleteDivision(d.id)"
                                            class="text-destructive hover:opacity-80 ml-1"
                                        >
                                            <X class="size-3" />
                                        </button>
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
            <!-- END TAB 2 -->

            <!-- TAB 3: TACTICAL CERTIFICATIONS -->
            <div v-if="activeTab === 'certifications'" class="space-y-6 font-mono text-xs">
                <SectionHeader
                    title="Kualifikasi"
                    description="Beri kualifikasi taktis kepada personel."
                >
                    <template #actions>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 gap-1.5 font-mono text-xs"
                                :disabled="isLoading"
                                @click="fetchOfficers"
                            >
                                <RefreshCw class="size-3.5" :class="{ 'motion-safe:animate-spin': isLoading }" />
                                <span>Refresh</span>
                            </Button>
                            <Button
                                size="sm"
                                class="h-8 gap-1.5 font-mono text-xs font-bold"
                                @click="openAddCertModal()"
                            >
                                <Plus class="size-4" />
                                <span>Beri Kualifikasi</span>
                            </Button>
                        </div>
                    </template>
                </SectionHeader>

                <!-- Filters & Search -->
                <Card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <Search class="size-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
                            <Input
                                v-model="certSearchQuery"
                                type="text"
                                placeholder="Cari berdasarkan nama perwira, callsign, atau jenis kualifikasi..."
                                class="pl-9 h-9 text-xs font-mono"
                            />
                        </div>
                        <div class="text-xs text-muted-foreground whitespace-nowrap">
                            Total: <strong class="text-foreground">{{ totalCertificationsCount }}</strong> kualifikasi aktif
                        </div>
                    </div>
                </Card>

                <!-- Certifications Roster -->
                <Card>
                    <CardHeader class="pb-3 border-b border-border">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-bold font-mono">
                                Daftar Kualifikasi Taktis Aktif Member
                            </CardTitle>
                            <span class="text-xs text-muted-foreground font-mono">
                                {{ filteredCertifiedOfficers.length }} personel
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <div
                            v-if="filteredCertifiedOfficers.length === 0"
                            class="py-12"
                        >
                            <EmptyState
                                :icon="Award"
                                title="Tidak Ada Kualifikasi Ditemukan"
                                :description="certSearchQuery ? 'Tidak ada personel atau kualifikasi yang cocok dengan pencarian.' : 'Belum ada personel yang memiliki kualifikasi taktis.'"
                            >
                                <template v-if="!certSearchQuery" #action>
                                    <Button size="sm" class="gap-1.5 font-mono text-xs" @click="openAddCertModal()">
                                        <Plus class="size-4" />
                                        <span>Beri Kualifikasi Pertama</span>
                                    </Button>
                                </template>
                            </EmptyState>
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="o in filteredCertifiedOfficers"
                                :key="o.id"
                                class="p-4 bg-muted/40 border border-border rounded-lg space-y-2.5 flex flex-col justify-between"
                            >
                                <div>
                                    <div class="font-bold text-foreground text-sm flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <DeptBadge v-if="o.agency?.agency_code || o.department" :department="o.agency?.agency_code || o.department" />
                                            <span class="truncate">{{ o.officer_name }}</span>
                                        </div>
                                        <span class="text-primary text-xs font-mono shrink-0">{{ o.callsign }}</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 mt-2.5">
                                        <Badge
                                            v-for="c in o.certifications"
                                            :key="c.id"
                                            variant="outline"
                                            class="px-2 py-1 text-xs font-mono gap-1.5"
                                        >
                                            <Award class="size-3 text-primary shrink-0" />
                                            <span>{{ c.cert_type }}</span>
                                            <button
                                                type="button"
                                                :aria-label="`Hapus kualifikasi ${c.cert_type}`"
                                                @click="deleteCertification(c.id, c.cert_type)"
                                                class="text-destructive hover:opacity-80 ml-0.5 cursor-pointer"
                                            >
                                                <X class="size-3" />
                                            </button>
                                        </Badge>
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-border/50 flex justify-end">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-6 px-2 text-xs font-mono text-muted-foreground hover:text-foreground gap-1"
                                        @click="openAddCertModal(o.id)"
                                    >
                                        <Plus class="size-3" />
                                        <span>Tambah</span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <!-- END TAB 3 -->

            <!-- TAB 4: ANNOUNCEMENTS & ALERTS -->
            <div v-if="activeTab === 'announcements'" class="space-y-6 font-mono text-xs">
                <SectionHeader
                    title="Pengumuman & alert"
                    description="Banner pengumuman yang muncul di halaman utama."
                >
                    <template #actions>
                        <Button size="sm" @click="openAddAnnouncementModal" class="gap-1.5">
                            <Plus class="size-4" />
                            <span>Tambah alert</span>
                        </Button>
                    </template>
                </SectionHeader>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card
                        v-for="a in announcements"
                        :key="a.id"
                        class="flex flex-col justify-between"
                    >
                        <CardContent class="p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <Badge variant="info" class="text-xs font-mono uppercase">
                                    {{ a.type }}
                                </Badge>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="toggleAnnouncement(a.id)"
                                    class="h-6 px-2 text-xs font-mono font-bold"
                                    :class="
                                        a.is_active
                                            ? 'bg-success/15 text-success border-success/40 hover:bg-success/25'
                                            : 'bg-destructive/15 text-destructive border-destructive/40 hover:bg-destructive/25'
                                    "
                                >
                                    {{ a.is_active ? "AKTIF" : "NONAKTIF" }}
                                </Button>
                            </div>
                            <h3 class="text-sm font-bold text-foreground">
                                {{ a.title }}
                            </h3>
                            <p class="text-muted-foreground leading-relaxed font-sans text-xs">
                                {{ a.message }}
                            </p>
                        </CardContent>

                        <div class="px-4 py-3 border-t border-border flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">{{ a.action_text || "Tanpa Action" }}</span>
                            <div class="flex items-center gap-2">
                                <Button variant="ghost" size="sm" @click="openEditAnnouncementModal(a)" class="h-7 px-2 text-xs">
                                    <Pencil class="size-3 mr-1" />
                                    <span>Edit</span>
                                </Button>
                                <Button variant="ghost" size="sm" @click="deleteAnnouncement(a.id)" class="h-7 px-2 text-xs text-destructive hover:text-destructive">
                                    <Trash2 class="size-3 mr-1" />
                                    <span>Hapus</span>
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
            <!-- END TAB 4 -->

            <!-- TAB 5: MEMBER ACCOUNTS -->
            <div v-if="activeTab === 'users'" class="space-y-6 font-mono text-xs">
                <SectionHeader
                    title="Akun pengguna"
                    description="Kelola akun dan peran pengguna di platform."
                >
                    <template #actions>
                        <Input
                            v-model="userSearchQuery"
                            type="text"
                            placeholder="Cari nama / email…"
                            class="w-56 sm:w-64"
                        />
                        <Button size="sm" @click="openAddUserModal" class="gap-1.5 shrink-0">
                            <Plus class="size-4" />
                            <span>Tambah akun</span>
                        </Button>
                    </template>
                </SectionHeader>

                <Card class="overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/40 text-xs uppercase font-mono">
                                <TableHead>Nama Member</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role Access</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody class="text-xs">
                            <TableRow v-for="u in filteredUsers" :key="u.id">
                                <TableCell class="font-bold text-foreground">
                                    {{ u.name }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ u.email }}
                                </TableCell>
                                <TableCell class="space-y-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <!-- Role Badge -->
                                        <Badge
                                            :variant="
                                                u.role === 'admin'
                                                    ? 'default'
                                                    : u.role === 'officer'
                                                    ? 'success'
                                                    : u.role === 'clipper'
                                                    ? 'info'
                                                    : 'secondary'
                                            "
                                            class="text-xs font-mono font-bold uppercase"
                                        >
                                            {{
                                                u.role === "admin"
                                                    ? "Admin"
                                                    : u.role === "officer"
                                                    ? "Officer (2 Slot)"
                                                    : u.role === "clipper"
                                                    ? "Clipper"
                                                    : "Member"
                                            }}
                                        </Badge>

                                        <!-- Tier / Package Badge -->
                                        <Badge
                                            :variant="
                                                u.clipper_tier === 'ultimate' || u.role === 'officer' || u.role === 'admin'
                                                    ? 'warning'
                                                    : u.clipper_tier === 'creator'
                                                    ? 'info'
                                                    : 'outline'
                                            "
                                            class="text-xs font-mono uppercase"
                                        >
                                            {{
                                                u.role === "officer" || u.role === "admin"
                                                    ? "PRO Ultimate"
                                                    : u.clipper_tier === "ultimate"
                                                    ? "VIP Supporter (Unlimited)"
                                                    : u.clipper_tier === "creator"
                                                    ? "IME Clipper (10/hari)"
                                                    : "Free Member (3/minggu)"
                                            }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right space-x-1.5">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="openEditUserModal(u)"
                                        class="h-7 px-2.5 text-xs gap-1"
                                    >
                                        <Pencil class="size-3" />
                                        <span>Edit</span>
                                    </Button>
                                    <Button
                                        v-if="u.id !== auth.user?.id"
                                        variant="destructive"
                                        size="sm"
                                        @click="handleDeleteUser(u.id, u.name)"
                                        class="h-7 px-2.5 text-xs gap-1"
                                    >
                                        <Trash2 class="size-3" />
                                        <span>Hapus</span>
                                    </Button>
                                    <span v-else class="text-muted-foreground italic text-xs">
                                        Anda (Admin)
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>
            <!-- END TAB 5 -->

            <!-- TAB 6: KLAIM PERWIRA -->
            <div v-if="activeTab === 'claims'">
                <OfficerClaimsTab @toast="showToast" @updated="fetchOfficers" />
            </div>

            <!-- TAB 7: MODERASI BIO-LINK -->
            <div v-if="activeTab === 'moderation'">
                <BioModerationTab @toast="showToast" />
            </div>

            <!-- TAB 8: PENGAJUAN MUTASI & PANGKAT (PRD v3) -->
            <div v-if="activeTab === 'requests'">
                <AdminChangeRequestsTab @toast="showToast" />
            </div>

            <!-- TAB 9: HERO SLIDES (HS-02) -->
            <div v-if="activeTab === 'hero'">
                <HeroSlidesTab @toast="showToast" />
            </div>

            <!-- TAB 10: SQUADS (SQ-03) -->
            <div v-if="activeTab === 'squads'">
                <SquadsTab @toast="showToast" />
            </div>

            <!-- TAB 11: RECOMMENDED SITES (REC-01) -->
            <div v-if="activeTab === 'recommended'">
                <RecommendedSitesTab @toast="showToast" />
            </div>
        </PageContainer>

        <!-- MODAL: ADD / EDIT OFFICER (With Cascading Agency Dropdown) -->
        <Dialog :open="showModal" @update:open="showModal = $event">
            <DialogContent class="sm:max-w-xl max-h-[90vh] overflow-y-auto font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-base font-bold uppercase font-mono">
                        {{ isEditMode ? "Edit Officer Details" : "Add New Police Unit" }}
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Kelola identitas perwira, instansi, pangkat, dan sinkronisasi kanal YouTube.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <!-- YouTube Handle with Auto-Check Button -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">YouTube Handle / Channel URL *</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="form.handle"
                                type="text"
                                placeholder="@channelname"
                                class="flex-1 text-xs font-mono"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                @click="verifyYouTubeChannel"
                                :disabled="isCheckingChannel"
                                class="font-bold text-xs shrink-0"
                            >
                                {{ isCheckingChannel ? "Verifying..." : "Verify" }}
                            </Button>
                        </div>
                    </div>

                    <!-- Streamer Name & Officer Character Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Streamer OOC Name *</Label>
                            <Input
                                v-model="form.streamer_name"
                                type="text"
                                placeholder="e.g. Gusti Aidan"
                                class="text-xs"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Officer IC Name *</Label>
                            <Input
                                v-model="form.officer_name"
                                type="text"
                                placeholder="e.g. Ofc. Adam Darski"
                                class="text-xs"
                            />
                        </div>
                    </div>

                    <!-- Cascading Agency, Rank, & Division Selection -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Instansi (Agency) *</Label>
                            <select
                                v-model="form.agency_id"
                                @change="onAgencyChange"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option :value="null" class="bg-popover text-popover-foreground">
                                    -- Default (LSPD) --
                                </option>
                                <option
                                    v-for="a in agenciesList"
                                    :key="a.id"
                                    :value="a.id"
                                    class="bg-popover text-popover-foreground"
                                >
                                    {{ a.agency_code }} - {{ a.agency_name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Pangkat (Rank) *</Label>
                            <select
                                v-model="form.rank_id"
                                @change="onRankChange"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option :value="null" class="bg-popover text-popover-foreground">-- Select Rank --</option>
                                <option
                                    v-for="r in availableRanksForSelectedAgency"
                                    :key="r.id"
                                    :value="r.id"
                                    class="bg-popover text-popover-foreground"
                                >
                                    {{ r.rank_title }} (Lvl {{ r.level }})
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Sub-Divisi Kerja</Label>
                            <select
                                v-model="form.division_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option :value="null" class="bg-popover text-popover-foreground">
                                    -- General Patrol --
                                </option>
                                <option
                                    v-for="d in availableDivisionsForSelectedAgency"
                                    :key="d.id"
                                    :value="d.id"
                                    class="bg-popover text-popover-foreground"
                                >
                                    {{ d.division_name }} ({{ d.division_code }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Callsign, Badge, Duty Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Callsign Radio *</Label>
                            <Input
                                v-model="form.callsign"
                                type="text"
                                placeholder="e.g. 1-ADAM-12"
                                class="text-xs font-bold text-primary font-mono"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Badge Number</Label>
                            <Input
                                v-model="form.badge_number"
                                type="text"
                                placeholder="e.g. #163"
                                class="text-xs font-mono"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Duty Status</Label>
                            <select
                                v-model="form.duty_status"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="10-8 (On-Duty)" class="bg-popover text-popover-foreground">10-8 (On-Duty)</option>
                                <option value="10-7 (Off-Duty)" class="bg-popover text-popover-foreground">10-7 (Off-Duty)</option>
                                <option value="10-6 (Busy)" class="bg-popover text-popover-foreground">10-6 (Busy)</option>
                                <option value="Suspended" class="bg-popover text-popover-foreground">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <!-- Station & Active Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Station</Label>
                            <select
                                v-model="form.station"
                                class="w-full h-9 px-3 rounded-md border border-input bg-background text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="Central">Central</option>
                                <option value="71">Station 71</option>
                                <option value="72">Station 72</option>
                            </select>
                        </div>
                        <div class="flex items-center pt-5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <Checkbox
                                    :checked="form.is_active"
                                    @update:checked="form.is_active = $event"
                                />
                                <span class="text-foreground font-bold text-xs">Status Aktif Dimonitor</span>
                            </label>
                        </div>
                    </div>

                    <!-- Avatar URL & Privacy Switch (PRV-01) -->
                    <div class="space-y-3 p-3 bg-muted/40 rounded-lg border border-border">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">{{ $t('admin.officer_form.avatar_url') }}</Label>
                            <Input
                                v-model="form.avatar_url"
                                @input="form.avatar_is_custom = true"
                                type="url"
                                placeholder="https://yt3.ggpht.com/..."
                                class="text-xs font-mono"
                            />
                        </div>
                        <div class="flex items-start justify-between gap-3 pt-1">
                            <div class="space-y-0.5">
                                <Label class="text-xs font-bold text-foreground cursor-pointer" for="admin-custom-avatar">
                                    Kunci Avatar Kustom
                                </Label>
                                <p class="text-xs text-muted-foreground leading-tight">
                                    Jika aktif, foto profil ini tidak akan ditimpa otomatis oleh foto channel YouTube saat sinkronisasi subscriber.
                                </p>
                            </div>
                            <Switch
                                id="admin-custom-avatar"
                                :model-value="form.avatar_is_custom"
                                @update:model-value="form.avatar_is_custom = $event"
                            />
                        </div>
                        <div class="flex items-start justify-between gap-3 pt-1 border-t border-border/50">
                            <div class="space-y-0.5">
                                <Label class="text-xs font-bold text-foreground cursor-pointer" for="admin-hide-avatar">
                                    {{ $t('admin.officer_form.hide_avatar_label') }}
                                </Label>
                                <p class="text-xs text-muted-foreground leading-tight">
                                    {{ $t('admin.officer_form.hide_avatar_desc') }}
                                </p>
                            </div>
                            <Switch
                                id="admin-hide-avatar"
                                :model-value="form.avatar_hidden"
                                @update:model-value="form.avatar_hidden = $event"
                            />
                        </div>
                    </div>

                    <!-- Admin Duty & Hashtag Settings -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-muted/40 rounded-lg border border-border">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Akumulasi Duty (Menit)</Label>
                            <Input
                                v-model.number="form.monthly_duty_minutes"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="text-warning font-mono font-bold text-xs"
                            />
                            <span class="text-xs text-muted-foreground block">
                                Setara ~{{ ((form.monthly_duty_minutes || 0) / 60).toFixed(1) }} jam patroli
                            </span>
                        </div>
                        <div class="flex items-center pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <Checkbox
                                    :checked="form.bypass_hashtag_check"
                                    @update:checked="form.bypass_hashtag_check = $event"
                                />
                                <div>
                                    <span class="text-foreground font-bold block text-xs">Break / Bypass Hashtag Rule</span>
                                    <span class="text-xs text-muted-foreground block leading-tight">
                                        Selalu lolos 10-8 walau lupa hashtag #IMEPolice
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showModal = false">Batal</Button>
                    <Button @click="saveOfficer" :disabled="isSavingOfficer" class="font-bold">
                        {{ isSavingOfficer ? "Saving..." : "Simpan Data Petugas" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: ADD / EDIT AGENCY -->
        <Dialog :open="showAgencyModal" @update:open="showAgencyModal = $event">
            <DialogContent class="sm:max-w-md font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ isEditAgency ? "Edit Instansi" : "Tambah Instansi Baru" }}
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Kode Instansi (e.g. SASP, LSPD) *</Label>
                        <Input v-model="agencyForm.agency_code" type="text" class="text-xs font-mono" />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Nama Lengkap Instansi *</Label>
                        <Input v-model="agencyForm.agency_name" type="text" class="text-xs" />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Yurisdiksi *</Label>
                        <select
                            v-model="agencyForm.jurisdiction"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="Statewide" class="bg-popover text-popover-foreground">Statewide</option>
                            <option value="City" class="bg-popover text-popover-foreground">City</option>
                            <option value="County" class="bg-popover text-popover-foreground">County</option>
                            <option value="State Parks" class="bg-popover text-popover-foreground">State Parks</option>
                        </select>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showAgencyModal = false">Batal</Button>
                    <Button @click="saveAgency" class="font-bold">Simpan Instansi</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: ADD / EDIT RANK -->
        <Dialog :open="showRankModal" @update:open="showRankModal = $event">
            <DialogContent class="sm:max-w-md font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ isEditRank ? "Edit Pangkat" : "Tambah Pangkat Baru" }}
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Nama Pangkat (e.g. Senior Trooper) *</Label>
                        <Input v-model="rankForm.rank_title" type="text" class="text-xs" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Level (1 - 10)</Label>
                            <Input
                                v-model="rankForm.level"
                                type="number"
                                min="1"
                                max="10"
                                class="text-xs font-mono"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Gaji Pokok ($)</Label>
                            <Input v-model="rankForm.base_salary" type="number" class="text-xs font-mono" />
                        </div>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showRankModal = false">Batal</Button>
                    <Button @click="saveRank" class="font-bold">Simpan Pangkat</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: ADD / EDIT DIVISION -->
        <Dialog :open="showDivisionModal" @update:open="showDivisionModal = $event">
            <DialogContent class="sm:max-w-md font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ isEditDivision ? "Edit Sub-Divisi" : "Tambah Sub-Divisi Baru" }}
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Nama Sub-Divisi (e.g. Special Response Team) *</Label>
                        <Input v-model="divisionForm.division_name" type="text" class="text-xs" />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Kode Divisi (e.g. SRT, HP, CID)</Label>
                        <Input v-model="divisionForm.division_code" type="text" class="text-xs font-mono" />
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showDivisionModal = false">Batal</Button>
                    <Button @click="saveDivision" class="font-bold">Simpan Divisi</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: ADD / EDIT ANNOUNCEMENT / ALERT -->
        <Dialog :open="showAnnouncementModal" @update:open="showAnnouncementModal = $event">
            <DialogContent class="sm:max-w-lg font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ isEditAnnouncement ? "Edit Announcement / Alert" : "Tambah Announcement / Alert Baru" }}
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Tipe Alert *</Label>
                            <select
                                v-model="announcementForm.type"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="info" class="bg-popover text-popover-foreground">Info (Biru)</option>
                                <option value="warning" class="bg-popover text-popover-foreground">Peringatan (Kuning)</option>
                                <option value="danger" class="bg-popover text-popover-foreground">Bahaya / Darurat (Merah)</option>
                                <option value="success" class="bg-popover text-popover-foreground">Sukses (Hijau)</option>
                                <option value="promo" class="bg-popover text-popover-foreground">Promosi / Event (Ungu)</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Status *</Label>
                            <select
                                v-model="announcementForm.is_active"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option :value="true" class="bg-popover text-popover-foreground">Aktif (Tampil)</option>
                                <option :value="false" class="bg-popover text-popover-foreground">Nonaktif (Sembunyikan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Judul Alert / Banner *</Label>
                        <Input
                            v-model="announcementForm.title"
                            type="text"
                            placeholder="Contoh: CODE 3 EMERGENCY ANNOUNCEMENT"
                            class="text-xs"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Pesan Pengumuman *</Label>
                        <Textarea
                            v-model="announcementForm.message"
                            rows="3"
                            placeholder="Tulis isi pengumuman atau instruksi taktis..."
                            class="text-xs resize-none"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Teks Tombol Aksi (Opsional)</Label>
                            <Input
                                v-model="announcementForm.action_text"
                                type="text"
                                placeholder="Contoh: Lihat Detail"
                                class="text-xs"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">URL Aksi (Opsional)</Label>
                            <Input
                                v-model="announcementForm.action_url"
                                type="text"
                                placeholder="https://..."
                                class="text-xs"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">Icon (Opsional)</Label>
                            <Input
                                v-model="announcementForm.icon"
                                type="text"
                                placeholder="Contoh: 🚨, 📢, ⚠️"
                                class="text-xs"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold">URL Banner Image (Opsional)</Label>
                            <Input
                                v-model="announcementForm.image_url"
                                type="text"
                                placeholder="https://..."
                                class="text-xs"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showAnnouncementModal = false">Batal</Button>
                    <Button @click="saveAnnouncement" :disabled="isSavingAnnouncement" class="font-bold">
                        {{ isSavingAnnouncement ? "Menyimpan..." : isEditAnnouncement ? "Update Alert" : "Simpan Alert Baru" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: ADD / EDIT USER MEMBER -->
        <Dialog :open="showUserModal" @update:open="showUserModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{ isEditUser ? "Edit akun" : "Tambah akun" }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ isEditUser
                            ? "Ubah data akun pengguna di bawah ini."
                            : "Isi informasi akun untuk pengguna baru." }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5 py-2">
                    <div class="space-y-1.5">
                        <Label for="user-name">Nama lengkap</Label>
                        <Input id="user-name" v-model="userForm.name" type="text" placeholder="Contoh: John Doe" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="user-email">Email</Label>
                        <Input id="user-email" v-model="userForm.email" type="email" placeholder="nama@email.com" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="user-password">Password</Label>
                        <Input
                            id="user-password"
                            v-model="userForm.password"
                            type="password"
                            :placeholder="isEditUser ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter'"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="user-role">Peran</Label>
                        <select
                            id="user-role"
                            v-model="userForm.role"
                            @change="onUserRoleChange"
                            class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="member" class="bg-popover text-popover-foreground">Member</option>
                            <option value="officer" class="bg-popover text-popover-foreground">Officer</option>
                            <option value="clipper" class="bg-popover text-popover-foreground">Clipper komunitas</option>
                            <option value="admin" class="bg-popover text-popover-foreground">Admin</option>
                        </select>
                        <p class="text-xs text-muted-foreground">
                            {{
                                userForm.role === "admin" ? "Akses penuh ke panel admin."
                                : userForm.role === "officer" ? "Akses officer + 2 slot Clipper."
                                : userForm.role === "clipper" ? "Pemotong klip komunitas (editor)."
                                : "Pengguna umum / pengunjung platform."
                            }}
                        </p>
                    </div>

                    <!-- Tier otomatis untuk officer & admin -->
                    <div
                        v-if="userForm.role === 'officer' || userForm.role === 'admin'"
                        class="rounded-md bg-success/10 border border-success/30 p-3 text-sm text-success"
                    >
                        <p class="font-medium">Tier Ultimate otomatis</p>
                        <p class="mt-0.5 text-xs opacity-90">
                            Akun {{ userForm.role === "admin" ? "admin" : "officer" }} langsung mendapat klip tanpa batas, durasi 10 menit, DVR live, dan 2 slot penunjukan.
                        </p>
                    </div>

                    <div v-else class="space-y-1.5">
                        <Label for="user-tier">Paket klip</Label>
                        <select
                            id="user-tier"
                            v-model="userForm.clipper_tier"
                            class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="free" class="bg-popover text-popover-foreground">Free — 3 klip / minggu, maks 1 menit</option>
                            <option value="creator" class="bg-popover text-popover-foreground">Komunitas — 10 klip / hari, maks 5 menit</option>
                            <option value="ultimate" class="bg-popover text-popover-foreground">Ultimate — tanpa batas, maks 10 menit + DVR</option>
                        </select>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showUserModal = false">Batal</Button>
                    <Button @click="saveUser" :disabled="isSavingUser">
                        {{ isSavingUser ? "Menyimpan…" : isEditUser ? "Simpan perubahan" : "Tambah akun" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: BERI KUALIFIKASI -->
        <Dialog :open="showAddCertModal" @update:open="showAddCertModal = $event">
            <DialogContent class="sm:max-w-md font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-base font-bold uppercase font-mono">
                        Beri Kualifikasi Taktis
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Pilih perwira dan cantumkan jenis kualifikasi taktis dinas.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Pilih Petugas Kepolisian *</Label>
                        <select
                            v-model="certOfficerId"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option :value="null" class="bg-popover text-popover-foreground">
                                -- Pilih Petugas Kepolisian --
                            </option>
                            <option
                                v-for="o in officers"
                                :key="o.id"
                                :value="o.id"
                                class="bg-popover text-popover-foreground"
                            >
                                {{ o.officer_name }} ({{ o.callsign }} - {{ o.agency?.agency_code || o.department }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Jenis Kualifikasi *</Label>
                        <Input
                            v-model="certType"
                            type="text"
                            placeholder="Contoh: Class 2 Firearms, Air Support Pilot, CQB Breaching..."
                            class="text-xs"
                            @keydown.enter.prevent="addCertification"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showAddCertModal = false">Batal</Button>
                    <Button
                        @click="addCertification"
                        :disabled="isSubmittingCert || !certOfficerId || !certType.trim()"
                        class="font-bold gap-1.5"
                    >
                        <Plus class="size-4" />
                        <span>Berikan Kualifikasi</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- SHARED CONFIRMATION ALERT DIALOG (Agency, Rank, Division, Announcement, User) -->
        <AlertDialog :open="confirmDialog.show" @update:open="confirmDialog.show = $event">
            <AlertDialogContent class="font-mono text-xs">
                <AlertDialogHeader>
                    <AlertDialogTitle class="font-mono uppercase text-sm">
                        {{ confirmDialog.title }}
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        {{ confirmDialog.description }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="confirmDialog.show = false">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        @click="executeConfirmDialog"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- MODAL: ADJUST DUTY TIME -->
        <Dialog :open="showDutyModal" @update:open="showDutyModal = $event">
            <DialogContent class="sm:max-w-md font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold uppercase font-mono">
                        Adjust Duty Time
                    </DialogTitle>
                    <DialogDescription v-if="dutyOfficer" class="text-xs">
                        {{ dutyOfficer.officer_name }} ({{ dutyOfficer.callsign }})
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 py-2">
                    <div class="p-3 bg-muted/40 border border-border rounded-md text-foreground">
                        Waktu Akumulasi Saat Ini:
                        <strong class="text-warning ml-1">{{
                            formatDutyHours(dutyOfficer?.monthly_duty_minutes)
                        }}</strong>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Jenis Penyesuaian</Label>
                        <select
                            v-model="dutyAction"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="add" class="bg-popover text-popover-foreground">Tambah Waktu (Menit)</option>
                            <option value="subtract" class="bg-popover text-popover-foreground">Kurangi Waktu (Menit)</option>
                            <option value="set" class="bg-popover text-popover-foreground">Set Total Menit Langsung</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold">Durasi Menit</Label>
                        <Input
                            v-model.number="dutyMinutesInput"
                            type="number"
                            min="0"
                            placeholder="Masukkan menit, contoh: 60 (1 jam)"
                            class="font-mono text-xs"
                        />
                        <div class="text-xs text-muted-foreground">
                            Subtotal setara ±{{ ((dutyMinutesInput || 0) / 60).toFixed(1) }} jam
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="showDutyModal = false">Batal</Button>
                    <Button
                        @click="handleAdjustDuty"
                        :disabled="isAdjustingDuty"
                        class="font-bold"
                    >
                        {{ isAdjustingDuty ? "Memproses..." : "Terapkan Penyesuaian" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL: SUSPEND BIO-LINK / BAN USER REASON -->
        <Dialog :open="reasonDialog.show" @update:open="reasonDialog.show = $event">
            <DialogContent class="sm:max-w-md font-mono text-xs">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold uppercase font-mono">
                        {{ reasonDialog.mode === "suspend" ? "Bekukan Bio-Link (Kill-Switch)" : "Ban Akun Pengguna" }}
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        {{
                            reasonDialog.mode === "suspend"
                                ? `Masukkan alasan pembekuan halaman bio untuk ${reasonDialog.officer?.officer_name || ""}.`
                                : `Masukkan alasan pemblokiran akun user ${reasonDialog.officer?.user?.name || ""}.`
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2 py-2">
                    <Label class="text-xs font-bold">Alasan Moderasi *</Label>
                    <Input
                        v-model="reasonDialog.reason"
                        type="text"
                        placeholder="Masukkan alasan tindakan..."
                        class="text-xs"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="reasonDialog.show = false">Batal</Button>
                    <Button
                        variant="destructive"
                        @click="submitReasonDialog"
                        :disabled="reasonDialog.isSubmitting"
                    >
                        {{ reasonDialog.isSubmitting ? "Memproses..." : "Konfirmasi Tindakan" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ALERT DIALOG: DELETE OFFICER -->
        <AlertDialog :open="showDeleteConfirm" @update:open="showDeleteConfirm = $event">
            <AlertDialogContent class="font-mono text-xs">
                <AlertDialogHeader>
                    <AlertDialogTitle class="font-mono uppercase text-sm">
                        Hapus Unit Perwira?
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        Apakah Anda yakin ingin menghapus data unit perwira
                        <strong class="text-foreground">{{ officerToDelete?.officer_name }} ({{ officerToDelete?.callsign }})</strong>?
                        Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteConfirm = false">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        @click="executeDeleteOfficer"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Hapus Perwira
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- TACTICAL BIO-LINK & BINDING MODAL -->
        <OfficerBindingModal
            :show="showBindingModal"
            :mode="bindingMode"
            :officer="bindingOfficer"
            :users-list="usersList"
            @close="showBindingModal = false"
            @success="fetchOfficers"
            @toast="showToast"
        />

        <!-- OFFICER DETAIL SHEET (UX-09) -->
        <OfficerDetailSheet
            v-model:open="showDetailSheet"
            :officer="selectedDetailOfficer"
            @edit="openEditModal"
            @delete="confirmDeleteOfficer"
            @duty="openDutyModal"
            @toggle-hashtag="toggleHashtagBypass"
            @bind="openBindModal"
            @slug="openSlugModal"
            @unbind="openUnbindModal"
            @toggle-suspend="toggleSuspendProfile"
            @toggle-ban="toggleBanUser"
        />
    </AdminLayout>
</template>
