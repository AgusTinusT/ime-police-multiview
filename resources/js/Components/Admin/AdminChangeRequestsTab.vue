<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const emit = defineEmits(["toast"]);

const requests = ref([]);
const isLoading = ref(false);
const statusFilter = ref("pending"); // 'pending' | 'approved' | 'rejected' | 'all'

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

const handleApprove = async (req) => {
    const adminReason = prompt(`Setujui pengajuan ${req.field === 'rank_id' ? 'pangkat' : 'kesatuan'} untuk ${req.officer?.officer_name}? Catatan Komando (opsional):`, "Disetujui Komando SASP");
    if (adminReason === null) return;

    try {
        const res = await axios.post(`/api/v1/admin/officer-change-requests/${req.id}/approve`, {
            reason: adminReason.trim() || undefined,
        });
        emit("toast", res.data.message || "Pengajuan berhasil disetujui!", "success");
        fetchRequests();
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal menyetujui pengajuan.", "error");
    }
};

const handleReject = async (req) => {
    const adminReason = prompt(`Tolak pengajuan ${req.field === 'rank_id' ? 'pangkat' : 'kesatuan'} untuk ${req.officer?.officer_name}. Alasan penolakan (wajib):`, "Tidak memenuhi persyaratan masa dinas");
    if (adminReason === null) return;
    if (!adminReason.trim()) {
        emit("toast", "Alasan penolakan wajib diisi.", "error");
        return;
    }

    try {
        const res = await axios.post(`/api/v1/admin/officer-change-requests/${req.id}/reject`, {
            reason: adminReason.trim(),
        });
        emit("toast", res.data.message || "Pengajuan telah ditolak.", "success");
        fetchRequests();
    } catch (err) {
        emit("toast", err.response?.data?.message || "Gagal menolak pengajuan.", "error");
    }
};

onMounted(() => {
    fetchRequests();
});
</script>

<template>
    <div class="space-y-5 font-mono text-xs">
        <!-- HEADER & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-xl p-4">
            <div>
                <h2 class="text-sm font-bold text-white uppercase flex items-center gap-2">
                    <span>Pengajuan Pangkat & Kesatuan (PRD v3)</span>
                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-blue-950 text-blue-300 border border-blue-500/40">
                        KOMANDO REVIEW
                    </span>
                </h2>
                <p class="text-slate-400 text-[11px] mt-0.5">
                    Tinjau dan verifikasi pengajuan kenaikan pangkat atau mutasi kesatuan dari perwira aktif.
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Status Filter Pills -->
                <div class="inline-flex rounded-lg bg-slate-950 p-1 border border-slate-800">
                    <button
                        @click="statusFilter = 'pending'; fetchRequests()"
                        :class="[
                            'px-3 py-1 rounded-md text-[11px] font-bold transition cursor-pointer',
                            statusFilter === 'pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Pending
                    </button>
                    <button
                        @click="statusFilter = 'approved'; fetchRequests()"
                        :class="[
                            'px-3 py-1 rounded-md text-[11px] font-bold transition cursor-pointer',
                            statusFilter === 'approved' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Disetujui
                    </button>
                    <button
                        @click="statusFilter = 'rejected'; fetchRequests()"
                        :class="[
                            'px-3 py-1 rounded-md text-[11px] font-bold transition cursor-pointer',
                            statusFilter === 'rejected' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40' : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Ditolak
                    </button>
                    <button
                        @click="statusFilter = 'all'; fetchRequests()"
                        :class="[
                            'px-3 py-1 rounded-md text-[11px] font-bold transition cursor-pointer',
                            statusFilter === 'all' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40' : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Semua
                    </button>
                </div>

                <button
                    @click="fetchRequests"
                    :disabled="isLoading"
                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 transition text-[11px] cursor-pointer"
                >
                    {{ isLoading ? "Memuat..." : "Refresh" }}
                </button>
            </div>
        </div>

        <!-- TABLE OF REQUESTS -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-lg">
            <div v-if="isLoading" class="p-8 text-center text-slate-400">
                Memuat data pengajuan...
            </div>

            <div v-else-if="requests.length === 0" class="p-8 text-center text-slate-500">
                Tidak ada pengajuan mutasi / pangkat dengan status <span class="font-bold text-slate-400">{{ statusFilter }}</span>.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-950 text-[10px] uppercase text-slate-400 border-b border-slate-800">
                            <th class="py-3 px-4">Perwira & Kesatuan</th>
                            <th class="py-3 px-4">Jenis & Perubahan</th>
                            <th class="py-3 px-4">Alasan & Nomor Sprin</th>
                            <th class="py-3 px-4">Waktu & Reviewer</th>
                            <th class="py-3 px-4 text-center">Status & Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-xs">
                        <tr
                            v-for="req in requests"
                            :key="req.id"
                            class="hover:bg-slate-800/40 transition"
                        >
                            <!-- Officer info -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-white text-sm">
                                    {{ req.officer?.officer_name || "Perwira #" + req.officer_id }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ req.officer?.department || "-" }} • Callsign #{{ req.officer?.callsign || "-" }}
                                </div>
                                <div v-if="req.officer?.user" class="text-[10px] text-slate-500">
                                    Akun: {{ req.officer.user.name }} ({{ req.officer.user.email }})
                                </div>
                            </td>

                            <!-- Field & Change details -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="px-1.5 py-0.5 rounded-sm text-[9px] font-bold uppercase bg-slate-800 text-cyan-300">
                                        {{ req.field === 'rank_id' ? 'Kenaikan Pangkat' : 'Mutasi Kesatuan' }}
                                    </span>
                                </div>
                                <div class="text-slate-300">
                                    Tujuan: <strong class="text-cyan-400">{{ req.new_value }}</strong>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Sebelumnya: {{ req.old_value || "(belum diatur)" }}
                                </div>
                            </td>

                            <!-- Reason from officer -->
                            <td class="py-3 px-4 max-w-xs">
                                <p class="text-slate-200 text-[11px] italic leading-relaxed">
                                    "{{ req.reason }}"
                                </p>
                                <p v-if="req.admin_reason" class="text-[10px] text-amber-300 mt-1">
                                    Catatan: {{ req.admin_reason }}
                                </p>
                            </td>

                            <!-- Timestamps & Reviewer -->
                            <td class="py-3 px-4 text-[11px] text-slate-400">
                                <div>Diajukan: {{ new Date(req.created_at).toLocaleString('id-ID') }}</div>
                                <div v-if="req.reviewer" class="text-slate-500 mt-0.5">
                                    Direview: {{ req.reviewer.name }}
                                </div>
                            </td>

                            <!-- Status & Actions -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase',
                                            req.status === 'pending' ? 'bg-amber-950 text-amber-300 border border-amber-500/40' :
                                            req.status === 'approved' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/40' :
                                            'bg-rose-950 text-rose-300 border border-rose-500/40'
                                        ]"
                                    >
                                        {{ req.status }}
                                    </span>

                                    <div v-if="req.status === 'pending'" class="flex items-center gap-1.5 mt-1">
                                        <button
                                            @click="handleApprove(req)"
                                            class="px-2.5 py-1 rounded-sm bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold transition cursor-pointer"
                                            title="Setujui dan mutasikan pangkat/kesatuan perwira ini"
                                        >
                                            ✓ Setujui
                                        </button>
                                        <button
                                            @click="handleReject(req)"
                                            class="px-2.5 py-1 rounded-sm bg-rose-600 hover:bg-rose-500 text-white text-[10px] font-bold transition cursor-pointer"
                                            title="Tolak pengajuan ini"
                                        >
                                            ✕ Tolak
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
