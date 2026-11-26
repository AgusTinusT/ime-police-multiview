<?php

namespace App\Services;

use App\Models\LiveNotificationLog;
use Illuminate\Support\Collection;

class LiveLatencyAnalyticsService
{
    /**
     * Build latency summary, source breakdown, and recent logs.
     *
     * @return array{
     *     days: int,
     *     source: string,
     *     summary: array<string, int|float|null>,
     *     by_source: array<int, array<string, int|float|string|null>>,
     *     logs: array<int, array<string, mixed>>
     * }
     */
    public function buildReport(int $days = 7, string $source = 'all', int $limit = 50): array
    {
        $query = LiveNotificationLog::query()
            ->with(['officer:id,officer_name,callsign,department'])
            ->orderByDesc('detected_at');

        if ($days > 0) {
            $query->where('detected_at', '>=', now()->subDays($days));
        }

        if ($source !== '' && $source !== 'all') {
            $query->where('detection_source', $source);
        }

        /** @var Collection<int, LiveNotificationLog> $allLogs */
        $allLogs = $query->get();

        $detectionDelays = $allLogs
            ->pluck('detection_delay_seconds')
            ->filter(fn ($v) => $v !== null)
            ->values();

        $deliveryDelays = $allLogs
            ->pluck('delivery_delay_seconds')
            ->filter(fn ($v) => $v !== null)
            ->values();

        $totalDelays = $allLogs
            ->pluck('total_delay_seconds')
            ->filter(fn ($v) => $v !== null)
            ->values();

        $summary = [
            'total_notifications' => $allLogs->count(),
            'measured_count' => $detectionDelays->count(),
            'avg_detection_seconds' => $this->average($detectionDelays),
            'median_detection_seconds' => $this->percentile($detectionDelays, 50),
            'p95_detection_seconds' => $this->percentile($detectionDelays, 95),
            'avg_delivery_seconds' => $this->average($deliveryDelays),
            'avg_total_seconds' => $this->average($totalDelays),
        ];

        $bySource = $allLogs
            ->groupBy('detection_source')
            ->map(function (Collection $group, string $src): array {
                $delays = $group
                    ->pluck('detection_delay_seconds')
                    ->filter(fn ($v) => $v !== null)
                    ->values();

                return [
                    'source' => $src,
                    'count' => $group->count(),
                    'measured_count' => $delays->count(),
                    'avg_detection_seconds' => $this->average($delays),
                    'median_detection_seconds' => $this->percentile($delays, 50),
                    'p95_detection_seconds' => $this->percentile($delays, 95),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $recent = $allLogs
            ->take($limit)
            ->map(fn (LiveNotificationLog $log): array => [
                'id' => $log->id,
                'video_id' => $log->video_id,
                'title' => $log->title,
                'detection_source' => $log->detection_source,
                'youtube_started_at' => $log->youtube_started_at?->toIso8601String(),
                'detected_at' => $log->detected_at?->toIso8601String(),
                'notified_at' => $log->notified_at?->toIso8601String(),
                'detection_delay_seconds' => $log->detection_delay_seconds,
                'delivery_delay_seconds' => $log->delivery_delay_seconds,
                'total_delay_seconds' => $log->total_delay_seconds,
                'officer' => $log->officer ? [
                    'id' => $log->officer->id,
                    'officer_name' => $log->officer->officer_name,
                    'callsign' => $log->officer->callsign,
                    'department' => $log->officer->department,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'days' => $days,
            'source' => $source,
            'summary' => $summary,
            'by_source' => $bySource,
            'logs' => $recent,
        ];
    }

    private function average(Collection $values): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        return (int) round($values->avg());
    }

    private function percentile(Collection $values, int $percent): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $index = (int) ceil(($percent / 100) * $sorted->count()) - 1;
        $index = max(0, min($sorted->count() - 1, $index));

        return (int) $sorted[$index];
    }
}
