<script setup>
import { useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import { AlertTriangle } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: "",
});

const focusPasswordInput = () => {
    const el = passwordInput.value?.$el || passwordInput.value;
    el?.focus?.();
};

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => focusPasswordInput());
};

const deleteUser = () => {
    form.delete(route("profile.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => focusPasswordInput(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-4">
        <header class="space-y-1">
            <h2 class="text-base font-semibold text-destructive flex items-center gap-1.5">
                <AlertTriangle class="size-4 text-destructive shrink-0" />
                <span>Hapus Akun Permanen</span>
            </h2>
            <p class="text-xs text-muted-foreground">
                Setelah akun Anda dihapus, seluruh data dan preferensi tersimpan akan dihapus secara permanen.
            </p>
        </header>

        <div class="pt-1">
            <Button
                variant="destructive"
                @click="confirmUserDeletion"
            >
                Hapus Akun Pengguna
            </Button>
        </div>

        <!-- Danger Confirmation AlertDialog -->
        <AlertDialog
            :open="confirmingUserDeletion"
            @update:open="(!$event) && closeModal()"
        >
            <AlertDialogContent class="sm:max-w-md">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-destructive flex items-center gap-2">
                        <AlertTriangle class="size-4 shrink-0" />
                        <span>Konfirmasi Penghapusan Akun</span>
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus akun Anda secara permanen? Masukkan password akun Anda untuk mengonfirmasi tindakan ini.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div class="space-y-1.5">
                    <Label for="delete-account-password" class="sr-only">Password</Label>
                    <Input
                        id="delete-account-password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Masukkan Password Anda"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="text-xs text-destructive">
                        {{ form.errors.password }}
                    </p>
                </div>

                <AlertDialogFooter class="gap-2">
                    <Button variant="outline" @click="closeModal">
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ form.processing ? "Menghapus..." : "Ya, Hapus Akun" }}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </section>
</template>
