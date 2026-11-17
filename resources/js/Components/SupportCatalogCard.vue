<script setup>
import { Bell } from "@lucide/vue";
import { computed } from "vue";

import { DeptBadge, OfficerAvatar } from "@/Components/tactical";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";

const GOAL = 1000;

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
});

const count = computed(() => Math.max(0, Number(props.officer.subscriber_count) || 0));
const progressValue = computed(() => Math.min(100, (count.value / GOAL) * 100));
const remaining = computed(() => Math.max(0, GOAL - count.value));
// The last stretch is where a single subscribe matters most, so call it out.
const isNearGoal = computed(() => count.value >= GOAL * 0.8);
</script>

<template>
    <Card
        class="group w-44 shrink-0 snap-start gap-0 py-0 transition-colors hover:border-primary/40 hover:bg-accent/40 sm:w-52"
    >
        <CardContent class="relative flex h-full flex-col items-center p-4 text-center">
            <Badge v-if="isNearGoal" variant="warning" class="absolute left-2 top-2">Hampir 1K</Badge>

            <OfficerAvatar
                :src="officer.avatar_url"
                :name="officer.officer_name"
                :live="Boolean(officer.is_live_now)"
                size="lg"
                class="mt-2 ring-2 ring-border transition-shadow group-hover:ring-primary/60"
            />

            <h3 class="mt-3 w-full truncate text-sm font-semibold text-foreground" :title="officer.officer_name">
                {{ officer.officer_name }}
            </h3>
            <DeptBadge v-if="officer.department" :dept="officer.department" class="mt-1" />

            <div class="mt-4 w-full space-y-1.5 text-left">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-mono text-sm font-bold text-foreground">{{ count.toLocaleString("id-ID") }}</span>
                    <span class="text-xs text-muted-foreground">/ 1.000</span>
                </div>
                <Progress :model-value="progressValue" class="h-1.5" />
                <p class="text-xs" :class="isNearGoal ? 'text-warning' : 'text-muted-foreground'">
                    {{ remaining.toLocaleString("id-ID") }} lagi menuju 1K
                </p>
            </div>

            <Button
                v-if="officer.youtube_url"
                as-child
                size="sm"
                variant="secondary"
                class="mt-4 w-full group-hover:bg-live group-hover:text-live-foreground"
            >
                <a :href="officer.youtube_url" target="_blank" rel="noopener">
                    <Bell /> Subscribe
                </a>
            </Button>
        </CardContent>
    </Card>
</template>
