<script setup>
import { computed } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { ShieldCheck, Mail, AlertTriangle } from "@lucide/vue";

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
    officialSenderEmail: {
        type: String,
        default: "gusti.police.duty@12655999.brevosend.com",
    },
    officialSenderName: {
        type: String,
        default: "Gusti Police Duty - IME Roleplay",
    },
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent",
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="mb-4 text-xs text-muted-foreground leading-relaxed">
            Terima kasih telah mendaftar di <strong>Police Duty Multiview</strong>! Sebelum mulai menggunakan seluruh fitur, silakan verifikasi alamat email Anda dengan mengeklik tautan verifikasi yang telah kami kirimkan ke email Anda.
        </div>

        <!-- Notifikasi Link Baru Terkirim -->
        <Alert
            v-if="verificationLinkSent"
            class="mb-4 border-success/30 bg-success/15 text-success"
        >
            <AlertDescription class="text-xs font-mono">
                Tautan verifikasi baru telah berhasil dikirim ke alamat email Anda.
            </AlertDescription>
        </Alert>

        <!-- Informasi Pengirim Resmi (Official Sender Security Notice) -->
        <div class="mb-5 rounded-lg border border-primary/20 bg-primary/5 p-3 text-xs space-y-2">
            <div class="flex items-center gap-1.5 font-semibold text-primary">
                <ShieldCheck class="size-4 shrink-0" />
                <span>Pemberitahuan Keamanan Pengirim Resmi</span>
            </div>
            <p class="text-muted-foreground leading-relaxed">
                Pastikan email verifikasi yang Anda terima berasal dari alamat pengirim resmi sistem kami:
            </p>
            <div class="rounded border border-border bg-background/80 px-2.5 py-1.5 font-mono text-xs text-foreground select-all break-all">
                {{ officialSenderName }} &lt;{{ officialSenderEmail }}&gt;
            </div>
            <div class="flex items-center gap-1.5 text-xs text-warning pt-0.5">
                <AlertTriangle class="size-3.5 shrink-0" />
                <span>Jika belum masuk di Kotak Masuk (Inbox), silakan periksa folder <strong>Spam</strong> atau <strong>Promosi</strong>.</span>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between gap-3">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    <Mail class="mr-1.5 size-3.5" />
                    Kirim Ulang Email Verifikasi
                </Button>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-xs text-muted-foreground hover:text-foreground underline font-mono transition"
                >
                    Keluar (Log Out)
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
