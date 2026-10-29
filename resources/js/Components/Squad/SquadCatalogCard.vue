<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { GitFork, Users } from "@lucide/vue";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { LiveBadge, OfficerAvatar } from "@/Components/tactical";

const props = defineProps({
    squad: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["fork"]);

const displayedOfficers = computed(() => {
    return (props.squad.officers || []).slice(0, 5);
});

const overflowCount = computed(() => {
    const total = props.squad.officers?.length || 0;
    return total > 5 ? total - 5 : 0;
});
</script>

<template>
    <div class="w-64 sm:w-72 shrink-0 snap-start rounded-md border border-border bg-card p-4 hover:border-primary/50 transition-colors flex flex-col justify-between">
        <Link :href="`/multiview?squad=${squad.slug}`" class="block space-y-3 flex-1 focus-visible:outline-hidden">
            <!-- Header & Badges -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-foreground text-sm sm:text-base truncate group-hover:text-primary transition-colors">
                        {{ squad.name }}
                    </h3>
                    <p class="text-xs text-muted-foreground font-mono truncate">
                        by {{ squad.owner_name }}
                    </p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <LiveBadge
                        v-if="squad.live_count > 0"
                        :label="`${squad.live_count} LIVE`"
                    />
                    <Badge variant="secondary" class="gap-1 font-mono text-xs px-1.5">
                        <GitFork class="size-3" />
                        <span>{{ squad.forks_count || 0 }}</span>
                    </Badge>
                </div>
            </div>

            <!-- Description (if any) -->
            <p v-if="squad.description" class="text-xs text-muted-foreground line-clamp-2">
                {{ squad.description }}
            </p>

            <!-- Officer Avatars Stack -->
            <div class="pt-1">
                <div class="flex items-center -space-x-2 overflow-hidden py-1">
                    <OfficerAvatar
                        v-for="officer in displayedOfficers"
                        :key="officer.id"
                        :src="officer.avatar_url"
                        :name="officer.officer_name"
                        :callsign="officer.callsign"
                        :department="officer.department"
                        size="sm"
                        class="ring-2 ring-card shrink-0"
                    />
                    <div
                        v-if="overflowCount > 0"
                        class="size-8 rounded-full bg-muted flex items-center justify-center font-mono text-xs font-bold ring-2 ring-card text-muted-foreground shrink-0"
                    >
                        +{{ overflowCount }}
                    </div>
                </div>
                <div class="flex items-center gap-1 text-xs text-muted-foreground font-mono mt-1">
                    <Users class="size-3" />
                    <span>{{ squad.officers?.length || 0 }} perwira</span>
                </div>
            </div>
        </Link>

        <!-- Bottom Fork Action -->
        <div class="pt-3 border-t border-border mt-3">
            <Button
                variant="outline"
                size="sm"
                class="gap-1.5 w-full font-mono text-xs"
                @click.prevent.stop="emit('fork', squad)"
            >
                <GitFork class="size-3.5" />
                <span>{{ $t('squad.fork_button') }}</span>
            </Button>
        </div>
    </div>
</template>
