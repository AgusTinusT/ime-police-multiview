<script setup>
import { computed } from "vue";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";
import { Button } from "@/Components/ui/button";
import { OfficerAvatar, DeptBadge } from "@/Components/tactical";

const props = defineProps({
    officer: {
        type: Object,
        required: true,
    },
});

const progressValue = computed(() => {
    const count = Number(props.officer.subscriber_count) || 0;
    return Math.min(100, Math.max(0, count / 10));
});

const subText = computed(() => {
    const count = Number(props.officer.subscriber_count) || 0;
    return `${count.toLocaleString("id-ID")}/1.000 subscriber`;
});
</script>

<template>
    <Card class="w-44 sm:w-52 shrink-0 snap-start flex flex-col justify-between">
        <CardContent class="flex flex-col items-center p-4 text-center">
            <OfficerAvatar
                :src="officer.avatar_url"
                :name="officer.officer_name"
                :live="Boolean(officer.is_live_now)"
                size="lg"
                class="mb-3"
            />

            <div class="w-full min-w-0">
                <h3
                    class="truncate text-sm font-semibold text-foreground"
                    :title="officer.officer_name"
                >
                    {{ officer.officer_name }}
                </h3>
                <div class="mt-1 flex justify-center">
                    <DeptBadge :dept="officer.department" />
                </div>
            </div>

            <div class="mt-3 w-full space-y-1.5">
                <Progress :model-value="progressValue" class="h-1.5" />
                <p class="text-xs text-muted-foreground">
                    {{ subText }}
                </p>
            </div>

            <div class="mt-4 w-full">
                <Button
                    as-child
                    size="sm"
                    class="w-full"
                >
                    <a
                        :href="officer.youtube_url || 'https://youtube.com'"
                        target="_blank"
                        rel="noopener"
                    >
                        Subscribe
                    </a>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
