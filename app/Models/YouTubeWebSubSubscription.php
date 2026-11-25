<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouTubeWebSubSubscription extends Model
{
    protected $table = 'youtube_websub_subscriptions';

    protected $fillable = [
        'officer_id',
        'channel_id',
        'callback_token',
        'status',
        'lease_seconds',
        'expires_at',
        'last_verified_at',
        'last_notification_at',
        'last_error',
    ];

    protected $casts = [
        'lease_seconds' => 'integer',
        'expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'last_notification_at' => 'datetime',
    ];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}
