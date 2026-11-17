<template>
    <TacticalLayout>
        <Head title="ClipStudio - Tactical Video Clipper & Media Hub" />

        <PageContainer class="pt-6 pb-20 space-y-8 font-sans">
            <!-- HEADER / HERO & URL INPUT SECTION -->
            <section class="text-center max-w-4xl mx-auto space-y-3">
                <h1
                    class="text-2xl sm:text-3xl md:text-4xl font-bold text-foreground tracking-tight leading-tight uppercase font-tactical"
                >
                    ClipStudio —
                    <span class="text-primary">Tactical Video Trimmer</span>
                </h1>
                <p
                    class="text-xs sm:text-sm text-muted-foreground max-w-xl mx-auto font-sans leading-relaxed"
                >
                    Pemotongan video YouTube presisi tinggi untuk siaran
                    langsung dan tayangan perwira.
                </p>

                <!-- URL Loader Bar -->
                <div class="pt-2 max-w-2xl mx-auto">
                    <div
                        class="relative flex items-center bg-card border border-border rounded-xl p-1.5 focus-within:border-primary transition duration-200"
                    >
                        <div class="pl-3.5 text-destructive shrink-0">
                            <Video class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.youtube_url"
                            type="url"
                            @keyup.enter="loadVideoFromInput"
                            placeholder="Tempel tautan YouTube di sini (contoh: https://www.youtube.com/watch?v=...)"
                            class="w-full bg-transparent border-0 focus:border-0 focus:ring-0 focus:outline-hidden px-3.5 py-2.5 text-xs sm:text-sm text-foreground placeholder:text-muted-foreground font-mono shadow-none outline-hidden"
                        />
                        <Button
                            @click="loadVideoFromInput"
                            :disabled="isCheckingUrl"
                            class="shrink-0"
                        >
                            <Loader2
                                v-if="isCheckingUrl"
                                class="w-4 h-4 animate-spin"
                            />
                            <Zap v-else class="w-4 h-4" />
                            <span>{{
                                isCheckingUrl ? "Memuat..." : "Muat Stream"
                            }}</span>
                        </Button>
                    </div>

                    <!-- Quick Sample Videos -->
                    <div
                        class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs text-muted-foreground"
                    >
                        <span>Contoh Stream:</span>
                        <Button
                            v-for="(sample, idx) in sampleVideos"
                            :key="idx"
                            variant="outline"
                            size="sm"
                            @click="setSampleVideo(idx)"
                            class="h-7 text-xs cursor-pointer"
                        >
                            {{ sample.shortLabel }}
                        </Button>
                    </div>
                </div>
            </section>

            <!-- NOTIFICATION BANNERS -->
            <Alert
                v-if="formError"
                variant="destructive"
                class="p-4 flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="w-5 h-5 shrink-0" />
                    <AlertDescription>{{ formError }}</AlertDescription>
                </div>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Tutup pesan error"
                    @click="formError = ''"
                >
                    <X class="w-4 h-4" />
                </Button>
            </Alert>

            <Alert
                v-if="successMessage"
                class="p-4 border-success/40 bg-success/10 text-success flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2.5">
                    <Check class="w-5 h-5 shrink-0" />
                    <AlertDescription>{{ successMessage }}</AlertDescription>
                </div>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Tutup pesan sukses"
                    @click="successMessage = ''"
                >
                    <X class="w-4 h-4" />
                </Button>
            </Alert>

            <!-- INTERACTIVE TRIMMER WORKSPACE -->
            <section class="space-y-6 font-sans">
                <!-- Invalid URL Warning Banner -->
                <Alert
                    v-if="urlCheckResult && !urlCheckResult.valid"
                    class="p-3 rounded-xl bg-warning/10 border-warning/40 text-warning flex items-center gap-2.5 text-xs"
                >
                    <AlertTriangle class="w-4 h-4 shrink-0" />
                    <AlertDescription>{{ urlCheckResult.message }}</AlertDescription>
                </Alert>

                <!-- Post-Live / Processing Warning Banner -->
                <Alert
                    v-if="urlCheckResult && urlCheckResult.is_post_live"
                    class="p-3.5 rounded-xl bg-warning/15 border-warning/50 text-warning flex items-start gap-3 text-xs sm:text-sm"
                >
                    <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <div class="font-bold uppercase tracking-wide font-mono flex items-center gap-1.5">
                            <span>Status: Siaran Baru Berakhir (Processing VOD)</span>
                        </div>
                        <p class="leading-relaxed text-xs font-sans">
                            {{ urlCheckResult.message }}
                        </p>
                    </div>
                </Alert>

                <!-- Main Video Preview & Controls Grid (8:4 Balanced Layout) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- LEFT COLUMN: VIDEO PLAYER & TIMELINE TRIMMER (8 cols) -->
                    <div class="lg:col-span-8 space-y-4">
                        <!-- Video Player Box -->
                        <div
                            class="w-full aspect-video rounded-xl overflow-hidden bg-background relative border border-border shadow-xl"
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

                        <!-- TIMELINE TRIMMER CARD -->
                        <Card
                            class="lg:bg-card lg:border lg:border-border lg:rounded-2xl lg:p-4 space-y-3.5 border-0 bg-transparent p-0 shadow-none lg:shadow-xl"
                        >
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-primary font-bold text-xs uppercase tracking-wider"
                                >
                                    <Scissors class="w-4 h-4 text-primary shrink-0" />
                                    <span>Timeline Trimmer</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :variant="isDurationValid ? 'success' : 'destructive'"
                                        class="text-xs font-mono px-3 py-1 font-bold shrink-0"
                                    >
                                        {{
                                            isDurationValid
                                                ? `${formatSeconds(maxClipDurationSec)}`
                                                : "Melebihi Batas"
                                        }}
                                    </Badge>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="openMobileClipperMenu"
                                        class="lg:hidden font-mono text-xs font-bold flex items-center gap-1.5 cursor-pointer shrink-0"
                                        title="Buka Menu Opsi & Format Ekspor"
                                    >
                                        <SlidersHorizontal class="w-4 h-4 text-primary" />
                                        <span>Menu</span>
                                    </Button>
                                </div>
                            </div>

                            <!-- Waktu Pemutar Header Indicator -->
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs font-semibold text-muted-foreground"
                                    >Waktu Pemutar:</span
                                >
                                <div
                                    class="bg-muted border border-border rounded-lg px-2.5 py-1 font-mono font-bold text-xs text-foreground tracking-wider"
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
                                            class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                                        >
                                            Mulai
                                        </label>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            @click="captureStartTime"
                                            class="h-6 px-2.5 text-xs font-mono font-bold cursor-pointer"
                                            title="Set waktu mulai dari posisi pemutar video saat ini"
                                        >
                                            <span>Pilih Waktu</span>
                                        </Button>
                                    </div>
                                    <Input
                                        v-model="form.start_time"
                                        type="text"
                                        placeholder="00:00:00"
                                        class="text-xs font-mono text-center font-bold"
                                    />
                                    <span
                                        class="text-xs text-muted-foreground block mt-0.5"
                                        >Format: HH:MM:SS</span
                                    >
                                </div>

                                <div>
                                    <div
                                        class="flex items-center justify-between mb-1"
                                    >
                                        <label
                                            class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                                        >
                                            Selesai
                                        </label>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            @click="captureEndTime"
                                            class="h-6 px-2.5 text-xs font-mono font-bold cursor-pointer"
                                            title="Set waktu selesai dari posisi pemutar video saat ini"
                                        >
                                            <span>Pilih Waktu</span>
                                        </Button>
                                    </div>
                                    <Input
                                        v-model="form.end_time"
                                        type="text"
                                        placeholder="00:05:00"
                                        class="text-xs font-mono text-center font-bold"
                                    />
                                    <span
                                        class="text-xs text-muted-foreground block mt-0.5"
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
                        </Card>
                    </div>

                    <!-- RIGHT COLUMN: EXPORT PARAMETERS & SIDEBAR (4 cols) -->
                    <div class="lg:col-span-4 space-y-4">
                        <!-- Header Section -->
                        <div
                            class="flex items-center justify-between pb-3.5 border-b border-border"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/30 flex items-center justify-center text-primary shrink-0"
                                >
                                    <Download class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2
                                        class="text-sm font-semibold tracking-wide uppercase text-foreground font-sans"
                                    >
                                        Opsi Ekspor & Format
                                    </h2>
                                    <p
                                        class="text-xs text-muted-foreground font-sans hidden lg:block"
                                    >
                                        Pilih kualitas output video Anda
                                    </p>
                                </div>
                            </div>

                            <!-- Duration Cap Indicator Pill -->
                            <div
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-muted border border-border text-xs font-medium text-foreground font-mono"
                            >
                                <span class="text-muted-foreground font-sans"
                                    >Batas:</span
                                >
                                <span class="text-primary font-bold">{{
                                    formatSeconds(maxClipDurationSec)
                                }}</span>
                            </div>
                        </div>

                        <!-- Format Selection Options -->
                        <section
                            class="space-y-2.5"
                            aria-label="Pilihan Kualitas Video"
                        >
                            <div
                                class="flex items-center justify-between px-0.5"
                            >
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-muted-foreground font-sans"
                                    >Format & Kualitas</span
                                >
                                <span
                                    class="text-xs text-muted-foreground font-sans hidden lg:inline"
                                    >Durasi Terpilih:
                                    {{ formatSeconds(calculatedSeconds) }}</span
                                >
                            </div>

                            <div
                                class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-1 gap-2.5"
                            >
                                <button
                                    v-for="fmt in formatOptions"
                                    :key="fmt.value"
                                    type="button"
                                    @click="selectFormat(fmt.value, $event)"
                                    class="relative flex items-center justify-between p-3 rounded-xl border transition-all duration-200 cursor-pointer select-none text-left min-w-0"
                                    :class="[
                                        form.format === fmt.value
                                            ? 'border-primary/70 bg-primary/10 shadow-md ring-1 ring-primary/40'
                                            : 'border-border bg-card hover:bg-muted/50',
                                        fmt.value === 'MP4 1080p' &&
                                        !canUse1080p &&
                                        !isGuest
                                            ? 'opacity-60 bg-muted/40 border-dashed border-warning/40'
                                            : '',
                                        isGuest ? 'opacity-80' : '',
                                    ]"
                                >
                                    <div
                                        class="flex items-start gap-3 min-w-0 flex-1"
                                    >
                                        <!-- Radio Circle Indicator -->
                                        <div
                                            class="w-4 h-4 rounded-full border mt-0.5 flex items-center justify-center shrink-0 transition-colors"
                                            :class="
                                                form.format === fmt.value
                                                    ? 'border-primary'
                                                    : 'border-muted-foreground'
                                            "
                                        >
                                            <div
                                                class="w-2 h-2 rounded-full transition-all"
                                                :class="
                                                    form.format === fmt.value
                                                        ? 'bg-primary'
                                                        : 'bg-transparent'
                                                "
                                            ></div>
                                        </div>

                                        <div class="space-y-0.5 min-w-0 flex-1">
                                            <div
                                                class="flex items-center gap-2 flex-wrap"
                                            >
                                                <span
                                                    class="text-sm font-semibold text-foreground font-sans truncate"
                                                    >{{ fmt.name }}</span
                                                >
                                                <span
                                                    class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0 flex items-center gap-1"
                                                    :class="
                                                        fmt.value ===
                                                            'MP4 1080p' &&
                                                        !canUse1080p &&
                                                        !isGuest
                                                            ? 'bg-warning/15 text-warning border border-warning/40'
                                                            : fmt.tagClass
                                                    "
                                                >
                                                    <Lock
                                                        v-if="
                                                            fmt.value ===
                                                                'MP4 1080p' &&
                                                            !canUse1080p &&
                                                            !isGuest
                                                        "
                                                        class="w-3 h-3 text-warning shrink-0"
                                                    />
                                                    <span>{{
                                                        fmt.value ===
                                                            "MP4 1080p" &&
                                                        !canUse1080p &&
                                                        !isGuest
                                                            ? "Khusus PRO / Clipper"
                                                            : fmt.tag
                                                    }}</span>
                                                </span>
                                            </div>
                                            <!-- Desktop Use Case Subtitle -->
                                            <p
                                                class="hidden lg:block text-xs text-muted-foreground leading-normal font-sans"
                                            >
                                                {{ fmt.useCase }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Right Est Size / Bitrate Badge -->
                                    <div
                                        class="hidden lg:block pl-3 shrink-0 text-right"
                                    >
                                        <Badge variant="outline" class="font-mono text-xs">
                                            {{ fmt.desc }}
                                        </Badge>
                                    </div>
                                </button>
                            </div>
                        </section>

                        <!-- SUMMARY METADATA CARD -->
                        <Card
                            class="bg-card rounded-xl border-border p-3.5 space-y-2.5 font-sans"
                        >
                            <div
                                class="flex items-center justify-between pb-2 border-b border-border"
                            >
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
                                >
                                    <BarChart3 class="w-3.5 h-3.5 text-primary" />
                                    Ringkasan Ekspor
                                </span>
                                <Badge variant="secondary" class="font-mono text-xs">
                                    {{
                                        formatOptions.find(
                                            (f) => f.value === form.format,
                                        )?.name || form.format.toUpperCase()
                                    }}
                                </Badge>
                            </div>

                            <div
                                class="grid grid-cols-2 gap-2 text-xs font-mono"
                            >
                                <!-- Durasi Klip -->
                                <div
                                    class="bg-muted/50 p-2.5 rounded-lg border border-border"
                                >
                                    <div
                                        class="text-xs text-muted-foreground flex items-center gap-1 mb-1 font-sans"
                                    >
                                        <Clock class="w-3 h-3 text-muted-foreground" />
                                        Durasi Klip
                                    </div>
                                    <div class="font-medium text-foreground">
                                        {{ formatSeconds(calculatedSeconds) }}
                                        <span
                                            class="text-xs text-muted-foreground font-sans"
                                            >({{ calculatedSeconds }}s)</span
                                        >
                                    </div>
                                </div>

                                <!-- Masa Simpan Cloud -->
                                <div
                                    class="bg-muted/50 p-2.5 rounded-lg border border-border"
                                >
                                    <div
                                        class="text-xs text-muted-foreground flex items-center gap-1 mb-1 font-sans"
                                    >
                                        <ShieldCheck class="w-3 h-3 text-muted-foreground" />
                                        Masa Simpan Cloud
                                    </div>
                                    <div
                                        class="font-medium text-foreground font-sans text-xs"
                                    >
                                        1 Jam
                                        <span class="text-muted-foreground text-xs"
                                            >(Auto-clean)</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- QUOTA & AUTHENTICATION BANNER -->
                        <Card
                            class="rounded-xl p-3 bg-card border-border font-sans text-xs space-y-2"
                        >
                            <div
                                class="flex items-center justify-between"
                            >
                                <span
                                    class="text-muted-foreground flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-warning motion-safe:animate-pulse"
                                    ></span>
                                    Sisa Kuota {{ quotaPeriodLabel }}:
                                </span>
                                <span
                                    class="font-mono font-bold text-foreground"
                                >
                                    {{
                                        isUnlimitedQuota
                                            ? "Unlimited"
                                            : `${userDailyRemaining} dari ${dailyClipsLimit} Klip`
                                    }}
                                </span>
                            </div>

                            <!-- Quota progress visual -->
                            <Progress
                                v-if="!isUnlimitedQuota"
                                :model-value="Math.min(100, (dailyClipsCount / dailyClipsLimit) * 100)"
                                class="h-1.5"
                            />

                            <div
                                class="flex items-center justify-between text-xs text-muted-foreground pt-0.5"
                            >
                                <template v-if="isGuest">
                                    <span>Guest: 3 klip gratis / minggu</span>
                                    <a
                                        href="/login"
                                        class="text-primary hover:underline font-medium underline-offset-2 transition-colors"
                                    >
                                        Masuk Sekarang
                                    </a>
                                </template>
                                <template v-else-if="isUnlimitedQuota">
                                    <span class="text-muted-foreground"
                                        >PRO Ultimate / Officer Active
                                        (Unlimited)</span
                                    >
                                </template>
                                <template v-else-if="userDailyRemaining <= 0">
                                    <span class="text-warning font-semibold"
                                        >Kuota
                                        {{ quotaPeriodLabel.toLowerCase() }}
                                        habis</span
                                    >
                                    <button
                                        @click="showRequestClipperModal = true"
                                        class="text-primary hover:underline font-bold text-xs inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        <span>Pengajuan Akses</span>
                                        <ArrowRight class="w-3 h-3 shrink-0" />
                                    </button>
                                </template>
                                <template v-else>
                                    <span
                                        >Tersisa
                                        <strong class="text-foreground font-mono"
                                            >{{
                                                userDailyRemaining
                                            }}
                                            klip</strong
                                        >
                                        {{ quotaPeriodLabel.toLowerCase() }} ({{
                                            user?.clipper_tier_name || "Free"
                                        }})</span
                                    >
                                </template>
                            </div>
                        </Card>

                        <!-- PRIMARY CTA BUTTON -->
                        <div class="mt-1 flex flex-col gap-2">
                            <Button
                                @click="submitTrim"
                                :disabled="
                                    isSubmitting ||
                                    (!isGuest && !isDurationValid) ||
                                    submitCooldownSec > 0
                                "
                                size="lg"
                                class="w-full py-3.5 lg:py-4 px-4 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer font-sans"
                            >
                                <Loader2
                                    v-if="isSubmitting"
                                    class="animate-spin h-5 w-5 shrink-0"
                                />
                                <span
                                    v-else-if="submitCooldownSec > 0"
                                    class="font-mono"
                                    >Tunggu Cooldown ({{
                                        submitCooldownSec
                                    }}s)...</span
                                >
                                <template v-else-if="isGuest">
                                    <LogIn class="w-4 h-4 shrink-0" />
                                    <span>Masuk untuk Memotong Video</span>
                                </template>
                                <template
                                    v-else-if="
                                        !isUnlimitedQuota &&
                                        userDailyRemaining <= 0
                                    "
                                >
                                    <Lock class="w-4 h-4 shrink-0" />
                                    <span
                                        >Kuota {{ quotaPeriodLabel }} Habis ({{
                                            dailyClipsCount
                                        }}/{{ dailyClipsLimit }} Klip)</span
                                    >
                                </template>
                                <template v-else>
                                    <Scissors class="w-4 h-4 shrink-0" />
                                    <span>{{
                                        urlCheckResult && urlCheckResult.is_live
                                            ? "POTONG KLIP LIVE (HLS DVR)"
                                            : `POTONG & SIMPAN KLIP (${form.format.toUpperCase()})`
                                    }}</span>
                                </template>
                            </Button>

                            <!-- Dynamic Live Progress Status Bar Container -->
                            <div
                                v-if="isSubmitting"
                                class="bg-muted p-2.5 rounded-xl border border-border flex flex-col gap-1.5 font-mono text-xs"
                            >
                                <div
                                    class="flex justify-between items-center text-xs"
                                >
                                    <span
                                        class="text-info flex items-center gap-1.5 font-semibold motion-safe:animate-pulse"
                                    >
                                        <Loader2 class="w-3 h-3 animate-spin text-info" />
                                        Merender klip video di server FFmpeg...
                                    </span>
                                    <span class="text-foreground font-bold"
                                        >Memproses</span
                                    >
                                </div>
                                <Progress :model-value="100" class="h-1.5 motion-safe:animate-pulse" />
                            </div>
                        </div>

                        <!-- LOGGED-IN ACCOUNT CONTROL CARD OR GUEST PLAN COMPARISON TEASER -->
                        <Card
                            class="bg-card border-border rounded-2xl p-4 shadow-xl flex flex-col gap-3 font-sans mt-3"
                        >
                            <!-- VIEW A: FOR LOGGED IN USERS -->
                            <template v-if="!isGuest">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-lg bg-primary/10 border border-primary/30 flex items-center justify-center text-primary shrink-0"
                                        >
                                            <ShieldCheck class="w-4 h-4 text-primary" />
                                        </div>
                                        <div>
                                            <h4
                                                class="text-xs font-bold text-foreground uppercase tracking-wider"
                                            >
                                                Status Akun & Tier Aktif
                                            </h4>
                                            <p
                                                class="text-xs text-muted-foreground font-mono truncate max-w-[150px]"
                                            >
                                                {{ user?.name || user?.email }}
                                            </p>
                                        </div>
                                    </div>

                                    <Badge
                                        :variant="isUnlimitedQuota ? 'warning' : 'secondary'"
                                        class="font-mono text-xs font-bold uppercase shrink-0"
                                    >
                                        {{
                                            user?.clipper_tier_name ||
                                            "Free Tier"
                                        }}
                                    </Badge>
                                </div>

                                <!-- Dynamic Capabilities Badge & Controls -->
                                <div
                                    class="bg-muted/50 border border-border rounded-xl p-3 space-y-2 text-xs font-mono"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-muted-foreground"
                                            >Durasi Maks:</span
                                        >
                                        <span
                                            class="text-foreground font-bold"
                                            >{{
                                                formatSeconds(
                                                    maxClipDurationSec,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-muted-foreground"
                                            >Kualitas Maks:</span
                                        >
                                        <span
                                            class="text-success font-bold"
                                            >{{
                                                canUse1080p
                                                    ? "1080p Lossless"
                                                    : "720p HD"
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-muted-foreground"
                                            >Potong Live DVR:</span
                                        >
                                        <span
                                            :class="
                                                isUnlimitedQuota
                                                    ? 'text-success font-bold'
                                                    : 'text-muted-foreground'
                                            "
                                            >{{
                                                isUnlimitedQuota
                                                    ? "Aktif"
                                                    : "Terbatas"
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Action Buttons depending on user role -->
                                <div class="flex flex-col gap-2 pt-1">
                                    <Button
                                        v-if="
                                            !isUnlimitedQuota &&
                                            clipperRequestStatus !== 'pending'
                                        "
                                        variant="outline"
                                        @click="showRequestClipperModal = true"
                                        class="w-full py-2.5 px-3.5 rounded-xl text-xs font-mono flex items-center justify-between cursor-pointer"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 text-foreground"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full bg-success"
                                            ></span>
                                            <span>Akses Clipper Beta</span>
                                        </span>
                                        <span
                                            class="text-primary font-bold hover:underline"
                                            >Daftar (Gratis) →</span
                                        >
                                    </Button>
                                </div>
                            </template>

                            <!-- VIEW B: FOR GUESTS ONLY (Teaser Plan Comparison) -->
                            <template v-else>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-lg bg-primary/10 border border-primary/30 flex items-center justify-center text-primary shrink-0"
                                        >
                                            <Sparkles class="w-4 h-4 text-primary" />
                                        </div>
                                        <div>
                                            <h4
                                                class="text-xs font-bold text-foreground uppercase tracking-wider"
                                            >
                                                Perbedaan Paket & Akses Spesial
                                            </h4>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Pilih akses sesuai kebutuhan
                                                Anda
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Direct Comparison Chips (Free vs PRO / Officer) -->
                                <div
                                    class="grid grid-cols-2 gap-2 text-xs font-mono"
                                >
                                    <div
                                        class="bg-muted/50 border border-border rounded-xl p-2.5 flex flex-col gap-0.5"
                                    >
                                        <span
                                            class="text-muted-foreground text-xs font-bold uppercase"
                                            >FREE MEMBER:</span
                                        >
                                        <span
                                            class="text-foreground font-semibold text-xs"
                                            >3 Klip / Minggu</span
                                        >
                                        <span class="text-muted-foreground text-xs"
                                            >Maks 1 Menit / Klip</span
                                        >
                                    </div>

                                    <div
                                        class="bg-primary/10 border border-primary/30 rounded-xl p-2.5 flex flex-col gap-0.5"
                                    >
                                        <span
                                            class="text-primary text-xs font-bold uppercase"
                                            >OFFICER & PRO:</span
                                        >
                                        <span
                                            class="text-foreground font-semibold text-xs"
                                            >Unlimited Potong</span
                                        >
                                        <span class="text-primary text-xs"
                                            >Hak 2 Slot & Lossless</span
                                        >
                                    </div>
                                </div>

                                <!-- Button to Open Access Request Modal -->
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showRequestClipperModal = true"
                                    class="w-full py-2.5 lg:py-4 lg:min-h-12 px-3.5 rounded-xl text-xs lg:text-sm font-bold flex items-center justify-between group cursor-pointer"
                                >
                                    <div
                                        class="flex items-center gap-2.5 font-mono"
                                    >
                                        <Sparkles class="w-4 h-4 text-primary shrink-0" />
                                        <span>Daftar Akses Clipper Beta</span>
                                    </div>
                                    <div
                                        class="flex items-center gap-1 text-primary group-hover:translate-x-1 transition-transform font-mono text-xs shrink-0"
                                    >
                                        <span>Daftar Gratis</span>
                                        <ArrowRight class="w-3.5 h-3.5 shrink-0" />
                                    </div>
                                </Button>
                            </template>
                        </Card>
                    </div>
                </div>
            </section>

            <!-- DAFTAR KLIP SERVER (CLIPS LIBRARY) -->
            <section id="library-section" class="space-y-6 pt-2">
                <!-- Section Header -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <h2
                                class="text-2xl font-bold text-foreground tracking-tight uppercase font-tactical"
                            >
                                Koleksi Klip Video Saya
                            </h2>
                            <Badge
                                v-if="clipsList.length"
                                variant="secondary"
                                class="font-mono text-xs font-bold"
                            >
                                {{ clipsList.length }} Klip
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Klip video yang telah Anda proses dan siap diunduh.
                            File otomatis dihapus sistem 1 jam setelah dibuat.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="fetchClips"
                            class="text-xs font-semibold flex items-center gap-1.5 font-mono"
                        >
                            <RefreshCw
                                class="w-3.5 h-3.5"
                                :class="
                                    isLoadingClips
                                        ? 'animate-spin text-primary'
                                        : ''
                                "
                            />
                            <span>Refresh List</span>
                        </Button>
                    </div>
                </div>

                <!-- Info Notice -->
                <Alert
                    class="p-3 rounded-md bg-card border-warning/40 text-xs text-warning flex items-center gap-2.5"
                >
                    <Clock class="w-4 h-4 text-warning shrink-0" />
                    <span
                        ><strong>Info Storage Server:</strong> Berkas video
                        hasil pemotongan akan
                        <strong
                            >dihapus secara otomatis oleh sistem 1 jam setelah
                            dibuat</strong
                        >
                        untuk menghemat ruang server.</span
                    >
                </Alert>

                <!-- Loading State -->
                <div
                    v-if="isLoadingClips && clipsList.length === 0"
                    class="py-12 text-center text-muted-foreground text-xs font-mono"
                >
                    <Loader2
                        class="animate-spin h-6 w-6 text-primary mx-auto mb-2"
                    />
                    Memuat daftar klip video server...
                </div>

                <!-- Empty State -->
                <Card
                    v-else-if="clipsList.length === 0"
                    class="py-12 text-center text-muted-foreground text-xs bg-card rounded-xl border-border space-y-2"
                >
                    <p
                        class="font-bold text-foreground uppercase font-tactical text-sm"
                    >
                        Belum ada klip video yang disimpan.
                    </p>
                    <p class="text-muted-foreground font-sans">
                        Gunakan form di atas untuk mulai memotong klip video
                        dari YouTube.
                    </p>
                </Card>

                <!-- Clips Grid List -->
                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
                >
                    <Card
                        v-for="clip in clipsList"
                        :key="clip.id"
                        class="p-4 rounded-xl bg-card border-border flex flex-col justify-between space-y-3 hover:border-primary/40 transition group"
                    >
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <h3
                                    class="font-bold text-sm text-foreground line-clamp-1 group-hover:text-primary transition font-sans"
                                >
                                    {{ clip.title }}
                                </h3>

                                <!-- Status Badges -->
                                <Badge
                                    v-if="clip.status === 'completed'"
                                    variant="success"
                                    class="shrink-0 font-mono text-xs"
                                >
                                    Selesai
                                </Badge>
                                <Badge
                                    v-else-if="clip.status === 'processing'"
                                    variant="info"
                                    class="shrink-0 font-mono text-xs"
                                >
                                    Memproses...
                                </Badge>
                                <Badge
                                    v-else-if="clip.status === 'pending'"
                                    variant="secondary"
                                    class="shrink-0 font-mono text-xs"
                                >
                                    Antrean
                                </Badge>
                                <Badge
                                    v-else-if="clip.status === 'expired'"
                                    variant="outline"
                                    class="shrink-0 font-mono text-xs text-muted-foreground"
                                    title="File fisik video telah dibersihkan sistem setelah 1 jam"
                                >
                                    Kadaluarsa (File Dihapus)
                                </Badge>
                                <Badge
                                    v-else
                                    variant="destructive"
                                    class="shrink-0 font-mono text-xs"
                                >
                                    Gagal
                                </Badge>
                            </div>

                            <p
                                class="text-xs text-muted-foreground font-mono truncate"
                            >
                                {{ clip.youtube_url }}
                            </p>

                            <div
                                class="flex items-center justify-between text-xs text-muted-foreground font-mono pt-1"
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
                                    <Badge variant="outline" class="font-mono text-xs text-primary">
                                        {{ clip.format || "MP4 1080p" }}
                                    </Badge>
                                </div>
                                <Badge
                                    v-if="clip.status === 'completed'"
                                    variant="warning"
                                    class="text-xs font-sans flex items-center gap-1"
                                    title="File ini akan dihapus otomatis 1 jam setelah dibuat"
                                >
                                    <Clock class="w-3 h-3 shrink-0" />
                                    <span>1 Jam</span>
                                </Badge>
                                <span
                                    v-else-if="clip.status === 'expired'"
                                    class="text-xs text-muted-foreground font-sans italic"
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
                            class="text-xs p-2 rounded-md bg-destructive/10 border border-destructive/30 text-destructive font-mono wrap-break-word"
                        >
                            {{ clip.error_message }}
                        </div>

                        <!-- Card Footer Controls -->
                        <div
                            class="flex items-center justify-between pt-2 border-t border-border"
                        >
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="promptDeleteClip(clip.id)"
                                class="text-muted-foreground hover:text-destructive text-xs flex items-center gap-1.5 font-mono"
                                title="Hapus Klip"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                                <span>Hapus</span>
                            </Button>

                            <Button
                                v-if="
                                    clip.status === 'completed' &&
                                    clip.download_url
                                "
                                as-child
                                size="sm"
                            >
                                <a
                                    :href="clip.download_url"
                                    download
                                    class="text-xs font-semibold flex items-center gap-1.5"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>{{
                                        clip.format === "MP3 Audio"
                                            ? "Unduh MP3"
                                            : "Unduh Video"
                                    }}</span>
                                </a>
                            </Button>
                        </div>
                    </Card>
                </div>
            </section>
        </PageContainer>

        <!-- GUEST AUTHENTICATION REQUIRED MODAL OVERLAY -->
        <Dialog :open="showGuestAuthModal" @update:open="(val) => (showGuestAuthModal = val)">
            <DialogContent class="sm:max-w-md bg-card border-border text-center space-y-4">
                <div
                    class="w-16 h-16 rounded-full bg-primary/15 border border-primary/30 text-primary flex items-center justify-center mx-auto"
                >
                    <Lock class="w-8 h-8 text-primary" />
                </div>

                <DialogHeader class="space-y-2 text-center sm:text-center">
                    <DialogTitle
                        class="text-xl font-bold text-foreground uppercase font-tactical tracking-tight"
                    >
                        Pendaftaran Akun Gratis Diperlukan
                    </DialogTitle>
                    <DialogDescription
                        class="text-xs text-muted-foreground leading-relaxed font-sans"
                    >
                        Untuk mulai memproses & mengunduh potongan video
                        YouTube ini, silakan masuk atau daftar akun gratis.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="p-3 rounded-xl bg-muted/50 border border-border text-left text-xs font-mono space-y-1 text-foreground"
                >
                    <div
                        class="flex items-center gap-2 text-success font-bold"
                    >
                        <Check class="w-4 h-4" />
                        <span>Dapatkan 3 Klip Gratis per Minggu</span>
                    </div>
                    <div
                        class="flex items-center gap-2 text-success font-bold"
                    >
                        <Check class="w-4 h-4" />
                        <span>Pemrosesan Lossless MP4 / MP3 / GIF</span>
                    </div>
                </div>

                <div class="space-y-2 pt-2">
                    <Button as-child class="w-full font-bold text-xs uppercase tracking-wider font-tactical">
                        <a href="/register" class="flex items-center justify-center gap-2">
                            <UserPlus class="w-4 h-4" />
                            <span>Daftar Akun Gratis Sekarang</span>
                        </a>
                    </Button>

                    <Button as-child variant="outline" class="w-full font-bold text-xs">
                        <a href="/login">
                            Sudah punya akun? Masuk (Sign In)
                        </a>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- UNIFIED CLAIM & VERIFICATION MODAL (CLIPPER & OFFICER REQUEST) -->
        <ClipperClaimModal
            :show="showRequestClipperModal"
            initial-plan="general"
            @close="showRequestClipperModal = false"
            @submitted="fetchClipperRequestStatus"
        />

        <!-- DELEGATION CLIPPER MODAL (FOR OFFICERS/ADMINS - 2 SLOTS) -->
        <Dialog :open="showDelegationModal" @update:open="(val) => (showDelegationModal = val)">
            <DialogContent class="sm:max-w-xl bg-card border-border space-y-4 font-sans text-left">
                <DialogHeader class="space-y-1 text-left">
                    <div class="flex items-center gap-2">
                        <Badge variant="warning" class="font-mono text-xs font-bold uppercase">
                            Hak Otonomi Officer
                        </Badge>
                        <Badge variant="secondary" class="font-mono text-xs font-bold uppercase">
                            2 Slot Penunjukan
                        </Badge>
                    </div>
                    <DialogTitle
                        class="text-xl font-bold text-foreground uppercase tracking-tight font-tactical"
                    >
                        Penunjukan Slot Police Duty Clipper (2 Slot)
                    </DialogTitle>
                    <DialogDescription
                        class="text-xs sm:text-sm text-muted-foreground leading-relaxed"
                    >
                        Sebagai Officer, Anda berhak menunjuk hingga
                        <strong>2 akun Clipper (Dokumentasi/Editor)</strong>
                        untuk memotong video Police Duty (10 Klip/Hari, Max
                        5 Menit). Akun yang ditunjuk
                        <strong>HANYA mendapatkan hak Clipper</strong> dan
                        <strong>TIDAK mendapatkan hak penunjukan ulang</strong>.
                    </DialogDescription>
                </DialogHeader>

                <!-- Alert Banners inside Modal -->
                <Alert
                    v-if="delegationErrMsg"
                    variant="destructive"
                    class="p-3 text-xs flex items-center justify-between gap-2 font-mono"
                >
                    <AlertDescription>{{ delegationErrMsg }}</AlertDescription>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Tutup error delegasi"
                        @click="delegationErrMsg = ''"
                    >
                        <X class="w-3.5 h-3.5" />
                    </Button>
                </Alert>

                <Alert
                    v-if="delegationSuccessMsg"
                    class="p-3 border-success/40 bg-success/10 text-success text-xs flex items-center justify-between gap-2 font-mono"
                >
                    <AlertDescription>{{ delegationSuccessMsg }}</AlertDescription>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Tutup pesan sukses delegasi"
                        @click="delegationSuccessMsg = ''"
                    >
                        <X class="w-3.5 h-3.5" />
                    </Button>
                </Alert>

                <!-- Slots Overview Box -->
                <div
                    class="p-3.5 rounded-xl bg-muted/50 border border-border space-y-3"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span
                            class="text-muted-foreground font-semibold uppercase tracking-wider"
                            >Status Kuota Slot Penunjukan:</span
                        >
                        <span
                            class="font-mono font-bold text-sm"
                            :class="
                                delegationData.used_slots >= 2
                                    ? 'text-warning'
                                    : 'text-success'
                            "
                        >
                            {{ delegationData.used_slots }}/2 Slot Terpakai
                        </span>
                    </div>

                    <Progress
                        :model-value="(delegationData.used_slots / 2) * 100"
                        class="h-2"
                    />
                </div>

                <!-- List of Current Delegations -->
                <div class="space-y-3">
                    <h4
                        class="text-xs font-semibold text-muted-foreground uppercase tracking-wider"
                    >
                        Akun Yang Telah Anda Tunjuk ({{
                            delegationData.delegations
                                ? delegationData.delegations.length
                                : 0
                        }}/2)
                    </h4>

                    <div
                        v-if="isLoadingDelegation"
                        class="p-4 text-center text-xs text-muted-foreground font-mono"
                    >
                        Memuat data slot...
                    </div>

                    <div
                        v-else-if="
                            !delegationData.delegations ||
                            delegationData.delegations.length === 0
                        "
                        class="p-4 rounded-xl bg-muted/30 border border-dashed border-border text-center space-y-1"
                    >
                        <p class="text-xs text-muted-foreground">
                            Belum ada akun member yang Anda tunjuk.
                        </p>
                        <p class="text-xs text-muted-foreground font-mono">
                            Gunakan formulir di bawah untuk menunjuk akun
                            via alamat email.
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="del in delegationData.delegations"
                            :key="del.id"
                            class="p-3 rounded-xl bg-muted/40 border border-border flex items-center justify-between gap-3 text-xs"
                        >
                            <div class="space-y-0.5 truncate">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-bold text-foreground truncate"
                                        >{{
                                            del.assigned_user?.name ||
                                            "Member"
                                        }}</span
                                    >
                                    <Badge variant="success" class="font-mono text-xs uppercase">
                                        {{
                                            del.tier === "ultimate"
                                                ? "Ultimate Tier"
                                                : "Creator Tier (10 Klip/hr)"
                                        }}
                                    </Badge>
                                </div>
                                <p
                                    class="text-xs text-muted-foreground font-mono truncate"
                                >
                                    {{ del.assigned_user?.email }}
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="
                                    promptRevokeDelegation(
                                        del.id,
                                        del.assigned_user?.name ||
                                            del.assigned_user?.email,
                                    )
                                "
                                class="text-xs flex items-center gap-1 shrink-0 font-mono cursor-pointer"
                                title="Cabut penunjukan & kembalikan pengguna ke member biasa"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                                <span>Cabut</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Form to Assign New Member (If slots available) -->
                <div
                    v-if="delegationData.used_slots < 2"
                    class="p-4 rounded-xl bg-muted/40 border border-primary/30 space-y-3"
                >
                    <h4
                        class="text-xs font-semibold text-primary uppercase tracking-wider flex items-center gap-1.5"
                    >
                        <UserPlus class="w-4 h-4 text-primary shrink-0" />
                        <span
                            >Tunjuk Akun Member Baru (Sisa Slot:
                            {{ 2 - delegationData.used_slots }})</span
                        >
                    </h4>

                    <div class="space-y-2">
                        <div>
                            <label
                                class="block text-xs text-muted-foreground mb-1"
                                >Email Akun Member:</label
                            >
                            <Input
                                v-model="assignEmailInput"
                                type="email"
                                placeholder="contoh: user@gmail.com"
                                class="text-xs font-mono"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs text-muted-foreground mb-1"
                                >Pilih Kategori Hak Clipper:</label
                            >
                            <select
                                v-model="assignTierInput"
                                class="w-full bg-background border border-input rounded-lg px-3 py-2 text-xs text-foreground focus:border-primary focus:ring-0 outline-hidden transition font-sans"
                            >
                                <option value="creator">
                                    Police Duty Clipper (10 Klip/hari,
                                    Durasi Maks 5 Menit)
                                </option>
                            </select>
                        </div>
                    </div>

                    <Button
                        @click="assignDelegation"
                        :disabled="isAssigningDelegation"
                        class="w-full font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 cursor-pointer font-tactical"
                    >
                        <Loader2
                            v-if="isAssigningDelegation"
                            class="animate-spin h-4 w-4"
                        />
                        <span v-else>Tunjuk Sebagai Clipper</span>
                    </Button>
                </div>

                <div
                    v-else
                    class="p-3 rounded-xl bg-warning/15 border border-warning/40 text-warning text-xs text-center font-mono"
                >
                    Kuota penunjukan Anda telah penuh (2/2 Slot Terpakai).
                    Cabut salah satu penunjukan di atas jika ingin mengganti
                    akun target.
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showDelegationModal = false"
                    >
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MOBILE CLIPPER MENU BOTTOM SHEET DRAWER -->
        <Teleport to="body">
            <div
                v-if="isMobileClipperMenuOpen"
                class="fixed inset-0 z-50 flex flex-col justify-end lg:hidden font-sans"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-background/80 backdrop-blur-xs transition-opacity"
                    @click="closeMobileClipperMenu"
                ></div>

                <!-- Sheet Panel -->
                <div
                    class="relative z-10 w-full max-w-lg mx-auto bg-card border-t border-border rounded-t-3xl shadow-2xl overflow-hidden max-h-[88vh] flex flex-col animate-in slide-in-from-bottom duration-300"
                >
                    <!-- Drag handle & Header -->
                    <div
                        class="pt-3 pb-1 flex justify-center cursor-pointer"
                        @click="closeMobileClipperMenu"
                    >
                        <div
                            class="w-12 h-1.5 rounded-full bg-muted hover:bg-muted-foreground/40 transition-colors"
                        ></div>
                    </div>

                    <div
                        class="px-5 py-3 border-b border-border flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/30 flex items-center justify-center text-primary shrink-0"
                            >
                                <SlidersHorizontal class="w-4 h-4" />
                            </div>
                            <div>
                                <h3
                                    class="font-bold text-foreground text-sm uppercase tracking-wide font-mono"
                                >
                                    Menu Opsi & Ekspor ClipStudio
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Pengaturan format, kuota & ekspor klip video
                                </p>
                            </div>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Tutup menu opsi"
                            @click="closeMobileClipperMenu"
                        >
                            <X class="w-5 h-5" />
                        </Button>
                    </div>

                    <!-- Drawer Scrollable Content -->
                    <div class="p-4 sm:p-5 overflow-y-auto space-y-4 pb-8">
                        <!-- 1. Format Selection Options -->
                        <section class="space-y-2">
                            <div
                                class="flex items-center justify-between px-0.5"
                            >
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                                    >Format & Kualitas Output</span
                                >
                                <span
                                    class="text-xs text-primary font-mono font-bold"
                                    >{{
                                        formatSeconds(calculatedSeconds)
                                    }}</span
                                >
                            </div>

                            <div class="grid grid-cols-1 gap-2">
                                <button
                                    v-for="fmt in formatOptions"
                                    :key="'mobile-' + fmt.value"
                                    type="button"
                                    @click="selectFormat(fmt.value, $event)"
                                    class="relative flex items-center justify-between p-3 rounded-xl border transition-all duration-200 cursor-pointer text-left"
                                    :class="[
                                        form.format === fmt.value
                                            ? 'border-primary/70 bg-primary/10 shadow-md ring-1 ring-primary/40'
                                            : 'border-border bg-muted/40 hover:bg-muted',
                                        fmt.value === 'MP4 1080p' &&
                                        !canUse1080p &&
                                        !isGuest
                                            ? 'opacity-60 bg-muted/30 border-dashed border-warning/40'
                                            : '',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                            :class="
                                                form.format === fmt.value
                                                    ? 'border-primary'
                                                    : 'border-muted-foreground'
                                            "
                                        >
                                            <div
                                                class="w-2 h-2 rounded-full"
                                                :class="
                                                    form.format === fmt.value
                                                        ? 'bg-primary'
                                                        : 'bg-transparent'
                                                "
                                            ></div>
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="text-xs font-bold text-foreground"
                                                    >{{ fmt.name }}</span
                                                >
                                                <span
                                                    class="text-xs font-medium px-1.5 py-0.5 rounded-full"
                                                    :class="
                                                        fmt.value ===
                                                            'MP4 1080p' &&
                                                        !canUse1080p &&
                                                        !isGuest
                                                            ? 'bg-warning/15 text-warning'
                                                            : fmt.tagClass
                                                    "
                                                >
                                                    {{
                                                        fmt.value ===
                                                            "MP4 1080p" &&
                                                        !canUse1080p &&
                                                        !isGuest
                                                            ? "PRO Only"
                                                            : fmt.tag
                                                    }}
                                                </span>
                                            </div>
                                            <p
                                                class="text-xs text-muted-foreground mt-0.5"
                                            >
                                                {{ fmt.useCase }}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge variant="outline" class="font-mono text-xs shrink-0">
                                        {{ fmt.desc }}
                                    </Badge>
                                </button>
                            </div>
                        </section>

                        <!-- 2. Export Summary & Quota -->
                        <section
                            class="bg-muted/40 rounded-xl border border-border p-3 space-y-2"
                        >
                            <div
                                class="flex items-center justify-between text-xs pb-1.5 border-b border-border"
                            >
                                <span
                                    class="text-muted-foreground font-semibold uppercase"
                                    >Durasi Klip Terpilih</span
                                >
                                <span class="text-foreground font-mono font-bold"
                                    >{{ formatSeconds(calculatedSeconds) }} ({{
                                        calculatedSeconds
                                    }}s)</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-muted-foreground"
                                    >Sisa Kuota {{ quotaPeriodLabel }}:</span
                                >
                                <span class="text-primary font-mono font-bold">
                                    {{
                                        isUnlimitedQuota
                                            ? "Unlimited"
                                            : `${userDailyRemaining} dari ${dailyClipsLimit} Klip`
                                    }}
                                </span>
                            </div>
                        </section>

                        <!-- 3. Primary Submit CTA Button inside Mobile Drawer -->
                        <Button
                            @click="
                                submitTrim();
                                closeMobileClipperMenu();
                            "
                            :disabled="
                                isSubmitting ||
                                (!isGuest && !isDurationValid) ||
                                submitCooldownSec > 0
                            "
                            size="lg"
                            class="w-full py-3.5 px-4 rounded-xl font-semibold text-xs uppercase tracking-wider flex items-center justify-center gap-2 cursor-pointer font-sans"
                        >
                            <Scissors class="w-4 h-4 shrink-0" />
                            <span>{{
                                urlCheckResult?.is_live
                                    ? "POTONG KLIP LIVE (HLS DVR)"
                                    : `POTONG & SIMPAN KLIP (${form.format.toUpperCase()})`
                            }}</span>
                        </Button>

                        <!-- 4. Quick Account / Request Buttons -->
                        <div class="pt-1">
                            <Button
                                v-if="
                                    !isGuest &&
                                    !isUnlimitedQuota &&
                                    clipperRequestStatus !== 'pending'
                                "
                                variant="outline"
                                @click="
                                    closeMobileClipperMenu();
                                    showRequestClipperModal = true;
                                "
                                class="w-full py-2.5 px-3.5 rounded-xl text-xs font-mono flex items-center justify-between cursor-pointer"
                            >
                                <span>Akses Clipper Beta (Gratis)</span>
                                <span class="text-primary font-bold"
                                    >Daftar →</span
                                >
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ALERT DIALOG: DELETE CLIP -->
        <AlertDialog :open="clipToDeleteId !== null" @update:open="(val) => { if (!val) clipToDeleteId = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Klip Video?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus klip video ini dari server?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="clipToDeleteId = null">Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmDeleteClip">
                        Hapus Klip
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ALERT DIALOG: REVOKE DELEGATION -->
        <AlertDialog :open="delegationToRevoke !== null" @update:open="(val) => { if (!val) delegationToRevoke = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Cabut Penunjukan Clipper?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin mencabut penunjukan Clipper untuk {{ delegationToRevoke?.targetName }}? Slot penunjukan Anda akan kembali bertambah.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="delegationToRevoke = null">Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmRevokeDelegation">
                        Cabut Penunjukan
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </TacticalLayout>
</template>

<script setup>
import DualRangeTrimmer from "@/Components/DualRangeTrimmer.vue";
import CustomVideoPlayer from "@/Components/CustomVideoPlayer.vue";
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { PageContainer } from "@/Components/tactical";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Progress } from "@/Components/ui/progress";
import {
    AlertCircle,
    AlertTriangle,
    ArrowRight,
    BarChart3,
    Check,
    Clock,
    Download,
    Loader2,
    Lock,
    LogIn,
    RefreshCw,
    Scissors,
    ShieldCheck,
    SlidersHorizontal,
    Sparkles,
    Trash2,
    UserPlus,
    Video,
    X,
    Zap,
} from "@lucide/vue";
import { Head, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "vue-sonner";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

const props = defineProps({
    initialUrl: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isGuest = computed(() => !user.value);
const isClipperOrAdmin = computed(() => user.value?.can_trim_video || false);
const isUnlimitedQuota = computed(() => {
    if (!user.value) return false;
    return (
        user.value.clipper_tier === "ultimate" ||
        (user.value.daily_clips_limit || 0) >= 9999
    );
});
const maxClipDurationSec = computed(
    () =>
        user.value?.max_clip_duration_sec ||
        (isClipperOrAdmin.value ? 600 : 60),
);
const quotaPeriodLabel = computed(
    () =>
        user.value?.quota_period_label ||
        (user.value?.clipper_tier === "free" ? "Minggu Ini" : "Hari Ini"),
);
const canUse1080p = computed(
    () => user.value?.can_use_1080p ?? user.value?.clipper_tier !== "free",
);
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
const clipToDeleteId = ref(null);
const delegationToRevoke = ref(null);

// Mobile Menu Drawer State
const isMobileClipperMenuOpen = ref(false);

const openMobileClipperMenu = () => {
    isMobileClipperMenuOpen.value = true;
    if (typeof document !== "undefined") {
        document.body.style.overflow = "hidden";
    }
};

const closeMobileClipperMenu = () => {
    isMobileClipperMenuOpen.value = false;
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
};

const handleKeyDown = (e) => {
    if (e.key === "Escape" && isMobileClipperMenuOpen.value) {
        closeMobileClipperMenu();
    }
};

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
        name: "1080p Full HD",
        desc: "Full HD 1080p",
        badge: "FHD",
        badgeClass: "bg-primary/15 text-primary border border-primary/30",
        tag: "Rekomendasi Utama",
        tagClass: "bg-primary/15 text-primary border border-primary/30",
        useCase: "Kualitas presisi tinggi untuk editing YouTube & TikTok",
    },
    {
        value: "MP4 720p",
        name: "720p HD",
        desc: "HD 720p",
        badge: "HD",
        badgeClass: "bg-muted text-muted-foreground border border-border",
        tag: "Cepat & Ringan",
        tagClass: "bg-muted text-muted-foreground border border-border",
        useCase: "Pemrosesan cepat, cocok untuk tinjauan & preview",
    },
    {
        value: "MP4 480p",
        name: "480p Standard",
        desc: "SD 480p",
        badge: "SD",
        badgeClass: "bg-muted text-muted-foreground border border-border",
        tag: "Hemat Kuota",
        tagClass: "bg-muted text-muted-foreground border border-border",
        useCase: "Resolusi standar untuk koneksi hemat",
    },
    {
        value: "MP3 Audio",
        name: "MP3 Audio",
        desc: "320 kbps",
        badge: "AUDIO",
        badgeClass: "bg-muted text-muted-foreground border border-border",
        tag: "Audio Saja",
        tagClass: "bg-muted text-muted-foreground border border-border",
        useCase: "Ekstrak musik, percakapan & rekaman suara",
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
            if (response.data.duration && response.data.duration > 0) {
                videoDurationSec.value = response.data.duration;
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

const activeVideoId = computed(() => {
    const url = (form.value.youtube_url || "").trim();
    if (!url) return "";
    const regExp =
        /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|live|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i;
    const match = url.match(regExp);
    return match && match[1] ? match[1] : "";
});

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

watch(activeVideoId, (newId, oldId) => {
    playerCurrentSeconds.value = 0;
    if (newId !== oldId) {
        form.value.title = "";
        urlCheckResult.value = null;
    }
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

const estimatedFileSize = computed(() => {
    const dur = calculatedSeconds.value || 0;
    if (dur <= 0) return "0 MB";

    const isLiveStream = urlCheckResult.value?.is_live || false;
    let rateMBps = 0.3;
    const fmt = form.value.format || "";
    if (fmt.includes("1080p")) rateMBps = 0.58;
    else if (fmt.includes("720p")) rateMBps = 0.3;
    else if (fmt.includes("480p")) rateMBps = 0.13;
    else if (fmt.includes("MP3")) rateMBps = 0.04;

    if (isLiveStream) {
        const minMB = (dur * rateMBps * 0.9).toFixed(1);
        const maxMB = (dur * rateMBps * 2.2).toFixed(1);
        return `~${minMB} - ${maxMB} MB (Live Stream)`;
    }

    const totalMB = (dur * rateMBps).toFixed(1);
    return totalMB < 1
        ? `${Math.max(50, Math.round(totalMB * 1024))} KB (VOD)`
        : `~${totalMB} MB (VOD)`;
});

async function fetchClipperRequestStatus() {
    if (!user.value || isClipperOrAdmin.value) return;
    try {
        const res = await axios.get("/api/v1/clipper-requests/status");
        if (res.data && res.data.status) {
            clipperRequestStatus.value = res.data.status;
        }
    } catch (err) {}
}

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

function promptRevokeDelegation(id, targetName) {
    delegationToRevoke.value = { id, targetName };
}

async function confirmRevokeDelegation() {
    if (!delegationToRevoke.value) return;
    const { id } = delegationToRevoke.value;
    delegationToRevoke.value = null;
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
    form.value.title = "";
    form.value.start_time = "00:00:10";
    form.value.end_time = "00:00:40";
    if (sample.durationSec) {
        videoDurationSec.value = sample.durationSec;
    }
    checkYoutubeUrl(sample.youtubeUrl);
}

function loadVideoFromInput() {
    const url = (form.value.youtube_url || "").trim();
    if (!url) {
        formError.value = "Silakan masukkan URL YouTube terlebih dahulu!";
        return;
    }
    formError.value = "";
    checkYoutubeUrl(url);
}

function handleGuestAuth(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (isGuest.value) {
        window.location.href = "/login";
    }
}

function selectFormat(fmtValue, e) {
    if (isGuest.value) {
        handleGuestAuth(e);
        return;
    }
    if (fmtValue === "MP4 1080p" && !canUse1080p.value) {
        formError.value =
            "Format MP4 1080p Full HD khusus untuk Paket Clipper & VIP Supporter. Pengguna Free Member dapat memilih 720p, 480p, atau MP3 Audio.";
        form.value.format = "MP4 720p";
        return;
    }
    formError.value = "";
    form.value.format = fmtValue;
}

async function submitTrim() {
    if (isGuest.value) {
        handleGuestAuth();
        return;
    }

    if (submitCooldownSec.value > 0) return;

    if (!isUnlimitedQuota.value && userDailyRemaining.value <= 0) {
        formError.value = `Kuota pemotongan video harian Anda (${dailyClipsCount.value}/${dailyClipsLimit.value} klip) telah habis. Silakan naikkan paket Clipper Anda!`;
        return;
    }

    if (!isDurationValid.value) {
        const maxTxt = `${formatSeconds(maxClipDurationSec.value)} (${maxClipDurationSec.value}s)`;
        formError.value = `Durasi pemotongan tidak valid atau melebihi batas ${maxTxt}.`;
        return;
    }

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
    closeMobileClipperMenu();

    try {
        const payload = {
            ...form.value,
            title: form.value.title || urlCheckResult.value?.title || "",
        };
        const response = await axios.post("/api/v1/clips/trim", payload);
        successMessage.value =
            response.data.message ||
            "Proses pemotongan video telah dimasukkan ke dalam antrean server!";
        startSubmitCooldown(5);

        if (user.value && typeof user.value.daily_clips_count === "number") {
            user.value.daily_clips_count++;
        }

        await fetchClips();

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

function promptDeleteClip(id) {
    clipToDeleteId.value = id;
}

async function confirmDeleteClip() {
    const id = clipToDeleteId.value;
    if (!id) return;
    clipToDeleteId.value = null;
    try {
        await axios.delete(`/api/v1/clips/${id}`);
        clipsList.value = clipsList.value.filter((c) => c.id !== id);
        toast.success("Klip video berhasil dihapus dari server.");
    } catch (err) {
        toast.error("Gagal menghapus klip video.");
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
    window.addEventListener("keydown", handleKeyDown);
});

onUnmounted(() => {
    stopPolling();
    stopYTTimePolling();
    if (cooldownTimer) {
        clearInterval(cooldownTimer);
        cooldownTimer = null;
    }
    if (typeof window !== "undefined") {
        window.removeEventListener("keydown", handleKeyDown);
    }
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
});
</script>
