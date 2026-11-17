<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_id',
        'cert_type',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}
