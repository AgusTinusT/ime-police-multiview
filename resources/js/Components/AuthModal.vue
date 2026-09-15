<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import iconUser from '@/Components/Icons/user-svgrepo-com.svg';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const activeTab = ref('login'); // 'login' or 'register'

// Login Form
const loginForm = useForm({
    email: '',
    password: '',
    remember: true,
});

// Register Form
const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const handleLogin = () => {
    loginForm.post('/login', {
        onSuccess: () => {
            loginForm.reset('password');
            emit('close');
        },
        onError: () => {
            // Keep modal open to show validation errors
        },
    });
};

const handleRegister = () => {
    registerForm.post('/register', {
        onSuccess: () => {
            registerForm.reset('password', 'password_confirmation');
            emit('close');
        },
        onError: () => {
            // Keep modal open to show validation errors
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200">
            <!-- Modal Container -->
            <div class="relative w-full max-w-md bg-slate-950 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden font-sans text-slate-100">
                
                <!-- Modal Header -->
                <div class="bg-[#0b1320] px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-600/20 border border-blue-500/40 flex items-center justify-center text-blue-400">
                            <img :src="iconUser" class="w-4 h-4 invert opacity-90" alt="User" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold font-mono tracking-wider text-slate-100 uppercase leading-none">IME POLICE ACCOUNT</h3>
                            <span class="text-[10px] text-slate-400 font-sans leading-tight">Masuk atau daftar untuk sinkronisasi cloud</span>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                        ✕
                    </button>
                </div>

                <!-- Tab Switcher (Login vs Register) -->
                <div class="flex border-b border-slate-800/80 bg-slate-900/50">
                    <button 
                        @click="activeTab = 'login'" 
                        :class="activeTab === 'login' ? 'border-b-2 border-blue-500 text-blue-400 font-bold bg-slate-900/90' : 'text-slate-400 hover:text-slate-200'"
                        class="flex-1 py-2.5 text-xs font-mono text-center uppercase tracking-wider transition"
                    >
                        🔑 Sign In
                    </button>
                    <button 
                        @click="activeTab = 'register'" 
                        :class="activeTab === 'register' ? 'border-b-2 border-blue-500 text-blue-400 font-bold bg-slate-900/90' : 'text-slate-400 hover:text-slate-200'"
                        class="flex-1 py-2.5 text-xs font-mono text-center uppercase tracking-wider transition"
                    >
                        ✨ Register
                    </button>
                </div>

                <!-- Form Body -->
                <div class="p-6">
                    <!-- LOGIN FORM -->
                    <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Email Address</label>
                            <input 
                                v-model="loginForm.email"
                                type="email"
                                required
                                placeholder="perwira@ime-rp.com"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <span v-if="loginForm.errors.email" class="text-[11px] text-red-400 mt-1 block">{{ loginForm.errors.email }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Password</label>
                            <input 
                                v-model="loginForm.password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <span v-if="loginForm.errors.password" class="text-[11px] text-red-400 mt-1 block">{{ loginForm.errors.password }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="flex items-center space-x-2 text-slate-400 cursor-pointer">
                                <input type="checkbox" v-model="loginForm.remember" class="rounded bg-slate-900 border-slate-800 text-blue-600 focus:ring-blue-500" />
                                <span>Remember me</span>
                            </label>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="loginForm.processing"
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center justify-center space-x-2 disabled:opacity-50"
                        >
                            <span>{{ loginForm.processing ? 'Signing in...' : 'Sign In to Account' }}</span>
                        </button>
                    </form>

                    <!-- REGISTER FORM -->
                    <form v-else @submit.prevent="handleRegister" class="space-y-4">
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Full Name / Callname</label>
                            <input 
                                v-model="registerForm.name"
                                type="text"
                                required
                                placeholder="Agus Tinus Turnip"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <span v-if="registerForm.errors.name" class="text-[11px] text-red-400 mt-1 block">{{ registerForm.errors.name }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Email Address</label>
                            <input 
                                v-model="registerForm.email"
                                type="email"
                                required
                                placeholder="nama@email.com"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <span v-if="registerForm.errors.email" class="text-[11px] text-red-400 mt-1 block">{{ registerForm.errors.email }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Password</label>
                            <input 
                                v-model="registerForm.password"
                                type="password"
                                required
                                placeholder="Minimal 8 karakter"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <span v-if="registerForm.errors.password" class="text-[11px] text-red-400 mt-1 block">{{ registerForm.errors.password }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Confirm Password</label>
                            <input 
                                v-model="registerForm.password_confirmation"
                                type="password"
                                required
                                placeholder="Ulangi password"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        <button 
                            type="submit" 
                            :disabled="registerForm.processing"
                            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-2 disabled:opacity-50"
                        >
                            <span>{{ registerForm.processing ? 'Creating Account...' : 'Create Member Account' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Footer Info -->
                <div class="bg-slate-900/60 px-5 py-3 border-t border-slate-800/80 text-[11px] text-slate-400 text-center">
                    <span>Cloud Watchlist & Multi-device Sync • IME Roleplay Community</span>
                </div>
            </div>
        </div>
    </Teleport>
</template>
