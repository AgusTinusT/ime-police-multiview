<script setup>
import { onMounted, ref, watch } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import {
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    GitFork,
    RefreshCw,
    Search,
    Users,
} from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Switch } from "@/Components/ui/switch";
import { Input } from "@/Components/ui/input";
import { Card } from "@/Components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { SectionHeader } from "@/Components/tactical";

const emit = defineEmits(["toast"]);

const squads = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
});
const searchQuery = ref("");
const isLoading = ref(false);
const togglingId = ref(null);

let searchTimeout = null;

const fetchSquads = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {
            page,
            search: searchQuery.value.trim() || undefined,
        };
        const res = await axios.get("/api/v1/admin/squads", { params });
        if (res.data?.status === "success") {
            squads.value = res.data.data || [];
            pagination.value = res.data.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
            };
        }
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal memuat daftar squad.";
        toast.error(msg);
        emit("toast", msg, "error");
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchSquads();
});

watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchSquads(1);
    }, 300);
});

const toggleHide = async (squad) => {
    togglingId.value = squad.id;
    try {
        const res = await axios.patch(`/api/v1/admin/squads/${squad.id}/hide`);
        squad.is_hidden = res.data?.is_hidden;
        const msg = res.data?.message || (squad.is_hidden ? "Squad disembunyikan." : "Squad ditampilkan kembali.");
        toast.success(msg);
        emit("toast", msg, "success");
    } catch (err) {
        const msg = err.response?.data?.message || "Gagal mengubah status squad.";
        toast.error(msg);
        emit("toast", msg, "error");
    } finally {
        togglingId.value = null;
    }
};

const formatDate = (isoStr) => {
    if (!isoStr) return "-";
    try {
        return new Date(isoStr).toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    } catch {
        return isoStr;
    }
};
</script>

<template>
    <div class="space-y-6 font-mono text-xs">
        <SectionHeader
            title="Moderasi Squad Komunitas"
            description="Pantau dan sembunyikan squad publik dari Dashboard jika melanggar ketentuan."
        >
            <template #actions>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1.5 font-mono text-xs"
                    :disabled="isLoading"
                    @click="fetchSquads(pagination.current_page)"
                >
                    <RefreshCw class="size-3.5" :class="{ 'motion-safe:animate-spin': isLoading }" />
                    <span>Refresh</span>
                </Button>
            </template>
        </SectionHeader>

        <!-- Filters & Search -->
        <Card class="p-4">
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <Search class="size-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari berdasarkan nama squad..."
                        class="pl-9 h-9 text-xs font-mono"
                    />
                </div>
                <div class="text-xs text-muted-foreground whitespace-nowrap">
                    Total: <strong class="text-foreground">{{ pagination.total }}</strong> squad publik
                </div>
            </div>
        </Card>

        <!-- Squads Table -->
        <Card class="overflow-hidden">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/40 uppercase text-xs font-mono">
                            <TableHead>Nama Squad</TableHead>
                            <TableHead>Pemilik</TableHead>
                            <TableHead class="text-center">Jumlah Anggota</TableHead>
                            <TableHead class="text-center">Fork</TableHead>
                            <TableHead>Dibuat</TableHead>
                            <TableHead class="text-right">Disembunyikan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="squad in squads"
                            :key="squad.id"
                            :class="{ 'opacity-60 bg-muted/20': squad.is_hidden }"
                        >
                            <!-- Nama & Link -->
                            <TableCell>
                                <div class="space-y-0.5">
                                    <a
                                        :href="`/multiview?squad=${squad.slug}`"
                                        target="_blank"
                                        class="font-bold text-foreground text-sm hover:text-primary hover:underline inline-flex items-center gap-1.5"
                                        title="Buka squad di Multiview"
                                    >
                                        <span>{{ squad.name }}</span>
                                        <ExternalLink class="size-3 text-muted-foreground shrink-0" />
                                    </a>
                                    <p v-if="squad.description" class="text-xs text-muted-foreground line-clamp-1 max-w-xs">
                                        {{ squad.description }}
                                    </p>
                                    <span class="text-xs text-muted-foreground font-mono block">
                                        /@{{ squad.slug }}
                                    </span>
                                </div>
                            </TableCell>

                            <!-- Pemilik -->
                            <TableCell class="font-mono text-xs">
                                <span class="font-medium text-foreground">{{ squad.owner_name }}</span>
                            </TableCell>

                            <!-- Jumlah Anggota -->
                            <TableCell class="text-center font-mono text-xs">
                                <span class="inline-flex items-center gap-1">
                                    <Users class="size-3 text-muted-foreground" />
                                    <span>{{ squad.officers_count }}</span>
                                </span>
                            </TableCell>

                            <!-- Fork -->
                            <TableCell class="text-center font-mono text-xs">
                                <span class="inline-flex items-center gap-1">
                                    <GitFork class="size-3 text-muted-foreground" />
                                    <span>{{ squad.forks_count }}</span>
                                </span>
                            </TableCell>

                            <!-- Dibuat -->
                            <TableCell class="font-mono text-xs text-muted-foreground">
                                {{ formatDate(squad.created_at) }}
                            </TableCell>

                            <!-- Disembunyikan Switch -->
                            <TableCell class="text-right">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    <Badge
                                        :variant="squad.is_hidden ? 'destructive' : 'secondary'"
                                        class="font-mono text-xs font-bold"
                                    >
                                        {{ squad.is_hidden ? "HIDDEN" : "VISIBLE" }}
                                    </Badge>
                                    <Switch
                                        :model-value="Boolean(squad.is_hidden)"
                                        :disabled="togglingId === squad.id"
                                        :aria-label="`Toggle sembunyikan ${squad.name}`"
                                        @update:model-value="toggleHide(squad)"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!isLoading && squads.length === 0">
                            <TableCell colspan="6" class="h-32 text-center text-muted-foreground font-mono">
                                Tidak ada squad publik yang ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Footer -->
            <div
                v-if="pagination.last_page > 1"
                class="flex items-center justify-between p-4 border-t border-border font-mono text-xs"
            >
                <span class="text-muted-foreground">
                    Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
                </span>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1 font-mono text-xs"
                        :disabled="pagination.current_page <= 1 || isLoading"
                        @click="fetchSquads(pagination.current_page - 1)"
                    >
                        <ChevronLeft class="size-3.5" />
                        <span>Sebelumnya</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1 font-mono text-xs"
                        :disabled="pagination.current_page >= pagination.last_page || isLoading"
                        @click="fetchSquads(pagination.current_page + 1)"
                    >
                        <span>Selanjutnya</span>
                        <ChevronRight class="size-3.5" />
                    </Button>
                </div>
            </div>
        </Card>
    </div>
</template>
