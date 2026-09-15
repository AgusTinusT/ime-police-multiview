<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_code',
        'agency_name',
        'jurisdiction',
        'badge_logo_url',
    ];

    public function ranks(): HasMany
    {
        return $this->hasMany(Rank::class)->orderBy('level', 'desc');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }
}
