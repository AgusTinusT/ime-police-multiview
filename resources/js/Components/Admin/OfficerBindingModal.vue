<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import { Link2, Unlink, Tag, AlertTriangle } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: "bind", // 'bind' | 'unbind' | 'slug'
    },
    officer: {
        type: Object,
        default: null,
    },
    usersList: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "success", "toast"]);

const selectedUserId = ref("");
const reasonInput = ref("");
const slugInput = ref("");
const isSubmitting = ref(false);
const formError = ref("");
const userSearch = ref("");

const filteredUsers = computed(() => {
    if (!userSearch.value) return props.usersList;
    const q = userSearch.value.toLowerCase();
    return props.usersList.filter(
        (u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    );
});

const handleBind = async () => {
    if (!selectedUserId.value) {
        formError.value = "Silakan pilih akun user yang akan di-bind.";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";

    try {
        const res = await axios.post(`/api/v1/admin/officers/${props.officer.id}/bind-user`, {
            user_id: selectedUserId.value,
            reason: reasonInput.value || null,
        });

        emit("toast", res.data.message || "User berhasil dihubungkan.", "success");
        emit("success");
        emit("close");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal menghubungkan perwira ke akun.";
    } finally {
        isSubmitting.value = false;
    }
};

const handleUnbind = async () => {
    if (!reasonInput.value || reasonInput.value.trim().length < 5) {
        formError.value = "Alasan pemutusan ikatan wajib diisi (minimal 5 karakter).";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";

    try {
        const res = await axios.post(`/api/v1/admin/officers/${props.officer.id}/unbind-user`, {
            reason: reasonInput.value.trim(),
        });

        emit("toast", res.data.message || "Ikatan perwira berhasil dicabut.", "success");
        emit("success");
        emit("close");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal mencabut ikatan perwira.";
    } finally {
        isSubmitting.value = false;
    }
};

const handleUpdateSlug = async () => {
    if (!slugInput.value.trim() || !reasonInput.value.trim()) {
        formError.value = "Slug baru dan alasan perubahan wajib diisi.";
        return;
    }

    const profileId = props.officer?.bio_profile?.id;
    if (!profileId) {
        formError.value = "Profil bio perwira belum tersedia.";
        return;
    }

    isSubmitting.value = true;
    formError.value = "";

    try {
        const res = await axios.patch(`/api/v1/admin/bio-profiles/${profileId}/slug`, {
            slug: slugInput.value.trim(),
            reason: reasonInput.value.trim(),
        });

        emit("toast", res.data.message || "Slug bio perwira berhasil diperbarui.", "success");
        emit("success");
        emit("close");
    } catch (err) {
        formError.value = err.response?.data?.message || "Gagal mengubah slug profil.";
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Dialog
        :open="Boolean(show && officer)"
        @update:open="(!$event) && emit('close')"
    >
        <DialogContent v-if="officer" class="sm:max-w-md text-xs z-[60]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 uppercase text-sm">
                    <Link2 v-if="mode === 'bind'" class="size-4 text-primary" />
                    <Unlink v-else-if="mode === 'unbind'" class="size-4 text-destructive" />
                    <Tag v-else class="size-4 text-primary" />
                    <span>
                        {{
                            mode === "bind"
                                ? "Bind Akun ke Perwira"
                                : mode === "unbind"
                                ? "Cabut Ikatan Akun (Unbind)"
                                : "Ubah Slug Publik Bio-Link"
                        }}
                    </span>
                </DialogTitle>
                <DialogDescription>
                    Kelola ikatan akun user atau identitas tautan publik perwira.
                </DialogDescription>
            </DialogHeader>

            <!-- OFFICER DETAILS -->
            <div class="p-3 rounded-lg bg-muted/50 border border-border space-y-1">
                <div class="font-bold text-foreground text-sm">
                    {{ officer.officer_name }}
                </div>
                <div class="text-muted-foreground text-xs font-mono">
                    Callsign: <span class="text-primary font-semibold">{{ officer.callsign }}</span> • Departemen: {{ officer.department }}
                </div>
                <div v-if="officer.user" class="text-success text-xs pt-1 border-t border-border">
                    User Saat Ini: {{ officer.user.name }} ({{ officer.user.email }})
                </div>
                <div v-if="officer.bio_profile" class="text-muted-foreground text-xs">
                    Slug Bio: <span class="text-primary font-mono">/@{{ officer.bio_profile.slug }}</span>
                </div>
            </div>

            <!-- ERROR ALERT -->
            <Alert v-if="formError" variant="destructive">
                <AlertDescription>{{ formError }}</AlertDescription>
            </Alert>

            <!-- MODE 1: BIND -->
            <div v-if="mode === 'bind'" class="space-y-3">
                <div class="space-y-1.5">
                    <Label for="bind-user-search" class="text-xs uppercase">
                        Pilih Akun User Aktif &amp; Terverifikasi <span class="text-primary">*</span>
                    </Label>
                    <Input
                        id="bind-user-search"
                        v-model="userSearch"
                        type="text"
                        placeholder="Filter nama / email user..."
                        class="mb-1.5"
                    />
                    <select
                        v-model="selectedUserId"
                        aria-label="Pilih akun user"
                        class="w-full h-9 px-3 rounded-md bg-background border border-input text-xs text-foreground focus:outline-hidden focus:ring-2 focus:ring-ring"
                    >
                        <option value="">-- Pilih User ({{ filteredUsers.length }} akun) --</option>
                        <option v-for="u in filteredUsers" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }}) [{{ u.role }}]
                        </option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="bind-reason" class="text-xs uppercase">Catatan / Alasan Binding (Opsional)</Label>
                    <Input
                        id="bind-reason"
                        v-model="reasonInput"
                        type="text"
                        placeholder="Contoh: Streamer resmi SASP"
                    />
                </div>

                <DialogFooter class="gap-2 pt-2">
                    <Button variant="outline" @click="emit('close')">
                        Batal
                    </Button>
                    <Button :disabled="isSubmitting" @click="handleBind">
                        {{ isSubmitting ? "Menyimpan..." : "Simpan Binding" }}
                    </Button>
                </DialogFooter>
            </div>

            <!-- MODE 2: UNBIND -->
            <div v-else-if="mode === 'unbind'" class="space-y-3">
                <Alert variant="destructive">
                    <AlertTriangle class="size-4" />
                    <AlertDescription class="text-xs leading-relaxed">
                        Memutus ikatan akan mencabut hak perwira dari akun <strong class="text-foreground">{{ officer.user?.name }}</strong>, menonaktifkan halaman bio-link publiknya, serta mencabut seluruh slot delegasi clipper.
                    </AlertDescription>
                </Alert>

                <div class="space-y-1.5">
                    <Label for="unbind-reason" class="text-xs uppercase">
                        Alasan Pemutusan Ikatan (Wajib) <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="unbind-reason"
                        v-model="reasonInput"
                        rows="3"
                        placeholder="Contoh: Resign / Dishonorable discharge / Pergantian streamer"
                    />
                </div>

                <DialogFooter class="gap-2 pt-2">
                    <Button variant="outline" @click="emit('close')">
                        Batal
                    </Button>
                    <Button variant="destructive" :disabled="isSubmitting" @click="handleUnbind">
                        {{ isSubmitting ? "Memutus..." : "Konfirmasi Unbind" }}
                    </Button>
                </DialogFooter>
            </div>

            <!-- MODE 3: SLUG -->
            <div v-else-if="mode === 'slug'" class="space-y-3">
                <div class="space-y-1.5">
                    <Label for="new-slug-input" class="text-xs uppercase">
                        Slug Publik Baru <span class="text-primary">*</span>
                    </Label>
                    <div class="flex items-center">
                        <span class="px-3 h-9 inline-flex items-center rounded-l-md bg-muted text-muted-foreground border border-r-0 border-input font-mono text-xs">
                            /@
                        </span>
                        <Input
                            id="new-slug-input"
                            v-model="slugInput"
                            type="text"
                            placeholder="nama-slug-baru"
                            class="rounded-l-none font-mono"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Slug lama akan otomatis di-redirect 301 ke slug baru ini.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="slug-reason" class="text-xs uppercase">
                        Alasan Perubahan Slug <span class="text-primary">*</span>
                    </Label>
                    <Input
                        id="slug-reason"
                        v-model="reasonInput"
                        type="text"
                        placeholder="Contoh: Penyesuaian nama panggilan dinas"
                    />
                </div>

                <DialogFooter class="gap-2 pt-2">
                    <Button variant="outline" @click="emit('close')">
                        Batal
                    </Button>
                    <Button :disabled="isSubmitting" @click="handleUpdateSlug">
                        {{ isSubmitting ? "Menyimpan..." : "Ubah Slug" }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
