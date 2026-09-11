<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Admin Login - Tactical Multiview" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4 selection:bg-cyan-500 selection:text-black relative overflow-hidden">
        <!-- Background Grid Effect -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b15_1px,transparent_1px),linear-gradient(to_bottom,#1e293b15_1px,transparent_1px)] bg-[size:4rem_4rem] pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10 space-y-6">
            <!-- Brand / Header -->
            <div class="text-center space-y-2">
                <Link href="/" class="inline-flex items-center justify-center">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-cyan-600 to-blue-500 flex items-center justify-center shadow-xl shadow-cyan-500/20 border border-cyan-400/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                </Link>
                <h1 class="text-xl font-black text-white font-mono tracking-wider uppercase">
                    Admin Dispatch Login
                </h1>
                <p class="text-xs text-slate-400 font-sans">
                    Tactical Police Multiview Management Portal
                </p>
            </div>

            <!-- Login Card -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-md space-y-5">
                <div v-if="status" class="p-3 rounded-xl bg-emerald-950/80 border border-emerald-600/50 text-emerald-300 text-xs font-mono">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4 text-xs font-mono">
                    <!-- Email -->
                    <div class="space-y-1">
                        <label for="email" class="block text-slate-300 font-bold">Email Dispatcher</label>
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            placeholder="admin@example.com"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-inner"
                        />
                        <div v-if="form.errors.email" class="text-red-400 text-[11px] mt-1">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1">
                        <label for="password" class="block text-slate-300 font-bold">Password</label>
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            placeholder="••••••••"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-inner"
                        />
                        <div v-if="form.errors.password" class="text-red-400 text-[11px] mt-1">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded bg-slate-950 border-slate-800 text-cyan-600 focus:ring-cyan-500 w-4 h-4"
                            />
                            <span class="text-slate-400 text-xs">Ingat saya</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 disabled:opacity-50 text-white font-bold font-mono text-xs shadow-lg shadow-cyan-600/30 transition flex items-center justify-center space-x-2"
                        >
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>{{ form.processing ? 'Memverifikasi Akun...' : 'Masuk ke Admin Portal' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Back to Public Dashboard -->
            <div class="text-center">
                <Link href="/" class="text-xs text-slate-500 hover:text-cyan-400 transition font-mono flex items-center justify-center space-x-1">
                    <span>← Kembali ke Dashboard Publik</span>
                </Link>
            </div>
        </div>
    </div>
</template>
