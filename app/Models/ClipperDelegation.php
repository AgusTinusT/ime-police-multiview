<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClipperDelegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'granted_by_user_id',
        'assigned_user_id',
        'tier',
    ];

    public function grantor()
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
