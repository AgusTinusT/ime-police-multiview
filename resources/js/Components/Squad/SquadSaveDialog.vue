<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { GitFork, Loader2, Plus, Save, Search, Trash2, UserPlus, X } from "@lucide/vue";
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
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { DeptBadge, OfficerAvatar } from "@/Components/tactical";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    squad: {
        type: Object,
        default: null,
    },
    initialOfficers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:open", "saved", "forked", "deleted"]);

const name = ref("");
const description = ref("");
const visibility = ref("private");
const officers = ref([]);
const isSubmitting = ref(false);
const errors = ref({});

const allOfficers = ref([]);
const isLoadingOfficers = ref(false);
const officerSearch = ref("");
const showAddPicker = ref(false);

const loadAvailableOfficers = async () => {
    if (allOfficers.value.length > 0) return;
    isLoadingOfficers.value = true;
    try {
        const res = await axios.get("/api/v1/squads/officers");
        if (res.data?.status === "success") {
            allOfficers.value = res.data.data || [];
        }
    } catch {
        // silent fail
    } finally {
        isLoadingOfficers.value = false;
    }
};

const selectableOfficers = computed(() => {
    const currentIds = new Set(officers.value.map((o) => Number(o.id)));
    let list = allOfficers.value.filter((o) => !currentIds.has(Number(o.id)));
    if (officerSearch.value.trim()) {
        const q = officerSearch.value.trim().toLowerCase();
        list = list.filter((o) =>
            [o.officer_name, o.streamer_name, o.callsign, o.department, o.badge_number]
                .filter(Boolean)
                .some((v) => String(v).toLowerCase().includes(q))
        );
    }
    return list;
});

const addOfficer = (officer) => {
    if (officers.value.length >= 12) {
        toast.warning("Maksimal 12 perwira dalam satu squad.");
        return;
    }
    officers.value.push(officer);
    delete errors.value.officer_ids;
};

const isOwner = computed(() => {
    if (!props.squad) return true;
    return Boolean(props.squad.is_owner);
});

const isEditing = computed(() => Boolean(props.squad?.id));

const syncForm = () => {
    errors.value = {};
    showAddPicker.value = false;
    officerSearch.value = "";
    loadAvailableOfficers();
    if (props.squad) {
        name.value = props.squad.name || "";
        description.value = props.squad.description || "";
        visibility.value = props.squad.visibility || "private";
        // Start with the squad's current members, then merge in whatever POVs
        // the user had selected in Multiview — opening Edit after picking POVs
        // should carry those over so they can be added in one go.
        const base = [...(props.squad.officers || [])];
        const haveIds = new Set(base.map((o) => Number(o.id)));
        const extras = (props.initialOfficers || []).filter((o) => !haveIds.has(Number(o.id)));
        officers.value = [...base, ...extras];
    } else {
        name.value = "";
        description.value = "";
        visibility.value = "private";
        officers.value = [...props.initialOfficers];
    }
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            syncForm();
        }
    },
    { immediate: true },
);

const removeOfficer = (id) => {
    officers.value = officers.value.filter((o) => o.id !== id);
    if (officers.value.length === 0) {
        errors.value.officer_ids = "Pilih minimal 1 perwira.";
    } else {
        delete errors.value.officer_ids;
    }
};

const handleSave = async () => {
    errors.value = {};

    if (!name.value || name.value.trim().length < 3) {
        errors.value.name = "Nama squad minimal 3 karakter.";
        return;
    }
    if (name.value.trim().length > 40) {
        errors.value.name = "Nama squad maksimal 40 karakter.";
        return;
    }
    if (officers.value.length === 0) {
        errors.value.officer_ids = "Pilih minimal 1 perwira.";
        return;
    }
    if (officers.value.length > 12) {
        errors.value.officer_ids = "Maksimal 12 perwira dalam satu squad.";
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            name: name.value.trim(),
            description: description.value ? description.value.trim() : null,
            visibility: visibility.value,
            officer_ids: officers.value.map((o) => o.id),
        };

        let res;
        if (isEditing.value && isOwner.value) {
            res = await axios.put(`/api/v1/squads/${props.squad.id}`, payload);
            toast.success("Squad berhasil diperbarui.");
        } else {
            res = await axios.post("/api/v1/squads", payload);
            toast.success("Squad berhasil disimpan.");
        }

        emit("saved", res.data.data);
        emit("update:open", false);
    } catch (err) {
        if (err.response?.status === 422) {
            const data = err.response.data;
            if (data.errors) {
                const mapped = {};
                for (const key in data.errors) {
                    mapped[key] = data.errors[key][0];
                }
                errors.value = mapped;
            } else if (data.message) {
                errors.value.general = data.message;
            }
        } else if (err.response?.status === 403) {
            errors.value.general = "Anda tidak memiliki izin untuk mengubah squad ini.";
        } else if (err.response?.status === 429) {
            const retry = Number(err.response.headers?.["retry-after"]) || 60;
            errors.value.general = `Terlalu banyak percobaan simpan. Coba lagi dalam ${retry} detik.`;
        } else {
            errors.value.general = err.response?.data?.message || "Gagal menyimpan squad.";
        }
    } finally {
        isSubmitting.value = false;
    }
};

const showDeleteConfirm = ref(false);
const isDeleting = ref(false);
const showForkConfirm = ref(false);

const handleDelete = async () => {
    if (!props.squad?.id) return;
    isDeleting.value = true;
    try {
        await axios.delete(`/api/v1/squads/${props.squad.id}`);
        toast.success("Squad berhasil dihapus.");
        emit("deleted", props.squad.id);
        emit("update:open", false);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menghapus squad.");
    } finally {
        isDeleting.value = false;
        showDeleteConfirm.value = false;
    }
};

const handleForkAndEdit = async () => {
    if (!props.squad?.id) return;
    isSubmitting.value = true;
    errors.value = {};
    try {
        const res = await axios.post(`/api/v1/squads/${props.squad.id}/fork`);
        toast.success("Squad berhasil di-fork ke koleksi pribadi Anda.");
        const forkedSquad = res.data.data;
        emit("forked", forkedSquad);
        name.value = forkedSquad.name;
        description.value = forkedSquad.description || "";
        visibility.value = "private";
        officers.value = [...(forkedSquad.officers || [])];
    } catch (err) {
        errors.value.general = err.response?.data?.message || "Gagal melakukan fork squad.";
    } finally {
        isSubmitting.value = false;
        showForkConfirm.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="font-mono text-sm uppercase tracking-wider text-primary">
                    {{ isEditing ? (isOwner ? $t('squad.dialog_title_edit') : 'Fork & Ubah Squad') : $t('squad.dialog_title_create') }}
                </DialogTitle>
                <DialogDescription class="text-xs text-muted-foreground font-mono">
                    {{ $t('squad.dialog_desc') }}
                </DialogDescription>
            </DialogHeader>

            <!-- General Error Alert -->
            <div
                v-if="errors.general"
                class="rounded-md bg-destructive/15 border border-destructive/30 p-2.5 text-xs text-destructive font-mono"
            >
                {{ errors.general }}
            </div>

            <!-- Non-owner Notice & Fork Button -->
            <div
                v-if="isEditing && !isOwner"
                class="rounded-md bg-muted/50 border border-border p-3 text-xs space-y-2 font-mono"
            >
                <p class="text-muted-foreground">
                    Squad ini milik pengguna lain (<strong>{{ squad.owner_name }}</strong>). Anda dapat mem-fork squad ini untuk membuat salinan pribadi.
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="gap-1.5 w-full font-mono text-xs"
                    :disabled="isSubmitting"
                    @click="showForkConfirm = true"
                >
                    <GitFork class="size-3.5" />
                    <span>{{ $t('squad.fork_and_edit') }}</span>
                </Button>
            </div>

            <form class="space-y-4 py-2" @submit.prevent="handleSave">
                <!-- Name -->
                <div class="space-y-1.5">
                    <Label for="sq-name" class="text-xs font-mono">{{ $t('squad.name_label') }}</Label>
                    <Input
                        id="sq-name"
                        v-model="name"
                        type="text"
                        maxlength="40"
                        :placeholder="$t('squad.name_placeholder')"
                        :disabled="isEditing && !isOwner"
                        class="text-xs font-mono"
                    />
                    <p v-if="errors.name" class="text-xs text-destructive font-mono">
                        {{ errors.name }}
                    </p>
                </div>

                <!-- Description -->
                <div class="space-y-1.5">
                    <Label for="sq-desc" class="text-xs font-mono">{{ $t('squad.desc_label') }}</Label>
                    <Textarea
                        id="sq-desc"
                        v-model="description"
                        maxlength="160"
                        rows="2"
                        :placeholder="$t('squad.desc_placeholder')"
                        :disabled="isEditing && !isOwner"
                        class="text-xs font-mono resize-none"
                    />
                    <p v-if="errors.description" class="text-xs text-destructive font-mono">
                        {{ errors.description }}
                    </p>
                </div>

                <!-- Visibility -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-mono">{{ $t('squad.visibility_label') }}</Label>
                    <Select v-model="visibility" :disabled="isEditing && !isOwner">
                        <SelectTrigger class="text-xs font-mono">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="private">
                                {{ $t('squad.visibility_private') }}
                            </SelectItem>
                            <SelectItem value="public">
                                {{ $t('squad.visibility_public') }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Officer List -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <Label class="text-xs font-mono">
                            {{ $t('squad.officers_label', { count: officers.length }) }}
                        </Label>
                        <span class="text-muted-foreground">Maks 12</span>
                    </div>

                    <div class="max-h-48 overflow-y-auto space-y-1.5 rounded-md border border-border p-2 bg-muted/20">
                        <div
                            v-for="officer in officers"
                            :key="officer.id"
                            class="flex items-center justify-between gap-2 p-1.5 rounded-sm bg-card border border-border/60 text-xs font-mono"
                        >
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <OfficerAvatar
                                    :src="officer.avatar_url"
                                    :name="officer.officer_name"
                                    :callsign="officer.callsign"
                                    :department="officer.department"
                                    size="sm"
                                    class="shrink-0"
                                />
                                <div class="min-w-0 flex-1 truncate">
                                    <span class="font-bold text-foreground truncate block">
                                        {{ officer.officer_name }}
                                    </span>
                                    <span class="text-muted-foreground text-xs flex items-center gap-1">
                                        <span>{{ officer.callsign }}</span>
                                        <span>·</span>
                                        <DeptBadge :department="officer.department" />
                                    </span>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                :disabled="isEditing && !isOwner"
                                :aria-label="`Hapus ${officer.officer_name}`"
                                class="size-6 text-muted-foreground hover:text-destructive shrink-0"
                                @click="removeOfficer(officer.id)"
                            >
                                <X class="size-3.5" />
                            </Button>
                        </div>

                        <p v-if="officers.length === 0" class="text-xs text-muted-foreground italic text-center py-2 font-mono">
                            Belum ada perwira terpilih.
                        </p>
                    </div>

                    <!-- Add Officer Picker (Live & Offline) -->
                    <div v-if="(!isEditing || isOwner) && officers.length < 12" class="pt-1">
                        <Button
                            v-if="!showAddPicker"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="w-full text-xs font-mono gap-1.5 border-dashed border-border hover:border-primary/50 text-muted-foreground hover:text-foreground"
                            @click="showAddPicker = true; loadAvailableOfficers();"
                        >
                            <UserPlus class="size-3.5 text-primary" />
                            <span>Tambah Perwira (Live / Offline)</span>
                        </Button>

                        <div v-else class="rounded-md border border-border p-2 space-y-2 bg-muted/40">
                            <div class="flex items-center gap-1.5">
                                <div class="relative flex-1">
                                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground pointer-events-none" />
                                    <Input
                                        v-model="officerSearch"
                                        type="search"
                                        placeholder="Cari nama, callsign, departemen..."
                                        class="h-8 pl-8 pr-2 text-xs font-mono bg-background"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 px-2 text-xs font-mono text-muted-foreground hover:text-foreground cursor-pointer"
                                    @click="showAddPicker = false; officerSearch = '';"
                                >
                                    Tutup
                                </Button>
                            </div>

                            <div v-if="isLoadingOfficers" class="py-4 text-center text-xs text-muted-foreground font-mono">
                                <Loader2 class="size-4 motion-safe:animate-spin mx-auto mb-1 text-primary" />
                                Memuat daftar perwira...
                            </div>

                            <div v-else class="max-h-36 overflow-y-auto space-y-1 pr-1">
                                <div
                                    v-for="cand in selectableOfficers"
                                    :key="cand.id"
                                    class="flex items-center justify-between gap-2 p-1.5 rounded-sm hover:bg-accent border border-transparent hover:border-border transition cursor-pointer text-xs font-mono"
                                    @click="addOfficer(cand)"
                                >
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <OfficerAvatar
                                            :src="cand.avatar_url"
                                            :name="cand.officer_name"
                                            :callsign="cand.callsign"
                                            :department="cand.department"
                                            size="sm"
                                            class="shrink-0"
                                        />
                                        <div class="min-w-0 flex-1 truncate">
                                            <span class="font-bold text-foreground truncate block">
                                                {{ cand.officer_name }}
                                            </span>
                                            <span class="text-muted-foreground text-xs flex items-center gap-1">
                                                <span>{{ cand.callsign }}</span>
                                                <span>·</span>
                                                <DeptBadge :department="cand.department" />
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span
                                            v-if="cand.is_online"
                                            class="text-xs px-1.5 py-0.5 rounded-full font-bold bg-success/20 text-success border border-success/30 uppercase tracking-wider"
                                        >
                                            10-8 Live
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs px-1.5 py-0.5 rounded-full font-bold bg-muted text-muted-foreground border border-border uppercase tracking-wider"
                                        >
                                            10-7 Off
                                        </span>
                                        <Plus class="size-3.5 text-primary" />
                                    </div>
                                </div>

                                <p v-if="selectableOfficers.length === 0" class="text-xs text-muted-foreground italic text-center py-3 font-mono">
                                    {{ officerSearch ? 'Tidak ada perwira yang cocok.' : 'Semua perwira sudah ditambahkan.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-if="errors.officer_ids" class="text-xs text-destructive font-mono">
                        {{ errors.officer_ids }}
                    </p>
                </div>

                <DialogFooter class="gap-2 sm:gap-2 pt-2 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between">
                    <Button
                        v-if="isEditing && isOwner"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="gap-1.5 text-xs font-mono text-destructive hover:bg-destructive/10 hover:text-destructive cursor-pointer justify-center sm:justify-start"
                        :disabled="isSubmitting || isDeleting"
                        @click="showDeleteConfirm = true"
                    >
                        <Trash2 class="size-3.5" />
                        <span>{{ $t('squad.delete_button') }}</span>
                    </Button>
                    <div v-else class="hidden sm:block" />

                    <div class="flex items-center gap-2 justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="text-xs font-mono"
                            @click="emit('update:open', false)"
                        >
                            Batal
                        </Button>
                        <Button
                            v-if="!isEditing || isOwner"
                            type="submit"
                            size="sm"
                            :disabled="isSubmitting || isDeleting"
                            class="gap-1.5 text-xs font-mono"
                        >
                            <Loader2 v-if="isSubmitting" class="size-3.5 motion-safe:animate-spin" />
                            <Save v-else class="size-3.5" />
                            <span>{{ isSubmitting ? $t('squad.saving') : $t('squad.save_button') }}</span>
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- ALERT DIALOG: DELETE CONFIRMATION -->
    <AlertDialog :open="showDeleteConfirm" @update:open="showDeleteConfirm = $event">
        <AlertDialogContent class="font-mono text-xs z-[70]">
            <AlertDialogHeader>
                <AlertDialogTitle class="text-sm font-bold uppercase font-mono">
                    {{ $t('squad.delete_confirm_title') }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-xs">
                    Apakah Anda yakin ingin menghapus squad <strong>"{{ name }}"</strong>? Tindakan ini tidak dapat dibatalkan.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter class="gap-2">
                <AlertDialogCancel :disabled="isDeleting">Batal</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90 font-bold"
                    :disabled="isDeleting"
                    @click="handleDelete"
                >
                    <Loader2 v-if="isDeleting" class="size-3.5 motion-safe:animate-spin mr-1.5" />
                    {{ isDeleting ? "Menghapus..." : $t('squad.delete_confirm_action') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- ALERT DIALOG: FORK CONFIRMATION -->
    <AlertDialog :open="showForkConfirm" @update:open="showForkConfirm = $event">
        <AlertDialogContent class="font-mono text-xs z-[70]">
            <AlertDialogHeader>
                <AlertDialogTitle class="text-sm font-bold uppercase font-mono">
                    {{ $t('squad.fork_confirm_title') }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-xs">
                    Apakah Anda ingin menduplikasi squad <strong>"{{ squad?.name }}"</strong> ke dalam koleksi squad pribadi Anda?
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter class="gap-2">
                <AlertDialogCancel :disabled="isSubmitting">Batal</AlertDialogCancel>
                <AlertDialogAction
                    class="font-bold"
                    :disabled="isSubmitting"
                    @click="handleForkAndEdit"
                >
                    <Loader2 v-if="isSubmitting" class="size-3.5 motion-safe:animate-spin mr-1.5" />
                    {{ isSubmitting ? "Menduplikasi..." : $t('squad.fork_confirm_action') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

