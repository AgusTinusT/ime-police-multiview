<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioLinkClick extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'officer_bio_link_id',
        'visitor_hash',
        'referer_host',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(OfficerBioLink::class, 'officer_bio_link_id');
    }
}
