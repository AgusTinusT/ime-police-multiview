<script setup>
import { Button } from "@/Components/ui/button";
import { Card } from "@/Components/ui/card";
import { Radio } from "@lucide/vue";

defineProps({
    expiringTacChannel: { type: Object, default: null },
    tacticalToast: { type: Object, default: null },
    formatRemainingTime: { type: Function, required: true },
});

const emit = defineEmits([
    "extend-timer",
    "disband-channel",
    "select-department",
]);
</script>

<template>
    <!-- FLOATING TAC CHANNEL EXPIRATION WARNING ALERT -->
    <Card
        v-if="expiringTacChannel"
        class="fixed bottom-20 md:bottom-16 right-4 z-60 bg-background/95 border-warning/80 rounded-lg px-4 py-3 flex-row items-center space-x-3 text-xs max-w-sm animate-in slide-in-from-bottom duration-300 pointer-events-auto backdrop-blur-md"
    >
        <div
            class="w-8 h-8 rounded-full bg-warning/20 border border-warning/40 flex items-center justify-center shrink-0"
        >
            <span
                class="w-2.5 h-2.5 rounded-full bg-warning motion-safe:animate-ping"
            ></span>
        </div>
        <div class="flex-1 min-w-0">
            <div class="font-bold text-warning font-mono text-xs truncate">
                {{ expiringTacChannel.name }} ({{
                    expiringTacChannel.video_ids?.length || 0
                }}
                Units) tersisa
                {{ formatRemainingTime(expiringTacChannel.remaining_seconds) }}
            </div>
            <div class="text-xs text-muted-foreground truncate">
                Apakah situasi masih berlangsung?
            </div>
        </div>
        <div class="flex items-center space-x-1.5 shrink-0">
            <Button
                type="button"
                size="sm"
                class="h-7 px-2.5 bg-warning text-warning-foreground hover:bg-warning/90 font-bold text-xs font-mono"
                @click="emit('extend-timer', expiringTacChannel.code, 20)"
            >
                +20m
            </Button>
            <Button
                type="button"
                variant="destructive"
                size="sm"
                class="h-7 px-2.5 text-xs font-mono"
                @click="emit('disband-channel', expiringTacChannel.code)"
            >
                Bubarkan
            </Button>
            <Button
                type="button"
                variant="secondary"
                size="sm"
                class="h-7 px-2.5 text-xs font-mono"
                @click="emit('select-department', expiringTacChannel.code)"
            >
                Buka
            </Button>
        </div>
    </Card>

    <!-- FLOATING TACTICAL ACTION TOAST -->
    <Card
        v-if="tacticalToast"
        class="fixed bottom-20 md:bottom-16 right-4 z-60 bg-card border-warning/60 rounded-md px-4 py-2.5 flex-row items-center space-x-2.5 text-xs font-mono text-warning animate-in slide-in-from-bottom duration-200 pointer-events-auto"
    >
        <Radio class="size-4 text-warning shrink-0" />
        <span>{{ tacticalToast.message }}</span>
    </Card>
</template>
