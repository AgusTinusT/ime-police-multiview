<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import {
    Bug,
    FileEdit,
    PlusCircle,
    MessageSquarePlus,
    Send,
    RefreshCw,
    Info,
    CheckCircle2,
    AlertCircle,
    Bot,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { PageContainer, PageHeader } from "@/Components/tactical";
import { cn } from "@/lib/utils";

useI18n();

const props = defineProps({
    prefillType: {
        type: String,
        default: "BUG_REPORT",
    },
});

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

const categoryOptions = [
    {
        id: "BUG_REPORT",
        title: "Bug / Error",
        subtitle: "Sistem bermasalah",
        icon: Bug,
        activeClass: "bg-destructive/15 border-destructive/50 text-foreground",
    },
    {
        id: "DATA_CORRECTION",
        title: "Typo / Teks",
        subtitle: "Koreksi kata",
        icon: FileEdit,
        activeClass: "bg-warning/15 border-warning/50 text-foreground",
    },
    {
        id: "CHANNEL_REQUEST",
        title: "Ide Fitur",
        subtitle: "Usulan streamer",
        icon: PlusCircle,
        activeClass: "bg-primary/15 border-primary/50 text-foreground",
    },
    {
        id: "OTHER",
        title: "Lainnya",
        subtitle: "Kritik & kesan",
        icon: MessageSquarePlus,
        activeClass: "bg-info/15 border-info/50 text-foreground",
    },
];

// Category Selection Function
const selectType = (type) => {
    feedbackForm.value.type = type;
};

// Dynamic Labels based on selected category
const titleLabel = computed(() => {
    if (feedbackForm.value.type === "DATA_CORRECTION") {
        return "Halaman atau Dokumen yang Memiliki Typo";
    } else if (feedbackForm.value.type === "CHANNEL_REQUEST") {
        return "Nama Fitur / Usulan Streamer Baru";
    } else if (feedbackForm.value.type === "OTHER") {
        return "Subjek / Judul Masukan";
    }
    return "Judul Kendala / Bug";
});

const titlePlaceholder = computed(() => {
    if (feedbackForm.value.type === "DATA_CORRECTION") {
        return "Contoh: Typo pada dokumen SOP-MV-01 di halaman FAQ";
    } else if (feedbackForm.value.type === "CHANNEL_REQUEST") {
        return "Contoh: Penambahan Streamer Ofc. Budi (1-ADAM-12)";
    } else if (feedbackForm.value.type === "OTHER") {
        return "Contoh: Pertanyaan seputar integrasi API Discord";
    }
    return "Contoh: Tombol mute video tidak merespons di layar Multiview";
});

const descriptionLabel = computed(() => {
    if (feedbackForm.value.type === "DATA_CORRECTION") {
        return "Konteks Paragraf / Keterangan Tambahan";
    } else if (feedbackForm.value.type === "CHANNEL_REQUEST") {
        return "Bagaimana fitur / streamer ini mempermudah operasi dispatch?";
    }
    return "Deskripsi & Langkah Kejadian";
});

const descriptionPlaceholder = computed(() => {
    if (feedbackForm.value.type === "DATA_CORRECTION") {
        return "Jelaskan bagian paragraf atau teks yang memerlukan perbaikan...";
    } else if (feedbackForm.value.type === "CHANNEL_REQUEST") {
        return "Jelaskan detail usulan fitur atau alokasi siaran live streamer...";
    }
    return "Jelaskan langkah demi langkah sampai kendala ini terjadi...";
});

// Submit Feedback Handler
const submitFeedbackForm = async () => {
    if (
        !feedbackForm.value.message ||
        feedbackForm.value.message.trim() === ""
    ) {
        toast.error("Mohon isi deskripsi / detail laporan Anda.");
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
            const msg =
                data.message ||
                "Masukan Anda berhasil dikirim ke Discord Dispatcher!";
            successMessage.value = msg;
            toast.success(msg);
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
            const errMsg =
                data.message || "Gagal mengirim laporan. Silakan coba lagi.";
            errorMessage.value = errMsg;
            toast.error(errMsg);
        }
    } catch {
        const netMsg = "Terjadi kesalahan jaringan saat mengirim laporan.";
        errorMessage.value = netMsg;
        toast.error(netMsg);
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <TacticalLayout>
        <Head title="Pusat Laporan & Feedback — IME Police Terminal" />

        <PageContainer size="md" class="py-8 sm:py-12 space-y-8">
            <!-- Hero Header Section -->
            <PageHeader
                eyebrow="DUKUNGAN PENGGUNA & DISPATCH"
                :title="$t('feedback.title')"
                :subtitle="$t('feedback.subtitle')"
            />

            <!-- Alert Notifications -->
            <Alert
                v-if="successMessage"
                class="border-success/30 bg-success/15 text-success"
            >
                <CheckCircle2 class="size-4 text-success" />
                <AlertTitle class="font-bold">Laporan Berhasil Terkirim!</AlertTitle>
                <AlertDescription class="text-xs font-mono">
                    {{ successMessage }}
                </AlertDescription>
            </Alert>

            <Alert
                v-if="errorMessage"
                variant="destructive"
            >
                <AlertCircle class="size-4" />
                <AlertTitle class="font-bold">Gagal Mengirim Laporan</AlertTitle>
                <AlertDescription class="text-xs font-mono">
                    {{ errorMessage }}
                </AlertDescription>
            </Alert>

            <!-- Main Form Card Container -->
            <Card>
                <CardContent class="p-6 sm:p-8 space-y-8">
                    <!-- 1. Category Selector (4 Interactive Cards) -->
                    <div>
                        <Label class="block text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground mb-3">
                            1. Pilih Jenis Masukan
                            <span class="text-destructive">*</span>
                        </Label>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <button
                                v-for="opt in categoryOptions"
                                :key="opt.id"
                                type="button"
                                :class="
                                    cn(
                                        'p-4 rounded-lg border text-center transition flex flex-col items-center justify-center gap-1.5',
                                        feedbackForm.type === opt.id
                                            ? opt.activeClass
                                            : 'bg-muted/30 border-border text-muted-foreground hover:border-primary/40 hover:text-foreground',
                                    )
                                "
                                @click="selectType(opt.id)"
                            >
                                <component :is="opt.icon" class="size-5 text-primary" />
                                <span class="text-xs font-bold font-display uppercase tracking-wider">
                                    {{ opt.title }}
                                </span>
                                <span class="text-xs text-muted-foreground font-mono">
                                    {{ opt.subtitle }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Form Inputs -->
                    <form class="space-y-6" @submit.prevent="submitFeedbackForm">
                        <!-- Report Title -->
                        <div class="space-y-1.5">
                            <Label for="feedback-title" class="text-xs font-semibold">
                                {{ titleLabel }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="feedback-title"
                                v-model="feedbackForm.title"
                                type="text"
                                required
                                :placeholder="titlePlaceholder"
                            />
                        </div>

                        <!-- Dynamic Typo / Text Correction Fields -->
                        <div
                            v-if="feedbackForm.type === 'DATA_CORRECTION'"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1"
                        >
                            <div class="space-y-1.5">
                                <Label for="feedback-typo-wrong" class="text-xs font-semibold">
                                    Teks yang Salah (Saat Ini)
                                </Label>
                                <Input
                                    id="feedback-typo-wrong"
                                    v-model="feedbackForm.typo_wrong"
                                    type="text"
                                    placeholder="Contoh: 'Pengamban Patroli'"
                                    class="font-mono"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="feedback-typo-correct" class="text-xs font-semibold">
                                    Usulan Koreksi yang Benar
                                </Label>
                                <Input
                                    id="feedback-typo-correct"
                                    v-model="feedbackForm.typo_correct"
                                    type="text"
                                    placeholder="Contoh: 'Pengembangan Patroli'"
                                    class="font-mono"
                                />
                            </div>
                        </div>

                        <!-- Dynamic Officer / Streamer Fields -->
                        <div
                            v-if="feedbackForm.type === 'CHANNEL_REQUEST'"
                            class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1"
                        >
                            <div class="sm:col-span-2 space-y-1.5">
                                <Label for="feedback-officer-name" class="text-xs font-semibold">
                                    Nama Karakter / Callsign Polisi
                                </Label>
                                <Input
                                    id="feedback-officer-name"
                                    v-model="feedbackForm.officer_name"
                                    type="text"
                                    placeholder="Ofc. Budi / 1-ADAM-12"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="feedback-department" class="text-xs font-semibold">
                                    Kesatuan / Department
                                </Label>
                                <select
                                    id="feedback-department"
                                    v-model="feedbackForm.department"
                                    class="w-full h-9 px-3 rounded-md bg-background border border-input text-foreground text-xs focus:outline-hidden focus:ring-2 focus:ring-ring font-mono"
                                >
                                    <option value="LSPD">LSPD (Police)</option>
                                    <option value="BCSO">BCSO (Sheriff)</option>
                                    <option value="SASP">SASP (State Police)</option>
                                    <option value="SAPR">SAPR (Park Rangers)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Main Description Textarea -->
                        <div class="space-y-1.5">
                            <Label for="feedback-message" class="text-xs font-semibold">
                                {{ descriptionLabel }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <Textarea
                                id="feedback-message"
                                v-model="feedbackForm.message"
                                required
                                rows="4"
                                :placeholder="descriptionPlaceholder"
                            />
                        </div>

                        <!-- Screenshot / Image Link Input Field -->
                        <div class="space-y-2">
                            <Label for="feedback-image-url" class="text-xs font-semibold">
                                Tangkapan Layar / Screenshot URL (Opsional)
                            </Label>
                            <Input
                                id="feedback-image-url"
                                v-model="feedbackForm.image_url"
                                type="url"
                                placeholder="Tempelkan URL Gambar (contoh: https://i.imgur.com/example.png atau Discord attachment link)..."
                                class="font-mono"
                            />
                            <div class="border border-dashed border-border rounded-lg p-4 text-center bg-muted/30 text-xs text-muted-foreground space-y-1">
                                <div class="flex items-center justify-center gap-1.5 text-primary font-mono text-xs font-semibold">
                                    <Bot class="size-4" />
                                    <span>INTEGRASI GAMBAR DISCORD BOT AUTOMATIC</span>
                                </div>
                                <p class="text-xs text-muted-foreground max-w-prose mx-auto">
                                    Discord Webhook otomatis mendeteksi URL gambar (Imgur, Discord Attachment, Gyazo, dsb.) dan menampilkan gambar pratinjau utuh secara langsung di channel Discord Dispatcher!
                                </p>
                            </div>
                        </div>

                        <!-- Contact & Related Links -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label for="feedback-sender-name" class="text-xs font-semibold">
                                    Nama / Email Pengirim (Untuk Notifikasi Perbaikan)
                                </Label>
                                <Input
                                    id="feedback-sender-name"
                                    v-model="feedbackForm.sender_name"
                                    type="text"
                                    placeholder="Contoh: Citizen Ray / raymond@gmail.com"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="feedback-handle-url" class="text-xs font-semibold">
                                    Tautan URL / YouTube Terkait (Jika Ada)
                                </Label>
                                <Input
                                    id="feedback-handle-url"
                                    v-model="feedbackForm.handle_or_url"
                                    type="url"
                                    placeholder="https://youtube.com/@handle atau link halaman terkait"
                                    class="font-mono"
                                />
                            </div>
                        </div>

                        <!-- Submit Action Footer -->
                        <div class="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                            <span class="text-xs text-muted-foreground font-mono">
                                Terhubung langsung ke Webhook Dispatcher
                            </span>

                            <Button
                                type="submit"
                                :disabled="isSubmitting"
                                class="w-full sm:w-auto font-mono font-bold uppercase tracking-wider"
                            >
                                <RefreshCw
                                    v-if="isSubmitting"
                                    class="size-4 mr-2 motion-safe:animate-spin"
                                />
                                <Send v-else class="size-4 mr-2" />
                                <span>
                                    {{
                                        isSubmitting
                                            ? "MENGIRIM LAPORAN..."
                                            : "KIRIM MASUKAN SEKARANG"
                                    }}
                                </span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Bottom Info Banner -->
            <Alert>
                <Info class="size-4 text-primary" />
                <AlertDescription class="text-xs text-muted-foreground leading-relaxed max-w-prose">
                    Semua laporan diproses secara otomatis oleh sistem Dispatcher. Bug kritis dan usulan streamer akan langsung ditinjau oleh Komando IME Roleplay.
                </AlertDescription>
            </Alert>
        </PageContainer>
    </TacticalLayout>
</template>
