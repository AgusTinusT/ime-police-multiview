<?php

namespace App\Console\Commands;

use App\Services\LiveLatencyAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class LiveLatencyReportCommand extends Command
{
    protected $signature = 'live:latency-report {--days=7 : Rentang hari (0 = semua)} {--source=all : Filter sumber deteksi} {--limit=25 : Jumlah baris terbaru}';

    protected $description = 'Tampilkan laporan latensi deteksi & pengiriman notifikasi live dibanding jam mulai YouTube';

    public function handle(LiveLatencyAnalyticsService $analytics): int
    {
        $days = max(0, (int) $this->option('days'));
        $source = (string) $this->option('source');
        $limit = max(1, min(100, (int) $this->option('limit')));

        $report = $analytics->buildReport($days, $source, $limit);
        $summary = $report['summary'];

        $periodLabel = $days > 0 ? "{$days} hari terakhir" : 'semua waktu';
        $this->info("=== Laporan Latensi Notifikasi Live ({$periodLabel}) ===");
        $this->line(sprintf(
            'Total notifikasi: %d | Terukur (ada jam mulai YT): %d | Rata-rata deteksi: %s | Median (p50): %s | p95: %s | Rata-rata kirim: %s',
            $summary['total_notifications'],
            $summary['measured_count'],
            $this->formatSeconds($summary['avg_detection_seconds']),
            $this->formatSeconds($summary['median_detection_seconds']),
            $this->formatSeconds($summary['p95_detection_seconds']),
            $this->formatSeconds($summary['avg_delivery_seconds']),
        ));

        if (! empty($report['by_source'])) {
            $this->newLine();
            $this->info('Perbandingan per Sumber Deteksi:');
            $this->table(
                ['Sumber', 'Jumlah', 'Terukur', 'Avg Deteksi', 'Median (p50)', 'p95'],
                array_map(fn (array $row): array => [
                    $row['source'],
                    $row['count'],
                    $row['measured_count'],
                    $this->formatSeconds($row['avg_detection_seconds']),
                    $this->formatSeconds($row['median_detection_seconds']),
                    $this->formatSeconds($row['p95_detection_seconds']),
                ], $report['by_source']),
            );
        }

        if (empty($report['logs'])) {
            $this->warn('Belum ada data notifikasi live pada rentang ini.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Notifikasi Terbaru:');
        $this->table(
            ['Perwira', 'Sumber', 'Mulai YouTube', 'Terdeteksi', 'Delay Deteksi', 'Delay Kirim', 'Total'],
            array_map(fn (array $log): array => [
                $log['officer']['officer_name'] ?? ('#' . $log['video_id']),
                $log['detection_source'],
                $this->formatTime($log['youtube_started_at']),
                $this->formatTime($log['detected_at']),
                $this->formatSeconds($log['detection_delay_seconds']),
                $this->formatSeconds($log['delivery_delay_seconds']),
                $this->formatSeconds($log['total_delay_seconds']),
            ], $report['logs']),
        );

        return self::SUCCESS;
    }

    private function formatSeconds(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        if ($seconds < 60) {
            return "{$seconds}d";
        }
        $mins = intdiv($seconds, 60);
        $rem = $seconds % 60;

        return "{$mins}m {$rem}d";
    }

    private function formatTime(?string $iso): string
    {
        if (! $iso) {
            return '—';
        }

        return Carbon::parse($iso)->timezone('Asia/Jakarta')->format('d/m H:i:s');
    }
}
