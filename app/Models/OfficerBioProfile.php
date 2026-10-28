<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficerBioProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_id',
        'slug',
        'display_title',
        'bio_tagline',
        'banner_path',
        'theme_preset',
        'button_style',
        'accent_color',
        'show_live_stream_banner',
        'is_active',
        'is_suspended',
        'suspension_reason',
        'suspended_by',
        'suspended_at',
    ];

    protected $casts = [
        'show_live_stream_banner' => 'boolean',
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(OfficerBioLink::class)->orderBy('sort_order', 'asc');
    }

    public function activeLinks(): HasMany
    {
        return $this->hasMany(OfficerBioLink::class)
            ->where('is_active', true)
            ->where('moderation_status', 'approved')
            ->orderBy('sort_order', 'asc');
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(BioSlugRedirect::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(BioDailyStat::class);
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }
}
