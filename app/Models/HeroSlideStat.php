<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroSlideStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_slide_id',
        'stat_date',
        'impressions',
        'clicks',
    ];

    protected $casts = [
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];

    public function slide(): BelongsTo
    {
        return $this->belongsTo(HeroSlide::class, 'hero_slide_id');
    }
}

