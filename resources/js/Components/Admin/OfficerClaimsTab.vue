<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

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
    <div class="space-y-4 font-mono text-xs">
        <!-- HEADER & FILTER TOOLBAR -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-white uppercase flex items-center gap-2">
                    <span>🛡️ Antrean Peninjauan Klaim Akun Perwira</span>
                </h2>
                <p class="text-slate-400 text-[11px] mt-0.5">
                    Verifikasi identitas streamer & bind profil dinas 1-to-1 dengan pencegahan impersonasi.
                </p>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    v-for="st in [
                        { key: 'pending', label: '⏳ Menunggu Review' },
                        { key: 'approved', label: '✓ Disetujui' },
                        { key: 'rejected', label: '✕ Ditolak' },
                        { key: 'all', label: 'Semua Status' },
                    ]"
                    :key="st.key"
                    @click="changeStatus(st.key)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition border"
                    :class="
                        statusFilter === st.key
                            ? 'bg-cyan-600 text-black border-cyan-500 shadow-xs'
                            : 'bg-slate-950 text-slate-400 border-slate-800 hover:text-white'
                    "
                >
                    {{ st.label }}
                </button>

                <button
                    @click="fetchClaims(pagination.current_page)"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-950 hover:bg-slate-800 text-cyan-400 border border-slate-800 transition"
                    title="Muat Ulang"
                >
                    ↻
                </button>
            </div>
        </div>

        <!-- CLAIMS TABLE -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-lg">
            <div v-if="isLoading" class="py-12 text-center text-slate-400">
                Memuat antrean klaim...
            </div>

            <div v-else-if="claims.length === 0" class="py-12 text-center text-slate-500 italic">
                Tidak ada pengajuan klaim dalam kategori ini.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-950 text-[11px] uppercase text-slate-400 border-b border-slate-800">
                            <th class="py-3 px-4">Perwira Roster</th>
                            <th class="py-3 px-4">User Pengaju</th>
                            <th class="py-3 px-4">Discord Tag</th>
                            <th class="py-3 px-4">Catatan Pengaju</th>
                            <th class="py-3 px-4">Status & Waktu</th>
                            <th class="py-3 px-4 text-right">Aksi Komando</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr
                            v-for="claim in claims"
                            :key="claim.id"
                            class="hover:bg-slate-800/40 transition"
                        >
                            <!-- Officer Roster Info -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white text-sm">
                                    {{ claim.officer?.officer_name || "-" }}
                                </div>
                                <div class="text-slate-400 text-[11px]">
                                    {{ claim.officer?.streamer_name }} • [{{ claim.officer?.department }} #{{ claim.officer?.callsign }}]
                                </div>
                            </td>

                            <!-- User Info -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-cyan-300">
                                    {{ claim.user?.name || "-" }}
                                </div>
                                <div class="text-slate-400 text-[11px]">
                                    {{ claim.user?.email }}
                                </div>
                            </td>

                            <!-- Discord Tag -->
                            <td class="py-3.5 px-4 font-bold text-amber-300">
                                {{ claim.proof_discord_tag || "-" }}
                            </td>

                            <!-- Notes -->
                            <td class="py-3.5 px-4 text-slate-300 max-w-xs truncate font-sans text-xs">
                                {{ claim.notes || "-" }}
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4">
                                <span
                                    class="px-2 py-0.5 rounded-sm text-[10px] font-black uppercase tracking-wider"
                                    :class="{
                                        'bg-amber-950 text-amber-300 border border-amber-500/40': claim.status === 'pending',
                                        'bg-emerald-950 text-emerald-300 border border-emerald-500/40': claim.status === 'approved',
                                        'bg-rose-950 text-rose-300 border border-rose-500/40': claim.status === 'rejected',
                                        'bg-slate-950 text-slate-400 border border-slate-700': claim.status === 'cancelled',
                                    }"
                                >
                                    {{ claim.status }}
                                </span>
                                <div class="text-[10px] text-slate-500 mt-1">
                                    {{ new Date(claim.created_at).toLocaleString() }}
                                </div>
                                <div v-if="claim.review_reason" class="text-[10px] text-slate-400 italic mt-0.5">
                                    "{{ claim.review_reason }}"
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div v-if="claim.status === 'pending'" class="flex items-center justify-end gap-1.5">
                                    <button
                                        @click="openApproveModal(claim)"
                                        class="px-2.5 py-1 rounded-sm bg-emerald-950 hover:bg-emerald-900 text-emerald-300 border border-emerald-700 transition font-bold"
                                    >
                                        ✓ Setujui
                                    </button>
                                    <button
                                        @click="openRejectModal(claim)"
                                        class="px-2.5 py-1 rounded-sm bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-700 transition font-bold"
                                    >
                                        ✕ Tolak
                                    </button>
                                </div>
                                <div v-else class="text-slate-500 text-[11px] italic">
                                    Oleh: {{ claim.reviewer?.name || "Admin" }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="p-3 bg-slate-950 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400"
            >
                <span>Total {{ pagination.total }} pengajuan</span>
                <div class="flex items-center gap-1">
                    <button
                        @click="fetchClaims(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="px-2 py-1 rounded-sm bg-slate-900 hover:bg-slate-800 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span class="px-2 font-bold text-white">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                    <button
                        @click="fetchClaims(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="px-2 py-1 rounded-sm bg-slate-900 hover:bg-slate-800 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- APPROVE MODAL -->
        <div
            v-if="showApproveModal"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="w-full max-w-md bg-slate-900 border border-emerald-500/40 rounded-2xl p-5 shadow-2xl text-left space-y-4">
                <div class="flex items-center gap-2 text-emerald-400 font-bold border-b border-white/10 pb-2">
                    <span>✓ KONFIRMASI PERSETUJUAN KLAIM</span>
                </div>

                <p class="text-xs text-slate-300 font-sans">
                    Anda akan menghubungkan akun user <strong class="text-white">{{ activeClaim?.user?.name }}</strong> dengan profil dinas perwira <strong class="text-cyan-300">{{ activeClaim?.officer?.officer_name }} ({{ activeClaim?.officer?.callsign }})</strong>.
                </p>

                <div v-if="formError" class="p-2.5 rounded-sm bg-rose-950 border border-rose-500 text-rose-200 text-xs">
                    {{ formError }}
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-300 text-xs uppercase">Catatan / Alasan Persetujuan (Opsional)</label>
                    <input
                        v-model="actionReason"
                        type="text"
                        placeholder="Contoh: Terverifikasi via voice channel kepolisian"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-emerald-500 outline-hidden"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="showApproveModal = false"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleApprove"
                        :disabled="isProcessing"
                        class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold uppercase tracking-wider"
                    >
                        {{ isProcessing ? "Menyetujui..." : "Setujui & Bind Perwira" }}
                    </button>
                </div>
            </div>
        </div>

        <!-- REJECT MODAL -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="w-full max-w-md bg-slate-900 border border-rose-500/40 rounded-2xl p-5 shadow-2xl text-left space-y-4">
                <div class="flex items-center gap-2 text-rose-400 font-bold border-b border-white/10 pb-2">
                    <span>✕ PENOLAKAN KLAIM PERWIRA</span>
                </div>

                <p class="text-xs text-slate-300 font-sans">
                    Pengajuan klaim dari <strong class="text-white">{{ activeClaim?.user?.name }}</strong> untuk <strong class="text-cyan-300">{{ activeClaim?.officer?.officer_name }}</strong> akan ditolak.
                </p>

                <div v-if="formError" class="p-2.5 rounded-sm bg-rose-950 border border-rose-500 text-rose-200 text-xs">
                    {{ formError }}
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-300 text-xs uppercase">Alasan Penolakan <span class="text-rose-400">*</span></label>
                    <textarea
                        v-model="actionReason"
                        rows="2"
                        placeholder="Contoh: Tag Discord tidak merespon / Bukan pemilik kanal resmi"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:border-rose-500 outline-hidden"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="showRejectModal = false"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleReject"
                        :disabled="isProcessing"
                        class="px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-bold uppercase tracking-wider"
                    >
                        {{ isProcessing ? "Menolak..." : "Tolak Pengajuan" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
