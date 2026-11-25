<?php

namespace App\Models;

use App\Services\OfficerAvatarStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Officer extends Model
{
    use HasFactory;

    public const OWNER_MANAGED_FIELDS = [
        'officer_name',
        'streamer_name',
        'callsign',
        'badge_number',
        'avatar_url',
        'station',
        'division_id',
    ];

    public function isClaimed(): bool
    {
        return $this->user_id !== null;
    }

    protected $fillable = [
        'user_id',
        'channel_id',
        'handle',
        'streamer_name',
        'officer_name',
        'callsign',
        'badge_number',
        'department',
        'rank',
        'station',
        'agency_id',
        'rank_id',
        'division_id',
        'duty_status',
        'subscriber_count',
        'subscriber_count_text',
        'avatar_url',
        'avatar_disk',
        'avatar_path',
        'avatar_source_url',
        'is_active',
        'is_listed',
        'monthly_duty_minutes',
        'last_duty_at',
        'bypass_hashtag_check',
        'owner_updated_at',
        'callsign_changed_at',
        'avatar_is_custom',
        'avatar_hidden',
    ];

    protected $casts = [
        'subscriber_count' => 'integer',
        'monthly_duty_minutes' => 'integer',
        'last_duty_at' => 'datetime',
        'is_active' => 'boolean',
        'is_listed' => 'boolean',
        'bypass_hashtag_check' => 'boolean',
        'owner_updated_at' => 'datetime',
        'callsign_changed_at' => 'datetime',
        'avatar_is_custom' => 'boolean',
        'avatar_hidden' => 'boolean',
    ];

    protected $appends = [
        'public_avatar_url',
    ];

    protected $hidden = [
        'avatar_disk',
        'avatar_path',
    ];

    protected static function booted(): void
    {
        // A stored avatar belongs to avatar_url only while avatar_url still
        // points at it. Any other avatar_url (YouTube sync, placeholder, null)
        // detaches the stored file.
        static::saving(function (Officer $officer) {
            if ($officer->avatar_path && $officer->isDirty('avatar_url')
                && ! OfficerAvatarStore::urlMatchesPath($officer->avatar_url, $officer->avatar_path)) {
                $officer->avatar_disk = null;
                $officer->avatar_path = null;
                $officer->avatar_source_url = null;
            }
        });

        static::updated(function (Officer $officer) {
            if ($officer->wasChanged('avatar_path')) {
                OfficerAvatarStore::delete($officer->getOriginal('avatar_disk'), $officer->getOriginal('avatar_path'));
            }
        });

        static::deleted(function (Officer $officer) {
            OfficerAvatarStore::delete($officer->avatar_disk, $officer->avatar_path);
        });
    }

    /**
     * Get the public-facing avatar URL (null if avatar_hidden is true).
     */
    public function getPublicAvatarUrlAttribute(): ?string
    {
        return $this->avatar_hidden ? null : $this->avatar_url;
    }

    /**
     * Canonical YouTube channel URL from handle, falling back to channel_id.
     * Returns null if neither is a real identifier.
     */
    public function getYoutubeUrlAttribute(): ?string
    {
        $handle = trim($this->handle ?? '');
        if ($handle !== '') {
            $clean = str_starts_with($handle, '@') ? $handle : '@' . $handle;
            return "https://www.youtube.com/{$clean}";
        }
        $channel = (string) ($this->channel_id ?? '');
        if ($channel !== '' && ! str_starts_with($channel, 'UC_')) {
            return "https://www.youtube.com/channel/{$channel}";
        }
        return null;
    }

    /**
     * Determine if officer is on hiatus (inactive/not live for >= hiatus_days).
     */
    public function getIsHiatusAttribute(): bool
    {
        if ($this->relationLoaded('activeStream') ? $this->activeStream : $this->activeStream()->exists()) {
            return false;
        }
        if (!$this->last_duty_at) {
            // Officer baru / belum pernah live: hiatus bila dibuat >= hiatus_days hari lalu.
            return $this->created_at ? $this->created_at->diffInDays(now()) >= config('police.hiatus_days', 14) : false;
        }
        return $this->last_duty_at->diffInDays(now()) >= config('police.hiatus_days', 14);
    }

    /**
     * Account user managing this officer profile (1:1 relation)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tactical Bio Link Profile
     */
    public function bioProfile(): HasOne
    {
        return $this->hasOne(OfficerBioProfile::class);
    }

    /**
     * Rank & Agency Change Requests
     */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(OfficerChangeRequest::class);
    }

    /**
     * Claims submitted for this officer
     */
    public function claims(): HasMany
    {
        return $this->hasMany(OfficerClaim::class);
    }

    /**
     * Relational Agency (LSPD, BCSO, SASP, SAPR)
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function agencyRelation()
    {
        return $this->agency();
    }

    /**
     * Relational Rank
     */
    public function rankRelation()
    {
        return $this->belongsTo(Rank::class, 'rank_id');
    }

    /**
     * Relational Sub-Division
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function divisionRelation()
    {
        return $this->division();
    }

    /**
     * Tactical Certifications held by officer
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    /**
     * Get all streams for this officer.
     */
    public function streams(): HasMany
    {
        return $this->hasMany(ActiveStream::class, 'officer_id', 'id');
    }

    /**
     * Get currently active live stream for this officer.
     */
    public function activeStream(): HasOne
    {
        return $this->hasOne(ActiveStream::class, 'officer_id', 'id')
            ->where('status', 'LIVE');
    }

    /**
     * Live-notification subscriptions targeting this officer.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(OfficerSubscription::class);
    }

    /**
     * Users following this officer for live notifications.
     */
    public function subscribers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'officer_subscriptions')
            ->withPivot(['notify_in_app', 'notify_web_push'])
            ->withTimestamps();
    }

    /**
     * YouTube WebSub lease record for this officer's channel.
     */
    public function webSubSubscription(): HasOne
    {
        return $this->hasOne(YouTubeWebSubSubscription::class);
    }

    /**
     * Scope a query to only include active monitored officers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Officers shown on public rosters (dashboard, directory, ranking, multiview,
     * squads, stream sync). Excludes non-personnel rows that exist only to back a
     * bio-link (is_listed = false). Bio-link pages keep using is_active alone.
     */
    public function scopeRoster($query)
    {
        return $query->where('is_active', true)->where('is_listed', true);
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeDepartment($query, ?string $dept)
    {
        if ($dept && $dept !== 'ALL') {
            return $query->where('department', $dept);
        }
        return $query;
    }
}
