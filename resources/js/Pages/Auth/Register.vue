<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register - SASP Police Multiview" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4 selection:bg-blue-500 selection:text-white font-sans text-slate-100">
        <div class="w-full max-w-sm space-y-6">
            <!-- Plain Logo & Header -->
            <div class="text-center space-y-2.5">
                <Link href="/" class="inline-block">
                    <img :src="logoSaspColor" class="w-14 h-14 rounded-full mx-auto" alt="SASP Logo" />
                </Link>
                <div>
                    <h1 class="text-base font-bold text-white font-mono tracking-wider uppercase">
                        SASP POLICE MULTIVIEW
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        IME Roleplay Community
                    </p>
                </div>
            </div>

            <!-- Card Container -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                <!-- Tab Switcher -->
                <div class="flex border-b border-slate-800 pb-3">
                    <Link href="/login" class="flex-1 py-1.5 text-xs font-mono text-center font-medium text-slate-400 hover:text-slate-200 transition">
                        SIGN IN
                    </Link>
                    <span class="flex-1 py-1.5 text-xs font-mono text-center font-bold text-blue-400 border-b-2 border-blue-500">
                        REGISTER
                    </span>
                </div>

                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div class="space-y-1">
                        <label for="name" class="block text-slate-300 font-medium">Nama Lengkap</label>
                        <input
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                            autofocus
                            placeholder="John Doe"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                        <div v-if="form.errors.name" class="text-red-400 text-[11px] mt-1">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="email" class="block text-slate-300 font-medium">Email Address</label>
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            placeholder="nama@email.com"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                        <div v-if="form.errors.email" class="text-red-400 text-[11px] mt-1">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="password" class="block text-slate-300 font-medium">Password</label>
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            placeholder="Minimal 8 karakter"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                        <div v-if="form.errors.password" class="text-red-400 text-[11px] mt-1">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="password_confirmation" class="block text-slate-300 font-medium">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            v-model="form.password_confirmation"
                            required
                            placeholder="Ulangi password"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                        <div v-if="form.errors.password_confirmation" class="text-red-400 text-[11px] mt-1">
                            {{ form.errors.password_confirmation }}
                        </div>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold text-xs transition shadow-sm"
                        >
                            {{ form.processing ? 'Creating Account...' : 'Register' }}
                        </button>
                    </div>
                </form>

                <div class="pt-3 border-t border-slate-800/80 text-center">
                    <p class="text-xs text-slate-400">
                        Sudah memiliki akun?
                        <Link href="/login" class="text-blue-400 hover:text-blue-300 font-semibold ml-1">
                            Masuk di sini
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Minimalist Back Link -->
            <div class="text-center">
                <Link href="/" class="text-xs text-slate-400 hover:text-slate-200 transition">
                    ← Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
