<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Check } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const focusInput = (inputRef) => {
    const el = inputRef.value?.$el || inputRef.value;
    el?.focus?.();
};

const updatePassword = () => {
    form.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                focusInput(passwordInput);
            }
            if (form.errors.current_password) {
                form.reset("current_password");
                focusInput(currentPasswordInput);
            }
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <header class="space-y-1">
            <h2 class="text-base font-bold text-foreground font-mono uppercase tracking-wider">
                Keamanan &amp; Ubah Password
            </h2>
            <p class="text-xs text-muted-foreground">
                Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.
            </p>
        </header>

        <form class="space-y-4 text-xs" @submit.prevent="updatePassword">
            <div class="space-y-1.5">
                <Label for="current_password">Password Saat Ini</Label>
                <Input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <p v-if="form.errors.current_password" class="text-xs text-destructive">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div class="space-y-1.5">
                <Label for="password">Password Baru</Label>
                <Input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <p v-if="form.errors.password" class="text-xs text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="space-y-1.5">
                <Label for="password_confirmation">Konfirmasi Password Baru</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <p v-if="form.errors.password_confirmation" class="text-xs text-destructive">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Menyimpan..." : "Perbarui Password" }}
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
                        <span>Password berhasil diperbarui.</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
