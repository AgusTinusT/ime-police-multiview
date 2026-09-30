<template>
    <TacticalLayout>
        <Head title="ClipStudio - Tactical Video Clipper & Media Hub" />

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-20 space-y-8 font-sans">
            
            <!-- HEADER / HERO & URL INPUT SECTION -->
            <section class="text-center max-w-3xl mx-auto space-y-4">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-xs font-semibold text-blue-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Pemrosesan Lossless YouTube & Multi-Format (MP4 / WebM / MP3 / GIF)</span>
                </div>

                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight leading-tight uppercase font-tactical">
                    Potong & Klip Video YouTube <br class="hidden sm:inline" />
                    <span class="text-blue-400">Dengan Presisi Milidetik</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-2xl mx-auto">
                    Masukkan URL video YouTube untuk menentukan titik potong, preview klip instan, dan unduh ke format MP4, WebM, MP3 Audio, atau GIF animasi tanpa watermark (Maksimal 10 Menit).
                </p>

                <!-- URL Loader Bar -->
                <div class="pt-2 max-w-2xl mx-auto">
                    <div class="relative flex items-center bg-slate-950 border border-slate-800 rounded-xl p-1.5 focus-within:border-blue-500 transition duration-200">
                        <div class="pl-3.5 text-red-500 shrink-0">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </div>
                        <input
                            v-model="form.youtube_url"
                            type="url"
                            placeholder="Tempel tautan YouTube di sini (contoh: https://www.youtube.com/watch?v=...)"
                            class="w-full bg-transparent border-0 focus:border-0 focus:ring-0 focus:outline-none px-3.5 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 font-mono shadow-none outline-none"
                        />
                        <button
                            @click="loadVideoFromInput"
                            class="px-4 sm:px-6 py-2.5 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-bold transition flex items-center gap-1.5 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Muat Stream</span>
                        </button>
                    </div>

                    <!-- Quick Sample Videos -->
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-400">
                        <span class="text-slate-500">Contoh Stream:</span>
                        <button
                            v-for="(sample, idx) in sampleVideos"
                            :key="idx"
                            @click="setSampleVideo(idx)"
                            class="px-2.5 py-1 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white transition text-xs font-mono"
                        >
                            {{ sample.shortLabel }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- NOTIFICATION BANNERS -->
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="formError" class="p-4 rounded-md bg-slate-950 border border-red-900/50 text-red-400 text-sm flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ formError }}</span>
                    </div>
                    <button @click="formError = ''" class="text-red-400 hover:text-white transition p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>

            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="successMessage" class="p-4 rounded-md bg-slate-950 border border-emerald-900/50 text-emerald-400 text-sm flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ successMessage }}</span>
                    </div>
                    <button @click="successMessage = ''" class="text-emerald-400 hover:text-white transition p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>

            <!-- INTERACTIVE TRIMMER WORKSPACE -->
            <section class="bg-slate-900 border border-slate-800 rounded-xl p-5 sm:p-7 space-y-6">
                
                <!-- Video Details Header Bar -->
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-5 border-b border-slate-800">
                    <div class="flex items-start space-x-4">
                        <div class="relative w-20 h-14 sm:w-28 sm:h-16 rounded-md overflow-hidden bg-black shrink-0 border border-slate-800">
                            <img
                                :src="videoThumb"
                                alt="Video thumbnail"
                                class="w-full h-full object-cover"
                            />
                            <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-sm bg-black/80 text-[10px] font-mono text-white font-semibold">
                                {{ formatSecondsToTimestamp(videoDurationSec).split('.')[0] }}
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-950 text-blue-400 border border-slate-800 font-mono">
                                    {{ activeVideoId ? `YOUTUBE ID: ${activeVideoId}` : 'DEMO SOURCE' }}
                                </span>
                                <span class="text-xs text-slate-400 font-mono">
                                    {{ videoChannel }}
                                </span>
                            </div>
                            <h2 class="text-base sm:text-lg font-bold text-white tracking-tight mt-1 line-clamp-1 font-tactical uppercase">
                                {{ videoTitle }}
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-3 font-mono">
                                <span>Status Pemutar: <strong class="text-emerald-400 font-bold">{{ activeVideoId ? 'YouTube Embed Live' : 'HTML5 Demo' }}</strong></span>
                                <span>•</span>
                                <span>Batas Maksimal: <strong class="text-slate-200">10 Menit (600s)</strong></span>
                            </p>
                        </div>
                    </div>

                    <!-- Clip Range Badge -->
                    <div class="flex items-center gap-2 sm:self-center font-mono">
                        <div class="px-3.5 py-2 rounded-md bg-slate-950 border border-slate-800 text-xs">
                            <span class="text-slate-400">Durasi Potongan:</span>
                            <strong class="text-blue-400 font-bold ml-1.5">
                                {{ formattedDuration }}
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Main Video Preview & Controls Box -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- LEFT COLUMN: VIDEO PLAYER & PREVIEW (8 cols) -->
                    <div class="lg:col-span-8 space-y-4">
                        
                        <!-- Video Canvas / Player Container (Method 2 Custom Player UI) -->
                        <div class="space-y-3">
                            <CustomVideoPlayer
                                ref="mainPlayerRef"
                                :src="form.youtube_url || videoSrc"
                                :youtube-id="activeVideoId"
                                :title="videoTitle"
                                @timeupdate="onMainPlayerTimeUpdate"
                                class="w-full h-full"
                            />

                            <!-- Live Time Controls & Marker Capture Bar -->
                            <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-xs">
                                <!-- Top Control Row -->
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center space-x-1.5 font-mono">
                                        <span class="text-slate-400 text-[11px]">Waktu Pemutar:</span>
                                        <span class="font-bold text-white bg-slate-900 border border-slate-800 px-2 py-0.5 rounded-md font-mono">
                                            {{ formatSecondsToTimestamp(playerCurrentSeconds) }}
                                        </span>
                                    </div>

                                    <!-- Seek Controls -->
                                    <div class="flex items-center gap-1 font-mono">
                                        <button @click="seekPlayer(-30)" class="px-2 py-1 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 transition text-[11px] cursor-pointer" title="Mundur 30 detik">
                                            -30s
                                        </button>
                                        <button @click="seekPlayer(-10)" class="px-2 py-1 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 transition text-[11px] cursor-pointer" title="Mundur 10 detik">
                                            -10s
                                        </button>
                                        <button @click="seekPlayer(10)" class="px-2 py-1 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 transition text-[11px] cursor-pointer" title="Maju 10 detik">
                                            +10s
                                        </button>
                                        <button @click="seekPlayer(30)" class="px-2 py-1 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 transition text-[11px] cursor-pointer" title="Maju 30 detik">
                                            +30s
                                        </button>
                                    </div>

                                    <!-- Quick Capture Timestamp Triggers -->
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            @click="captureStartTime"
                                            class="px-2.5 py-1 rounded-md bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] transition flex items-center gap-1 font-mono cursor-pointer shadow-sm"
                                            title="Tentukan Waktu Mulai (IN) dari pemutar video"
                                        >
                                            <span>Set Start (IN)</span>
                                        </button>
                                        <button
                                            @click="captureEndTime"
                                            class="px-2.5 py-1 rounded-md bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] transition flex items-center gap-1 font-mono cursor-pointer shadow-sm"
                                            title="Tentukan Waktu Selesai (OUT) dari pemutar video"
                                        >
                                            <span>Set End (OUT)</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Bottom Row: Quick Duration Presets (+1m, +3m, +5m, +10m) -->
                                <div class="pt-2 border-t border-slate-900 flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span>Durasi Cepat dari Waktu Mulai (IN):</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button @click="setPresetDuration(60)" class="px-2.5 py-1 rounded-md bg-slate-900 hover:bg-blue-900/40 hover:border-blue-700 text-slate-300 hover:text-blue-300 border border-slate-800 text-[11px] font-bold transition cursor-pointer">
                                            +1m
                                        </button>
                                        <button @click="setPresetDuration(180)" class="px-2.5 py-1 rounded-md bg-slate-900 hover:bg-blue-900/40 hover:border-blue-700 text-slate-300 hover:text-blue-300 border border-slate-800 text-[11px] font-bold transition cursor-pointer">
                                            +3m
                                        </button>
                                        <button @click="setPresetDuration(300)" class="px-2.5 py-1 rounded-md bg-slate-900 hover:bg-blue-900/40 hover:border-blue-700 text-slate-300 hover:text-blue-300 border border-slate-800 text-[11px] font-bold transition cursor-pointer">
                                            +5m
                                        </button>
                                        <button @click="setPresetDuration(600)" class="px-2.5 py-1 rounded-md bg-slate-900 hover:bg-blue-900/40 hover:border-blue-700 text-slate-300 hover:text-blue-300 border border-slate-800 text-[11px] font-bold transition cursor-pointer">
                                            +10m
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: EXPORT PARAMETERS & FORM (4 cols) -->
                    <div class="lg:col-span-4 space-y-4 bg-slate-950 border border-slate-800 rounded-xl p-5">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2 font-sans uppercase tracking-wider">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pengaturan Potongan Klip
                            </h3>
                        </div>

                        <!-- YouTube URL Input -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                URL Video YouTube <span class="text-blue-400">*</span>
                            </label>
                            <input 
                                v-model="form.youtube_url"
                                type="text"
                                placeholder="https://www.youtube.com/watch?v=..."
                                class="w-full rounded-md bg-slate-900 border border-slate-800 px-3.5 py-2 text-xs text-white focus:border-blue-500 transition font-mono"
                            />
                        </div>

                        <!-- Custom Title -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                Judul / Catatan Klip (Opsional)
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Beri judul klip kejadian ini..."
                                class="w-full px-3.5 py-2 rounded-md bg-slate-900 border border-slate-800 text-white text-xs focus:border-blue-500 transition"
                            />
                        </div>

                        <!-- Dual Precision Timestamp Inputs -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                    Waktu Mulai <span class="text-blue-400">*</span>
                                </label>
                                <input
                                    v-model="form.start_time"
                                    type="text"
                                    placeholder="00:00:00"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-md px-2.5 py-2 text-xs font-mono text-white text-center focus:border-blue-500 font-bold"
                                />
                                <span class="text-[10px] text-slate-500 block mt-0.5">Format: HH:MM:SS</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                    Waktu Selesai <span class="text-blue-400">*</span>
                                </label>
                                <input
                                    v-model="form.end_time"
                                    type="text"
                                    placeholder="00:05:00"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-md px-2.5 py-2 text-xs font-mono text-white text-center focus:border-blue-500 font-bold"
                                />
                                <span class="text-[10px] text-slate-500 block mt-0.5">Format: HH:MM:SS</span>
                            </div>
                        </div>

                        <!-- Duration Progress Indicator -->
                        <div
                            class="rounded-md border p-3 space-y-1"
                            :class="isDurationValid ? 'bg-slate-900 border-slate-800 text-emerald-400' : 'bg-slate-900 border-red-900/50 text-red-400'"
                        >
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span>Total Durasi:</span>
                                <span class="font-bold">{{ formattedDuration }}</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-950 overflow-hidden">
                                <div 
                                    class="h-full transition-all duration-300"
                                    :class="isDurationValid ? 'bg-emerald-500' : 'bg-red-500'"
                                    :style="{ width: `${Math.min(100, (calculatedSeconds / 600) * 100)}%` }"
                                ></div>
                            </div>
                            <div v-if="calculatedSeconds > 600" class="text-[10px] text-red-400 font-semibold font-mono">
                                Melebihi batas maksimal 10 menit (600 detik)!
                            </div>
                        </div>

                        <!-- Format Selector -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300">
                                Format & Kualitas Video
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    v-for="fmt in formatOptions"
                                    :key="fmt.value"
                                    class="relative flex items-start space-x-2.5 p-2.5 rounded-lg bg-slate-900 border border-slate-800 cursor-pointer hover:border-slate-700 transition"
                                    :class="form.format === fmt.value ? 'border-blue-500 bg-slate-950 ring-1 ring-blue-500/50' : ''"
                                >
                                    <input
                                        type="radio"
                                        name="formatOption"
                                        :value="fmt.value"
                                        v-model="form.format"
                                        class="mt-0.5 text-blue-500 focus:ring-0 bg-slate-950 border-slate-700"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-bold text-white text-xs leading-none">{{ fmt.name }}</span>
                                            <span v-if="fmt.badge" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-950 text-blue-300 border border-blue-800/60 font-mono">
                                                {{ fmt.badge }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 block mt-1 leading-tight">
                                            {{ fmt.desc }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Action Button -->
                        <button
                            @click="submitTrim"
                            :disabled="isSubmitting || !isDurationValid"
                            class="w-full py-3 px-4 rounded-md bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-tactical"
                        >
                            <svg v-if="isSubmitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <template v-else>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 0L4 4m5.121 5.121L4 14.121" />
                                </svg>
                                <span>Potong & Simpan Klip Video</span>
                            </template>
                        </button>
                    </div>
                </div>

            </section>

            <!-- DAFTAR KLIP SERVER (CLIPS LIBRARY) -->
            <section id="library-section" class="space-y-6 pt-2">
                
                <!-- Section Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-bold text-white tracking-tight uppercase font-tactical">
                                Koleksi Klip Video Saya
                            </h2>
                            <span v-if="clipsList.length" class="px-2.5 py-0.5 rounded-md bg-slate-950 border border-slate-800 text-blue-400 font-mono text-xs font-bold">
                                {{ clipsList.length }} Klip
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Klip video yang telah Anda proses dan siap diunduh. File otomatis dihapus sistem 1 jam setelah dibuat.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="fetchClips"
                            class="px-3.5 py-2 rounded-md bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition flex items-center gap-1.5 font-mono"
                        >
                            <svg class="w-3.5 h-3.5" :class="isLoadingClips ? 'animate-spin text-blue-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Refresh List</span>
                        </button>
                    </div>
                </div>

                <!-- Info Notice -->
                <div class="p-3 rounded-md bg-slate-900 border border-amber-900/50 text-xs text-amber-300 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><strong>Info Storage Server:</strong> Berkas video hasil pemotongan akan <strong>dihapus secara otomatis oleh sistem 1 jam setelah dibuat</strong> untuk menghemat ruang server.</span>
                </div>

                <!-- Loading State -->
                <div v-if="isLoadingClips && clipsList.length === 0" class="py-12 text-center text-slate-400 text-xs font-mono">
                    <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memuat daftar klip video server...
                </div>

                <!-- Empty State -->
                <div v-else-if="clipsList.length === 0" class="py-12 text-center text-slate-400 text-xs bg-slate-900 rounded-xl border border-slate-800 space-y-2">
                    <p class="font-bold text-white uppercase font-tactical text-sm">Belum ada klip video yang disimpan.</p>
                    <p class="text-slate-400 font-sans">Gunakan form di atas untuk mulai memotong klip video dari YouTube.</p>
                </div>

                <!-- Clips Grid List -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div 
                        v-for="clip in clipsList" 
                        :key="clip.id"
                        class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-3 hover:border-slate-700 transition group"
                    >
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-sm text-white line-clamp-1 group-hover:text-blue-400 transition font-sans">
                                    {{ clip.title }}
                                </h3>
                                
                                <!-- Status Badges -->
                                <span v-if="clip.status === 'completed'" class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-emerald-400 border border-emerald-900/50 font-mono">
                                    Selesai
                                </span>
                                <span v-else-if="clip.status === 'processing'" class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-amber-400 border border-amber-900/50 font-mono">
                                    Memproses...
                                </span>
                                <span v-else-if="clip.status === 'pending'" class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-blue-400 border border-blue-900/50 font-mono">
                                    Antrean
                                </span>
                                <span v-else class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-red-400 border border-red-900/50 font-mono">
                                    Gagal
                                </span>
                            </div>

                            <p class="text-xs text-slate-400 font-mono truncate">
                                {{ clip.youtube_url }}
                            </p>

                            <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono pt-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span>Durasi: {{ formatSeconds(clip.duration_seconds) }}</span>
                                    <span>•</span>
                                    <span class="text-blue-400 font-semibold px-1.5 py-0.5 rounded bg-slate-950 border border-slate-800 text-[10px]">{{ clip.format || 'MP4 1080p' }}</span>
                                </div>
                                <span v-if="clip.status === 'completed'" class="text-[10px] text-amber-400 font-sans flex items-center gap-1 bg-slate-950 px-1.5 py-0.5 rounded-md border border-amber-900/50" title="File ini akan dihapus otomatis 1 jam setelah dibuat">
                                    <svg class="w-3 h-3 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>1 Jam</span>
                                </span>
                            </div>
                        </div>

                        <!-- Error Banner if failed -->
                        <div v-if="clip.status === 'failed' && clip.error_message" class="text-[10px] p-2 rounded-md bg-slate-950 border border-red-900/50 text-red-400 font-mono break-words">
                            {{ clip.error_message }}
                        </div>



                        <!-- Card Footer Controls -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                            <button 
                                @click="deleteClip(clip.id)" 
                                class="p-1.5 rounded-md text-slate-400 hover:text-red-400 hover:bg-slate-950 transition text-xs flex items-center gap-1.5 font-mono"
                                title="Hapus Klip"
                            >
                                <img :src="iconDelete" class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100" alt="" />
                                <span>Hapus</span>
                            </button>

                            <a 
                                v-if="clip.status === 'completed' && clip.download_url"
                                :href="clip.download_url" 
                                download 
                                class="py-1.5 px-3 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3m0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>{{ clip.format === 'MP3 Audio' ? 'Unduh MP3' : 'Unduh Video' }}</span>
                            </a>
                        </div>

                    </div>
                </div>

            </section>

        </div>
    </TacticalLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import CustomVideoPlayer from '@/Components/CustomVideoPlayer.vue';
import axios from 'axios';

import iconDelete from '@/Components/Icons/delete-2-svgrepo-com.svg';

const props = defineProps({
    initialUrl: String,
});

// Demo Sample Streams
const sampleVideos = [
    {
        shortLabel: 'Motionlife 1800 Slot',
        title: 'Motionlife Roleplay Upgrade 1800 Slot! Menuju 2000 kah.... #motionliferp',
        youtubeUrl: 'https://www.youtube.com/watch?v=3UfzPC8I5Gk',
        channel: 'Motionlife Roleplay',
        durationSec: 3600,
    },
    {
        shortLabel: 'Police Duty Clipper',
        title: 'Feature potong youtube police duty untuk clipper #imepolice',
        youtubeUrl: 'https://www.youtube.com/watch?v=YBXh-xEJVZg',
        channel: 'IME Police Duty',
        durationSec: 600,
    },
];

const formatOptions = [
    { value: 'MP4 720p', name: 'MP4 720p HD', desc: 'Rekomendasi CapCut & Mobile', badge: 'Populer' },
    { value: 'MP4 1080p', name: 'MP4 1080p FHD', desc: 'Kualitas Tinggi (Desktop)' },
    { value: 'MP4 360p', name: 'MP4 360p SD', desc: 'Sangat Hemat Kuota Data' },
    { value: 'MP3 Audio', name: 'MP3 Audio', desc: 'Ekstrak Suara / Soundbite' },
];

// Backend Form State
const form = ref({
    youtube_url: '',
    title: '',
    start_time: '00:00:10',
    end_time: '00:00:40',
    format: 'MP4 720p',
});

const isSubmitting = ref(false);
const isLoadingClips = ref(false);
const formError = ref('');
const successMessage = ref('');
const clipsList = ref([]);
const playerCurrentSeconds = ref(0);
const videoDurationSec = ref(214);
const mainPlayerRef = ref(null);
let pollTimer = null;
let ytTimeTimer = null;

const videoTitle = computed(() => {
    if (activeVideoId.value) {
        return `YouTube Video Stream [${activeVideoId.value}]`;
    }
    return form.value.title || 'Demo Video Reference Stream';
});

const videoChannel = computed(() => {
    return activeVideoId.value ? 'YouTube Live Embed' : 'Local Reference';
});

const videoThumb = computed(() => {
    if (activeVideoId.value) {
        return `https://img.youtube.com/vi/${activeVideoId.value}/hqdefault.jpg`;
    }
    return 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=400&q=80';
});

const videoSrc = ref('https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4');

// Parse YouTube Video ID
const activeVideoId = computed(() => {
    const url = form.value.youtube_url || '';
    if (!url) return '';
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : '';
});

// YouTube Embed Iframe Listener & Polling
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
            if (info && typeof info.duration === 'number' && info.duration > 0) {
                videoDurationSec.value = Math.floor(info.duration);
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
                iframe.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: 1, channel: 'widget' }), '*');
                iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'getCurrentTime', args: [] }), '*');
                iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'getDuration', args: [] }), '*');
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

function parseTimeToSeconds(val) {
    if (!val) return 0;
    if (!isNaN(val)) return parseInt(val, 10);
    const parts = String(val).trim().split(':').map(Number);
    if (parts.length === 3) return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
    if (parts.length === 2) return (parts[0] * 60) + parts[1];
    if (parts.length === 1) return parts[0] || 0;
    return 0;
}

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
    return `${m}m ${s}s (${secs}d)`;
});

function formatSeconds(secs) {
    if (!secs) return '0d';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}m ${s}s`;
}

// Player controls
function seekPlayer(secondsDelta) {
    if (mainPlayerRef.value) {
        mainPlayerRef.value.seekRelative(secondsDelta);
    }
}

function onMainPlayerTimeUpdate(sec) {
    playerCurrentSeconds.value = Math.floor(sec);
}

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

function onHtml5TimeUpdate() {
    if (html5VideoRef.value) {
        playerCurrentSeconds.value = Math.floor(html5VideoRef.value.currentTime);
        if (html5VideoRef.value.duration) {
            videoDurationSec.value = Math.floor(html5VideoRef.value.duration);
        }
    }
}

function setSampleVideo(idx) {
    const sample = sampleVideos[idx];
    if (!sample) return;
    form.value.youtube_url = sample.youtubeUrl;
    form.value.title = sample.title;
    form.value.start_time = '00:00:10';
    form.value.end_time = '00:00:40';
}

function loadVideoFromInput() {
    if (!form.value.youtube_url) {
        formError.value = 'Silakan masukkan URL YouTube terlebih dahulu!';
    } else {
        formError.value = '';
    }
}

// Submit Trim Job to Laravel API
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
        successMessage.value = response.data.message || 'Proses pemotongan video telah dimasukkan ke dalam antrean server!';
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

// Fetch Clips List from Laravel API
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
    if (!confirm('Apakah Anda yakin ingin menghapus klip video ini dari server?')) return;
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
    } else {
        form.value.youtube_url = 'https://www.youtube.com/watch?v=3UfzPC8I5Gk';
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
