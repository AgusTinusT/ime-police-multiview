<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_id',
        'channel_id',
        'video_id',
        'title',
        'thumbnail_url',
        'status',
        'incident_code',
        'description',
        'viewers_count',
        'last_synced_at',
        'live_notified_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'live_notified_at' => 'datetime',
        'viewers_count' => 'integer',
        'officer_id' => 'integer',
    ];

    /**
     * Get the officer associated with the active stream.
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id', 'id');
    }

    /**
     * Scope a query to only include live streams.
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'LIVE');
    }
}
