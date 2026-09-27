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
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <!-- Avatar Circle -->
                    <div 
                        :class="[
                            'w-16 h-16 rounded-xl flex items-center justify-center text-2xl font-black border shrink-0',
                            user.role === 'admin' 
                                ? 'bg-amber-500/20 text-amber-400 border-amber-500/40' 
                                : 'bg-blue-600/20 text-blue-400 border-blue-500/40'
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
                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-extrabold rounded-md tracking-wider uppercase border',
                                    user.role === 'admin' 
                                        ? 'bg-amber-950/80 text-amber-300 border-amber-500/50' 
                                        : 'bg-emerald-950/80 text-emerald-300 border-emerald-500/50'
                                ]"
                            >
                                <svg 
                                    v-if="user.role === 'admin'"
                                    class="w-3 h-3 text-amber-400 shrink-0" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <svg 
                                    v-else
                                    class="w-3 h-3 text-emerald-400 shrink-0" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ user.role === 'admin' ? 'Admin Dispatcher' : 'Member User' }}
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
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </div>

            <!-- Update Password Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                <UpdatePasswordForm class="max-w-2xl" />
            </div>

            <!-- Delete User Account Form -->
            <div class="bg-slate-900 border border-red-900/50 rounded-xl p-6">
                <DeleteUserForm class="max-w-2xl" />
            </div>

        </div>
    </TacticalLayout>
</template>
