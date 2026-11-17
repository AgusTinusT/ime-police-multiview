<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioDailyStat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'officer_bio_profile_id',
        'officer_bio_link_id',
        'stat_date',
        'views',
        'clicks',
        'unique_visitors',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'views' => 'integer',
        'clicks' => 'integer',
        'unique_visitors' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OfficerBioProfile::class, 'officer_bio_profile_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(OfficerBioLink::class, 'officer_bio_link_id');
    }
}
