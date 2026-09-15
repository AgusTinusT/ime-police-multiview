<script setup>
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Pengaturan Profil & Akun - SASP Police Multiview" />

    <TacticalLayout>
        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
            
            <!-- User Header Summary Card -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-950 to-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <!-- Avatar Circle -->
                    <div 
                        :class="[
                            'w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-black shadow-lg border-2 shrink-0',
                            user.role === 'admin' 
                                ? 'bg-gradient-to-br from-amber-500 via-yellow-600 to-amber-700 text-slate-950 border-amber-400 shadow-amber-950/60' 
                                : 'bg-gradient-to-br from-blue-600 via-indigo-600 to-slate-800 text-white border-blue-400/80 shadow-blue-950/60'
                        ]"
                    >
                        {{ user.name?.charAt(0).toUpperCase() || 'U' }}
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center space-x-2 flex-wrap gap-1">
                            <h1 class="text-xl font-extrabold text-white leading-tight">
                                {{ user.name }}
                            </h1>
                            <span 
                                :class="[
                                    'px-2.5 py-0.5 text-[10px] font-extrabold rounded-full tracking-wider uppercase border',
                                    user.role === 'admin' 
                                        ? 'bg-amber-950/80 text-amber-300 border-amber-500/50 shadow-sm' 
                                        : 'bg-emerald-950/80 text-emerald-300 border-emerald-500/50 shadow-sm'
                                ]"
                            >
                                {{ user.role === 'admin' ? '🛡️ Admin Dispatcher' : '👤 Member User' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-mono">
                            {{ user.email }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 text-xs font-mono text-slate-400 border-t md:border-t-0 md:border-l border-slate-800 pt-3 md:pt-0 md:pl-6">
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase text-slate-500 font-semibold">Status Hak Akses</span>
                        <span class="text-slate-200 font-bold">{{ user.role === 'admin' ? 'Full Command Portal' : 'Community Member' }}</span>
                    </div>
                </div>
            </div>

            <!-- Profile Information Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </div>

            <!-- Update Password Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <UpdatePasswordForm class="max-w-2xl" />
            </div>

            <!-- Delete User Account Form -->
            <div class="bg-slate-900 border border-red-950/60 rounded-2xl p-6 shadow-xl">
                <DeleteUserForm class="max-w-2xl" />
            </div>

        </div>
    </TacticalLayout>
</template>
