<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'officer_id',
        'notify_in_app',
        'notify_web_push',
    ];

    protected $casts = [
        'notify_in_app' => 'boolean',
        'notify_web_push' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}
