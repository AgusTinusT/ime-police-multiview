<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "claimed"]);

const isLoading = ref(false);
const unclaimedOfficers = ref([]);
const myClaims = ref([]);
const searchQuery = ref("");

// Form state
const selectedOfficerId = ref("");
const proofDiscordTag = ref("");
const notes = ref("");
const isSubmitting = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

const activeClaim = computed(() => {
    return myClaims.value.find((c) => c.status === "pending") || myClaims.value[0] || null;
});

const filteredOfficers = computed(() => {
    if (!searchQuery.value) return unclaimedOfficers.value;
    const q = searchQuery.value.toLowerCase();
    return unclaimedOfficers.value.filter(
        (o) =>
            o.officer_name.toLowerCase().includes(q) ||
            o.callsign.toLowerCase().includes(q) ||
            o.department.toLowerCase().includes(q) ||
            (o.streamer_name && o.streamer_name.toLowerCase().includes(q))
    );
});

const loadData = async () => {
    isLoading.value = true;
    errorMessage.value = "";
    try {
        const [officersRes, claimsRes] = await Promise.all([
            axios.get("/api/v1/officers/unclaimed"),
            axios.get("/api/v1/officer-claims/mine"),
        ]);
        unclaimedOfficers.value = officersRes.data.officers || [];
        myClaims.value = claimsRes.data.claims || [];
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal memuat data perwira.";
    } finally {
        isLoading.value = false;
    }
};

const submitClaim = async () => {
    if (!selectedOfficerId.value) {
        errorMessage.value = "Silakan pilih profil perwira yang ingin Anda klaim.";
        return;
    }
    if (!proofDiscordTag.value.trim()) {
        errorMessage.value = "Discord tag wajib diisi untuk verifikasi identitas perwira.";
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const res = await axios.post("/api/v1/officer-claims", {
            officer_id: selectedOfficerId.value,
            proof_discord_tag: proofDiscordTag.value.trim(),
            notes: notes.value.trim() || null,
        });

        successMessage.value = res.data.message || "Pengajuan klaim berhasil dikirim.";
        selectedOfficerId.value = "";
        proofDiscordTag.value = "";
        notes.value = "";
        await loadData();
        emit("claimed");
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal mengajukan klaim perwira.";
    } finally {
        isSubmitting.value = false;
    }
};

const cancelClaim = async (claimId) => {
    if (!confirm("Apakah Anda yakin ingin membatalkan pengajuan klaim ini?")) return;
    try {
        await axios.delete(`/api/v1/officer-claims/${claimId}`);
        await loadData();
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal membatalkan klaim.";
    }
};

onMounted(() => {
    if (props.show) {
        loadData();
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
    >
        <div class="w-full max-w-lg bg-slate-900 border border-cyan-500/30 rounded-2xl p-6 shadow-2xl text-left space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛡️</span>
                    <div>
                        <h3 class="text-sm font-black font-mono uppercase text-white tracking-wider">
                            Klaim Profil Perwira Dinas
                        </h3>
                        <p class="text-[11px] text-slate-400 font-sans">
                            SASP Tactical Verification & Anti-Impersonation System
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-slate-400 hover:text-white font-mono text-sm px-2 py-1"
                >
                    ✕
                </button>
            </div>

            <!-- Messages -->
            <div
                v-if="errorMessage"
                class="p-3 rounded-xl bg-rose-950/80 border border-rose-500/50 text-rose-200 text-xs font-mono"
            >
                ⚠️ {{ errorMessage }}
            </div>

            <div
                v-if="successMessage"
                class="p-3 rounded-xl bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 text-xs font-mono"
            >
                ✓ {{ successMessage }}
            </div>

            <!-- Existing Active Claim Status Card -->
            <div
                v-if="activeClaim && activeClaim.status === 'pending'"
                class="p-4 rounded-xl bg-slate-950 border border-amber-500/40 space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-black bg-amber-950 text-amber-400 border border-amber-500/40">
                        ⏳ SEDANG DITINJAU ADMIN
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">
                        {{ new Date(activeClaim.created_at).toLocaleDateString() }}
                    </span>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-white font-mono">
                        {{ activeClaim.officer?.officer_name }} ({{ activeClaim.officer?.callsign }})
                    </h4>
                    <p class="text-[11px] text-slate-400 font-sans mt-0.5">
                        Discord Tag: <span class="text-cyan-300 font-mono">{{ activeClaim.proof_discord_tag }}</span>
                    </p>
                    <p v-if="activeClaim.notes" class="text-[11px] text-slate-400 italic mt-0.5">
                        "{{ activeClaim.notes }}"
                    </p>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-white/5">
                    <span class="text-[10px] text-slate-500">
                        Admin akan memverifikasi di saluran Discord SASP.
                    </span>
                    <button
                        @click="cancelClaim(activeClaim.id)"
                        class="text-[10px] font-mono text-rose-400 hover:text-rose-300 underline"
                    >
                        Batalkan Pengajuan
                    </button>
                </div>
            </div>

            <!-- Form: Submit New Claim (if not already pending) -->
            <div v-else-if="!isLoading" class="space-y-3.5">
                <!-- Search & Select Officer -->
                <div class="space-y-1">
                    <label class="block text-xs font-mono uppercase text-slate-300">
                        Pilih Profil Perwira Dinas Anda
                    </label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama perwira, callsign, atau departemen..."
                        class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-none mb-1.5"
                    />
                    <select
                        v-model="selectedOfficerId"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-none"
                    >
                        <option value="">-- Pilih Perwira Dari Roster ({{ filteredOfficers.length }} tersedia) --</option>
                        <option v-for="o in filteredOfficers" :key="o.id" :value="o.id">
                            {{ o.officer_name }} ({{ o.streamer_name }}) • [{{ o.department }} #{{ o.callsign }}]
                        </option>
                    </select>
                </div>

                <!-- Proof Discord Tag -->
                <div class="space-y-1">
                    <label class="block text-xs font-mono uppercase text-slate-300">
                        Discord Tag Perwira <span class="text-cyan-400">*</span>
                    </label>
                    <input
                        v-model="proofDiscordTag"
                        type="text"
                        placeholder="Contoh: username#1234 atau username"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-none"
                    />
                    <p class="text-[10px] text-slate-400">
                        Admin SASP akan menghubungi akun Discord ini untuk memvalidasi kepemilikan stream/perwira.
                    </p>
                </div>

                <!-- Notes / Additional Proof -->
                <div class="space-y-1">
                    <label class="block text-xs font-mono uppercase text-slate-300">
                        Catatan Tambahan (Opsional)
                    </label>
                    <textarea
                        v-model="notes"
                        rows="2"
                        placeholder="Sebutkan waktu patroli atau bukti lain jika diperlukan..."
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-none"
                    ></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono transition"
                    >
                        Tutup
                    </button>
                    <button
                        @click="submitClaim"
                        :disabled="isSubmitting"
                        class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md"
                    >
                        {{ isSubmitting ? "Mengirim..." : "Kirim Pengajuan Klaim" }}
                    </button>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div v-else class="py-8 text-center text-slate-400 font-mono text-xs">
                Memuat data perwira...
            </div>
        </div>
    </div>
</template>
