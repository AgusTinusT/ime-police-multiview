<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TacChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'video_ids',
        'expires_at',
    ];

    protected $casts = [
        'video_ids' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $appends = [
        'remaining_seconds',
        'is_active',
        'unit_count',
    ];

    /**
     * Get remaining seconds until expiration.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        $diff = now()->diffInSeconds($this->expires_at, false);
        return max(0, (int) $diff);
    }

    /**
     * Check if TAC channel is actively in use.
     */
    public function getIsActiveAttribute(): bool
    {
        $videoIds = $this->video_ids ?? [];
        return !empty($videoIds) && $this->remaining_seconds > 0;
    }

    /**
     * Get count of assigned stream units.
     */
    public function getUnitCountAttribute(): int
    {
        $videoIds = $this->video_ids ?? [];
        return count($videoIds);
    }

    /**
     * Check and auto-reset channel if it has expired.
     */
    public function checkAndResetIfExpired(): bool
    {
        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            $this->video_ids = [];
            $this->expires_at = null;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Ensure standard 10 TAC channels exist in database.
     */
    public static function ensureChannelsExist(): void
    {
        $codes = ['TAC_1', 'TAC_2', 'TAC_3', 'TAC_4', 'TAC_5', 'TAC_6', 'TAC_7', 'TAC_8', 'TAC_9', 'TAC_10'];
        $names = [
            'TAC_1' => 'TAC 1',
            'TAC_2' => 'TAC 2',
            'TAC_3' => 'TAC 3',
            'TAC_4' => 'TAC 4',
            'TAC_5' => 'TAC 5',
            'TAC_6' => 'TAC 6',
            'TAC_7' => 'TAC 7',
            'TAC_8' => 'TAC 8',
            'TAC_9' => 'TAC 9',
            'TAC_10' => 'TAC 10',
        ];

        foreach ($codes as $code) {
            static::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $names[$code] ?? $code,
                    'video_ids' => [],
                    'expires_at' => null,
                ]
            );
        }
    }
}
