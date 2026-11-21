<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TacClip extends Model
{
    use HasFactory;

    protected $table = 'tac_clips';

    protected $fillable = [
        'user_id',
        'officer_id',
        'video_id',
        'title',
        'start_seconds',
        'end_seconds',
        'officer_name',
        'officer_handle',
        'creator_name',
        'likes_count',
        'views_count',
        'is_approved',
        'archive_status',
        'archive_disk',
        'archive_path',
        'archive_size',
        'archive_attempts',
        'archive_error',
        'archived_at',
    ];

    protected $casts = [
        'officer_id' => 'integer',
        'start_seconds' => 'integer',
        'end_seconds' => 'integer',
        'likes_count' => 'integer',
        'views_count' => 'integer',
        'is_approved' => 'boolean',
        'archive_size' => 'integer',
        'archive_attempts' => 'integer',
        'archived_at' => 'datetime',
    ];

    public const ARCHIVE_PENDING = 'pending';
    public const ARCHIVE_PROCESSING = 'processing';
    public const ARCHIVE_STORED = 'stored';
    public const ARCHIVE_FAILED = 'failed';
    public const ARCHIVE_EXPIRED = 'expired';

    /**
     * Short-lived signed link to the archived 720p copy, or null when there is none
     * (not archived yet, expired, or the disk cannot sign URLs).
     */
    public function archiveUrl(): ?string
    {
        if ($this->archive_status !== self::ARCHIVE_STORED || ! $this->archive_path || ! $this->archive_disk) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk($this->archive_disk)->temporaryUrl(
                $this->archive_path,
                now()->addMinutes((int) config('momen.archive_url_minutes', 120)),
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Day the archived file will be removed (the moment row itself stays).
     */
    public function archiveExpiresAt(): ?\Illuminate\Support\Carbon
    {
        return $this->archived_at?->copy()->addDays((int) config('momen.archive_days', 30));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function officer()
    {
        return $this->belongsTo(Officer::class);
    }
}
