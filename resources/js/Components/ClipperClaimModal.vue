<template>
    <Dialog :open="show" @update:open="(val) => { if (!val) close(); }">
        <DialogContent class="sm:max-w-lg p-0 bg-card border-border overflow-hidden text-foreground font-sans max-h-[92vh] overflow-y-auto">
            <!-- Header -->
            <DialogHeader class="p-6 sm:p-7 pb-4 border-b border-border text-left space-y-2">
                <div class="flex items-center justify-between">
                    <Badge variant="success" class="inline-flex items-center gap-1.5 font-mono text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-success motion-safe:animate-pulse"></span>
                        Beta Tester Access (FREE)
                    </Badge>
                </div>

                <DialogTitle class="text-xl sm:text-2xl font-bold text-foreground pt-1 uppercase font-tactical tracking-tight">
                    Pengajuan Hak Akses Clipper
                </DialogTitle>
                <DialogDescription class="text-xs text-muted-foreground">
                    Pendaftaran resmi Clipper Komunitas IME. Seluruh pengajuan
                    bersifat <strong class="text-success">GRATIS</strong> selama masa
                    Uji Coba Beta.
                </DialogDescription>

                <!-- Status Banner Pembayaran & Officer -->
                <div class="grid grid-cols-2 gap-2 pt-2 font-mono text-xs">
                    <div
                        class="p-2.5 rounded-xl bg-primary/10 border border-primary/30 text-primary flex items-center gap-2"
                    >
                        <Zap class="w-4 h-4 text-primary shrink-0" />
                        <span>Hak Akses: <strong>GRATIS</strong></span>
                    </div>

                    <div
                        class="p-2.5 rounded-xl bg-muted border border-border text-muted-foreground flex items-center gap-2 opacity-75"
                        title="Fitur Pembayaran Berbayar Saat Ini Dinonaktifkan"
                    >
                        <Lock class="w-4 h-4 text-warning shrink-0" />
                        <span>
                            Pembayaran:
                            <strong class="text-warning">NONAKTIF</strong>
                        </span>
                    </div>
                </div>
            </DialogHeader>

            <!-- Form Body -->
            <form
                @submit.prevent="submitForm"
                class="p-6 sm:p-7 pt-4 space-y-4"
            >
                <!-- Alert Feedback -->
                <Alert
                    v-if="successMessage"
                    class="p-3.5 border-success/40 bg-success/10 text-success text-xs font-mono flex items-center gap-2"
                >
                    <Check class="w-4 h-4 shrink-0" />
                    <AlertDescription>{{ successMessage }}</AlertDescription>
                </Alert>

                <Alert
                    v-if="errorMessage"
                    variant="destructive"
                    class="p-3.5 text-xs font-mono flex items-center gap-2"
                >
                    <X class="w-4 h-4 shrink-0" />
                    <AlertDescription>{{ errorMessage }}</AlertDescription>
                </Alert>

                <!-- Info Target Akses -->
                <div
                    class="bg-muted/50 p-3.5 rounded-xl border border-border flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-primary text-primary-foreground flex items-center justify-center font-bold text-xs font-tactical shrink-0"
                        >
                            BETA
                        </div>
                        <div>
                            <p
                                class="text-xs font-mono text-muted-foreground uppercase tracking-wider"
                            >
                                Peran Hak Akses yang Diajukan
                            </p>
                            <p class="text-xs sm:text-sm font-bold text-foreground">
                                CLIPPER IME ROLEPLAY
                            </p>
                        </div>
                    </div>
                    <Badge variant="success" class="font-mono text-xs font-bold shrink-0">
                        GRATIS
                    </Badge>
                </div>

                <!-- Field Email / Akun -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-foreground">
                        Alamat Email Terdaftar
                        <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="nama@email.com"
                        class="text-xs sm:text-sm font-mono"
                    />
                    <p class="text-xs text-muted-foreground">
                        Hak akses Clipper akan diaktifkan ke akun pengguna
                        dengan email ini setelah disetujui.
                    </p>
                </div>

                <!-- Link Channel YouTube / Media Social -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-foreground">
                        Link Channel YouTube / Portofolio Klip
                    </Label>
                    <Input
                        v-model="form.channel_url"
                        type="url"
                        placeholder="https://youtube.com/@channel_anda atau link sampel klip..."
                        class="text-xs sm:text-sm font-mono"
                    />
                </div>

                <!-- Catatan / Alasan Pengajuan -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-foreground">
                        Alasan / Keterangan Pengajuan Akses Clipper
                    </Label>
                    <Textarea
                        v-model="form.reason"
                        rows="3"
                        placeholder="Tuliskan keterangan singkat keikutsertaan Anda (misal: Editor komunitas IME, Clipper channel YouTube, atau permohonan akses Beta)..."
                        class="text-xs sm:text-sm font-sans"
                    />
                </div>

                <!-- Upload Screenshot Bukti / Sampel (Opsional) -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-foreground">
                        Screenshot Bukti Channel / Portofolio (Opsional)
                    </Label>
                    <div
                        class="border-2 border-dashed border-border hover:border-primary/50 rounded-xl p-4 text-center cursor-pointer transition bg-muted/30"
                        @click="triggerFileInput"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleFileUpload"
                        />
                        <Image class="w-7 h-7 text-muted-foreground mx-auto mb-1.5" />
                        <p class="text-xs text-foreground font-medium">
                            {{
                                fileName
                                    ? fileName
                                    : "Klik untuk upload screenshot channel / bukti keaktifan"
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground mt-0.5 font-mono">
                            PNG, JPG, atau WebP (Maks. 5 MB)
                        </p>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-2">
                    <Button
                        type="submit"
                        :disabled="loading"
                        size="lg"
                        class="w-full py-3 text-xs sm:text-sm font-bold rounded-xl font-tactical uppercase tracking-wider flex items-center justify-center gap-2"
                    >
                        <Loader2
                            v-if="loading"
                            class="w-4 h-4 animate-spin"
                        />
                        <span>{{
                            loading
                                ? "Mengirim Pengajuan..."
                                : "Kirim Pengajuan Akses Clipper"
                        }}</span>
                        <ArrowRight v-if="!loading" class="w-4 h-4" />
                    </Button>
                    <p
                        class="text-center text-xs text-muted-foreground mt-2.5 font-mono"
                    >
                        Notifikasi pengajuan akan langsung diteruskan ke Admin
                        via Discord.
                    </p>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/Components/ui/dialog";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import {
    ArrowRight,
    Check,
    Image,
    Loader2,
    Lock,
    X,
    Zap,
} from "@lucide/vue";

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
