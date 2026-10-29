<template>
    <Dialog :open="show" @update:open="(val) => { if (!val) closeModal(); }">
        <DialogContent class="sm:max-w-3xl p-0 bg-card border-border text-foreground overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <DialogHeader class="flex flex-row items-center justify-between border-b border-border bg-muted/40 px-6 py-4 space-y-0">
                <div class="flex items-center gap-3 text-left">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-destructive/15 text-destructive border border-destructive/30">
                        <Scissors class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold tracking-wide text-foreground flex items-center gap-2">
                            Tactical Stream Trimmer
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground">
                            Potong segmen video YouTube (Maksimal 10 Menit)
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <!-- Tab Buttons -->
            <div class="flex border-b border-border bg-muted/20 px-6">
                <button
                    @click="activeTab = 'create'"
                    class="py-3 px-4 font-medium text-sm border-b-2 transition flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'create' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Plus class="w-4 h-4" />
                    Potong Video Baru
                </button>
                <button
                    @click="activeTab = 'history'; fetchClips();"
                    class="py-3 px-4 font-medium text-sm border-b-2 transition flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'history' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Layers class="w-4 h-4" />
                    Daftar Klip Saya
                    <Badge v-if="clipsList.length" variant="secondary" class="ml-1 font-mono text-xs">
                        {{ clipsList.length }}
                    </Badge>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto flex-1 space-y-5">
                <!-- TAB 1: FORM POTONG VIDEO -->
                <div v-if="activeTab === 'create'" class="space-y-4">
                    <!-- Notification Banner -->
                    <Alert v-if="formError" variant="destructive" class="p-3.5 text-xs flex items-center gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <AlertDescription>{{ formError }}</AlertDescription>
                    </Alert>

                    <Alert v-if="successMessage" class="p-3.5 border-success/40 bg-success/10 text-success text-xs flex items-center gap-2">
                        <Check class="w-4 h-4 shrink-0" />
                        <AlertDescription>{{ successMessage }}</AlertDescription>
                    </Alert>

                    <!-- YouTube URL Input -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            URL Video / Live Stream YouTube <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            v-model="form.youtube_url"
                            type="text"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="font-mono"
                        />
                    </div>

                    <!-- Judul Klip (Optional) -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Judul Catatan Klip (Opsional)
                        </Label>
                        <Input
                            v-model="form.title"
                            type="text"
                            placeholder="Contoh: Kejadian Kamera 1 Menit 10"
                        />
                    </div>

                    <!-- Timestamp Inputs (Start & End) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Waktu Mulai (Start Time) <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                v-model="form.start_time"
                                type="text"
                                placeholder="00:01:30 atau 90"
                                class="font-mono"
                            />
                            <p class="text-xs text-muted-foreground">Format: Jam:Menit:Detik atau Menit:Detik</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Waktu Selesai (End Time) <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                v-model="form.end_time"
                                type="text"
                                placeholder="00:06:30 atau 390"
                                class="font-mono"
                            />
                            <p class="text-xs text-muted-foreground">Format: Jam:Menit:Detik atau Menit:Detik</p>
                        </div>
                    </div>

                    <!-- Format & Quality Selector -->
                    <div class="space-y-1.5">
                        <Label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Format & Kualitas Output
                        </Label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label
                                v-for="fmt in formatOptions"
                                :key="fmt.value"
                                class="flex items-center space-x-2 p-2.5 rounded-xl bg-muted/40 border border-border cursor-pointer hover:border-primary/50 transition"
                                :class="form.format === fmt.value ? 'border-primary bg-primary/10' : ''"
                            >
                                <input
                                    type="radio"
                                    name="modalFormatOption"
                                    :value="fmt.value"
                                    v-model="form.format"
                                    class="text-primary focus:ring-0 bg-background border-border"
                                />
                                <span class="font-medium text-foreground text-xs">{{ fmt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Duration Badge Counter -->
                    <div
                        class="rounded-xl border p-4 flex items-center justify-between"
                        :class="isDurationValid ? 'bg-success/10 border-success/30 text-success' : 'bg-destructive/10 border-destructive/30 text-destructive'"
                    >
                        <div class="flex items-center gap-2">
                            <Clock class="w-5 h-5" />
                            <span class="text-xs font-medium">Estimasi Durasi Potongan:</span>
                        </div>
                        <div class="text-sm font-bold font-mono text-right">
                            {{ formattedDuration }}
                            <span v-if="calculatedSeconds > 600" class="text-xs block text-destructive font-normal">
                                (Melebihi batas max 10 menit!)
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <Button
                            @click="submitTrim"
                            :disabled="isSubmitting || !isDurationValid"
                            class="w-full py-3 font-bold text-sm tracking-wide flex items-center justify-center gap-2"
                        >
                            <Loader2 v-if="isSubmitting" class="animate-spin h-4 w-4" />
                            <span>{{ isSubmitting ? 'Memproses Antrean Klip...' : 'Potong & Simpan Klip Video' }}</span>
                        </Button>
                    </div>
                </div>

                <!-- TAB 2: DAFTAR KLIP SAYA -->
                <div v-else-if="activeTab === 'history'" class="space-y-4">
                    <div v-if="isLoadingClips" class="py-12 text-center text-muted-foreground text-xs">
                        <Loader2 class="animate-spin h-6 w-6 text-primary mx-auto mb-2" />
                        Memuat daftar klip video...
                    </div>

                    <div v-else-if="clipsList.length === 0" class="py-12 text-center text-muted-foreground text-xs bg-muted/30 rounded-xl border border-border">
                        Belum ada klip video yang dipotong.
                    </div>

                    <div v-else class="space-y-3">
                        <Card
                            v-for="clip in clipsList"
                            :key="clip.id"
                            class="p-4 bg-muted/30 border-border flex flex-col gap-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h4 class="font-bold text-sm text-foreground truncate">{{ clip.title }}</h4>
                                    <p class="text-xs text-muted-foreground font-mono truncate max-w-md mt-0.5">{{ clip.youtube_url }}</p>
                                    <div class="flex items-center gap-3 text-xs text-muted-foreground mt-2 font-mono">
                                        <span>Durasi: {{ formatSeconds(clip.duration_seconds) }}</span>
                                        <span>•</span>
                                        <span>{{ new Date(clip.created_at).toLocaleString('id-ID') }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- Status Badge -->
                                    <Badge v-if="clip.status === 'completed'" variant="success">
                                        Selesai
                                    </Badge>
                                    <Badge v-else-if="clip.status === 'processing'" variant="info" class="motion-safe:animate-pulse">
                                        Memproses...
                                    </Badge>
                                    <Badge v-else-if="clip.status === 'pending'" variant="secondary">
                                        Antrean
                                    </Badge>
                                    <Badge v-else variant="destructive">
                                        Gagal
                                    </Badge>

                                    <!-- Delete Button -->
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Hapus klip video"
                                        @click="promptDeleteClip(clip.id)"
                                        class="text-muted-foreground hover:text-destructive"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Error display -->
                            <div v-if="clip.status === 'failed' && clip.error_message" class="text-xs p-2.5 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive font-mono">
                                {{ clip.error_message }}
                            </div>
                            <div v-else-if="clip.status === 'completed' && !clip.download_url" class="text-xs p-2.5 rounded-lg bg-warning/10 border border-warning/30 text-warning font-mono">
                                File video tidak ditemukan di server. Silakan hapus klip ini dan buat klip baru.
                            </div>

                            <!-- Video Preview & Download -->
                            <div v-if="clip.status === 'completed' && clip.download_url" class="space-y-2 mt-1">
                                <video controls class="w-full max-h-56 rounded-lg bg-background border border-border">
                                    <source :src="clip.download_url" type="video/mp4">
                                    Browser Anda tidak mendukung HTML5 Video.
                                </video>
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-xs text-warning font-mono flex items-center gap-1">
                                        <Clock class="w-3.5 h-3.5 text-warning shrink-0" />
                                        <span>Otomatis dihapus dlm 1 jam</span>
                                    </span>
                                    <Button as-child size="sm" variant="default">
                                        <a :href="clip.download_url" download class="flex items-center gap-1.5">
                                            <Download class="w-4 h-4" />
                                            Unduh File Video (.mp4)
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-border bg-muted/40 px-6 py-3 flex items-center justify-between text-xs text-muted-foreground">
                <span class="font-sans font-medium">Tactical Stream Trimmer</span>
                <span>Batas Maksimal: 10 Menit</span>
            </div>
        </DialogContent>
    </Dialog>

    <AlertDialog :open="clipToDeleteId !== null" @update:open="(val) => { if (!val) clipToDeleteId = null; }">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Hapus Klip Video?</AlertDialogTitle>
                <AlertDialogDescription>
                    Apakah Anda yakin ingin menghapus klip video ini? Tindakan ini tidak dapat dibatalkan.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="clipToDeleteId = null">Batal</AlertDialogCancel>
                <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmDeleteClip">
                    Hapus
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { toast } from 'vue-sonner';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from '@/Components/ui/alert-dialog';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card } from '@/Components/ui/card';
import {
    Scissors,
    Plus,
    Layers,
    AlertCircle,
    Check,
    Clock,
    Loader2,
    Trash2,
    Download,
} from '@lucide/vue';

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
const clipToDeleteId = ref(null);
let pollTimer = null;

const formatOptions = [
    { value: 'MP4 1080p', label: 'MP4 (1080p FHD)' },
    { value: 'MP4 720p', label: 'MP4 (720p HD)' },
    { value: 'MP4 360p', label: 'MP4 (360p SD)' },
    { value: 'MP3 Audio', label: 'MP3 (320kbps Audio)' },
];

const form = ref({
    youtube_url: '',
    title: '',
    start_time: '00:00:00',
    end_time: '00:05:00',
    format: 'MP4 1080p',
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

function promptDeleteClip(id) {
    clipToDeleteId.value = id;
}

async function confirmDeleteClip() {
    const id = clipToDeleteId.value;
    if (!id) return;
    clipToDeleteId.value = null;
    try {
        await axios.delete(`/api/v1/clips/${id}`);
        clipsList.value = clipsList.value.filter(c => c.id !== id);
        toast.success('Klip video berhasil dihapus.');
    } catch (err) {
        toast.error('Gagal menghapus klip video.');
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
