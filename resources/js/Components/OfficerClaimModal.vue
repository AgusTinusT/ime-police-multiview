<script setup>
import { ref, onMounted, computed, watch } from "vue";
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

// Mode: 'youtube' (primary) | 'manual' (fallback)
const claimMode = ref("youtube");

// Form state
const selectedOfficerId = ref("");
const proofDiscordTag = ref("");
const notes = ref("");
const isSubmitting = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

// YouTube token verification state
const issuedToken = ref("");
const activeYouTubeClaim = ref(null);
const isCheckingYouTube = ref(false);
const copiedToken = ref(false);

const activeClaim = computed(() => {
    return myClaims.value.find((c) => c.status === "pending") || null;
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

        // Check if there is an active pending YouTube claim
        const pendingYt = myClaims.value.find((c) => c.status === "pending" && c.verification_method === "youtube_token");
        if (pendingYt) {
            activeYouTubeClaim.value = pendingYt;
            claimMode.value = "youtube";
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal memuat data perwira.";
    } finally {
        isLoading.value = false;
    }
};

// 1. YouTube automated claim: generate secret token
const requestYouTubeToken = async () => {
    if (!selectedOfficerId.value) {
        errorMessage.value = "Silakan pilih profil perwira yang ingin Anda klaim.";
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const res = await axios.post("/api/v1/officer-claims/youtube", {
            officer_id: selectedOfficerId.value,
        });

        issuedToken.value = res.data.token;
        activeYouTubeClaim.value = res.data.claim;
        successMessage.value = res.data.message || "Token verifikasi berhasil dibuat.";
        await loadData();
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal membuat token verifikasi YouTube.";
    } finally {
        isSubmitting.value = false;
    }
};

// 2. YouTube automated claim: verify token in description
const checkYouTubeVerification = async () => {
    const claim = activeYouTubeClaim.value || activeClaim.value;
    if (!claim) return;

    isCheckingYouTube.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const res = await axios.post(`/api/v1/officer-claims/${claim.id}/verify-youtube`, {
            token: issuedToken.value || undefined,
        });

        successMessage.value = res.data.message || "Verifikasi berhasil! Profil perwira telah terhubung.";
        activeYouTubeClaim.value = null;
        issuedToken.value = "";
        await loadData();
        emit("claimed");
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Pengecekan gagal. Pastikan token telah tersimpan di deskripsi channel.";
    } finally {
        isCheckingYouTube.value = false;
    }
};

// Copy token helper
const copyToken = async () => {
    if (!issuedToken.value) return;
    try {
        await navigator.clipboard.writeText(issuedToken.value);
        copiedToken.value = true;
        setTimeout(() => {
            copiedToken.value = false;
        }, 2500);
    } catch {
        // Fallback
    }
};

// 3. Manual Admin claim
const submitManualClaim = async () => {
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
        activeYouTubeClaim.value = null;
        issuedToken.value = "";
        await loadData();
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Gagal membatalkan klaim.";
    }
};

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            loadData();
        }
    }
);

onMounted(() => {
    if (props.show) {
        loadData();
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto font-sans"
        >
            <div class="w-full max-w-lg my-auto bg-slate-900 border border-cyan-500/30 rounded-2xl p-6 shadow-2xl text-left space-y-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛡️</span>
                    <div>
                        <h3 class="text-sm font-black font-mono uppercase text-white tracking-wider">
                            Klaim Kepemilikan Profil Perwira
                        </h3>
                        <p class="text-[11px] text-slate-400 font-sans">
                            SASP Self-Service Ownership & Verification (PRD v3)
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

            <!-- Existing Pending YouTube Token Claim Waiting for Verification -->
            <div
                v-if="activeYouTubeClaim && activeYouTubeClaim.status === 'pending'"
                class="p-4 rounded-xl bg-slate-950 border border-cyan-500/40 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-mono font-black bg-cyan-950 text-cyan-400 border border-cyan-500/40">
                        ⚡ VERIFIKASI YOUTUBE AKTIF
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">
                        Berlaku 24 Jam
                    </span>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-white font-mono">
                        {{ activeYouTubeClaim.officer?.officer_name }} ({{ activeYouTubeClaim.officer?.callsign }})
                    </h4>
                    <p class="text-[11px] text-slate-400 font-sans mt-0.5">
                        Channel ID: <span class="text-cyan-300 font-mono">{{ activeYouTubeClaim.officer?.channel_id }}</span>
                    </p>
                </div>

                <!-- Token Display Box -->
                <div v-if="issuedToken" class="p-3 bg-slate-900 border border-cyan-500/30 rounded-xl space-y-1.5">
                    <div class="text-[10px] uppercase font-mono text-cyan-400 font-bold">
                        Kode Token Anda (Tempel di Deskripsi YouTube):
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <code class="text-xs font-mono text-amber-300 font-bold tracking-wider select-all break-all">
                            {{ issuedToken }}
                        </code>
                        <button
                            @click="copyToken"
                            class="px-2.5 py-1 rounded-lg bg-cyan-950 hover:bg-cyan-900 text-cyan-300 border border-cyan-700 text-[10px] font-mono font-bold shrink-0 transition"
                        >
                            {{ copiedToken ? "✓ Tersalin!" : "Salin" }}
                        </button>
                    </div>
                </div>

                <div class="text-[11px] text-slate-300 space-y-1 font-sans bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                    <p class="font-bold text-white text-[11px]">Instruksi Langkah Mudah:</p>
                    <ol class="list-decimal list-inside space-y-0.5 text-[11px] text-slate-300">
                        <li>Buka channel YouTube Anda (atau deskripsi siaran live aktif).</li>
                        <li>Tempelkan kode token di atas pada bagian <strong>Tentang (About)</strong> atau Deskripsi.</li>
                        <li>Simpan perubahan di YouTube, lalu tekan tombol <strong>Periksa Sekarang</strong> di bawah.</li>
                    </ol>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-white/5">
                    <button
                        @click="cancelClaim(activeYouTubeClaim.id)"
                        class="text-[10px] font-mono text-rose-400 hover:text-rose-300 underline"
                    >
                        Batalkan Klaim
                    </button>
                    <button
                        @click="checkYouTubeVerification"
                        :disabled="isCheckingYouTube"
                        class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md flex items-center gap-1.5"
                    >
                        <svg v-if="isCheckingYouTube" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isCheckingYouTube ? "Memeriksa..." : "Periksa Sekarang →" }}</span>
                    </button>
                </div>
            </div>

            <!-- Existing Pending Manual Claim -->
            <div
                v-else-if="activeClaim && activeClaim.status === 'pending' && activeClaim.verification_method === 'manual_admin'"
                class="p-4 rounded-xl bg-slate-950 border border-amber-500/40 space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-mono font-black bg-amber-950 text-amber-400 border border-amber-500/40">
                        ⏳ REVIEW MANUAL ADMIN
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
            <div v-else-if="!isLoading" class="space-y-4">
                <!-- Method Selection Tabs -->
                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800 text-xs font-mono">
                    <button
                        type="button"
                        @click="claimMode = 'youtube'"
                        class="py-2 px-3 rounded-lg font-bold transition flex items-center justify-center gap-1.5"
                        :class="claimMode === 'youtube' ? 'bg-cyan-600 text-black shadow-sm' : 'text-slate-400 hover:text-white'"
                    >
                        <span>⚡ Token YouTube</span>
                        <span class="text-[9px] uppercase px-1 py-0.2 rounded-sm bg-black/30 font-mono">Utama</span>
                    </button>
                    <button
                        type="button"
                        @click="claimMode = 'manual'"
                        class="py-2 px-3 rounded-lg font-bold transition flex items-center justify-center gap-1.5"
                        :class="claimMode === 'manual' ? 'bg-cyan-600 text-black shadow-sm' : 'text-slate-400 hover:text-white'"
                    >
                        <span>📝 Review Admin</span>
                        <span class="text-[9px] uppercase px-1 py-0.2 rounded-sm bg-black/30 font-mono">Cadangan</span>
                    </button>
                </div>

                <!-- Search & Select Officer -->
                <div class="space-y-1">
                    <label class="block text-xs font-mono uppercase text-slate-300">
                        Pilih Profil Perwira Dinas Anda
                    </label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama perwira, callsign, atau departemen..."
                        class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden mb-1.5"
                    />
                    <select
                        v-model="selectedOfficerId"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                    >
                        <option value="">-- Pilih Perwira Dari Roster ({{ filteredOfficers.length }} tersedia) --</option>
                        <option v-for="o in filteredOfficers" :key="o.id" :value="o.id">
                            {{ o.officer_name }} ({{ o.streamer_name }}) • [{{ o.department }} #{{ o.callsign }}]
                        </option>
                    </select>
                </div>

                <!-- MODE 1: YOUTUBE VERIFICATION EXPLANATION & ACTION -->
                <div v-if="claimMode === 'youtube'" class="space-y-3 p-3.5 bg-slate-950/60 rounded-xl border border-cyan-500/20">
                    <div class="text-[11px] text-slate-300 space-y-1.5 leading-relaxed font-sans">
                        <p class="font-bold text-cyan-400 flex items-center gap-1.5">
                            <span>⚡ Cara Kerja Verifikasi Otomatis:</span>
                        </p>
                        <p>
                            Sistem akan menerbitkan kode token acak rahasia. Anda cukup menempelkannya sementara di deskripsi channel YouTube yang terdaftar. Sistem akan memvalidasi secara otomatis tanpa menunggu approval admin.
                        </p>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-white/5">
                        <button
                            @click="$emit('close')"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono transition"
                        >
                            Tutup
                        </button>
                        <button
                            @click="requestYouTubeToken"
                            :disabled="isSubmitting"
                            class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md"
                        >
                            {{ isSubmitting ? "Menerbitkan Token..." : "Dapatkan Token Verifikasi →" }}
                        </button>
                    </div>
                </div>

                <!-- MODE 2: MANUAL ADMIN FORM -->
                <div v-else class="space-y-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-mono uppercase text-slate-300">
                            Discord Tag Perwira <span class="text-cyan-400">*</span>
                        </label>
                        <input
                            v-model="proofDiscordTag"
                            type="text"
                            placeholder="Contoh: username#1234 atau username"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        />
                        <p class="text-[10px] text-slate-400 font-sans">
                            Admin SASP akan menghubungi Discord ini untuk memvalidasi identitas perwira jika channel tidak dapat diakses.
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-mono uppercase text-slate-300">
                            Catatan / Keterangan (Opsional)
                        </label>
                        <textarea
                            v-model="notes"
                            rows="2"
                            placeholder="Alasan memakai verifikasi manual..."
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs font-mono text-white focus:border-cyan-500 outline-hidden"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                        <button
                            @click="$emit('close')"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono transition"
                        >
                            Tutup
                        </button>
                        <button
                            @click="submitManualClaim"
                            :disabled="isSubmitting"
                            class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-black font-mono font-bold text-xs uppercase tracking-wider transition shadow-md"
                        >
                            {{ isSubmitting ? "Mengirim..." : "Kirim Review Manual" }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div v-else class="py-8 text-center text-slate-400 font-mono text-xs">
                Memuat data perwira...
            </div>
        </div>
        </div>
    </Teleport>
</template>
