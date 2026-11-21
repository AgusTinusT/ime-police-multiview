<template>
    <TacticalLayout>
        <Head title="Panduan Hak Akses Police Duty Clipper - Clip Studio IME Police" />

        <PageContainer class="pt-8 pb-24 space-y-10 font-sans">
            <!-- BREADCRUMB NAVIGATION -->
            <nav class="flex items-center gap-2 text-xs font-mono text-muted-foreground">
                <Link href="/clip-studio" class="hover:text-foreground transition">Clip Studio</Link>
                <span>/</span>
                <Link href="/clip-studio" class="hover:text-foreground transition">Pricing & Paket</Link>
                <span>/</span>
                <span class="text-primary font-bold">Panduan Police Duty Clipper</span>
            </nav>

            <!-- HERO HEADER SECTION -->
            <section class="text-center max-w-3xl mx-auto space-y-4">
                <Badge variant="outline" class="inline-flex items-center gap-2 px-3 py-1 font-mono text-xs">
                    <span class="w-2 h-2 rounded-full bg-primary motion-safe:animate-pulse"></span>
                    <span>Panduan Resmi Platform & Cara Mendapatkan Akses</span>
                </Badge>

                <h1 class="text-3xl sm:text-4xl font-bold text-foreground tracking-tight uppercase font-tactical">
                    Panduan Profesi <span class="text-primary">Police Duty Clipper</span>
                </h1>

                <p class="text-sm sm:text-base text-muted-foreground font-sans leading-relaxed">
                    Pelajari cara mendapatkan <strong class="text-foreground">Akses Gratis (Rp 0)</strong> sebagai Clipper khusus aksi kepolisian IME Police dengan terhubung langsung ke Officer aktif.
                </p>

                <!-- Quick Action Bar -->
                <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                    <Button as-child class="font-bold text-xs uppercase tracking-wider font-tactical">
                        <Link href="/officers" class="inline-flex items-center gap-2">
                            <Users class="w-4 h-4" />
                            <span>Cari Officer Aktif (Direktori)</span>
                        </Link>
                    </Button>

                    <Button as-child variant="outline" class="font-bold text-xs font-sans">
                        <Link href="/multiview" class="inline-flex items-center gap-2">
                            <Video class="w-4 h-4 text-primary" />
                            <span>Tonton Live Patrol Multiview</span>
                        </Link>
                    </Button>
                </div>
            </section>

            <!-- SECTION 1: APA ITU POLICE DUTY CLIPPER -->
            <Card class="p-6 rounded-xl bg-card border-border space-y-4">
                <div class="flex items-center gap-3 border-b border-border pb-3">
                    <div class="p-2 rounded-sm bg-primary/10 text-primary border border-primary/20">
                        <ShieldCheck class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-foreground uppercase font-tactical">
                            Apa itu Police Duty Clipper?
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Peran penting komunitas dalam mendokumentasikan operasional kepolisian IME Roleplay.
                        </p>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                    <strong class="text-foreground">Police Duty Clipper</strong> adalah penonton atau kreator konten yang mendedikasikan waktu dan pemotong klipnya untuk merekam serta membagikan momen-momen aksi dari petugas Kepolisian IME (seperti LSPD, SASP, LSCSD, Ranger, dll).
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    <div class="p-3.5 rounded-lg bg-muted/50 border border-border space-y-1">
                        <span class="text-xs font-mono font-bold text-primary uppercase block">1. Aksi Patroli & Pursuit</span>
                        <p class="text-xs text-muted-foreground">Momen pengejar kecepatan tinggi (high-speed chase), pit maneuver, dan penangkapan tersangka.</p>
                    </div>
                    <div class="p-3.5 rounded-lg bg-muted/50 border border-border space-y-1">
                        <span class="text-xs font-mono font-bold text-primary uppercase block">2. Tactical & Operasi Bersama</span>
                        <p class="text-xs text-muted-foreground">Penggerebekan (raid), penyergapan bank, perimeter barricade, dan koordinasi radio TAC.</p>
                    </div>
                    <div class="p-3.5 rounded-lg bg-muted/50 border border-border space-y-1">
                        <span class="text-xs font-mono font-bold text-primary uppercase block">3. Interaksi Roleplay Unik</span>
                        <p class="text-xs text-muted-foreground">Negosiasi hangat, dialog interogasi presisi, serta momen kocak saat bertugas di jalanan.</p>
                    </div>
                </div>
            </Card>

            <!-- SECTION 2: PANDUAN 4 LANGKAH MENDAPATKAN AKSES -->
            <section class="space-y-4">
                <div class="border-b border-border pb-2">
                    <h2 class="text-lg font-bold text-foreground uppercase font-tactical">
                        Cara Mendapatkan Hak Akses (4 Langkah Mudah)
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Ikuti alur berikut untuk menghubungkan akun Anda dengan Officer aktif tanpa antrean admin.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- STEP 1 -->
                    <Card class="p-5 rounded-xl bg-card border-border space-y-3 relative">
                        <div class="flex items-center justify-between">
                            <Badge variant="secondary" class="font-mono text-xs font-bold text-primary">
                                LANGKAH 01
                            </Badge>
                            <span class="text-xs font-mono text-muted-foreground">Temukan Streamer</span>
                        </div>
                        <h3 class="text-base font-bold text-foreground">Temukan Officer yang Sedang Patroli</h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Buka menu <Link href="/officers" class="text-primary underline hover:text-primary/80">Direktori Officer</Link> atau halaman <Link href="/multiview" class="text-primary underline hover:text-primary/80">Multiview</Link> di website ini. Cari Officer yang sedang aktif siaran langsung (<em>Live Duty</em>).
                        </p>
                        <div class="pt-1">
                            <Link href="/officers" class="text-xs text-primary font-mono hover:underline flex items-center gap-1">
                                <span>Buka Direktori Officer</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </Card>

                    <!-- STEP 2 -->
                    <Card class="p-5 rounded-xl bg-card border-border space-y-3 relative">
                        <div class="flex items-center justify-between">
                            <Badge variant="secondary" class="font-mono text-xs font-bold text-primary">
                                LANGKAH 02
                            </Badge>
                            <span class="text-xs font-mono text-muted-foreground">Komunikasi</span>
                        </div>
                        <h3 class="text-base font-bold text-foreground">Hubungi Officer & Tunjukkan Channel Klip</h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Sapa Officer di chat livestream atau Discord komunitas. Tanyakan apakah mereka memiliki slot Clipper kosong dan tunjukkan minat Anda untuk membantu klip aksi patrolinya.
                        </p>
                        <div class="p-2.5 rounded-sm bg-muted/50 border border-border text-xs text-muted-foreground font-mono flex items-center gap-1.5">
                            <Info class="w-3.5 h-3.5 text-primary shrink-0" />
                            <span>Tip: Tunjukkan link channel YouTube/TikTok Anda atau hasil pemotongan klip sebelumnya!</span>
                        </div>
                    </Card>

                    <!-- STEP 3 -->
                    <Card class="p-5 rounded-xl bg-card border-border space-y-3 relative">
                        <div class="flex items-center justify-between">
                            <Badge variant="secondary" class="font-mono text-xs font-bold text-primary">
                                LANGKAH 03
                            </Badge>
                            <span class="text-xs font-mono text-muted-foreground">Penunjukan Direct</span>
                        </div>
                        <h3 class="text-base font-bold text-foreground">Officer Memasukkan Akun Anda (2 Slot)</h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Setiap Officer memiliki <strong class="text-foreground">2 Slot Otonomi Mandiri</strong>. Officer cukup membuka menu pengelolaan Clipper di akunnya dan memasukkan Username atau Email akun Anda.
                        </p>
                        <div class="p-2.5 rounded-sm bg-muted/50 border border-border text-xs text-muted-foreground font-mono flex items-center gap-1.5">
                            <Zap class="w-3.5 h-3.5 text-success shrink-0" />
                            <span>Tanpa perlu menunggu persetujuan Admin! Hak akses diberikan secara instan oleh Officer.</span>
                        </div>
                    </Card>

                    <!-- STEP 4 -->
                    <Card class="p-5 rounded-xl bg-card border-border space-y-3 relative">
                        <div class="flex items-center justify-between">
                            <Badge variant="success" class="font-mono text-xs font-bold">
                                LANGKAH 04
                            </Badge>
                            <span class="text-xs font-mono text-muted-foreground">Akses Aktif</span>
                        </div>
                        <h3 class="text-base font-bold text-foreground">Akses Clip Studio Aktif & Siap Digunakan</h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Buka halaman <Link href="/clip-studio" class="text-primary underline hover:text-primary/80">Clip Studio (/clip-studio)</Link>. Status akun Anda otomatis menjadi <strong class="text-foreground">Police Duty Clipper</strong> dengan limit 10 klip/hari dan durasi hingga 5 menit!
                        </p>
                        <div class="pt-1">
                            <Link href="/clip-studio" class="text-xs text-success font-mono hover:underline flex items-center gap-1 font-bold">
                                <span>Buka Clip Studio Sekarang</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- SECTION 3: ETIKA & KETENTUAN CLIPPER -->
            <Card class="p-5 rounded-xl bg-card border-border space-y-3">
                <div class="flex items-center gap-2 text-foreground font-mono font-bold text-xs">
                    <AlertTriangle class="w-4 h-4 text-warning shrink-0" />
                    <span>Etika & Ketentuan Profesi Police Clipper</span>
                </div>
                <ul class="space-y-2 text-xs text-muted-foreground font-mono list-disc list-inside leading-relaxed">
                    <li><strong class="text-foreground">Hak Pencabutan Officer:</strong> Officer berhak mengganti atau mencabut slot Clipper miliknya kapan saja jika Clipper tidak aktif atau melanggar aturan.</li>
                    <li><strong class="text-foreground">Kesepakatan Mandiri:</strong> Seluruh kesepakatan pembagian tugas, format judul, atau credit disepakati langsung antara Officer & Clipper tanpa keterlibatan biaya platform.</li>
                    <li><strong class="text-foreground">Menjaga Aturan Server:</strong> Dilarang menggunakan hasil klip untuk tindakan <em>Meta Gaming</em>, memprovokasi keributan OOC, atau membagikan rahasia taktis IC secara ilegal.</li>
                </ul>
            </Card>

            <!-- FOOTER CTA BAR -->
            <Card class="p-6 rounded-xl bg-card border-border text-center space-y-3">
                <h3 class="text-xl font-bold text-foreground uppercase font-tactical">
                    Sudah Menemukan Officer Anda?
                </h3>
                <p class="text-xs sm:text-sm text-muted-foreground max-w-xl mx-auto">
                    Mulai jelajahi daftar Officer aktif sekarang atau periksa detail paket langganan lainnya di halaman pricing.
                </p>
                <div class="pt-1 flex flex-wrap items-center justify-center gap-3">
                    <Button as-child class="font-bold text-xs uppercase tracking-wider font-tactical">
                        <Link href="/officers">
                            Direktori Officer
                        </Link>
                    </Button>
                    <Button as-child variant="secondary" class="font-bold text-xs uppercase tracking-wider font-tactical">
                        <Link href="/clip-studio">
                            Halaman Pricing & Tarif
                        </Link>
                    </Button>
                </div>
            </Card>
        </PageContainer>
    </TacticalLayout>
</template>

<script setup>
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import { PageContainer } from '@/Components/tactical';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    AlertTriangle,
    ArrowRight,
    Info,
    ShieldCheck,
    Users,
    Video,
    Zap,
} from '@lucide/vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
