<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import iconLogout from "@/Components/Icons/leave-svgrepo-com.svg";
import iconCut from "@/Components/Icons/cut-svgrepo-com.svg";

const isOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

const handleLogout = () => {
    isOpen.value = false;
    router.post("/logout");
};

const openClipperModal = () => {
    isOpen.value = false;
    window.dispatchEvent(
        new CustomEvent("open-clipper-modal", { detail: { url: "" } }),
    );
};

onMounted(() => {
    document.addEventListener("click", closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener("click", closeDropdown);
});
</script>

<template>
    <div ref="dropdownRef" class="relative flex items-center text-left z-50 shrink-0">
        <!-- Logged-in State: Username + Down Arrow Button -->
        <template v-if="$page.props.auth?.user">
            <button
                @click.stop="toggleDropdown"
                class="h-8 flex items-center space-x-1.5 px-3 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 transition group cursor-pointer focus:outline-none shrink-0"
                :title="`Logged in as ${$page.props.auth.user.name} (${$page.props.auth.user.role || 'member'})`"
            >
                <!-- Username -->
                <span
                    class="text-xs font-bold text-slate-200 group-hover:text-white truncate max-w-[110px] sm:max-w-[140px] leading-none"
                >
                    {{ $page.props.auth.user.name }}
                </span>

                <!-- Down Arrow Icon -->
                <svg
                    class="w-3.5 h-3.5 text-slate-400 group-hover:text-white transition-transform duration-200 shrink-0"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <!-- Profile Floating Popover Dropdown Card -->
            <transition
                enter-active-class="transition ease-out duration-150 transform"
                enter-from-class="opacity-0 scale-95 -translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition ease-in duration-100 transform"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 -translate-y-2"
            >
                <div
                    v-if="isOpen"
                    class="absolute right-0 top-full mt-2 w-72 bg-slate-900 border border-slate-800 rounded-xl divide-y divide-slate-800 overflow-hidden font-sans z-50 animate-in"
                >
                    <!-- Header Profile Section -->
                    <div class="p-4 bg-slate-900 flex items-start space-x-3">
                        <div
                            :class="[
                                'w-10 h-10 rounded-md flex items-center justify-center text-sm font-bold shrink-0 border border-slate-800 bg-slate-950',
                                $page.props.auth.user.role === 'admin'
                                    ? 'text-amber-400'
                                    : 'text-blue-400',
                            ]"
                        >
                            {{
                                $page.props.auth.user.name
                                    ?.charAt(0)
                                    .toUpperCase() || "U"
                            }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-white truncate leading-snug">
                                {{ $page.props.auth.user.name }}
                            </h4>
                            <p class="text-xs text-slate-400 truncate mb-1.5 font-mono">
                                {{ $page.props.auth.user.email }}
                            </p>

                            <!-- Role Badge Pill -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-extrabold rounded-md uppercase border border-slate-800 bg-slate-950',
                                    $page.props.auth.user.role === 'admin'
                                        ? 'text-amber-400'
                                        : 'text-emerald-400',
                                ]"
                            >
                                <svg 
                                    v-if="$page.props.auth.user.role === 'admin'"
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
                                {{
                                    $page.props.auth.user.role === "admin"
                                        ? "Admin Dispatcher"
                                        : "Member User"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Navigation Action Links -->
                    <div class="py-1.5 text-xs font-medium bg-slate-900">
                        <!-- Admin Command Hub (Admin Only) -->
                        <Link
                            v-if="$page.props.auth.user.role === 'admin'"
                            href="/admin/officers"
                            @click="isOpen = false"
                            class="flex items-center px-4 py-2.5 text-amber-300 hover:bg-slate-800 hover:text-amber-200 transition gap-2.5 font-semibold"
                        >
                            <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <div class="flex flex-col">
                                <span>Admin Command Hub</span>
                                <span class="text-[10px] text-slate-400 font-normal">
                                    Kelola Perwira & Pengumuman
                                </span>
                            </div>
                        </Link>

                        <!-- Tactical Video Trimmer Studio (Admin / Clipper) -->
                        <Link
                            v-if="
                                $page.props.auth.user.role === 'admin' ||
                                $page.props.auth.user.can_trim_video
                            "
                            href="/clipper"
                            @click="isOpen = false"
                            class="flex items-center px-4 py-2.5 text-slate-200 hover:bg-slate-800 hover:text-white transition gap-2.5 font-semibold group"
                        >
                            <img
                                :src="iconCut"
                                class="w-4 h-4 invert opacity-80 group-hover:opacity-100 transition shrink-0"
                                alt="Tactical Video Trimmer"
                            />
                            <div class="flex flex-col">
                                <span>Tactical Video Trimmer</span>
                                <span class="text-[10px] text-slate-400 font-normal">
                                    Studio Preview & Potong Video YouTube
                                </span>
                            </div>
                        </Link>

                        <!-- Profile Settings -->
                        <Link
                            href="/profile"
                            @click="isOpen = false"
                            class="flex items-center px-4 py-2.5 text-slate-200 hover:bg-slate-800 hover:text-white transition gap-2.5"
                        >
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div class="flex flex-col">
                                <span>Profil & Akun</span>
                                <span class="text-[10px] text-slate-400 font-normal">
                                    Ubah nama, email, & password
                                </span>
                            </div>
                        </Link>
                    </div>

                    <!-- Footer Section: Sign Out Button -->
                    <div class="p-2 bg-slate-950">
                        <button
                            @click="handleLogout"
                            class="w-full flex items-center justify-center space-x-2 px-3 py-2 rounded-md bg-slate-900 hover:bg-red-950/60 text-red-400 hover:text-red-300 border border-slate-800 transition text-xs font-bold group"
                        >
                            <img
                                :src="iconLogout"
                                class="w-4 h-4 invert opacity-80 group-hover:opacity-100 transition"
                                alt="Logout"
                            />
                            <span>Keluar (Sign Out)</span>
                        </button>
                    </div>
                </div>
            </transition>
        </template>

        <!-- Guest State: Sign In Button -->
        <template v-else>
            <Link
                href="/login"
                class="h-8 flex items-center space-x-1.5 px-3 text-xs font-bold rounded-md bg-blue-600 hover:bg-blue-500 text-white transition shrink-0"
            >
                <svg
                    class="w-4 h-4 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                    />
                </svg>
                <span>Sign In</span>
            </Link>
        </template>
    </div>
</template>
