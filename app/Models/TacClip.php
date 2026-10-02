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
    ];

    protected $casts = [
        'start_seconds' => 'integer',
        'end_seconds' => 'integer',
        'likes_count' => 'integer',
        'views_count' => 'integer',
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function officer()
    {
        return $this->belongsTo(Officer::class);
    }
}
