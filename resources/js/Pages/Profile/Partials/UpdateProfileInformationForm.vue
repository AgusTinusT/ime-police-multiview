<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { Check } from "@lucide/vue";
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

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
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

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-xs text-warning">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-xs text-primary underline hover:text-primary/80 font-semibold ml-1"
                    >
                        Kirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-medium text-success"
                >
                    Link verifikasi baru telah dikirimkan ke email Anda.
                </div>
            </div>

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
