<script setup>
import { ref, computed, nextTick } from "vue";
import { Trash2 } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Separator } from "@/Components/ui/separator";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
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
import { DeptBadge } from "@/Components/tactical";

const props = defineProps({
    officer: {
        type: Object,
        default: () => ({}),
    },
    stream: {
        type: Object,
        default: () => ({}),
    },
});

const certifications = [
    { code: "CERT-HPD", title: "High Pursuit Driver", status: "Aktif" },
    { code: "CERT-ASL", title: "Air Support Lead", status: "Aktif" },
    { code: "CERT-NEG", title: "Negotiator", status: "Aktif" },
];

// Mock initial impressions list (YouTube comment style)
const impressions = ref([
    {
        id: 1,
        author_name: "Dispatcher_Alpha9",
        author_role: "Senior Dispatcher",
        impression_type: "TACTICAL_RATING",
        content:
            "Navigasi mengemudi saat 10-80 sangat presisi. Selalu memberikan pit maneuver dengan pertimbangan keamanan warga sipil yang baik.",
        likes_count: 24,
        is_liked: false,
        created_at: "2 jam lalu",
    },
    {
        id: 2,
        author_name: "Citizen_V",
        author_role: "Warga",
        impression_type: "FUNNY_MOMENT",
        content:
            "Lucu waktu negosiasi perampokan bank kemarin, perwira ini tetap tenang menangani suspek dengan komedi ringan.",
        likes_count: 42,
        is_liked: true,
        created_at: "5 jam lalu",
    },
    {
        id: 3,
        author_name: "Trooper_K-9",
        author_role: "Verified Officer",
        impression_type: "COMMUNITY_NOTE",
        content:
            "Perwira senior dengan dedikasi tinggi. Selalu siap memimpin respon awal saat sinyal 10-99 (baku tembak) berbunyi di Paleto.",
        likes_count: 18,
        is_liked: false,
        created_at: "1 hari lalu",
    },
]);

const sortOrder = ref("newest");

const sortedImpressions = computed(() => {
    const list = [...impressions.value];
    if (sortOrder.value === "oldest") {
        return list.reverse();
    }
    return list;
});

// YouTube Comment Input Form State
const isCommentInputFocused = ref(false);
const newCommentContent = ref("");
const newCommentAuthor = ref("");
const newCommentType = ref("COMMUNITY_NOTE");

const commentTextareaRef = ref(null);

const focusCommentInput = () => {
    isCommentInputFocused.value = true;
    nextTick(() => {
        if (commentTextareaRef.value) {
            const el =
                commentTextareaRef.value.$el || commentTextareaRef.value;
            if (el && typeof el.focus === "function") el.focus();
        }
    });
};

const cancelComment = () => {
    isCommentInputFocused.value = false;
    newCommentContent.value = "";
    newCommentAuthor.value = "";
};

const submitComment = () => {
    if (!newCommentContent.value.trim()) return;

    impressions.value.unshift({
        id: Date.now(),
        author_name: newCommentAuthor.value.trim() || "Penonton Komunitas",
        author_role: "Penonton",
        impression_type: newCommentType.value,
        content: newCommentContent.value.trim(),
        likes_count: 0,
        is_liked: false,
        created_at: "Baru saja",
    });

    newCommentContent.value = "";
    isCommentInputFocused.value = false;
    sortOrder.value = "newest";
};

defineExpose({
    focusCommentInput,
});

const pendingDeleteId = ref(null);

const deleteImpression = (id) => {
    pendingDeleteId.value = id;
};

const confirmDeleteImpression = () => {
    if (pendingDeleteId.value !== null) {
        impressions.value = impressions.value.filter(
            (i) => i.id !== pendingDeleteId.value,
        );
        pendingDeleteId.value = null;
    }
};
</script>

<template>
    <div class="space-y-5 font-sans">
        <!-- TACTICAL DOSSIER HEADER CARD -->
        <Card class="p-5 space-y-4">
            <div
                class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
            >
                <div class="flex items-center space-x-3.5">
                    <div
                        class="w-12 h-12 rounded-sm bg-background border border-border flex items-center justify-center font-bold text-base text-foreground font-mono shrink-0"
                    >
                        {{
                            officer?.callsign ||
                            stream?.officer?.callsign ||
                            "SLT"
                        }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <DeptBadge
                                :dept="
                                    officer?.department ||
                                    stream?.officer?.department ||
                                    'SASP'
                                "
                            />
                            <span
                                class="text-xs font-mono text-muted-foreground"
                            >
                                Badge #{{
                                    officer?.badge_number ||
                                    stream?.officer?.badge_number ||
                                    "402"
                                }}
                            </span>
                        </div>
                        <h2
                            class="text-base font-semibold text-foreground mt-1"
                        >
                            {{
                                officer?.officer_name ||
                                stream?.officer?.officer_name ||
                                "Senior Lead Officer"
                            }}
                        </h2>
                        <p
                            class="text-xs text-muted-foreground font-mono mt-0.5"
                        >
                            {{
                                officer?.rank ||
                                stream?.officer?.rank ||
                                "Senior Lead Trooper"
                            }}
                        </p>
                    </div>
                </div>

                <!-- SUBTLE METRICS BADGES -->
                <div
                    class="grid grid-cols-2 gap-2 w-full md:w-auto font-mono text-center text-xs"
                >
                    <div
                        class="bg-background border border-border p-2.5 rounded-md"
                    >
                        <span class="block text-xs text-muted-foreground"
                            >STATUS PATROLI</span
                        >
                        <span
                            class="text-xs font-medium text-foreground mt-0.5 block"
                            >10-8 ACTIVE</span
                        >
                    </div>
                    <div
                        class="bg-background border border-border p-2.5 rounded-md"
                    >
                        <span class="block text-xs text-muted-foreground"
                            >CATATAN KOMUNITAS</span
                        >
                        <span
                            class="text-xs font-medium text-foreground mt-0.5 block"
                            >{{ impressions.length }} Entri</span
                        >
                    </div>
                </div>
            </div>

            <Separator />

            <!-- CERTIFICATIONS TABLE -->
            <div class="space-y-2 font-mono text-xs">
                <span class="text-foreground font-medium block"
                    >Sertifikasi Taktis:</span
                >
                <div class="rounded-md border border-border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/40">
                                <TableHead class="h-8 py-1 px-3 text-xs"
                                    >Kode</TableHead
                                >
                                <TableHead class="h-8 py-1 px-3 text-xs"
                                    >Sertifikasi</TableHead
                                >
                                <TableHead
                                    class="h-8 py-1 px-3 text-right text-xs"
                                    >Status</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="cert in certifications"
                                :key="cert.code"
                            >
                                <TableCell
                                    class="py-1.5 px-3 text-xs text-muted-foreground"
                                    >{{ cert.code }}</TableCell
                                >
                                <TableCell
                                    class="py-1.5 px-3 text-xs font-medium text-foreground"
                                    >{{ cert.title }}</TableCell
                                >
                                <TableCell
                                    class="py-1.5 px-3 text-right text-xs"
                                >
                                    <Badge
                                        variant="success"
                                        class="text-xs px-1.5 py-0"
                                        >{{ cert.status }}</Badge
                                    >
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </Card>

        <!-- COMMUNITY IMPRESSIONS FEED SECTION -->
        <div class="space-y-4">
            <!-- SECTION TITLE & SORT FILTER -->
            <div
                class="flex items-center justify-between flex-wrap gap-3 border-b border-border pb-3"
            >
                <div class="flex items-center space-x-2">
                    <h3
                        class="text-xs font-medium text-foreground uppercase tracking-tight font-mono flex items-center gap-2"
                    >
                        <span>Kesan & Lore Komunitas</span>
                        <Badge variant="outline" class="font-mono text-xs">
                            {{ impressions.length }}
                        </Badge>
                    </h3>
                </div>

                <!-- SORT FILTER (Terbaru / Terlama) -->
                <div class="flex items-center space-x-1 text-xs font-mono">
                    <span class="text-muted-foreground mr-1.5 text-xs"
                        >Urutkan:</span
                    >
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            sortOrder === 'newest' ? 'secondary' : 'outline'
                        "
                        @click="sortOrder = 'newest'"
                        class="h-7 px-2.5 text-xs"
                    >
                        Terbaru
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            sortOrder === 'oldest' ? 'secondary' : 'outline'
                        "
                        @click="sortOrder = 'oldest'"
                        class="h-7 px-2.5 text-xs"
                    >
                        Terlama
                    </Button>
                </div>
            </div>

            <!-- YOUTUBE-STYLE INLINE COMMENT POSTING BOX -->
            <Card class="p-3.5 space-y-3">
                <div class="flex items-start space-x-3">
                    <div
                        class="w-8 h-8 rounded-sm bg-background border border-border flex items-center justify-center font-mono font-medium text-xs text-muted-foreground shrink-0 mt-0.5"
                    >
                        {{
                            newCommentAuthor
                                ? newCommentAuthor.charAt(0).toUpperCase()
                                : "U"
                        }}
                    </div>

                    <div class="flex-1 space-y-2.5">
                        <!-- UNCOLLAPSED / COLLAPSED INPUT -->
                        <div
                            v-if="!isCommentInputFocused"
                            @click="focusCommentInput"
                            class="w-full bg-background border border-border rounded-md px-3 py-2 text-muted-foreground text-xs cursor-text font-mono hover:border-primary/40 transition-colors"
                        >
                            Buat catatan kesan tentang perwira ini...
                        </div>

                        <div
                            v-else
                            class="space-y-3 animate-in fade-in duration-150"
                        >
                            <Textarea
                                ref="commentTextareaRef"
                                v-model="newCommentContent"
                                rows="3"
                                placeholder="Buat catatan kesan atau rekap RP perwira ini..."
                                class="text-xs font-mono resize-none leading-relaxed"
                            />

                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pt-1"
                            >
                                <div
                                    class="flex items-center space-x-2 text-xs font-mono"
                                >
                                    <Input
                                        v-model="newCommentAuthor"
                                        type="text"
                                        placeholder="Panggilan Anda (Opsional)"
                                        class="h-8 text-xs w-48"
                                    />
                                </div>

                                <!-- ACTION BUTTONS -->
                                <div
                                    class="flex items-center space-x-2 justify-end"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="cancelComment"
                                        class="h-8 text-xs"
                                    >
                                        Batal
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        @click="submitComment"
                                        :disabled="!newCommentContent.trim()"
                                        class="h-8 text-xs"
                                    >
                                        Kirim Kesan
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- IMPRESSIONS FEED LIST -->
            <div class="space-y-3">
                <Card
                    v-for="item in sortedImpressions"
                    :key="item.id"
                    class="p-4 space-y-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2.5">
                            <div
                                class="w-7 h-7 rounded-sm bg-background border border-border flex items-center justify-center font-mono font-medium text-xs text-foreground"
                            >
                                {{ item.author_name.charAt(0) }}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span
                                        class="text-xs font-medium text-foreground font-mono"
                                        >{{ item.author_name }}</span
                                    >
                                    <Badge
                                        variant="outline"
                                        class="text-xs px-1.5 py-0 font-mono text-muted-foreground"
                                    >
                                        {{ item.author_role }}
                                    </Badge>
                                </div>
                                <span
                                    class="text-xs font-mono text-muted-foreground block"
                                    >{{ item.created_at }}</span
                                >
                            </div>
                        </div>

                        <!-- ADMIN ACTION: DELETE COMMENT -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="deleteImpression(item.id)"
                            class="h-7 px-2 text-muted-foreground hover:text-destructive text-xs font-mono gap-1"
                            title="Hapus Komentar (Akses Admin)"
                        >
                            <Trash2 class="w-3 h-3" />
                            <span>Hapus</span>
                        </Button>
                    </div>

                    <!-- IMPRESSION CONTENT TEXT -->
                    <p
                        class="text-xs text-foreground font-mono leading-relaxed bg-background p-3 rounded-md border border-border"
                    >
                        "{{ item.content }}"
                    </p>
                </Card>

                <Card
                    v-if="sortedImpressions.length === 0"
                    class="p-8 text-center text-muted-foreground text-xs font-mono"
                >
                    Belum ada kesan untuk perwira ini.
                </Card>
            </div>
        </div>

        <AlertDialog
            :open="pendingDeleteId !== null"
            @update:open="(val) => { if (!val) pendingDeleteId = null; }"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Catatan Kesan?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus catatan kesan ini? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="pendingDeleteId = null">
                        Batal
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="confirmDeleteImpression"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
