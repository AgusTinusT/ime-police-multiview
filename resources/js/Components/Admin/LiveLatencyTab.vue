<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import { StatTile, DeptBadge, EmptyState } from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Skeleton } from "@/Components/ui/skeleton";
import {
    Activity,
    Clock,
    ExternalLink,
    Gauge,
    Radio,
    RefreshCw,
    Send,
} from "@lucide/vue";

const emit = defineEmits(["toast"]);

const days = ref("7");
const source = ref("all");
const loading = ref(false);
const report = ref({
    summary: {
        total_notifications: 0,
        measured_count: 0,
        avg_detection_seconds: null,
        median_detection_seconds: null,
        p95_detection_seconds: null,
        avg_delivery_seconds: null,
        avg_total_seconds: null,
    },
    by_source: [],
    logs: [],
});

const sourceLabels = {
    websub: "WebSub Push",
    websub_recheck: "WebSub Re-check",
    sync_hashtag: "Sync Hashtag",
    sync_rss: "Sync RSS",
    sync_handle: "Sync Handle",
    sync: "Sync Fallback",
};

const formatSource = (src) => sourceLabels[src] || src;

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined) return "—";
    if (seconds < 60) return `${seconds} dtk`;
    const mins = Math.floor(seconds / 60);
    const rem = seconds % 60;
    return `${mins}m ${rem}d`;
};

const formatDateTime = (iso) => {
    if (!iso) return "—";
    try {
        return new Date(iso).toLocaleString("id-ID", {
            day: "2-digit",
            month: "short",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
    } catch {
        return iso;
    }
};

const delayBadgeVariant = (seconds) => {
    if (seconds === null || seconds === undefined) return "secondary";
    if (seconds <= 60) return "success";
    if (seconds <= 180) return "warning";
    return "destructive";
};

const sourceBadgeVariant = (src) => {
    if (src === "websub" || src === "websub_recheck") return "info";
    return "secondary";
};

const fetchReport = async () => {
    loading.value = true;
    try {
        const res = await axios.get("/api/v1/admin/live-latency", {
            params: {
                days: Number(days.value),
                source: source.value,
                limit: 50,
            },
        });
        report.value = res.data;
    } catch (e) {
        emit("toast", {
            type: "error",
            message:
                e.response?.data?.message ||
                "Gagal memuat laporan latensi notifikasi live.",
        });
    } finally {
        loading.value = false;
    }
};

onMounted(fetchReport);
</script>

<template>
    <div class="space-y-6">
        <!-- Filter & Action Bar -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
        >
            <div>
                <h2
                    class="text-lg font-bold tracking-tight text-foreground flex items-center gap-2"
                >
                    <Activity class="size-5 text-primary" />
                    Latensi Deteksi &amp; Notifikasi Live
                </h2>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Perbandingan jam mulai live asli dari YouTube terhadap waktu
                    deteksi dan pengiriman notifikasi website.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="days" @update:model-value="fetchReport">
                    <SelectTrigger class="w-36 h-9 text-xs">
                        <SelectValue placeholder="Rentang" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="1">24 Jam Terakhir</SelectItem>
                        <SelectItem value="7">7 Hari Terakhir</SelectItem>
                        <SelectItem value="30">30 Hari Terakhir</SelectItem>
                        <SelectItem value="0">Semua Waktu</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="source" @update:model-value="fetchReport">
                    <SelectTrigger class="w-40 h-9 text-xs">
                        <SelectValue placeholder="Sumber" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Sumber</SelectItem>
                        <SelectItem value="websub">WebSub Push</SelectItem>
                        <SelectItem value="websub_recheck"
                            >WebSub Re-check</SelectItem
                        >
                        <SelectItem value="sync_hashtag"
                            >Sync Hashtag</SelectItem
                        >
                        <SelectItem value="sync_rss">Sync RSS</SelectItem>
                        <SelectItem value="sync_handle">Sync Handle</SelectItem>
                        <SelectItem value="sync">Sync Fallback</SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="loading"
                    @click="fetchReport"
                >
                    <RefreshCw
                        :class="[
                            'size-4',
                            loading && 'motion-safe:animate-spin',
                        ]"
                    />
                    Muat Ulang
                </Button>
            </div>
        </div>

        <!-- KPI Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <StatTile
                label="Total Notifikasi"
                :value="report.summary.total_notifications"
                :hint="`${report.summary.measured_count} terukur dari YouTube`"
                :icon="Radio"
            />
            <StatTile
                label="Rata-rata Deteksi"
                :value="formatDuration(report.summary.avg_detection_seconds)"
                hint="YouTube mulai → terdeteksi"
                :icon="Clock"
            />
            <StatTile
                label="Median (p50) / p95"
                :value="`${formatDuration(report.summary.median_detection_seconds)} / ${formatDuration(report.summary.p95_detection_seconds)}`"
                hint="Distribusi delay deteksi"
                :icon="Gauge"
            />
            <StatTile
                label="Rata-rata Kirim"
                :value="formatDuration(report.summary.avg_delivery_seconds)"
                :hint="`Total rata-rata: ${formatDuration(report.summary.avg_total_seconds)}`"
                :icon="Send"
            />
        </div>

        <!-- Per-Source Breakdown -->
        <Card v-if="report.by_source.length > 0">
            <CardHeader class="pb-3">
                <CardTitle class="text-sm">
                    Perbandingan per Sumber Deteksi
                </CardTitle>
                <CardDescription class="text-xs">
                    Membandingkan kecepatan WebSub real-time vs polling cron
                    1-menit.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Sumber</TableHead>
                                <TableHead class="text-right">Jumlah</TableHead>
                                <TableHead class="text-right"
                                    >Terukur</TableHead
                                >
                                <TableHead class="text-right"
                                    >Avg Deteksi</TableHead
                                >
                                <TableHead class="text-right"
                                    >Median (p50)</TableHead
                                >
                                <TableHead class="text-right">p95</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="row in report.by_source"
                                :key="row.source"
                            >
                                <TableCell>
                                    <Badge
                                        :variant="
                                            sourceBadgeVariant(row.source)
                                        "
                                    >
                                        {{ formatSource(row.source) }}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-xs"
                                >
                                    {{ row.count }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-xs text-muted-foreground"
                                >
                                    {{ row.measured_count }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Badge
                                        :variant="
                                            delayBadgeVariant(
                                                row.avg_detection_seconds,
                                            )
                                        "
                                    >
                                        {{
                                            formatDuration(
                                                row.avg_detection_seconds,
                                            )
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-xs"
                                >
                                    {{
                                        formatDuration(
                                            row.median_detection_seconds,
                                        )
                                    }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-xs text-muted-foreground"
                                >
                                    {{
                                        formatDuration(
                                            row.p95_detection_seconds,
                                        )
                                    }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Recent Notification Logs -->
        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="text-sm">
                    Riwayat Perbandingan Notifikasi Live
                </CardTitle>
                <CardDescription class="text-xs">
                    Hijau ≤ 60 detik · Kuning 1–3 menit · Merah &gt; 3 menit.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="loading" class="space-y-2">
                    <Skeleton class="h-10 w-full" />
                    <Skeleton class="h-10 w-full" />
                    <Skeleton class="h-10 w-full" />
                </div>

                <EmptyState
                    v-else-if="report.logs.length === 0"
                    :icon="Activity"
                    title="Belum ada data latensi"
                    description="Data akan tercatat otomatis setiap kali sistem mendeteksi perwira mulai live."
                />

                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Perwira &amp; Stream</TableHead>
                                <TableHead>Sumber</TableHead>
                                <TableHead>Mulai YouTube</TableHead>
                                <TableHead>Terdeteksi</TableHead>
                                <TableHead class="text-right"
                                    >Delay Deteksi</TableHead
                                >
                                <TableHead class="text-right"
                                    >Delay Kirim</TableHead
                                >
                                <TableHead class="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="log in report.logs"
                                :key="log.id"
                            >
                                <TableCell class="max-w-xs">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-semibold text-xs text-foreground truncate"
                                        >
                                            {{
                                                log.officer?.officer_name ||
                                                "Unknown"
                                            }}
                                        </span>
                                        <DeptBadge
                                            v-if="log.officer?.department"
                                            :dept="log.officer.department"
                                        />
                                    </div>
                                    <div
                                        class="flex items-center gap-1.5 mt-0.5 text-xs text-muted-foreground"
                                    >
                                        <span class="truncate">{{
                                            log.title || log.video_id
                                        }}</span>
                                        <a
                                            :href="`https://www.youtube.com/watch?v=${log.video_id}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-primary hover:underline shrink-0"
                                            aria-label="Buka video YouTube"
                                        >
                                            <ExternalLink class="size-3" />
                                        </a>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            sourceBadgeVariant(
                                                log.detection_source,
                                            )
                                        "
                                    >
                                        {{ formatSource(log.detection_source) }}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    class="font-mono text-xs text-muted-foreground whitespace-nowrap"
                                >
                                    {{ formatDateTime(log.youtube_started_at) }}
                                </TableCell>
                                <TableCell
                                    class="font-mono text-xs text-foreground whitespace-nowrap"
                                >
                                    {{ formatDateTime(log.detected_at) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Badge
                                        :variant="
                                            delayBadgeVariant(
                                                log.detection_delay_seconds,
                                            )
                                        "
                                    >
                                        {{
                                            formatDuration(
                                                log.detection_delay_seconds,
                                            )
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-xs text-muted-foreground whitespace-nowrap"
                                >
                                    {{
                                        formatDuration(
                                            log.delivery_delay_seconds,
                                        )
                                    }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-xs font-semibold text-foreground whitespace-nowrap"
                                >
                                    {{
                                        formatDuration(log.total_delay_seconds)
                                    }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
