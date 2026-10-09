<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto font-sans"
        @click.self="close"
    >
        <!-- Modal Container -->
        <div
            class="bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-800 overflow-hidden relative text-slate-100 my-8"
        >
            <!-- Header -->
            <div class="p-6 sm:p-7 pb-3 border-b border-slate-800/80">
                <div class="flex items-center justify-between">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20"
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full bg-blue-400"
                        ></span>
                        Verifikasi & Claim Lisensi
                    </span>
                    <button
                        @click="close"
                        class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <h3
                    class="text-xl sm:text-2xl font-bold text-white mt-3 uppercase font-tactical"
                >
                    Aktivasi & Klaim Lisensi
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Pilih metode klaim pembayaran paket atau pengajuan hak akses
                    Officer.
                </p>

                <!-- Switcher Tipe Pengajuan -->
                <div
                    class="grid grid-cols-2 gap-1 bg-slate-950 p-1 rounded-xl mt-4 border border-slate-800 font-mono text-xs"
                >
                    <button
                        type="button"
                        @click="switchTab('transfer')"
                        :class="[
                            'py-2.5 px-3 font-bold rounded-lg transition flex items-center justify-center gap-1.5',
                            activeTab === 'transfer'
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <span>Bukti Transfer</span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('request')"
                        :class="[
                            'py-2.5 px-3 font-bold rounded-lg transition flex items-center justify-center gap-1.5',
                            activeTab === 'request'
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        <span>Akses Officer</span>
                    </button>
                </div>
            </div>

            <!-- Form Body -->
            <form
                @submit.prevent="submitForm"
                class="p-6 sm:p-7 pt-4 space-y-4"
            >
                <!-- Alert Feedback -->
                <div
                    v-if="successMessage"
                    class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono flex items-center gap-2"
                >
                    <svg
                        class="w-4 h-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span>{{ successMessage }}</span>
                </div>

                <div
                    v-if="errorMessage"
                    class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-mono flex items-center gap-2"
                >
                    <svg
                        class="w-4 h-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Info Target Paket -->
                <div
                    class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-xs font-tactical"
                        >
                            {{ selectedPlanBadge }}
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-mono text-slate-400 uppercase tracking-wider"
                            >
                                {{
                                    activeTab === "transfer"
                                        ? "Target Paket Pembayaran"
                                        : "Tipe Pengajuan"
                                }}
                            </p>
                            <p class="text-xs sm:text-sm font-bold text-white">
                                {{ selectedPlanName }}
                            </p>
                        </div>
                    </div>
                    <span
                        v-if="activeTab === 'transfer'"
                        class="text-xs text-blue-400 font-mono font-semibold cursor-pointer hover:underline"
                        @click="togglePlanSelector"
                    >
                        {{ showPlanSelector ? "Tutup" : "Ganti" }}
                    </span>
                    <span
                        v-else
                        class="text-[11px] text-emerald-400 font-mono font-bold bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20"
                    >
                        Gratis Slot
                    </span>
                </div>

                <!-- Plan Selector Dropdown (Hanya Tampil di Tab Transfer: Komunitas & VIP) -->
                <div
                    v-if="showPlanSelector && activeTab === 'transfer'"
                    class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-xs"
                >
                    <label
                        class="block text-[11px] font-mono text-slate-400 uppercase"
                        >Pilih Paket Layanan Berbayar:</label
                    >
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono"
                    >
                        <button
                            type="button"
                            @click="selectPlan('general')"
                            :class="[
                                'p-2.5 rounded-lg border text-left transition',
                                form.request_type === 'general'
                                    ? 'border-blue-500 bg-blue-500/10 text-white'
                                    : 'border-slate-800 text-slate-400 hover:border-slate-700',
                            ]"
                        >
                            <span class="font-bold block"
                                >Komunitas (Rp 5k)</span
                            >
                            <span class="text-[10px] text-slate-500"
                                >Klip IME Group</span
                            >
                        </button>
                        <button
                            type="button"
                            @click="selectPlan('vip')"
                            :class="[
                                'p-2.5 rounded-lg border text-left transition',
                                form.request_type === 'vip'
                                    ? 'border-blue-500 bg-blue-500/10 text-white'
                                    : 'border-slate-800 text-slate-400 hover:border-slate-700',
                            ]"
                        >
                            <span class="font-bold block">VIP (Rp 10k)</span>
                            <span class="text-[10px] text-slate-500"
                                >Unlimited Clips</span
                            >
                        </button>
                    </div>
                </div>

                <!-- Field Email / Akun -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-300 mb-1.5"
                        >Alamat Email Terdaftar</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="nama@email.com"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-mono"
                    />
                    <p class="text-[11px] text-slate-500 mt-1">
                        Hak akses akan langsung diaktifkan ke akun dengan email
                        ini.
                    </p>
                </div>

                <!-- TAB A: BUKTI TRANSFER (Hanya Komunitas & VIP) -->
                <div v-if="activeTab === 'transfer'" class="space-y-4 pt-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-300 mb-1.5"
                                >ID / Nama Pengirim</label
                            >
                            <input
                                v-model="form.sender_id"
                                type="text"
                                placeholder="Contoh: agus_turnip"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-300 mb-1.5"
                                >Nominal Transfer</label
                            >
                            <input
                                v-model="form.amount"
                                type="text"
                                placeholder="Contoh: Rp 5.000 / Rp 10.000"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-mono"
                            />
                        </div>
                    </div>

                    <!-- Upload File Screenshot Bukti Pembayaran -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-300 mb-1.5"
                            >Screenshot Bukti Pembayaran / Saweran</label
                        >
                        <div
                            class="border-2 border-dashed border-slate-800 hover:border-blue-500/50 rounded-xl p-4 text-center cursor-pointer transition bg-slate-950/60"
                            @click="triggerFileInput"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileUpload"
                            />
                            <svg
                                class="w-7 h-7 text-slate-500 mx-auto mb-1.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            <p class="text-xs text-slate-300 font-medium">
                                {{
                                    fileName
                                        ? fileName
                                        : "Klik untuk upload screenshot pembayaran"
                                }}
                            </p>
                            <p
                                class="text-[11px] text-slate-500 mt-0.5 font-mono"
                            >
                                PNG, JPG, atau WebP (Maks. 5 MB)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- TAB B: PENGAJUAN HAK AKSES OFFICER -->
                <div v-else class="space-y-4 pt-1">
                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-300 mb-1.5"
                            >Link Channel YouTube</label
                        >
                        <input
                            v-model="form.channel_url"
                            type="url"
                            placeholder="https://youtube.com/@channel_anda"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-mono"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-300 mb-1.5"
                            >Informasi & Bukti Identitas Officer</label
                        >
                        <textarea
                            v-model="form.reason"
                            rows="2"
                            placeholder="Tuliskan Nama Officer/Karakter, NRP/Pangkat, unit Kepolisian IME, atau penjelasan pengajuan..."
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-sans"
                        ></textarea>
                    </div>

                    <!-- Upload Screenshot Bukti Officer -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-300 mb-1.5"
                            >Screenshot Bukti Akun Officer (Profile MDT/Website
                            Putih).
                        </label>
                        <div
                            class="border-2 border-dashed border-slate-800 hover:border-blue-500/50 rounded-xl p-4 text-center cursor-pointer transition bg-slate-950/60"
                            @click="triggerFileInput"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileUpload"
                            />
                            <svg
                                class="w-7 h-7 text-slate-500 mx-auto mb-1.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            <p class="text-xs text-slate-300 font-medium">
                                {{
                                    fileName
                                        ? fileName
                                        : "Klik untuk upload screenshot bukti Officer"
                                }}
                            </p>
                            <p
                                class="text-[11px] text-slate-500 mt-0.5 font-mono"
                            >
                                PNG, JPG, atau WebP (Maks. 5 MB)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 disabled:opacity-50 text-white text-xs sm:text-sm font-bold rounded-xl transition font-tactical uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg"
                    >
                        <svg
                            v-if="loading"
                            class="w-4 h-4 animate-spin text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>{{
                            loading
                                ? "Mengirim Pengajuan..."
                                : activeTab === "transfer"
                                  ? "Kirim Bukti Pembayaran"
                                  : "Kirim Pengajuan Officer"
                        }}</span>
                        <svg
                            v-if="!loading"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>
                    </button>
                    <p
                        class="text-center text-[11px] text-slate-500 mt-2.5 font-mono"
                    >
                        Verifikasi manual diproses maksimal 1x24 jam kerja.
                    </p>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialPlan: {
        type: String,
        default: "general", // general, vip, officer
    },
});

const emit = defineEmits(["close", "submitted"]);

const page = usePage();
const activeTab = ref("transfer"); // transfer, request
const loading = ref(false);
const successMessage = ref("");
const errorMessage = ref("");
const showPlanSelector = ref(false);
const fileInput = ref(null);
const fileName = ref("");
const selectedFile = ref(null);

const form = ref({
    email: page.props.auth?.user?.email || "",
    request_type: props.initialPlan,
    sender_id: "",
    amount: "",
    channel_url: "",
    reason: "",
});

const syncTabAndPlan = (plan) => {
    if (plan === "officer") {
        activeTab.value = "request";
        form.value.request_type = "officer";
    } else {
        activeTab.value = "transfer";
        form.value.request_type = plan === "vip" ? "vip" : "general";
    }
};

watch(
    () => props.initialPlan,
    (newPlan) => {
        syncTabAndPlan(newPlan);
    },
    { immediate: true },
);

const switchTab = (tab) => {
    activeTab.value = tab;
    showPlanSelector.value = false;
    if (tab === "transfer") {
        if (form.value.request_type === "officer") {
            form.value.request_type = "general";
        }
    } else if (tab === "request") {
        form.value.request_type = "officer";
    }
};

const selectedPlanName = computed(() => {
    switch (form.value.request_type) {
        case "vip":
            return "VIP Supporter Tier (Rp 10k)";
        case "officer":
            return "Officer";
        case "general":
        default:
            return "Clipper Komunitas IME (Rp 5k)";
    }
});

const selectedPlanBadge = computed(() => {
    switch (form.value.request_type) {
        case "vip":
            return "VIP";
        case "officer":
            return "POLICE";
        case "general":
        default:
            return "BETA";
    }
});

const close = () => {
    emit("close");
};

const togglePlanSelector = () => {
    showPlanSelector.value = !showPlanSelector.value;
};

const selectPlan = (type) => {
    form.value.request_type = type;
    showPlanSelector.value = false;
};

const triggerFileInput = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            errorMessage.value = "Ukuran file terlalu besar. Maksimal 5 MB.";
            return;
        }
        selectedFile.value = file;
        fileName.value = file.name;
        errorMessage.value = "";
    }
};

const submitForm = async () => {
    loading.value = true;
    successMessage.value = "";
    errorMessage.value = "";

    try {
        const formData = new FormData();
        formData.append("email", form.value.email);
        formData.append("request_type", form.value.request_type);
        formData.append("channel_url", form.value.channel_url || "");

        let customReason = form.value.reason || "";
        if (activeTab.value === "transfer") {
            customReason = `[Transfer Bagi-Bagi] Pengirim: ${form.value.sender_id || "-"}, Nominal: ${form.value.amount || "-"}. ${customReason}`;
        } else {
            customReason = `[Pengajuan Akses Officer] ${customReason}`;
        }
        formData.append("reason", customReason);

        if (selectedFile.value) {
            formData.append("proof_image", selectedFile.value);
        }

        const response = await axios.post(
            "/api/v1/clipper-requests",
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            },
        );

        successMessage.value =
            response.data.message || "Pengajuan klaim berhasil dikirim!";
        setTimeout(() => {
            emit("submitted");
            close();
        }, 1800);
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message ||
            "Gagal mengirim pengajuan. Pastikan data diisi dengan benar.";
    } finally {
        loading.value = false;
    }
};
</script>
