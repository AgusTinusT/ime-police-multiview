<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

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
    <div 
        v-if="isVisible && currentAnnouncement" 
        @mouseenter="stopTimer" 
        @mouseleave="startTimer"
        class="bg-gradient-to-r from-blue-950/90 via-indigo-950/95 to-slate-950 border border-blue-500/40 rounded-2xl p-3 sm:p-4 mb-4 shadow-2xl backdrop-blur-xl transition-all duration-300 relative overflow-hidden font-sans group"
    >
        <!-- Background Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-start sm:items-center justify-between gap-3 relative z-10">
            <!-- Banner Main Content -->
            <div class="flex items-start sm:items-center space-x-3 min-w-0 flex-1">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-500/20 border border-blue-400/40 flex items-center justify-center text-blue-300 text-lg shrink-0 mt-0.5 sm:mt-0 shadow-inner">
                    📢
                </div>
                
                <div class="min-w-0 flex-1">
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <span 
                            class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-blue-600 text-white font-mono shadow-sm"
                        >
                            {{ currentAnnouncement.badge_text || 'DISPATCH ANNOUNCEMENT' }}
                        </span>

                        <span v-if="props.announcements.length > 1" class="text-[10px] text-slate-400 font-mono">
                            ({{ activeIndex + 1 }}/{{ props.announcements.length }})
                        </span>
                    </div>

                    <h4 class="text-xs sm:text-sm font-bold text-white mt-1 leading-snug truncate">
                        {{ currentAnnouncement.title }}
                    </h4>

                    <p v-if="currentAnnouncement.content" class="text-[11px] sm:text-xs text-slate-300 mt-0.5 line-clamp-2 leading-relaxed">
                        {{ currentAnnouncement.content }}
                    </p>
                </div>
            </div>

            <!-- Action Link & Navigation / Close Controls -->
            <div class="flex items-center space-x-2 shrink-0">
                <a 
                    v-if="currentAnnouncement.action_url"
                    :href="currentAnnouncement.action_url"
                    target="_blank"
                    class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs font-mono shadow-lg transition flex items-center gap-1"
                >
                    <span>{{ currentAnnouncement.action_text || 'Buka Link' }}</span>
                    <span>→</span>
                </a>

                <!-- Carousel Controls (if > 1 announcements) -->
                <div v-if="props.announcements.length > 1" class="hidden sm:flex items-center space-x-1 border-l border-slate-800 pl-2">
                    <button 
                        @click="prevSlide" 
                        class="p-1.5 rounded-lg bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white transition text-xs"
                        title="Pengumuman Sebelumnya"
                    >
                        ❮
                    </button>
                    <button 
                        @click="nextSlide" 
                        class="p-1.5 rounded-lg bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white transition text-xs"
                        title="Pengumuman Selanjutnya"
                    >
                        ❯
                    </button>
                </div>

                <!-- Dismiss Button -->
                <button 
                    @click="dismissBanner"
                    class="p-1.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-white transition text-xs font-mono"
                    title="Tutup Banner"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Slide Indicators (If > 1) -->
        <div v-if="props.announcements.length > 1" class="flex items-center justify-center space-x-1.5 mt-2.5 pt-2 border-t border-slate-800/60">
            <button 
                v-for="(_, idx) in props.announcements" 
                :key="idx"
                @click="setSlide(idx)"
                class="h-1 rounded-full transition-all duration-300"
                :class="idx === activeIndex ? 'w-5 bg-blue-400' : 'w-1.5 bg-slate-700 hover:bg-slate-500'"
            ></button>
        </div>
    </div>
</template>
