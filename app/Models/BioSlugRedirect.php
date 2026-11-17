<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioSlugRedirect extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'old_slug',
        'officer_bio_profile_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OfficerBioProfile::class, 'officer_bio_profile_id');
    }
}
