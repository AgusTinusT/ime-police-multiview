<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'badge',
        'poster_name',
        'poster_subtitle',
        'poster_image_url',
        'background_image_url',
        'url',
        'cta_text',
        'description',
        'chips',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'chips' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}

