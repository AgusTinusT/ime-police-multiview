<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div
        class="w-full rounded-2xl border border-slate-800 bg-slate-900 p-8 sm:p-12 mb-14 sm:mb-20 lg:mb-24 font-sans text-center relative"
    >
        <div class="max-w-3xl mx-auto space-y-6 flex flex-col items-center">
            <!-- Flat Badge -->
            <div
                class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border border-slate-800 bg-slate-950 text-slate-300 text-[11px] font-mono font-medium"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>AKSES BETA TESTER &bull; GRATIS</span>
            </div>

            <!-- Clean Matte Title -->
            <h2
                class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight text-white font-tactical uppercase leading-tight"
            >
                Clip video kualitas tinggi dengan mudah dan cepat.
            </h2>

            <!-- Clean Description -->
            <p
                class="text-xs sm:text-sm md:text-base text-slate-400 max-w-lg leading-relaxed font-sans"
            >
                Coba Clip Studio tanpa biaya. <br />Dapatkan jatah harian setiap
                hari untuk anda. GRATIS!
            </p>

            <!-- Flat Action Button (No Glow Shadow) -->
            <div class="pt-2">
                <Link
                    href="/clipper"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 rounded-xl text-xs sm:text-sm font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-500 transition-colors font-tactical cursor-pointer"
                >
                    <span>Buka Studio Sekarang</span>
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"
                        />
                    </svg>
                </Link>
            </div>

            <!-- Crisp Flat Micro Feature Highlights Line -->
            <div
                class="w-full pt-6 mt-2 border-t border-slate-800 text-xs font-mono text-slate-400"
            >
                <div
                    class="flex flex-wrap items-center justify-center gap-y-2 gap-x-3 sm:gap-x-5"
                >
                    <span
                        class="inline-flex items-center gap-1.5 text-slate-300"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-blue-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        10 Klip / Hari
                    </span>
                    <span class="text-slate-700 hidden sm:inline">&bull;</span>
                    <span
                        class="inline-flex items-center gap-1.5 text-slate-300"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-blue-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Full HD 1080p
                    </span>
                    <span class="text-slate-700 hidden sm:inline">&bull;</span>
                    <span
                        class="inline-flex items-center gap-1.5 text-slate-300"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-blue-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Maks. 5 Menit
                    </span>
                    <span class="text-slate-700 hidden sm:inline">&bull;</span>
                    <span
                        class="inline-flex items-center gap-1.5 text-slate-300"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-blue-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Youtube live
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
