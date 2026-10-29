<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { ArrowRight, ChevronLeft, ChevronRight, Megaphone, X } from '@lucide/vue';

const props = defineProps({
    announcements: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const isVisible = ref(true);
const activeIndex = ref(0);
let timer = null;

const currentAnnouncement = computed(() => {
    if (!props.announcements || props.announcements.length === 0) return null;
    return props.announcements[activeIndex.value % props.announcements.length];
});

const announcementTone = computed(() => {
    const type = (currentAnnouncement.value?.type || '').toLowerCase();
    if (type === 'warning' || type === 'poll') {
        return {
            alertClass: 'border-warning/40 bg-warning/10',
            iconWrapClass: 'bg-warning/15 border-warning/40 text-warning',
            badgeVariant: 'warning',
        };
    }
    if (type === 'info') {
        return {
            alertClass: 'border-info/40 bg-info/10',
            iconWrapClass: 'bg-info/15 border-info/40 text-info',
            badgeVariant: 'info',
        };
    }
    return {
        alertClass: 'border-primary/40 bg-card/95',
        iconWrapClass: 'bg-primary/15 border-primary/40 text-primary',
        badgeVariant: 'default',
    };
});

const startTimer = () => {
    if (timer) clearInterval(timer);
    if (props.announcements && props.announcements.length > 1) {
        timer = setInterval(() => {
            nextSlide();
        }, 6000);
    }
};

const stopTimer = () => {
    if (timer) clearInterval(timer);
};

const nextSlide = () => {
    if (!props.announcements || props.announcements.length === 0) return;
    activeIndex.value = (activeIndex.value + 1) % props.announcements.length;
};

const prevSlide = () => {
    if (!props.announcements || props.announcements.length === 0) return;
    activeIndex.value = (activeIndex.value - 1 + props.announcements.length) % props.announcements.length;
};

const setSlide = (idx) => {
    activeIndex.value = idx;
    startTimer();
};

const dismissBanner = () => {
    isVisible.value = false;
    emit('close');
};

watch(() => props.announcements, (newVal) => {
    if (newVal && newVal.length > 0) {
        activeIndex.value = 0;
        startTimer();
    } else {
        stopTimer();
    }
}, { immediate: true, deep: true });

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <Alert
        v-if="isVisible && currentAnnouncement"
        @mouseenter="stopTimer"
        @mouseleave="startTimer"
        :class="cn('block p-3 sm:p-4 mb-4 rounded-xl backdrop-blur-xl transition-all duration-300 relative overflow-hidden font-sans group', announcementTone.alertClass)"
    >
        <div class="flex items-start sm:items-center justify-between gap-3 relative z-10">
            <!-- Banner Main Content -->
            <div class="flex items-start sm:items-center space-x-3 min-w-0 flex-1">
                <div
                    :class="cn('w-9 h-9 sm:w-10 sm:h-10 rounded-xl border flex items-center justify-center shrink-0 mt-0.5 sm:mt-0', announcementTone.iconWrapClass)"
                >
                    <Megaphone class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <Badge
                            :variant="announcementTone.badgeVariant"
                            class="uppercase tracking-wider font-mono font-extrabold"
                        >
                            {{ currentAnnouncement.badge_text || 'DISPATCH ANNOUNCEMENT' }}
                        </Badge>

                        <span v-if="props.announcements.length > 1" class="text-xs text-muted-foreground font-mono">
                            ({{ activeIndex + 1 }}/{{ props.announcements.length }})
                        </span>
                    </div>

                    <AlertTitle class="text-xs sm:text-sm font-bold text-foreground mt-1 leading-snug truncate">
                        {{ currentAnnouncement.title }}
                    </AlertTitle>

                    <AlertDescription v-if="currentAnnouncement.content" class="text-xs text-foreground/80 mt-0.5 line-clamp-2 leading-relaxed">
                        {{ currentAnnouncement.content }}
                    </AlertDescription>
                </div>
            </div>

            <!-- Action Link & Navigation / Close Controls -->
            <div class="flex items-center space-x-2 shrink-0">
                <Button
                    v-if="currentAnnouncement.action_url"
                    as-child
                    size="sm"
                    class="font-mono text-xs"
                >
                    <a
                        :href="currentAnnouncement.action_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span>{{ currentAnnouncement.action_text || 'Buka Link' }}</span>
                        <ArrowRight class="size-3.5" />
                    </a>
                </Button>

                <!-- Carousel Controls (if > 1 announcements) -->
                <div v-if="props.announcements.length > 1" class="hidden sm:flex items-center space-x-1 border-l border-border pl-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        aria-label="Pengumuman Sebelumnya"
                        title="Pengumuman Sebelumnya"
                        @click="prevSlide"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        aria-label="Pengumuman Selanjutnya"
                        title="Pengumuman Selanjutnya"
                        @click="nextSlide"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>

                <!-- Dismiss Button -->
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-7 text-muted-foreground hover:text-foreground"
                    aria-label="Tutup Banner"
                    title="Tutup Banner"
                    @click="dismissBanner"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <!-- Slide Indicators (If > 1) -->
        <div v-if="props.announcements.length > 1" class="flex items-center justify-center space-x-1.5 mt-2.5 pt-2 border-t border-border/60">
            <button
                v-for="(_, idx) in props.announcements"
                :key="idx"
                type="button"
                :aria-label="`Pengumuman ${idx + 1}`"
                @click="setSlide(idx)"
                :class="cn('h-1 rounded-full transition-all duration-300', idx === activeIndex ? 'w-5 bg-primary' : 'w-1.5 bg-muted hover:bg-muted-foreground')"
            ></button>
        </div>
    </Alert>
</template>
