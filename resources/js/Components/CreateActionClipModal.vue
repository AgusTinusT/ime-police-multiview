<script setup>
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Scissors, ArrowRight } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    stream: { type: Object, default: () => ({}) },
    currentTime: { type: Number, default: 0 },
});

const emit = defineEmits(["close", "submit"]);

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const title = ref("");
const startTime = ref("00:00");
const endTime = ref("00:30");
const authorName = ref(authUser.value?.name || "");

watch(
    () => authUser.value?.name,
    (newName) => {
        if (newName && !authorName.value) {
            authorName.value = newName;
        }
    },
    { immediate: true },
);

const durationPreset = ref(30);

const formatTimeHelper = (seconds) => {
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

const parseTimestamp = (str) => {
    if (!str) return 0;
    const parts = String(str).trim().split(":");
    if (parts.length === 3) {
        return (
            (parseInt(parts[0], 10) || 0) * 3600 +
            (parseInt(parts[1], 10) || 0) * 60 +
            (parseInt(parts[2], 10) || 0)
        );
    }
    if (parts.length === 2) {
        return (
            (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0)
        );
    }
    return parseInt(str, 10) || 0;
};

const setDurationPreset = (seconds) => {
    durationPreset.value = seconds;
    const totalSecs = parseTimestamp(startTime.value);
    const endSecs = totalSecs + seconds;
    endTime.value = formatTimeHelper(endSecs);
};

const openStudioClipper = () => {
    if (props.stream?.video_id) {
        const targetUrl = `https://www.youtube.com/watch?v=${props.stream.video_id}`;
        router.visit(`/clip-studio?url=${encodeURIComponent(targetUrl)}`);
        emit("close");
    }
};

const handleSubmit = () => {
    if (!title.value.trim()) {
        toast.warning("Harap masukkan judul klip momen aksi!");
        return;
    }

    emit("submit", {
        title: title.value,
        startTime: startTime.value,
        endTime: endTime.value,
        authorName:
            authorName.value.trim() ||
            authUser.value?.name ||
            "Penonton Komunitas",
        user_id: authUser.value?.id || null,
    });

    title.value = "";
    authorName.value = authUser.value?.name || "";
    emit("close");
};
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (val) => {
                if (!val) emit('close');
            }
        "
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 font-mono text-sm">
                    <Scissors class="w-4 h-4 text-destructive" />
                    <span>Tandai Momen Aksi Baru</span>
                </DialogTitle>
                <DialogDescription class="font-mono text-xs">
                    Pratinjau Video &amp; Bookmark Klip Siaran
                </DialogDescription>
            </DialogHeader>

            <!-- EMBEDDED MINI PREVIEW PLAYER -->
            <div v-if="stream?.video_id" class="space-y-2">
                <div
                    class="relative w-full aspect-video bg-background rounded-lg overflow-hidden border border-border"
                >
                    <iframe
                        :src="`https://www.youtube.com/embed/${stream.video_id}?autoplay=1&controls=1&rel=0`"
                        class="w-full h-full border-0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
                <p
                    class="text-xs text-muted-foreground font-mono italic text-center"
                >
                    Gunakan pemutar video di atas untuk melihat &amp; memastikan
                    stempel waktu klip secara langsung.
                </p>
            </div>

            <!-- FORM BODY -->
            <div class="space-y-3.5 font-mono text-xs">
                <div>
                    <label class="block text-foreground font-medium mb-1"
                        >Judul Momen Aksi / Klip *</label
                    >
                    <Input
                        v-model="title"
                        type="text"
                        placeholder="Misal: PIT Maneuver di Highway 68..."
                        class="h-9 text-xs"
                    />
                </div>

                <!-- PRESET DURATION BUTTONS -->
                <div>
                    <label class="block text-foreground font-medium mb-1"
                        >Durasi Klip Cepat</label
                    >
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                durationPreset === 30 ? 'secondary' : 'outline'
                            "
                            @click="setDurationPreset(30)"
                            class="h-7 px-2.5 text-xs font-mono"
                        >
                            30 Detik
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                durationPreset === 60 ? 'secondary' : 'outline'
                            "
                            @click="setDurationPreset(60)"
                            class="h-7 px-2.5 text-xs font-mono"
                        >
                            60 Detik
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                durationPreset === 90 ? 'secondary' : 'outline'
                            "
                            @click="setDurationPreset(90)"
                            class="h-7 px-2.5 text-xs font-mono"
                        >
                            90 Detik
                        </Button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-foreground font-medium mb-1"
                            >Waktu Mulai (HH:MM:SS)</label
                        >
                        <Input
                            v-model="startTime"
                            type="text"
                            placeholder="00:00:00"
                            class="h-9 text-xs text-center font-semibold"
                        />
                    </div>
                    <div>
                        <label class="block text-foreground font-medium mb-1"
                            >Waktu Selesai (HH:MM:SS)</label
                        >
                        <Input
                            v-model="endTime"
                            type="text"
                            placeholder="00:00:30"
                            class="h-9 text-xs text-center font-semibold"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-foreground font-medium mb-1"
                        >Nama Pembuat Klip (Opsional)</label
                    >
                    <Input
                        v-model="authorName"
                        type="text"
                        placeholder="Panggilan Anda"
                        class="h-9 text-xs"
                    />
                </div>
            </div>

            <!-- ALTERNATIVE METHOD: STUDIO CLIPPER LINK -->
            <div class="pt-2 border-t border-border flex items-center justify-between">
                <button
                    type="button"
                    @click="openStudioClipper"
                    class="text-xs font-mono text-primary hover:underline flex items-center gap-1 cursor-pointer"
                >
                    <span>Buka Pembuat Klip Pro (Clip Studio)</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </button>
            </div>

            <!-- MODAL FOOTER -->
            <DialogFooter class="pt-2 border-t border-border">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="emit('close')"
                    class="text-xs font-mono"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="handleSubmit"
                    class="text-xs font-mono font-semibold"
                >
                    Simpan Klip
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
