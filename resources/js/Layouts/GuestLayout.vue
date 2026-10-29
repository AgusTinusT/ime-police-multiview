<script setup>
import logoSaspColor from '@/Components/Icons/SASP_256.jpg';
import { Card } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background p-4 font-sans text-foreground selection:bg-primary selection:text-primary-foreground"
    >
        <div class="w-full max-w-md space-y-6">
            <div class="text-center space-y-2">
                <Link href="/" class="inline-block">
                    <img :src="logoSaspColor" class="w-14 h-14 rounded-full mx-auto" alt="SASP Logo" />
                </Link>
                <div>
                    <h1 class="text-base font-bold text-foreground font-mono tracking-wider uppercase">
                        SASP POLICE MULTIVIEW
                    </h1>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        IME Roleplay Community
                    </p>
                </div>
            </div>

            <Card class="p-6">
                <slot />
            </Card>

            <div class="text-center">
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/" class="text-xs text-muted-foreground hover:text-foreground transition">
                        <ArrowLeft class="size-3.5" />
                        <span>Kembali ke Dashboard</span>
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
