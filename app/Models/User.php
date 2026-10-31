<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'banned_reason', 'banned_at', 'name_skeleton', 'clipper_tier'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
     * Check if user is an Officer or Admin.
     * Derived primarily from officer relation; accepts legacy role === 'officer' during transition.
     */
    public function isOfficer(): bool
    {
        return $this->isAdmin() || $this->officer()->exists() || $this->role === 'officer';
    }

    /**
     * Check if user account is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    /**
     * Check if user has permission to delegate Clipper slots (Officers & Admins only).
     */
    public function canDelegateClipper(): bool
    {
        if (!$this->isOfficer()) {
            return false;
        }
        // Safety guard: a user who received a delegation can NEVER delegate
        if ($this->clipperDelegationReceived()->exists()) {
            return false;
        }
        return true;
    }

    /**
     * Check if user can trim/cut YouTube videos.
     */
    public function canTrimVideo(): bool
    {
        return in_array($this->role, ['admin', 'officer', 'clipper']) || in_array($this->clipper_tier, ['creator', 'ultimate']);
    }

    /**
     * Get user's resolved clipper tier code.
     */
    public function getClipperTier(): string
    {
        if ($this->isOfficer() || $this->clipper_tier === 'ultimate' || ($this->role === 'clipper' && (!$this->clipper_tier || $this->clipper_tier === 'free'))) {
            return 'ultimate';
        }
        if ($this->clipper_tier === 'creator') {
            return 'creator';
        }
        return 'free';
    }

    /**
     * Get user's tier human readable name.
     */
    public function getClipperTierName(): string
    {
        $tier = $this->getClipperTier();
        if ($tier === 'ultimate') return 'PRO Ultimate';
        if ($tier === 'creator') return 'IME Clipper';
        return 'Free Member';
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
            'banned_at' => 'datetime',
        ];
    }

    /**
     * The officer record linked to this user (1:1).
     */
    public function officer(): HasOne
    {
        return $this->hasOne(Officer::class, 'user_id');
    }

    /**
     * Officer claims submitted by this user.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(OfficerClaim::class);
    }

    /**
     * Audit log actions performed by this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    /**
     * Squads created by this user.
     */
    public function squads(): HasMany
    {
        return $this->hasMany(\App\Models\Squad::class);
    }

    /**
     * Get the user's created video clips.
     */
    public function videoClips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VideoClip::class);
    }

    /**
     * Get delegations granted by this officer/user (max 2).
     */
    public function clipperDelegationsGranted(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClipperDelegation::class, 'granted_by_user_id');
    }

    /**
     * Get delegation received by this user.
     */
    public function clipperDelegationReceived(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClipperDelegation::class, 'assigned_user_id');
    }

    /**
     * Get number of clips processed by user this week (excluding failed clips).
     */
    public function getWeeklyClipsCount(): int
    {
        return $this->videoClips()
            ->where('created_at', '>=', now()->startOfWeek())
            ->where('status', '!=', 'failed')
            ->count();
    }

    /**
     * Get number of clips processed by user today (excluding failed clips).
     */
    public function getDailyClipsCount(): int
    {
        return $this->videoClips()
            ->whereDate('created_at', now()->toDateString())
            ->where('status', '!=', 'failed')
            ->count();
    }

    /**
     * Get quota clip count based on tier (Weekly for free tier, Daily for creator/ultimate).
     */
    public function getClipsQuotaCount(): int
    {
        return $this->getClipperTier() === 'free'
            ? $this->getWeeklyClipsCount()
            : $this->getDailyClipsCount();
    }

    /**
     * Get quota period label (Minggu Ini / Hari Ini).
     */
    public function getQuotaPeriodLabel(): string
    {
        return $this->getClipperTier() === 'free' ? 'Minggu Ini' : 'Hari Ini';
    }

    /**
     * Check if user can export video in 1080p Full HD (Creator & Ultimate tiers only).
     */
    public function canUse1080p(): bool
    {
        return $this->getClipperTier() !== 'free';
    }

    /**
     * Get daily clip limit based on user tier.
     */
    public function getDailyClipsLimit(): int
    {
        $tier = $this->getClipperTier();
        if ($tier === 'ultimate') return 9999;
        if ($tier === 'creator') return 10;
        return 3;
    }

    /**
     * Get max clip duration in seconds based on user tier.
     */
    public function getMaxClipDurationSec(): int
    {
        $tier = $this->getClipperTier();
        if ($tier === 'ultimate') return 600; // 10 minutes
        if ($tier === 'creator') return 300;  // 5 minutes
        return 60;   // 1 minute
    }
}

