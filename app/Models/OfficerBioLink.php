<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficerBioLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_bio_profile_id',
        'title',
        'subtitle',
        'url',
        'url_host',
        'icon',
        'link_type',
        'recommended_officer_id',
        'badge_label',
        'is_highlighted',
        'is_active',
        'moderation_status',
        'moderation_reason',
        'moderated_by',
        'moderated_at',
        'sort_order',
    ];

    protected $casts = [
        'is_highlighted' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'moderated_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OfficerBioProfile::class, 'officer_bio_profile_id');
    }

    public function recommendedOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'recommended_officer_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(BioLinkClick::class, 'officer_bio_link_id');
    }
}
