<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";

// SVG Icon Assets & Branding Logos
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";

// Sub-components
import UserAccountMenu from "@/Components/UserAccountMenu.vue";

defineProps({
    isDataSaverEnabled: {
        type: Boolean,
        default: false,
    },
    isSyncingFeeds: {
        type: Boolean,
        default: false,
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "enable-data-saver",
    "disable-data-saver",
    "trigger-sync",
    "toggle-fullscreen",
]);

const page = usePage();
const isUrlActive = (path) => {
    const url = page.url || "";
    if (path === "/") {
        return (
            url === "/" || url === "/dashboard" || url.startsWith("/dashboard?")
        );
    }
    return url.startsWith(path);
};

// Bottom Sheet Mobile Drawer State
const isBottomSheetOpen = ref(false);

const openBottomSheet = () => {
    isBottomSheetOpen.value = true;
    document.body.style.overflow = "hidden";
};

const closeBottomSheet = () => {
    isBottomSheetOpen.value = false;
    document.body.style.overflow = "";
};

const handleKeyDown = (e) => {
    if (e.key === "Escape" && isBottomSheetOpen.value) {
        closeBottomSheet();
    }
};

const handleResize = () => {
    if (window.innerWidth >= 768 && isBottomSheetOpen.value) {
        closeBottomSheet();
    }
};

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyDown);
    window.removeEventListener("resize", handleResize);
    document.body.style.overflow = "";
});
</script>

<template>
    <!-- 1. TOP HEADER (DESKTOP FULL & MOBILE COMPACT) -->
    <header
        class="bg-slate-950/90 backdrop-blur-md border-b border-slate-800 px-3 sm:px-4 lg:px-8 py-2.5 sm:py-3 sticky top-0 z-40"
    >
        <div
            class="max-w-screen-2xl mx-auto w-full flex items-center justify-between gap-2"
        >
            <!-- Left Area: Branding & Logo -->
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 shrink-0">
                <Link
                    href="/"
                    class="flex items-center space-x-2 shrink-0 group cursor-pointer"
                    title="Halaman Beranda Dashboard"
                >
                    <div
                        class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-md bg-slate-900 border border-slate-800 group-hover:border-slate-700 p-1 overflow-hidden transition"
                    >
                        <img
                            :src="logoSaspColor"
                            class="w-full h-full object-contain rounded"
                            alt="SASP Badge"
                        />
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-[11px] sm:text-xs font-black tracking-wider text-blue-400 group-hover:text-blue-300 uppercase leading-tight transition"
                        >
                            IME ROLEPLAY
                        </span>
                        <span
                            class="text-[9px] sm:text-[10px] font-bold tracking-wide text-slate-300 group-hover:text-white uppercase leading-tight transition"
                        >
                            POLICE DUTY
                        </span>
                    </div>
                </Link>

                <!-- Vertical Divider (Visible on desktop) -->
                <div class="h-5 w-px bg-slate-800 hidden md:block"></div>

                <!-- Desktop Navigation Links (>= md) -->
                <nav class="hidden md:flex items-center space-x-1 shrink-0">
                    <Link
                        href="/"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-md transition shrink-0',
                            isUrlActive('/')
                                ? 'text-white bg-blue-600'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Home Discovery Hub"
                    >
                        <span>Home</span>
                    </Link>

                    <Link
                        href="/multiview"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-md transition shrink-0 flex items-center gap-1.5',
                            isUrlActive('/multiview')
                                ? 'text-white bg-blue-600'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Stand-alone Multiview Stage"
                    >
                        <span>Multiview</span>
                    </Link>

                    <Link
                        href="/action-clips"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/action-clips') || isUrlActive('/clips')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="TacClips — Kumpulan Klip Momen Aksi Kepolisian"
                    >
                        <span>TacClips</span>
                    </Link>

                    <Link
                        href="/officers"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/officers')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Officers"
                    >
                        <span>Officers</span>
                    </Link>

                    <Link
                        href="/about"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/about')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="About"
                    >
                        <span>About</span>
                    </Link>

                    <Link
                        href="/faq"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/faq')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="FAQ"
                    >
                        FAQ
                    </Link>

                    <Link
                        href="/feedback"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition shrink-0',
                            isUrlActive('/feedback')
                                ? 'text-white bg-blue-600 font-bold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900',
                        ]"
                        title="Feedback"
                    >
                        Feedback
                    </Link>
                </nav>
            </div>

            <!-- Right Area: Stream Controls & User Account Menu -->
            <div class="flex items-center space-x-2 shrink-0">
                <UserAccountMenu />
            </div>
        </div>
    </header>

    <!-- 2. MOBILE BOTTOM NAVIGATION BAR (FIXED BOTTOM, HIDDEN ON DESKTOP) -->
    <nav
        aria-label="Navigasi Bawah Mobile Takktis"
        class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/95 backdrop-blur-lg border-t border-slate-800/90 shadow-2xl pb-safe"
    >
        <div class="max-w-md mx-auto px-2">
            <div class="flex items-center justify-around h-15">
                <!-- TAB 1: HOME -->
                <Link
                    href="/"
                    :class="[
                        'flex-1 flex flex-col items-center justify-center py-1.5 transition-colors',
                        isUrlActive('/') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <div class="relative p-0.5">
                        <svg class="w-5 h-5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span
                            v-if="isUrlActive('/')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-blue-500"
                        ></span>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5">Home</span>
                </Link>

                <!-- TAB 2: MULTIVIEW -->
                <Link
                    href="/multiview"
                    :class="[
                        'flex-1 flex flex-col items-center justify-center py-1.5 transition-colors',
                        isUrlActive('/multiview') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <div class="relative p-0.5">
                        <svg class="w-5 h-5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span
                            v-if="isUrlActive('/multiview')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-blue-500"
                        ></span>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5">Multiview</span>
                </Link>

                <!-- TAB 3: TACCLIPS -->
                <Link
                    href="/action-clips"
                    :class="[
                        'flex-1 flex flex-col items-center justify-center py-1.5 transition-colors',
                        isUrlActive('/action-clips') || isUrlActive('/clips') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <div class="relative p-0.5">
                        <svg class="w-5 h-5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span
                            v-if="isUrlActive('/action-clips') || isUrlActive('/clips')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-blue-500"
                        ></span>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5">TacClips</span>
                </Link>

                <!-- TAB 4: OFFICERS -->
                <Link
                    href="/officers"
                    :class="[
                        'flex-1 flex flex-col items-center justify-center py-1.5 transition-colors',
                        isUrlActive('/officers') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <div class="relative p-0.5">
                        <svg class="w-5 h-5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span
                            v-if="isUrlActive('/officers')"
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-blue-500"
                        ></span>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5">Officers</span>
                </Link>

                <!-- TAB 5: LAINNYA (MORE / BOTTOM SHEET TRIGGER) -->
                <button
                    type="button"
                    @click="openBottomSheet"
                    class="flex-1 flex flex-col items-center justify-center py-1.5 text-slate-400 hover:text-slate-200 transition-colors cursor-pointer"
                >
                    <div class="relative p-0.5">
                        <svg class="w-5 h-5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5">Lainnya</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- 3. BOTTOM SHEET MODAL (SLIDE UP FOR SECONDARY NAV & QUICK ACCESS) -->
    <Teleport to="body">
        <div
            v-if="isBottomSheetOpen"
            class="fixed inset-0 z-50 flex flex-col justify-end md:hidden"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop Overlay -->
            <div
                class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"
                @click="closeBottomSheet"
            ></div>

            <!-- Bottom Sheet Container Panel -->
            <div
                class="relative z-10 w-full max-w-lg mx-auto bg-slate-900 border-t border-slate-800 rounded-t-3xl shadow-2xl overflow-hidden max-h-[85vh] flex flex-col animate-in slide-in-from-bottom duration-300"
            >
                <!-- Drag Handle -->
                <div class="pt-3 pb-1 flex justify-center cursor-pointer" @click="closeBottomSheet">
                    <div class="w-12 h-1.5 rounded-full bg-slate-700 hover:bg-slate-600 transition-colors"></div>
                </div>

                <!-- Sheet Header -->
                <div class="px-5 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-100 text-sm tracking-wide uppercase font-mono">Navigasi Takktis</h3>
                        <p class="text-xs text-slate-400">Pilih menu atau informasi platform</p>
                    </div>
                    <button
                        type="button"
                        @click="closeBottomSheet"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Sheet Scrollable Content -->
                <div class="p-5 overflow-y-auto space-y-5 pb-8">
                    <!-- Navigasi Utama List -->
                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-1 font-mono">Menu Utama</h4>
                        <div class="space-y-1">
                            <Link
                                href="/"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-200 hover:bg-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    <span>Beranda (Home)</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>

                            <Link
                                href="/multiview"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/multiview') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-200 hover:bg-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                    <span>Multiview Stage</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>

                            <Link
                                href="/action-clips"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/action-clips') || isUrlActive('/clips') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-200 hover:bg-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span>TacClips Video</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>

                            <Link
                                href="/officers"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/officers') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-200 hover:bg-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>Direktori Perwira (Officers)</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Informasi Tambahan List -->
                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-1 font-mono">Informasi & Bantuan</h4>
                        <div class="space-y-1">
                            <Link
                                href="/about"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/about') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-300 hover:bg-slate-800'
                                ]"
                            >
                                <span>Tentang IME Police</span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>

                            <Link
                                href="/faq"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/faq') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-300 hover:bg-slate-800'
                                ]"
                            >
                                <span>Tanya Jawab (FAQ)</span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>

                            <Link
                                href="/feedback"
                                @click="closeBottomSheet"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl transition-colors font-medium text-xs',
                                    isUrlActive('/feedback') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'bg-slate-950/60 text-slate-300 hover:bg-slate-800'
                                ]"
                            >
                                <span>Kirim Masukan (Feedback)</span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Platform Version Footer -->
                    <div class="pt-2 text-center border-t border-slate-800/80">
                        <p class="text-[10px] text-slate-500 font-mono">IME Roleplay Police Duty &bull; Mobile Hybrid Interface</p>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

