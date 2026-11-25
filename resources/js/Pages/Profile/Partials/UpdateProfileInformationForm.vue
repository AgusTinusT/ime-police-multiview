<script setup>
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { Check, CheckCircle2, MailCheck, Send, TriangleAlert } from "@lucide/vue";
import { toast } from "vue-sonner";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isEmailVerified = computed(() => !!user.value?.email_verified_at);
const sendingVerification = ref(false);
const localVerificationSent = ref(false);

const form = useForm({
    name: user.value?.name || "",
    email: user.value?.email || "",
});

const sendVerificationEmail = () => {
    if (sendingVerification.value) return;
    sendingVerification.value = true;
    router.post(
        route("verification.send"),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                localVerificationSent.value = true;
                toast.success("Link verifikasi baru telah dikirim ke email Anda.");
            },
            onError: () => {
                toast.error("Gagal mengirim email verifikasi. Coba lagi beberapa saat.");
            },
            onFinish: () => {
                sendingVerification.value = false;
            },
        },
    );
};
</script>

<template>
    <section class="space-y-6">
        <header class="space-y-1">
            <h2 class="text-base font-semibold text-foreground">
                Informasi Profil Akun
            </h2>
            <p class="text-xs text-muted-foreground">
                Perbarui nama tampilan akun dan alamat email terdaftar Anda.
            </p>
        </header>

        <form
            class="space-y-4 text-xs"
            @submit.prevent="form.patch(route('profile.update'))"
        >
            <div class="space-y-1.5">
                <Label for="name">Nama Tampilan Akun</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nama Lengkap / Callsign"
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-1.5">
                <Label for="email">Alamat Email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <p v-if="form.errors.email" class="text-xs text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>

            <!-- Email Verification Status Banner -->
            <Alert
                v-if="isEmailVerified"
                class="border-success/30 bg-success/10 text-foreground"
            >
                <CheckCircle2 class="size-4 text-success" />
                <AlertTitle class="text-xs font-semibold text-success">
                    Email Sudah Terverifikasi
                </AlertTitle>
                <AlertDescription class="text-xs text-muted-foreground">
                    Alamat email <span class="font-mono text-foreground">{{ user.email }}</span> telah terverifikasi dan siap digunakan untuk seluruh fitur akun.
                </AlertDescription>
            </Alert>

            <Alert
                v-else-if="mustVerifyEmail"
                class="border-warning/40 bg-warning/10 text-foreground"
            >
                <TriangleAlert class="size-4 text-warning" />
                <AlertTitle class="text-xs font-semibold text-warning">
                    Email Belum Diverifikasi
                </AlertTitle>
                <AlertDescription class="space-y-3 text-xs text-muted-foreground">
                    <p>
                        Verifikasi alamat email Anda untuk mengamankan akun dan mengaktifkan fitur penuh. Email resmi hanya dikirim dari
                        <span class="font-mono text-foreground">gusti.police.duty@12655999.brevosend.com</span>
                        (cek folder Spam/Promosi jika belum muncul di Inbox).
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="sendingVerification"
                            class="gap-1.5 border-warning/40 hover:bg-warning/15"
                            @click="sendVerificationEmail"
                        >
                            <Send class="size-3.5" />
                            <span>
                                {{
                                    sendingVerification
                                        ? "Mengirim..."
                                        : "Verifikasi Email"
                                }}
                            </span>
                        </Button>

                        <span
                            v-if="status === 'verification-link-sent' || localVerificationSent"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-success"
                        >
                            <MailCheck class="size-3.5" />
                            <span>Link verifikasi baru telah dikirim ke email Anda.</span>
                        </span>
                    </div>
                </AlertDescription>
            </Alert>

            <div class="flex items-center gap-4 pt-2">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Menyimpan..." : "Simpan Perubahan" }}
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-success flex items-center gap-1"
                    >
                        <Check class="size-3.5" />
                        <span>Profil berhasil diperbarui.</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
