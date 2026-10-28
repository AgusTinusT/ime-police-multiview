<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";

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

        // Only send callsign/badge if cooldown is not active
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

const cancelRequest = async (requestId) => {
    if (!confirm("Apakah Anda yakin ingin membatalkan pengajuan mutasi ini?")) {
        return;
    }

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

    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-cyan-500 selection:text-black pb-20">
        <!-- TOP NAVIGATION BAR -->
        <header class="sticky top-0 z-40 bg-slate-900/80 backdrop-blur-md border-b border-white/10 px-4 lg:px-8 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link
                    href="/dashboard/bio-links"
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                    title="Kembali ke Tactical Hub"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-sm sm:text-base font-black font-mono tracking-wider uppercase text-white flex items-center gap-2">
                        <span>PROFIL DINAS PERWIRA</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-cyan-950 text-cyan-300 border border-cyan-500/40">
                            PRD v3
                        </span>
                    </h1>
                    <p class="text-[11px] text-slate-400 font-mono">
                        Manajemen Kepemilikan & Identitas Perwira Mandiri
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    href="/dashboard/bio-links"
                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-mono text-cyan-300 hover:text-cyan-200 border border-cyan-500/30 transition flex items-center gap-1.5"
                >
                    <span>🛡️ Kelola Tautan Bio</span>
                </Link>
            </div>
        </header>

        <!-- FLASH NOTIFICATION -->
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="statusMessage"
                    class="p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-500/60 text-emerald-200 text-xs font-mono shadow-lg flex items-center gap-2"
                >
                    <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ statusMessage }}</span>
                </div>
            </transition>

            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="errorMessage"
                    class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/60 text-rose-200 text-xs font-mono shadow-lg flex items-center gap-2"
                >
                    <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ errorMessage }}</span>
                </div>
            </transition>
        </div>

        <!-- MAIN CONTAINER -->
        <main class="max-w-6xl mx-auto px-4 mt-6">
            <!-- HERO SUMMARY CARD -->
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-white/10 shadow-2xl flex flex-col md:flex-row items-center md:items-start justify-between gap-6 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    <img
                        :src="officer.avatar_url || 'https://api.dicebear.com/7.x/bottts/svg?seed=officer'"
                        class="w-20 h-20 rounded-2xl bg-slate-950 border-2 border-cyan-500/40 object-cover shadow-lg shrink-0"
                        alt=""
                    />
                    <div class="text-center sm:text-left space-y-1">
                        <div class="flex items-center gap-2 flex-wrap justify-center sm:justify-start">
                            <h2 class="text-xl font-black font-mono uppercase text-white tracking-wide">
                                {{ officer.officer_name }}
                            </h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono uppercase bg-emerald-950 text-emerald-300 border border-emerald-500/40">
                                TERVERIFIKASI
                            </span>
                            <span v-if="officer.avatar_is_custom" class="px-2 py-0.5 rounded text-[9px] font-bold font-mono uppercase bg-cyan-950 text-cyan-300 border border-cyan-500/30">
                                AVATAR KUSTOM
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 font-mono">
                            {{ officer.department }} • {{ officer.rank }} • Callsign #{{ officer.callsign }}
                        </p>
                        <p class="text-xs text-slate-400 font-mono">
                            Streamer: <span class="text-cyan-400 font-semibold">{{ officer.streamer_name || "-" }}</span>
                            <span v-if="officer.patrol_zone"> • Sektor: {{ officer.patrol_zone }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <button
                        @click="openChangeRequestModal('rank_id')"
                        class="px-4 py-2 rounded-xl bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/40 text-xs font-mono font-bold transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>🎖️ Ajukan Kenaikan Pangkat</span>
                    </button>
                    <button
                        @click="openChangeRequestModal('agency_id')"
                        class="px-4 py-2 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 border border-blue-500/40 text-xs font-mono font-bold transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>🏢 Ajukan Mutasi Kesatuan</span>
                    </button>
                </div>
            </div>

            <!-- TAB SELECTOR -->
            <div class="mt-6 flex border-b border-slate-800 text-xs font-mono">
                <button
                    @click="switchTab('profile')"
                    class="px-5 py-2.5 font-bold transition border-b-2 flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'profile' ? 'border-cyan-400 text-cyan-300 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-white'"
                >
                    <span>⚙️ Pengaturan Profil</span>
                </button>
                <button
                    @click="switchTab('requests')"
                    class="px-5 py-2.5 font-bold transition border-b-2 flex items-center gap-2 cursor-pointer relative"
                    :class="activeTab === 'requests' ? 'border-cyan-400 text-cyan-300 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-white'"
                >
                    <span>📜 Pengajuan Mutasi & Pangkat</span>
                    <span
                        v-if="localRequests.filter(r => r.status === 'pending').length > 0"
                        class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"
                    ></span>
                </button>
                <button
                    @click="switchTab('history')"
                    class="px-5 py-2.5 font-bold transition border-b-2 flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'history' ? 'border-cyan-400 text-cyan-300 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-white'"
                >
                    <span>🕒 Riwayat Perubahan</span>
                </button>
            </div>

            <!-- TAB 1: PROFILE MANAGEMENT (PRD v3 4.3 4 GROUPS) -->
            <div v-if="activeTab === 'profile'" class="mt-6 space-y-6">
                <!-- GROUP 1: IDENTITAS KARAKTER & STREAMER (LANGSUNG) -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-white flex items-center gap-2">
                                <span>Grup 1: Identitas Karakter & Streamer</span>
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-950 text-emerald-300 border border-emerald-500/30">
                                    LANGSUNG
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Perubahan identitas nama dibatasi maksimal 3 kali dalam 30 hari kalender.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Nama Karakter RP <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.officer_name"
                                type="text"
                                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none"
                                placeholder="Contoh: Saprudin Mintoro"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Nama Streamer / Alias
                            </label>
                            <input
                                v-model="form.streamer_name"
                                type="text"
                                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none"
                                placeholder="Contoh: Bang Sapru Gaming"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Sektor / Zona Patroli Rutin
                            </label>
                            <input
                                v-model="form.patrol_zone"
                                type="text"
                                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none"
                                placeholder="Contoh: Vinewood Hills & Sandy Shores"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Divisi / Satuan Tugas
                            </label>
                            <select
                                v-model="form.division_id"
                                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none"
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

                        <div class="md:col-span-2 space-y-2 pt-2 border-t border-slate-800/80">
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Foto Avatar Profil
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.avatar_url"
                                    type="url"
                                    class="flex-1 px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none"
                                    placeholder="https://i.imgur.com/..."
                                />
                                <label class="flex items-center gap-2 cursor-pointer select-none text-xs font-mono text-slate-300">
                                    <input
                                        v-model="form.use_custom_avatar"
                                        type="checkbox"
                                        class="rounded bg-slate-950 border-slate-700 text-cyan-500 focus:ring-0"
                                    />
                                    <span>Gunakan Avatar Kustom (Kunci dari Overwrite Sync YouTube)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GROUP 2: PENANDA UNIT (LANGSUNG + CEK UNIK + COOLDOWN 7 HARI) -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-white flex items-center gap-2">
                                <span>Grup 2: Penanda Unit & Callsign</span>
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-950 text-amber-300 border border-amber-500/30">
                                    COOLDOWN 7 HARI
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Callsign harus unik di seluruh kesatuan aktif dan tunduk pada batas ubah 1x per 7 hari.
                            </p>
                        </div>

                        <div v-if="!canChangeCallsign" class="px-3 py-1 rounded-xl bg-amber-950/60 border border-amber-500/50 text-amber-300 text-xs font-mono flex items-center gap-2">
                            <span>⏳ Cooldown aktif: sisa {{ remainingCooldownDays }} hari</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Callsign Unit <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.callsign"
                                :disabled="!canChangeCallsign"
                                type="text"
                                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none disabled:opacity-50 disabled:cursor-not-allowed uppercase"
                                placeholder="Contoh: 1-ADAM-12"
                            />
                            <p class="text-[10px] text-slate-500 font-mono mt-1">
                                Tidak boleh sama dengan callsign perwira aktif lainnya.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">
                                Nomor Lencana / Badge
                            </label>
                            <input
                                v-model="form.badge_number"
                                :disabled="!canChangeCallsign"
                                type="text"
                                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 text-sm font-mono text-white outline-none disabled:opacity-50 disabled:cursor-not-allowed font-mono"
                                placeholder="Contoh: 704"
                            />
                        </div>
                    </div>
                </div>

                <!-- GROUP 3: PANGKAT & KESATUAN (LEWAT PENGAJUAN KOMANDO) -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-white flex items-center gap-2">
                                <span>Grup 3: Pangkat & Kesatuan</span>
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-blue-950 text-blue-300 border border-blue-500/30">
                                    LEWAT PENGAJUAN KOMANDO
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Kenaikan pangkat atau pindah kesatuan memerlukan verifikasi dan persetujuan Dispatcher / Komando SASP.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-mono text-slate-400 block">Kesatuan Saat Ini</span>
                                <span class="text-sm font-black font-mono text-white">{{ officer.department || "-" }}</span>
                            </div>
                            <button
                                @click="openChangeRequestModal('agency_id')"
                                class="px-3 py-1.5 rounded-lg bg-blue-950/60 hover:bg-blue-900/60 border border-blue-500/40 text-blue-300 text-xs font-mono transition cursor-pointer"
                            >
                                Ajukan Mutasi
                            </button>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-mono text-slate-400 block">Pangkat Saat Ini</span>
                                <span class="text-sm font-black font-mono text-white">{{ officer.rank || "-" }}</span>
                            </div>
                            <button
                                @click="openChangeRequestModal('rank_id')"
                                class="px-3 py-1.5 rounded-lg bg-amber-950/60 hover:bg-amber-900/60 border border-amber-500/40 text-amber-300 text-xs font-mono transition cursor-pointer"
                            >
                                Ajukan Kenaikan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- GROUP 4: IDENTITAS TERKUNCI (READ-ONLY 🔒) -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 shadow-lg">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold font-mono uppercase text-white flex items-center gap-2">
                                <span>Grup 4: Identitas Terkunci</span>
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-rose-950 text-rose-300 border border-rose-500/30">
                                    LOCKED 🔒
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Dikunci permanen oleh sistem untuk mencegah pembajakan channel dan broken link publik.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <span class="text-[10px] uppercase font-mono text-slate-400 block">Channel ID YouTube</span>
                            <span class="text-xs font-mono text-slate-300 truncate block mt-1 select-all font-semibold">
                                {{ officer.channel_id || "-" }}
                            </span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <span class="text-[10px] uppercase font-mono text-slate-400 block">YouTube Custom URL</span>
                            <span class="text-xs font-mono text-cyan-300 truncate block mt-1">
                                {{ officer.custom_url || "-" }}
                            </span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <span class="text-[10px] uppercase font-mono text-slate-400 block">Bio-Link Slug</span>
                            <span class="text-xs font-mono text-emerald-300 truncate block mt-1 select-all">
                                /@{{ officer.bio_profile?.slug || officer.slug || "-" }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 p-3 rounded-xl bg-slate-950/40 border border-slate-800 text-[11px] text-slate-400 font-mono flex items-center gap-2">
                        <span>ℹ️</span>
                        <span>Jika Anda mengganti channel YouTube utama Anda, ajukan tiket bantuan ke Admin SASP untuk re-verifikasi.</span>
                    </div>
                </div>

                <!-- SAVE BUTTON ACTIONS -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button
                        @click="saveProfile"
                        :disabled="isSavingProfile"
                        class="px-6 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-mono font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-cyan-500/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer flex items-center gap-2"
                    >
                        <svg v-if="isSavingProfile" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span>{{ isSavingProfile ? "Menyimpan Perubahan..." : "Simpan Pembaruan Profil" }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 2: CHANGE REQUESTS (PENGAJUAN MUTASI & PANGKAT) -->
            <div v-if="activeTab === 'requests'" class="mt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold font-mono uppercase text-white">
                            Riwayat Pengajuan Pangkat & Kesatuan
                        </h3>
                        <p class="text-xs text-slate-400">
                            Daftar tiket mutasi atau kenaikan pangkat yang pernah diajukan.
                        </p>
                    </div>

                    <button
                        @click="openChangeRequestModal('rank_id')"
                        class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-mono font-bold transition cursor-pointer"
                    >
                        + Buat Pengajuan Baru
                    </button>
                </div>

                <div v-if="localRequests.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-dashed border-slate-800 text-center text-xs font-mono text-slate-500">
                    Belum ada pengajuan mutasi pangkat atau kesatuan.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="req in localRequests"
                        :key="req.id"
                        class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono uppercase bg-slate-800 text-slate-300">
                                    {{ req.field === 'rank_id' ? 'Kenaikan Pangkat' : 'Mutasi Kesatuan' }}
                                </span>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded text-[9px] font-bold font-mono uppercase',
                                        req.status === 'pending' ? 'bg-amber-950 text-amber-300 border border-amber-500/30' :
                                        req.status === 'approved' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' :
                                        'bg-rose-950 text-rose-300 border border-rose-500/30'
                                    ]"
                                >
                                    {{ req.status }}
                                </span>
                                <span class="text-[10px] text-slate-500 font-mono">
                                    Diajukan: {{ new Date(req.created_at).toLocaleDateString('id-ID') }}
                                </span>
                            </div>

                            <p class="text-xs font-mono text-slate-200">
                                Permintaan ke: <strong class="text-cyan-300">{{ req.new_value }}</strong>
                                <span class="text-slate-500"> (Sebelumnya: {{ req.old_value || '-' }})</span>
                            </p>

                            <p class="text-[11px] text-slate-400 italic">
                                Alasan: "{{ req.reason }}"
                            </p>

                            <p v-if="req.admin_reason" class="text-[11px] text-amber-300/90 font-mono">
                                Catatan Komando: {{ req.admin_reason }}
                            </p>
                        </div>

                        <div v-if="req.status === 'pending'" class="flex items-center gap-2">
                            <button
                                @click="cancelRequest(req.id)"
                                class="px-3 py-1.5 rounded-lg bg-rose-950/60 hover:bg-rose-900/60 border border-rose-500/40 text-rose-300 text-xs font-mono transition cursor-pointer"
                            >
                                Batalkan Pengajuan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: AUDIT HISTORY -->
            <div v-if="activeTab === 'history'" class="mt-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold font-mono uppercase text-white">
                            Log Audit Perubahan Profil
                        </h3>
                        <p class="text-xs text-slate-400">
                            Catatan audit keamanan perubahan profil mandiri dan aksi admin.
                        </p>
                    </div>

                    <button
                        @click="loadHistory"
                        :disabled="isLoadingHistory"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 transition cursor-pointer"
                    >
                        {{ isLoadingHistory ? "Memuat..." : "Segarkan Log" }}
                    </button>
                </div>

                <div v-if="isLoadingHistory" class="p-8 text-center text-xs font-mono text-slate-400">
                    Memuat riwayat audit...
                </div>

                <div v-else-if="historyLogs.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-dashed border-slate-800 text-center text-xs font-mono text-slate-500">
                    Belum ada riwayat perubahan yang tercatat.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="log in historyLogs"
                        :key="log.id"
                        class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 text-xs font-mono flex items-start justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-800 text-cyan-300">
                                    {{ log.action }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                </span>
                            </div>
                            <p class="text-slate-300">
                                {{ log.details }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL: AJUKAN KENAIKAN PANGKAT / MUTASI KESATUAN -->
        <div
            v-if="isRequestModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in"
        >
            <div class="w-full max-w-md rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold font-mono uppercase text-white flex items-center gap-2">
                        <span>{{ requestForm.field === 'rank_id' ? '🎖️ Ajukan Kenaikan Pangkat' : '🏢 Ajukan Mutasi Kesatuan' }}</span>
                    </h3>
                    <button
                        @click="isRequestModalOpen = false"
                        class="text-slate-400 hover:text-white text-lg font-mono"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <!-- Field Type Toggle -->
                    <div>
                        <label class="block text-slate-400 mb-1">Jenis Pengajuan</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="requestForm.field = 'rank_id'"
                                :class="[
                                    'py-2 px-3 rounded-xl border text-center transition cursor-pointer',
                                    requestForm.field === 'rank_id' ? 'bg-amber-950/80 border-amber-500/60 text-amber-300' : 'bg-slate-950 border-slate-800 text-slate-400'
                                ]"
                            >
                                Pangkat
                            </button>
                            <button
                                type="button"
                                @click="requestForm.field = 'agency_id'"
                                :class="[
                                    'py-2 px-3 rounded-xl border text-center transition cursor-pointer',
                                    requestForm.field === 'agency_id' ? 'bg-blue-950/80 border-blue-500/60 text-blue-300' : 'bg-slate-950 border-slate-800 text-slate-400'
                                ]"
                            >
                                Kesatuan
                            </button>
                        </div>
                    </div>

                    <!-- Target Selection for Agency Mutasi -->
                    <div v-if="requestForm.field === 'agency_id'">
                        <label class="block text-slate-400 mb-1">Kesatuan Tujuan <span class="text-rose-400">*</span></label>
                        <select
                            v-model="requestForm.new_value"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white outline-none focus:border-cyan-500"
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
                    <div v-else>
                        <label class="block text-slate-400 mb-1">Pangkat Tujuan <span class="text-rose-400">*</span></label>
                        <select
                            v-model="requestForm.new_value"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white outline-none focus:border-cyan-500"
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
                    <div>
                        <label class="block text-slate-400 mb-1">
                            Alasan & Dasar Perintah Dinas <span class="text-rose-400">*</span>
                        </label>
                        <textarea
                            v-model="requestForm.reason"
                            rows="3"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white outline-none focus:border-cyan-500"
                            placeholder="Contoh: Telah lulus diklat perwira / Nomor Sprin: SP/124/X/2026/SASP"
                        ></textarea>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2">
                    <button
                        @click="isRequestModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitChangeRequest"
                        :disabled="isSubmittingRequest"
                        class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-mono font-bold transition cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmittingRequest ? "Mengirim..." : "Kirim Pengajuan" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
