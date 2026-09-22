<template>
    <TacticalLayout>
        <Head title="Tactical Video Clipper - IME Police Multiview" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            
            <!-- Header Title Banner -->
            <div class="relative overflow-hidden rounded-2xl border border-blue-500/30 bg-gradient-to-r from-blue-950/40 via-slate-900/90 to-slate-950 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-md">
                <div class="absolute -right-10 -top-10 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500/30 to-blue-700/20 border border-blue-500/40 shadow-inner shrink-0 p-2.5">
                            <img :src="iconCut" class="w-full h-full object-contain filter invert opacity-95" alt="Cut Icon" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-xl sm:text-2xl font-black tracking-wide text-white uppercase font-sans">
                                    Tactical Video Clipper Studio
                                </h1>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                                Pemutar preview YouTube interaktif untuk memotong segmen video kejadian (Maksimal 10 Menit).
                            </p>
                        </div>
                    </div>

                    <!-- Quick Navigation Badges -->
                    <div class="flex items-center gap-2 text-xs font-mono text-slate-400">
                        <span class="px-3 py-1.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Batas Maksimal: 10 Menit
                        </span>
                    </div>
                </div>
            </div>

            <!-- Notification Messages -->
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="formError" class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm flex items-center justify-between gap-3 shadow-lg">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ formError }}</span>
                    </div>
                    <button @click="formError = ''" class="text-red-400 hover:text-white text-xs">✕</button>
                </div>
            </transition>

            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="successMessage" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between gap-3 shadow-lg">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ successMessage }}</span>
                    </div>
                    <button @click="successMessage = ''" class="text-emerald-400 hover:text-white text-xs">✕</button>
                </div>
            </transition>

            <!-- Main Workspace: Left (YouTube Interactive Player) + Right (Control Trimmer Form) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- LEFT COLUMN: YOUTUBE PREVIEW PLAYER & TIMESTAMP EXTRACTION CONTROLS (8 cols) -->
                <div class="lg:col-span-7 xl:col-span-8 space-y-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl overflow-hidden flex flex-col">
                        
                        <!-- Player Header Bar -->
                        <div class="px-5 py-3.5 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="activeVideoId ? 'bg-blue-500 animate-ping' : 'bg-slate-600'"></span>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-300 font-sans">
                                    Preview Video YouTube
                                </h2>
                            </div>
                            <span v-if="activeVideoId" class="text-xs font-mono text-blue-400 bg-blue-950/60 border border-blue-800/50 px-2 py-0.5 rounded-md">
                                ID: {{ activeVideoId }}
                            </span>
                        </div>

                        <!-- YouTube Embed Iframe or Placeholder -->
                        <div class="relative w-full aspect-video bg-black flex items-center justify-center overflow-hidden">
                            <template v-if="activeVideoId">
                                <iframe
                                    ref="ytIframe"
                                    id="youtube-preview-player"
                                    :src="`https://www.youtube.com/embed/${activeVideoId}?enablejsapi=1&autoplay=1&rel=0`"
                                    class="w-full h-full border-0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </template>

                            <template v-else>
                                <div class="text-center px-6 py-12 space-y-3">
                                    <div class="w-16 h-16 rounded-2xl bg-blue-950/40 border border-blue-500/30 text-blue-400 flex items-center justify-center mx-auto shadow-inner p-3.5">
                                        <img :src="iconCut" class="w-full h-full object-contain filter invert opacity-80" alt="" />
                                    </div>
                                    <h3 class="text-sm font-bold text-white">Preview YouTube Belum Dimuat</h3>
                                    <p class="text-xs text-slate-400 max-w-sm">
                                        Masukkan link YouTube atau pilih stream dari CCTV Multiview untuk memuat video preview interaktif di sini.
                                    </p>
                                </div>
                            </template>
                        </div>

                        <!-- Player Toolbar & Timestamp Sync Triggers -->
                        <div class="p-4 bg-slate-950/90 border-t border-slate-800 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400 font-medium flex items-center gap-1.5">
                                        <img :src="iconTime" class="w-3.5 h-3.5 invert opacity-80" alt="" />
                                        <span>Waktu Saat Ini:</span>
                                    </span>
                                    <span class="font-mono text-slate-200 bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800 font-bold">
                                        {{ formatSecondsToTimestamp(playerCurrentSeconds) }}
                                    </span>
                                </div>

                                <!-- Seek Controls -->
                                <div class="flex items-center gap-1.5 font-mono">
                                    <button @click="seekPlayer(-30)" :disabled="!activeVideoId" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 disabled:opacity-50 transition text-xs">
                                        -30s
                                    </button>
                                    <button @click="seekPlayer(-10)" :disabled="!activeVideoId" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 disabled:opacity-50 transition text-xs">
                                        -10s
                                    </button>
                                    <button @click="seekPlayer(10)" :disabled="!activeVideoId" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 disabled:opacity-50 transition text-xs">
                                        +10s
                                    </button>
                                    <button @click="seekPlayer(30)" :disabled="!activeVideoId" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 disabled:opacity-50 transition text-xs">
                                        +30s
                                    </button>
                                </div>
                            </div>

                            <!-- Capture Timestamp Action Buttons -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <button
                                    @click="captureStartTime"
                                    :disabled="!activeVideoId"
                                    class="py-2.5 px-4 rounded-xl bg-blue-950/80 hover:bg-blue-900 border border-blue-500/40 text-blue-300 font-bold text-xs shadow transition flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <img :src="iconTrimStart" class="w-4 h-4 invert opacity-90" alt="" />
                                    <span>Set Waktu Mulai (Start Time)</span>
                                </button>

                                <button
                                    @click="captureEndTime"
                                    :disabled="!activeVideoId"
                                    class="py-2.5 px-4 rounded-xl bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-300 font-bold text-xs shadow transition flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <img :src="iconTrimEnd" class="w-4 h-4 invert opacity-90" alt="" />
                                    <span>Set Waktu Selesai (End Time)</span>
                                </button>
                            </div>

                            <!-- Preset Duration Helpers -->
                            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-900 font-sans">
                                <span>Durasi Cepat dari Waktu Mulai:</span>
                                <div class="flex items-center gap-1.5 font-mono">
                                    <button @click="setPresetDuration(60)" class="px-2.5 py-0.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800">
                                        1 Menit
                                    </button>
                                    <button @click="setPresetDuration(180)" class="px-2.5 py-0.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800">
                                        3 Menit
                                    </button>
                                    <button @click="setPresetDuration(300)" class="px-2.5 py-0.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800">
                                        5 Menit
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- RIGHT COLUMN: TRIMMER FORM PARAMETERS (5 cols) -->
                <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-300 font-sans flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Pengaturan Potongan
                            </h2>
                        </div>

                        <!-- YouTube URL Input -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                URL Video YouTube <span class="text-blue-400">*</span>
                            </label>
                            <input 
                                v-model="form.youtube_url"
                                type="text"
                                placeholder="https://www.youtube.com/watch?v=..."
                                class="w-full rounded-xl bg-slate-950 border border-slate-700/80 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder:text-slate-600 font-mono"
                            />
                        </div>

                        <!-- Judul Klip / Catatan Incident -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                Judul / Catatan Klip (Opsional)
                            </label>
                            <input 
                                v-model="form.title"
                                type="text"
                                placeholder="Contoh: Kejadian Pengejaran di Highway 1"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700/80 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder:text-slate-600 text-xs"
                            />
                        </div>

                        <!-- Timestamps (Start & End) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                    Waktu Mulai <span class="text-blue-400">*</span>
                                </label>
                                <input 
                                    v-model="form.start_time"
                                    type="text"
                                    placeholder="00:01:30"
                                    class="w-full rounded-xl bg-slate-950 border border-slate-700/80 px-3 py-2 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-mono"
                                />
                                <span class="text-[10px] text-slate-500">Format: HH:MM:SS</span>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                    Waktu Selesai <span class="text-blue-400">*</span>
                                </label>
                                <input 
                                    v-model="form.end_time"
                                    type="text"
                                    placeholder="00:06:30"
                                    class="w-full rounded-xl bg-slate-950 border border-slate-700/80 px-3 py-2 text-sm text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition font-mono"
                                />
                                <span class="text-[10px] text-slate-500">Format: HH:MM:SS</span>
                            </div>
                        </div>

                        <!-- Calculated Duration Badge -->
                        <div class="rounded-xl border p-3.5 space-y-1.5"
                             :class="isDurationValid ? 'bg-emerald-950/20 border-emerald-500/30 text-emerald-300' : 'bg-red-950/20 border-red-500/30 text-red-300'">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium">Durasi Potongan:</span>
                                <span class="font-bold font-mono text-sm">{{ formattedDuration }}</span>
                            </div>
                            <!-- Visual Timeline Progress Bar -->
                            <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                <div 
                                    class="h-full transition-all duration-300"
                                    :class="isDurationValid ? 'bg-emerald-500' : 'bg-red-500'"
                                    :style="{ width: `${Math.min(100, (calculatedSeconds / 600) * 100)}%` }"
                                ></div>
                            </div>
                            <div v-if="calculatedSeconds > 600" class="text-[11px] text-red-400 font-semibold">
                                Melebihi batas maksimal 10 menit (600 detik)!
                            </div>
                        </div>

                        <!-- Action Submit Button -->
                        <button 
                            @click="submitTrim"
                            :disabled="isSubmitting || !isDurationValid"
                            class="w-full py-3.5 px-6 rounded-xl font-bold text-sm tracking-wide bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white shadow-lg shadow-blue-950/50 border border-blue-500/40 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
                        >
                            <svg v-if="isSubmitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <template v-else>
                                <img :src="iconCut" class="w-4 h-4 invert" alt="" />
                                <span>Potong & Simpan Klip Video</span>
                            </template>
                        </button>
                    </div>
                </div>

            </div>

            <!-- BOTTOM SECTION: DAFTAR KLIP SAYA (CLIPS LIBRARY) -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center border border-blue-500/30 p-1.5">
                            <img :src="iconCloud" class="w-full h-full object-contain filter invert opacity-90" alt="" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                Daftar Klip Video
                                <span v-if="clipsList.length" class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">
                                    {{ clipsList.length }} Klip
                                </span>
                            </h2>
                            <p class="text-xs text-slate-400">Klip video yang telah Anda buat dan siap diunduh.</p>
                        </div>
                    </div>

                    <button 
                        @click="fetchClips"
                        class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition flex items-center gap-1.5"
                    >
                        <img :src="iconRefresh" class="w-3.5 h-3.5 invert opacity-80" :class="isLoadingClips ? 'animate-spin' : ''" alt="" />
                        <span>Refresh List</span>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoadingClips && clipsList.length === 0" class="py-12 text-center text-slate-400 text-xs">
                    <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memuat daftar klip video...
                </div>

                <!-- Empty State -->
                <div v-else-if="clipsList.length === 0" class="py-12 text-center text-slate-400 text-xs bg-slate-950/40 rounded-xl border border-slate-800/80 space-y-2">
                    <p class="font-medium text-slate-300">Belum ada klip video yang disimpan.</p>
                    <p class="text-slate-500">Gunakan form di atas untuk mulai memotong klip video dari YouTube.</p>
                </div>

                <!-- Clips Grid List -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="clip in clipsList" 
                        :key="clip.id"
                        class="p-4 rounded-xl bg-slate-950 border border-slate-800/90 flex flex-col justify-between space-y-3 hover:border-slate-700 transition shadow-lg group"
                    >
                        <div class="space-y-1.5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-sm text-white line-clamp-1 group-hover:text-blue-400 transition">
                                    {{ clip.title }}
                                </h3>
                                
                                <!-- Status Badge -->
                                <span v-if="clip.status === 'completed'" class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    Selesai
                                </span>
                                <span v-else-if="clip.status === 'processing'" class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse">
                                    Memproses...
                                </span>
                                <span v-else-if="clip.status === 'pending'" class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                    Antrean
                                </span>
                                <span v-else class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500/20 text-red-400 border border-red-500/30">
                                    Gagal
                                </span>
                            </div>

                            <p class="text-xs text-slate-400 font-mono truncate">
                                {{ clip.youtube_url }}
                            </p>

                            <div class="flex items-center gap-2 text-[11px] text-slate-400 font-mono pt-1">
                                <span>Durasi: {{ formatSeconds(clip.duration_seconds) }}</span>
                                <span>•</span>
                                <span>{{ new Date(clip.created_at).toLocaleDateString('id-ID') }}</span>
                            </div>
                        </div>

                        <!-- Error Banner if failed or missing -->
                        <div v-if="clip.status === 'failed' && clip.error_message" class="text-[10px] p-2 rounded-lg bg-red-950/40 border border-red-900/50 text-red-400 font-mono break-words">
                            {{ clip.error_message }}
                        </div>
                        <div v-else-if="clip.status === 'completed' && !clip.download_url" class="text-[10px] p-2 rounded-lg bg-amber-950/40 border border-amber-900/50 text-amber-400 font-mono">
                            File video tidak ditemukan di server. Silakan hapus item ini dan coba potong ulang.
                        </div>

                        <!-- HTML5 Video Player Preview (if completed & available) -->
                        <div v-if="clip.status === 'completed' && clip.download_url" class="space-y-2">
                            <video controls class="w-full max-h-48 rounded-lg bg-black border border-slate-800">
                                <source :src="clip.download_url" type="video/mp4">
                                Browser Anda tidak mendukung HTML5 Video.
                            </video>
                        </div>

                        <!-- Card Footer Controls -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-900">
                            <button 
                                @click="deleteClip(clip.id)" 
                                class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-900 transition text-xs flex items-center gap-1.5"
                                title="Hapus Klip"
                            >
                                <img :src="iconDelete" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="" />
                                <span>Hapus</span>
                            </button>

                            <a 
                                v-if="clip.status === 'completed' && clip.download_url"
                                :href="clip.direct_download_url || clip.download_url" 
                                download 
                                target="_blank"
                                class="py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition flex items-center gap-1.5 shadow"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3m0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Unduh MP4</span>
                            </a>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </TacticalLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import axios from 'axios';

import iconCut from '@/Components/Icons/cut-svgrepo-com.svg';
import iconTrimStart from '@/Components/Icons/trim-start-svgrepo-com.svg';
import iconTrimEnd from '@/Components/Icons/trim-end-svgrepo-com.svg';
import iconCloud from '@/Components/Icons/cloud-svgrepo-com.svg';
import iconDelete from '@/Components/Icons/delete-2-svgrepo-com.svg';
import iconRefresh from '@/Components/Icons/refresh-cw-svgrepo-com.svg';
import iconTime from '@/Components/Icons/time-svgrepo-com.svg';

const props = defineProps({
    initialUrl: String,
});

const isSubmitting = ref(false);
const isLoadingClips = ref(false);
const formError = ref('');
const successMessage = ref('');
const clipsList = ref([]);
const playerCurrentSeconds = ref(0);
let pollTimer = null;

const form = ref({
    youtube_url: '',
    title: '',
    start_time: '00:00:00',
    end_time: '00:05:00',
});

// Parse YouTube Video ID from standard / embed / short links
const activeVideoId = computed(() => {
    const url = form.value.youtube_url || '';
    if (!url) return '';
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : '';
});

// Setup YouTube Player Iframe API Listener via postMessage & Polling
let ytTimeTimer = null;

function initYTMessageListener() {
    window.addEventListener('message', (event) => {
        if (!event.data) return;
        let data = event.data;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { return; }
        }
        if (data && (data.event === 'infoDelivery' || data.info)) {
            const info = data.info || data;
            if (info && typeof info.currentTime === 'number') {
                playerCurrentSeconds.value = Math.floor(info.currentTime);
            }
        }
    });
}

function startYTTimePolling() {
    stopYTTimePolling();
    ytTimeTimer = setInterval(() => {
        const iframe = document.getElementById('youtube-preview-player');
        if (iframe && iframe.contentWindow && activeVideoId.value) {
            try {
                // Send listening handshake & getCurrentTime request to YouTube iframe
                iframe.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: 1, channel: 'widget' }), '*');
                iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'getCurrentTime', args: [] }), '*');
            } catch (e) {}
        }
    }, 400);
}

function stopYTTimePolling() {
    if (ytTimeTimer) {
        clearInterval(ytTimeTimer);
        ytTimeTimer = null;
    }
}

watch(activeVideoId, (newId) => {
    playerCurrentSeconds.value = 0;
    if (newId) {
        startYTTimePolling();
    } else {
        stopYTTimePolling();
    }
});

// Convert timestamp string (HH:MM:SS, MM:SS, or seconds) to seconds
function parseTimeToSeconds(val) {
    if (!val) return 0;
    if (!isNaN(val)) return parseInt(val, 10);
    const parts = String(val).trim().split(':').map(Number);
    if (parts.length === 3) return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
    if (parts.length === 2) return (parts[0] * 60) + parts[1];
    if (parts.length === 1) return parts[0] || 0;
    return 0;
}

// Format seconds into HH:MM:SS timestamp
function formatSecondsToTimestamp(totalSecs) {
    if (isNaN(totalSecs) || totalSecs < 0) return '00:00:00';
    const hrs = Math.floor(totalSecs / 3600);
    const mins = Math.floor((totalSecs % 3600) / 60);
    const secs = totalSecs % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(hrs)}:${pad(mins)}:${pad(secs)}`;
}

const calculatedSeconds = computed(() => {
    const start = parseTimeToSeconds(form.value.start_time);
    const end = parseTimeToSeconds(form.value.end_time);
    return end > start ? end - start : 0;
});

const isDurationValid = computed(() => {
    return calculatedSeconds.value > 0 && calculatedSeconds.value <= 600;
});

const formattedDuration = computed(() => {
    const secs = calculatedSeconds.value;
    if (secs <= 0) return '00:00 (Waktu Tidak Valid)';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m} menit ${s} detik (${secs}d)`;
});

function formatSeconds(secs) {
    if (!secs) return '0d';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}m ${s}s`;
}

// Capture timestamp triggers
function captureStartTime() {
    form.value.start_time = formatSecondsToTimestamp(playerCurrentSeconds.value);
}

function captureEndTime() {
    form.value.end_time = formatSecondsToTimestamp(playerCurrentSeconds.value);
}

function setPresetDuration(secondsOffset) {
    const startSecs = parseTimeToSeconds(form.value.start_time);
    form.value.end_time = formatSecondsToTimestamp(startSecs + secondsOffset);
}

function seekPlayer(secondsDelta) {
    const iframe = document.getElementById('youtube-preview-player');
    if (!iframe) return;
    const targetTime = Math.max(0, playerCurrentSeconds.value + secondsDelta);
    iframe.contentWindow.postMessage(JSON.stringify({
        event: 'command',
        func: 'seekTo',
        args: [targetTime, true]
    }), '*');
    playerCurrentSeconds.value = targetTime;
}

async function submitTrim() {
    if (!isDurationValid.value) {
        formError.value = 'Durasi pemotongan tidak valid atau melebihi batas 10 menit (600 detik).';
        return;
    }

    isSubmitting.value = true;
    formError.value = '';
    successMessage.value = '';

    try {
        const response = await axios.post('/api/v1/clips/trim', form.value);
        successMessage.value = response.data.message || 'Proses pemotongan video telah dimasukkan ke dalam antrean!';
        await fetchClips();
    } catch (err) {
        if (err.response && err.response.data && err.response.data.message) {
            formError.value = err.response.data.message;
        } else if (err.response && err.response.data && err.response.data.errors) {
            const firstErr = Object.values(err.response.data.errors)[0];
            formError.value = Array.isArray(firstErr) ? firstErr[0] : firstErr;
        } else {
            formError.value = 'Terjadi kesalahan saat membuat klip video.';
        }
    } finally {
        isSubmitting.value = false;
    }
}

async function fetchClips() {
    isLoadingClips.value = true;
    try {
        const response = await axios.get('/api/v1/clips');
        clipsList.value = response.data.data || response.data || [];
    } catch (err) {
        console.error('Failed to fetch clips list:', err);
    } finally {
        isLoadingClips.value = false;
    }
}

async function deleteClip(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus klip video ini?')) return;
    try {
        await axios.delete(`/api/v1/clips/${id}`);
        clipsList.value = clipsList.value.filter(c => c.id !== id);
    } catch (err) {
        alert('Gagal menghapus klip video.');
    }
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(() => {
        const hasPendingOrProcessing = clipsList.value.some(c => c.status === 'pending' || c.status === 'processing');
        if (hasPendingOrProcessing) {
            fetchClips();
        }
    }, 5000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onMounted(() => {
    if (props.initialUrl) {
        form.value.youtube_url = props.initialUrl;
    }
    initYTMessageListener();
    if (activeVideoId.value) {
        startYTTimePolling();
    }
    fetchClips();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
    stopYTTimePolling();
});
</script>
