<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        'channel_id',
        'handle',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function activeStreams()
    {
        return $this->hasMany(ActiveStream::class, 'channel_id', 'channel_id');
    }
}
