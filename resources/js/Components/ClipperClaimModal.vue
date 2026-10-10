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
            <div class="p-6 sm:p-7 pb-4 border-b border-slate-800/80">
                <div class="flex items-center justify-between">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
                        ></span>
                        Beta Tester Access (FREE)
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
                    class="text-xl sm:text-2xl font-bold text-white mt-3 uppercase font-tactical tracking-tight"
                >
                    Pengajuan Hak Akses Clipper
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Pendaftaran resmi Clipper Komunitas IME. Seluruh pengajuan
                    bersifat
                    <strong class="text-emerald-400">GRATIS</strong> selama masa
                    Uji Coba Beta.
                </p>

                <!-- Status Banner Pembayaran & Officer -->
                <div class="grid grid-cols-2 gap-2 mt-4 font-mono text-[11px]">
                    <div
                        class="p-2.5 rounded-xl bg-blue-950/50 border border-blue-800/60 text-blue-300 flex items-center gap-2"
                    >
                        <svg
                            class="w-4 h-4 text-blue-400 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                        <span>Hak Akses: <strong>GRATIS</strong></span>
                    </div>

                    <div
                        class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 flex items-center gap-2 opacity-75"
                        title="Fitur Pembayaran Berbayar Saat Ini Dinonaktifkan"
                    >
                        <svg
                            class="w-4 h-4 text-amber-400 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                        <span
                            >Pembayaran:
                            <strong class="text-amber-400"
                                >NONAKTIF</strong
                            ></span
                        >
                    </div>
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

                <!-- Info Target Akses -->
                <div
                    class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-xs font-tactical shrink-0"
                        >
                            BETA
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-mono text-slate-400 uppercase tracking-wider"
                            >
                                Peran Hak Akses yang Diajukan
                            </p>
                            <p class="text-xs sm:text-sm font-bold text-white">
                                CLIPPER IME ROLEPLAY
                            </p>
                        </div>
                    </div>
                    <span
                        class="text-[11px] text-emerald-400 font-mono font-bold bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-500/20 shrink-0"
                    >
                        GRATIS
                    </span>
                </div>

                <!-- Field Email / Akun -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-300 mb-1.5"
                    >
                        Alamat Email Terdaftar
                        <span class="text-rose-400">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="nama@email.com"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-mono"
                    />
                    <p class="text-[11px] text-slate-500 mt-1">
                        Hak akses Clipper akan diaktifkan ke akun pengguna
                        dengan email ini setelah disetujui.
                    </p>
                </div>

                <!-- Link Channel YouTube / Media Social -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-300 mb-1.5"
                    >
                        Link Channel YouTube / Portofolio Klip
                    </label>
                    <input
                        v-model="form.channel_url"
                        type="url"
                        placeholder="https://youtube.com/@channel_anda atau link sampel klip..."
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-mono"
                    />
                </div>

                <!-- Catatan / Alasan Pengajuan -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-300 mb-1.5"
                    >
                        Alasan / Keterangan Pengajuan Akses Clipper
                    </label>
                    <textarea
                        v-model="form.reason"
                        rows="3"
                        placeholder="Tuliskan keterangan singkat keikutsertaan Anda (misal: Editor komunitas IME, Clipper channel YouTube, atau permohonan akses Beta)..."
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-500 font-sans"
                    ></textarea>
                </div>

                <!-- Upload Screenshot Bukti / Sampel (Opsional) -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-300 mb-1.5"
                    >
                        Screenshot Bukti Channel / Portofolio (Opsional)
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
                                    : "Klik untuk upload screenshot channel / bukti keaktifan"
                            }}
                        </p>
                        <p class="text-[11px] text-slate-500 mt-0.5 font-mono">
                            PNG, JPG, atau WebP (Maks. 5 MB)
                        </p>
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
                                : "Kirim Pengajuan Akses Clipper"
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
                        Notifikasi pengajuan akan langsung diteruskan ke Admin
                        via Discord.
                    </p>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialPlan: {
        type: String,
        default: "creator",
    },
});

const emit = defineEmits(["close", "submitted"]);

const page = usePage();
const loading = ref(false);
const successMessage = ref("");
const errorMessage = ref("");
const fileInput = ref(null);
const fileName = ref("");
const selectedFile = ref(null);

const form = ref({
    email: page.props.auth?.user?.email || "",
    request_type: "creator",
    channel_url: "",
    reason: "",
});

const close = () => {
    emit("close");
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
        formData.append("request_type", "creator");
        formData.append("channel_url", form.value.channel_url || "");

        const customReason = `[Pengajuan Access Clipper Beta] ${form.value.reason || "Permohonan akses Clipper Komunitas."}`;
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
            response.data.message ||
            "Pengajuan akses Clipper berhasil dikirim!";
        setTimeout(() => {
            emit("submitted");
            close();
        }, 1800);
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message ||
            "Gagal mengirim pengajuan. Pastikan email diisi dengan benar.";
    } finally {
        loading.value = false;
    }
};
</script>
