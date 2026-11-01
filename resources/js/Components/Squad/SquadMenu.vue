<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { LogIn, LogOut, Pencil, Plus, Users } from "@lucide/vue";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
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

const handleOpenSave = () => {
    if (!currentUser.value) {
        showAuthModal.value = true;
        return;
    }
    dialogSquad.value = null;
    showSaveDialog.value = true;
};

const handleEditSquad = () => {
    if (!props.activeSquad) return;
    dialogSquad.value = props.activeSquad;
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
                class="gap-2 cursor-pointer text-primary font-bold"
                @click="handleOpenSave"
            >
                <Plus class="size-3.5" />
                <span>{{ $t('squad.save_current_pov') }}</span>
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
        :initial-officers="currentOfficers"
        @saved="onSquadSaved"
        @forked="onSquadForked"
    />

    <!-- Guest Auth Modal -->
    <AuthModal
        :is-open="showAuthModal"
        @close="showAuthModal = false"
    />
</template>
