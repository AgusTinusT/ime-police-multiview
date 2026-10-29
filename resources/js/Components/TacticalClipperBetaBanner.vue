<script setup>
import { Link } from "@inertiajs/vue3";
import { Alert } from "@/Components/ui/alert";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { ArrowRight, Check } from "@lucide/vue";
</script>

<template>
    <Alert
        class="w-full rounded-2xl border-border bg-card p-8 sm:p-12 mb-14 sm:mb-20 lg:mb-24 font-sans text-center relative block"
    >
        <div class="max-w-3xl mx-auto space-y-6 flex flex-col items-center">
            <!-- Flat Badge -->
            <Badge
                variant="outline"
                class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border-border bg-background text-foreground text-xs font-mono font-medium"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                <span>AKSES BETA TESTER &bull; GRATIS</span>
            </Badge>

            <!-- Clean Matte Title -->
            <h2
                class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight text-foreground font-tactical uppercase leading-tight"
            >
                Clip video kualitas tinggi dengan mudah dan cepat.
            </h2>

            <!-- Clean Description -->
            <p
                class="text-xs sm:text-sm md:text-base text-muted-foreground max-w-lg leading-relaxed font-sans"
            >
                Coba Clip Studio tanpa biaya. <br />Dapatkan jatah harian setiap
                hari untuk anda. GRATIS!
            </p>

            <!-- Flat Action Button -->
            <div class="pt-2">
                <Button
                    as-child
                    size="lg"
                    class="px-7 py-3 rounded-xl text-xs sm:text-sm font-bold uppercase tracking-wider font-tactical"
                >
                    <Link href="/clipper">
                        <span>Buka Studio Sekarang</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </Button>
            </div>

            <!-- Crisp Flat Micro Feature Highlights Line -->
            <div
                class="w-full pt-6 mt-2 border-t border-border text-xs font-mono text-muted-foreground"
            >
                <div
                    class="flex flex-wrap items-center justify-center gap-y-2 gap-x-3 sm:gap-x-5"
                >
                    <span
                        class="inline-flex items-center gap-1.5 text-foreground"
                    >
                        <Check class="w-3.5 h-3.5 text-primary" />
                        10 Klip / Hari
                    </span>
                    <span class="text-muted-foreground/50 hidden sm:inline">&bull;</span>
                    <span
                        class="inline-flex items-center gap-1.5 text-foreground"
                    >
                        <Check class="w-3.5 h-3.5 text-primary" />
                        Full HD 1080p
                    </span>
                    <span class="text-muted-foreground/50 hidden sm:inline">&bull;</span>
                    <span
                        class="inline-flex items-center gap-1.5 text-foreground"
                    >
                        <Check class="w-3.5 h-3.5 text-primary" />
                        Maks. 5 Menit
                    </span>
                    <span class="text-muted-foreground/50 hidden sm:inline">&bull;</span>
                    <span
                        class="inline-flex items-center gap-1.5 text-foreground"
                    >
                        <Check class="w-3.5 h-3.5 text-primary" />
                        Youtube live
                    </span>
                </div>
            </div>
        </div>
    </Alert>
</template>
