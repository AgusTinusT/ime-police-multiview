<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "vue-sonner";
import { Loader2, LogIn, LogOut, Pencil, Plus, Trash2, Users } from "@lucide/vue";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
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
import { Badge } from "@/Components/ui/badge";
import { LiveBadge } from "@/Components/tactical";
import AuthModal from "@/Components/AuthModal.vue";
import SquadSaveDialog from "./SquadSaveDialog.vue";

const props = defineProps({
    activeSquad: {
        type: Object,
        default: null,
    },
    currentOfficers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["select-squad", "clear-squad"]);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const mySquads = ref([]);
const isLoading = ref(false);
const showAuthModal = ref(false);
const showSaveDialog = ref(false);
const dialogSquad = ref(null);
const dialogOfficers = ref([]);

const fetchMySquads = async () => {
    if (!currentUser.value) return;
    isLoading.value = true;
    try {
        const res = await axios.get("/api/v1/squads/mine");
        if (res.data?.status === "success") {
            mySquads.value = res.data.data || [];
        }
    } catch {
        // silent fail for menu listing
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    if (currentUser.value) {
        fetchMySquads();
    }
});

watch(currentUser, (user) => {
    if (user) {
        fetchMySquads();
    } else {
        mySquads.value = [];
    }
});

const handleOpenSaveCurrent = () => {
    if (!currentUser.value) {
        showAuthModal.value = true;
        return;
    }
    dialogSquad.value = null;
    dialogOfficers.value = [...props.currentOfficers];
    showSaveDialog.value = true;
};

const handleOpenCreateBlank = () => {
    if (!currentUser.value) {
        showAuthModal.value = true;
        return;
    }
    dialogSquad.value = null;
    dialogOfficers.value = [];
    showSaveDialog.value = true;
};

const handleEditSquad = () => {
    if (!props.activeSquad) return;
    dialogSquad.value = props.activeSquad;
    dialogOfficers.value = [...props.currentOfficers];
    showSaveDialog.value = true;
};

const onSquadSaved = (savedSquad) => {
    fetchMySquads();
    emit("select-squad", savedSquad);
};

const onSquadForked = (forkedSquad) => {
    fetchMySquads();
    emit("select-squad", forkedSquad);
};

const onSquadDeleted = (deletedId) => {
    fetchMySquads();
    if (props.activeSquad?.id === deletedId) {
        emit("clear-squad");
    }
};

const showDeleteActiveConfirm = ref(false);
const isDeletingActive = ref(false);

const handleDeleteActiveSquad = async () => {
    if (!props.activeSquad?.id) return;
    isDeletingActive.value = true;
    try {
        await axios.delete(`/api/v1/squads/${props.activeSquad.id}`);
        toast.success("Squad berhasil dihapus.");
        emit("clear-squad");
        fetchMySquads();
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menghapus squad.");
    } finally {
        isDeletingActive.value = false;
        showDeleteActiveConfirm.value = false;
    }
};
</script>

<template>
    <DropdownMenu @update:open="(val) => val && fetchMySquads()">
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="size-8 px-0 sm:size-9 sm:w-auto sm:px-3 text-xs font-bold sm:gap-1.5 font-mono"
                :class="{ 'border-primary text-primary bg-primary/10': activeSquad }"
                :title="activeSquad ? `Squad: ${activeSquad.name}` : $t('squad.menu')"
            >
                <Users class="size-4 shrink-0" />
                <span class="hidden sm:inline max-w-[130px] truncate">
                    {{ activeSquad ? activeSquad.name : $t('squad.menu') }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-64 font-mono text-xs">
            <!-- Active Squad Section -->
            <template v-if="activeSquad">
                <DropdownMenuLabel class="text-xs">
                    <div class="flex items-center justify-between gap-1">
                        <span class="truncate font-bold text-primary">{{ activeSquad.name }}</span>
                        <Badge variant="secondary" class="text-xs uppercase font-mono px-1">
                            {{ activeSquad.visibility }}
                        </Badge>
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuItem
                    v-if="currentUser"
                    class="gap-2 cursor-pointer"
                    @click="handleEditSquad"
                >
                    <Pencil class="size-3.5" />
                    <span>{{ activeSquad.is_owner ? 'Ubah Squad' : 'Fork & Edit Squad' }}</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="currentUser && activeSquad.is_owner"
                    class="gap-2 cursor-pointer text-destructive focus:bg-destructive/15 focus:text-destructive"
                    @click="showDeleteActiveConfirm = true"
                >
                    <Trash2 class="size-3.5" />
                    <span>{{ $t('squad.delete_button') }}</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="gap-2 cursor-pointer text-warning"
                    @click="emit('clear-squad')"
                >
                    <LogOut class="size-3.5" />
                    <span>{{ $t('squad.exit_squad') }}</span>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
            </template>

            <!-- Save Current POVs Item -->
            <DropdownMenuItem
                v-if="currentOfficers.length > 0"
                class="gap-2 cursor-pointer text-primary font-bold"
                @click="handleOpenSaveCurrent"
            >
                <Plus class="size-3.5" />
                <span>{{ $t('squad.save_current_pov') }} ({{ currentOfficers.length }})</span>
            </DropdownMenuItem>

            <!-- Create Blank Squad Item (Free selection) -->
            <DropdownMenuItem
                class="gap-2 cursor-pointer font-bold"
                @click="handleOpenCreateBlank"
            >
                <Plus class="size-3.5 text-primary" />
                <span>Buat Squad Baru (Pilih Bebas)</span>
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <!-- Logged-in: My Squads List -->
            <template v-if="currentUser">
                <DropdownMenuLabel class="text-xs text-muted-foreground flex items-center justify-between">
                    <span>{{ $t('squad.my_squads') }}</span>
                    <span v-if="mySquads.length">({{ mySquads.length }})</span>
                </DropdownMenuLabel>

                <div v-if="isLoading" class="p-2 text-center text-xs text-muted-foreground">
                    Memuat squad...
                </div>
                <div v-else-if="mySquads.length === 0" class="px-2 py-2 text-xs text-muted-foreground italic">
                    {{ $t('squad.no_squads') }}
                </div>
                <template v-else>
                    <div class="max-h-56 overflow-y-auto space-y-0.5">
                        <DropdownMenuItem
                            v-for="s in mySquads"
                            :key="s.id"
                            class="gap-2 cursor-pointer justify-between"
                            :class="{ 'bg-primary/10 font-bold': activeSquad?.id === s.id }"
                            @click="emit('select-squad', s)"
                        >
                            <span class="truncate flex-1">{{ s.name }}</span>
                            <div class="flex items-center gap-1 shrink-0">
                                <LiveBadge
                                    v-if="s.live_count > 0"
                                    :label="`${s.live_count}`"
                                    class="scale-90"
                                />
                                <span class="text-muted-foreground text-xs">{{ s.officers?.length || 0 }}u</span>
                            </div>
                        </DropdownMenuItem>
                    </div>
                </template>
            </template>

            <!-- Guest: Sign in Item -->
            <template v-else>
                <DropdownMenuItem
                    class="gap-2 cursor-pointer text-primary font-bold"
                    @click="showAuthModal = true"
                >
                    <LogIn class="size-3.5" />
                    <span>{{ $t('squad.login_to_create') }}</span>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>

    <!-- Save & Edit Dialog -->
    <SquadSaveDialog
        v-model:open="showSaveDialog"
        :squad="dialogSquad"
        :initial-officers="dialogOfficers"
        @saved="onSquadSaved"
        @forked="onSquadForked"
        @deleted="onSquadDeleted"
    />

    <!-- Guest Auth Modal -->
    <AuthModal
        :is-open="showAuthModal"
        @close="showAuthModal = false"
    />

    <!-- ALERT DIALOG: DELETE ACTIVE SQUAD CONFIRMATION -->
    <AlertDialog :open="showDeleteActiveConfirm" @update:open="showDeleteActiveConfirm = $event">
        <AlertDialogContent class="font-mono text-xs z-[70]">
            <AlertDialogHeader>
                <AlertDialogTitle class="text-sm font-bold uppercase font-mono">
                    {{ $t('squad.delete_confirm_title') }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-xs">
                    Apakah Anda yakin ingin menghapus squad aktif <strong>"{{ activeSquad?.name }}"</strong>? Tindakan ini tidak dapat dibatalkan.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter class="gap-2">
                <AlertDialogCancel :disabled="isDeletingActive">Batal</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90 font-bold"
                    :disabled="isDeletingActive"
                    @click="handleDeleteActiveSquad"
                >
                    <Loader2 v-if="isDeletingActive" class="size-3.5 motion-safe:animate-spin mr-1.5" />
                    {{ isDeletingActive ? "Menghapus..." : $t('squad.delete_confirm_action') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
