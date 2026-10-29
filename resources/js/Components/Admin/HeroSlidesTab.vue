<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Switch } from "@/Components/ui/switch";
import { Card, CardContent } from "@/Components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { Skeleton } from "@/Components/ui/skeleton";
import { EmptyState, SectionHeader } from "@/Components/tactical";
import {
    Copy,
    GripVertical,
    Images,
    Pencil,
    Plus,
    Trash2,
} from "@lucide/vue";
import HeroSlideForm from "./HeroSlideForm.vue";

const emit = defineEmits(["toast"]);

const slides = ref([]);
const isLoading = ref(false);
const showFormDialog = ref(false);
const isEditMode = ref(false);
const activeSlide = ref(null);
const slideToDelete = ref(null);
const showDeleteDialog = ref(false);
const draggedIndex = ref(null);

function notify(message, type = "success") {
    if (type === "error") {
        toast.error(message);
    } else {
        toast.success(message);
    }
    emit("toast", message, type);
}

async function fetchSlides() {
    isLoading.value = true;
    try {
        const res = await axios.get("/api/v1/admin/hero-slides");
        slides.value = res.data.slides || [];
    } catch (err) {
        notify(err.response?.data?.message || "Gagal memuat daftar slide hero.", "error");
    } finally {
        isLoading.value = false;
    }
}

function getScheduleStatus(slide) {
    if (!slide.is_active) {
        return { label: "Nonaktif", variant: "secondary" };
    }
    const now = Date.now();
    if (slide.starts_at && new Date(slide.starts_at).getTime() > now) {
        return { label: "Terjadwal", variant: "warning" };
    }
    if (slide.ends_at && new Date(slide.ends_at).getTime() <= now) {
        return { label: "Berakhir", variant: "secondary" };
    }
    return { label: "Tayang", variant: "success" };
}

function openCreateDialog() {
    isEditMode.value = false;
    activeSlide.value = null;
    showFormDialog.value = true;
}

function openEditDialog(slide) {
    isEditMode.value = true;
    activeSlide.value = { ...slide };
    showFormDialog.value = true;
}

function openDuplicateDialog(slide) {
    isEditMode.value = false;
    activeSlide.value = {
        ...slide,
        id: null,
        title: slide.title ? `${slide.title} (Salinan)` : "",
        is_active: false,
    };
    showFormDialog.value = true;
}

function confirmDelete(slide) {
    slideToDelete.value = slide;
    showDeleteDialog.value = true;
}

async function executeDelete() {
    if (!slideToDelete.value) return;
    const id = slideToDelete.value.id;
    try {
        const res = await axios.delete(`/api/v1/admin/hero-slides/${id}`);
        notify(res.data.message || "Slide hero dihapus.");
        showDeleteDialog.value = false;
        slideToDelete.value = null;
        fetchSlides();
    } catch (err) {
        notify(err.response?.data?.message || "Gagal menghapus slide hero.", "error");
    }
}

async function handleToggle(slide) {
    try {
        const res = await axios.patch(`/api/v1/admin/hero-slides/${slide.id}/toggle`);
        slide.is_active = res.data.slide?.is_active ?? !slide.is_active;
        notify(res.data.message || "Status slide diperbarui.");
    } catch (err) {
        notify(err.response?.data?.message || "Gagal mengubah status slide.", "error");
        fetchSlides();
    }
}

function onFormSaved(data) {
    showFormDialog.value = false;
    notify(data?.message || "Slide hero berhasil disimpan.");
    fetchSlides();
}

function onDragStart(idx) {
    draggedIndex.value = idx;
}

async function onDrop(targetIdx) {
    const fromIdx = draggedIndex.value;
    draggedIndex.value = null;
    if (fromIdx === null || fromIdx === targetIdx) return;

    const reordered = [...slides.value];
    const [moved] = reordered.splice(fromIdx, 1);
    reordered.splice(targetIdx, 0, moved);
    slides.value = reordered;

    try {
        const orderedIds = reordered.map((s) => s.id);
        const res = await axios.post("/api/v1/admin/hero-slides/reorder", {
            ordered_ids: orderedIds,
        });
        notify(res.data.message || "Urutan slide diperbarui.");
    } catch (err) {
        notify(err.response?.data?.message || "Gagal menyimpan urutan slide.", "error");
        fetchSlides();
    }
}

onMounted(() => {
    fetchSlides();
});
</script>

<template>
    <div class="space-y-6">
        <Card class="py-4">
            <CardContent class="px-5">
                <SectionHeader
                    title="Manajemen Hero Slider Dashboard"
                    description="Kelola sorotan fitur, event komunitas, dan Live Spotlight di halaman utama. Maksimal 5 slide aktif ditayangkan sesuai urutan."
                >
                    <template #actions>
                        <Button size="sm" @click="openCreateDialog">
                            <Plus class="size-4" />
                            Tambah Slide Hero
                        </Button>
                    </template>
                </SectionHeader>
            </CardContent>
        </Card>

        <!-- Loading State -->
        <Card v-if="isLoading" class="py-6">
            <CardContent class="space-y-3 px-5">
                <Skeleton class="h-10 w-full" />
                <Skeleton class="h-14 w-full" />
                <Skeleton class="h-14 w-full" />
            </CardContent>
        </Card>

        <!-- Empty State -->
        <EmptyState
            v-else-if="slides.length === 0"
            :icon="Images"
            title="Belum Ada Slide Hero"
            description="Saat tidak ada slide aktif, halaman utama akan menampilkan hero statis standar."
        >
            <template #action>
                <Button size="sm" @click="openCreateDialog">
                    <Plus class="size-4" />
                    Buat Slide Pertama
                </Button>
            </template>
        </EmptyState>

        <!-- Slides Table -->
        <Card v-else class="py-0 overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10"></TableHead>
                        <TableHead>Slide</TableHead>
                        <TableHead>Tipe & Audiens</TableHead>
                        <TableHead>Status Jadwal</TableHead>
                        <TableHead class="text-right">7 Hari (Imp / Klik)</TableHead>
                        <TableHead class="text-center">Aktif</TableHead>
                        <TableHead class="text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="(slide, idx) in slides"
                        :key="slide.id"
                        draggable="true"
                        class="cursor-move"
                        @dragstart="onDragStart(idx)"
                        @dragover.prevent
                        @drop="onDrop(idx)"
                    >
                        <TableCell class="text-muted-foreground">
                            <GripVertical class="size-4" aria-hidden="true" />
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-12 w-20 shrink-0 overflow-hidden rounded-md border border-border bg-muted/40 flex items-center justify-center"
                                >
                                    <img
                                        v-if="slide.image_desktop_url"
                                        :src="slide.image_desktop_url"
                                        :alt="slide.image_alt || slide.title || 'Slide'"
                                        class="size-full object-cover"
                                    />
                                    <span v-else class="font-mono text-xs text-muted-foreground">
                                        AUTO
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-foreground truncate max-w-xs">
                                        {{
                                            slide.slide_type === "live_spotlight"
                                                ? "Live Spotlight (Otomatis Stream Terbanyak)"
                                                : slide.title
                                        }}
                                    </p>
                                    <p v-if="slide.subtitle" class="text-xs text-muted-foreground truncate max-w-xs">
                                        {{ slide.subtitle }}
                                    </p>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Badge variant="outline" class="font-mono uppercase">
                                    {{ slide.slide_type }}
                                </Badge>
                                <Badge variant="secondary" class="font-mono uppercase">
                                    {{ slide.audience }}
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="getScheduleStatus(slide).variant">
                                {{ getScheduleStatus(slide).label }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right font-mono text-xs tabular-nums">
                            {{ slide.impressions_7d || 0 }} / {{ slide.clicks_7d || 0 }}
                        </TableCell>
                        <TableCell class="text-center">
                            <Switch
                                :model-value="!!slide.is_active"
                                :aria-label="`Toggle aktif ${slide.title || slide.slide_type}`"
                                @update:model-value="handleToggle(slide)"
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Ubah slide"
                                    @click="openEditDialog(slide)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Duplikat slide"
                                    @click="openDuplicateDialog(slide)"
                                >
                                    <Copy class="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    aria-label="Hapus slide"
                                    @click="confirmDelete(slide)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>

        <!-- Create / Edit Form Dialog -->
        <Dialog v-model:open="showFormDialog">
            <DialogContent class="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {{ isEditMode ? "Ubah Slide Hero" : "Tambah Slide Hero Baru" }}
                    </DialogTitle>
                    <DialogDescription>
                        Atur konten, gambar, jadwal tayang, serta pratinjau tampilan desktop dan mobile.
                    </DialogDescription>
                </DialogHeader>
                <HeroSlideForm
                    :initial-slide="activeSlide"
                    :is-edit="isEditMode"
                    @saved="onFormSaved"
                    @cancel="showFormDialog = false"
                    @toast="notify"
                />
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation AlertDialog -->
        <AlertDialog v-model:open="showDeleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus slide hero ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Slide "{{ slideToDelete?.title || slideToDelete?.slide_type }}" beserta file gambarnya akan dihapus permanen.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="executeDelete"
                    >
                        Hapus Permanen
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
