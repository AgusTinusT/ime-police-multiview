<script setup>
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { DeptBadge, OfficerAvatar } from "@/Components/tactical";
import { Eye, EyeOff, Loader2, RotateCcw, Search, SlidersHorizontal } from "@lucide/vue";
import { useOfficerFilter } from "@/Composables/useOfficerFilter";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    initialOfficers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:open", "close"]);

const {
    disabledOfficerKeys,
    isOfficerDisabled,
    hideOfficer,
    unhideOfficer,
    resetAllFilters,
} = useOfficerFilter();

const searchQuery = ref("");
const selectedDept = ref("ALL");
const showOnlyHidden = ref(false);
const isLoading = ref(false);
const officerList = ref(props.initialOfficers || []);

const fetchOfficers = async () => {
    if (officerList.value.length > 0) return;
    isLoading.value = true;
    try {
        const res = await axios.get("/api/v1/squads/officers");
        if (res.data?.data && Array.isArray(res.data.data)) {
            officerList.value = res.data.data;
        }
    } catch (e) {
        console.error("Gagal memuat daftar perwira:", e);
    } finally {
        isLoading.value = false;
    }
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            fetchOfficers();
        }
    }
);

onMounted(() => {
    if (props.open) {
        fetchOfficers();
    }
});

const hiddenCount = computed(() => {
    return officerList.value.filter((o) => isOfficerDisabled(o)).length;
});

const filteredOfficers = computed(() => {
    let list = officerList.value;

    // Filter department
    if (selectedDept.value !== "ALL") {
        list = list.filter((o) => {
            const dept = (o.department || "").toUpperCase();
            if (selectedDept.value === "SAPR") {
                return dept === "SAPR" || dept === "PARK RANGER";
            }
            return dept === selectedDept.value;
        });
    }

    // Filter only hidden
    if (showOnlyHidden.value) {
        list = list.filter((o) => isOfficerDisabled(o));
    }

    // Filter search
    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter((o) => {
            const name = (o.officer_name || "").toLowerCase();
            const streamer = (o.streamer_name || "").toLowerCase();
            const callsign = (o.callsign || "").toLowerCase();
            const badge = (o.badge_number || "").toLowerCase();
            return (
                name.includes(q) ||
                streamer.includes(q) ||
                callsign.includes(q) ||
                badge.includes(q)
            );
        });
    }

    return list;
});

const departments = ["ALL", "LSPD", "BCSO", "SASP", "SAPR"];
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90dvh] flex flex-col p-0 sm:max-w-xl font-sans">
            <!-- Header -->
            <DialogHeader class="p-5 pb-3 border-b border-border text-left">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-md bg-primary/10 text-primary shrink-0">
                        <SlidersHorizontal class="size-4" />
                    </div>
                    <div>
                        <DialogTitle class="text-base font-bold text-foreground">
                            Kelola Visibilitas Perwira
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Sembunyikan siaran perwira tertentu dari feed Dashboard dan Multiview Anda.
                        </DialogDescription>
                    </div>
                </div>

                <!-- Search and Quick Filter Bar -->
                <div class="mt-4 space-y-2">
                    <div class="relative">
                        <Search class="size-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Cari perwira, callsign, atau badge..."
                            class="pl-9 h-8 text-xs bg-muted/50 border-input"
                        />
                    </div>

                    <!-- Department Tabs & Only Hidden Toggle -->
                    <div class="flex flex-wrap items-center justify-between gap-1.5 pt-1">
                        <div class="flex items-center gap-1 overflow-x-auto pb-1 max-w-full">
                            <Button
                                v-for="dept in departments"
                                :key="dept"
                                size="sm"
                                :variant="selectedDept === dept ? 'default' : 'outline'"
                                class="h-6 px-2 text-xs font-mono font-bold"
                                @click="selectedDept = dept"
                            >
                                {{ dept }}
                            </Button>
                        </div>

                        <Button
                            size="sm"
                            :variant="showOnlyHidden ? 'destructive' : 'ghost'"
                            class="h-6 px-2 text-xs gap-1 font-mono"
                            @click="showOnlyHidden = !showOnlyHidden"
                        >
                            <EyeOff class="size-3" />
                            <span>Disembunyikan ({{ hiddenCount }})</span>
                        </Button>
                    </div>
                </div>
            </DialogHeader>

            <!-- Officer List Body -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2 min-h-[260px] max-h-[50vh]">
                <div v-if="isLoading" class="flex flex-col items-center justify-center py-12 text-muted-foreground gap-2">
                    <Loader2 class="size-6 motion-safe:animate-spin text-primary" />
                    <span class="text-xs">Memuat daftar perwira...</span>
                </div>

                <div
                    v-else-if="filteredOfficers.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground"
                >
                    <EyeOff class="size-8 opacity-40 mb-2" />
                    <p class="text-sm font-semibold">Tidak ada perwira ditemukan</p>
                    <p class="text-xs mt-1">Coba sesuaikan kata kunci pencarian atau filter instansi Anda.</p>
                </div>

                <div
                    v-for="officer in filteredOfficers"
                    v-else
                    :key="officer.id"
                    class="flex items-center justify-between p-2.5 rounded-lg border border-border bg-card/60 hover:bg-accent/40 transition gap-3"
                >
                    <!-- Officer Info -->
                    <div class="flex items-center gap-3 min-w-0">
                        <OfficerAvatar
                            :src="officer.avatar_url"
                            :name="officer.officer_name || officer.streamer_name"
                            size="sm"
                            class="size-8 shrink-0"
                        />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-foreground truncate">
                                    {{ officer.officer_name || officer.streamer_name }}
                                </span>
                                <DeptBadge v-if="officer.department" :dept="officer.department" />
                            </div>
                            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5 font-mono">
                                <span v-if="officer.callsign">[{{ officer.callsign }}]</span>
                                <span v-if="officer.rank" class="truncate">{{ officer.rank }}</span>
                                <span v-if="officer.streamer_name && officer.streamer_name !== officer.officer_name" class="truncate opacity-70">
                                    · {{ officer.streamer_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Visibility Action Button -->
                    <Button
                        size="sm"
                        :variant="isOfficerDisabled(officer) ? 'destructive' : 'secondary'"
                        class="h-7 px-2.5 text-xs font-semibold shrink-0 gap-1.5"
                        @click="isOfficerDisabled(officer) ? unhideOfficer(officer) : hideOfficer(officer)"
                    >
                        <template v-if="isOfficerDisabled(officer)">
                            <EyeOff class="size-3.5" />
                            <span>Sembunyi</span>
                        </template>
                        <template v-else>
                            <Eye class="size-3.5 text-muted-foreground" />
                            <span>Tampil</span>
                        </template>
                    </Button>
                </div>
            </div>

            <!-- Footer -->
            <DialogFooter class="p-3 px-5 border-t border-border flex items-center justify-between sm:justify-between bg-muted/20">
                <div>
                    <Button
                        v-if="hiddenCount > 0"
                        size="sm"
                        variant="ghost"
                        class="h-8 text-xs text-muted-foreground hover:text-destructive gap-1.5 font-mono"
                        @click="resetAllFilters(true)"
                    >
                        <RotateCcw class="size-3" />
                        <span>Tampilkan Semua (Reset {{ hiddenCount }})</span>
                    </Button>
                </div>

                <Button
                    size="sm"
                    variant="outline"
                    class="h-8 text-xs font-semibold"
                    @click="emit('update:open', false)"
                >
                    Tutup
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

