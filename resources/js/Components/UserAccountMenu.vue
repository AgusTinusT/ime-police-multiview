<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import iconLogout from "@/Components/Icons/leave-svgrepo-com.svg";

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
    <div ref="dropdownRef" class="relative inline-block text-left z-50">
        <!-- Logged-in State: YouTube-Style Pure Circular Avatar Button -->
        <template v-if="$page.props.auth?.user">
            <button
                @click.stop="toggleDropdown"
                class="relative rounded-full focus:outline-none group p-0.5 transition transform hover:scale-105"
                :title="`Logged in as ${$page.props.auth.user.name} (${$page.props.auth.user.role || 'member'})`"
            >
                <!-- Avatar Circle with Initial -->
                <div
                    :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-black shadow-lg tracking-wider border-2 transition',
                        $page.props.auth.user.role === 'admin'
                            ? 'bg-gradient-to-br from-amber-500 via-yellow-600 to-amber-700 text-slate-950 border-amber-400 shadow-amber-950/60 group-hover:border-amber-300'
                            : 'bg-gradient-to-br from-blue-600 via-indigo-600 to-slate-800 text-white border-blue-400/80 shadow-blue-950/60 group-hover:border-blue-300',
                    ]"
                >
                    {{
                        $page.props.auth.user.name?.charAt(0).toUpperCase() ||
                        "U"
                    }}
                </div>
            </button>

            <!-- YouTube-Style Floating Popover Dropdown Card -->
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
                    class="absolute right-0 mt-2 w-72 bg-[#0d1527]/95 border border-slate-700/80 rounded-2xl shadow-2xl shadow-black/80 backdrop-blur-xl divide-y divide-slate-800/80 overflow-hidden font-sans z-50 animate-in"
                >
                    <!-- Header Profile Section (YouTube Style) -->
                    <div
                        class="p-4 bg-gradient-to-b from-slate-900/90 to-transparent flex items-start space-x-3"
                    >
                        <div
                            :class="[
                                'w-11 h-11 rounded-full flex items-center justify-center text-base font-black shrink-0 shadow-lg border',
                                $page.props.auth.user.role === 'admin'
                                    ? 'bg-gradient-to-br from-amber-500 via-yellow-600 to-amber-700 text-slate-950 border-amber-300'
                                    : 'bg-gradient-to-br from-blue-600 via-indigo-600 to-slate-800 text-white border-blue-400',
                            ]"
                        >
                            {{
                                $page.props.auth.user.name
                                    ?.charAt(0)
                                    .toUpperCase() || "U"
                            }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4
                                class="text-sm font-bold text-white truncate leading-snug"
                            >
                                {{ $page.props.auth.user.name }}
                            </h4>
                            <p
                                class="text-xs text-slate-400 truncate mb-1.5 font-mono"
                            >
                                {{ $page.props.auth.user.email }}
                            </p>

                            <!-- Role Badge Pill -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-extrabold rounded-full tracking-wider uppercase border',
                                    $page.props.auth.user.role === 'admin'
                                        ? 'bg-amber-950/80 text-amber-300 border-amber-500/50 shadow-sm shadow-amber-900/50'
                                        : 'bg-emerald-950/80 text-emerald-300 border-emerald-500/50 shadow-sm shadow-emerald-900/50',
                                ]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="
                                        $page.props.auth.user.role === 'admin'
                                            ? 'bg-amber-400 animate-pulse'
                                            : 'bg-emerald-400'
                                    "
                                ></span>
                                {{
                                    $page.props.auth.user.role === "admin"
                                        ? "🛡️ Admin Dispatcher"
                                        : "👤 Member User"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Navigation Action Links -->
                    <div class="py-1.5 text-xs font-medium">
                        <!-- Admin Command Hub (Admin Only) -->
                        <Link
                            v-if="$page.props.auth.user.role === 'admin'"
                            href="/admin/officers"
                            @click="isOpen = false"
                            class="flex items-center px-4 py-2.5 text-amber-300 hover:bg-amber-950/40 hover:text-amber-200 transition gap-2.5 font-semibold"
                        >
                            <span class="text-base">🛡️</span>
                            <div class="flex flex-col">
                                <span>Admin Command Hub</span>
                                <span
                                    class="text-[10px] text-amber-400/70 font-normal"
                                    >Kelola Perwira & Pengumuman</span
                                >
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
                            class="flex items-center px-4 py-2.5 text-slate-200 hover:bg-slate-800/70 hover:text-white transition gap-2.5 font-semibold"
                        >
                            <div class="flex flex-col">
                                <span>Tactical Video Trimmer</span>
                                <span
                                    class="text-[10px] text-slate-400 font-normal"
                                    >Studio Preview & Potong Video YouTube</span
                                >
                            </div>
                        </Link>

                        <!-- Profile Settings -->
                        <Link
                            href="/profile"
                            @click="isOpen = false"
                            class="flex items-center px-4 py-2.5 text-slate-200 hover:bg-slate-800/70 hover:text-white transition gap-2.5"
                        >
                            <span class="text-base">⚙️</span>
                            <div class="flex flex-col">
                                <span>Profil & Akun</span>
                                <span
                                    class="text-[10px] text-slate-400 font-normal"
                                    >Ubah nama, email, & password</span
                                >
                            </div>
                        </Link>
                    </div>

                    <!-- Footer Section: Sign Out Button -->
                    <div class="p-2 bg-slate-950/60">
                        <button
                            @click="handleLogout"
                            class="w-full flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-red-950/50 hover:bg-red-900/80 text-red-300 hover:text-white border border-red-800/40 transition text-xs font-bold shadow-md group"
                        >
                            <img
                                :src="iconLogout"
                                class="w-4 h-4 invert opacity-80 group-hover:opacity-100 group-hover:scale-110 transition"
                                alt="Logout"
                            />
                            <span>Keluar (Sign Out)</span>
                        </button>
                    </div>
                </div>
            </transition>
        </template>

        <!-- Guest State: YouTube-Style Sign In Button -->
        <template v-else>
            <Link
                href="/login"
                class="flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-600/30 border border-blue-400/50 transition hover:scale-105"
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
