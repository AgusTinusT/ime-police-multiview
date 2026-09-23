<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";

import iconFeedback from "@/Components/Icons/report-svgrepo-com.svg";
import iconRadio from "@/Components/Icons/radio-signal-svgrepo-com.svg";
import iconQuickAdd from "@/Components/Icons/button-plus-svgrepo-com.svg";
import iconEdit from "@/Components/Icons/edit-2-svgrepo-com.svg";
import iconBug from "@/Components/Icons/bug-svgrepo-com.svg";
import iconSend from "@/Components/Icons/send-svgrepo-com.svg";
import iconRefresh from "@/Components/Icons/refresh-cw-svgrepo-com.svg";

const props = defineProps({
    prefillType: {
        type: String,
        default: "BUG_REPORT",
    },
});

// Feedback Form reactive state matching feeback_reverensi.html
const feedbackForm = ref({
    type: props.prefillType || "BUG_REPORT",
    title: "",
    typo_wrong: "",
    typo_correct: "",
    message: "",
    image_url: "",
    sender_name: "",
    email: "",
    handle_or_url: "",
    related_url: "",
    officer_name: "",
    department: "LSPD",
});

const isSubmitting = ref(false);
const successMessage = ref("");
const errorMessage = ref("");

// Category Selection Function
const selectType = (type) => {
    feedbackForm.value.type = type;
};

// Dynamic Labels based on selected category
const titleLabel = computed(() => {
    if (feedbackForm.value.type === 'DATA_CORRECTION') {
        return 'Halaman atau Dokumen yang Memiliki Typo';
    } else if (feedbackForm.value.type === 'CHANNEL_REQUEST') {
        return 'Nama Fitur / Usulan Streamer Baru';
    } else if (feedbackForm.value.type === 'OTHER') {
        return 'Subjek / Judul Masukan';
    }
    return 'Judul Kendala / Bug';
});

const titlePlaceholder = computed(() => {
    if (feedbackForm.value.type === 'DATA_CORRECTION') {
        return 'Contoh: Typo pada dokumen SOP-MV-01 di halaman FAQ';
    } else if (feedbackForm.value.type === 'CHANNEL_REQUEST') {
        return 'Contoh: Penambahan Streamer Ofc. Budi (1-ADAM-12)';
    } else if (feedbackForm.value.type === 'OTHER') {
        return 'Contoh: Pertanyaan seputar integrasi API Discord';
    }
    return 'Contoh: Tombol mute video tidak merespons di layar Multiview';
});

const descriptionLabel = computed(() => {
    if (feedbackForm.value.type === 'DATA_CORRECTION') {
        return 'Konteks Paragraf / Keterangan Tambahan';
    } else if (feedbackForm.value.type === 'CHANNEL_REQUEST') {
        return 'Bagaimana fitur / streamer ini mempermudah operasi dispatch?';
    }
    return 'Deskripsi & Langkah Kejadian';
});

const descriptionPlaceholder = computed(() => {
    if (feedbackForm.value.type === 'DATA_CORRECTION') {
        return 'Jelaskan bagian paragraf atau teks yang memerlukan perbaikan...';
    } else if (feedbackForm.value.type === 'CHANNEL_REQUEST') {
        return 'Jelaskan detail usulan fitur atau alokasi siaran live streamer...';
    }
    return 'Jelaskan langkah demi langkah sampai kendala ini terjadi...';
});

// Submit Feedback Handler
const submitFeedbackForm = async () => {
    if (!feedbackForm.value.message || feedbackForm.value.message.trim() === "") {
        alert("Mohon isi deskripsi / detail laporan Anda.");
        return;
    }

    isSubmitting.value = true;
    successMessage.value = "";
    errorMessage.value = "";

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/api/v1/feedback", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(feedbackForm.value),
        });

        const data = await res.json();
        if (res.ok && data.status === "success") {
            successMessage.value =
                data.message ||
                "Masukan Anda berhasil dikirim ke Discord Dispatcher!";
            feedbackForm.value = {
                type: "BUG_REPORT",
                title: "",
                typo_wrong: "",
                typo_correct: "",
                message: "",
                image_url: "",
                sender_name: "",
                email: "",
                handle_or_url: "",
                related_url: "",
                officer_name: "",
                department: "LSPD",
            };
        } else {
            errorMessage.value =
                data.message || "Gagal mengirim laporan. Silakan coba lagi.";
        }
    } catch (e) {
        errorMessage.value =
            "Terjadi kesalahan jaringan saat mengirim laporan.";
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <TacticalLayout>
        <Head title="Pusat Laporan & Feedback — IME Police Terminal" />

        <!-- Ambient background glow spots -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="absolute -top-32 left-1/4 -translate-x-1/2 w-[700px] h-[400px] bg-blue-600/10 blur-[140px] rounded-full"></div>
            <div class="absolute top-[35%] -right-28 w-[500px] h-[500px] bg-blue-800/15 blur-[130px] rounded-full"></div>
            <div class="absolute bottom-10 left-1/3 w-[600px] h-[350px] bg-slate-900/40 blur-[150px] rounded-full"></div>
        </div>

        <main class="relative z-10 max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-8">
            
            <!-- Hero Header Section -->
            <div class="text-center space-y-3">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-blue-950/80 border border-blue-800/60 text-blue-400 font-mono text-xs font-bold shadow-sm">
                    <img :src="iconRadio" class="w-3.5 h-3.5 invert opacity-90" alt="" />
                    <span>DUKUNGAN PENGGUNA & DISPATCH</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-tactical font-black text-slate-100 uppercase tracking-wider">
                    KIRIM LAPORAN ATAU MASUKAN
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto leading-relaxed font-sans">
                    Menemukan bug, kesalahan teks (typo), atau punya usulan fitur baru? Bantu kami meningkatkan platform dengan menyampaikan detailnya langsung ke Command Center.
                </p>
            </div>

            <!-- Toast Notifications -->
            <div
                v-if="successMessage"
                class="bg-emerald-950/90 border border-emerald-500/50 rounded-2xl p-4 text-xs text-emerald-300 font-mono flex items-start gap-3 shadow-lg"
            >
                <span class="text-lg leading-none">✓</span>
                <div>
                    <div class="font-bold text-emerald-200 mb-0.5">
                        Laporan Berhasil Terkirim!
                    </div>
                    <div>{{ successMessage }}</div>
                </div>
            </div>

            <div
                v-if="errorMessage"
                class="bg-rose-950/90 border border-rose-500/50 rounded-2xl p-4 text-xs text-rose-300 font-mono flex items-start gap-3 shadow-lg"
            >
                <span class="text-lg leading-none">✕</span>
                <div>
                    <div class="font-bold text-rose-200 mb-0.5">
                        Gagal Mengirim Laporan
                    </div>
                    <div>{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Main Form Card Container (Sleek Dark Surface) -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl space-y-8">
                
                <!-- 1. Category Selector (4 Interactive Cards) -->
                <div>
                    <label class="block text-xs font-mono font-bold uppercase tracking-wider text-slate-400 mb-3">
                        1. Pilih Jenis Masukan <span class="text-rose-400">*</span>
                    </label>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <!-- Option 1: Bug / Error -->
                        <button
                            type="button"
                            @click="selectType('BUG_REPORT')"
                            :class="[
                                'p-4 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1.5',
                                feedbackForm.type === 'BUG_REPORT'
                                    ? 'bg-rose-950/80 border-rose-500/80 text-white shadow-lg shadow-rose-950/50 ring-1 ring-rose-500/30'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                            ]"
                        >
                            <img :src="iconBug" class="w-6 h-6 invert" alt="" />
                            <span class="text-xs font-bold font-tactical uppercase tracking-wider">Bug / Error</span>
                            <span class="text-[10px] text-slate-400 font-mono">Sistem bermasalah</span>
                        </button>

                        <!-- Option 2: Typo / Text Correction -->
                        <button
                            type="button"
                            @click="selectType('DATA_CORRECTION')"
                            :class="[
                                'p-4 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1.5',
                                feedbackForm.type === 'DATA_CORRECTION'
                                    ? 'bg-amber-950/80 border-amber-500/80 text-white shadow-lg shadow-amber-950/50 ring-1 ring-amber-500/30'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                            ]"
                        >
                            <img :src="iconEdit" class="w-6 h-6 invert" alt="" />
                            <span class="text-xs font-bold font-tactical uppercase tracking-wider">Typo / Teks</span>
                            <span class="text-[10px] text-slate-400 font-mono">Koreksi kata</span>
                        </button>

                        <!-- Option 3: Feature / Streamer Idea -->
                        <button
                            type="button"
                            @click="selectType('CHANNEL_REQUEST')"
                            :class="[
                                'p-4 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1.5',
                                feedbackForm.type === 'CHANNEL_REQUEST'
                                    ? 'bg-blue-950/80 border-blue-500/80 text-white shadow-lg shadow-blue-950/50 ring-1 ring-blue-500/30'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                            ]"
                        >
                            <img :src="iconQuickAdd" class="w-6 h-6 invert" alt="" />
                            <span class="text-xs font-bold font-tactical uppercase tracking-wider">Ide Fitur</span>
                            <span class="text-[10px] text-slate-400 font-mono">Usulan streamer</span>
                        </button>

                        <!-- Option 4: General Feedback -->
                        <button
                            type="button"
                            @click="selectType('OTHER')"
                            :class="[
                                'p-4 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1.5',
                                feedbackForm.type === 'OTHER'
                                    ? 'bg-purple-950/80 border-purple-500/80 text-white shadow-lg shadow-purple-950/50 ring-1 ring-purple-500/30'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                            ]"
                        >
                            <img :src="iconFeedback" class="w-6 h-6 invert" alt="" />
                            <span class="text-xs font-bold font-tactical uppercase tracking-wider">Lainnya</span>
                            <span class="text-[10px] text-slate-400 font-mono">Kritik & kesan</span>
                        </button>
                    </div>
                </div>

                <!-- Form Inputs -->
                <form @submit.prevent="submitFeedbackForm" class="space-y-6">
                    
                    <!-- Report Title -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            {{ titleLabel }} <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="feedbackForm.title"
                            type="text"
                            required
                            :placeholder="titlePlaceholder"
                            class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs sm:text-sm focus:outline-none focus:border-blue-500 transition"
                        />
                    </div>

                    <!-- Dynamic Typo / Text Correction Fields (Shown when Typo category is active) -->
                    <div v-if="feedbackForm.type === 'DATA_CORRECTION'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Teks yang Salah (Saat Ini)
                            </label>
                            <input
                                v-model="feedbackForm.typo_wrong"
                                type="text"
                                placeholder="Contoh: 'Pengamban Patroli'"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs focus:outline-none focus:border-blue-500 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Usulan Koreksi yang Benar
                            </label>
                            <input
                                v-model="feedbackForm.typo_correct"
                                type="text"
                                placeholder="Contoh: 'Pengembangan Patroli'"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs focus:outline-none focus:border-blue-500 font-mono"
                            />
                        </div>
                    </div>

                    <!-- Dynamic Officer / Streamer Fields (Shown when Feature/Streamer category is active) -->
                    <div v-if="feedbackForm.type === 'CHANNEL_REQUEST'" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Nama Karakter / Callsign Polisi
                            </label>
                            <input
                                v-model="feedbackForm.officer_name"
                                type="text"
                                placeholder="Ofc. Budi / 1-ADAM-12"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs focus:outline-none focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Kesatuan / Department
                            </label>
                            <select
                                v-model="feedbackForm.department"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs focus:outline-none focus:border-blue-500 font-mono"
                            >
                                <option value="LSPD">LSPD (Police)</option>
                                <option value="BCSO">BCSO (Sheriff)</option>
                                <option value="SASP">SASP (State Police)</option>
                                <option value="SAPR">SAPR (Park Rangers)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Main Description Textarea -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            {{ descriptionLabel }} <span class="text-rose-400">*</span>
                        </label>
                        <textarea
                            v-model="feedbackForm.message"
                            required
                            rows="4"
                            :placeholder="descriptionPlaceholder"
                            class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs sm:text-sm focus:outline-none focus:border-blue-500 transition leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Screenshot / Image Link Input Field -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Tangkapan Layar / Screenshot URL (Opsional)
                        </label>
                        <div class="space-y-2">
                            <input
                                v-model="feedbackForm.image_url"
                                type="url"
                                placeholder="Tempelkan URL Gambar (contoh: https://i.imgur.com/example.png atau Discord attachment link)..."
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs font-mono focus:outline-none focus:border-blue-500"
                            />
                            <div class="border border-dashed border-slate-800 rounded-2xl p-4 text-center bg-slate-950/40 text-xs text-slate-400 space-y-1">
                                <div class="flex items-center justify-center gap-1.5 text-blue-400 font-mono text-[11px] font-semibold">
                                    <span>🤖 INTEGRASI GAMBAR DISCORD BOT AUTOMATIC</span>
                                </div>
                                <p class="text-[11px] text-slate-400">
                                    Discord Webhook otomatis mendeteksi URL gambar (Imgur, Discord Attachment, Gyazo, dsb.) dan menampilkan gambar pratinjau utuh secara langsung di channel Discord Dispatcher!
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Related Links -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Nama / Email Pengirim (Untuk Notifikasi Perbaikan)
                            </label>
                            <input
                                v-model="feedbackForm.sender_name"
                                type="text"
                                placeholder="Contoh: Citizen Ray / raymond@gmail.com"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs focus:outline-none focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Tautan URL / YouTube Terkait (Jika Ada)
                            </label>
                            <input
                                v-model="feedbackForm.handle_or_url"
                                type="url"
                                placeholder="https://youtube.com/@handle atau link halaman terkait"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder:text-slate-600 text-xs font-mono focus:outline-none focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Submit Action Footer -->
                    <div class="pt-2 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <span class="text-[11px] text-slate-500 font-mono">
                            ⚡ Terhubung langsung ke Webhook Dispatcher
                        </span>

                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-mono font-bold uppercase tracking-wider transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2"
                        >
                            <img
                                v-if="isSubmitting"
                                :src="iconRefresh"
                                class="w-4 h-4 animate-spin invert"
                                alt=""
                            />
                            <img
                                v-else
                                :src="iconSend"
                                class="w-4 h-4 invert"
                                alt=""
                            />
                            <span>{{ isSubmitting ? "MENGIRIM LAPORAN..." : "KIRIM MASUKAN SEKARANG" }}</span>
                        </button>
                    </div>
                </form>

            </div>

            <!-- Bottom Info Banner -->
            <div class="flex items-center gap-3 p-4 rounded-2xl bg-slate-900 border border-slate-800 text-xs text-slate-400 shadow-sm">
                <div class="p-2 rounded-xl bg-blue-950 text-blue-400 border border-blue-800/60 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="leading-relaxed">
                    Semua laporan diproses secara otomatis oleh sistem Dispatcher. Bug kritis dan usulan streamer akan langsung ditinjau oleh Komando IME Roleplay.
                </span>
            </div>

        </main>
    </TacticalLayout>
</template>
