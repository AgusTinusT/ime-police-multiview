<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Squad extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'visibility',
        'forked_from_id',
        'forks_count',
        'is_hidden',
    ];

    protected $casts = [
        'forks_count' => 'integer',
        'is_hidden' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function forkedFrom(): BelongsTo
    {
        return $this->belongsTo(Squad::class, 'forked_from_id');
    }

    public function forks(): HasMany
    {
        return $this->hasMany(Squad::class, 'forked_from_id');
    }

    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(Officer::class, 'squad_officer')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Scope for public and visible squads.
     */
    public function scopePublicVisible($query)
    {
        return $query->where('visibility', 'public')->where('is_hidden', false);
    }
}
