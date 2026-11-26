<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_id',
        'video_id',
        'title',
        'detection_source',
        'youtube_started_at',
        'detected_at',
        'notified_at',
        'detection_delay_seconds',
        'delivery_delay_seconds',
        'total_delay_seconds',
    ];

    protected $casts = [
        'officer_id' => 'integer',
        'youtube_started_at' => 'datetime',
        'detected_at' => 'datetime',
        'notified_at' => 'datetime',
        'detection_delay_seconds' => 'integer',
        'delivery_delay_seconds' => 'integer',
        'total_delay_seconds' => 'integer',
    ];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}
