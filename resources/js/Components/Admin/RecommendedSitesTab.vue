<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import {
    ExternalLink,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Trash2,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Switch } from "@/Components/ui/switch";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Card } from "@/Components/ui/card";
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
    DialogFooter,
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
import { SectionHeader } from "@/Components/tactical";
import RecommendedSiteCard from "@/Components/Recommended/RecommendedSiteCard.vue";

const emit = defineEmits(["toast"]);

const sites = ref([]);
const searchQuery = ref("");
const isLoading = ref(false);
const isSaving = ref(false);
const togglingId = ref(null);

// Form Dialog state
const showDialog = ref(false);
const editingSite = ref(null);
const formErrors = ref({});
const formData = ref({
    name: "",
    badge: "",
    poster_name: "",
    poster_subtitle: "",
    poster_image_url: "",
    background_image_url: "",
    url: "",
    cta_text: "Kunjungi website",
    description: "",
    chips_text: "",
    is_featured: false,
    is_active: true,
    sort_order: 0,
});

// Delete Alert Dialog state
const showDeleteDialog = ref(false);
const deletingSite = ref(null);
const isDeleting = ref(false);

const fetchSites = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get("/api/v1/admin/recommended-sites");
        sites.value = res.data?.data || [];
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal memuat rekomendasi website.";
        toast.error(msg);
        emit("toast", msg, "error");
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchSites();
});

const filteredSites = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return sites.value;
    return sites.value.filter(
        (s) =>
            s.name.toLowerCase().includes(q) ||
            s.poster_name.toLowerCase().includes(q) ||
            s.url.toLowerCase().includes(q) ||
            (s.badge && s.badge.toLowerCase().includes(q))
    );
});

const openAddDialog = () => {
    editingSite.value = null;
    formErrors.value = {};
    formData.value = {
        name: "",
        badge: "",
        poster_name: "",
        poster_subtitle: "",
        poster_image_url: "",
        background_image_url: "",
        url: "",
        cta_text: "Kunjungi website",
        description: "",
        chips_text: "",
        is_featured: false,
        is_active: true,
        sort_order: sites.value.length,
    };
    showDialog.value = true;
};

const openEditDialog = (site) => {
    editingSite.value = site;
    formErrors.value = {};
    formData.value = {
        name: site.name || "",
        badge: site.badge || "",
        poster_name: site.poster_name || "",
        poster_subtitle: site.poster_subtitle || "",
        poster_image_url: site.poster_image_url || "",
        background_image_url: site.background_image_url || "",
        url: site.url || "",
        cta_text: site.cta_text || "Kunjungi website",
        description: site.description || "",
        chips_text: Array.isArray(site.chips) ? site.chips.join(", ") : "",
        is_featured: Boolean(site.is_featured),
        is_active: Boolean(site.is_active),
        sort_order: site.sort_order ?? 0,
    };
    showDialog.value = true;
};

const previewSiteData = computed(() => {
    const chips = formData.value.chips_text
        ? formData.value.chips_text
              .split(",")
              .map((c) => c.trim())
              .filter(Boolean)
        : [];

    return {
        name: formData.value.name || "NAMA WEBSITE",
        badge: formData.value.badge || "",
        poster_name: formData.value.poster_name || "NAMA POSTER",
        poster_subtitle: formData.value.poster_subtitle || "",
        poster_image_url: formData.value.poster_image_url || "/images/recommended/lspd-sprite.webp",
        background_image_url: formData.value.background_image_url || "",
        url: formData.value.url || "#",
        cta_text: formData.value.cta_text || "Kunjungi website",
        description: formData.value.description || "Deskripsi website yang akan ditampilkan pada kartu rekomendasi di Dashboard.",
        chips,
        is_featured: formData.value.is_featured,
        is_active: formData.value.is_active,
    };
});

const saveSite = async () => {
    formErrors.value = {};
    isSaving.value = true;

    const chips = formData.value.chips_text
        ? formData.value.chips_text
              .split(",")
              .map((c) => c.trim())
              .filter(Boolean)
        : [];

    const payload = {
        name: formData.value.name,
        badge: formData.value.badge || null,
        poster_name: formData.value.poster_name,
        poster_subtitle: formData.value.poster_subtitle || null,
        poster_image_url: formData.value.poster_image_url,
        background_image_url: formData.value.background_image_url || null,
        url: formData.value.url,
        cta_text: formData.value.cta_text || "Kunjungi website",
        description: formData.value.description,
        chips: chips.length ? chips : null,
        is_featured: formData.value.is_featured,
        is_active: formData.value.is_active,
        sort_order: Number(formData.value.sort_order) || 0,
    };

    try {
        if (editingSite.value) {
            const res = await axios.put(`/api/v1/admin/recommended-sites/${editingSite.value.id}`, payload);
            const updated = res.data?.data;
            const idx = sites.value.findIndex((s) => s.id === updated.id);
            if (idx !== -1) {
                sites.value[idx] = updated;
            }
            toast.success(res.data?.message || "Rekomendasi berhasil diperbarui.");
        } else {
            const res = await axios.post("/api/v1/admin/recommended-sites", payload);
            sites.value.push(res.data?.data);
            toast.success(res.data?.message || "Rekomendasi berhasil ditambahkan.");
        }
        showDialog.value = false;
        fetchSites();
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;
        } else {
            const msg = err.response?.data?.message || "Gagal menyimpan rekomendasi website.";
            toast.error(msg);
        }
    } finally {
        isSaving.value = false;
    }
};

const toggleActive = async (site) => {
    togglingId.value = site.id;
    try {
        const res = await axios.patch(`/api/v1/admin/recommended-sites/${site.id}/toggle`);
        site.is_active = res.data?.is_active;
        const msg = res.data?.message || (site.is_active ? "Website diaktifkan." : "Website dinonaktifkan.");
        toast.success(msg);
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal mengubah status website.";
        toast.error(msg);
    } finally {
        togglingId.value = null;
    }
};

const confirmDelete = (site) => {
    deletingSite.value = site;
    showDeleteDialog.value = true;
};

const deleteSite = async () => {
    if (!deletingSite.value) return;
    isDeleting.value = true;
    try {
        await axios.delete(`/api/v1/admin/recommended-sites/${deletingSite.value.id}`);
        sites.value = sites.value.filter((s) => s.id !== deletingSite.value.id);
        toast.success("Rekomendasi website berhasil dihapus.");
        showDeleteDialog.value = false;
        deletingSite.value = null;
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal menghapus rekomendasi website.";
        toast.error(msg);
    } finally {
        isDeleting.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <SectionHeader
            title="Rekomendasi Website"
            description="Kelola tautan website roleplay dan platform mitra IME Police yang direkomendasikan di Dashboard."
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1.5 font-mono text-xs"
                        :disabled="isLoading"
                        @click="fetchSites"
                    >
                        <RefreshCw class="size-3.5" :class="{ 'motion-safe:animate-spin': isLoading }" />
                        <span>Refresh</span>
                    </Button>
                    <Button
                        size="sm"
                        class="h-8 gap-1.5 font-mono text-xs font-bold"
                        @click="openAddDialog"
                    >
                        <Plus class="size-4" />
                        <span>Tambah Rekomendasi</span>
                    </Button>
                </div>
            </template>
        </SectionHeader>

        <!-- Filters & Search -->
        <Card class="p-3.5 sm:p-4">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <Search class="size-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari berdasarkan nama atau URL..."
                        class="pl-9 h-9 text-xs font-mono"
                    />
                </div>
                <div class="text-xs text-muted-foreground font-mono">
                    Total: <strong class="text-foreground">{{ sites.length }}</strong> website rekomendasi
                </div>
            </div>
        </Card>

        <!-- Sites Table -->
        <Card class="overflow-hidden">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/40 uppercase text-xs font-mono">
                            <TableHead class="w-16">Poster</TableHead>
                            <TableHead>Nama & Judul</TableHead>
                            <TableHead>URL Tujuan</TableHead>
                            <TableHead class="text-center">Chips</TableHead>
                            <TableHead class="text-center">Urutan</TableHead>
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="site in filteredSites"
                            :key="site.id"
                            :class="{ 'opacity-60 bg-muted/20': !site.is_active }"
                            class="text-xs font-mono"
                        >
                            <!-- Thumbnail Poster -->
                            <TableCell>
                                <div class="size-10 rounded-md border border-border bg-muted overflow-hidden flex items-center justify-center">
                                    <img
                                        :src="site.poster_image_url"
                                        :alt="site.name"
                                        class="size-full object-contain"
                                    />
                                </div>
                            </TableCell>

                            <!-- Nama & Badge -->
                            <TableCell>
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-foreground text-sm font-sans">{{ site.name }}</span>
                                        <Badge v-if="site.badge" variant="outline" class="font-mono text-xs">
                                            {{ site.badge }}
                                        </Badge>
                                        <Badge
                                            v-if="site.is_featured"
                                            variant="outline"
                                            class="bg-warning/15 text-warning border-warning/40 text-xs font-bold"
                                        >
                                            ★ REKOMENDASI
                                        </Badge>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ site.poster_name }}
                                        <span v-if="site.poster_subtitle">· {{ site.poster_subtitle }}</span>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- URL -->
                            <TableCell>
                                <a
                                    :href="site.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary hover:underline inline-flex items-center gap-1 max-w-[240px] truncate"
                                >
                                    <span class="truncate">{{ site.url }}</span>
                                    <ExternalLink class="size-3 shrink-0" />
                                </a>
                            </TableCell>

                            <!-- Chips Count -->
                            <TableCell class="text-center">
                                <Badge variant="secondary" class="font-mono text-xs">
                                    {{ (site.chips || []).length }} chip
                                </Badge>
                            </TableCell>

                            <!-- Sort Order -->
                            <TableCell class="text-center tabular-nums">
                                {{ site.sort_order }}
                            </TableCell>

                            <!-- Switch Status -->
                            <TableCell class="text-center">
                                <Switch
                                    :checked="site.is_active"
                                    :disabled="togglingId === site.id"
                                    @update:checked="toggleActive(site)"
                                />
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Edit rekomendasi"
                                        @click="openEditDialog(site)"
                                    >
                                        <Pencil class="size-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Hapus rekomendasi"
                                        class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                        @click="confirmDelete(site)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="filteredSites.length === 0">
                            <TableCell colspan="7" class="h-28 text-center text-muted-foreground">
                                {{ searchQuery ? "Tidak ada rekomendasi website yang cocok." : "Belum ada website rekomendasi." }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>

        <!-- ADD / EDIT DIALOG -->
        <Dialog v-model:open="showDialog">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="font-heading font-extrabold text-base">
                        {{ editingSite ? "Edit Rekomendasi Website" : "Tambah Rekomendasi Website" }}
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Isi informasi website roleplay yang ingin ditampilkan pada kartu rekomendasi di Dashboard.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2 text-xs">
                    <!-- Name & Badge -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2 space-y-1.5">
                            <Label for="rec-name" class="font-mono">Nama Judul Besar *</Label>
                            <Input
                                id="rec-name"
                                v-model="formData.name"
                                placeholder="Contoh: LSPD HANDBOOK"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.name" class="text-destructive text-xs">
                                {{ formErrors.name[0] }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="rec-badge" class="font-mono">Badge Kotak</Label>
                            <Input
                                id="rec-badge"
                                v-model="formData.badge"
                                placeholder="Contoh: HANDBOOK"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.badge" class="text-destructive text-xs">
                                {{ formErrors.badge[0] }}
                            </p>
                        </div>
                    </div>

                    <!-- Poster Name & Subtitle -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="rec-poster-name" class="font-mono">Nama Poster *</Label>
                            <Input
                                id="rec-poster-name"
                                v-model="formData.poster_name"
                                placeholder="Contoh: LOS SANTOS POLICE DEPARTMENT"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.poster_name" class="text-destructive text-xs">
                                {{ formErrors.poster_name[0] }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="rec-poster-sub" class="font-mono">Subjudul Poster</Label>
                            <Input
                                id="rec-poster-sub"
                                v-model="formData.poster_subtitle"
                                placeholder="Contoh: Handbook Internal"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.poster_subtitle" class="text-destructive text-xs">
                                {{ formErrors.poster_subtitle[0] }}
                            </p>
                        </div>
                    </div>

                    <!-- URL Gambar Poster & Background -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="rec-poster-img" class="font-mono">URL Poster Gambar *</Label>
                            <Input
                                id="rec-poster-img"
                                v-model="formData.poster_image_url"
                                placeholder="/images/recommended/... atau https://..."
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.poster_image_url" class="text-destructive text-xs">
                                {{ formErrors.poster_image_url[0] }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="rec-bg-img" class="font-mono">URL Gambar Latar</Label>
                            <Input
                                id="rec-bg-img"
                                v-model="formData.background_image_url"
                                placeholder="/images/recommended/... atau https://..."
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.background_image_url" class="text-destructive text-xs">
                                {{ formErrors.background_image_url[0] }}
                            </p>
                        </div>
                    </div>

                    <!-- Target URL & CTA Text -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2 space-y-1.5">
                            <Label for="rec-url" class="font-mono">URL Website Tujuan *</Label>
                            <Input
                                id="rec-url"
                                v-model="formData.url"
                                placeholder="https://ucup-meletup.site/"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.url" class="text-destructive text-xs">
                                {{ formErrors.url[0] }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="rec-cta" class="font-mono">Teks Tombol CTA</Label>
                            <Input
                                id="rec-cta"
                                v-model="formData.cta_text"
                                placeholder="Kunjungi website"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.cta_text" class="text-destructive text-xs">
                                {{ formErrors.cta_text[0] }}
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <Label for="rec-desc" class="font-mono">Deskripsi * (Maks. 600 karakter)</Label>
                        <Textarea
                            id="rec-desc"
                            v-model="formData.description"
                            rows="3"
                            placeholder="Tuliskan ringkasan fitur atau panduan yang ditawarkan website ini..."
                            class="text-xs"
                        />
                        <p v-if="formErrors.description" class="text-destructive text-xs">
                            {{ formErrors.description[0] }}
                        </p>
                    </div>

                    <!-- Chips & Sort Order -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2 space-y-1.5">
                            <Label for="rec-chips" class="font-mono">Tag / Chips (Pisahkan dengan koma)</Label>
                            <Input
                                id="rec-chips"
                                v-model="formData.chips_text"
                                placeholder="10-Codes, Penal Code, Patrol Report"
                                class="h-9 font-mono text-xs"
                            />
                            <p v-if="formErrors.chips" class="text-destructive text-xs">
                                {{ formErrors.chips[0] }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="rec-sort" class="font-mono">Urutan Tampil</Label>
                            <Input
                                id="rec-sort"
                                v-model.number="formData.sort_order"
                                type="number"
                                min="0"
                                class="h-9 font-mono text-xs"
                            />
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-border">
                        <div class="flex items-center gap-2">
                            <Switch id="rec-featured" v-model="formData.is_featured" />
                            <Label for="rec-featured" class="font-mono cursor-pointer">
                                Chip Emas "★ REKOMENDASI"
                            </Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Switch id="rec-active" v-model="formData.is_active" />
                            <Label for="rec-active" class="font-mono cursor-pointer">
                                Aktif (Tampilkan di Publik)
                            </Label>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="pt-4 border-t border-border space-y-2">
                        <Label class="font-mono text-muted-foreground uppercase text-xs">
                            Pratinjau Kartu:
                        </Label>
                        <RecommendedSiteCard :site="previewSiteData" />
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" size="sm" @click="showDialog = false">
                        Batal
                    </Button>
                    <Button
                        size="sm"
                        class="font-mono font-bold"
                        :disabled="isSaving"
                        @click="saveSite"
                    >
                        {{ isSaving ? "Menyimpan..." : "Simpan Rekomendasi" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- DELETE CONFIRMATION ALERT -->
        <AlertDialog v-model:open="showDeleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-destructive font-heading font-extrabold">
                        Hapus Rekomendasi Website?
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        Apakah Anda yakin ingin menghapus <strong>{{ deletingSite?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="isDeleting">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="isDeleting"
                        class="bg-destructive hover:bg-destructive/90 text-destructive-foreground font-mono font-bold"
                        @click="deleteSite"
                    >
                        {{ isDeleting ? "Menghapus..." : "Ya, Hapus" }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>

