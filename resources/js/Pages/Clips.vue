<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import CustomVideoPlayer from "@/Components/CustomVideoPlayer.vue";
import axios from "axios";
import iconCut from "@/Components/Icons/cut-svgrepo-com.svg";

const props = defineProps({
    officers: {
        type: Array,
        default: () => [],
    },
    initialClips: {
        type: Array,
        default: () => [],
    },
    lastSyncedAt: {
        type: String,
        default: "",
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isAdmin = computed(
    () => !!authUser.value?.is_admin || authUser.value?.role === "admin",
);
const canAccessClipper = computed(() => {
    if (!authUser.value) return false;
    const r = (authUser.value.role || "").toLowerCase();
    return r === "clips" || r === "admin" || !!authUser.value.is_admin;
});

const openInClipper = (clip) => {
    if (!clip) return;
    const videoUrl = clip.youtube_url || (clip.video_id ? `https://www.youtube.com/watch?v=${clip.video_id}` : "");
    const startSec = clip.start_seconds || 0;
    const endSec = clip.end_seconds || 60;
    const title = clip.title || "";

    const targetUrl = `/clipper?url=${encodeURIComponent(videoUrl)}&start=${encodeURIComponent(startSec)}&end=${encodeURIComponent(endSec)}&title=${encodeURIComponent(title)}`;
    window.open(targetUrl, "_blank");
};

// STORAGE & LOCAL STATE
const STORAGE_KEY = "ime_focus_community_clips";
const VIEW_MODE_KEY = "ime_clips_view_mode";
const LIKED_CLIPS_KEY = "ime_liked_clips";

const loadCustomClips = () => {
    if (typeof window === "undefined") return [];
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        return [];
    }
};

const saveCustomClips = (clipsList) => {
    if (typeof window === "undefined") return;
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(clipsList));
    } catch (e) {
        // Silently handle storage error
    }
};

// Initialize clips exclusively from MySQL database props.initialClips
const clips = ref(props.initialClips || []);

// LIKED CLIPS STATE
const loadLikedClips = () => {
    if (typeof window === "undefined") return {};
    try {
        const stored = localStorage.getItem(LIKED_CLIPS_KEY);
        return stored ? JSON.parse(stored) : {};
    } catch (e) {
        return {};
    }
};

const likedClipsMap = ref(loadLikedClips());

const toggleLikeClip = async (clipId) => {
    if (!clipId) return;
    const isLiked = !!likedClipsMap.value[clipId];

    if (isLiked) {
        delete likedClipsMap.value[clipId];
    } else {
        likedClipsMap.value[clipId] = true;
    }

    if (typeof window !== "undefined") {
        localStorage.setItem(
            LIKED_CLIPS_KEY,
            JSON.stringify(likedClipsMap.value),
        );
    }

    try {
        const response = await axios.post(`/api/v1/tac-clips/${clipId}/like`);
        if (response.data && response.data.likes_count !== undefined) {
            const clip = clips.value.find((c) => c.id === clipId);
            if (clip) {
                clip.likes_count = response.data.likes_count;
            }
        }
    } catch (e) {
        // Silently handle backend like error
    }
};

const isClipLiked = (clipId) => !!likedClipsMap.value[clipId];

const getClipLikesCount = (clip) => {
    const base = clip?.likes_count || 0;
    return isClipLiked(clip?.id) ? Math.max(base, base + (likedClipsMap.value[clip?.id] ? 1 : 0)) : base;
};

// ADD NEW TACCLIP MODAL STATE
const isAddModalOpen = ref(false);
const isSubmittingClip = ref(false);
const newClipForm = ref({
    youtube_url: "",
    title: "",
    start_time: "00:00:10",
    end_time: "00:01:00",
    officer_id: "",
    creator_name: "",
});

const openAddModal = () => {
    newClipForm.value = {
        youtube_url: "",
        title: "",
        start_time: "00:00:10",
        end_time: "00:01:00",
        officer_id: "",
        creator_name: authUser.value ? authUser.value.name : "",
    };
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
};

const parseTimestampToSeconds = (input) => {
    if (!input) return 0;
    if (typeof input === "number") return input;
    const str = String(input).trim();
    if (!isNaN(str) && !str.includes(":")) return parseInt(str, 10);
    const parts = str.split(":").map((n) => parseInt(n, 10) || 0);
    if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
    if (parts.length === 2) return parts[0] * 60 + parts[1];
    if (parts.length === 1) return parts[0];
    return 0;
};

const submitNewClip = async () => {
    if (!newClipForm.value.youtube_url.trim()) {
        alert("Mohon masukkan URL video YouTube.");
        return;
    }
    if (!newClipForm.value.title.trim()) {
        alert("Mohon masukkan judul momen aksi.");
        return;
    }

    const startSec = parseTimestampToSeconds(newClipForm.value.start_time);
    const endSec = parseTimestampToSeconds(newClipForm.value.end_time);

    if (endSec <= startSec) {
        alert("Waktu Selesai harus lebih besar dari Waktu Mulai.");
        return;
    }

    if (endSec - startSec > 600) {
        alert("Durasi klip maksimal 10 menit (600 detik).");
        return;
    }

    isSubmittingClip.value = true;
    try {
        const selectedOfficer = props.officers.find(o => o.id === parseInt(newClipForm.value.officer_id, 10));

        const response = await axios.post("/api/v1/tac-clips", {
            youtube_url: newClipForm.value.youtube_url,
            title: newClipForm.value.title,
            start_seconds: startSec,
            end_seconds: endSec,
            officer_id: selectedOfficer ? selectedOfficer.id : null,
            officer_name: selectedOfficer ? selectedOfficer.officer_name : null,
            officer_handle: selectedOfficer ? selectedOfficer.handle : null,
            creator_name: newClipForm.value.creator_name || (authUser.value ? authUser.value.name : "Guest"),
        });

        if (response.data && response.data.clip) {
            clips.value.unshift(response.data.clip);
            saveCustomClips(clips.value);
            activeShortIndex.value = 0;
            closeAddModal();
            showToast("TacClip momen aksi berhasil ditambahkan dan dapat dilihat publik!");
        }
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal menyimpan TacClip.";
        alert(msg);
    } finally {
        isSubmittingClip.value = false;
    }
};

// VIEW MODE STATE: 'shorts' (16:9 Cinema Reels Feed) vs 'grid' (Grid Galeri)
const loadInitialViewMode = () => {
    if (typeof window === "undefined") return "shorts";
    return localStorage.getItem(VIEW_MODE_KEY) || "shorts";
};

const viewMode = ref(loadInitialViewMode());

const setViewMode = (mode) => {
    viewMode.value = mode;
    if (typeof window !== "undefined") {
        localStorage.setItem(VIEW_MODE_KEY, mode);
    }
};

// PURGE LEGACY LOCALSTORAGE STORAGE_KEY IF PRESENT
onMounted(() => {
    if (typeof window !== "undefined") {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {}
    }
});

// FILTERS & SEARCH STATE
const searchQuery = ref("");
const selectedDepartment = ref("ALL"); // 'ALL', 'LSPD', 'BCSO', 'SASP', 'SAPR'
const scopeFilter = ref("all"); // 'all', 'my_clips'
const sortBy = ref("newest"); // 'newest', 'oldest', 'duration'

// PAGINATION & LAZY LOADING (FOR GRID MODE)
const visibleCount = ref(12);

const loadMore = () => {
    visibleCount.value += 12;
};

watch([searchQuery, selectedDepartment, scopeFilter, sortBy], () => {
    visibleCount.value = 12;
    activeShortIndex.value = 0;
});

// TIME FORMATTING HELPERS
const formatTime = (seconds) => {
    if (isNaN(seconds) || seconds === null || seconds < 0) return "00:00";
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    const pad = (num) => String(num).padStart(2, "0");
    if (h > 0) {
        return `${h}:${pad(m)}:${pad(s)}`;
    }
    return `${pad(m)}:${pad(s)}`;
};

const formatDurationText = (start, end) => {
    const diff = Math.max(0, (end || 0) - (start || 0));
    if (diff < 60) return `${diff} Detik`;
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    return s > 0 ? `${m}m ${s}s` : `${m} Menit`;
};

const formatLikesText = (num) => {
    if (num >= 1000) return (num / 1000).toFixed(1) + "k";
    return String(num);
};

// AUTHORIZATION CHECK FOR DELETING CLIP
const canDeleteClip = (clip) => {
    if (!clip) return false;
    if (isAdmin.value) return true;
    if (authUser.value && clip.user_id && clip.user_id === authUser.value.id) return true;
    if (
        authUser.value &&
        clip.creator_name &&
        authUser.value.name &&
        clip.creator_name.trim().toLowerCase() ===
            authUser.value.name.trim().toLowerCase()
    ) {
        return true;
    }
    return true; // Guests can also delete local items
};

const deleteClip = async (clipId) => {
    const clipTarget = clips.value.find((c) => c.id === clipId);
    if (clipTarget && !canDeleteClip(clipTarget)) {
        alert("Anda tidak memiliki izin untuk menghapus klip ini!");
        return;
    }
    if (
        confirm(
            "Apakah Anda yakin ingin menghapus klip momen aksi ini secara permanen dari komunitas?",
        )
    ) {
        try {
            await axios.delete(`/api/v1/tac-clips/${clipId}`);
        } catch (e) {
            // Silently fallback if deletion fails
        }
        clips.value = clips.value.filter((c) => c.id !== clipId);
        saveCustomClips(clips.value);
        if (
            activeShortIndex.value >= filteredClips.value.length &&
            activeShortIndex.value > 0
        ) {
            activeShortIndex.value--;
        }
        showToast("Klip berhasil dihapus dari komunitas");
    }
};

// FILTERED & SORTED CLIPS COMPUTED
const filteredClips = computed(() => {
    let list = [...clips.value];

    // 1. Scope Filter (Semua vs Klip Saya)
    if (scopeFilter.value === "my_clips") {
        if (!authUser.value) {
            list = [];
        } else {
            list = list.filter((c) => canDeleteClip(c));
        }
    }

    // 2. Department Filter
    if (selectedDepartment.value !== "ALL") {
        const dept = selectedDepartment.value.toUpperCase();
        list = list.filter((c) => {
            if (!c.officer_name && !c.officer_handle) return true;
            const matchedOfficer = props.officers.find(
                (o) =>
                    (c.officer_id && o.id === c.officer_id) ||
                    (c.officer_name &&
                        o.officer_name?.toLowerCase() ===
                            c.officer_name?.toLowerCase()) ||
                    (c.officer_handle &&
                        o.handle?.toLowerCase() ===
                            c.officer_handle?.toLowerCase()),
            );
            if (!matchedOfficer) return true;
            if (dept === "SAPR")
                return ["SAPR", "PARK RANGER"].includes(
                    matchedOfficer.department?.toUpperCase(),
                );
            return matchedOfficer.department?.toUpperCase() === dept;
        });
    }

    // 3. Search Query
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (c) =>
                (c.title || "").toLowerCase().includes(q) ||
                (c.creator_name || "").toLowerCase().includes(q) ||
                (c.officer_name || "").toLowerCase().includes(q) ||
                (c.officer_handle || "").toLowerCase().includes(q),
        );
    }

    // 4. Sorting
    if (sortBy.value === "oldest") {
        list.sort((a, b) => (a.id || 0) - (b.id || 0));
    } else if (sortBy.value === "duration") {
        list.sort(
            (a, b) =>
                b.end_seconds -
                b.start_seconds -
                (a.end_seconds - a.start_seconds),
        );
    } else {
        // 'newest'
        list.sort((a, b) => (b.id || 0) - (a.id || 0));
    }

    return list;
});

const paginatedClips = computed(() => {
    return filteredClips.value.slice(0, visibleCount.value);
});

const hasMoreClips = computed(() => {
    return visibleCount.value < filteredClips.value.length;
});

// INTERSECTION OBSERVER FOR INFINITE SCROLL (GRID MODE)
const sentinelRef = ref(null);
let observer = null;

onMounted(() => {
    if ("IntersectionObserver" in window) {
        observer = new IntersectionObserver(
            (entries) => {
                if (
                    entries[0].isIntersecting &&
                    hasMoreClips.value &&
                    viewMode.value === "grid"
                ) {
                    loadMore();
                }
            },
            { rootMargin: "300px" },
        );
        if (sentinelRef.value) {
            observer.observe(sentinelRef.value);
        }
    }
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// TELEMETRY KPI COMPUTED
const totalClipsCount = computed(() => clips.value.length);

const myClipsCount = computed(() => {
    if (!authUser.value) return 0;
    return clips.value.filter((c) => canDeleteClip(c)).length;
});

const uniqueOfficersCount = computed(() => {
    const handles = new Set();
    clips.value.forEach((c) => {
        if (c.officer_handle) handles.add(c.officer_handle);
        else if (c.officer_name) handles.add(c.officer_name);
    });
    return handles.size;
});

const topClipperName = computed(() => {
    if (clips.value.length === 0) return "Belum Ada";
    const counts = {};
    clips.value.forEach((c) => {
        const creator = c.creator_name || "Guest";
        counts[creator] = (counts[creator] || 0) + 1;
    });
    let max = 0;
    let top = "Anonim";
    Object.entries(counts).forEach(([name, cnt]) => {
        if (cnt > max) {
            max = cnt;
            top = name;
        }
    });
    return top;
});

// TOAST NOTIFICATION LOGIC
const toastText = ref("");
const toastVisible = ref(false);
let toastTimeout = null;

const showToast = (msg) => {
    toastText.value = msg;
    toastVisible.value = true;
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toastVisible.value = false;
    }, 2200);
};

// MODAL EMBED PLAYER FOR GRID MODE
const activePlayingClip = ref(null);
const iframeKey = ref(0);

const openClipModal = (clip) => {
    activePlayingClip.value = clip;
    iframeKey.value++;
};

const closeClipModal = () => {
    activePlayingClip.value = null;
};

const replayClip = () => {
    iframeKey.value++;
    shortKey.value++;
};

const copyClipLink = (clip) => {
    if (!clip) return;
    const url = `https://www.youtube.com/watch?v=${clip.video_id}&t=${clip.start_seconds}s`;
    navigator.clipboard
        .writeText(url)
        .then(() => {
            showToast("Tautan video berhasil disalin!");
        })
        .catch(() => {
            showToast("Gagal menyalin tautan");
        });
};

// 16:9 CLEAN CINEMA FEED MODE LOGIC & KEYBOARD NAV
const activeShortIndex = ref(0);
const shortKey = ref(0);
const isMuted = ref(false);
const progressPercentage = ref(0);
let progressInterval = null;

const toggleMute = () => {
    isMuted.value = !isMuted.value;
    shortKey.value++;
};

const currentShortClip = computed(() => {
    if (filteredClips.value.length === 0) return null;
    const index = Math.max(
        0,
        Math.min(activeShortIndex.value, filteredClips.value.length - 1),
    );
    return filteredClips.value[index];
});

const currentClipAvatar = computed(() => {
    const clip = currentShortClip.value;
    if (!clip) return "";

    // 1. Look up matching officer in props.officers database array passed from controller
    if (props.officers && props.officers.length > 0) {
        const clipName = (clip.officer_name || "").toLowerCase().trim();
        const clipHandle = (clip.officer_handle || clip.handle || "").toLowerCase().replace("@", "").trim();
        const clipTitle = (clip.title || "").toLowerCase().trim();
        const clipCreator = (clip.creator_name || "").toLowerCase().trim();

        const found = props.officers.find((o) => {
            const offName = (o.officer_name || "").toLowerCase().trim();
            const offStreamer = (o.streamer_name || "").toLowerCase().trim();
            const offHandle = (o.handle || o.channel_id || "").toLowerCase().replace("@", "").trim();
            const offCallsign = (o.callsign || "").toLowerCase().trim();

            if (clip.officer_id && o.id === clip.officer_id) return true;

            if (clipName || clipHandle) {
                return (
                    (clipName && offName && (clipName === offName || clipName.includes(offName) || offName.includes(clipName))) ||
                    (clipName && offStreamer && (clipName === offStreamer || clipName.includes(offStreamer) || offStreamer.includes(clipName))) ||
                    (clipHandle && offHandle && clipHandle === offHandle) ||
                    (clipName && offCallsign && clipName.includes(offCallsign))
                );
            }

            // Fallback match if clipName/clipHandle are empty: check title or creator
            if (offName && (clipTitle.includes(offName) || clipCreator.includes(offName))) return true;
            if (offStreamer && (clipTitle.includes(offStreamer) || clipCreator.includes(offStreamer))) return true;
            if (offHandle && (clipTitle.includes(offHandle) || clipCreator.includes(offHandle))) return true;

            return false;
        });

        if (found && found.avatar_url && !found.avatar_url.includes("dicebear")) {
            return found.avatar_url;
        }
    }

    // 2. If clip object itself contains a valid non-dicebear avatar URL
    if (clip.avatar_url && !clip.avatar_url.includes("dicebear")) {
        return clip.avatar_url;
    }
    if (clip.officer_avatar && !clip.officer_avatar.includes("dicebear")) {
        return clip.officer_avatar;
    }
    if (clip.avatar && !clip.avatar.includes("dicebear")) {
        return clip.avatar;
    }

    // 3. Fallback to any active officer YouTube avatar from database
    const fallbackOfficer = props.officers?.find(o => o.avatar_url && !o.avatar_url.includes("dicebear"));
    if (fallbackOfficer?.avatar_url) {
        return fallbackOfficer.avatar_url;
    }

    // 4. Default static YouTube fallback avatar
    return clip.avatar_url || "https://yt3.googleusercontent.com/i589zY0D0fUTVDXVbRIPK5HTFahFA8pagMPjj11Djoa7kzjTCqC8BDf4CQzWohqjTMt8W9_woLg=s900-c-k-c0x00ffffff-no-rj";
});

const handleAvatarError = (e) => {
    const fallbackOfficer = props.officers?.find(o => o.avatar_url && !o.avatar_url.includes("dicebear"));
    if (fallbackOfficer?.avatar_url) {
        e.target.src = fallbackOfficer.avatar_url;
    } else {
        e.target.src = "https://yt3.googleusercontent.com/i589zY0D0fUTVDXVbRIPK5HTFahFA8pagMPjj11Djoa7kzjTCqC8BDf4CQzWohqjTMt8W9_woLg=s900-c-k-c0x00ffffff-no-rj";
    }
};

const startProgressTimer = () => {
    if (progressInterval) clearInterval(progressInterval);
    progressPercentage.value = 0;
    const clip = currentShortClip.value;
    if (!clip) return;
    const duration = Math.max(
        1,
        (clip.end_seconds || 60) - (clip.start_seconds || 0),
    );
    const startTime = Date.now();
    progressInterval = setInterval(() => {
        const elapsed = (Date.now() - startTime) / 1000;
        const pct = Math.min(100, (elapsed / duration) * 100);
        progressPercentage.value = pct;
        if (pct >= 100) {
            clearInterval(progressInterval);
        }
    }, 100);
};

watch(
    [currentShortClip, shortKey],
    () => {
        startProgressTimer();
    },
    { immediate: true },
);

const nextShort = () => {
    if (activeShortIndex.value < filteredClips.value.length - 1) {
        activeShortIndex.value++;
        shortKey.value++;
    }
};

const prevShort = () => {
    if (activeShortIndex.value > 0) {
        activeShortIndex.value--;
        shortKey.value++;
    }
};

// KEYBOARD SHORTCUTS FOR 16:9 FEED (UP/DOWN ARROWS, PAGE UP/DOWN, K, R, M)
const handleKeydown = (e) => {
    if (viewMode.value !== "shorts") return;
    if (
        ["input", "textarea", "select"].includes(
            document.activeElement?.tagName?.toLowerCase(),
        )
    )
        return;

    if (e.key === "ArrowDown" || e.key === "PageDown" || e.key === "j") {
        e.preventDefault();
        nextShort();
    } else if (e.key === "ArrowUp" || e.key === "PageUp" || e.key === "k") {
        e.preventDefault();
        prevShort();
    } else if (e.key === "r") {
        replayClip();
    } else if (e.key === "m") {
        toggleMute();
    }
};

// MOUSE WHEEL DEBOUNCED NAVIGATION
let wheelCoolDown = false;
const handleWheel = (e) => {
    if (viewMode.value !== "shorts" || wheelCoolDown) return;
    if (Math.abs(e.deltaY) < 30) return;

    wheelCoolDown = true;
    if (e.deltaY > 0) {
        nextShort();
    } else {
        prevShort();
    }
    setTimeout(() => {
        wheelCoolDown = false;
    }, 600);
};

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
    if (progressInterval) clearInterval(progressInterval);
});
</script>

<template>
    <Head title="TacClips — Galeri Momen Aksi Kepolisian - IME Roleplay" />

    <!-- MINIMALIST FULLSCREEN CINEMA FEED CONTAINER -->
    <div
        @wheel="handleWheel"
        class="relative w-screen h-screen h-dvh bg-slate-950 text-slate-100 font-sans overflow-hidden flex items-center justify-center p-0 m-0 select-none"
    >
        <!-- FLOATING TOP-LEFT HOME ICON BUTTON -->
        <Link
            href="/"
            class="fixed top-4 left-4 z-50 w-10 h-10 rounded-full bg-slate-900/90 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white flex items-center justify-center transition duration-200 shadow-lg cursor-pointer"
            title="Kembali ke Beranda"
        >
            <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
            </svg>
        </Link>

        <!-- FLOATING MIDDLE-RIGHT SCROLL CONTROL BUTTONS -->
        <div
            v-if="filteredClips.length > 0"
            class="fixed right-4 top-1/2 -translate-y-1/2 z-50 flex flex-col gap-2.5 items-center"
        >
            <button
                @click="prevShort"
                :disabled="activeShortIndex === 0"
                :class="[
                    'w-10 h-10 rounded-full border flex items-center justify-center transition shadow-lg',
                    activeShortIndex === 0
                        ? 'opacity-25 cursor-not-allowed bg-slate-900/40 border-slate-800/50 text-slate-600'
                        : 'bg-slate-900/90 hover:bg-slate-800 border-slate-800 text-slate-300 hover:text-white cursor-pointer',
                ]"
                title="Klip Sebelumnya (Panah Atas / Wheel Up)"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 15l7-7 7 7"
                    />
                </svg>
            </button>

            <button
                @click="nextShort"
                :disabled="activeShortIndex === filteredClips.length - 1"
                :class="[
                    'w-10 h-10 rounded-full border flex items-center justify-center transition shadow-lg',
                    activeShortIndex === filteredClips.length - 1
                        ? 'opacity-25 cursor-not-allowed bg-slate-900/40 border-slate-800/50 text-slate-600'
                        : 'bg-slate-900/90 hover:bg-slate-800 border-slate-800 text-slate-300 hover:text-white cursor-pointer',
                ]"
                title="Klip Selanjutnya (Panah Bawah / Wheel Down)"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>
        </div>

        <!-- MAIN STAGE AREA (NO OVERLAYS ON VIDEO) -->
        <div
            v-if="filteredClips.length > 0"
            class="relative w-full h-full flex flex-col items-center justify-center p-2 sm:p-4 max-w-[1600px] mx-auto space-y-2.5"
        >
            <!-- TOP / MIDDLE ROW: VIDEO & DETAILS COLUMN + SIDE ACTIONS COLUMN -->
            <div class="flex items-center justify-center gap-3 sm:gap-5 w-full">
                <!-- VIDEO & DETAILS COLUMN (PERFECTLY ALIGNED WITH VIDEO FRAME EDGES) -->
                <div
                    class="flex-1 max-w-[1380px] w-full flex flex-col gap-2 min-w-0"
                >
                    <!-- 16:9 CLEAN VIDEO FRAME CONTAINER -->
                    <div
                        class="relative w-full aspect-video max-h-[calc(100vh-105px)] max-h-[calc(100dvh-105px)] rounded-xl border border-slate-800/80 shadow-2xl overflow-hidden bg-black flex items-center justify-center"
                    >
                        <!-- CUSTOM VIDEO PLAYER (MATCHING CLIPPER.VUE CUSTOM PLAYER UI) -->
                        <CustomVideoPlayer
                            v-if="currentShortClip"
                            :key="`${currentShortClip.id}-${shortKey}`"
                            :youtube-id="currentShortClip.video_id"
                            :title="currentShortClip.title"
                            :start-seconds="currentShortClip.start_seconds || 0"
                            :end-seconds="currentShortClip.end_seconds || 0"
                            :autoplay="true"
                            class="w-full h-full"
                        />
                    </div>

                    <!-- BOTTOM DETAILS ROW (ALIGNED EXACTLY WITH VIDEO FRAME EDGES) -->
                    <div
                        v-if="currentShortClip"
                        class="w-full flex items-center justify-between gap-4 px-0.5 text-left font-sans"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Profile Photo Avatar (Matching OfficerDirectory.vue) -->
                            <div
                                class="w-9 h-9 rounded-full border border-slate-800 overflow-hidden bg-slate-900 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    :src="currentClipAvatar"
                                    :alt="currentShortClip.officer_name || 'Officer'"
                                    referrerpolicy="no-referrer"
                                    @error="handleAvatarError"
                                    class="w-full h-full object-cover rounded-full"
                                />
                            </div>

                            <!-- Details: Officer, Title, Creator -->
                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="text-xs font-semibold text-slate-200 font-mono"
                                    >
                                        {{
                                            currentShortClip.officer_name ||
                                            currentShortClip.officer_handle ||
                                            "@PatrolUnit"
                                        }}
                                    </span>
                                    <span
                                        class="text-[10px] text-slate-500 font-mono"
                                    >
                                        • Ditandai oleh
                                        <span class="text-slate-400">{{
                                            currentShortClip.creator_name ||
                                            "Guest"
                                        }}</span>
                                    </span>
                                </div>
                                <h2
                                    class="text-xs sm:text-sm font-medium text-slate-100 truncate leading-snug"
                                >
                                    {{ currentShortClip.title }}
                                </h2>
                            </div>
                        </div>

                        <!-- Tags / Timestamp -->
                        <div
                            class="flex items-center gap-2 shrink-0 font-mono text-[10px]"
                        >
                            <span
                                class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-slate-400"
                            >
                                #{{
                                    currentShortClip.officer_name
                                        ? currentShortClip.officer_name.replace(
                                              /\s+/g,
                                              "",
                                          )
                                        : "PoliceRoleplay"
                                }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-slate-400"
                            >
                                ⏱️
                                {{ formatTime(currentShortClip.start_seconds) }}
                                -
                                {{ formatTime(currentShortClip.end_seconds) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SIDE ACTIONS COLUMN (OUTSIDE VIDEO FRAME, ON THE RIGHT) -->
                <div
                    v-if="currentShortClip"
                    class="flex flex-col items-center gap-3 shrink-0 font-mono py-1 self-center"
                >
                    <!-- OPEN & CUT IN CLIPPER (FOR CLIPS ROLE OR ADMIN) -->
                    <div
                        v-if="canAccessClipper"
                        class="flex flex-col items-center space-y-1"
                    >
                        <button
                            @click="openInClipper(currentShortClip)"
                            class="w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-slate-200 flex items-center justify-center transition cursor-pointer shadow-md group"
                            title="Buka & Potong di ClipStudio (Halaman Baru)"
                        >
                            <img :src="iconCut" class="w-4 h-4 invert opacity-75 group-hover:opacity-100 transition-opacity" alt="Cut" />
                        </button>
                        <span class="text-[10px] font-mono text-slate-400"
                            >Clipper</span
                        >
                    </div>

                    <!-- OPEN ON YOUTUBE FULL -->
                    <div class="flex flex-col items-center space-y-1">
                        <a
                            :href="`https://www.youtube.com/watch?v=${currentShortClip.video_id}&t=${currentShortClip.start_seconds}s`"
                            target="_blank"
                            class="w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-slate-200 flex items-center justify-center transition cursor-pointer shadow-md"
                            title="Buka video penuh di YouTube"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                />
                            </svg>
                        </a>
                        <span class="text-[10px] font-mono text-slate-400"
                            >Full</span
                        >
                    </div>

                    <!-- DELETE BUTTON (IF AUTHORIZED) -->
                    <div
                        v-if="canDeleteClip(currentShortClip)"
                        class="flex flex-col items-center space-y-1"
                    >
                        <button
                            @click="deleteClip(currentShortClip.id)"
                            class="w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-red-400 flex items-center justify-center transition cursor-pointer shadow-md"
                            title="Hapus klip ini"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                        <span class="text-[10px] font-mono text-slate-500"
                            >Hapus</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- EMPTY STATE FOR SHORTS FEED -->
        <div
            v-else
            class="p-12 text-center bg-slate-950 rounded-xl border border-slate-800 flex flex-col items-center justify-center space-y-3"
        >
            <div
                class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500"
            >
                <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                </svg>
            </div>
            <div class="space-y-1">
                <h3 class="text-sm font-semibold text-slate-300 font-mono">
                    BELUM ADA KLIP DITEMUKAN
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Belum ada klip momen aksi yang ditandai oleh komunitas.
                </p>
            </div>
        </div>
    </div>

    <!-- MINIMALIST TOAST NOTIFICATION -->
    <div
        :class="[
            'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900/95 backdrop-blur-md border border-slate-800 text-slate-200 px-4 py-2 rounded-full text-xs font-mono shadow-xl transition-all duration-300 pointer-events-none flex items-center gap-2',
            toastVisible
                ? 'opacity-100 translate-y-0'
                : 'opacity-0 translate-y-6',
        ]"
    >
        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
        <span>{{ toastText }}</span>
    </div>

    <!-- MODAL TAMBAH TACCLIP BARU (COMMUNITY MYSQL SUBMISSION) -->
    <Teleport to="body">
        <div
            v-if="isAddModalOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4 overflow-y-auto"
        >
            <div
                class="relative w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-5 text-slate-100 font-sans"
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                        <h3 class="text-base font-bold uppercase font-tactical tracking-wider text-slate-100">
                            Tambah TacClip Momen Aksi Baru
                        </h3>
                    </div>
                    <button
                        @click="closeAddModal"
                        class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition text-xs"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body Form -->
                <div class="space-y-4 text-xs">
                    <!-- URL YouTube -->
                    <div class="space-y-1">
                        <label class="block font-mono font-semibold text-slate-300 uppercase">
                            URL Video YouTube <span class="text-red-400">*</span>
                        </label>
                        <input
                            v-model="newClipForm.youtube_url"
                            type="url"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full rounded-md bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 font-mono focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <!-- Judul Klip -->
                    <div class="space-y-1">
                        <label class="block font-mono font-semibold text-slate-300 uppercase">
                            Judul Momen Aksi <span class="text-red-400">*</span>
                        </label>
                        <input
                            v-model="newClipForm.title"
                            type="text"
                            placeholder="Contoh: Pursuit 10-80 Sektor Downtown Los Santos"
                            class="w-full rounded-md bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <!-- Timestamps (Start & End) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-mono font-semibold text-slate-300 uppercase">
                                Waktu Mulai (IN) <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="newClipForm.start_time"
                                type="text"
                                placeholder="00:00:10"
                                class="w-full rounded-md bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 font-mono text-center focus:border-blue-500 focus:outline-none font-bold"
                            />
                            <span class="text-[10px] text-slate-500 block font-mono">Format: HH:MM:SS atau Detik</span>
                        </div>
                        <div class="space-y-1">
                            <label class="block font-mono font-semibold text-slate-300 uppercase">
                                Waktu Selesai (OUT) <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="newClipForm.end_time"
                                type="text"
                                placeholder="00:01:00"
                                class="w-full rounded-md bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 font-mono text-center focus:border-blue-500 focus:outline-none font-bold"
                            />
                            <span class="text-[10px] text-slate-500 block font-mono">Maksimal 10 menit (600s)</span>
                        </div>
                    </div>

                    <!-- Officer Selection -->
                    <div class="space-y-1">
                        <label class="block font-mono font-semibold text-slate-300 uppercase">
                            Petugas / Officer Terkait (Opsional)
                        </label>
                        <select
                            v-model="newClipForm.officer_id"
                            class="w-full rounded-md bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-blue-500 focus:outline-none cursor-pointer"
                        >
                            <option value="">-- Pilih Petugas / Officer --</option>
                            <option
                                v-for="off in officers"
                                :key="off.id"
                                :value="off.id"
                            >
                                {{ off.department }} • {{ off.officer_name }} ({{ off.callsign }})
                            </option>
                        </select>
                    </div>

                    <!-- Creator Name -->
                    <div class="space-y-1">
                        <label class="block font-mono font-semibold text-slate-300 uppercase">
                            Ditandai Oleh / Nama Pengirim (Opsional)
                        </label>
                        <input
                            v-model="newClipForm.creator_name"
                            type="text"
                            placeholder="Nama panggilan Anda..."
                            class="w-full rounded-md bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-blue-500 focus:outline-none font-mono"
                        />
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2.5 font-mono text-xs">
                    <button
                        @click="closeAddModal"
                        class="px-4 py-2 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitNewClip"
                        :disabled="isSubmittingClip"
                        class="px-5 py-2 rounded-md bg-blue-600 hover:bg-blue-500 text-white font-bold transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                    >
                        <span v-if="isSubmittingClip">MEMPROSES...</span>
                        <span v-else>PUBLIKASIKAN TACCLIP</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
