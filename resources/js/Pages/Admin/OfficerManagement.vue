<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import axios from 'axios';

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
        }),
    },
    agenciesMaster: {
        type: Array,
        default: () => [],
    },
    auth: {
        type: Object,
        default: () => ({ user: null }),
    },
});

// State
const officers = ref([]);
const agenciesList = ref(props.agenciesMaster || []);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedDept = ref('ALL');
const selectedStatus = ref('ALL'); // ALL, ACTIVE, DISABLED

// Action states (loading spinners)
const isSyncingStreams = ref(false);
const isSyncingSubs = ref(false);
const isCheckingChannel = ref(false);
const isSavingOfficer = ref(false);
const isDeletingOfficer = ref(false);

// Toast Notification
const toast = ref({
    show: false,
    message: '',
    type: 'success', // 'success', 'error', 'info'
});

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 4500);
};

// Modal State: Officer
// Duty Time Adjustment Modal State
const showDutyModal = ref(false);
const dutyOfficer = ref(null);
const dutyAction = ref('add'); // 'add', 'subtract', 'set'
const dutyMinutesInput = ref(60);
const isAdjustingDuty = ref(false);

const openDutyModal = (officer) => {
    dutyOfficer.value = officer;
    dutyAction.value = 'add';
    dutyMinutesInput.value = 60;
    showDutyModal.value = true;
};

const handleAdjustDuty = async () => {
    if (!dutyOfficer.value) return;
    isAdjustingDuty.value = true;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await axios.patch(`/api/v1/officers/${dutyOfficer.value.id}/adjust-duty`, {
            action: dutyAction.value,
            minutes: dutyMinutesInput.value,
        }, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        });
        if (res.data && res.data.status === 'success') {
            showToast(res.data.message, 'success');
            dutyOfficer.value.monthly_duty_minutes = res.data.monthly_duty_minutes;
            showDutyModal.value = false;
            fetchOfficers();
        } else {
            showToast(res.data?.message || 'Gagal mengubah waktu patroli', 'error');
        }
    } catch (e) {
        showToast('Gagal mengubah waktu patroli: ' + (e.response?.data?.message || e.message), 'error');
    } finally {
        isAdjustingDuty.value = false;
    }
};

const toggleHashtagBypass = async (officer) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await axios.patch(`/api/v1/officers/${officer.id}/toggle-hashtag-bypass`, {}, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        });
        if (res.data && res.data.status === 'success') {
            officer.bypass_hashtag_check = res.data.bypass_hashtag_check;
            showToast(res.data.message, 'success');
        } else {
            showToast(res.data?.message || 'Gagal mengubah status bypass hashtag', 'error');
        }
    } catch (e) {
        showToast('Gagal mengubah status bypass hashtag: ' + (e.response?.data?.message || e.message), 'error');
    }
};

const formatDutyHours = (minutes) => {
    const min = parseInt(minutes || 0);
    const hrs = (min / 60).toFixed(1);
    return `${hrs} Jam (${min} mnt)`;
};

const form = ref({
    id: null,
    handle: '',
    streamer_name: '',
    officer_name: '',
    callsign: '',
    badge_number: '',
    department: 'LSPD',
    rank: 'Officer',
    agency_id: null,
    rank_id: null,
    division_id: null,
    duty_status: '10-8 (On-Duty)',
    patrol_zone: 'Los Santos Metropolitan',
    channel_id: '',
    avatar_url: '',
    is_active: true,
    monthly_duty_minutes: 0,
    bypass_hashtag_check: false,
});

// Available Ranks & Divisions based on selected Agency in Form
const availableRanksForSelectedAgency = computed(() => {
    if (!form.value.agency_id) {
        const matched = agenciesList.value.find(a => a.agency_code === form.value.department);
        return matched ? matched.ranks || [] : [];
    }
    const agency = agenciesList.value.find(a => a.id == form.value.agency_id);
    return agency ? agency.ranks || [] : [];
});

const availableDivisionsForSelectedAgency = computed(() => {
    if (!form.value.agency_id) {
        const matched = agenciesList.value.find(a => a.agency_code === form.value.department);
        return matched ? matched.divisions || [] : [];
    }
    const agency = agenciesList.value.find(a => a.id == form.value.agency_id);
    return agency ? agency.divisions || [] : [];
});

const onAgencyChange = () => {
    const agency = agenciesList.value.find(a => a.id == form.value.agency_id);
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
    const rankObj = ranks.find(r => r.id == form.value.rank_id);
    if (rankObj) {
        form.value.rank = rankObj.rank_title;
    }
};

// Fetch Agencies Master List
const fetchAgencies = async () => {
    try {
        const res = await fetch('/api/v1/agencies');
        const json = await res.json();
        if (json.status === 'success') {
            agenciesList.value = json.data || [];
        }
    } catch (e) {
        console.warn('Failed to load agencies:', e);
    }
};

// Fetch Officers List
const fetchOfficers = async () => {
    isLoading.value = true;
    try {
        const res = await fetch('/api/v1/officers');
        const json = await res.json();
        if (json.status === 'success') {
            officers.value = json.data || [];
        }
    } catch (e) {
        showToast('Failed to load officer data: ' + e.message, 'error');
    } finally {
        isLoading.value = false;
    }
};

// Tabs State
const activeTab = ref('officers'); // 'officers', 'agencies', 'certifications', 'announcements', 'users'

// Modal & Form State: Agencies, Ranks, Divisions CRUD
const showAgencyModal = ref(false);
const isEditAgency = ref(false);
const agencyForm = ref({ id: null, agency_code: '', agency_name: '', jurisdiction: 'Statewide', badge_logo_url: '' });

const showRankModal = ref(false);
const isEditRank = ref(false);
const rankForm = ref({ id: null, agency_id: null, rank_title: '', level: 1, base_salary: 5000 });

const showDivisionModal = ref(false);
const isEditDivision = ref(false);
const divisionForm = ref({ id: null, agency_id: null, division_name: '', division_code: '' });

// Agency CRUD Handlers
const openAddAgencyModal = () => {
    isEditAgency.value = false;
    agencyForm.value = { id: null, agency_code: '', agency_name: '', jurisdiction: 'Statewide', badge_logo_url: '' };
    showAgencyModal.value = true;
};
const openEditAgencyModal = (agency) => {
    isEditAgency.value = true;
    agencyForm.value = { ...agency };
    showAgencyModal.value = true;
};
const saveAgency = async () => {
    const isEdit = isEditAgency.value;
    const url = isEdit ? `/api/v1/agencies/${agencyForm.value.id}` : '/api/v1/agencies';
    const method = isEdit ? 'PUT' : 'POST';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(agencyForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            showAgencyModal.value = false;
            fetchAgencies();
            fetchOfficers();
        } else {
            showToast(json.message || 'Error saving agency', 'error');
        }
    } catch (e) {
        showToast('Request failed: ' + e.message, 'error');
    }
};
const deleteAgency = async (id) => {
    if (!confirm('Hapus instansi ini beserta seluruh pangkat dan divisinya?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/agencies/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            fetchAgencies();
            fetchOfficers();
        }
    } catch (e) {
        showToast('Error deleting agency', 'error');
    }
};

// Rank CRUD Handlers
const openAddRankModal = (agencyId) => {
    isEditRank.value = false;
    rankForm.value = { id: null, agency_id: agencyId, rank_title: '', level: 1, base_salary: 5000 };
    showRankModal.value = true;
};
const openEditRankModal = (rank) => {
    isEditRank.value = true;
    rankForm.value = { ...rank };
    showRankModal.value = true;
};
const saveRank = async () => {
    const isEdit = isEditRank.value;
    const url = isEdit ? `/api/v1/ranks/${rankForm.value.id}` : '/api/v1/ranks';
    const method = isEdit ? 'PUT' : 'POST';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(rankForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            showRankModal.value = false;
            fetchAgencies();
        } else {
            showToast(json.message || 'Error saving rank', 'error');
        }
    } catch (e) {
        showToast('Request failed: ' + e.message, 'error');
    }
};
const deleteRank = async (id) => {
    if (!confirm('Hapus pangkat ini?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/ranks/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            fetchAgencies();
        }
    } catch (e) {
        showToast('Error deleting rank', 'error');
    }
};

// Division CRUD Handlers
const openAddDivisionModal = (agencyId) => {
    isEditDivision.value = false;
    divisionForm.value = { id: null, agency_id: agencyId, division_name: '', division_code: '' };
    showDivisionModal.value = true;
};
const openEditDivisionModal = (division) => {
    isEditDivision.value = true;
    divisionForm.value = { ...division };
    showDivisionModal.value = true;
};
const saveDivision = async () => {
    const isEdit = isEditDivision.value;
    const url = isEdit ? `/api/v1/divisions/${divisionForm.value.id}` : '/api/v1/divisions';
    const method = isEdit ? 'PUT' : 'POST';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(divisionForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            showDivisionModal.value = false;
            fetchAgencies();
        } else {
            showToast(json.message || 'Error saving division', 'error');
        }
    } catch (e) {
        showToast('Request failed: ' + e.message, 'error');
    }
};
const deleteDivision = async (id) => {
    if (!confirm('Hapus sub-divisi ini?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/divisions/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            fetchAgencies();
        }
    } catch (e) {
        showToast('Error deleting division', 'error');
    }
};

// Certifications Handlers
const certOfficerId = ref(null);
const certType = ref('');

const addCertification = async () => {
    if (!certOfficerId.value || !certType.value.trim()) {
        showToast('Pilih petugas dan masukkan jenis kualifikasi!', 'error');
        return;
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch('/api/v1/certifications', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ officer_id: certOfficerId.value, cert_type: certType.value }),
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            certType.value = '';
            fetchOfficers();
        }
    } catch (e) {
        showToast('Failed to add certification', 'error');
    }
};

const deleteCertification = async (id) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/certifications/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast('Kualifikasi dihapus.', 'success');
            fetchOfficers();
        }
    } catch (e) {
        showToast('Failed to delete certification', 'error');
    }
};

// Announcements State & Logic
const announcements = ref([]);
const isAnnouncementsLoading = ref(false);
const showAnnouncementModal = ref(false);
const isEditAnnouncement = ref(false);
const isSavingAnnouncement = ref(false);

const announcementForm = ref({
    id: null,
    type: 'info',
    title: '',
    message: '',
    action_text: '',
    action_url: '',
    icon: '',
    image_url: '',
    is_active: true,
});

const fetchAnnouncements = async () => {
    isAnnouncementsLoading.value = true;
    try {
        const res = await fetch('/api/v1/announcements');
        const json = await res.json();
        if (json.status === 'success') {
            announcements.value = json.data || [];
        }
    } catch (e) {
        showToast('Failed to load announcements: ' + e.message, 'error');
    } finally {
        isAnnouncementsLoading.value = false;
    }
};

const openAddAnnouncementModal = () => {
    isEditAnnouncement.value = false;
    announcementForm.value = {
        id: null,
        type: 'info',
        title: '',
        message: '',
        action_text: '',
        action_url: '',
        icon: '',
        image_url: '',
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
    const url = isEdit ? `/api/v1/announcements/${announcementForm.value.id}` : '/api/v1/announcements';
    const method = isEdit ? 'PUT' : 'POST';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(announcementForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            showAnnouncementModal.value = false;
            fetchAnnouncements();
        } else {
            showToast(json.message || 'Validation error', 'error');
        }
    } catch (e) {
        showToast('Request failed: ' + e.message, 'error');
    } finally {
        isSavingAnnouncement.value = false;
    }
};

const deleteAnnouncement = async (id) => {
    if (!confirm('Yakin ingin menghapus pengumuman ini?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/announcements/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast('Announcement deleted.', 'success');
            fetchAnnouncements();
        }
    } catch (e) {
        showToast('Error deleting announcement.', 'error');
    }
};

const toggleAnnouncement = async (id) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/announcements/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        if (res.ok) {
            showToast('Status updated.', 'success');
            fetchAnnouncements();
        }
    } catch (e) {
        showToast('Error updating status.', 'error');
    }
};

// Filtered Officers List
const filteredOfficers = computed(() => {
    return officers.value.filter((officer) => {
        // Department Filter
        if (selectedDept.value !== 'ALL') {
            if (selectedDept.value === 'SAPR') {
                if (officer.department !== 'SAPR' && officer.department !== 'PARK RANGER') return false;
            } else if (officer.department !== selectedDept.value) {
                return false;
            }
        }

        // Status Filter
        if (selectedStatus.value === 'ACTIVE' && !officer.is_active) {
            return false;
        }
        if (selectedStatus.value === 'DISABLED' && officer.is_active) {
            return false;
        }

        // Search Query
        if (searchQuery.value.trim() !== '') {
            const query = searchQuery.value.toLowerCase();
            const matchName = officer.officer_name && officer.officer_name.toLowerCase().includes(query);
            const matchStreamer = officer.streamer_name && officer.streamer_name.toLowerCase().includes(query);
            const matchCallsign = officer.callsign && officer.callsign.toLowerCase().includes(query);
            const matchBadge = officer.badge_number && officer.badge_number.toLowerCase().includes(query);
            const matchHandle = officer.handle && officer.handle.toLowerCase().includes(query);
            const matchRank = officer.rank && officer.rank.toLowerCase().includes(query);
            return matchName || matchStreamer || matchCallsign || matchBadge || matchHandle || matchRank;
        }

        return true;
    });
});

// Department Counts
const deptCounts = computed(() => {
    const list = officers.value;
    return {
        ALL: list.length,
        LSPD: list.filter(o => o.department === 'LSPD').length,
        BCSO: list.filter(o => o.department === 'BCSO').length,
        SASP: list.filter(o => o.department === 'SASP').length,
        SAPR: list.filter(o => o.department === 'SAPR' || o.department === 'PARK RANGER').length,
        active: list.filter(o => o.is_active).length,
        inactive: list.filter(o => !o.is_active).length,
    };
});

// Department Colors
const getDeptBadgeClass = (dept) => {
    switch (dept) {
        case 'LSPD': return 'bg-blue-950/80 text-blue-400 border-blue-700/60';
        case 'BCSO': return 'bg-amber-950/80 text-amber-400 border-amber-700/60';
        case 'SASP': return 'bg-emerald-950/80 text-emerald-400 border-emerald-700/60';
        case 'SAPR':
        case 'PARK RANGER': return 'bg-green-950/80 text-green-400 border-green-700/60';
        default: return 'bg-slate-800 text-slate-300 border-slate-700';
    }
};

// Open Modal for Add Officer
const openAddModal = () => {
    isEditMode.value = false;
    const defaultAgency = agenciesList.value.find(a => a.agency_code === 'LSPD') || agenciesList.value[0];
    form.value = {
        id: null,
        handle: '',
        streamer_name: '',
        officer_name: '',
        callsign: '',
        badge_number: '',
        department: defaultAgency ? defaultAgency.agency_code : 'LSPD',
        rank: 'Officer',
        agency_id: defaultAgency ? defaultAgency.id : null,
        rank_id: defaultAgency && defaultAgency.ranks?.length ? defaultAgency.ranks[0].id : null,
        division_id: defaultAgency && defaultAgency.divisions?.length ? defaultAgency.divisions[0].id : null,
        duty_status: '10-8 (On-Duty)',
        patrol_zone: 'Los Santos Metropolitan',
        channel_id: '',
        avatar_url: '',
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
        handle: officer.handle || '',
        streamer_name: officer.streamer_name || '',
        officer_name: officer.officer_name || '',
        callsign: officer.callsign || '',
        badge_number: officer.badge_number || '',
        department: officer.department || 'LSPD',
        rank: officer.rank || 'Officer',
        agency_id: officer.agency_id || null,
        rank_id: officer.rank_id || null,
        division_id: officer.division_id || null,
        duty_status: officer.duty_status || '10-8 (On-Duty)',
        patrol_zone: officer.patrol_zone || 'Los Santos Metropolitan',
        channel_id: officer.channel_id || '',
        avatar_url: officer.avatar_url || '',
        is_active: Boolean(officer.is_active),
        monthly_duty_minutes: officer.monthly_duty_minutes || 0,
        bypass_hashtag_check: Boolean(officer.bypass_hashtag_check),
    };
    showModal.value = true;
};

// Verify / Auto-Fetch YouTube Channel
const verifyYouTubeChannel = async () => {
    if (!form.value.handle.trim()) {
        showToast('Please enter a YouTube handle or URL first (e.g. @channelname)', 'error');
        return;
    }

    isCheckingChannel.value = true;
    try {
        const res = await axios.post('/admin/api/check-channel', {
            handle: form.value.handle,
        });

        const json = res.data;
        if (json.status === 'success' && json.data) {
            const data = json.data;
            if (data.channel_id) form.value.channel_id = data.channel_id;
            if (data.handle) form.value.handle = data.handle;
            if (data.streamer_name && !form.value.streamer_name) form.value.streamer_name = data.streamer_name;
            if (data.avatar_url) form.value.avatar_url = data.avatar_url;
            showToast(`Channel verified: ${data.streamer_name || data.handle} (${data.subscriber_count_text || 'Subscribers OK'})`, 'success');
        } else {
            showToast(json.message || 'YouTube channel not found.', 'error');
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast('Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).', 'error');
        } else {
            showToast('Failed to verify channel: ' + (e.response?.data?.message || e.message), 'error');
        }
    } finally {
        isCheckingChannel.value = false;
    }
};

// Save Officer (Create or Update)
const saveOfficer = async () => {
    if (!form.value.officer_name.trim() || !form.value.callsign.trim() || !form.value.handle.trim()) {
        showToast('Officer Name, Callsign, and YouTube Handle are required!', 'error');
        return;
    }

    isSavingOfficer.value = true;
    const url = isEditMode.value ? `/api/v1/officers/${form.value.id}` : '/api/v1/officers';
    const method = isEditMode.value ? 'put' : 'post';

    try {
        const res = await axios[method](url, form.value);
        const json = res.data;

        if (json.status === 'success') {
            showToast(json.message || (isEditMode.value ? 'Officer updated successfully!' : 'Officer added successfully!'), 'success');
            showModal.value = false;
            fetchOfficers();
        } else {
            showToast(json.message || 'Failed to save officer data.', 'error');
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast('Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).', 'error');
        } else {
            showToast('An error occurred while saving: ' + (e.response?.data?.message || e.message), 'error');
        }
    } finally {
        isSavingOfficer.value = false;
    }
};

// Toggle Officer Active Status
const toggleOfficerStatus = async (officer) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/api/v1/officers/${officer.id}/toggle`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            officer.is_active = json.is_active;
            showToast(json.message, 'success');
        } else {
            showToast(json.message || 'Failed to update officer status.', 'error');
        }
    } catch (e) {
        showToast('Failed to update status: ' + e.message, 'error');
    }
};

// Delete Officer
const confirmDeleteOfficer = (officer) => {
    officerToDelete.value = officer;
    showDeleteConfirm.value = true;
};

const executeDeleteOfficer = async () => {
    if (!officerToDelete.value) return;
    isDeletingOfficer.value = true;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch(`/api/v1/officers/${officerToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            showDeleteConfirm.value = false;
            officerToDelete.value = null;
            fetchOfficers();
        } else {
            showToast(json.message || 'Failed to delete officer.', 'error');
        }
    } catch (e) {
        showToast('Failed to delete officer: ' + e.message, 'error');
    } finally {
        isDeletingOfficer.value = false;
    }
};

// Trigger Live Stream Sync
const triggerSyncStreams = async () => {
    isSyncingStreams.value = true;
    try {
        const res = await axios.post('/admin/api/sync-streams');
        const json = res.data;
        if (json.status === 'success') {
            showToast(json.message, 'success');
        } else {
            showToast(json.message || 'Stream sync failed.', 'error');
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast('Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).', 'error');
        } else {
            showToast('Failed to sync live streams: ' + (e.response?.data?.message || e.message), 'error');
        }
    } finally {
        isSyncingStreams.value = false;
    }
};

// Trigger Subscriber Count Sync
const triggerSyncSubs = async () => {
    isSyncingSubs.value = true;
    try {
        const res = await axios.post('/admin/api/sync-subscribers');
        const json = res.data;
        if (json.status === 'success') {
            showToast(json.message, 'success');
            fetchOfficers();
        } else {
            showToast(json.message || 'Failed to sync subscribers.', 'error');
        }
    } catch (e) {
        if (e.response && e.response.status === 419) {
            showToast('Sesi Anda telah berakhir (CSRF Expired). Silakan muat ulang halaman (Ctrl+R).', 'error');
        } else {
            showToast('Failed to sync subscribers: ' + (e.response?.data?.message || e.message), 'error');
        }
    } finally {
        isSyncingSubs.value = false;
    }
};

// Logout handler
const handleLogout = () => {
    router.post(route('logout'));
};

// Registered Users Management
const usersList = ref([]);
const isLoadingUsers = ref(false);
const userSearchQuery = ref('');
const selectedUserWatchlistModal = ref(null);
const showUserModal = ref(false);
const isEditUser = ref(false);
const isSavingUser = ref(false);

const userForm = ref({
    id: null,
    name: '',
    email: '',
    password: '',
    role: 'member',
});

const openAddUserModal = () => {
    isEditUser.value = false;
    userForm.value = {
        id: null,
        name: '',
        email: '',
        password: '',
        role: 'member',
    };
    showUserModal.value = true;
};

const openEditUserModal = (user) => {
    isEditUser.value = true;
    userForm.value = {
        id: user.id,
        name: user.name || '',
        email: user.email || '',
        password: '',
        role: user.role || 'member',
    };
    showUserModal.value = true;
};

const saveUser = async () => {
    if (!userForm.value.name.trim() || !userForm.value.email.trim()) {
        showToast('Nama dan Email wajib diisi!', 'error');
        return;
    }
    if (!isEditUser.value && !userForm.value.password.trim()) {
        showToast('Password wajib diisi untuk akun baru!', 'error');
        return;
    }

    isSavingUser.value = true;
    const isEdit = isEditUser.value;
    const url = isEdit ? `/api/v1/admin/users/${userForm.value.id}` : '/api/v1/admin/users';
    const method = isEdit ? 'PUT' : 'POST';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(userForm.value),
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            showUserModal.value = false;
            fetchUsers();
        } else {
            showToast(json.message || 'Gagal menyimpan data akun member.', 'error');
        }
    } catch (e) {
        showToast('Request failed: ' + e.message, 'error');
    } finally {
        isSavingUser.value = false;
    }
};

const fetchUsers = async () => {
    isLoadingUsers.value = true;
    try {
        const res = await fetch('/api/v1/admin/users', {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            usersList.value = json.data || [];
        }
    } catch (e) {
        console.warn('Failed to fetch registered users:', e);
    } finally {
        isLoadingUsers.value = false;
    }
};

const filteredUsers = computed(() => {
    if (!userSearchQuery.value) return usersList.value;
    const q = userSearchQuery.value.toLowerCase().trim();
    return usersList.value.filter(u => 
        u.name?.toLowerCase().includes(q) || 
        u.email?.toLowerCase().includes(q)
    );
});

const handleDeleteUser = async (id, name) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus akun member "${name}"?`)) return;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch(`/api/v1/admin/users/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast('Akun member berhasil dihapus.', 'success');
            fetchUsers();
        } else {
            showToast(json.message || 'Gagal menghapus user.', 'error');
        }
    } catch (e) {
        showToast('Gagal menghapus user: ' + e.message, 'error');
    }
};

onMounted(() => {
    fetchAgencies();
    fetchOfficers();
    fetchAnnouncements();
    fetchUsers();
});
</script>

<template>
    <Head title="Admin Command Hub - Tactical Police Multiview" />

    <TacticalLayout activeTab="admin">
        <!-- MAIN CONTENT AREA -->
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <!-- PAGE HEADER CARD -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-md bg-blue-600 flex items-center justify-center text-white shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-base sm:text-lg font-black tracking-wider text-white font-mono uppercase">
                                Tactical Admin Hub
                            </h1>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold font-mono uppercase bg-slate-950 text-blue-400 border border-slate-800">
                                Dispatcher Portal
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-sans hidden sm:block">
                            Police Unit Roster Management, Master Agencies, Ranks & YouTube Stream Synchronization
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 text-xs font-mono text-slate-400">
                    <span class="px-3 py-1 bg-slate-950 border border-slate-800 rounded-md text-slate-300">
                        Logged in as: <strong class="text-blue-400 font-bold">{{ auth.user?.name || 'Admin' }}</strong>
                    </span>
                </div>
            </div>

            <!-- ADMIN TABS -->
            <div class="flex items-center space-x-2 border-b border-slate-800 pb-2 overflow-x-auto">
                <button 
                    @click="activeTab = 'officers'" 
                    :class="['px-4 py-2 rounded-md text-xs font-bold font-mono tracking-wide transition whitespace-nowrap', activeTab === 'officers' ? 'bg-blue-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-900']"
                >
                    🚔 Officers Roster
                </button>
                <button 
                    @click="activeTab = 'agencies'" 
                    :class="['px-4 py-2 rounded-md text-xs font-bold font-mono tracking-wide transition whitespace-nowrap', activeTab === 'agencies' ? 'bg-blue-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-900']"
                >
                    🏛️ Agencies & Ranks Structure
                </button>
                <button 
                    @click="activeTab = 'certifications'" 
                    :class="['px-4 py-2 rounded-md text-xs font-bold font-mono tracking-wide transition whitespace-nowrap', activeTab === 'certifications' ? 'bg-blue-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-900']"
                >
                    🎖️ Tactical Certifications
                </button>
                <button 
                    @click="activeTab = 'announcements'" 
                    :class="['px-4 py-2 rounded-md text-xs font-bold font-mono tracking-wide transition whitespace-nowrap', activeTab === 'announcements' ? 'bg-blue-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-900']"
                >
                    📢 Promotions & Alerts
                </button>
                <button 
                    @click="activeTab = 'users'" 
                    :class="['px-4 py-2 rounded-md text-xs font-bold font-mono tracking-wide transition whitespace-nowrap flex items-center gap-1.5', activeTab === 'users' ? 'bg-blue-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-900']"
                >
                    <span>👥 Member Accounts</span>
                    <span class="px-1.5 py-0.2 text-[10px] bg-slate-900 text-slate-200 rounded-md font-mono border border-slate-800">{{ usersList.length }}</span>
                </button>
            </div>

            <!-- TAB 1: OFFICERS -->
            <div v-if="activeTab === 'officers'" class="space-y-6">
                <!-- STATS CARDS -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 sm:gap-4">
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-slate-400 uppercase tracking-wider">Total Units</span>
                        <div class="text-2xl font-black text-white mt-1">{{ deptCounts.ALL }}</div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-emerald-400 uppercase tracking-wider">Active Units</span>
                        <div class="text-2xl font-black text-emerald-400 mt-1">{{ deptCounts.active }}</div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-blue-400 uppercase tracking-wider">LSPD</span>
                        <div class="text-2xl font-black text-blue-400 mt-1">{{ deptCounts.LSPD }}</div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-amber-400 uppercase tracking-wider">BCSO</span>
                        <div class="text-2xl font-black text-amber-400 mt-1">{{ deptCounts.BCSO }}</div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-teal-300 uppercase tracking-wider">SASP</span>
                        <div class="text-2xl font-black text-teal-300 mt-1">{{ deptCounts.SASP }}</div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-emerald-400 uppercase tracking-wider">SAPR</span>
                        <div class="text-2xl font-black text-emerald-400 mt-1">{{ deptCounts.SAPR }}</div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                        <span class="text-[11px] font-mono font-bold text-rose-400 uppercase tracking-wider">Disabled</span>
                        <div class="text-2xl font-black text-rose-400 mt-1">{{ deptCounts.inactive }}</div>
                    </div>
                </div>

                <!-- ACTION CONTROL BAR -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 sm:p-5 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                    <!-- Search & Filters -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                        <!-- Search Input -->
                        <div class="relative flex-1 min-w-[220px]">
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Search officer name, callsign, badge, handle..." 
                                class="w-full bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-500 rounded-md px-4 py-2 pl-10 text-xs sm:text-sm focus:outline-none focus:border-blue-500"
                            />
                            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <button 
                                v-if="searchQuery" 
                                @click="searchQuery = ''" 
                                class="absolute right-3 top-2.5 text-slate-500 hover:text-slate-300 text-xs"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Department Tabs Filter -->
                        <div class="flex items-center space-x-1 bg-slate-950 p-1 rounded-md border border-slate-800 shrink-0">
                            <button 
                                v-for="dept in ['ALL', 'LSPD', 'BCSO', 'SASP', 'SAPR']" 
                                :key="dept" 
                                @click="selectedDept = dept"
                                class="px-3 py-1.5 rounded-md text-xs font-bold font-mono transition"
                                :class="selectedDept === dept ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                            >
                                {{ dept }}
                            </button>
                        </div>

                        <!-- Status Filter -->
                        <div class="relative shrink-0">
                            <select 
                                v-model="selectedStatus" 
                                class="appearance-none bg-slate-950 border border-slate-800 text-slate-200 rounded-md pl-3.5 pr-9 py-2 text-xs font-mono focus:outline-none focus:border-blue-500 cursor-pointer"
                            >
                                <option value="ALL">All Statuses</option>
                                <option value="ACTIVE">Active Only</option>
                                <option value="DISABLED">Disabled Only</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons: Sync & Add -->
                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <button 
                            @click="triggerSyncStreams" 
                            :disabled="isSyncingStreams"
                            class="px-3.5 py-2 rounded-md bg-slate-950 hover:bg-slate-800 disabled:opacity-50 text-slate-300 border border-slate-800 text-xs font-bold font-mono transition flex items-center space-x-2"
                        >
                            <span>{{ isSyncingStreams ? 'Syncing...' : 'Sync Live Streams' }}</span>
                        </button>

                        <button 
                            @click="triggerSyncSubs" 
                            :disabled="isSyncingSubs"
                            class="px-3.5 py-2 rounded-md bg-slate-950 hover:bg-slate-800 disabled:opacity-50 text-emerald-400 border border-slate-800 text-xs font-bold font-mono transition flex items-center space-x-2"
                        >
                            <span>{{ isSyncingSubs ? 'Syncing Subs...' : 'Sync Subscribers' }}</span>
                        </button>

                        <button 
                            @click="openAddModal" 
                            class="px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold font-mono transition flex items-center space-x-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Officer</span>
                        </button>
                    </div>
                </div>

                <!-- OFFICERS TABLE -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                    <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400 font-mono">
                        <div>Showing <strong class="text-white">{{ filteredOfficers.length }}</strong> of {{ officers.length }} total units</div>
                        <button @click="fetchOfficers" class="hover:text-blue-400 flex items-center space-x-1">
                            <span>Refresh Data</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-950 text-[11px] font-mono uppercase text-slate-400 border-b border-slate-800">
                                    <th class="py-3 px-4">Officer Unit</th>
                                    <th class="py-3 px-4">Agency & Rank</th>
                                    <th class="py-3 px-4">Callsign / Badge</th>
                                    <th class="py-3 px-4">Duty Status & Certs</th>
                                    <th class="py-3 px-4">Akumulasi Duty & Hashtag Rule</th>
                                    <th class="py-3 px-4">YouTube Handle</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-xs">
                                <tr 
                                    v-for="officer in filteredOfficers" 
                                    :key="officer.id" 
                                    class="hover:bg-slate-800/40 transition"
                                    :class="{ 'opacity-60 bg-slate-950/40': !officer.is_active }"
                                >
                                    <!-- Officer Info & Avatar -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center space-x-3">
                                            <img 
                                                :src="officer.avatar_url || `https://api.dicebear.com/7.x/bottts/svg?seed=${officer.callsign}`" 
                                                referrerpolicy="no-referrer"
                                                class="w-10 h-10 rounded-md bg-slate-950 object-cover border border-slate-800 shrink-0" 
                                                loading="lazy"
                                            />
                                            <div>
                                                <div class="font-bold text-white text-sm leading-tight">
                                                    {{ officer.officer_name }}
                                                </div>
                                                <div class="text-slate-400 text-[11px]">Streamer: {{ officer.streamer_name || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Agency & Rank -->
                                    <td class="py-3.5 px-4 font-mono">
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="getDeptBadgeClass(officer.agency?.agency_code || officer.department)">
                                                {{ officer.agency?.agency_name || officer.department }}
                                            </span>
                                            <span class="text-slate-300 text-[11px] font-medium">
                                                {{ officer.rank_relation?.rank_title || officer.rank || 'Officer' }}
                                                <span v-if="officer.division" class="text-blue-400">({{ officer.division.division_code || officer.division.division_name }})</span>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Callsign / Badge -->
                                    <td class="py-3.5 px-4 font-mono">
                                        <div class="font-bold text-blue-400">{{ officer.callsign }}</div>
                                        <div class="text-slate-400 text-[11px]">Badge {{ officer.badge_number || '#000' }}</div>
                                    </td>

                                    <!-- Duty Status & Certifications -->
                                    <td class="py-3.5 px-4 font-mono">
                                        <div class="text-[11px] text-emerald-400 font-bold mb-1">
                                            {{ officer.duty_status || '10-8 (On-Duty)' }}
                                        </div>
                                        <div class="flex flex-wrap gap-1" v-if="officer.certifications && officer.certifications.length > 0">
                                            <span 
                                                v-for="cert in officer.certifications" 
                                                :key="cert.id"
                                                class="px-1.5 py-0.2 rounded-md bg-slate-950 text-blue-300 text-[9px] border border-slate-800"
                                            >
                                                🎖️ {{ cert.cert_type }}
                                            </span>
                                        </div>
                                        <span v-else class="text-[10px] text-slate-500 italic">No certs</span>
                                    </td>

                                    <!-- Akumulasi Duty & Hashtag Bypass -->
                                    <td class="py-3.5 px-4 font-mono">
                                        <div class="flex items-center space-x-1.5 mb-1">
                                            <span class="text-amber-400 font-bold text-[11px]">⏱️ {{ formatDutyHours(officer.monthly_duty_minutes) }}</span>
                                            <button 
                                                @click="openDutyModal(officer)"
                                                title="Kelola/Sesuaikan Waktu Jam Patroli"
                                                class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-amber-300 text-[10px] border border-slate-700 transition"
                                            >
                                                ✏️ Adjust
                                            </button>
                                        </div>
                                        <div class="mt-1">
                                            <button 
                                                @click="toggleHashtagBypass(officer)"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold border transition flex items-center space-x-1"
                                                :class="officer.bypass_hashtag_check ? 'bg-purple-950/80 text-purple-300 border-purple-700/60 hover:bg-purple-900' : 'bg-slate-900 text-slate-400 border-slate-800 hover:bg-slate-800'"
                                                :title="officer.bypass_hashtag_check ? 'Bypass Aktif: Streamer dihitung valid patroli meski lupa memasukkan hashtag #imepolice' : 'Wajib Hashtag: Streamer harus memasukkan hashtag #imepolice / keyword valid'"
                                            >
                                                <span>{{ officer.bypass_hashtag_check ? '🔓 Bypass Hashtag: ON' : '🔒 Hashtag Check: Strict' }}</span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- YouTube Handle -->
                                    <td class="py-3.5 px-4 font-mono">
                                        <a 
                                            :href="`https://www.youtube.com/${officer.handle && officer.handle.startsWith('@') ? officer.handle : '@' + (officer.handle || '')}`" 
                                            target="_blank" 
                                            class="text-blue-400 hover:underline flex items-center space-x-1"
                                        >
                                            <span>{{ officer.handle }}</span>
                                        </a>
                                    </td>

                                    <!-- Status Toggle -->
                                    <td class="py-3.5 px-4 text-center">
                                        <button 
                                            @click="toggleOfficerStatus(officer)"
                                            class="px-2.5 py-1 rounded-md text-[10px] font-mono font-bold tracking-wider transition border"
                                            :class="officer.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-800 hover:bg-emerald-900' : 'bg-rose-950 text-rose-400 border-rose-800 hover:bg-rose-900'"
                                        >
                                            {{ officer.is_active ? 'ACTIVE' : 'DISABLED' }}
                                        </button>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button 
                                                @click="openEditModal(officer)" 
                                                class="px-2.5 py-1 rounded-md bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 transition text-xs font-mono"
                                            >
                                                Edit
                                            </button>
                                            <button 
                                                @click="confirmDeleteOfficer(officer)" 
                                                class="px-2.5 py-1 rounded-md bg-rose-950 hover:bg-rose-900 text-rose-400 border border-rose-800 transition text-xs font-mono"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- END TAB 1 -->

            <!-- TAB 2: AGENCIES & RANKS STRUCTURE -->
            <div v-if="activeTab === 'agencies'" class="space-y-6 font-mono text-xs">
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase">Master Instansi Kepolisian & Hierarki Pangkat</h2>
                        <p class="text-slate-400 text-[11px] mt-0.5">Kelola data instansi (SASP, LSPD, BCSO, SAPR), tingkatan pangkat, level komando & sub-divisi taktis.</p>
                    </div>
                    <button @click="openAddAgencyModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">
                        + Tambah Instansi
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div 
                        v-for="agency in agenciesList" 
                        :key="agency.id"
                        class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4"
                    >
                        <!-- Agency Header -->
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="px-3 py-1.5 rounded-md font-black text-sm border" :class="getDeptBadgeClass(agency.agency_code)">
                                    {{ agency.agency_code }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">{{ agency.agency_name }}</h3>
                                    <span class="text-[10px] text-slate-400">Yurisdiksi: <strong class="text-blue-400">{{ agency.jurisdiction }}</strong></span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button @click="openEditAgencyModal(agency)" class="px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-slate-200 border border-slate-800 rounded-md text-[11px]">Edit</button>
                                <button @click="deleteAgency(agency.id)" class="px-2.5 py-1 bg-rose-950 hover:bg-rose-900 text-rose-400 border border-rose-800 rounded-md text-[11px]">Hapus</button>
                            </div>
                        </div>

                        <!-- Ranks List -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold border-b border-slate-800 pb-1">
                                <span>TINGKATAN PANGKAT (LEVEL 1-10)</span>
                                <button @click="openAddRankModal(agency.id)" class="text-blue-400 hover:underline">+ Tambah Pangkat</button>
                            </div>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                <div v-for="r in agency.ranks" :key="r.id" class="p-2 bg-slate-950 border border-slate-800 rounded-md flex items-center justify-between">
                                    <div>
                                        <span class="font-bold text-white">{{ r.rank_title }}</span>
                                        <span class="text-[10px] text-slate-400 ml-2">Level {{ r.level }} • ${{ Number(r.base_salary).toLocaleString() }}/duty</span>
                                    </div>
                                    <div class="flex items-center space-x-1.5">
                                        <button @click="openEditRankModal(r)" class="text-slate-400 hover:text-white">✏️</button>
                                        <button @click="deleteRank(r.id)" class="text-rose-400 hover:text-rose-300">✕</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Divisions List -->
                        <div class="space-y-2 pt-2 border-t border-slate-800">
                            <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold border-b border-slate-800 pb-1">
                                <span>SUB-DIVISI KERJA</span>
                                <button @click="openAddDivisionModal(agency.id)" class="text-blue-400 hover:underline">+ Tambah Divisi</button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="d in agency.divisions" :key="d.id" class="px-2.5 py-1 bg-slate-950 border border-slate-800 rounded-md flex items-center gap-2">
                                    <span class="font-bold text-slate-200">{{ d.division_name }}</span>
                                    <span class="text-[10px] text-blue-400">({{ d.division_code || '-' }})</span>
                                    <button @click="deleteDivision(d.id)" class="text-rose-400 hover:text-rose-300 text-[10px]">✕</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- END TAB 2 -->

            <!-- TAB 3: TACTICAL CERTIFICATIONS -->
            <div v-if="activeTab === 'certifications'" class="space-y-6 font-mono text-xs">
                <!-- Cert Add Control -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-3">
                    <h2 class="text-sm font-bold text-white uppercase">Pemberian Kualifikasi Taktis Personel</h2>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <select v-model="certOfficerId" class="bg-slate-950 border border-slate-800 text-slate-200 rounded-md px-3 py-2 text-xs flex-1">
                            <option :value="null">-- Pilih Petugas Kepolisian --</option>
                            <option v-for="o in officers" :key="o.id" :value="o.id">
                                {{ o.officer_name }} ({{ o.callsign }} - {{ o.department }})
                            </option>
                        </select>
                        <input 
                            v-model="certType" 
                            type="text" 
                            placeholder="Contoh: Class 2 Firearms, Air Support Pilot, CQB Breaching..." 
                            class="bg-slate-950 border border-slate-800 text-slate-200 rounded-md px-3 py-2 text-xs flex-1"
                        />
                        <button @click="addCertification" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">
                            + Berikan Kualifikasi
                        </button>
                    </div>
                </div>

                <!-- Certifications Roster Table -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-3">
                    <h3 class="text-sm font-bold text-slate-200">Daftar Kualifikasi Taktis Aktif Member</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="o in officers.filter(o => o.certifications && o.certifications.length > 0)" 
                            :key="o.id"
                            class="p-4 bg-slate-950 border border-slate-800 rounded-xl space-y-2"
                        >
                            <div class="font-bold text-white text-sm flex items-center justify-between">
                                <span>{{ o.officer_name }}</span>
                                <span class="text-blue-400 text-xs font-mono">{{ o.callsign }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span 
                                    v-for="c in o.certifications" 
                                    :key="c.id" 
                                    class="px-2 py-1 bg-slate-900 border border-slate-800 text-blue-300 rounded-md text-[10px] flex items-center gap-1.5"
                                >
                                    <span>🎖️ {{ c.cert_type }}</span>
                                    <button @click="deleteCertification(c.id)" class="text-rose-400 hover:text-white">✕</button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- END TAB 3 -->

            <!-- TAB 4: ANNOUNCEMENTS & ALERTS -->
            <div v-if="activeTab === 'announcements'" class="space-y-6 font-mono text-xs">
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase">Manajemen Promosi, Pengumuman & Alert Dashboard</h2>
                        <p class="text-slate-400 text-[11px] mt-0.5">Kelola banner pengumuman taktis yang muncul di halaman utama CCTV Multiview.</p>
                    </div>
                    <button @click="openAddAnnouncementModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">
                        + Tambah Alert
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="a in announcements" 
                        :key="a.id"
                        class="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-2 flex flex-col justify-between"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-950 text-blue-300 border border-slate-800">
                                    {{ a.type }}
                                </span>
                                <button @click="toggleAnnouncement(a.id)" class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="a.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-800' : 'bg-rose-950 text-rose-400 border-rose-800'">
                                    {{ a.is_active ? 'AKTIF' : 'NONAKTIF' }}
                                </button>
                            </div>
                            <h3 class="text-sm font-bold text-white">{{ a.title }}</h3>
                            <p class="text-slate-300 leading-relaxed">{{ a.message }}</p>
                        </div>

                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">{{ a.action_text || 'Tanpa Action' }}</span>
                            <div class="flex items-center space-x-2">
                                <button @click="openEditAnnouncementModal(a)" class="text-slate-300 hover:text-white">Edit</button>
                                <button @click="deleteAnnouncement(a.id)" class="text-rose-400 hover:text-rose-300">Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- END TAB 4 -->

            <!-- TAB 5: MEMBER ACCOUNTS -->
            <div v-if="activeTab === 'users'" class="space-y-6 font-mono text-xs">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase">Daftar Akun Member Terdaftar</h2>
                        <p class="text-slate-400 text-[11px] mt-0.5">Kelola akun pengunjung & hak akses role di platform IME Police Multiview.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="userSearchQuery" type="text" placeholder="Cari nama / email..." class="w-64 bg-slate-950 border border-slate-800 text-slate-200 rounded-md px-3 py-1.5 text-xs" />
                        <button @click="openAddUserModal" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md shrink-0">
                            + Tambah Member
                        </button>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-950 text-slate-400 text-[11px] uppercase border-b border-slate-800">
                                <th class="p-3.5">Nama Member</th>
                                <th class="p-3.5">Email</th>
                                <th class="p-3.5">Role Access</th>
                                <th class="p-3.5">Watchlist</th>
                                <th class="p-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-slate-800/40">
                                <td class="p-3.5 font-bold text-white">{{ u.name }}</td>
                                <td class="p-3.5 text-slate-300">{{ u.email }}</td>
                                <td class="p-3.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase" :class="{
                                        'bg-purple-950 text-purple-300 border border-purple-800': u.role === 'admin',
                                        'bg-blue-950 text-blue-300 border border-blue-800': u.role === 'clipper',
                                        'bg-slate-950 text-slate-300 border border-slate-800': u.role !== 'admin' && u.role !== 'clipper',
                                    }">
                                        {{ u.role || 'member' }}
                                    </span>
                                </td>
                                <td class="p-3.5">
                                    <button @click="selectedUserWatchlistModal = u" class="text-blue-400 hover:underline">
                                        ⭐ {{ u.watchlists_count || 0 }} Stream
                                    </button>
                                </td>
                                <td class="p-3.5 text-right space-x-1.5">
                                    <button @click="openEditUserModal(u)" class="px-2.5 py-1 bg-slate-950 text-slate-200 border border-slate-800 rounded-md hover:bg-slate-800">
                                        ✏️ Edit
                                    </button>
                                    <button v-if="u.id !== auth.user?.id" @click="handleDeleteUser(u.id, u.name)" class="px-2.5 py-1 bg-rose-950 text-rose-400 border border-rose-800 rounded-md hover:bg-rose-900">
                                        Hapus
                                    </button>
                                    <span v-else class="text-slate-500 italic text-[11px]">Anda (Admin)</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div> <!-- END TAB 5 -->

        </div>

        <!-- MODAL: ADD / EDIT OFFICER (With Cascading Agency Dropdown) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 overflow-y-auto">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-xl p-6 space-y-5 my-8 font-mono text-xs">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white uppercase">
                        {{ isEditMode ? 'Edit Officer Details' : 'Add New Police Unit' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <div class="space-y-4">
                    <!-- YouTube Handle with Auto-Check Button -->
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">YouTube Handle / Channel URL *</label>
                        <div class="flex items-center gap-2">
                            <input 
                                v-model="form.handle" 
                                type="text" 
                                placeholder="@channelname" 
                                class="flex-1 bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2 focus:outline-none focus:border-blue-500"
                            />
                            <button 
                                @click="verifyYouTubeChannel" 
                                :disabled="isCheckingChannel"
                                class="px-3 py-2 bg-slate-950 hover:bg-slate-800 text-blue-400 border border-slate-800 rounded-md font-bold transition shrink-0"
                            >
                                {{ isCheckingChannel ? 'Verifying...' : 'Verify' }}
                            </button>
                        </div>
                    </div>

                    <!-- Streamer Name & Officer Character Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Streamer OOC Name *</label>
                            <input v-model="form.streamer_name" type="text" placeholder="e.g. Gusti Aidan" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Officer IC Name *</label>
                            <input v-model="form.officer_name" type="text" placeholder="e.g. Ofc. Adam Darski" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2" />
                        </div>
                    </div>

                    <!-- Cascading Agency, Rank, & Division Selection -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Instansi (Agency) *</label>
                            <select v-model="form.agency_id" @change="onAgencyChange" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2">
                                <option :value="null">-- Default (LSPD) --</option>
                                <option v-for="a in agenciesList" :key="a.id" :value="a.id">
                                    {{ a.agency_code }} - {{ a.agency_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Pangkat (Rank) *</label>
                            <select v-model="form.rank_id" @change="onRankChange" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2">
                                <option :value="null">-- Select Rank --</option>
                                <option v-for="r in availableRanksForSelectedAgency" :key="r.id" :value="r.id">
                                    {{ r.rank_title }} (Lvl {{ r.level }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Sub-Divisi Kerja</label>
                            <select v-model="form.division_id" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2">
                                <option :value="null">-- General Patrol --</option>
                                <option v-for="d in availableDivisionsForSelectedAgency" :key="d.id" :value="d.id">
                                    {{ d.division_name }} ({{ d.division_code }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Callsign, Badge, Duty Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Callsign Radio *</label>
                            <input v-model="form.callsign" type="text" placeholder="e.g. 1-ADAM-12" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2 font-bold text-blue-400" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Badge Number</label>
                            <input v-model="form.badge_number" type="text" placeholder="e.g. #163" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Duty Status</label>
                            <select v-model="form.duty_status" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2">
                                <option value="10-8 (On-Duty)">10-8 (On-Duty)</option>
                                <option value="10-7 (Off-Duty)">10-7 (Off-Duty)</option>
                                <option value="10-6 (Busy)">10-6 (Busy)</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <!-- Patrol Zone & Active Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Patrol Zone</label>
                            <input v-model="form.patrol_zone" type="text" placeholder="e.g. Mission Row / Downtown" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2" />
                        </div>
                        <div class="flex items-center pt-5">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-blue-500" />
                                <span class="text-slate-200 font-bold">Status Aktif Dimonitor</span>
                            </label>
                        </div>
                    </div>

                    <!-- Admin Duty & Hashtag Settings -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-950/60 rounded-lg border border-slate-800">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Akumulasi Duty (Menit)</label>
                            <input v-model.number="form.monthly_duty_minutes" type="number" min="0" placeholder="0" class="w-full bg-slate-950 border border-slate-800 text-amber-400 font-mono font-bold rounded-md px-3.5 py-2" />
                            <span class="text-[10px] text-slate-400">Setara ~{{ ( (form.monthly_duty_minutes || 0) / 60 ).toFixed(1) }} jam patroli</span>
                        </div>
                        <div class="flex items-center pt-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input v-model="form.bypass_hashtag_check" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-purple-500" />
                                <div>
                                    <span class="text-slate-200 font-bold block text-xs">Break / Bypass Hashtag Rule</span>
                                    <span class="text-[10px] text-slate-400 block leading-tight">Selalu lolos 10-8 walau lupa hashtag #IMEPolice</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <button @click="showModal = false" class="px-4 py-2 bg-slate-950 border border-slate-800 text-slate-300 rounded-md hover:bg-slate-800">Batal</button>
                    <button @click="saveOfficer" :disabled="isSavingOfficer" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">
                        {{ isSavingOfficer ? 'Saving...' : 'Simpan Data Petugas' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD / EDIT AGENCY -->
        <div v-if="showAgencyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md p-6 space-y-4 font-mono text-xs">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-bold text-white uppercase">{{ isEditAgency ? 'Edit Instansi' : 'Tambah Instansi Baru' }}</h3>
                    <button @click="showAgencyModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Kode Instansi (e.g. SASP, LSPD) *</label>
                        <input v-model="agencyForm.agency_code" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Nama Lengkap Instansi *</label>
                        <input v-model="agencyForm.agency_name" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Yurisdiksi *</label>
                        <select v-model="agencyForm.jurisdiction" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2">
                            <option value="Statewide">Statewide</option>
                            <option value="City">City</option>
                            <option value="County">County</option>
                            <option value="State Parks">State Parks</option>
                        </select>
                    </div>
                </div>
                <div class="pt-2 text-right border-t border-slate-800 flex justify-end gap-2">
                    <button @click="showAgencyModal = false" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-md">Batal</button>
                    <button @click="saveAgency" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">Simpan Instansi</button>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD / EDIT RANK -->
        <div v-if="showRankModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md p-6 space-y-4 font-mono text-xs">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-bold text-white uppercase">{{ isEditRank ? 'Edit Pangkat' : 'Tambah Pangkat Baru' }}</h3>
                    <button @click="showRankModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Nama Pangkat (e.g. Senior Trooper) *</label>
                        <input v-model="rankForm.rank_title" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Level (1 - 10)</label>
                            <input v-model="rankForm.level" type="number" min="1" max="10" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Gaji Pokok ($)</label>
                            <input v-model="rankForm.base_salary" type="number" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                        </div>
                    </div>
                </div>
                <div class="pt-2 text-right border-t border-slate-800 flex justify-end gap-2">
                    <button @click="showRankModal = false" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-md">Batal</button>
                    <button @click="saveRank" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">Simpan Pangkat</button>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD / EDIT DIVISION -->
        <div v-if="showDivisionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md p-6 space-y-4 font-mono text-xs">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-bold text-white uppercase">{{ isEditDivision ? 'Edit Sub-Divisi' : 'Tambah Sub-Divisi Baru' }}</h3>
                    <button @click="showDivisionModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Nama Sub-Divisi (e.g. Special Response Team) *</label>
                        <input v-model="divisionForm.division_name" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Kode Divisi (e.g. SRT, HP, CID)</label>
                        <input v-model="divisionForm.division_code" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                    </div>
                </div>
                <div class="pt-2 text-right border-t border-slate-800 flex justify-end gap-2">
                    <button @click="showDivisionModal = false" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-md">Batal</button>
                    <button @click="saveDivision" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md">Simpan Divisi</button>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD / EDIT ANNOUNCEMENT / ALERT -->
        <div v-if="showAnnouncementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-lg p-6 space-y-4 font-mono text-xs">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-bold text-white uppercase">{{ isEditAnnouncement ? 'Edit Announcement / Alert' : 'Tambah Announcement / Alert Baru' }}</h3>
                    <button @click="showAnnouncementModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Tipe Alert *</label>
                            <select v-model="announcementForm.type" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2">
                                <option value="info">Info (Biru)</option>
                                <option value="warning">Peringatan (Kuning)</option>
                                <option value="danger">Bahaya / Darurat (Merah)</option>
                                <option value="success">Sukses (Hijau)</option>
                                <option value="promo">Promosi / Event (Ungu)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Status *</label>
                            <select v-model="announcementForm.is_active" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2">
                                <option :value="true">Aktif (Tampil)</option>
                                <option :value="false">Nonaktif (Sembunyikan)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Judul Alert / Banner *</label>
                        <input v-model="announcementForm.title" type="text" placeholder="Contoh: CODE 3 EMERGENCY ANNOUNCEMENT" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                    </div>

                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">Pesan Pengumuman *</label>
                        <textarea v-model="announcementForm.message" rows="3" placeholder="Tulis isi pengumuman atau instruksi taktis..." class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Teks Tombol Aksi (Opsional)</label>
                            <input v-model="announcementForm.action_text" type="text" placeholder="Contoh: Lihat Detail" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">URL Aksi (Opsional)</label>
                            <input v-model="announcementForm.action_url" type="text" placeholder="https://..." class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Icon (Opsional)</label>
                            <input v-model="announcementForm.icon" type="text" placeholder="Contoh: 🚨, 📢, ⚠️" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">URL Banner Image (Opsional)</label>
                            <input v-model="announcementForm.image_url" type="text" placeholder="https://..." class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2" />
                        </div>
                    </div>
                </div>
                <div class="pt-2 text-right border-t border-slate-800 flex justify-end gap-2">
                    <button @click="showAnnouncementModal = false" class="px-3 py-1.5 bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-800 rounded-md">Batal</button>
                    <button @click="saveAnnouncement" :disabled="isSavingAnnouncement" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md disabled:opacity-50">
                        {{ isSavingAnnouncement ? 'Menyimpan...' : (isEditAnnouncement ? 'Update Alert' : 'Simpan Alert Baru') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- TOAST NOTIFICATION POPUP -->
        <transition 
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="toast.show" 
                class="fixed bottom-5 right-5 z-50 max-w-sm rounded-md p-4 border flex items-center space-x-3 font-mono text-xs"
                :class="{
                    'bg-slate-900 text-emerald-300 border-emerald-800': toast.type === 'success',
                    'bg-slate-900 text-rose-300 border-rose-800': toast.type === 'error',
                    'bg-slate-900 text-blue-300 border-blue-800': toast.type === 'info',
                }"
            >
                <span v-if="toast.type === 'success'" class="text-base">✓</span>
                <span v-else-if="toast.type === 'error'" class="text-base">✕</span>
                <span v-else class="text-base">ℹ</span>
                <div class="flex-1">{{ toast.message }}</div>
                <button @click="toast.show = false" class="text-slate-400 hover:text-white">✕</button>
            </div>
        </transition>

        <!-- USER WATCHLIST DETAIL MODAL -->
        <Teleport to="body">
            <div v-if="selectedUserWatchlistModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
                <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-xl p-6 text-slate-100 font-sans space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">⭐</span>
                            <div>
                                <h3 class="text-sm font-bold font-mono text-white">Cloud Watchlist Member</h3>
                                <p class="text-xs text-slate-400">{{ selectedUserWatchlistModal.name }} ({{ selectedUserWatchlistModal.email }})</p>
                            </div>
                        </div>
                        <button @click="selectedUserWatchlistModal = null" class="text-slate-400 hover:text-white text-lg">✕</button>
                    </div>

                    <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        <div v-if="!selectedUserWatchlistModal.watchlists || selectedUserWatchlistModal.watchlists.length === 0" class="py-6 text-center text-slate-500 font-mono text-xs">
                            Member ini belum menyimpan stream ke Cloud Watchlist.
                        </div>
                        <div v-else v-for="item in selectedUserWatchlistModal.watchlists" :key="item.id" class="p-3 bg-slate-950 border border-slate-800 rounded-md flex items-center justify-between text-xs">
                            <div>
                                <div class="font-bold text-slate-200">{{ item.officer_name || item.video_id }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">Video ID: {{ item.video_id }}</div>
                            </div>
                            <a :href="`https://youtube.com/watch?v=${item.video_id}`" target="_blank" class="px-2.5 py-1 bg-rose-950 text-rose-300 border border-rose-800 rounded-md text-[11px] font-mono hover:bg-rose-900 transition">
                                YouTube ↗
                            </a>
                        </div>
                    </div>

                    <div class="pt-2 text-right border-t border-slate-800">
                        <button @click="selectedUserWatchlistModal = null" class="px-4 py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 text-xs font-bold rounded-md text-slate-200 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL: ADD / EDIT USER MEMBER -->
        <Teleport to="body">
            <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
                <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md p-6 space-y-4 font-mono text-xs text-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <h3 class="text-sm font-bold text-white uppercase">{{ isEditUser ? 'Edit Akun Member' : 'Tambah Akun Member Baru' }}</h3>
                        <button @click="showUserModal = false" class="text-slate-400 hover:text-white">✕</button>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Nama Lengkap *</label>
                            <input v-model="userForm.name" type="text" placeholder="Contoh: John Doe" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Email Address *</label>
                            <input v-model="userForm.email" type="email" placeholder="nama@email.com" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">
                                Password {{ isEditUser ? '(Opsional - Isi jika ingin ubah password)' : '*' }}
                            </label>
                            <input v-model="userForm.password" type="password" placeholder="Minimal 6 karakter" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Role Akses Pengguna *</label>
                            <select v-model="userForm.role" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="member">Member (Pengunjung Umum - Watchlist Cloud)</option>
                                <option value="clipper">Clipper (Editor - Akses Fitur Potong Video Clipper)</option>
                                <option value="admin">Admin (Akses Penuh Command Center & Dashboard Dispatcher)</option>
                            </select>
                        </div>
                    </div>
                    <div class="pt-2 text-right border-t border-slate-800 flex justify-end gap-2">
                        <button @click="showUserModal = false" class="px-3 py-1.5 bg-slate-950 border border-slate-800 text-slate-300 rounded-md hover:bg-slate-800">Batal</button>
                        <button @click="saveUser" :disabled="isSavingUser" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md disabled:opacity-50">
                            {{ isSavingUser ? 'Menyimpan...' : (isEditUser ? 'Update Akun' : 'Simpan Akun Baru') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL: ADJUST DUTY TIME -->
        <Teleport to="body">
            <div v-if="showDutyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 font-mono text-xs">
                <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md p-6 space-y-4 text-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase">Adjust Duty Time</h3>
                            <p class="text-[11px] text-slate-400" v-if="dutyOfficer">{{ dutyOfficer.officer_name }} ({{ dutyOfficer.callsign }})</p>
                        </div>
                        <button @click="showDutyModal = false" class="text-slate-400 hover:text-white">✕</button>
                    </div>

                    <div class="space-y-3">
                        <div class="p-3 bg-slate-950 border border-slate-800 rounded-md text-slate-300">
                            <div>Waktu Akumulasi Saat Ini: <strong class="text-amber-400">{{ formatDutyHours(dutyOfficer?.monthly_duty_minutes) }}</strong></div>
                        </div>

                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Jenis Penyesuaian</label>
                            <select v-model="dutyAction" class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2">
                                <option value="add">➕ Tambah Waktu (Menit)</option>
                                <option value="subtract">➖ Kurangi Waktu (Menit)</option>
                                <option value="set">📌 Set Total Menit Langsung</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Durasi Menit</label>
                            <input 
                                v-model.number="dutyMinutesInput" 
                                type="number" 
                                min="0" 
                                placeholder="Masukkan menit, contoh: 60 (1 jam)" 
                                class="w-full bg-slate-950 border border-slate-800 text-slate-100 rounded-md px-3.5 py-2 font-mono text-white" 
                            />
                            <div class="text-[10px] text-slate-400 mt-1">
                                Subtotal setara ±{{ ((dutyMinutesInput || 0) / 60).toFixed(1) }} jam
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                        <button @click="showDutyModal = false" class="px-4 py-2 bg-slate-950 border border-slate-800 text-slate-300 rounded-md hover:bg-slate-800">Batal</button>
                        <button @click="handleAdjustDuty" :disabled="isAdjustingDuty" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-md disabled:opacity-50">
                            {{ isAdjustingDuty ? 'Memproses...' : 'Terapkan Penyesuaian' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </TacticalLayout>
</template>
