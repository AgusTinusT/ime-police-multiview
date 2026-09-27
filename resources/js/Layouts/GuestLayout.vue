<script setup>
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-950 p-4 font-sans text-slate-100 selection:bg-blue-500 selection:text-white"
    >
        <div class="w-full max-w-md space-y-6">
            <div class="text-center space-y-2">
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

            <div
                class="bg-slate-900 border border-slate-800 rounded-xl p-6"
            >
                <slot />
            </div>

            <div class="text-center">
                <Link href="/" class="text-xs text-slate-400 hover:text-slate-200 transition">
                    ← Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
