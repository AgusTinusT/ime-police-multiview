<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

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
    auth: {
        type: Object,
        default: () => ({ user: null }),
    },
});

// State
const officers = ref([]);
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

// Modal State
const showModal = ref(false);
const isEditMode = ref(false);
const showDeleteConfirm = ref(false);
const officerToDelete = ref(null);

const form = ref({
    id: null,
    handle: '',
    streamer_name: '',
    officer_name: '',
    callsign: '',
    badge_number: '',
    department: 'LSPD',
    rank: 'Officer',
    patrol_zone: 'Los Santos Metropolitan',
    channel_id: '',
    avatar_url: '',
    is_active: true,
});

const departments = ['LSPD', 'BCSO', 'SASP', 'SAPR'];

const defaultRanks = {
    LSPD: ['Chief of Police', 'Assistant Chief', 'Deputy Chief', 'Captain', 'Lieutenant', 'Sergeant', 'Senior Officer', 'Officer', 'Cadet'],
    BCSO: ['Sheriff', 'Undersheriff', 'Assistant Sheriff', 'Captain', 'Lieutenant', 'Master Sergeant', 'Sergeant', 'Senior Deputy', 'Deputy', 'Cadet'],
    SASP: ['State Commissioner', 'Colonel', 'Major', 'Captain', 'Lieutenant', 'Staff Sergeant', 'Sergeant', 'Senior Trooper', 'Trooper', 'Cadet'],
    SAPR: ['Chief Ranger', 'Assistant Chief Ranger', 'Captain Ranger', 'Lieutenant Ranger', 'Sergeant Ranger', 'Senior Park Ranger', 'Park Ranger', 'Cadet Ranger'],
};

const currentRankOptions = computed(() => {
    return defaultRanks[form.value.department] || defaultRanks.LSPD;
});

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

// Open Modal for Add
const openAddModal = () => {
    isEditMode.value = false;
    form.value = {
        id: null,
        handle: '',
        streamer_name: '',
        officer_name: '',
        callsign: '',
        badge_number: '',
        department: 'LSPD',
        rank: 'Officer',
        patrol_zone: 'Los Santos Metropolitan',
        channel_id: '',
        avatar_url: '',
        is_active: true,
    };
    showModal.value = true;
};

// Open Modal for Edit
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
        patrol_zone: officer.patrol_zone || 'Los Santos Metropolitan',
        channel_id: officer.channel_id || '',
        avatar_url: officer.avatar_url || '',
        is_active: Boolean(officer.is_active),
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/admin/api/check-channel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ handle: form.value.handle }),
        });

        const json = await res.json();
        if (res.ok && json.status === 'success') {
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
        showToast('Failed to verify channel: ' + e.message, 'error');
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = isEditMode.value ? `/api/v1/officers/${form.value.id}` : '/api/v1/officers';
    const method = isEditMode.value ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message || (isEditMode.value ? 'Officer updated successfully!' : 'Officer added successfully!'), 'success');
            showModal.value = false;
            fetchOfficers();
        } else {
            showToast(json.message || 'Failed to save officer data.', 'error');
        }
    } catch (e) {
        showToast('An error occurred while saving: ' + e.message, 'error');
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch('/admin/api/sync-streams', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
        } else {
            showToast(json.message || 'Stream sync failed.', 'error');
        }
    } catch (e) {
        showToast('Failed to sync live streams: ' + e.message, 'error');
    } finally {
        isSyncingStreams.value = false;
    }
};

// Trigger Subscriber Count Sync
const triggerSyncSubs = async () => {
    isSyncingSubs.value = true;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch('/admin/api/sync-subscribers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (res.ok && json.status === 'success') {
            showToast(json.message, 'success');
            fetchOfficers();
        } else {
            showToast(json.message || 'Failed to sync subscribers.', 'error');
        }
    } catch (e) {
        showToast('Failed to sync subscribers: ' + e.message, 'error');
    } finally {
        isSyncingSubs.value = false;
    }
};

// Logout handler
const handleLogout = () => {
    router.post(route('logout'));
};

onMounted(() => {
    fetchOfficers();
});
</script>

<template>
    <Head title="Admin Command Hub - Tactical Police Multiview" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-cyan-500 selection:text-black">
        <!-- TOP HEADER / NAVBAR -->
        <header class="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 shadow-xl px-4 lg:px-8 py-3.5">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-500 flex items-center justify-center shadow-lg shadow-cyan-500/20 border border-cyan-400/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-base sm:text-lg font-black tracking-wider text-white font-mono uppercase">
                                Tactical Admin Hub
                            </h1>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono uppercase bg-cyan-500/20 text-cyan-400 border border-cyan-500/40">
                                Dispatcher Portal
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-sans hidden sm:block">
                            Police Unit Roster Management & YouTube Stream Synchronization
                        </p>
                    </div>
                </div>

                <!-- Right Quick Links & User Profile -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Public Dashboard Link -->
                    <Link 
                        href="/" 
                        class="px-3 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 shadow transition flex items-center space-x-1.5"
                    >
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden sm:inline">Open Public Dashboard</span>
                    </Link>

                    <!-- User Account / Logout -->
                    <div class="flex items-center space-x-2.5 pl-2 border-l border-slate-800">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-white leading-tight">{{ auth.user?.name || 'Admin' }}</div>
                            <div class="text-[10px] text-slate-400 leading-tight">{{ auth.user?.email || 'admin@dispatch' }}</div>
                        </div>
                        <button 
                            @click="handleLogout" 
                            class="p-2 rounded-lg bg-red-950/40 hover:bg-red-900/60 text-red-400 border border-red-800/40 transition hover:text-red-300 shadow"
                            title="Sign Out"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
            <!-- STATS CARDS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 sm:gap-4">
                <div class="bg-slate-900/80 border border-slate-800/90 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-slate-400 uppercase tracking-wider">Total Units</span>
                    <div class="text-2xl font-black text-white mt-1">{{ deptCounts.ALL }}</div>
                </div>

                <div class="bg-slate-900/80 border border-emerald-900/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-emerald-400 uppercase tracking-wider">Active Units</span>
                    <div class="text-2xl font-black text-emerald-400 mt-1">{{ deptCounts.active }}</div>
                </div>

                <div class="bg-slate-900/80 border border-blue-900/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-blue-400 uppercase tracking-wider">LSPD</span>
                    <div class="text-2xl font-black text-blue-400 mt-1">{{ deptCounts.LSPD }}</div>
                </div>

                <div class="bg-slate-900/80 border border-amber-900/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-amber-400 uppercase tracking-wider">BCSO</span>
                    <div class="text-2xl font-black text-amber-400 mt-1">{{ deptCounts.BCSO }}</div>
                </div>

                <div class="bg-slate-900/80 border border-emerald-900/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-emerald-300 uppercase tracking-wider">SASP</span>
                    <div class="text-2xl font-black text-emerald-300 mt-1">{{ deptCounts.SASP }}</div>
                </div>

                <div class="bg-slate-900/80 border border-green-900/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-green-400 uppercase tracking-wider">SAPR</span>
                    <div class="text-2xl font-black text-green-400 mt-1">{{ deptCounts.SAPR }}</div>
                </div>

                <div class="bg-slate-900/80 border border-red-900/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                    <span class="text-[11px] font-mono font-bold text-red-400 uppercase tracking-wider">Disabled</span>
                    <div class="text-2xl font-black text-red-400 mt-1">{{ deptCounts.inactive }}</div>
                </div>
            </div>

            <!-- ACTION CONTROL BAR -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[220px]">
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Search officer name, callsign, badge, handle..." 
                            class="w-full bg-slate-950/80 border border-slate-800 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-2.5 pl-10 text-xs sm:text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-inner"
                        />
                        <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button 
                            v-if="searchQuery" 
                            @click="searchQuery = ''" 
                            class="absolute right-3 top-3 text-slate-500 hover:text-slate-300 text-xs"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Department Tabs Filter -->
                    <div class="flex items-center space-x-1 bg-slate-950/80 p-1 rounded-xl border border-slate-800 shrink-0">
                        <button 
                            v-for="dept in ['ALL', 'LSPD', 'BCSO', 'SASP', 'SAPR']" 
                            :key="dept" 
                            @click="selectedDept = dept"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold font-mono transition"
                            :class="selectedDept === dept ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                        >
                            {{ dept }}
                        </button>
                    </div>

                    <!-- Status Filter with Custom Arrow -->
                    <div class="relative shrink-0">
                        <select 
                            v-model="selectedStatus" 
                            class="appearance-none bg-slate-950/80 border border-slate-800 text-slate-200 rounded-xl pl-3.5 pr-9 py-2.5 text-xs font-mono focus:outline-none focus:border-cyan-500 cursor-pointer shadow-inner"
                        >
                            <option value="ALL">All Statuses</option>
                            <option value="ACTIVE">Active Only</option>
                            <option value="DISABLED">Disabled Only</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: Sync & Add -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <!-- Sync Live Streams Button -->
                    <button 
                        @click="triggerSyncStreams" 
                        :disabled="isSyncingStreams"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-cyan-300 border border-cyan-700/40 text-xs font-bold font-mono shadow-md hover:shadow-cyan-900/30 transition flex items-center space-x-2"
                        title="Run live YouTube stream detection crawler"
                    >
                        <svg class="w-4 h-4 text-cyan-400" :class="{ 'animate-spin': isSyncingStreams }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ isSyncingStreams ? 'Syncing...' : 'Sync Live Streams' }}</span>
                    </button>

                    <!-- Sync Subscribers Button -->
                    <button 
                        @click="triggerSyncSubs" 
                        :disabled="isSyncingSubs"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-emerald-300 border border-emerald-700/40 text-xs font-bold font-mono shadow-md hover:shadow-emerald-900/30 transition flex items-center space-x-2"
                        title="Update subscriber counts for all registered officers"
                    >
                        <svg class="w-4 h-4 text-emerald-400" :class="{ 'animate-spin': isSyncingSubs }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>{{ isSyncingSubs ? 'Syncing Subs...' : 'Sync Subscribers' }}</span>
                    </button>

                    <!-- Add Officer Button (No plus text) -->
                    <button 
                        @click="openAddModal" 
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-bold font-mono shadow-lg shadow-cyan-600/30 transition flex items-center space-x-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Officer</span>
                    </button>
                </div>
            </div>

            <!-- DATA TABLE -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Table Header info -->
                <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400 font-mono">
                    <div>Showing <strong class="text-white">{{ filteredOfficers.length }}</strong> of {{ officers.length }} total units</div>
                    <button @click="fetchOfficers" class="hover:text-cyan-400 flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Refresh Data</span>
                    </button>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 text-[11px] font-mono uppercase text-slate-400 border-b border-slate-800">
                                <th class="py-3 px-4">Officer Unit</th>
                                <th class="py-3 px-4">Department & Rank</th>
                                <th class="py-3 px-4">Callsign / Badge</th>
                                <th class="py-3 px-4">YouTube Handle</th>
                                <th class="py-3 px-4">Subscribers</th>
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
                                            class="w-10 h-10 rounded-xl bg-slate-800 object-cover border border-slate-700/80 shrink-0" 
                                            loading="lazy"
                                        />
                                        <div>
                                            <div class="font-bold text-white text-sm leading-tight flex items-center space-x-1.5">
                                                <span>{{ officer.officer_name }}</span>
                                            </div>
                                            <div class="text-slate-400 text-[11px]">Streamer: {{ officer.streamer_name || '-' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Department & Rank -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="getDeptBadgeClass(officer.department)">
                                            {{ officer.department }}
                                        </span>
                                        <span class="text-slate-300 text-[11px] font-medium">{{ officer.rank || 'Officer' }}</span>
                                    </div>
                                </td>

                                <!-- Callsign / Badge -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-cyan-300">{{ officer.callsign }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ officer.badge_number || '#000' }}</div>
                                </td>

                                <!-- YouTube Handle -->
                                <td class="py-3.5 px-4 font-mono">
                                    <a 
                                        :href="`https://www.youtube.com/${officer.handle}`" 
                                        target="_blank" 
                                        class="text-blue-400 hover:text-blue-300 hover:underline flex items-center space-x-1"
                                    >
                                        <span>{{ officer.handle }}</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </td>

                                <!-- Subscriber Count & SVG Target Icon -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div v-if="officer.subscriber_count">
                                        <div class="font-bold text-slate-200">
                                            {{ (officer.subscriber_count).toLocaleString('en-US') }}
                                        </div>
                                        <!-- Clean SVG Target Icon for Target 1K -->
                                        <div v-if="officer.subscriber_count < 1000" class="text-[10px] text-amber-400 flex items-center gap-1 mt-0.5 font-bold">
                                            <svg class="w-3 h-3 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="5" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                                            </svg>
                                            <span>Target 1K ({{ Math.round((officer.subscriber_count / 1000) * 100) }}%)</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-500 italic text-[11px]">Not synced</span>
                                </td>

                                <!-- Status Toggle -->
                                <td class="py-3.5 px-4 text-center">
                                    <button 
                                        @click="toggleOfficerStatus(officer)"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold tracking-wider transition border shadow-sm"
                                        :class="officer.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-600/50 hover:bg-emerald-900' : 'bg-red-950 text-red-400 border-red-700/50 hover:bg-red-900'"
                                    >
                                        {{ officer.is_active ? 'ACTIVE' : 'DISABLED' }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Edit Button -->
                                        <button 
                                            @click="openEditModal(officer)" 
                                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition"
                                            title="Edit Officer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            @click="confirmDeleteOfficer(officer)" 
                                            class="p-1.5 rounded-lg bg-red-950/40 hover:bg-red-900/60 text-red-400 hover:text-red-300 border border-red-800/40 transition"
                                            title="Delete Officer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredOfficers.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-500 font-mono">
                                    <div class="text-3xl mb-2">🔍</div>
                                    <div>No officers matching the filter criteria.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- MODAL: ADD / EDIT OFFICER -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-5 my-8">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white font-mono flex items-center space-x-2">
                        <span>{{ isEditMode ? 'Edit Officer Details' : 'Add New Police Unit' }}</span>
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <!-- Modal Body Form -->
                <div class="space-y-4 text-xs font-mono">
                    <!-- YouTube Handle with Auto-Check Button -->
                    <div>
                        <label class="block text-slate-300 mb-1 font-bold">YouTube Handle / Channel URL *</label>
                        <div class="flex items-center gap-2">
                            <input 
                                v-model="form.handle" 
                                type="text" 
                                placeholder="@channelname or https://www.youtube.com/@channel" 
                                class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                            <button 
                                @click="verifyYouTubeChannel" 
                                :disabled="isCheckingChannel"
                                type="button"
                                class="px-3.5 py-2.5 rounded-xl bg-cyan-700 hover:bg-cyan-600 disabled:opacity-50 text-white font-bold transition flex items-center space-x-1 shrink-0"
                            >
                                <svg v-if="isCheckingChannel" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>{{ isCheckingChannel ? 'Verifying...' : 'Check Channel' }}</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Click 'Check Channel' to automatically fetch avatar, channel ID, and streamer title from YouTube.</p>
                    </div>

                    <!-- Officer Name & Streamer Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Officer Character Name *</label>
                            <input 
                                v-model="form.officer_name" 
                                type="text" 
                                placeholder="e.g. Ofc. Bido Saputra" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Streamer / Player Name</label>
                            <input 
                                v-model="form.streamer_name" 
                                type="text" 
                                placeholder="e.g. BidoSaputra" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                    </div>

                    <!-- Department & Rank -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Department *</label>
                            <div class="relative">
                                <select 
                                    v-model="form.department" 
                                    class="w-full appearance-none bg-slate-950 border border-slate-800 rounded-xl pl-3.5 pr-9 py-2.5 text-white focus:outline-none focus:border-cyan-500 cursor-pointer"
                                >
                                    <option value="LSPD">LSPD (Los Santos Police)</option>
                                    <option value="BCSO">BCSO (Blaine County Sheriff)</option>
                                    <option value="SASP">SASP (San Andreas State Police)</option>
                                    <option value="SAPR">SAPR (San Andreas Park Rangers)</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Rank / Position</label>
                            <input 
                                v-model="form.rank" 
                                list="rank-suggestions"
                                type="text" 
                                placeholder="Select or enter rank..." 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                            <datalist id="rank-suggestions">
                                <option v-for="r in currentRankOptions" :key="r" :value="r" />
                            </datalist>
                        </div>
                    </div>

                    <!-- Callsign & Badge Number -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Unit Callsign *</label>
                            <input 
                                v-model="form.callsign" 
                                type="text" 
                                placeholder="e.g. 1-LINCOLN-10" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500 font-bold"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Badge Number</label>
                            <input 
                                v-model="form.badge_number" 
                                type="text" 
                                placeholder="e.g. #104" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                    </div>

                    <!-- Patrol Zone & Channel ID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Patrol Zone</label>
                            <input 
                                v-model="form.patrol_zone" 
                                type="text" 
                                placeholder="Los Santos Metropolitan" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1 font-bold">Channel ID (Optional)</label>
                            <input 
                                v-model="form.channel_id" 
                                type="text" 
                                placeholder="UCxxxxxxxxxxxxxxxx" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                    </div>

                    <!-- Status Active Checkbox -->
                    <div class="flex items-center space-x-2 pt-2">
                        <input 
                            v-model="form.is_active" 
                            type="checkbox" 
                            id="is_active_cb" 
                            class="rounded bg-slate-950 border-slate-800 text-cyan-600 focus:ring-cyan-500 w-4 h-4"
                        />
                        <label for="is_active_cb" class="text-slate-300 font-bold cursor-pointer">
                            Active Unit (Enable in multiview and patrol search)
                        </label>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-3 border-t border-slate-800 pt-4 font-mono">
                    <button 
                        @click="showModal = false" 
                        type="button" 
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition text-xs"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveOfficer" 
                        :disabled="isSavingOfficer"
                        type="button" 
                        class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white font-bold transition text-xs shadow-lg shadow-cyan-600/30 flex items-center space-x-1.5"
                    >
                        <svg v-if="isSavingOfficer" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>{{ isSavingOfficer ? 'Saving...' : (isEditMode ? 'Save Changes' : 'Add Officer') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: DELETE CONFIRMATION -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-red-900/60 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
                <div class="text-red-400 font-bold font-mono text-base flex items-center space-x-2">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Confirm Officer Deletion</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed font-sans">
                    Are you sure you want to permanently delete officer <strong class="text-white">{{ officerToDelete?.officer_name }}</strong> ({{ officerToDelete?.callsign }}) from the database?
                </p>
                <div class="flex items-center justify-end space-x-3 pt-3 font-mono">
                    <button 
                        @click="showDeleteConfirm = false" 
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition text-xs"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="executeDeleteOfficer" 
                        :disabled="isDeletingOfficer"
                        class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white font-bold transition text-xs shadow-lg shadow-red-600/30"
                    >
                        {{ isDeletingOfficer ? 'Deleting...' : 'Yes, Delete Permanently' }}
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
                class="fixed bottom-5 right-5 z-50 max-w-sm rounded-2xl p-4 shadow-2xl border flex items-center space-x-3 font-mono text-xs"
                :class="{
                    'bg-emerald-950/95 text-emerald-200 border-emerald-600/60 shadow-emerald-950/50': toast.type === 'success',
                    'bg-red-950/95 text-red-200 border-red-600/60 shadow-red-950/50': toast.type === 'error',
                    'bg-slate-900/95 text-slate-200 border-cyan-500/60 shadow-black/60': toast.type === 'info',
                }"
            >
                <span v-if="toast.type === 'success'" class="text-base">✓</span>
                <span v-else-if="toast.type === 'error'" class="text-base">✕</span>
                <span v-else class="text-base">ℹ</span>
                <div class="flex-1">{{ toast.message }}</div>
                <button @click="toast.show = false" class="text-slate-400 hover:text-white">✕</button>
            </div>
        </transition>
    </div>
</template>
