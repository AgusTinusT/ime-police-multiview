<script setup>
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { Check, Clock, Flame, Radio, TriangleAlert } from '@lucide/vue';

const props = defineProps({
    selectedDepartment: {
        type: String,
        required: true
    },
    visibleStreamsCount: {
        type: Number,
        default: 0
    },
    remainingSeconds: {
        type: Number,
        default: 0
    },
    iconRadio: {
        type: String,
        default: ''
    },
    iconClock: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['extendTimer', 'disbandChannel']);

const formatRemainingTime = (totalSeconds) => {
    if (totalSeconds <= 0) return '00:00';
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const departmentTitle = computed(() => {
    return props.selectedDepartment ? props.selectedDepartment.replace('_', ' ') : 'KANAL TAKTIS';
});
</script>

<template>
    <div class="mb-4 space-y-2.5 font-sans">
        <!-- Main Channel HUD Toolbar -->
        <Alert class="block bg-warning/10 border-warning/40 rounded-xl p-3 backdrop-blur-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-warning/15 border border-warning/40 flex items-center justify-center text-warning shrink-0">
                        <Radio class="size-5" />
                    </div>
                    <div>
                        <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                            <AlertTitle class="text-sm font-bold text-warning font-mono tracking-wide uppercase">
                                KANAL RADIO TAKTIS: {{ departmentTitle }}
                            </AlertTitle>
                            <Badge variant="warning" class="gap-1.5 font-mono font-bold text-xs rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-live motion-safe:animate-pulse"></span>
                                <span>{{ visibleStreamsCount }} UNIT TERHUBUNG</span>
                            </Badge>
                        </div>
                        <AlertDescription class="text-xs text-muted-foreground mt-0.5 font-mono">
                            Kanal radio taktis aktif untuk pemantauan POV bersama.
                        </AlertDescription>
                    </div>
                </div>

                <!-- Timer Countdown Badge -->
                <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-1.5 bg-background/80 border border-warning/30 px-3 py-1.5 rounded-lg font-mono">
                        <Clock class="size-3.5 text-warning" />
                        <span class="text-xs text-muted-foreground uppercase">Sisa Waktu:</span>
                        <span
                            :class="cn('text-xs font-bold', remainingSeconds <= 60 ? 'text-destructive motion-safe:animate-pulse' : 'text-warning')"
                        >
                            {{ formatRemainingTime(remainingSeconds) }}
                        </span>
                    </div>
                </div>
            </div>
        </Alert>

        <!-- Expiration Alert Banner (Triggers in last 60 seconds) -->
        <Alert
            v-if="remainingSeconds > 0 && remainingSeconds <= 60"
            variant="destructive"
            class="block bg-destructive/15 border-2 border-destructive rounded-xl p-3.5 backdrop-blur-sm motion-safe:animate-pulse"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-destructive/20 border border-destructive flex items-center justify-center text-destructive shrink-0">
                        <TriangleAlert class="size-4" />
                    </div>
                    <div>
                        <AlertTitle class="text-xs font-bold text-destructive font-mono tracking-wide uppercase">
                            KONFIRMASI SITUASI: WAKTU {{ departmentTitle }} TERSISA {{ formatRemainingTime(remainingSeconds) }}!
                        </AlertTitle>
                        <AlertDescription class="text-xs text-foreground/80 mt-0.5">
                            Apakah kanal radio ini masih aktif digunakan, atau telah selesai?
                        </AlertDescription>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="font-bold text-xs font-mono gap-1.5"
                        @click="emit('extendTimer', selectedDepartment, 20)"
                    >
                        <Flame class="size-3.5" />
                        <span>Ya, Lanjutkan (+20 Menit)</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="font-bold text-xs font-mono gap-1.5"
                        @click="emit('disbandChannel', selectedDepartment)"
                    >
                        <Check class="size-3.5" />
                        <span>Situasi Selesai (Bubarkan)</span>
                    </Button>
                </div>
            </div>
        </Alert>
    </div>
</template>
