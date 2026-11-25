<script setup>
import { BellRing, PlusSquare, Share } from "@lucide/vue";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";

const open = defineModel("open", { type: Boolean, default: false });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-center justify-between gap-2 pr-6">
                    <DialogTitle>Instal di iPhone / iPad</DialogTitle>
                    <Badge variant="warning" class="font-mono text-xs">
                        iOS 16.4+
                    </Badge>
                </div>
                <DialogDescription>
                    Safari iOS mewajibkan situs dipasang ke Layar Utama agar notifikasi live bisa masuk seperti aplikasi.
                </DialogDescription>
            </DialogHeader>

            <ol class="space-y-2.5 py-2">
                <li class="flex items-start gap-3 rounded-lg border border-border bg-muted/40 p-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                        1
                    </span>
                    <div class="min-w-0 space-y-0.5 text-xs">
                        <p class="font-semibold text-foreground">
                            Ketuk ikon Share di bar bawah Safari
                        </p>
                        <p class="flex items-center gap-1.5 text-muted-foreground">
                            <Share class="size-3.5 shrink-0 text-primary" />
                            <span>Cari ikon kotak dengan panah ke atas.</span>
                        </p>
                    </div>
                </li>

                <li class="flex items-start gap-3 rounded-lg border border-border bg-muted/40 p-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                        2
                    </span>
                    <div class="min-w-0 space-y-0.5 text-xs">
                        <p class="font-semibold text-foreground">
                            Pilih "Tambahkan ke Layar Utama"
                        </p>
                        <p class="flex items-center gap-1.5 text-muted-foreground">
                            <PlusSquare class="size-3.5 shrink-0 text-primary" />
                            <span>Atau "Add to Home Screen", lalu ketuk Tambah.</span>
                        </p>
                    </div>
                </li>

                <li class="flex items-start gap-3 rounded-lg border border-border bg-muted/40 p-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                        3
                    </span>
                    <div class="min-w-0 space-y-0.5 text-xs">
                        <p class="font-semibold text-foreground">
                            Buka dari ikon aplikasi & aktifkan notifikasi
                        </p>
                        <p class="flex items-center gap-1.5 text-muted-foreground">
                            <BellRing class="size-3.5 shrink-0 text-primary" />
                            <span>Setelah dibuka dari Layar Utama, aktifkan toggle Web Push.</span>
                        </p>
                    </div>
                </li>
            </ol>

            <DialogFooter>
                <Button type="button" size="sm" @click="open = false">
                    Mengerti
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
