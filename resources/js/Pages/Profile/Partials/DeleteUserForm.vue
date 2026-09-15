<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
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
            <h2 class="text-base font-bold text-red-400 font-mono uppercase tracking-wider flex items-center gap-1.5">
                <span>⚠️ Hapus Akun Permanen</span>
            </h2>
            <p class="text-xs text-slate-400">
                Setelah akun Anda dihapus, seluruh data dan preferensi tersimpan akan dihapus secara permanen.
            </p>
        </header>

        <div class="pt-1">
            <button
                @click="confirmUserDeletion"
                class="px-4 py-2 rounded-lg bg-red-950 hover:bg-red-900 text-red-300 hover:text-white border border-red-800/60 font-bold text-xs transition shadow-md"
            >
                Hapus Akun Pengguna
            </button>
        </div>

        <!-- Danger Confirmation Modal (Dark Red Glassmorphism) -->
        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 bg-slate-950 border border-red-900/60 rounded-2xl text-slate-100 space-y-4">
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-red-400 flex items-center gap-2">
                        <span>⚠️ Konfirmasi Penghapusan Akun</span>
                    </h3>
                    <p class="text-xs text-slate-300">
                        Apakah Anda yakin ingin menghapus akun Anda secara permanen? Masukkan password akun Anda untuk mengonfirmasi tindakan ini.
                    </p>
                </div>

                <div class="space-y-1">
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Masukkan Password Anda"
                        @keyup.enter="deleteUser"
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition text-xs"
                    />
                    <div v-if="form.errors.password" class="text-red-400 text-[11px] mt-1">
                        {{ form.errors.password }}
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-800">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition"
                    >
                        Batal
                    </button>

                    <button
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white text-xs font-bold transition shadow-md"
                    >
                        {{ form.processing ? 'Menghapus...' : 'Ya, Hapus Akun' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
