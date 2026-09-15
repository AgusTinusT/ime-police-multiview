<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'rank_title',
        'level',
        'base_salary',
    ];

    protected $casts = [
        'level' => 'integer',
        'base_salary' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }
}
