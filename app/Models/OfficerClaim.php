<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'officer_id',
        'verification_method',
        'verification_token_hash',
        'token_expires_at',
        'last_checked_at',
        'check_attempts',
        'proof_discord_tag',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'check_attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
