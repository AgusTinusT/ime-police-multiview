<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoClip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'youtube_url',
        'start_time',
        'end_time',
        'duration_seconds',
        'file_path',
        'status',
        'error_message',
    ];

    /**
     * Relationship with User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
