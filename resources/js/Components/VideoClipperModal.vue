<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4 transition-all">
        <div class="relative w-full max-w-3xl rounded-2xl border border-red-500/30 bg-slate-950/95 shadow-2xl shadow-red-950/40 text-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-red-900/40 bg-red-950/30 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500/20 text-red-400 border border-red-500/40 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 0L5 5m4.121 4.121L5 19" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold tracking-wide text-white flex items-center gap-2">
                            Tactical Stream Trimmer
                        </h3>
                        <p class="text-xs text-slate-400">Potong segmen video YouTube (Maksimal 10 Menit)</p>
                    </div>
                </div>
                <button @click="closeModal" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Tab Buttons -->
            <div class="flex border-b border-slate-800 bg-slate-900/50 px-6">
                <button 
                    @click="activeTab = 'create'" 
                    class="py-3 px-4 font-medium text-sm border-b-2 transition flex items-center gap-2"
                    :class="activeTab === 'create' ? 'border-red-500 text-red-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Potong Video Baru
                </button>
                <button 
                    @click="activeTab = 'history'; fetchClips();" 
                    class="py-3 px-4 font-medium text-sm border-b-2 transition flex items-center gap-2"
                    :class="activeTab === 'history' ? 'border-red-500 text-red-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Daftar Klip Saya
                    <span v-if="clipsList.length" class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-slate-800 text-slate-300 font-mono">
                        {{ clipsList.length }}
                    </span>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto flex-1 space-y-5">
                
                <!-- TAB 1: FORM POTONG VIDEO -->
                <div v-if="activeTab === 'create'" class="space-y-4">
                    <!-- Notification Banner -->
                    <div v-if="formError" class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ formError }}</span>
                    </div>

                    <div v-if="successMessage" class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ successMessage }}</span>
                    </div>

                    <!-- YouTube URL Input -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                            URL Video / Live Stream YouTube <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                v-model="form.youtube_url"
                                type="text"
                                placeholder="https://www.youtube.com/watch?v=..."
                                class="w-full rounded-xl bg-slate-900 border border-slate-700/80 px-4 py-2.5 text-sm text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition placeholder:text-slate-500 font-mono"
                            />
                        </div>
                    </div>

                    <!-- Judul Klip (Optional) -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                            Judul Catatan Klip (Opsional)
                        </label>
                        <input 
                            v-model="form.title"
                            type="text"
                            placeholder="Contoh: Kejadian Kamera 1 Menit 10"
                            class="w-full rounded-xl bg-slate-900 border border-slate-700/80 px-4 py-2.5 text-sm text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition placeholder:text-slate-500"
                        />
                    </div>

                    <!-- Timestamp Inputs (Start & End) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                Waktu Mulai (Start Time) <span class="text-red-400">*</span>
                            </label>
                            <input 
                                v-model="form.start_time"
                                type="text"
                                placeholder="00:01:30 atau 90"
                                class="w-full rounded-xl bg-slate-900 border border-slate-700/80 px-4 py-2.5 text-sm text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition font-mono placeholder:text-slate-500"
                            />
                            <p class="text-[11px] text-slate-500 mt-1">Format: Jam:Menit:Detik atau Menit:Detik</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                Waktu Selesai (End Time) <span class="text-red-400">*</span>
                            </label>
                            <input 
                                v-model="form.end_time"
                                type="text"
                                placeholder="00:06:30 atau 390"
                                class="w-full rounded-xl bg-slate-900 border border-slate-700/80 px-4 py-2.5 text-sm text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition font-mono placeholder:text-slate-500"
                            />
                            <p class="text-[11px] text-slate-500 mt-1">Format: Jam:Menit:Detik atau Menit:Detik</p>
                        </div>
                    </div>

                    <!-- Duration Badge Counter -->
                    <div class="rounded-xl border p-4 flex items-center justify-between"
                         :class="isDurationValid ? 'bg-emerald-950/20 border-emerald-500/30 text-emerald-300' : 'bg-red-950/20 border-red-500/30 text-red-300'">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs font-medium">Estimasi Durasi Potongan:</span>
                        </div>
                        <div class="text-sm font-bold font-mono">
                            {{ formattedDuration }}
                            <span v-if="calculatedSeconds > 600" class="text-xs block text-red-400 font-normal">
                                (Melebihi batas max 10 menit!)
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button 
                            @click="submitTrim"
                            :disabled="isSubmitting || !isDurationValid"
                            class="w-full py-3 px-6 rounded-xl font-bold text-sm tracking-wide bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white shadow-lg shadow-red-900/30 border border-red-500/40 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
                        >
                            <svg v-if="isSubmitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isSubmitting ? 'Memproses Antrean Klip...' : 'Potong & Simpan Klip Video' }}</span>
                        </button>
                    </div>
                </div>

                <!-- TAB 2: DAFTAR KLIP SAYA -->
                <div v-else-if="activeTab === 'history'" class="space-y-4">
                    <div v-if="isLoadingClips" class="py-12 text-center text-slate-400 text-xs">
                        <svg class="animate-spin h-6 w-6 text-red-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memuat daftar klip video...
                    </div>

                    <div v-else-if="clipsList.length === 0" class="py-12 text-center text-slate-400 text-xs bg-slate-900/40 rounded-xl border border-slate-800">
                        Belum ada klip video yang dipotong.
                    </div>

                    <div v-else class="space-y-3">
                        <div 
                            v-for="clip in clipsList" 
                            :key="clip.id"
                            class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col gap-3 hover:border-slate-700 transition"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-bold text-sm text-white">{{ clip.title }}</h4>
                                    <p class="text-xs text-slate-400 font-mono truncate max-w-md mt-0.5">{{ clip.youtube_url }}</p>
                                    <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-2 font-mono">
                                        <span>Durasi: {{ formatSeconds(clip.duration_seconds) }}</span>
                                        <span>•</span>
                                        <span>{{ new Date(clip.created_at).toLocaleString('id-ID') }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Status Badge -->
                                    <span v-if="clip.status === 'completed'" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        Selesai
                                    </span>
                                    <span v-else-if="clip.status === 'processing'" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse">
                                        Memproses...
                                    </span>
                                    <span v-else-if="clip.status === 'pending'" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                        Antrean
                                    </span>
                                    <span v-else class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-500/20 text-red-400 border border-red-500/30">
                                        Gagal
                                    </span>

                                    <!-- Delete Button -->
                                    <button @click="deleteClip(clip.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Error display -->
                            <div v-if="clip.status === 'failed' && clip.error_message" class="text-[11px] p-2.5 rounded-lg bg-red-950/40 border border-red-900/50 text-red-400 font-mono">
                                {{ clip.error_message }}
                            </div>
                            <div v-else-if="clip.status === 'completed' && !clip.download_url" class="text-[11px] p-2.5 rounded-lg bg-amber-950/40 border border-amber-900/50 text-amber-400 font-mono">
                                File video tidak ditemukan di server. Silakan hapus klip ini dan buat klip baru.
                            </div>

                            <!-- Video Preview & Download -->
                            <div v-if="clip.status === 'completed' && clip.download_url" class="space-y-2 mt-1">
                                <video controls class="w-full max-h-56 rounded-lg bg-black border border-slate-800">
                                    <source :src="clip.download_url" type="video/mp4">
                                    Browser Anda tidak mendukung HTML5 Video.
                                </video>
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-[10px] text-amber-400 font-mono flex items-center gap-1">
                                        ⏱️ Otomatis dihapus dlm 1 jam
                                    </span>
                                    <a 
                                        :href="clip.download_url" 
                                        download 
                                        class="py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition flex items-center gap-1.5 shadow"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3m0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Unduh File Video (.mp4)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="border-t border-slate-800 bg-slate-900/70 px-6 py-3 flex items-center justify-between text-xs text-slate-400">
                <span class="font-sans font-medium">Tactical Stream Trimmer</span>
                <span>Batas Maksimal: 10 Menit</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    initialUrl: String,
});

const emit = defineEmits(['close']);

const activeTab = ref('create');
const isSubmitting = ref(false);
const isLoadingClips = ref(false);
const formError = ref('');
const successMessage = ref('');
const clipsList = ref([]);
let pollTimer = null;

const form = ref({
    youtube_url: '',
    title: '',
    start_time: '00:00:00',
    end_time: '00:05:00',
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.initialUrl) {
            form.value.youtube_url = props.initialUrl;
        }
        formError.value = '';
        successMessage.value = '';
        fetchClips();
        startPolling();
    } else {
        stopPolling();
    }
});

function closeModal() {
    stopPolling();
    emit('close');
}

// Convert timestamp (HH:MM:SS or MM:SS or integer) to seconds
function parseTimeToSeconds(val) {
    if (!val) return 0;
    if (!isNaN(val)) return parseInt(val, 10);
    const parts = String(val).trim().split(':').map(Number);
    if (parts.length === 3) return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
    if (parts.length === 2) return (parts[0] * 60) + parts[1];
    if (parts.length === 1) return parts[0] || 0;
    return 0;
}

const calculatedSeconds = computed(() => {
    const start = parseTimeToSeconds(form.value.start_time);
    const end = parseTimeToSeconds(form.value.end_time);
    return end > start ? end - start : 0;
});

const isDurationValid = computed(() => {
    return calculatedSeconds.value > 0 && calculatedSeconds.value <= 600;
});

const formattedDuration = computed(() => {
    const secs = calculatedSeconds.value;
    if (secs <= 0) return '00:00 (Invalid Time)';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m} menit ${s} detik (${secs}d)`;
});

function formatSeconds(secs) {
    if (!secs) return '0d';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}m ${s}s`;
}

async function submitTrim() {
    if (!isDurationValid.value) {
        formError.value = 'Durasi pemotongan tidak valid atau melebihi batas 10 menit (600 detik).';
        return;
    }

    isSubmitting.value = true;
    formError.value = '';
    successMessage.value = '';

    try {
        const response = await axios.post('/api/v1/clips/trim', form.value);
        successMessage.value = response.data.message || 'Proses pemotongan video berhasil diajukan!';
        activeTab.value = 'history';
        await fetchClips();
    } catch (err) {
        if (err.response && err.response.data && err.response.data.message) {
            formError.value = err.response.data.message;
        } else if (err.response && err.response.data && err.response.data.errors) {
            const firstErr = Object.values(err.response.data.errors)[0];
            formError.value = Array.isArray(firstErr) ? firstErr[0] : firstErr;
        } else {
            formError.value = 'Terjadi kesalahan saat mengajukan pemotongan video.';
        }
    } finally {
        isSubmitting.value = false;
    }
}

async function fetchClips() {
    isLoadingClips.value = true;
    try {
        const response = await axios.get('/api/v1/clips');
        clipsList.value = response.data.data || response.data || [];
    } catch (err) {
        console.error('Failed to fetch clips list:', err);
    } finally {
        isLoadingClips.value = false;
    }
}

async function deleteClip(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus klip video ini?')) return;
    try {
        await axios.delete(`/api/v1/clips/${id}`);
        clipsList.value = clipsList.value.filter(c => c.id !== id);
    } catch (err) {
        alert('Gagal menghapus klip video.');
    }
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(() => {
        const hasPendingOrProcessing = clipsList.value.some(c => c.status === 'pending' || c.status === 'processing');
        if (hasPendingOrProcessing || activeTab.value === 'history') {
            fetchClips();
        }
    }, 5000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onUnmounted(() => {
    stopPolling();
});
</script>
