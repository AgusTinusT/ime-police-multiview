<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_id',
        'requested_by',
        'field',
        'old_value',
        'new_value',
        'reason',
        'status',
        'pending_lock',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (OfficerChangeRequest $request) {
            if ($request->status === 'pending') {
                $request->pending_lock = "{$request->officer_id}:{$request->field}";
            } else {
                $request->pending_lock = null;
            }
        });
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
