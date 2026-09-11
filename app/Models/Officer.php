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
        'subscriber_count',
        'subscriber_count_text',
        'is_active',
    ];

    protected $casts = [
        'subscriber_count' => 'integer',
        'is_active' => 'boolean',
    ];

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
