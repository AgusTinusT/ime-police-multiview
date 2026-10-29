<script setup>
import { computed, onUnmounted, reactive, ref, watch } from "vue";
import axios from "axios";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Separator } from "@/Components/ui/separator";
import { Spinner } from "@/Components/ui/spinner";
import HeroCarousel from "@/Components/Hero/HeroCarousel.vue";
import { Monitor, Smartphone } from "@lucide/vue";

const props = defineProps({
    initialSlide: {
        type: Object,
        default: null,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["saved", "cancel", "toast"]);

function toDatetimeLocal(isoStr) {
    if (!isoStr) return "";
    const d = new Date(isoStr);
    if (Number.isNaN(d.getTime())) return "";
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const form = reactive({
    slide_type: props.initialSlide?.slide_type || "feature",
    title: props.initialSlide?.title || "",
    subtitle: props.initialSlide?.subtitle || "",
    badge_label: props.initialSlide?.badge_label || "",
    accent: props.initialSlide?.accent || "primary",
    audience: props.initialSlide?.audience || "all",
    image_alt: props.initialSlide?.image_alt || "",
    cta_primary_label: props.initialSlide?.cta_primary_label || "",
    cta_primary_url: props.initialSlide?.cta_primary_url || "",
    cta_secondary_label: props.initialSlide?.cta_secondary_label || "",
    cta_secondary_url: props.initialSlide?.cta_secondary_url || "",
    starts_at: toDatetimeLocal(props.initialSlide?.starts_at),
    ends_at: toDatetimeLocal(props.initialSlide?.ends_at),
    is_active: props.initialSlide?.is_active ?? true,
});

const desktopFile = ref(null);
const mobileFile = ref(null);
const desktopPreviewUrl = ref(props.initialSlide?.image_desktop_url || "");
const mobilePreviewUrl = ref(props.initialSlide?.image_mobile_url || "");
const previewMode = ref("desktop"); // 'desktop' | 'mobile'
const isSubmitting = ref(false);
const errors = ref({});

const objectUrls = [];

function registerObjectUrl(file) {
    const url = URL.createObjectURL(file);
    objectUrls.push(url);
    return url;
}

onUnmounted(() => {
    for (const url of objectUrls) {
        URL.revokeObjectURL(url);
    }
});

watch(
    () => props.initialSlide,
    (val) => {
        form.slide_type = val?.slide_type || "feature";
        form.title = val?.title || "";
        form.subtitle = val?.subtitle || "";
        form.badge_label = val?.badge_label || "";
        form.accent = val?.accent || "primary";
        form.audience = val?.audience || "all";
        form.image_alt = val?.image_alt || "";
        form.cta_primary_label = val?.cta_primary_label || "";
        form.cta_primary_url = val?.cta_primary_url || "";
        form.cta_secondary_label = val?.cta_secondary_label || "";
        form.cta_secondary_url = val?.cta_secondary_url || "";
        form.starts_at = toDatetimeLocal(val?.starts_at);
        form.ends_at = toDatetimeLocal(val?.ends_at);
        form.is_active = val?.is_active ?? true;
        desktopFile.value = null;
        mobileFile.value = null;
        desktopPreviewUrl.value = val?.image_desktop_url || "";
        mobilePreviewUrl.value = val?.image_mobile_url || "";
        errors.value = {};
    },
);

const isSpotlight = computed(() => form.slide_type === "live_spotlight");

function onDesktopFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    desktopFile.value = file;
    desktopPreviewUrl.value = registerObjectUrl(file);
}

function onMobileFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    mobileFile.value = file;
    mobilePreviewUrl.value = registerObjectUrl(file);
}

const previewSlide = computed(() => ({
    id: props.initialSlide?.id || 0,
    type: form.slide_type,
    slide_type: form.slide_type,
    title: isSpotlight.value ? "Capt. Vance (Contoh Live)" : form.title || "Judul Slide Hero",
    subtitle: isSpotlight.value
        ? form.subtitle || "Patroli Kode 3 San Andreas Highway"
        : form.subtitle,
    badge_label: form.badge_label || (isSpotlight.value ? "LIVE SPOTLIGHT" : ""),
    accent: form.accent,
    image_desktop_url: desktopPreviewUrl.value || "/images/hero-fallback.jpg",
    image_mobile_url:
        mobilePreviewUrl.value || desktopPreviewUrl.value || "/images/hero-fallback.jpg",
    image_alt: form.image_alt || form.title || "Pratinjau",
    cta_primary:
        form.cta_primary_label && form.cta_primary_url
            ? { label: form.cta_primary_label, url: form.cta_primary_url }
            : isSpotlight.value
              ? { label: "Tonton di Multiview", url: "/multiview" }
              : null,
    cta_secondary:
        form.cta_secondary_label && form.cta_secondary_url
            ? { label: form.cta_secondary_label, url: form.cta_secondary_url }
            : null,
    live: isSpotlight.value
        ? {
              officer_name: "Capt. Vance",
              department: "SASP",
              viewers_count: 1420,
              avatar_url: "",
          }
        : null,
}));

function firstError(field) {
    const val = errors.value?.[field];
    return Array.isArray(val) ? val[0] : val || "";
}

async function handleSubmit() {
    isSubmitting.value = true;
    errors.value = {};

    const fd = new FormData();
    if (props.isEdit && props.initialSlide?.id) {
        fd.append("_method", "PUT");
    }

    fd.append("slide_type", form.slide_type);
    if (!isSpotlight.value) {
        fd.append("title", form.title || "");
    }
    fd.append("subtitle", form.subtitle || "");
    fd.append("badge_label", form.badge_label || "");
    fd.append("accent", form.accent);
    fd.append("audience", form.audience);
    fd.append("image_alt", form.image_alt || "");
    fd.append("cta_primary_label", form.cta_primary_label || "");
    fd.append("cta_primary_url", form.cta_primary_url || "");
    fd.append("cta_secondary_label", form.cta_secondary_label || "");
    fd.append("cta_secondary_url", form.cta_secondary_url || "");
    if (form.starts_at) fd.append("starts_at", new Date(form.starts_at).toISOString());
    if (form.ends_at) fd.append("ends_at", new Date(form.ends_at).toISOString());
    fd.append("is_active", form.is_active ? "1" : "0");

    if (desktopFile.value) {
        fd.append("image_desktop", desktopFile.value);
    }
    if (mobileFile.value) {
        fd.append("image_mobile", mobileFile.value);
    }

    const url =
        props.isEdit && props.initialSlide?.id
            ? `/api/v1/admin/hero-slides/${props.initialSlide.id}`
            : "/api/v1/admin/hero-slides";

    try {
        const res = await axios.post(url, fd, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        emit("saved", res.data);
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
            emit("toast", err.response.data.message || "Periksa kembali isian formulir.", "error");
        } else {
            emit(
                "toast",
                err.response?.data?.message || "Gagal menyimpan slide hero.",
                "error",
            );
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="handleSubmit">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label>Tipe Slide</Label>
                <Select v-model="form.slide_type">
                    <SelectTrigger aria-label="Pilih tipe slide">
                        <SelectValue placeholder="Pilih tipe" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="feature">Feature (Fitur Baru)</SelectItem>
                        <SelectItem value="event">Event Komunitas</SelectItem>
                        <SelectItem value="cta">CTA (Ajakan Aksi)</SelectItem>
                        <SelectItem value="live_spotlight">Live Spotlight (Otomatis)</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="firstError('slide_type')" class="text-xs text-destructive">
                    {{ firstError("slide_type") }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label>Warna Aksen</Label>
                <Select v-model="form.accent">
                    <SelectTrigger aria-label="Pilih warna aksen">
                        <SelectValue placeholder="Pilih aksen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="primary">Primary (Biru)</SelectItem>
                        <SelectItem value="info">Info (Sian)</SelectItem>
                        <SelectItem value="success">Success (Hijau)</SelectItem>
                        <SelectItem value="warning">Warning (Amber)</SelectItem>
                        <SelectItem value="danger">Danger (Merah)</SelectItem>
                        <SelectItem value="dept-lspd">LSPD</SelectItem>
                        <SelectItem value="dept-bcso">BCSO</SelectItem>
                        <SelectItem value="dept-sasp">SASP</SelectItem>
                        <SelectItem value="dept-sapr">SAPR</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="firstError('accent')" class="text-xs text-destructive">
                    {{ firstError("accent") }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label>Target Audiens</Label>
                <Select v-model="form.audience">
                    <SelectTrigger aria-label="Pilih audiens">
                        <SelectValue placeholder="Pilih audiens" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Pengunjung</SelectItem>
                        <SelectItem value="guest">Guest (Belum Login)</SelectItem>
                        <SelectItem value="member">Member (Non-Officer)</SelectItem>
                        <SelectItem value="officer">Officer Terverifikasi</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="firstError('audience')" class="text-xs text-destructive">
                    {{ firstError("audience") }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div v-if="!isSpotlight" class="grid gap-2 sm:col-span-2">
                <Label for="hs-title">Judul Utama (maks. 80 karakter) *</Label>
                <Input
                    id="hs-title"
                    v-model="form.title"
                    maxlength="80"
                    placeholder="Contoh: PELUNCURAN MULTIVIEW KOMANDO"
                />
                <p v-if="firstError('title')" class="text-xs text-destructive">
                    {{ firstError("title") }}
                </p>
            </div>

            <div :class="isSpotlight ? 'sm:col-span-3 grid gap-2' : 'grid gap-2'">
                <Label for="hs-badge">Label Badge (maks. 24 karakter)</Label>
                <Input
                    id="hs-badge"
                    v-model="form.badge_label"
                    maxlength="24"
                    placeholder="FITUR BARU"
                />
                <p v-if="firstError('badge_label')" class="text-xs text-destructive">
                    {{ firstError("badge_label") }}
                </p>
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="hs-subtitle">Subjudul / Deskripsi (maks. 200 karakter)</Label>
            <Textarea
                id="hs-subtitle"
                v-model="form.subtitle"
                maxlength="200"
                rows="2"
                placeholder="Ringkasan singkat fitur atau pengumuman..."
            />
            <p v-if="firstError('subtitle')" class="text-xs text-destructive">
                {{ firstError("subtitle") }}
            </p>
        </div>

        <!-- Image Upload (Hidden for live_spotlight) -->
        <div v-if="!isSpotlight" class="space-y-4 rounded-lg border border-border bg-muted/20 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="hs-img-desktop">Gambar Desktop (min. 1600×600, maks. 2 MB) *</Label>
                    <Input
                        id="hs-img-desktop"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onDesktopFileChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        Otomatis dikonversi ke WebP (rasio ±8:3).
                    </p>
                    <p v-if="firstError('image_desktop')" class="text-xs text-destructive">
                        {{ firstError("image_desktop") }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="hs-img-mobile">Gambar Mobile Opsional (min. 1080×1080, maks. 2 MB)</Label>
                    <Input
                        id="hs-img-mobile"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onMobileFileChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        Jika kosong, gambar desktop akan dipakai di mobile.
                    </p>
                    <p v-if="firstError('image_mobile')" class="text-xs text-destructive">
                        {{ firstError("image_mobile") }}
                    </p>
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="hs-img-alt">Teks Alternatif Gambar (Alt Text, maks. 150 karakter) *</Label>
                <Input
                    id="hs-img-alt"
                    v-model="form.image_alt"
                    maxlength="150"
                    placeholder="Deskripsi visual gambar untuk aksesibilitas"
                />
                <p v-if="firstError('image_alt')" class="text-xs text-destructive">
                    {{ firstError("image_alt") }}
                </p>
            </div>
        </div>

        <!-- CTA Fields -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-3 rounded-lg border border-border p-3.5">
                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                    Tombol Aksi Utama (CTA 1)
                </p>
                <div class="grid gap-2">
                    <Label for="hs-cta1-label">Label Tombol (maks. 30)</Label>
                    <Input
                        id="hs-cta1-label"
                        v-model="form.cta_primary_label"
                        maxlength="30"
                        placeholder="Buka Multiview"
                    />
                    <p v-if="firstError('cta_primary_label')" class="text-xs text-destructive">
                        {{ firstError("cta_primary_label") }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="hs-cta1-url">URL Tujuan (/path atau https://)</Label>
                    <Input
                        id="hs-cta1-url"
                        v-model="form.cta_primary_url"
                        placeholder="/multiview"
                    />
                    <p v-if="firstError('cta_primary_url')" class="text-xs text-destructive">
                        {{ firstError("cta_primary_url") }}
                    </p>
                </div>
            </div>

            <div class="space-y-3 rounded-lg border border-border p-3.5">
                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Tombol Aksi Kedua (CTA 2)
                </p>
                <div class="grid gap-2">
                    <Label for="hs-cta2-label">Label Tombol (maks. 30)</Label>
                    <Input
                        id="hs-cta2-label"
                        v-model="form.cta_secondary_label"
                        maxlength="30"
                        placeholder="Direktori Perwira"
                    />
                    <p v-if="firstError('cta_secondary_label')" class="text-xs text-destructive">
                        {{ firstError("cta_secondary_label") }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="hs-cta2-url">URL Tujuan (/path atau https://)</Label>
                    <Input
                        id="hs-cta2-url"
                        v-model="form.cta_secondary_url"
                        placeholder="/officers"
                    />
                    <p v-if="firstError('cta_secondary_url')" class="text-xs text-destructive">
                        {{ firstError("cta_secondary_url") }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Schedule & Active Toggle -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="hs-starts">Mulai Tayang (Opsional)</Label>
                <Input id="hs-starts" v-model="form.starts_at" type="datetime-local" />
                <p v-if="firstError('starts_at')" class="text-xs text-destructive">
                    {{ firstError("starts_at") }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="hs-ends">Berakhir Tayang (Opsional)</Label>
                <Input id="hs-ends" v-model="form.ends_at" type="datetime-local" />
                <p v-if="firstError('ends_at')" class="text-xs text-destructive">
                    {{ firstError("ends_at") }}
                </p>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-border p-3 sm:mt-6">
                <Label for="hs-active">Aktifkan Slide</Label>
                <Switch id="hs-active" v-model="form.is_active" />
            </div>
        </div>

        <Separator />

        <!-- Live Preview -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="font-mono text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Pratinjau Langsung
                </span>
                <div class="flex items-center gap-1.5">
                    <Button
                        type="button"
                        size="sm"
                        :variant="previewMode === 'desktop' ? 'default' : 'outline'"
                        @click="previewMode = 'desktop'"
                    >
                        <Monitor class="size-4" />
                        Desktop
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="previewMode === 'mobile' ? 'default' : 'outline'"
                        @click="previewMode = 'mobile'"
                    >
                        <Smartphone class="size-4" />
                        Mobile
                    </Button>
                </div>
            </div>

            <div
                :class="[
                    'overflow-hidden rounded-xl border border-border bg-background transition-all',
                    previewMode === 'mobile' ? 'mx-auto max-w-sm' : 'w-full',
                ]"
            >
                <HeroCarousel :slides="[previewSlide]" :preview="true" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="emit('cancel')">
                Batal
            </Button>
            <Button type="submit" :disabled="isSubmitting">
                <Spinner v-if="isSubmitting" class="size-4" />
                <span>{{ isEdit ? "Simpan Perubahan" : "Tambah Slide" }}</span>
            </Button>
        </div>
    </form>
</template>
