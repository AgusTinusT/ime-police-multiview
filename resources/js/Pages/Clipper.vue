<template>
    <TacticalLayout>
        <Head title="ClipStudio - Tactical Video Clipper & Media Hub" />

        <div
            class="relative z-10 max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-20 space-y-8 font-sans"
        >
            <!-- HEADER / HERO & URL INPUT SECTION -->
            <section class="text-center max-w-4xl mx-auto space-y-4">
                <div
                    class="flex items-center justify-center gap-2 text-xs font-mono"
                >
                    <Link
                        href="/clipper/pricing"
                        class="text-slate-400 hover:text-white transition"
                        >Pricing & Layanan</Link
                    >
                    <span class="text-slate-600">•</span>
                    <Link
                        href="/clipper/police-guide"
                        class="text-blue-400 hover:underline font-bold"
                        >Panduan Police Duty Clipper (GRATIS) →</Link
                    >
                </div>

                <div class="tactical-badge-blue">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span
                        >Pemrosesan Presisi Lossless YouTube (1080p, 720p, 480p
                        & MP3 Audio)</span
                    >
                </div>

                <h1
                    class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight leading-tight uppercase font-tactical"
                >
                    ClipStudio & Layanan Clipper <br class="hidden sm:inline" />
                    <span class="text-blue-400"
                        >IME Roleplay & Police Duty</span
                    >
                </h1>
                <p
                    class="text-sm sm:text-base text-slate-300 max-w-3xl mx-auto font-sans leading-relaxed"
                >
                    Solusi pemotongan video YouTube presisi tinggi untuk
                    <strong>Clipper Komunitas IME</strong>,
                    <strong>Officer Faction</strong>, dan
                    <strong>VIP Supporter</strong>. Unduh klip video MP4 (1080p,
                    720p, 480p) atau ekstrak MP3 Audio tanpa watermark.
                </p>

                <!-- URL Loader Bar -->
                <div class="pt-2 max-w-2xl mx-auto">
                    <div
                        class="relative flex items-center bg-tactical-base border border-tactical-border rounded-xl p-1.5 focus-within:border-blue-500 transition duration-200"
                    >
                        <div class="pl-3.5 text-red-500 shrink-0">
                            <svg
                                class="w-5 h-5 fill-current"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                                />
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
                            class="btn-tactical-primary shrink-0"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                            <span>Muat Stream</span>
                        </button>
                    </div>

                    <!-- Quick Sample Videos -->
                    <div
                        class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-400"
                    >
                        <span class="text-slate-500">Contoh Stream:</span>
                        <button
                            v-for="(sample, idx) in sampleVideos"
                            :key="idx"
                            @click="setSampleVideo(idx)"
                            class="tactical-badge cursor-pointer"
                        >
                            {{ sample.shortLabel }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- NOTIFICATION BANNERS -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="formError"
                    class="p-4 rounded-md bg-slate-950 border border-red-900/50 text-red-400 text-sm flex items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2.5">
                        <svg
                            class="w-5 h-5 shrink-0 text-red-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>{{ formError }}</span>
                    </div>
                    <button
                        @click="formError = ''"
                        class="text-red-400 hover:text-white transition p-1"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </transition>

            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="successMessage"
                    class="p-4 rounded-md bg-slate-950 border border-emerald-900/50 text-emerald-400 text-sm flex items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2.5">
                        <svg
                            class="w-5 h-5 shrink-0 text-emerald-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <span>{{ successMessage }}</span>
                    </div>
                    <button
                        @click="successMessage = ''"
                        class="text-emerald-400 hover:text-white transition p-1"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </transition>

            <!-- INTERACTIVE TRIMMER WORKSPACE -->
            <section class="space-y-6 font-sans">
                <!-- Invalid URL Warning Banner -->
                <div
                    v-if="urlCheckResult && !urlCheckResult.valid"
                    class="p-3 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-300 flex items-center gap-2.5 text-xs"
                >
                    <svg
                        class="w-4 h-4 text-amber-400 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <span>{{ urlCheckResult.message }}</span>
                </div>

                <!-- Main Video Preview & Controls Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    <!-- LEFT COLUMN: VIDEO PLAYER & TIMELINE TRIMMER (7 cols) -->
                    <div class="lg:col-span-7 space-y-4">
                        <!-- Video Player Box -->
                        <div
                            class="w-full aspect-video rounded-xl overflow-hidden bg-black relative border border-[#17233c] shadow-xl"
                        >
                            <CustomVideoPlayer
                                ref="mainPlayerRef"
                                :src="form.youtube_url || videoSrc"
                                :youtube-id="activeVideoId"
                                :title="videoTitle"
                                @timeupdate="onMainPlayerTimeUpdate"
                                class="w-full h-full"
                            />
                        </div>

                        <!-- SECTION 2: TIMELINE TRIMMER -->
                        <div class="space-y-3.5">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-[#38bdf8] font-bold text-xs uppercase tracking-wider"
                                >
                                    <svg
                                        class="w-4 h-4 text-[#38bdf8] shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle cx="6" cy="6" r="3"></circle>
                                        <circle cx="6" cy="18" r="3"></circle>
                                        <line
                                            x1="20"
                                            y1="4"
                                            x2="8.12"
                                            y2="15.88"
                                        ></line>
                                        <line
                                            x1="14.47"
                                            y1="14.47"
                                            x2="20"
                                            y2="20"
                                        ></line>
                                        <line
                                            x1="8.12"
                                            y1="8.12"
                                            x2="12"
                                            y2="12"
                                        ></line>
                                    </svg>
                                    <span>Timeline Trimmer</span>
                                </div>
                                <span
                                    class="text-[10px] font-mono px-2 py-0.5 rounded-full border font-bold"
                                    :class="
                                        isDurationValid
                                            ? 'bg-emerald-950/40 border-emerald-500/40 text-emerald-400'
                                            : 'bg-red-950/40 border-red-500/40 text-red-400'
                                    "
                                >
                                    {{
                                        isDurationValid
                                            ? `OK (≤ ${formatSeconds(maxClipDurationSec)})`
                                            : "Melebihi Batas"
                                    }}
                                </span>
                            </div>

                            <!-- Waktu Pemutar Header Indicator -->
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs font-semibold text-slate-400"
                                    >Waktu Pemutar:</span
                                >
                                <div
                                    class="bg-[#0b1426] border border-[#192640] rounded-lg px-2.5 py-1 font-mono font-bold text-xs text-slate-100 shadow-inner tracking-wider"
                                >
                                    {{
                                        formatSecondsToTimestamp(
                                            playerCurrentSeconds,
                                        )
                                    }}
                                </div>
                            </div>

                            <!-- Dual Precision Timestamp Inputs -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div
                                        class="flex items-center justify-between mb-1"
                                    >
                                        <label
                                            class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300"
                                        >
                                            Waktu Mulai
                                            <span class="text-blue-400">*</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="captureStartTime"
                                            class="text-[11px] text-blue-400 hover:text-blue-300 font-mono font-bold flex items-center gap-1 cursor-pointer bg-blue-950/70 hover:bg-blue-900/90 px-2.5 py-1 rounded-md border border-blue-800/60 transition shadow-sm active:scale-95"
                                            title="Set waktu mulai dari posisi pemutar video saat ini"
                                        >
                                            <span>↓ Set</span>
                                        </button>
                                    </div>
                                    <input
                                        v-model="form.start_time"
                                        type="text"
                                        placeholder="00:00:00"
                                        class="w-full bg-[#0a1222] border border-[#1b2a48] rounded-xl px-2.5 py-2 text-xs font-mono text-white text-center focus:border-blue-500 font-bold"
                                    />
                                    <span
                                        class="text-[10px] text-slate-500 block mt-0.5"
                                        >Format: HH:MM:SS</span
                                    >
                                </div>

                                <div>
                                    <div
                                        class="flex items-center justify-between mb-1"
                                    >
                                        <label
                                            class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300"
                                        >
                                            Waktu Selesai
                                            <span class="text-blue-400">*</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="captureEndTime"
                                            class="text-[11px] text-blue-400 hover:text-blue-300 font-mono font-bold flex items-center gap-1 cursor-pointer bg-blue-950/70 hover:bg-blue-900/90 px-2.5 py-1 rounded-md border border-blue-800/60 transition shadow-sm active:scale-95"
                                            title="Set waktu selesai dari posisi pemutar video saat ini"
                                        >
                                            <span>↓ Set</span>
                                        </button>
                                    </div>
                                    <input
                                        v-model="form.end_time"
                                        type="text"
                                        placeholder="00:05:00"
                                        class="w-full bg-[#0a1222] border border-[#1b2a48] rounded-xl px-2.5 py-2 text-xs font-mono text-white text-center focus:border-blue-500 font-bold"
                                    />
                                    <span
                                        class="text-[10px] text-slate-500 block mt-0.5"
                                        >Format: HH:MM:SS</span
                                    >
                                </div>
                            </div>

                            <!-- DualRangeTrimmer Slider Component -->
                            <DualRangeTrimmer
                                :duration="videoDurationSec"
                                :current-time="playerCurrentSeconds"
                                :start-time="trimStartSecs"
                                :end-time="trimEndSecs"
                                :max-allowed-duration="maxClipDurationSec"
                                @update:start-time="onTrimmerStartUpdate"
                                @update:end-time="onTrimmerEndUpdate"
                                @seek="onTrimmerSeek"
                            />
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: EXPORT PARAMETERS & FORMAT MATRIX (5 cols) -->
                    <div class="lg:col-span-5 space-y-4">
                        <!-- Header Title -->
                        <div
                            class="flex items-center justify-between pb-2 border-b border-[#142036]"
                        >
                            <div
                                class="flex items-center gap-2 text-[#38bdf8] font-bold text-xs uppercase tracking-wider"
                            >
                                <svg
                                    class="w-4 h-4 text-[#38bdf8] shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                                <span>Opsi Unduhan & Format</span>
                            </div>

                            <!-- Max Duration Limit Badge Pill -->
                            <div
                                class="flex items-center gap-1.5 bg-[#0a1222] border border-[#192742] px-2 py-1 rounded-lg"
                            >
                                <span
                                    class="text-[10px] text-slate-400 font-mono"
                                    >Batas:</span
                                >
                                <span
                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-[#2563eb] text-white shadow-sm"
                                >
                                    {{ formatSeconds(maxClipDurationSec) }}
                                </span>
                            </div>
                        </div>

                        <!-- Resolution & Format Selection Card Matrix (Mobile UI 4 Grid) -->
                        <div class="space-y-2">
                            <label
                                class="block text-[11px] font-semibold uppercase tracking-wider text-slate-300"
                            >
                                Format & Kualitas Video
                            </label>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button
                                    v-for="fmt in formatOptions"
                                    :key="fmt.value"
                                    type="button"
                                    @click="
                                        isGuest
                                            ? handleGuestAuth($event)
                                            : (form.format = fmt.value)
                                    "
                                    class="py-2 px-1.5 rounded-xl border text-center flex flex-col items-center justify-center gap-1 transition-all cursor-pointer font-mono shadow-sm min-w-0"
                                    :class="[
                                        form.format === fmt.value
                                            ? 'border-blue-500 bg-[#0f1d38] shadow-md ring-1 ring-blue-500/50'
                                            : 'border-[#182640] bg-[#0a1222] hover:border-slate-600',
                                        isGuest ? 'opacity-80' : '',
                                    ]"
                                >
                                    <span
                                        class="text-[8px] sm:text-[9px] font-mono px-1 rounded font-bold"
                                        :class="fmt.badgeClass"
                                        >{{ fmt.badge }}</span
                                    >
                                    <span
                                        class="font-mono font-bold text-[11px] sm:text-xs truncate w-full"
                                        :class="
                                            form.format === fmt.value
                                                ? 'text-white'
                                                : 'text-slate-200'
                                        "
                                        >{{ fmt.name }}</span
                                    >
                                </button>
                            </div>
                        </div>

                        <!-- MINIMAL KUOTA HARIAN PROGRESS CARD -->
                        <div
                            class="bg-[#091120] border border-[#162238] rounded-xl p-2.5 flex flex-col gap-1.5 font-sans text-xs"
                        >
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-slate-400 font-medium"
                                    >Sisa Kuota:</span
                                >
                                <span
                                    class="font-mono font-bold"
                                    :class="
                                        userDailyRemaining <= 0 &&
                                        !isClipperOrAdmin
                                            ? 'text-amber-400'
                                            : 'text-slate-200'
                                    "
                                >
                                    {{
                                        isClipperOrAdmin
                                            ? "Unlimited"
                                            : `${dailyClipsCount}/${dailyClipsLimit} Klip`
                                    }}
                                </span>
                            </div>

                            <!-- Visual Progress Bar (Subtle) -->
                            <div
                                v-if="!isClipperOrAdmin"
                                class="w-full h-1 bg-[#131f36] rounded-full overflow-hidden"
                            >
                                <div
                                    class="h-full transition-all duration-300 rounded-full"
                                    :class="
                                        userDailyRemaining <= 0
                                            ? 'bg-amber-500'
                                            : 'bg-blue-500'
                                    "
                                    :style="{
                                        width: `${Math.min(100, (dailyClipsCount / dailyClipsLimit) * 100)}%`,
                                    }"
                                ></div>
                            </div>

                            <!-- Minimal Subtitle & Upgrade Prompt -->
                            <div class="text-[11px] leading-tight">
                                <template v-if="isGuest">
                                    <span class="text-slate-400"
                                        >Guest: Silakan
                                        <a
                                            href="/login"
                                            class="text-blue-400 underline font-semibold"
                                            >Masuk</a
                                        >
                                        /
                                        <a
                                            href="/register"
                                            class="text-blue-400 underline font-semibold"
                                            >Daftar</a
                                        >
                                        (3 klip/minggu).</span
                                    >
                                </template>
                                <template v-else-if="isClipperOrAdmin">
                                    <span class="text-slate-400"
                                        >Akses PRO Clipper aktif
                                        (Unlimited).</span
                                    >
                                </template>
                                <template v-else-if="userDailyRemaining <= 0">
                                    <div
                                        class="flex items-center justify-between gap-2 pt-0.5"
                                    >
                                        <span
                                            class="text-amber-400 font-semibold"
                                            >Kuota hari ini habis.</span
                                        >
                                        <Link
                                            href="/clipper/pricing"
                                            class="text-blue-400 hover:text-blue-300 font-bold underline flex items-center gap-1 font-mono text-[10px]"
                                        >
                                            Naikkan Paket / Upgrade →
                                        </Link>
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="text-slate-400"
                                        >Tersisa
                                        <strong class="text-slate-200 font-mono"
                                            >{{
                                                userDailyRemaining
                                            }}
                                            klip</strong
                                        >
                                        hari ini.</span
                                    >
                                </template>
                            </div>
                        </div>

                        <!-- Submit Action Button & Live Processing Bar -->
                        <div class="mt-1 flex flex-col gap-2">
                            <button
                                @click="submitTrim"
                                :disabled="
                                    isSubmitting ||
                                    (!isGuest && !isDurationValid) ||
                                    submitCooldownSec > 0
                                "
                                class="w-full py-3.5 px-4 rounded-xl text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-tactical cursor-pointer shadow-lg"
                                :class="
                                    isGuest
                                        ? 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-blue-600/30'
                                        : isDurationValid
                                          ? 'bg-gradient-to-r from-[#2563eb] to-[#1d4ed8] hover:from-[#1d4ed8] hover:to-[#1e40af] shadow-blue-600/30'
                                          : 'bg-slate-800 text-slate-400'
                                "
                            >
                                <svg
                                    v-if="isSubmitting"
                                    class="animate-spin h-5 w-5 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span
                                    v-else-if="submitCooldownSec > 0"
                                    class="font-mono"
                                    >Tunggu Cooldown ({{
                                        submitCooldownSec
                                    }}s)...</span
                                >
                                <template v-else-if="isGuest">
                                    <img
                                        :src="iconCut"
                                        class="w-4 h-4 invert opacity-90 shrink-0"
                                        alt=""
                                    />
                                    <span
                                        >Masuk / Sign In untuk Potong
                                        Video</span
                                    >
                                </template>
                                <template
                                    v-else-if="
                                        !isClipperOrAdmin &&
                                        userDailyRemaining <= 0
                                    "
                                >
                                    <svg
                                        class="w-4 h-4 text-slate-400 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                    <span>Kuota Harian Habis (3/3 Klip)</span>
                                </template>
                                <template v-else>
                                    <img
                                        :src="iconCut"
                                        class="w-4 h-4 invert opacity-90 shrink-0"
                                        alt=""
                                    />
                                    <span>{{
                                        urlCheckResult && urlCheckResult.is_live
                                            ? "POTONG KLIP LIVE (HLS DVR)"
                                            : `POTONG & SIMPAN KLIP (${form.format.toUpperCase()})`
                                    }}</span>
                                </template>
                            </button>

                            <!-- Dynamic Live Progress Status Bar Container -->
                            <div
                                v-if="isSubmitting"
                                class="bg-[#0a1222] p-2.5 rounded-xl border border-[#182640] flex flex-col gap-1.5 font-mono text-xs"
                            >
                                <div
                                    class="flex justify-between items-center text-[10px]"
                                >
                                    <span
                                        class="text-cyan-400 flex items-center gap-1.5 font-semibold animate-pulse"
                                    >
                                        <svg
                                            class="w-3 h-3 animate-spin text-cyan-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle
                                                class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                        Merender klip video di server FFmpeg...
                                    </span>
                                    <span class="text-slate-200 font-bold"
                                        >Memproses</span
                                    >
                                </div>
                                <div
                                    class="w-full h-1.5 bg-[#142036] rounded-full overflow-hidden"
                                >
                                    <div
                                        class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 animate-pulse w-full"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SIMPLIFIED PLAN COMPARISON CARD (Outside feature clip container, positioned right below it) -->
            <div
                class="bg-gradient-to-br from-[#0c162b] to-[#081021] border border-[#1b2b4b] rounded-2xl p-4 shadow-xl flex flex-col gap-3 font-sans"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0"
                        >
                            <svg
                                class="w-4 h-4 text-amber-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4
                                class="text-xs font-bold text-white uppercase tracking-wider"
                            >
                                Perbedaan Paket & Akses Spesial
                            </h4>
                            <p class="text-[10px] text-slate-400">
                                Pilih akses sesuai kebutuhan Anda
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="!isGuest"
                        class="flex items-center gap-1.5 shrink-0 font-sans"
                    >
                        <button
                            v-if="
                                !isClipperOrAdmin &&
                                clipperRequestStatus !== 'pending'
                            "
                            @click="showRequestClipperModal = true"
                            class="px-2.5 py-1 rounded-md bg-blue-600 hover:bg-blue-500 text-white font-bold text-[10px] transition shadow-sm cursor-pointer"
                        >
                            Ajukan Hak Spesial
                        </button>
                        <button
                            v-if="user?.can_delegate_clipper"
                            @click="
                                showDelegationModal = true;
                                fetchDelegations();
                            "
                            class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] transition shadow-sm cursor-pointer"
                        >
                            Penunjukan ({{ delegationData.used_slots }}/2 Slot)
                        </button>
                    </div>
                </div>

                <!-- Direct Comparison Chips (Free vs PRO / Officer) -->
                <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
                    <div
                        class="bg-[#070d1a] border border-[#16233d] rounded-xl p-2.5 flex flex-col gap-0.5"
                    >
                        <span
                            class="text-slate-400 text-[10px] font-bold uppercase"
                            >FREE MEMBER:</span
                        >
                        <span class="text-slate-200 font-semibold text-xs"
                            >3 Klip / Minggu</span
                        >
                        <span class="text-slate-400 text-[9px]"
                            >Maks 5 - 10m / Klip</span
                        >
                    </div>

                    <div
                        class="bg-[#0f1d39] border border-[#234278] rounded-xl p-2.5 flex flex-col gap-0.5"
                    >
                        <span
                            class="text-amber-400 text-[10px] font-bold uppercase"
                            >OFFICER & PRO:</span
                        >
                        <span class="text-white font-semibold text-xs"
                            >Unlimited Potong</span
                        >
                        <span class="text-cyan-400 text-[9px]"
                            >Hak 2 Slot & Lossless</span
                        >
                    </div>
                </div>

                <!-- Link Button to Full Pricing Page -->
                <Link
                    href="/clipper/pricing"
                    class="w-full py-2.5 px-3 rounded-xl bg-[#0e1c36] hover:bg-[#152a52] border border-[#233f72] text-xs font-bold text-slate-200 flex items-center justify-between transition group cursor-pointer shadow-sm"
                >
                    <div class="flex items-center gap-2 font-mono">
                        <svg
                            class="w-3.5 h-3.5 text-amber-400 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>Lihat Opsi Paket & Panduan Officer</span>
                    </div>
                    <div
                        class="flex items-center gap-1 text-cyan-400 group-hover:translate-x-1 transition-transform font-mono text-[11px]"
                    >
                        <span>Buka Paket →</span>
                    </div>
                </Link>
            </div>

            <!-- DAFTAR KLIP SERVER (CLIPS LIBRARY) -->
            <section id="library-section" class="space-y-6 pt-2">
                <!-- Section Header -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <h2
                                class="text-2xl font-bold text-white tracking-tight uppercase font-tactical"
                            >
                                Koleksi Klip Video Saya
                            </h2>
                            <span
                                v-if="clipsList.length"
                                class="px-2.5 py-0.5 rounded-md bg-slate-950 border border-slate-800 text-blue-400 font-mono text-xs font-bold"
                            >
                                {{ clipsList.length }} Klip
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Klip video yang telah Anda proses dan siap diunduh.
                            File otomatis dihapus sistem 1 jam setelah dibuat.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="fetchClips"
                            class="px-3.5 py-2 rounded-md bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition flex items-center gap-1.5 font-mono"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                :class="
                                    isLoadingClips
                                        ? 'animate-spin text-blue-400'
                                        : ''
                                "
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            <span>Refresh List</span>
                        </button>
                    </div>
                </div>

                <!-- Info Notice -->
                <div
                    class="p-3 rounded-md bg-slate-900 border border-amber-900/50 text-xs text-amber-300 flex items-center gap-2.5"
                >
                    <svg
                        class="w-4 h-4 text-amber-400 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span
                        ><strong>Info Storage Server:</strong> Berkas video
                        hasil pemotongan akan
                        <strong
                            >dihapus secara otomatis oleh sistem 1 jam setelah
                            dibuat</strong
                        >
                        untuk menghemat ruang server.</span
                    >
                </div>

                <!-- Loading State -->
                <div
                    v-if="isLoadingClips && clipsList.length === 0"
                    class="py-12 text-center text-slate-400 text-xs font-mono"
                >
                    <svg
                        class="animate-spin h-6 w-6 text-blue-500 mx-auto mb-2"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    Memuat daftar klip video server...
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="clipsList.length === 0"
                    class="py-12 text-center text-slate-400 text-xs bg-slate-900 rounded-xl border border-slate-800 space-y-2"
                >
                    <p
                        class="font-bold text-white uppercase font-tactical text-sm"
                    >
                        Belum ada klip video yang disimpan.
                    </p>
                    <p class="text-slate-400 font-sans">
                        Gunakan form di atas untuk mulai memotong klip video
                        dari YouTube.
                    </p>
                </div>

                <!-- Clips Grid List -->
                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
                >
                    <div
                        v-for="clip in clipsList"
                        :key="clip.id"
                        class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-3 hover:border-slate-700 transition group"
                    >
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <h3
                                    class="font-bold text-sm text-white line-clamp-1 group-hover:text-blue-400 transition font-sans"
                                >
                                    {{ clip.title }}
                                </h3>

                                <!-- Status Badges -->
                                <span
                                    v-if="clip.status === 'completed'"
                                    class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-emerald-400 border border-emerald-900/50 font-mono"
                                >
                                    Selesai
                                </span>
                                <span
                                    v-else-if="clip.status === 'processing'"
                                    class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-amber-400 border border-amber-900/50 font-mono"
                                >
                                    Memproses...
                                </span>
                                <span
                                    v-else-if="clip.status === 'pending'"
                                    class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-blue-400 border border-blue-900/50 font-mono"
                                >
                                    Antrean
                                </span>
                                <span
                                    v-else-if="clip.status === 'expired'"
                                    class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-slate-400 border border-slate-800 font-mono"
                                    title="File fisik video telah dibersihkan sistem setelah 1 jam"
                                >
                                    Kadaluarsa (File Dihapus)
                                </span>
                                <span
                                    v-else
                                    class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-950 text-red-400 border border-red-900/50 font-mono"
                                >
                                    Gagal
                                </span>
                            </div>

                            <p
                                class="text-xs text-slate-400 font-mono truncate"
                            >
                                {{ clip.youtube_url }}
                            </p>

                            <div
                                class="flex items-center justify-between text-[11px] text-slate-400 font-mono pt-1"
                            >
                                <div
                                    class="flex items-center gap-1.5 flex-wrap"
                                >
                                    <span
                                        >Durasi:
                                        {{
                                            formatSeconds(clip.duration_seconds)
                                        }}</span
                                    >
                                    <span>•</span>
                                    <span
                                        class="text-blue-400 font-semibold px-1.5 py-0.5 rounded bg-slate-950 border border-slate-800 text-[10px]"
                                        >{{ clip.format || "MP4 1080p" }}</span
                                    >
                                </div>
                                <span
                                    v-if="clip.status === 'completed'"
                                    class="text-[10px] text-amber-400 font-sans flex items-center gap-1 bg-slate-950 px-1.5 py-0.5 rounded-md border border-amber-900/50"
                                    title="File ini akan dihapus otomatis 1 jam setelah dibuat"
                                >
                                    <svg
                                        class="w-3 h-3 text-amber-400 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span>1 Jam</span>
                                </span>
                                <span
                                    v-else-if="clip.status === 'expired'"
                                    class="text-[10px] text-slate-500 font-sans italic"
                                >
                                    File Dibersihkan
                                </span>
                            </div>
                        </div>

                        <!-- Error Banner if failed -->
                        <div
                            v-if="
                                clip.status === 'failed' && clip.error_message
                            "
                            class="text-[10px] p-2 rounded-md bg-slate-950 border border-red-900/50 text-red-400 font-mono break-words"
                        >
                            {{ clip.error_message }}
                        </div>

                        <!-- Card Footer Controls -->
                        <div
                            class="flex items-center justify-between pt-2 border-t border-slate-800"
                        >
                            <button
                                @click="deleteClip(clip.id)"
                                class="p-1.5 rounded-md text-slate-400 hover:text-red-400 hover:bg-slate-950 transition text-xs flex items-center gap-1.5 font-mono"
                                title="Hapus Klip"
                            >
                                <img
                                    :src="iconDelete"
                                    class="w-3.5 h-3.5 invert opacity-70 hover:opacity-100"
                                    alt=""
                                />
                                <span>Hapus</span>
                            </button>

                            <a
                                v-if="
                                    clip.status === 'completed' &&
                                    clip.download_url
                                "
                                :href="clip.download_url"
                                download
                                class="py-1.5 px-3 rounded-md bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition flex items-center gap-1.5"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16v1a3 3m0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                                <span>{{
                                    clip.format === "MP3 Audio"
                                        ? "Unduh MP3"
                                        : "Unduh Video"
                                }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- STICKY MOBILE BOTTOM ACTION BAR (< 1024px) -->
        <div
            class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/95 border-t border-slate-800 p-3 shadow-2xl backdrop-blur-md flex items-center justify-between gap-3 font-sans"
        >
            <div class="flex items-center gap-2 font-mono text-xs">
                <span
                    class="px-2 py-1 rounded bg-slate-900 border border-slate-800 text-blue-400 font-bold"
                >
                    {{ formattedDuration }}
                </span>
                <span
                    class="text-[10px] text-slate-400 font-sans hidden sm:inline flex items-center gap-1"
                >
                    <span
                        v-if="urlCheckResult?.is_live"
                        class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping inline-block mr-1"
                    ></span>
                    <span>{{
                        urlCheckResult?.is_live ? "Live DVR" : "VOD Stream"
                    }}</span>
                </span>
            </div>

            <button
                @click="submitTrim"
                :disabled="
                    isSubmitting ||
                    (!isGuest && !isDurationValid) ||
                    submitCooldownSec > 0
                "
                class="py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50 flex items-center gap-2 font-tactical shadow-lg cursor-pointer"
            >
                <svg
                    v-if="isSubmitting"
                    class="animate-spin h-4 w-4 text-white"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <template v-else-if="isGuest">
                    <img
                        :src="iconCut"
                        class="w-4 h-4 invert opacity-90 shrink-0"
                        alt=""
                    />
                    <span>Masuk / Sign In</span>
                </template>
                <template v-else>
                    <img
                        :src="iconCut"
                        class="w-4 h-4 invert opacity-90 shrink-0"
                        alt=""
                    />
                    <span>Potong Klip Real</span>
                </template>
            </button>
        </div>

        <!-- GUEST AUTHENTICATION REQUIRED MODAL OVERLAY -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showGuestAuthModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90"
            >
                <div
                    class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 max-w-md w-full space-y-6 shadow-2xl relative text-center"
                >
                    <button
                        @click="showGuestAuthModal = false"
                        class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 transition"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    <div
                        class="w-16 h-16 rounded-full bg-blue-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center mx-auto shadow-inner"
                    >
                        <svg
                            class="w-8 h-8 text-blue-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>

                    <div class="space-y-2">
                        <h3
                            class="text-xl font-black text-white uppercase font-tactical tracking-tight"
                        >
                            Pendaftaran Akun Gratis Diperlukan
                        </h3>
                        <p
                            class="text-xs text-slate-300 leading-relaxed font-sans"
                        >
                            Untuk mulai memproses & mengunduh potongan video
                            YouTube ini, silakan masuk atau daftar akun gratis.
                        </p>
                    </div>

                    <div
                        class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-left text-xs font-mono space-y-1 text-slate-300"
                    >
                        <div
                            class="flex items-center gap-2 text-emerald-400 font-bold"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span>Dapatkan 3 Klip Gratis per Minggu</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-emerald-400 font-bold"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span>Pemrosesan Lossless MP4 / MP3 / GIF</span>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2">
                        <a
                            href="/register"
                            class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30 cursor-pointer block font-tactical"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                            <span>Daftar Akun Gratis Sekarang</span>
                        </a>

                        <a
                            href="/login"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white font-bold text-xs transition block text-center cursor-pointer font-sans"
                        >
                            Sudah punya akun? Masuk (Sign In)
                        </a>
                    </div>
                </div>
            </div>
        </transition>

        <!-- REQUEST CLIPPER ROLE MODAL OVERLAY -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showRequestClipperModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90"
            >
                <div
                    class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 max-w-lg w-full space-y-5 shadow-2xl relative font-sans"
                >
                    <button
                        @click="showRequestClipperModal = false"
                        class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 transition"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2.5 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800 font-mono text-[10px] font-bold uppercase"
                                >Hak Spesial Clipper</span
                            >
                        </div>
                        <h3
                            class="text-xl font-black text-white uppercase font-tactical tracking-tight"
                        >
                            Pengajuan Akses & Bukti Clipper
                        </h3>
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed font-sans"
                        >
                            Pilih kategori profesi Clipper Anda dan sertakan
                            bukti portofolio / channel untuk penyesuaian promo &
                            kuota.
                        </p>
                    </div>

                    <div
                        v-if="requestSuccessMsg"
                        class="p-3 rounded-lg bg-emerald-950 border border-emerald-800 text-emerald-300 text-xs font-semibold"
                    >
                        {{ requestSuccessMsg }}
                    </div>

                    <div
                        v-if="requestErrMsg"
                        class="p-3 rounded-lg bg-red-950 border border-red-800 text-red-300 text-xs font-semibold"
                    >
                        {{ requestErrMsg }}
                    </div>

                    <!-- Category Selector Options -->
                    <div class="space-y-2">
                        <label
                            class="block text-xs font-bold uppercase tracking-wider text-slate-200"
                        >
                            Kategori Pengajuan
                            <span class="text-blue-400">*</span>
                        </label>

                        <div class="space-y-2">
                            <!-- Option 1: Clipper Komunitas IME Roleplay (General) -->
                            <label
                                class="flex items-start space-x-3 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-slate-700 transition"
                                :class="
                                    clipperRequestType === 'general'
                                        ? 'border-blue-500 bg-blue-950/20 ring-1 ring-blue-500/50'
                                        : ''
                                "
                            >
                                <input
                                    type="radio"
                                    name="reqType"
                                    value="general"
                                    v-model="clipperRequestType"
                                    class="mt-1 text-blue-500 focus:ring-0 bg-slate-900 border-slate-700"
                                />
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="font-bold text-white text-xs sm:text-sm"
                                            >Clipper Komunitas IME Roleplay
                                            (General)</span
                                        >
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold rounded bg-blue-950 text-blue-400 border border-blue-800 font-mono shrink-0"
                                            >Diskon Promo</span
                                        >
                                    </div>
                                    <span
                                        class="text-xs text-slate-400 block mt-0.5"
                                        >Suka memotong klip umum komunitas.
                                        Sertakan bukti channel / hasil
                                        klip.</span
                                    >
                                </div>
                            </label>

                            <!-- Option 2: Police Duty Clipper -->
                            <label
                                class="flex items-start space-x-3 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-slate-700 transition"
                                :class="
                                    clipperRequestType === 'officer'
                                        ? 'border-blue-500 bg-blue-950/20 ring-1 ring-blue-500/50'
                                        : ''
                                "
                            >
                                <input
                                    type="radio"
                                    name="reqType"
                                    value="officer"
                                    v-model="clipperRequestType"
                                    class="mt-1 text-blue-500 focus:ring-0 bg-slate-900 border-slate-700"
                                />
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="font-bold text-white text-xs sm:text-sm"
                                            >Police Duty Clipper (Jalur
                                            Officer)</span
                                        >
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold rounded bg-teal-950 text-teal-300 border border-teal-800 font-mono shrink-0"
                                            >Direct Slot</span
                                        >
                                    </div>
                                    <span
                                        class="text-xs text-slate-400 block mt-0.5"
                                        >Dokumentasi tugas Kepolisian. Langsung
                                        minta slot dari Officer aktif.</span
                                    >
                                </div>
                            </label>

                            <!-- Option 3: Content Creator / Editor -->
                            <label
                                class="flex items-start space-x-3 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-slate-700 transition"
                                :class="
                                    clipperRequestType === 'creator'
                                        ? 'border-blue-500 bg-blue-950/20 ring-1 ring-blue-500/50'
                                        : ''
                                "
                            >
                                <input
                                    type="radio"
                                    name="reqType"
                                    value="creator"
                                    v-model="clipperRequestType"
                                    class="mt-1 text-blue-500 focus:ring-0 bg-slate-900 border-slate-700"
                                />
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="font-bold text-white text-xs sm:text-sm"
                                            >Content Creator / Video
                                            Editor</span
                                        >
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold rounded bg-indigo-950 text-indigo-400 border border-indigo-800 font-mono shrink-0"
                                            >10 Klip/Hari</span
                                        >
                                    </div>
                                    <span
                                        class="text-xs text-slate-400 block mt-0.5"
                                        >Editor YouTube/TikTok IME. Durasi klip
                                        hingga 5 menit.</span
                                    >
                                </div>
                            </label>

                            <!-- Option 4: VIP Supporter -->
                            <label
                                class="flex items-start space-x-3 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-slate-700 transition"
                                :class="
                                    clipperRequestType === 'vip'
                                        ? 'border-blue-500 bg-blue-950/20 ring-1 ring-blue-500/50'
                                        : ''
                                "
                            >
                                <input
                                    type="radio"
                                    name="reqType"
                                    value="vip"
                                    v-model="clipperRequestType"
                                    class="mt-1 text-blue-500 focus:ring-0 bg-slate-900 border-slate-700"
                                />
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="font-bold text-white text-xs sm:text-sm"
                                            >VIP Supporter (Akses Penuh)</span
                                        >
                                        <span
                                            class="px-2 py-0.5 text-[9px] font-bold rounded bg-emerald-950 text-emerald-400 border border-emerald-800 font-mono shrink-0"
                                            >Unlimited</span
                                        >
                                    </div>
                                    <span
                                        class="text-xs text-slate-400 block mt-0.5"
                                        >Akses tanpa batas, durasi 10 menit &
                                        prioritas antrean server.</span
                                    >
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Channel & Proof URL Input -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-slate-300"
                        >
                            Link Channel / Tautan Bukti Portofolio Klip
                        </label>
                        <input
                            v-model="clipperChannelUrl"
                            type="url"
                            placeholder="https://www.youtube.com/@channel-anda atau link video klip..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-0 outline-none transition font-mono"
                        />
                    </div>

                    <!-- Reason Input -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-slate-300"
                        >
                            Catatan Pengajuan / Bukti Keaktifan (Opsional)
                        </label>
                        <textarea
                            v-model="clipperRequestReason"
                            rows="2"
                            placeholder="Tuliskan keterangan singkat, nama Officer penunjuk, atau deskripsi portofolio..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-0 outline-none transition"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            @click="showRequestClipperModal = false"
                            class="py-2.5 px-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-white font-bold text-xs transition"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitClipperRequest"
                            :disabled="isSubmittingRequest"
                            class="py-2.5 px-5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-blue-600/30 cursor-pointer font-tactical"
                        >
                            <svg
                                v-if="isSubmittingRequest"
                                class="animate-spin h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <img
                                v-else
                                :src="iconCut"
                                class="w-3.5 h-3.5 invert opacity-90 shrink-0"
                                alt=""
                            />
                            <span>Kirim Pengajuan</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- DELEGATION CLIPPER MODAL (FOR OFFICERS/ADMINS - 2 SLOTS) -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showDelegationModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90"
            >
                <div
                    class="bg-slate-900 border border-slate-800 rounded-2xl max-w-xl w-full p-6 sm:p-7 shadow-2xl space-y-5 font-sans relative text-left"
                >
                    <button
                        @click="showDelegationModal = false"
                        class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 transition"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2.5 py-0.5 rounded bg-amber-950 text-amber-400 border border-amber-800/80 font-mono text-[10px] font-bold uppercase"
                                >Hak Otonomi Officer</span
                            >
                            <span
                                class="px-2.5 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-800/80 font-mono text-[10px] font-bold uppercase"
                                >2 Slot Penunjukan</span
                            >
                        </div>
                        <h3
                            class="text-xl font-black text-white uppercase tracking-tight font-tactical"
                        >
                            Kelola Hak Penunjukan Clipper Officer
                        </h3>
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed"
                        >
                            Sebagai Officer, Anda berhak menunjuk hingga
                            <strong>2 akun member</strong> secara instan.
                            Kesepakatan internal sepenuhnya diserahkan kepada
                            Anda & Clipper.
                        </p>
                    </div>

                    <!-- Alert Banners inside Modal -->
                    <div
                        v-if="delegationErrMsg"
                        class="p-3 rounded-xl bg-slate-950 border border-red-900/50 text-red-400 text-xs flex items-center justify-between gap-2 font-mono"
                    >
                        <span>{{ delegationErrMsg }}</span>
                        <button
                            @click="delegationErrMsg = ''"
                            class="text-red-400 hover:text-white"
                        >
                            ✕
                        </button>
                    </div>

                    <div
                        v-if="delegationSuccessMsg"
                        class="p-3 rounded-xl bg-slate-950 border border-emerald-900/50 text-emerald-400 text-xs flex items-center justify-between gap-2 font-mono"
                    >
                        <span>{{ delegationSuccessMsg }}</span>
                        <button
                            @click="delegationSuccessMsg = ''"
                            class="text-emerald-400 hover:text-white"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Slots Overview Box -->
                    <div
                        class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-3"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span
                                class="text-slate-300 font-semibold uppercase tracking-wider"
                                >Status Kuota Slot Penunjukan:</span
                            >
                            <span
                                class="font-mono font-bold text-sm"
                                :class="
                                    delegationData.used_slots >= 2
                                        ? 'text-amber-400'
                                        : 'text-emerald-400'
                                "
                            >
                                {{ delegationData.used_slots }}/2 Slot Terpakai
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div
                            class="w-full h-2 rounded-full bg-slate-900 overflow-hidden border border-slate-800"
                        >
                            <div
                                class="h-full bg-gradient-to-r from-emerald-500 to-blue-500 transition-all duration-300"
                                :style="{
                                    width: `${(delegationData.used_slots / 2) * 100}%`,
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- List of Current Delegations -->
                    <div class="space-y-3">
                        <h4
                            class="text-xs font-semibold text-slate-300 uppercase tracking-wider"
                        >
                            Akun Yang Telah Anda Tunjuk ({{
                                delegationData.delegations
                                    ? delegationData.delegations.length
                                    : 0
                            }}/2)
                        </h4>

                        <div
                            v-if="isLoadingDelegation"
                            class="p-4 text-center text-xs text-slate-400 font-mono"
                        >
                            Memuat data slot...
                        </div>

                        <div
                            v-else-if="
                                !delegationData.delegations ||
                                delegationData.delegations.length === 0
                            "
                            class="p-4 rounded-xl bg-slate-950/60 border border-dashed border-slate-800 text-center space-y-1"
                        >
                            <p class="text-xs text-slate-400">
                                Belum ada akun member yang Anda tunjuk.
                            </p>
                            <p class="text-[11px] text-slate-500 font-mono">
                                Gunakan formulir di bawah untuk menunjuk akun
                                via alamat email.
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="del in delegationData.delegations"
                                :key="del.id"
                                class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="space-y-0.5 truncate">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-bold text-white truncate"
                                            >{{
                                                del.assigned_user?.name ||
                                                "Member"
                                            }}</span
                                        >
                                        <span
                                            class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-slate-900 text-emerald-400 border border-slate-800 font-mono uppercase"
                                        >
                                            {{
                                                del.tier === "ultimate"
                                                    ? "Ultimate Tier"
                                                    : "Creator Tier (10 Klip/hr)"
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-400 font-mono truncate"
                                    >
                                        {{ del.assigned_user?.email }}
                                    </p>
                                </div>
                                <button
                                    @click="
                                        revokeDelegation(
                                            del.id,
                                            del.assigned_user?.name ||
                                                del.assigned_user?.email,
                                        )
                                    "
                                    class="py-1.5 px-3 rounded-lg bg-red-950/80 hover:bg-red-900 border border-red-800 text-red-300 hover:text-white font-semibold text-xs transition flex items-center gap-1 shrink-0 font-mono cursor-pointer"
                                    title="Cabut penunjukan & kembalikan pengguna ke member biasa"
                                >
                                    <img
                                        :src="iconDelete"
                                        class="w-3.5 h-3.5 invert opacity-90"
                                        alt=""
                                    />
                                    <span>Cabut</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form to Assign New Member (If slots available) -->
                    <div
                        v-if="delegationData.used_slots < 2"
                        class="p-4 rounded-xl bg-slate-950/80 border border-blue-900/50 space-y-3"
                    >
                        <h4
                            class="text-xs font-semibold text-blue-300 uppercase tracking-wider flex items-center gap-1.5"
                        >
                            <svg
                                class="w-4 h-4 text-blue-400 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                            <span
                                >Tunjuk Akun Member Baru (Sisa Slot:
                                {{ 2 - delegationData.used_slots }})</span
                            >
                        </h4>

                        <div class="space-y-2">
                            <div>
                                <label
                                    class="block text-[11px] text-slate-400 mb-1"
                                    >Email Akun Member:</label
                                >
                                <input
                                    v-model="assignEmailInput"
                                    type="email"
                                    placeholder="contoh: user@gmail.com"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-0 outline-none transition font-mono"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-[11px] text-slate-400 mb-1"
                                    >Pilih Kategori Hak Clipper:</label
                                >
                                <select
                                    v-model="assignTierInput"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:border-blue-500 focus:ring-0 outline-none transition font-sans"
                                >
                                    <option value="creator">
                                        Content Creator / Editor (10 Klip/hari,
                                        Durasi Maks 5 Menit)
                                    </option>
                                    <option value="ultimate">
                                        Official Streamer / VIP (Unlimited Klip,
                                        Durasi Maks 10 Menit)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <button
                            @click="assignDelegation"
                            :disabled="isAssigningDelegation"
                            class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30 cursor-pointer font-tactical"
                        >
                            <svg
                                v-if="isAssigningDelegation"
                                class="animate-spin h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span v-else>Tunjuk Sebagai Clipper</span>
                        </button>
                    </div>

                    <div
                        v-else
                        class="p-3 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-300 text-xs text-center font-mono"
                    >
                        Kuota penunjukan Anda telah penuh (2/2 Slot Terpakai).
                        Cabut salah satu penunjukan di atas jika ingin mengganti
                        akun target.
                    </div>

                    <div class="flex items-center justify-end pt-2">
                        <button
                            @click="showDelegationModal = false"
                            class="py-2 px-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-white font-bold text-xs transition cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </TacticalLayout>
</template>

<script setup>
import DualRangeTrimmer from "@/Components/DualRangeTrimmer.vue";
import CustomVideoPlayer from "@/Components/CustomVideoPlayer.vue";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

import iconDelete from "@/Components/Icons/delete-2-svgrepo-com.svg";

const props = defineProps({
    initialUrl: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isGuest = computed(() => !user.value);
const isClipperOrAdmin = computed(() => user.value?.can_trim_video || false);
const maxClipDurationSec = computed(() => (isClipperOrAdmin.value ? 600 : 60));
const dailyClipsCount = computed(() => user.value?.daily_clips_count || 0);
const dailyClipsLimit = computed(() => user.value?.daily_clips_limit || 3);
const userDailyRemaining = computed(() =>
    Math.max(0, dailyClipsLimit.value - dailyClipsCount.value),
);

const showGuestAuthModal = ref(false);
const showRequestClipperModal = ref(false);
const clipperRequestType = ref("officer");
const clipperChannelUrl = ref("");
const clipperRequestReason = ref("");
const clipperRequestStatus = ref(null);
const isSubmittingRequest = ref(false);
const requestSuccessMsg = ref("");
const requestErrMsg = ref("");

// Clipper Delegation State (Officer 2 Slots)
const showDelegationModal = ref(false);
const delegationData = ref({
    can_delegate: false,
    max_slots: 2,
    used_slots: 0,
    remaining_slots: 2,
    delegations: [],
});
const isLoadingDelegation = ref(false);
const isAssigningDelegation = ref(false);
const assignEmailInput = ref("");
const assignTierInput = ref("creator");
const delegationErrMsg = ref("");
const delegationSuccessMsg = ref("");
const showTierMatrix = ref(false);

// Demo Sample Streams
const sampleVideos = [
    {
        shortLabel: "Motionlife 1800 Slot",
        title: "Motionlife Roleplay Upgrade 1800 Slot! Menuju 2000 kah.... #motionliferp",
        youtubeUrl: "https://www.youtube.com/watch?v=3UfzPC8I5Gk",
        channel: "Motionlife Roleplay",
        durationSec: 3600,
    },
    {
        shortLabel: "Police Duty Clipper",
        title: "Feature potong youtube police duty untuk clipper #imepolice",
        youtubeUrl: "https://www.youtube.com/watch?v=YBXh-xEJVZg",
        channel: "IME Police Duty",
        durationSec: 600,
    },
];

const formatOptions = [
    {
        value: "MP4 1080p",
        name: "1080p",
        desc: "~35 MB",
        badge: "FHD",
        badgeClass: "bg-[#2563eb]/40 text-[#60a5fa]",
    },
    {
        value: "MP4 720p",
        name: "720p",
        desc: "~18 MB",
        badge: "HD",
        badgeClass: "bg-slate-800 text-slate-400",
    },
    {
        value: "MP4 480p",
        name: "480p",
        desc: "~8 MB",
        badge: "SD",
        badgeClass: "bg-slate-800 text-slate-400",
    },
    {
        value: "MP3 Audio",
        name: "MP3",
        desc: "320kbps",
        badge: "AUDIO",
        badgeClass: "bg-emerald-950/60 text-emerald-400",
    },
];

// Backend Form State
const form = ref({
    youtube_url: "",
    title: "",
    start_time: "00:00:10",
    end_time: "00:00:40",
    format: "MP4 720p",
});

const isSubmitting = ref(false);
const isLoadingClips = ref(false);
const isCheckingUrl = ref(false);
const urlCheckResult = ref(null);
let currentCheckRequestId = 0;
const submitCooldownSec = ref(0);
let cooldownTimer = null;
const formError = ref("");
const successMessage = ref("");
const clipsList = ref([]);
const playerCurrentSeconds = ref(0);
const videoDurationSec = ref(214);
const mainPlayerRef = ref(null);
let pollTimer = null;
let ytTimeTimer = null;

async function checkYoutubeUrl(urlToTest) {
    const targetUrl = (urlToTest || form.value.youtube_url || "").trim();
    if (!targetUrl || targetUrl.length < 10) {
        urlCheckResult.value = null;
        return;
    }

    const requestId = ++currentCheckRequestId;

    isCheckingUrl.value = true;
    try {
        const response = await axios.post("/api/v1/clips/check-url", {
            url: targetUrl,
        });
        // Guard against out-of-order asynchronous responses
        if (
            requestId !== currentCheckRequestId ||
            targetUrl !== form.value.youtube_url?.trim()
        ) {
            return;
        }

        if (response.data && response.data.valid) {
            urlCheckResult.value = response.data;
            if (response.data.title) {
                form.value.title = response.data.title;
            }
        } else {
            urlCheckResult.value = {
                valid: false,
                message: response.data.message || "URL YouTube tidak valid.",
            };
        }
    } catch (err) {
        if (
            requestId !== currentCheckRequestId ||
            targetUrl !== form.value.youtube_url?.trim()
        ) {
            return;
        }
        urlCheckResult.value = {
            valid: false,
            message:
                err.response?.data?.message ||
                "Gagal memvalidasi link YouTube.",
        };
    } finally {
        if (requestId === currentCheckRequestId) {
            isCheckingUrl.value = false;
        }
    }
}

function startSubmitCooldown(seconds = 5) {
    submitCooldownSec.value = seconds;
    if (cooldownTimer) clearInterval(cooldownTimer);
    cooldownTimer = setInterval(() => {
        if (submitCooldownSec.value > 0) {
            submitCooldownSec.value--;
        } else {
            clearInterval(cooldownTimer);
            cooldownTimer = null;
        }
    }, 1000);
}

const videoTitle = computed(() => {
    if (urlCheckResult.value?.title) {
        return urlCheckResult.value.title;
    }
    if (activeVideoId.value) {
        return `YouTube Video Stream [${activeVideoId.value}]`;
    }
    return form.value.title || "Demo Video Reference Stream";
});

const videoChannel = computed(() => {
    if (urlCheckResult.value?.channel_name) {
        return urlCheckResult.value.channel_name;
    }
    return activeVideoId.value ? "YouTube Live Embed" : "Local Reference";
});

const videoSrc = ref(
    "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
);

// Parse YouTube Video ID
const activeVideoId = computed(() => {
    const url = form.value.youtube_url || "";
    if (!url) return "";
    const regExp =
        /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|shorts\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return match && match[2].length === 11 ? match[2] : "";
});

// YouTube Embed Iframe Listener & Polling
function initYTMessageListener() {
    window.addEventListener("message", (event) => {
        if (!event.data) return;
        let data = event.data;
        if (typeof data === "string") {
            try {
                data = JSON.parse(data);
            } catch (e) {
                return;
            }
        }
        if (data && (data.event === "infoDelivery" || data.info)) {
            const info = data.info || data;
            if (info && typeof info.currentTime === "number") {
                playerCurrentSeconds.value = Math.floor(info.currentTime);
            }
            if (
                info &&
                typeof info.duration === "number" &&
                info.duration > 0
            ) {
                videoDurationSec.value = Math.floor(info.duration);
            }
        }
    });
}

function startYTTimePolling() {
    stopYTTimePolling();
    ytTimeTimer = setInterval(() => {
        const iframe = document.getElementById("youtube-preview-player");
        if (iframe && iframe.contentWindow && activeVideoId.value) {
            try {
                iframe.contentWindow.postMessage(
                    JSON.stringify({
                        event: "listening",
                        id: 1,
                        channel: "widget",
                    }),
                    "*",
                );
                iframe.contentWindow.postMessage(
                    JSON.stringify({
                        event: "command",
                        func: "getCurrentTime",
                        args: [],
                    }),
                    "*",
                );
                iframe.contentWindow.postMessage(
                    JSON.stringify({
                        event: "command",
                        func: "getDuration",
                        args: [],
                    }),
                    "*",
                );
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
    const parts = String(val).trim().split(":").map(Number);
    if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
    if (parts.length === 2) return parts[0] * 60 + parts[1];
    if (parts.length === 1) return parts[0] || 0;
    return 0;
}

function formatSecondsToTimestamp(totalSecs) {
    if (isNaN(totalSecs) || totalSecs < 0) return "00:00:00";
    const hrs = Math.floor(totalSecs / 3600);
    const mins = Math.floor((totalSecs % 3600) / 60);
    const secs = totalSecs % 60;
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(hrs)}:${pad(mins)}:${pad(secs)}`;
}

const calculatedSeconds = computed(() => {
    const start = parseTimeToSeconds(form.value.start_time);
    const end = parseTimeToSeconds(form.value.end_time);
    return end > start ? end - start : 0;
});

const isDurationValid = computed(() => {
    return (
        calculatedSeconds.value > 0 &&
        calculatedSeconds.value <= maxClipDurationSec.value
    );
});

// Fetch user's Clipper request status
async function fetchClipperRequestStatus() {
    if (!user.value || isClipperOrAdmin.value) return;
    try {
        const res = await axios.get("/api/v1/clipper-requests/status");
        if (res.data && res.data.status) {
            clipperRequestStatus.value = res.data.status;
        }
    } catch (err) {}
}

// Submit a new Clipper Role request
async function submitClipperRequest() {
    isSubmittingRequest.value = true;
    requestSuccessMsg.value = "";
    requestErrMsg.value = "";
    try {
        const res = await axios.post("/api/v1/clipper-requests", {
            request_type: clipperRequestType.value,
            channel_url: clipperChannelUrl.value,
            reason: clipperRequestReason.value,
        });
        clipperRequestStatus.value = "pending";
        requestSuccessMsg.value =
            res.data.message || "Pengajuan berhasil dikirim!";
        setTimeout(() => {
            showRequestClipperModal.value = false;
        }, 2000);
    } catch (err) {
        requestErrMsg.value =
            err.response?.data?.message || "Gagal mengirim pengajuan.";
    } finally {
        isSubmittingRequest.value = false;
    }
}

// Clipper Delegation API Calls
async function fetchDelegations() {
    if (!user.value?.can_delegate_clipper) return;
    isLoadingDelegation.value = true;
    try {
        const res = await axios.get("/api/v1/clipper-delegations");
        delegationData.value = res.data;
    } catch (e) {
        console.error("Failed to fetch delegations:", e);
    } finally {
        isLoadingDelegation.value = false;
    }
}

async function assignDelegation() {
    if (!assignEmailInput.value) {
        delegationErrMsg.value =
            "Silakan masukkan alamat email akun member yang ingin Anda tunjuk!";
        return;
    }
    isAssigningDelegation.value = true;
    delegationErrMsg.value = "";
    delegationSuccessMsg.value = "";
    try {
        const res = await axios.post("/api/v1/clipper-delegations/assign", {
            email: assignEmailInput.value,
            tier: assignTierInput.value,
        });
        delegationSuccessMsg.value =
            res.data.message || "Berhasil menunjuk akun sebagai Clipper!";
        assignEmailInput.value = "";
        await fetchDelegations();
    } catch (err) {
        delegationErrMsg.value =
            err.response?.data?.message ||
            err.response?.data?.errors?.email?.[0] ||
            "Gagal menunjuk akun.";
    } finally {
        isAssigningDelegation.value = false;
    }
}

async function revokeDelegation(id, targetName) {
    if (
        !confirm(
            `Apakah Anda yakin ingin mencabut penunjukan Clipper untuk ${targetName}? Slot penunjukan Anda akan kembali bertambah.`,
        )
    )
        return;
    delegationErrMsg.value = "";
    delegationSuccessMsg.value = "";
    try {
        const res = await axios.delete(`/api/v1/clipper-delegations/${id}`);
        delegationSuccessMsg.value =
            res.data.message || "Penunjukan berhasil dicabut.";
        await fetchDelegations();
    } catch (err) {
        delegationErrMsg.value =
            err.response?.data?.message || "Gagal mencabut penunjukan.";
    }
}

const formattedDuration = computed(() => {
    const secs = calculatedSeconds.value;
    if (secs <= 0) return "00:00 (Waktu Tidak Valid)";
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}m ${s}s (${secs}d)`;
});

const trimStartSecs = computed(() => parseTimeToSeconds(form.value.start_time));
const trimEndSecs = computed(() => parseTimeToSeconds(form.value.end_time));

function onTrimmerStartUpdate(val) {
    form.value.start_time = formatSecondsToTimestamp(val);
}

function onTrimmerEndUpdate(val) {
    form.value.end_time = formatSecondsToTimestamp(val);
}

function onTrimmerSeek(sec) {
    playerCurrentSeconds.value = sec;
    if (
        mainPlayerRef.value &&
        typeof mainPlayerRef.value.seekTo === "function"
    ) {
        mainPlayerRef.value.seekTo(sec);
    }
}

function formatSeconds(secs) {
    if (!secs) return "0d";
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}m ${s}s`;
}

function nudgeTime(type, delta) {
    if (type === "in") {
        const cur = parseTimeToSeconds(form.value.start_time);
        const endSecs = parseTimeToSeconds(form.value.end_time);
        const next = Math.max(0, Math.min(endSecs - 1, cur + delta));
        form.value.start_time = formatSecondsToTimestamp(next);
        playerCurrentSeconds.value = next;
    } else if (type === "out") {
        const cur = parseTimeToSeconds(form.value.end_time);
        const startSecs = parseTimeToSeconds(form.value.start_time);
        const maxLimit = videoDurationSec.value || 86400;
        const next = Math.max(startSecs + 1, Math.min(maxLimit, cur + delta));
        form.value.end_time = formatSecondsToTimestamp(next);
    }
}

function autoClampDuration() {
    const startSecs = parseTimeToSeconds(form.value.start_time);
    const clampedEnd = startSecs + maxClipDurationSec.value;
    const finalEnd = videoDurationSec.value
        ? Math.min(videoDurationSec.value, clampedEnd)
        : clampedEnd;
    form.value.end_time = formatSecondsToTimestamp(finalEnd);
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
    form.value.start_time = formatSecondsToTimestamp(
        playerCurrentSeconds.value,
    );
}

function captureEndTime() {
    form.value.end_time = formatSecondsToTimestamp(playerCurrentSeconds.value);
}

function setPresetDuration(secondsOffset) {
    formError.value = "";
    const startSecs = parseTimeToSeconds(form.value.start_time);
    let targetEndSecs = startSecs + secondsOffset;

    if (videoDurationSec.value && videoDurationSec.value > 0) {
        if (startSecs >= videoDurationSec.value) {
            formError.value = `Waktu mulai (${formatSecondsToTimestamp(startSecs)}) sudah melebihi durasi total video (${formatSecondsToTimestamp(videoDurationSec.value)})!`;
            return;
        }

        if (targetEndSecs > videoDurationSec.value) {
            targetEndSecs = videoDurationSec.value;
            successMessage.value = `Durasi otomatis disesuaikan hingga akhir video (${formatSecondsToTimestamp(videoDurationSec.value)}) karena total durasi video kurang dari +${Math.floor(secondsOffset / 60)}m.`;
        } else {
            successMessage.value = "";
        }
    } else {
        successMessage.value = "";
    }

    form.value.end_time = formatSecondsToTimestamp(targetEndSecs);
}

function setSampleVideo(idx) {
    const sample = sampleVideos[idx];
    if (!sample) return;
    form.value.youtube_url = sample.youtubeUrl;
    form.value.title = sample.title;
    form.value.start_time = "00:00:10";
    form.value.end_time = "00:00:40";
    if (sample.durationSec) {
        videoDurationSec.value = sample.durationSec;
    }
    checkYoutubeUrl(sample.youtubeUrl);
}

function loadVideoFromInput() {
    if (!form.value.youtube_url) {
        formError.value = "Silakan masukkan URL YouTube terlebih dahulu!";
    } else {
        formError.value = "";
    }
}

function handleGuestAuth(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (isGuest.value) {
        window.location.href = "/login";
    }
}

// Submit Trim Job to Laravel API
async function submitTrim() {
    if (isGuest.value) {
        handleGuestAuth();
        return;
    }

    if (submitCooldownSec.value > 0) return;

    if (!isClipperOrAdmin.value && userDailyRemaining.value <= 0) {
        formError.value =
            "Kuota pemotongan video gratis Anda hari ini (3/3 klip) telah habis. Silakan ajukan Role Clipper untuk kuota unlimited!";
        return;
    }

    if (!isDurationValid.value) {
        const maxTxt = isClipperOrAdmin.value
            ? "10 menit (600s)"
            : "1 menit (60s)";
        formError.value = `Durasi pemotongan tidak valid atau melebihi batas ${maxTxt}.`;
        return;
    }

    // Anti-Spam: Check if an identical clip is already being processed in queue
    const startSecs = parseTimeToSeconds(form.value.start_time);
    const endSecs = parseTimeToSeconds(form.value.end_time);

    const isDuplicatePending = clipsList.value.some((c) => {
        const matchUrl =
            c.youtube_url === form.value.youtube_url ||
            (activeVideoId.value &&
                c.youtube_url.includes(activeVideoId.value));
        const matchStart = Number(c.start_time) === startSecs;
        const matchEnd = Number(c.end_time) === endSecs;
        const isPending = c.status === "pending" || c.status === "processing";
        return matchUrl && matchStart && matchEnd && isPending;
    });

    if (isDuplicatePending) {
        formError.value =
            "Permintaan klip dengan URL dan rentang waktu yang sama sedang diproses di antrean server!";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";
    successMessage.value = "";

    try {
        const response = await axios.post("/api/v1/clips/trim", form.value);
        successMessage.value =
            response.data.message ||
            "Proses pemotongan video telah dimasukkan ke dalam antrean server!";
        startSubmitCooldown(5);
        await fetchClips();

        // Smooth scroll to clips library section so user sees the progress indicator
        const libraryEl = document.getElementById("library-section");
        if (libraryEl) {
            libraryEl.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    } catch (err) {
        if (err.response && err.response.data && err.response.data.message) {
            formError.value = err.response.data.message;
        } else if (
            err.response &&
            err.response.data &&
            err.response.data.errors
        ) {
            const firstErr = Object.values(err.response.data.errors)[0];
            formError.value = Array.isArray(firstErr) ? firstErr[0] : firstErr;
        } else {
            formError.value = "Terjadi kesalahan saat membuat klip video.";
        }
    } finally {
        isSubmitting.value = false;
    }
}

// Fetch Clips List from Laravel API
async function fetchClips() {
    if (isGuest.value) return;
    isLoadingClips.value = true;
    try {
        const response = await axios.get("/api/v1/clips");
        clipsList.value = response.data.data || response.data || [];
    } catch (err) {
        console.error("Failed to fetch clips list:", err);
    } finally {
        isLoadingClips.value = false;
    }
}

async function deleteClip(id) {
    if (
        !confirm(
            "Apakah Anda yakin ingin menghapus klip video ini dari server?",
        )
    )
        return;
    try {
        await axios.delete(`/api/v1/clips/${id}`);
        clipsList.value = clipsList.value.filter((c) => c.id !== id);
    } catch (err) {
        alert("Gagal menghapus klip video.");
    }
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(() => {
        const hasPendingOrProcessing = clipsList.value.some(
            (c) => c.status === "pending" || c.status === "processing",
        );
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
    if (typeof window !== "undefined") {
        const urlParams = new URLSearchParams(window.location.search);
        const queryUrl = urlParams.get("url");
        const queryStart = urlParams.get("start");
        const queryEnd = urlParams.get("end");
        const queryTitle = urlParams.get("title");

        if (queryUrl) {
            form.value.youtube_url = queryUrl;
        } else if (props.initialUrl) {
            form.value.youtube_url = props.initialUrl;
        }

        if (queryTitle) {
            form.value.title = queryTitle;
        }

        if (
            queryStart !== null &&
            queryStart !== undefined &&
            queryStart !== ""
        ) {
            const startSec = parseInt(queryStart, 10);
            if (!isNaN(startSec)) {
                form.value.start_time = formatSecondsToTimestamp(startSec);
            }
        }

        if (queryEnd !== null && queryEnd !== undefined && queryEnd !== "") {
            const endSec = parseInt(queryEnd, 10);
            if (!isNaN(endSec)) {
                form.value.end_time = formatSecondsToTimestamp(endSec);
            }
        }
    } else if (props.initialUrl) {
        form.value.youtube_url = props.initialUrl;
    }

    initYTMessageListener();
    if (form.value.youtube_url) {
        checkYoutubeUrl(form.value.youtube_url);
    }
    if (activeVideoId.value) {
        startYTTimePolling();
    }
    fetchClips();
    fetchClipperRequestStatus();
    if (isClipperOrAdmin.value) {
        fetchDelegations();
    }
    startPolling();
});

onUnmounted(() => {
    stopPolling();
    stopYTTimePolling();
});
</script>
