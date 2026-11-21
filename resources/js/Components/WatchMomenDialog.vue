<script setup>
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Clock, Download, Loader2, Play, Scissors } from "@lucide/vue";
import { computed, ref, watch } from "vue";
import { toast } from "vue-sonner";

import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { formatClipTime, parseClipTime } from "@/Utils/clipTime";

const MAX_SECONDS = 600; // same cap as POST /api/v1/tac-clips
const PRESETS = [30, 60, 120];

const props = defineProps({
    open: { type: Boolean, default: false },
    stream: { type: Object, required: true },
    // Playhead (seconds) captured when the dialog was opened.
    markAt: { type: Number, default: 0 },
    getCurrentTime: { type: Function, required: true },
    seekTo: { type: Function, required: true },
});

const emit = defineEmits(["update:open", "created"]);

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);
// Mirrors the access rule on /momen: Clipper is for the clips role and admins.
const canUseClipper = computed(() => {
    const u = authUser.value;
    if (!u) return false;
    const role = (u.role || "").toLowerCase();
    return role === "clips" || role === "admin" || !!u.is_admin;
});

const title = ref("");
const creatorName = ref("");
const startText = ref("0:00");
const endText = ref("0:30");
const preset = ref(30);
const submitting = ref(false);

const applyPreset = (seconds) => {
    preset.value = seconds;
    const end = Math.max(seconds, props.markAt);
    startText.value = formatClipTime(Math.max(0, end - seconds));
    endText.value = formatClipTime(end);
};

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        title.value = "";
        applyPreset(preset.value);
    },
);

const startSec = computed(() => parseClipTime(startText.value));
const endSec = computed(() => parseClipTime(endText.value));
const duration = computed(() =>
    startSec.value != null && endSec.value != null ? endSec.value - startSec.value : null,
);

const rangeError = computed(() => {
    if (startSec.value == null || endSec.value == null) return "Format waktu: 1:30 atau 1:02:03.";
    if (duration.value <= 0) return "Waktu selesai harus setelah waktu mulai.";
    if (duration.value > MAX_SECONDS) return "Durasi maksimal 10 menit.";
    return "";
});
const canSubmit = computed(() => !rangeError.value && title.value.trim().length > 0 && !submitting.value);

const setStartNow = () => {
    startText.value = formatClipTime(props.getCurrentTime());
};
const setEndNow = () => {
    endText.value = formatClipTime(props.getCurrentTime());
};
const preview = () => {
    if (startSec.value != null) props.seekTo(startSec.value);
};

const submit = async () => {
    if (!canSubmit.value) return;
    submitting.value = true;
    try {
        const res = await axios.post("/api/v1/tac-clips", {
            video_id: props.stream.video_id,
            title: title.value.trim(),
            start_seconds: startSec.value,
            end_seconds: endSec.value,
            officer_id: props.stream.officer?.id || null,
            officer_name: props.stream.officer?.officer_name || null,
            creator_name: authUser.value ? null : creatorName.value.trim() || null,
        });
        toast.success("Momen tersimpan dan bisa dilihat komunitas.");
        if (res.data?.clip) emit("created", res.data.clip);
        emit("update:open", false);
    } catch (err) {
        const errors = err.response?.data?.errors;
        const first = errors ? Object.values(errors)[0]?.[0] : null;
        toast.error(first || err.response?.data?.message || "Gagal menyimpan momen.");
    } finally {
        submitting.value = false;
    }
};

const clipperUrl = computed(() => {
    if (rangeError.value) return null;
    const params = new URLSearchParams({
        url: `https://www.youtube.com/watch?v=${props.stream.video_id}`,
        start: String(startSec.value),
        end: String(endSec.value),
        title: title.value.trim() || props.stream.title || "",
    });
    return `/clip-studio?${params.toString()}`;
});
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Scissors class="size-4" /> {{ $t('watch_clips.dialog_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('watch_clips.dialog_desc') }}</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <label for="clip-title" class="text-sm font-medium text-foreground">{{ $t('watch_clips.title_label') }}</label>
                    <Input
                        id="clip-title"
                        v-model="title"
                        maxlength="255"
                        :placeholder="$t('watch_clips.title_placeholder')"
                        autofocus
                    />
                </div>

                <div class="space-y-1.5">
                    <span class="text-sm font-medium text-foreground">{{ $t('watch_clips.length_label') }}</span>
                    <div class="flex gap-1.5">
                        <Button
                            v-for="p in PRESETS"
                            :key="p"
                            type="button"
                            size="sm"
                            :variant="preset === p ? 'default' : 'secondary'"
                            class="flex-1"
                            @click="applyPreset(p)"
                        >
                            {{ p < 60 ? `${p} dtk` : `${p / 60} mnt` }}
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ $t('watch_clips.preset_hint', { time: formatClipTime(markAt) }) }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label for="clip-start" class="text-sm font-medium text-foreground">{{ $t('watch_clips.start') }}</label>
                        <div class="flex gap-1">
                            <Input id="clip-start" v-model="startText" class="font-mono" inputmode="numeric" />
                            <Button type="button" size="icon" variant="outline" :title="$t('watch_clips.use_now')" :aria-label="$t('watch_clips.use_now')" @click="setStartNow">
                                <Clock />
                            </Button>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label for="clip-end" class="text-sm font-medium text-foreground">{{ $t('watch_clips.end') }}</label>
                        <div class="flex gap-1">
                            <Input id="clip-end" v-model="endText" class="font-mono" inputmode="numeric" />
                            <Button type="button" size="icon" variant="outline" :title="$t('watch_clips.use_now')" :aria-label="$t('watch_clips.use_now')" @click="setEndNow">
                                <Clock />
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 text-xs">
                    <span :class="rangeError ? 'text-destructive' : 'text-muted-foreground'">
                        {{ rangeError || $t('watch_clips.duration', { time: formatClipTime(duration) }) }}
                    </span>
                    <Button type="button" variant="link" size="sm" class="h-auto p-0" :disabled="startSec == null" @click="preview">
                        <Play /> {{ $t('watch_clips.preview') }}
                    </Button>
                </div>

                <div v-if="!authUser" class="space-y-1.5">
                    <label for="clip-creator" class="text-sm font-medium text-foreground">{{ $t('watch_clips.creator_label') }}</label>
                    <Input id="clip-creator" v-model="creatorName" maxlength="255" :placeholder="$t('watch_clips.creator_placeholder')" />
                </div>

                <p v-if="stream.is_live" class="rounded-md bg-muted/60 p-2 text-xs text-muted-foreground">
                    {{ $t('watch_clips.live_note') }}
                </p>

                <DialogFooter class="gap-2 sm:justify-between">
                    <Button
                        v-if="canUseClipper"
                        as-child
                        type="button"
                        variant="outline"
                        :disabled="!clipperUrl || stream.is_live"
                        :class="(!clipperUrl || stream.is_live) && 'pointer-events-none opacity-50'"
                        :title="stream.is_live ? $t('watch_clips.clipper_after_live') : ''"
                    >
                        <a :href="clipperUrl || '#'" target="_blank" rel="noopener" :aria-disabled="!clipperUrl || stream.is_live">
                            <Download /> {{ $t('watch_clips.download') }}
                        </a>
                    </Button>
                    <Button type="submit" :disabled="!canSubmit">
                        <Loader2 v-if="submitting" class="motion-safe:animate-spin" />
                        <Scissors v-else />
                        {{ $t('watch_clips.save') }}
                    </Button>
                </DialogFooter>
                <p v-if="canUseClipper && stream.is_live" class="text-right text-xs text-muted-foreground">
                    {{ $t('watch_clips.clipper_after_live') }}
                </p>
            </form>
        </DialogContent>
    </Dialog>
</template>
