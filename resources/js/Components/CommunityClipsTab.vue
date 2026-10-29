<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "vue-sonner";
import {
    Plus,
    Trash2,
    Play,
    RefreshCw,
    Video,
    ExternalLink,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import { EmptyState } from "@/Components/tactical";

const props = defineProps({
    officer: {
        type: Object,
        default: () => ({}),
    },
    stream: {
        type: Object,
        default: () => ({}),
    },
    customClips: {
        type: Array,
        default: null,
    },
});

const emit = defineEmits(["open-create-clip", "play-clip", "delete-clip"]);

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const defaultClips = ref([]);

const fetchCommunityClips = async () => {
    try {
        const response = await axios.get("/api/v1/tac-clips");
        if (response.data && response.data.data) {
            defaultClips.value = response.data.data;
        }
    } catch (e) {}
};

const clips = computed(() => {
    return props.customClips !== null ? props.customClips : defaultClips.value;
});

const clipScopeFilter = ref("officer");
const searchQuery = ref("");
const visibleCount = ref(12);

const loadMore = () => {
    visibleCount.value += 12;
};

watch([clipScopeFilter, searchQuery], () => {
    visibleCount.value = 12;
});

const currentVideoId = computed(() => {
    return (props.stream?.video_id || props.officer?.video_id || "")
        .toString()
        .trim();
});

const currentOfficerId = computed(() => {
    return (
        props.officer?.id ||
        props.officer?.officer_id ||
        props.stream?.officer?.id ||
        props.stream?.officer_id ||
        ""
    )
        .toString()
        .trim();
});

const currentOfficerName = computed(() => {
    return (
        props.officer?.officer_name ||
        props.officer?.name ||
        props.stream?.officer?.officer_name ||
        props.stream?.officer?.name ||
        ""
    )
        .toString()
        .trim()
        .toLowerCase();
});

const currentOfficerHandle = computed(() => {
    const h = (props.officer?.handle || props.stream?.officer?.handle || "")
        .toString()
        .trim()
        .toLowerCase();
    return h.startsWith("@") ? h.slice(1) : h;
});

const isClipMatchingOfficer = (c) => {
    if (
        currentVideoId.value &&
        c.video_id &&
        String(c.video_id).trim() === currentVideoId.value
    ) {
        return true;
    }
    if (
        currentOfficerId.value &&
        c.officer_id &&
        String(c.officer_id).trim() === currentOfficerId.value
    ) {
        return true;
    }
    if (
        currentOfficerName.value &&
        c.officer_name &&
        String(c.officer_name).trim().toLowerCase() === currentOfficerName.value
    ) {
        return true;
    }
    if (
        currentOfficerHandle.value &&
        c.officer_handle &&
        String(c.officer_handle).trim().toLowerCase().replace(/^@/, "") ===
            currentOfficerHandle.value
    ) {
        return true;
    }
    if (!c.video_id && !c.officer_id) {
        return true;
    }
    return false;
};

const isClipMatchingStream = (c) => {
    return !!(
        currentVideoId.value &&
        c.video_id &&
        String(c.video_id).trim() === currentVideoId.value
    );
};

const streamClipsCount = computed(() => {
    if (!currentVideoId.value) return 0;
    return clips.value.filter(isClipMatchingStream).length;
});

const officerClipsCount = computed(() => {
    return clips.value.filter(isClipMatchingOfficer).length;
});

const filteredClips = computed(() => {
    let list = clips.value;

    if (clipScopeFilter.value === "stream") {
        list = list.filter(isClipMatchingStream);
    } else {
        list = list.filter(isClipMatchingOfficer);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (c) =>
                (c.title || "").toLowerCase().includes(q) ||
                (c.creator_name || "").toLowerCase().includes(q),
        );
    }

    return list;
});

const paginatedClips = computed(() => {
    return filteredClips.value.slice(0, visibleCount.value);
});

const hasMoreClips = computed(() => {
    return visibleCount.value < filteredClips.value.length;
});

const sentinelRef = ref(null);
let observer = null;

onMounted(() => {
    fetchCommunityClips();
    if ("IntersectionObserver" in window) {
        observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && hasMoreClips.value) {
                    loadMore();
                }
            },
            { rootMargin: "250px" },
        );
        if (sentinelRef.value) {
            observer.observe(sentinelRef.value);
        }
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});

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

const canDeleteClip = (clip) => {
    if (!authUser.value) return false;
    const isUserAdmin =
        authUser.value.role === "admin" || !!authUser.value.is_admin;
    if (isUserAdmin) return true;
    if (clip.user_id && clip.user_id === authUser.value.id) return true;
    if (
        clip.creator_name &&
        authUser.value.name &&
        clip.creator_name.trim().toLowerCase() ===
            authUser.value.name.trim().toLowerCase()
    ) {
        return true;
    }
    return false;
};

const pendingDeleteClipId = ref(null);

const deleteClip = (clipId) => {
    const clipTarget = clips.value.find((c) => c.id === clipId);
    if (clipTarget && !canDeleteClip(clipTarget)) {
        toast.warning("Anda tidak memiliki izin untuk menghapus klip ini!");
        return;
    }
    pendingDeleteClipId.value = clipId;
};

const confirmDeleteClip = () => {
    const clipId = pendingDeleteClipId.value;
    pendingDeleteClipId.value = null;
    if (!clipId) return;

    emit("delete-clip", clipId);
    if (props.customClips === null) {
        defaultClips.value = defaultClips.value.filter(
            (c) => c.id !== clipId,
        );
    }
};

const activePlayingClip = ref(null);

const playClipModal = (clip) => {
    activePlayingClip.value = clip;
    emit("play-clip", clip);
};
</script>

<template>
    <div class="space-y-4 font-sans">
        <!-- HEADER & TOOLBAR -->
        <div
            class="flex items-center justify-between flex-wrap gap-3 border-b border-border pb-3"
        >
            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                <h3
                    class="text-xs font-medium text-foreground uppercase tracking-tight font-mono flex items-center gap-2"
                >
                    <span>Kumpulan Klip Aksi</span>
                    <Badge variant="outline" class="font-mono text-xs">
                        {{ filteredClips.length }} Klip
                    </Badge>
                </h3>

                <!-- FILTER SCOPE TOGGLE BUTTONS -->
                <div
                    class="flex items-center bg-background border border-border rounded-md p-0.5 font-mono text-xs gap-0.5"
                >
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            clipScopeFilter === 'officer' ? 'default' : 'ghost'
                        "
                        @click="clipScopeFilter = 'officer'"
                        class="h-7 px-2.5 text-xs gap-1"
                        title="Tampilkan semua klip dari officer ini (seluruh riwayat video)"
                    >
                        <span>Semua Klip Officer</span>
                        <span class="text-xs opacity-80"
                            >({{ officerClipsCount }})</span
                        >
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            clipScopeFilter === 'stream' ? 'default' : 'ghost'
                        "
                        @click="clipScopeFilter = 'stream'"
                        class="h-7 px-2.5 text-xs gap-1"
                        title="Hanya tampilkan klip dari siaran video yang sedang diputar"
                    >
                        <span>Siaran Ini</span>
                        <span class="text-xs opacity-80"
                            >({{ streamClipsCount }})</span
                        >
                    </Button>
                </div>
            </div>

            <!-- SEARCH INPUT & TANDAI AKSI / CREATE CLIP BUTTON -->
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <Input
                    v-model="searchQuery"
                    type="text"
                    maxlength="50"
                    placeholder="Cari klip atau pembuat..."
                    class="h-8 text-xs w-full sm:w-44"
                />

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="emit('open-create-clip')"
                    class="h-8 px-2.5 text-xs font-mono gap-1.5 shrink-0"
                >
                    <Plus class="w-3.5 h-3.5" />
                    <span>Tambah Klip Baru</span>
                </Button>
            </div>
        </div>

        <!-- ACTION CLIPS GRID -->
        <div v-if="filteredClips.length > 0" class="space-y-4">
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4"
            >
                <Card
                    v-for="clip in paginatedClips"
                    :key="clip.id"
                    class="hover:border-primary/40 overflow-hidden transition-colors flex flex-col group"
                >
                    <!-- THUMBNAIL AREA -->
                    <div
                        class="relative aspect-video bg-background overflow-hidden cursor-pointer"
                        @click="playClipModal(clip)"
                    >
                        <img
                            :src="`https://i.ytimg.com/vi/${clip.video_id}/hqdefault.jpg`"
                            :alt="clip.title"
                            class="w-full h-full object-cover opacity-75 group-hover:opacity-100 transition-opacity"
                        />

                        <!-- DELETE CLIP BUTTON -->
                        <div
                            v-if="canDeleteClip(clip)"
                            class="absolute top-2 right-2 z-20"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click.stop="deleteClip(clip.id)"
                                class="h-6 px-2 text-xs font-mono text-muted-foreground hover:text-destructive bg-background/90 gap-1"
                                title="Hapus Klip"
                            >
                                <Trash2 class="w-3 h-3" />
                                <span>Hapus</span>
                            </Button>
                        </div>

                        <!-- DURATION OVERLAY -->
                        <div
                            class="absolute bottom-2 right-2 bg-background/90 px-2 py-0.5 rounded-sm font-mono text-xs text-foreground border border-border z-10"
                        >
                            {{ formatTime(clip.start_seconds) }} -
                            {{ formatTime(clip.end_seconds) }}
                        </div>

                        <!-- PLAY BUTTON OVERLAY -->
                        <div
                            class="absolute inset-0 bg-background/30 group-hover:bg-background/10 transition-colors flex items-center justify-center"
                        >
                            <div
                                class="w-9 h-9 rounded-sm bg-card/90 hover:bg-muted border border-border text-foreground flex items-center justify-center"
                            >
                                <Play class="w-4 h-4 fill-current ml-0.5" />
                            </div>
                        </div>
                    </div>

                    <!-- CONTENT DETAILS -->
                    <div
                        class="p-3.5 flex flex-col justify-between flex-1 space-y-2.5"
                    >
                        <div>
                            <h4
                                class="text-xs font-medium text-foreground group-hover:text-primary transition-colors line-clamp-2 leading-relaxed cursor-pointer wrap-break-word"
                                @click="playClipModal(clip)"
                                :title="clip.title"
                            >
                                {{ clip.title }}
                            </h4>
                        </div>

                        <!-- CLEAN FOOTER: CREATOR & CREATED AT -->
                        <div
                            class="pt-2 border-t border-border flex items-center justify-between text-xs font-mono text-muted-foreground"
                        >
                            <span
                                class="truncate max-w-[130px] sm:max-w-[160px]"
                                :title="`Ditandai oleh: ${clip.creator_name}`"
                            >
                                Ditandai oleh:
                                <strong class="text-foreground font-semibold">{{
                                    clip.creator_name
                                }}</strong>
                            </span>
                            <span class="shrink-0 ml-1">{{
                                clip.created_at
                            }}</span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- LAZY LOADING SENTINEL & LOAD MORE BUTTON -->
            <div
                v-if="hasMoreClips"
                class="pt-4 flex flex-col items-center justify-center space-y-2"
            >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="loadMore"
                    class="px-5 text-xs font-mono gap-2"
                >
                    <RefreshCw
                        class="w-3.5 h-3.5 text-primary motion-safe:animate-spin"
                    />
                    <span
                        >Muat Lebih Banyak Klip (+12 — Tersisa
                        {{ filteredClips.length - visibleCount }})</span
                    >
                </Button>
                <div ref="sentinelRef" class="h-4 w-full"></div>
            </div>
        </div>

        <!-- RICH EMPTY STATE -->
        <EmptyState
            v-else
            :icon="Video"
            title="BELUM ADA KLIP MOMEN AKSI"
            :description="
                searchQuery
                    ? 'Tidak ada klip yang cocok dengan kata kunci pencarian Anda.'
                    : clipScopeFilter === 'stream'
                      ? 'Belum ada klip momen aksi yang ditandai khusus untuk siaran ini.'
                      : 'Belum ada klip momen aksi yang ditandai untuk perwira ini.'
            "
            action-label="Tandai Momen Aksi Pertama"
            @action="emit('open-create-clip')"
        />

        <!-- MODAL EMBED PLAYER -->
        <Dialog
            :open="!!activePlayingClip"
            @update:open="
                (val) => {
                    if (!val) activePlayingClip = null;
                }
            "
        >
            <DialogContent v-if="activePlayingClip" class="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 font-mono text-xs truncate"
                    >
                        <Badge variant="outline" class="font-mono text-xs">
                            CLIP
                        </Badge>
                        <span class="truncate">{{
                            activePlayingClip.title
                        }}</span>
                    </DialogTitle>
                </DialogHeader>

                <div
                    class="relative aspect-video w-full bg-background rounded-lg overflow-hidden border border-border"
                >
                    <iframe
                        :src="`https://www.youtube.com/embed/${activePlayingClip.video_id}?autoplay=1&start=${activePlayingClip.start_seconds}&end=${activePlayingClip.end_seconds}&rel=0`"
                        class="w-full h-full border-0"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>

                <div
                    class="flex items-center justify-between text-xs font-mono text-muted-foreground pt-1"
                >
                    <span
                        >Momen Aksi:
                        {{ formatTime(activePlayingClip.start_seconds) }} -
                        {{ formatTime(activePlayingClip.end_seconds) }}</span
                    >
                    <a
                        :href="`https://www.youtube.com/watch?v=${activePlayingClip.video_id}&t=${activePlayingClip.start_seconds}s`"
                        target="_blank"
                        class="text-primary hover:underline inline-flex items-center gap-1"
                    >
                        <span>Buka di YouTube</span>
                        <ExternalLink class="w-3 h-3" />
                    </a>
                </div>
            </DialogContent>
        </Dialog>

        <!-- KONFIRMASI HAPUS KLIP -->
        <AlertDialog
            :open="pendingDeleteClipId !== null"
            @update:open="
                (val) => {
                    if (!val) pendingDeleteClipId = null;
                }
            "
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Klip Momen Aksi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus klip momen aksi ini?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="pendingDeleteClipId = null">
                        Batal
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="confirmDeleteClip"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
