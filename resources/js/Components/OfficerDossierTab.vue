<script setup>
import { ref, computed, nextTick } from "vue";

const props = defineProps({
    officer: {
        type: Object,
        default: () => ({}),
    },
    stream: {
        type: Object,
        default: () => ({}),
    },
});

// Mock initial impressions list (YouTube comment style)
const impressions = ref([
    {
        id: 1,
        author_name: "Dispatcher_Alpha9",
        author_role: "Senior Dispatcher",
        impression_type: "TACTICAL_RATING",
        content: "Navigasi mengemudi saat 10-80 sangat presisi. Selalu memberikan pit maneuver dengan pertimbangan keamanan warga sipil yang baik.",
        likes_count: 24,
        is_liked: false,
        created_at: "2 jam lalu",
    },
    {
        id: 2,
        author_name: "Citizen_V",
        author_role: "Warga",
        impression_type: "FUNNY_MOMENT",
        content: "Lucu waktu negosiasi perampokan bank kemarin, perwira ini tetap tenang menangani suspek dengan komedi ringan.",
        likes_count: 42,
        is_liked: true,
        created_at: "5 jam lalu",
    },
    {
        id: 3,
        author_name: "Trooper_K-9",
        author_role: "Verified Officer",
        impression_type: "COMMUNITY_NOTE",
        content: "Perwira senior dengan dedikasi tinggi. Selalu siap memimpin respon awal saat sinyal 10-99 (baku tembak) berbunyi di Paleto.",
        likes_count: 18,
        is_liked: false,
        created_at: "1 hari lalu",
    },
]);

const sortOrder = ref("newest");

const sortedImpressions = computed(() => {
    const list = [...impressions.value];
    if (sortOrder.value === "oldest") {
        return list.reverse();
    }
    return list;
});

// YouTube Comment Input Form State
const isCommentInputFocused = ref(false);
const newCommentContent = ref("");
const newCommentAuthor = ref("");
const newCommentType = ref("COMMUNITY_NOTE");

const commentTextareaRef = ref(null);

const focusCommentInput = () => {
    isCommentInputFocused.value = true;
    nextTick(() => {
        if (commentTextareaRef.value) {
            commentTextareaRef.value.focus();
        }
    });
};

const cancelComment = () => {
    isCommentInputFocused.value = false;
    newCommentContent.value = "";
    newCommentAuthor.value = "";
};

const submitComment = () => {
    if (!newCommentContent.value.trim()) return;

    impressions.value.unshift({
        id: Date.now(),
        author_name: newCommentAuthor.value.trim() || "Penonton Komunitas",
        author_role: "Penonton",
        impression_type: newCommentType.value,
        content: newCommentContent.value.trim(),
        likes_count: 0,
        is_liked: false,
        created_at: "Baru saja",
    });

    // Reset & Collapse
    newCommentContent.value = "";
    isCommentInputFocused.value = false;
    sortOrder.value = "newest";
};

defineExpose({
    focusCommentInput,
});

const deleteImpression = (id) => {
    if (confirm("Apakah Anda yakin ingin menghapus catatan kesan ini?")) {
        impressions.value = impressions.value.filter((i) => i.id !== id);
    }
};

const toggleLike = (impression) => {
    impression.is_liked = !impression.is_liked;
    if (impression.is_liked) {
        impression.likes_count++;
    } else {
        impression.likes_count--;
    }
};

const getImpressionBadge = (type) => {
    switch (type) {
        case "TACTICAL_RATING":
            return "Rating Taktis";
        case "FUNNY_MOMENT":
            return "Momen Lucu";
        case "REPUTATION":
            return "Reputasi RP";
        default:
            return "Catatan Komunitas";
    }
};
</script>

<template>
    <div class="space-y-5 font-sans">
        <!-- TACTICAL DOSSIER HEADER CARD -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded bg-slate-950 border border-slate-800 flex items-center justify-center font-bold text-base text-slate-300 font-mono shrink-0">
                        {{ officer?.callsign || stream?.officer?.callsign || "SLT" }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-950 text-slate-300 border border-slate-800">
                                {{ officer?.department || stream?.officer?.department || "SASP" }}
                            </span>
                            <span class="text-xs font-mono text-slate-400">
                                Badge #{{ officer?.badge_number || stream?.officer?.badge_number || "402" }}
                            </span>
                        </div>
                        <h2 class="text-base font-semibold text-slate-100 mt-1">
                            {{ officer?.officer_name || stream?.officer?.officer_name || "Senior Lead Officer" }}
                        </h2>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">
                            {{ officer?.rank || stream?.officer?.rank || "Senior Lead Trooper" }}
                        </p>
                    </div>
                </div>

                <!-- SUBTLE METRICS BADGES -->
                <div class="grid grid-cols-2 gap-2 w-full md:w-auto font-mono text-center text-xs">
                    <div class="bg-slate-950 border border-slate-800 p-2.5 rounded-md">
                        <span class="block text-[10px] text-slate-500">STATUS PATROLI</span>
                        <span class="text-xs font-medium text-slate-300 mt-0.5 block">10-8 ACTIVE</span>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 p-2.5 rounded-md">
                        <span class="block text-[10px] text-slate-500">CATATAN KOMUNITAS</span>
                        <span class="text-xs font-medium text-slate-300 mt-0.5 block">{{ impressions.length }} Entri</span>
                    </div>
                </div>
            </div>

            <!-- CERTIFICATIONS -->
            <div class="pt-3 border-t border-slate-800 flex items-center justify-between flex-wrap gap-2 text-xs font-mono">
                <div class="flex items-center space-x-2 text-slate-400">
                    <span class="text-slate-300 font-medium">Sertifikasi:</span>
                    <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-300 border border-slate-800">High Pursuit Driver</span>
                    <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-300 border border-slate-800">Air Support Lead</span>
                    <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-300 border border-slate-800">Negotiator</span>
                </div>
            </div>
        </div>

        <!-- COMMUNITY IMPRESSIONS FEED SECTION -->
        <div class="space-y-4">
            <!-- SECTION TITLE & SORT FILTER -->
            <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <h3 class="text-xs font-medium text-slate-200 uppercase tracking-tight font-mono flex items-center gap-2">
                        <span>Kesan & Lore Komunitas</span>
                        <span class="px-2 py-0.5 rounded bg-slate-950 text-slate-400 border border-slate-800 font-mono text-[10px]">
                            {{ impressions.length }}
                        </span>
                    </h3>
                </div>

                <!-- SORT FILTER (Terbaru / Terlama) -->
                <div class="flex items-center space-x-1 text-xs font-mono">
                    <span class="text-slate-400 mr-1.5 text-[11px]">Urutkan:</span>
                    <button
                        @click="sortOrder = 'newest'"
                        :class="sortOrder === 'newest' ? 'bg-slate-800 text-slate-100 border-slate-700 font-medium' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                        class="px-2.5 py-1 rounded-md border transition-colors cursor-pointer"
                    >
                        Terbaru
                    </button>
                    <button
                        @click="sortOrder = 'oldest'"
                        :class="sortOrder === 'oldest' ? 'bg-slate-800 text-slate-100 border-slate-700 font-medium' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border-slate-800'"
                        class="px-2.5 py-1 rounded-md border transition-colors cursor-pointer"
                    >
                        Terlama
                    </button>
                </div>
            </div>

            <!-- YOUTUBE-STYLE INLINE COMMENT POSTING BOX -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 space-y-3">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded bg-slate-950 border border-slate-800 flex items-center justify-center font-mono font-medium text-xs text-slate-400 shrink-0 mt-0.5">
                        {{ newCommentAuthor ? newCommentAuthor.charAt(0).toUpperCase() : 'U' }}
                    </div>

                    <div class="flex-1 space-y-2.5">
                        <!-- UNCOLLAPSED / COLLAPSED INPUT -->
                        <div v-if="!isCommentInputFocused" @click="focusCommentInput" class="w-full bg-slate-950 border border-slate-800 rounded-md px-3 py-2 text-slate-400 text-xs cursor-text font-mono hover:border-slate-700 transition-colors">
                            Buat catatan kesan tentang perwira ini...
                        </div>

                        <div v-else class="space-y-3 animate-in fade-in duration-150">
                            <textarea
                                ref="commentTextareaRef"
                                v-model="newCommentContent"
                                rows="3"
                                placeholder="Buat catatan kesan atau rekap RP perwira ini..."
                                class="w-full bg-slate-950 border border-slate-800 focus:border-slate-700 rounded-md p-2.5 text-slate-100 text-xs font-mono outline-none resize-none leading-relaxed"
                            ></textarea>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pt-1">
                                <div class="flex items-center space-x-2 text-xs font-mono">
                                    <input
                                        v-model="newCommentAuthor"
                                        type="text"
                                        placeholder="Panggilan Anda (Opsional)"
                                        class="bg-slate-950 border border-slate-800 rounded-md px-2.5 py-1 text-slate-200 text-xs outline-none focus:border-slate-700 w-48"
                                    />
                                </div>

                                <!-- ACTION BUTTONS (YouTube Style Bottom Right) -->
                                <div class="flex items-center space-x-2 justify-end">
                                    <button
                                        @click="cancelComment"
                                        class="px-3 py-1.5 rounded-md text-xs font-medium text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        @click="submitComment"
                                        :disabled="!newCommentContent.trim()"
                                        :class="newCommentContent.trim() ? 'bg-blue-600 hover:bg-blue-500 text-white font-medium' : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
                                        class="px-3.5 py-1.5 rounded-md text-xs transition-colors"
                                    >
                                        Kirim Kesan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMPRESSIONS FEED LIST -->
            <div class="space-y-3">
                <div
                    v-for="item in sortedImpressions"
                    :key="item.id"
                    class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-7 h-7 rounded bg-slate-950 border border-slate-800 flex items-center justify-center font-mono font-medium text-xs text-slate-300">
                                {{ item.author_name.charAt(0) }}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-medium text-slate-200 font-mono">{{ item.author_name }}</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-950 text-slate-400 border border-slate-800 font-mono">
                                        {{ item.author_role }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-mono text-slate-500 block">{{ item.created_at }}</span>
                            </div>
                        </div>

                        <!-- ADMIN ACTION: DELETE COMMENT -->
                        <button
                            @click="deleteImpression(item.id)"
                            class="px-2 py-1 bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-red-400 border border-slate-800 hover:border-slate-700 rounded text-[10px] font-mono transition-colors flex items-center gap-1 cursor-pointer"
                            title="Hapus Komentar (Akses Admin)"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>

                    <!-- IMPRESSION CONTENT TEXT -->
                    <p class="text-xs text-slate-300 font-mono leading-relaxed bg-slate-950 p-3 rounded-md border border-slate-800">
                        "{{ item.content }}"
                    </p>

                </div>

                <div v-if="sortedImpressions.length === 0" class="p-8 text-center bg-slate-900 rounded-xl border border-slate-800 text-slate-400 text-xs font-mono">
                    Belum ada kesan untuk perwira ini.
                </div>
            </div>
        </div>
    </div>
</template>
