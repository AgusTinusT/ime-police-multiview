<template>
    <TacticalLayout>
        <Head title="Paket Layanan & Pricing Clipper - ClipStudio IME Police" />

        <PageContainer class="pt-8 pb-24 space-y-12 font-sans">
            <!-- HERO HEADER SECTION -->
            <section class="text-center max-w-3xl mx-auto space-y-4">
                <Badge
                    variant="outline"
                    class="inline-flex items-center gap-2 px-3 py-1 font-mono text-xs"
                >
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    <span>Paket & Akses Clipper IME Police</span>
                </Badge>

                <h1
                    class="text-3xl sm:text-4xl font-bold text-foreground tracking-tight uppercase font-tactical"
                >
                    Paket Layanan Clipper
                </h1>

                <p
                    class="text-sm sm:text-base text-muted-foreground font-sans leading-relaxed"
                >
                    Platform ini mendorong penonton menjadi
                    <strong class="text-foreground"
                        >Police Duty Clipper (GRATIS via Slot Officer)</strong
                    >. Tersedia pula promo diskon untuk Clipper kelompok IME dan
                    paket VIP Supporter.
                </p>

                <div
                    class="pt-2 flex flex-wrap items-center justify-center gap-3"
                >
                    <Button as-child class="font-bold text-xs uppercase tracking-wider font-tactical">
                        <Link href="/clipper">
                            Buka Studio Trimmer
                        </Link>
                    </Button>

                    <Button as-child variant="outline" class="font-bold text-xs font-sans text-primary">
                        <Link href="/clipper/police-guide">
                            Panduan Police Clipper →
                        </Link>
                    </Button>

                    <Button as-child variant="outline" class="font-bold text-xs font-sans">
                        <a href="#matrix-section">
                            Matriks Perbandingan
                        </a>
                    </Button>
                </div>
            </section>

            <!-- 3 PERSONA / PRICING CARDS GRID FOR CLIPPERS -->
            <section class="space-y-6">
                <div
                    class="border-b border-border pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                >
                    <div>
                        <h2
                            class="text-lg sm:text-xl font-bold text-foreground uppercase font-tactical"
                        >
                            Kategori Profesi Clipper
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Pilih akses yang sesuai dengan fokus konten Anda di
                            IME Roleplay.
                        </p>
                    </div>
                    <Badge variant="outline" class="font-mono text-xs">
                        Format: 1080p • 720p • 480p • MP3
                    </Badge>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- CARD 1: POLICE DUTY CLIPPER (RECOMMENDED / DORONGAN UTAMA — 1 TacticalFrame accent) -->
                    <TacticalFrame
                        variant="panel"
                        accent="primary"
                        class="relative"
                        inner-class="p-6 space-y-4 flex flex-col justify-between"
                    >
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Badge variant="secondary" class="font-mono text-xs font-bold uppercase text-primary">
                                    Rekomendasi Utama
                                </Badge>
                                <span
                                    class="text-xs text-success font-mono font-bold"
                                    >GRATIS Slot Officer</span
                                >
                            </div>

                            <div>
                                <h3 class="text-xl font-extrabold text-foreground">
                                    Police Duty Clipper
                                </h3>
                                <p
                                    class="text-xs text-muted-foreground mt-1 leading-relaxed"
                                >
                                    Clipper yang rutin membuat konten kepolisian
                                    di IME Roleplay. Baik itu individu tertentu
                                    atau secara keseluruhan.
                                </p>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-muted/50 border border-border space-y-1"
                            >
                                <span
                                    class="text-xs font-mono text-muted-foreground uppercase block"
                                    >Biaya Akses:</span
                                >
                                <div class="flex items-baseline gap-1">
                                    <span
                                        class="text-2xl font-bold text-foreground font-tactical"
                                        >GRATIS</span
                                    >
                                    <span
                                        class="text-xs text-muted-foreground font-mono"
                                        >/ Slot Officer</span
                                    >
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Slot ini didapat dari officer yang
                                    menunjukmu menjadi clipper.
                                </p>
                            </div>

                            <ul
                                class="space-y-2 text-xs text-foreground font-mono pt-1"
                            >
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-primary shrink-0" />
                                    <span
                                        >Fokus Konten:
                                        <strong>IME Police</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-primary shrink-0" />
                                    <span
                                        >Kuota:
                                        <strong>10 Klip / Hari</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-primary shrink-0" />
                                    <span
                                        >Durasi Maks:
                                        <strong>5 Menit (300s)</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-primary shrink-0" />
                                    <span
                                        >Format:
                                        <strong
                                            >1080p, 720p, 480p, MP3</strong
                                        ></span
                                    >
                                </li>
                            </ul>
                        </div>

                        <div class="pt-3 border-t border-border space-y-2">
                            <Button as-child class="w-full font-bold text-xs uppercase tracking-wider font-tactical">
                                <Link href="/clipper/police-guide">
                                    Panduan Cara Akses →
                                </Link>
                            </Button>
                        </div>
                    </TacticalFrame>

                    <!-- CARD 2: CLIPPER KOMUNITAS IME (DISKON BETA RP 5K) -->
                    <Card
                        class="p-6 rounded-xl bg-card border-border space-y-4 flex flex-col justify-between relative"
                    >
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Badge variant="outline" class="font-mono text-xs uppercase">
                                    General Clipper
                                </Badge>
                                <span
                                    class="text-xs text-success font-mono font-bold"
                                    >Promo Beta 50%</span
                                >
                            </div>

                            <div>
                                <h3 class="text-xl font-extrabold text-foreground">
                                    Clipper Komunitas IME
                                </h3>
                                <p
                                    class="text-xs text-muted-foreground mt-1 leading-relaxed"
                                >
                                    Untuk pengguna yang fokus membahas badside
                                    dan goodside tertentu di server IME.
                                </p>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-muted/50 border border-border space-y-1"
                            >
                                <span
                                    class="text-xs font-mono text-muted-foreground uppercase block"
                                    >Harga Promo Beta:</span
                                >
                                <div class="flex items-baseline gap-1.5">
                                    <span
                                        class="text-2xl font-bold text-success font-tactical"
                                        >Rp 5.000</span
                                    >
                                    <span
                                        class="text-xs text-muted-foreground line-through font-mono"
                                        >Rp 10.000</span
                                    >
                                    <span
                                        class="text-xs text-muted-foreground font-mono"
                                        >/ bln</span
                                    >
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Dukungan operasional server versi Early
                                    Access Beta.
                                </p>
                            </div>

                            <ul
                                class="space-y-2 text-xs text-foreground font-mono pt-1"
                            >
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Fokus Konten:
                                        <strong
                                            >IME Badside or Goodside</strong
                                        ></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Kuota:
                                        <strong>10 Klip / Hari</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Durasi Maks:
                                        <strong>5 Menit (300s)</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Format:
                                        <strong
                                            >1080p, 720p, 480p, MP3</strong
                                        ></span
                                    >
                                </li>
                            </ul>
                        </div>

                        <div class="pt-3 border-t border-border">
                            <Button
                                variant="secondary"
                                @click="openClaimModal('general')"
                                class="w-full font-bold text-xs uppercase tracking-wider font-tactical"
                            >
                                Langganan Beta Rp 5k
                            </Button>
                        </div>
                    </Card>

                    <!-- CARD 3: ULTIMATE VIP SUPPORTER TIER (RP 10K PROMO BETA) -->
                    <Card
                        class="p-6 rounded-xl bg-card border-border space-y-4 flex flex-col justify-between relative"
                    >
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Badge variant="outline" class="font-mono text-xs uppercase">
                                    VIP Supporter
                                </Badge>
                                <span
                                    class="text-xs text-success font-mono font-bold"
                                    >Beta Supporter</span
                                >
                            </div>

                            <div>
                                <h3 class="text-xl font-extrabold text-foreground">
                                    VIP Supporter Tier
                                </h3>
                                <p
                                    class="text-xs text-muted-foreground mt-1 leading-relaxed"
                                >
                                    Paket tanpa batas untuk streamer utama,
                                    kreator profesional, dan VIP Supporter
                                    platform.
                                </p>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-muted/50 border border-border space-y-1"
                            >
                                <span
                                    class="text-xs font-mono text-muted-foreground uppercase block"
                                    >Harga VIP Supporter (Beta):</span
                                >
                                <div class="flex items-baseline gap-1.5">
                                    <span
                                        class="text-2xl font-bold text-success font-tactical"
                                        >Rp 10.000</span
                                    >
                                    <span
                                        class="text-xs text-muted-foreground line-through font-mono"
                                        >Rp 20.000</span
                                    >
                                    <span
                                        class="text-xs text-muted-foreground font-mono"
                                        >/ bln</span
                                    >
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Akses penuh unlimited klip & prioritas
                                    antrean server.
                                </p>
                            </div>

                            <ul
                                class="space-y-2 text-xs text-foreground font-mono pt-1"
                            >
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Kuota:
                                        <strong>UNLIMITED KLIP</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Durasi Maks:
                                        <strong>10 Menit (600s)</strong></span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span
                                        >Potong Siaran Langsung & Prioritas
                                        Server</span
                                    >
                                </li>
                            </ul>
                        </div>

                        <div class="pt-3 border-t border-border">
                            <Button
                                variant="secondary"
                                @click="openClaimModal('vip')"
                                class="w-full font-bold text-xs uppercase tracking-wider font-tactical"
                            >
                                Langganan Beta VIP Rp 10k
                            </Button>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- DEDICATED OFFICER AUTONOMY & ONBOARDING CALL-TO-ACTION SECTION -->
            <Card
                class="p-6 sm:p-8 rounded-2xl bg-card border-2 border-border space-y-6 relative overflow-hidden shadow-xl"
            >
                <div
                    class="flex flex-col md:flex-row md:items-start justify-between gap-6"
                >
                    <div class="space-y-3 max-w-2xl">
                        <Badge
                            variant="secondary"
                            class="inline-flex items-center gap-2 px-3 py-1 text-primary text-xs font-mono font-bold uppercase"
                        >
                            <ShieldCheck class="w-4 h-4" />
                            <span>Khusus Petugas Kepolisian & Streamer Faction</span>
                        </Badge>

                        <h2
                            class="text-2xl sm:text-3xl font-bold text-foreground uppercase font-tactical tracking-tight leading-tight"
                        >
                            Apakah Anda Petugas Officer? <br />
                            <span class="text-primary"
                                >Akses VIP Supporter & 2 Slot Clipper
                                Gratis</span
                            >
                        </h2>

                        <p
                            class="text-xs sm:text-sm text-muted-foreground leading-relaxed"
                        >
                            Setiap Officer & Petugas Kepolisian di server IME
                            berhak mendapatkan
                            <strong class="text-foreground"
                                >Akses VIP Supporter Tier</strong
                            >
                            dan memegang
                            <strong class="text-foreground"
                                >2 Slot Police Duty Clipper</strong
                            >
                            secara mandiri. Ketentuan berlaku untuk Officer
                            Aktif.
                        </p>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs font-mono"
                        >
                            <div
                                class="p-3 rounded-lg bg-muted/50 border border-border space-y-1"
                            >
                                <span class="font-bold text-primary block"
                                    >VIP Supporter Tier</span
                                >
                                <span class="text-muted-foreground text-xs"
                                    >Mendapatkan akses penuh fitur VIP Supporter
                                    secara otomatis.</span
                                >
                            </div>
                            <div
                                class="p-3 rounded-lg bg-muted/50 border border-border space-y-1"
                            >
                                <span class="font-bold text-primary block"
                                    >Otonomi Mandiri</span
                                >
                                <span class="text-muted-foreground text-xs"
                                    >Tambah & cabut 2 slot Police Duty Clipper
                                    Anda kapan saja.</span
                                >
                            </div>
                            <div
                                class="p-3 rounded-lg bg-muted/50 border border-border space-y-1"
                            >
                                <span class="font-bold text-primary block"
                                    >Bebas Kesepakatan</span
                                >
                                <span class="text-muted-foreground text-xs"
                                    >Atur penugasan & kesepakatan secara
                                    internal.</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Officer CTA Action Box -->
                    <div
                        class="p-5 rounded-xl bg-muted/50 border border-border space-y-3 shrink-0 w-full md:w-72 text-center flex flex-col justify-between"
                    >
                        <div class="space-y-1">
                            <span
                                class="text-xs font-mono text-muted-foreground uppercase block font-semibold"
                                >Gabung Sebagai Officer</span
                            >
                            <span
                                class="text-lg font-bold text-foreground font-tactical uppercase"
                                >Mulai Otonomi Anda</span
                            >
                            <p class="text-xs text-muted-foreground">
                                Daftarkan akun atau login untuk mengaktifkan 2
                                slot Clipper.
                            </p>
                        </div>

                        <div class="space-y-2 pt-2">
                            <Button as-child class="w-full font-bold text-xs uppercase tracking-wider font-tactical">
                                <Link href="/register">
                                    Buat Akun Officer →
                                </Link>
                            </Button>

                            <Button
                                variant="outline"
                                @click="showOfficerClaimModal = true"
                                class="w-full text-primary font-bold text-xs font-sans"
                            >
                                Klaim Profil Officer →
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- BETA SERVER & OPERATIONAL NOTICE BOX -->
            <section class="space-y-4">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border pb-3"
                >
                    <div>
                        <Badge
                            variant="secondary"
                            class="inline-flex items-center gap-2 px-2.5 py-0.5 text-primary font-mono text-xs font-bold uppercase mb-1"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-primary motion-safe:animate-pulse"
                            ></span>
                            <span>System Transparency & VPS Health</span>
                        </Badge>
                        <h2
                            class="text-xl sm:text-2xl font-bold text-foreground uppercase font-tactical tracking-tight"
                        >
                            Informasi Operasional & Resource VPS
                        </h2>
                    </div>
                    <div
                        class="text-xs font-mono text-muted-foreground bg-card border border-border px-3 py-1.5 rounded-lg shrink-0"
                    >
                        Status:
                        <span class="text-success font-bold"
                            >Early Access Beta v2.0</span
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 font-sans">
                    <!-- LEFT SIDE: INFRASTRUKTUR & PEMELIHARAAN (5 Cols) -->
                    <Card
                        class="lg:col-span-5 p-6 rounded-2xl bg-card border-border relative overflow-hidden flex flex-col justify-between space-y-4 shadow-lg"
                    >
                        <div class="space-y-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center"
                            >
                                <Server class="w-5 h-5" />
                            </div>
                            <h3
                                class="text-lg font-bold text-foreground uppercase font-tactical"
                            >
                                Pengelolaan Server
                            </h3>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                Seluruh dana langganan digunakan
                                <strong class="text-foreground"
                                    >100% untuk biaya perawatan server &
                                    pemrosesan video</strong
                                >. Pemrosesan klip dikelola via antrean agar
                                server tetap cepat dan stabil.
                            </p>
                        </div>

                        <div
                            class="space-y-2 pt-2 border-t border-border text-xs font-mono"
                        >
                            <div
                                class="flex items-center justify-between p-2.5 rounded-lg bg-muted/50 border border-border"
                            >
                                <span class="text-muted-foreground"
                                    >Penyimpanan File Klip:</span
                                >
                                <span class="text-success font-bold"
                                    >Dihapus Otomatis (1 Jam)</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between p-2.5 rounded-lg bg-muted/50 border border-border"
                            >
                                <span class="text-muted-foreground"
                                    >Biaya Server:</span
                                >
                                <span class="text-primary font-bold"
                                    >100% Dana Komunitas</span
                                >
                            </div>
                        </div>
                    </Card>

                    <!-- RIGHT SIDE: TRANSPARANSI POTONG SIARAN LANGSUNG (7 Cols) -->
                    <Card
                        class="lg:col-span-7 p-6 rounded-2xl bg-card border-border relative overflow-hidden space-y-4 shadow-lg"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div
                                    class="inline-flex items-center gap-1.5 text-info font-mono font-bold text-xs"
                                >
                                    <Zap class="w-4 h-4 shrink-0 text-info" />
                                    <span
                                        >Transparansi Potong Siaran
                                        Langsung</span
                                    >
                                </div>
                                <h3
                                    class="text-lg font-bold text-foreground uppercase font-tactical"
                                >
                                    Mengapa Potong Live Stream Dibatasi Khusus
                                    VIP & Officer?
                                </h3>
                            </div>
                            <Badge variant="info" class="font-mono text-xs font-bold uppercase shrink-0">
                                Membutuhkan Server Besar
                            </Badge>
                        </div>

                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Memotong momen dari
                            <strong class="text-foreground"
                                >siaran langsung YouTube yang sedang berjalan
                                (Live Stream)</strong
                            >
                            membutuhkan daya dan ruang penyimpanan server yang
                            jauh lebih besar. Karena siaran masih tayang, server
                            harus mengolah file mentah berukuran sangat besar
                            secara langsung saat itu juga.
                        </p>

                        <!-- COMPARISON CARDS INSIDE -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-xs font-mono"
                        >
                            <div
                                class="p-3 rounded-xl bg-muted/50 border border-border space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-foreground"
                                        >Video Selesai Tayang (VOD)</span
                                    >
                                    <span
                                        class="text-xs text-success font-bold"
                                        >Proses Ringan</span
                                    >
                                </div>
                                <p
                                    class="text-xs text-muted-foreground leading-normal font-sans"
                                >
                                    Video yang sudah selesai tayang ukurannya
                                    sudah dipres oleh YouTube. Ringan & cepat
                                    untuk semua paket.
                                </p>
                            </div>

                            <div
                                class="p-3 rounded-xl bg-muted/50 border border-border space-y-1.5 hover:border-info/30 transition duration-200"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-info"
                                        >Siaran Langsung (Live)</span
                                    >
                                    <span
                                        class="text-xs text-info font-bold"
                                        >Beban Server Tinggi</span
                                    >
                                </div>
                                <p
                                    class="text-xs text-muted-foreground leading-normal font-sans"
                                >
                                    File mentah live stream memakan ruang server
                                    besar. Khusus paket
                                    <strong class="text-foreground"
                                        >VIP & Officer</strong
                                    >
                                    agar server tetap cepat.
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- COMPARISON MATRIX TABLE SECTION -->
            <section id="matrix-section" class="space-y-4 pt-2">
                <div class="border-b border-border pb-2">
                    <h2
                        class="text-lg font-bold text-foreground uppercase font-tactical"
                    >
                        Matriks Perbandingan Fitur & Tarif Beta
                    </h2>
                </div>

                <Card
                    class="overflow-x-auto rounded-xl border-border bg-card p-0"
                >
                    <Table class="w-full text-left font-sans text-xs text-foreground">
                        <TableHeader
                            class="bg-muted/50 text-muted-foreground text-xs font-mono border-b border-border uppercase"
                        >
                            <TableRow>
                                <TableHead class="p-3.5">Fitur / Layanan</TableHead>
                                <TableHead class="p-3.5">Free Member</TableHead>
                                <TableHead class="p-3.5">Clipper IME (Beta)</TableHead>
                                <TableHead class="p-3.5 text-primary">
                                    Police Duty Clipper
                                </TableHead>
                                <TableHead class="p-3.5">VIP Supporter (Beta)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody
                            class="divide-y divide-border font-mono text-xs"
                        >
                            <TableRow>
                                <TableCell
                                    class="p-3.5 font-sans font-bold text-foreground"
                                >
                                    Biaya Paket / Bulan
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">Rp 0</TableCell>
                                <TableCell class="p-3.5 text-success font-bold">
                                    Rp 5.000
                                    <span
                                        class="text-xs text-muted-foreground font-normal"
                                        >(Promo Beta)</span
                                    >
                                </TableCell>
                                <TableCell
                                    class="p-3.5 text-primary font-bold uppercase"
                                >
                                    GRATIS
                                    <span
                                        class="text-xs text-muted-foreground font-normal"
                                        >(Slot Officer)</span
                                    >
                                </TableCell>
                                <TableCell class="p-3.5 text-success font-bold">
                                    Rp 10.000
                                    <span
                                        class="text-xs text-muted-foreground font-normal"
                                        >(Promo Beta)</span
                                    >
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell
                                    class="p-3.5 font-sans font-bold text-foreground"
                                >
                                    Fokus Konten Utama
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">Umum</TableCell>
                                <TableCell class="p-3.5 text-foreground">
                                    Komunitas & Faction
                                </TableCell>
                                <TableCell class="p-3.5 text-primary font-bold">
                                    Police Duty IME
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground font-bold">
                                    Bebas / Semua
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell
                                    class="p-3.5 font-sans font-bold text-foreground"
                                >
                                    Batas Kuota Klip
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">
                                    3 Klip / Minggu
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground">
                                    10 Klip / Hari
                                </TableCell>
                                <TableCell class="p-3.5 text-primary font-bold">
                                    10 Klip / Hari
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground font-bold">
                                    UNLIMITED
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell
                                    class="p-3.5 font-sans font-bold text-foreground"
                                >
                                    Durasi Maksimal Klip
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">
                                    1 Menit (60s)
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground">
                                    5 Menit (300s)
                                </TableCell>
                                <TableCell class="p-3.5 text-primary font-bold">
                                    5 Menit (300s)
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground font-bold">
                                    10 Menit (600s)
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell
                                    class="p-3.5 font-sans font-bold text-foreground"
                                >
                                    Format Output
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">
                                    720p, 480p, MP3
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground">
                                    1080p, 720p, 480p, MP3
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground">
                                    1080p, 720p, 480p, MP3
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground">
                                    1080p, 720p, 480p, MP3
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell
                                    class="p-3.5 font-sans font-bold text-foreground"
                                >
                                    Potong Siaran Langsung (Live Stream)
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">Terbatas</TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">
                                    Terbatas (VOD)
                                </TableCell>
                                <TableCell class="p-3.5 text-muted-foreground">
                                    Terbatas (VOD)
                                </TableCell>
                                <TableCell class="p-3.5 text-foreground font-bold">
                                    <span class="flex items-center gap-1">
                                        <Check class="w-3.5 h-3.5 text-success shrink-0" />
                                        <span>Akses Penuh (Prioritas)</span>
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </section>

            <!-- FOOTER CTA BAR -->
            <Card
                class="p-6 rounded-xl bg-card border-border text-center space-y-3"
            >
                <h3
                    class="text-xl font-bold text-foreground uppercase font-tactical"
                >
                    Mulai Potong Klip Video Sekarang
                </h3>
                <p class="text-xs sm:text-sm text-muted-foreground max-w-xl mx-auto">
                    Gunakan pemutar interaktif ClipStudio untuk memotong momen
                    streamer favorit Anda secara instan.
                </p>
                <div class="pt-1 flex justify-center">
                    <Button as-child class="font-bold text-xs uppercase tracking-wider font-tactical">
                        <Link href="/clipper">
                            <span>Buka Studio Trimmer (/clipper) →</span>
                        </Link>
                    </Button>
                </div>
            </Card>
        </PageContainer>

        <!-- Claim & Verification Modal -->
        <ClipperClaimModal
            :show="claimModalVisible"
            :initial-plan="selectedPlanType"
            @close="claimModalVisible = false"
            @submitted="claimModalVisible = false"
        />

        <!-- Officer Bio Claim Modal (PRD D4) -->
        <OfficerClaimModal
            :show="showOfficerClaimModal"
            @close="showOfficerClaimModal = false"
        />
    </TacticalLayout>
</template>

<script setup>
import { ref } from "vue";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import ClipperClaimModal from "@/Components/ClipperClaimModal.vue";
import OfficerClaimModal from "@/Components/OfficerClaimModal.vue";
import { PageContainer, TacticalFrame } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Check, Server, ShieldCheck, Zap } from "@lucide/vue";
import { Head, Link } from "@inertiajs/vue3";

const claimModalVisible = ref(false);
const showOfficerClaimModal = ref(false);
const selectedPlanType = ref("general");

const openClaimModal = (planType = "general") => {
    selectedPlanType.value = planType;
    claimModalVisible.value = true;
};
</script>
