<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Officer extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'handle',
        'streamer_name',
        'officer_name',
        'callsign',
        'badge_number',
        'department',
        'rank',
        'patrol_zone',
        'agency_id',
        'rank_id',
        'division_id',
        'duty_status',
        'subscriber_count',
        'subscriber_count_text',
        'avatar_url',
        'is_active',
        'monthly_duty_minutes',
        'last_duty_at',
        'bypass_hashtag_check',
    ];

    protected $casts = [
        'subscriber_count' => 'integer',
        'monthly_duty_minutes' => 'integer',
        'last_duty_at' => 'datetime',
        'is_active' => 'boolean',
        'bypass_hashtag_check' => 'boolean',
    ];

    /**
     * Relational Agency (LSPD, BCSO, SASP, SAPR)
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
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
        return $this->hasMany(ActiveStream::class, 'channel_id', 'channel_id');
    }

    /**
     * Get currently active live stream for this officer.
     */
    public function activeStream(): HasOne
    {
        return $this->hasOne(ActiveStream::class, 'channel_id', 'channel_id')
            ->where('status', 'LIVE');
    }

    /**
     * Scope a query to only include active monitored officers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
