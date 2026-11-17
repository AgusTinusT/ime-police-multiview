<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class HeroSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'slide_type',
        'title',
        'subtitle',
        'badge_label',
        'accent',
        'image_desktop_path',
        'image_mobile_path',
        'image_alt',
        'cta_primary_label',
        'cta_primary_url',
        'cta_secondary_label',
        'cta_secondary_url',
        'audience',
        'starts_at',
        'ends_at',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_desktop_url',
        'image_mobile_url',
    ];

    public function getImageDesktopUrlAttribute(): ?string
    {
        if (!$this->image_desktop_path) {
            return null;
        }
        if (str_starts_with($this->image_desktop_path, 'http://') || str_starts_with($this->image_desktop_path, 'https://')) {
            return $this->image_desktop_path;
        }

        return Storage::disk('public')->url($this->image_desktop_path);
    }

    public function getImageMobileUrlAttribute(): ?string
    {
        if (!$this->image_mobile_path) {
            return $this->image_desktop_url;
        }
        if (str_starts_with($this->image_mobile_path, 'http://') || str_starts_with($this->image_mobile_path, 'https://')) {
            return $this->image_mobile_path;
        }

        return Storage::disk('public')->url($this->image_mobile_path);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(HeroSlideStat::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

