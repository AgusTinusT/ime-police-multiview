<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <header class="space-y-1">
            <h2 class="text-base font-bold text-white font-mono uppercase tracking-wider">
                Keamanan & Ubah Password
            </h2>
            <p class="text-xs text-slate-400">
                Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-4 text-xs">
            <div class="space-y-1">
                <label for="current_password" class="block text-slate-300 font-medium">Password Saat Ini</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                />
                <div v-if="form.errors.current_password" class="text-red-400 text-[11px] mt-1">
                    {{ form.errors.current_password }}
                </div>
            </div>

            <div class="space-y-1">
                <label for="password" class="block text-slate-300 font-medium">Password Baru</label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                />
                <div v-if="form.errors.password" class="text-red-400 text-[11px] mt-1">
                    {{ form.errors.password }}
                </div>
            </div>

            <div class="space-y-1">
                <label for="password_confirmation" class="block text-slate-300 font-medium">Konfirmasi Password Baru</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                />
                <div v-if="form.errors.password_confirmation" class="text-red-400 text-[11px] mt-1">
                    {{ form.errors.password_confirmation }}
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold text-xs transition shadow-md"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Perbarui Password' }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-400 flex items-center gap-1"
                    >
                        ✓ Password berhasil diperbarui.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
