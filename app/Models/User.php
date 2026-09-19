<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can trim/cut YouTube videos.
     */
    public function canTrimVideo(): bool
    {
        return in_array($this->role, ['admin', 'clipper']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's cloud watchlist items.
     */
    public function watchlists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserWatchlist::class);
    }

    /**
     * Get the user's created video clips.
     */
    public function videoClips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VideoClip::class);
    }
}

