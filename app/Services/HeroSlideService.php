<?php

namespace App\Services;

use App\Models\ActiveStream;
use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class HeroSlideService
{
    public const AUDIENCES = ['guest', 'member', 'officer', 'all'];

    public static function resolveAudience(?User $user): string
    {
        if (!$user) {
            return 'guest';
        }

        if ($user->isOfficer()) {
            return 'officer';
        }

        return 'member';
    }

    /**
     * Return up to 5 active, scheduled, audience-matched hero slides for dashboard.
     */
    public static function forAudience(?User $user): array
    {
        $audience = self::resolveAudience($user);

        return Cache::remember("hero_slides:{$audience}", 60, function () use ($audience) {
            $now = now();

            $slides = HeroSlide::query()
                ->where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                })
                ->whereIn('audience', ['all', $audience])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $result = [];

            foreach ($slides as $slide) {
                if ($slide->slide_type === 'live_spotlight') {
                    $mapped = self::mapLiveSpotlight($slide);
                    if ($mapped !== null) {
                        $result[] = $mapped;
                    }
                } else {
                    $result[] = self::mapStandardSlide($slide);
                }

                if (count($result) >= 5) {
                    break;
                }
            }

            return $result;
        });
    }

    /**
     * Clear all cached hero slide audience keys.
     */
    public static function forget(): void
    {
        foreach (self::AUDIENCES as $audience) {
            Cache::forget("hero_slides:{$audience}");
        }
    }

    protected static function mapStandardSlide(HeroSlide $slide): array
    {
        return [
            'id' => $slide->id,
            'type' => $slide->slide_type,
            'slide_type' => $slide->slide_type,
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'badge_label' => $slide->badge_label,
            'accent' => $slide->accent === 'danger' ? 'destructive' : $slide->accent,
            'image_desktop_url' => $slide->image_desktop_url,
            'image_mobile_url' => $slide->image_mobile_url ?: $slide->image_desktop_url,
            'image_alt' => $slide->image_alt ?: ($slide->title ?? 'Hero slide'),
            'cta_primary' => ($slide->cta_primary_label && $slide->cta_primary_url) ? [
                'label' => $slide->cta_primary_label,
                'url' => $slide->cta_primary_url,
            ] : null,
            'cta_secondary' => ($slide->cta_secondary_label && $slide->cta_secondary_url) ? [
                'label' => $slide->cta_secondary_label,
                'url' => $slide->cta_secondary_url,
            ] : null,
            'live' => null,
        ];
    }

    protected static function mapLiveSpotlight(HeroSlide $slide): ?array
    {
        $topStream = ActiveStream::with(['officer.bioProfile'])
            ->where('status', 'LIVE')
            ->whereHas('officer', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('viewers_count')
            ->first();

        if (!$topStream || !$topStream->officer) {
            return null;
        }

        $officer = $topStream->officer;
        $officerName = $officer->officer_name ?: $officer->streamer_name;
        $bioProfile = $officer->bioProfile;
        $hasActiveBio = $bioProfile && $bioProfile->is_active && !$bioProfile->is_suspended && !empty($bioProfile->slug);

        $ctaSecondary = null;
        if ($hasActiveBio) {
            $ctaSecondary = [
                'label' => $slide->cta_secondary_label ?: 'Profil Perwira',
                'url' => '/@' . $bioProfile->slug,
            ];
        } elseif ($slide->cta_secondary_label && $slide->cta_secondary_url) {
            $ctaSecondary = [
                'label' => $slide->cta_secondary_label,
                'url' => $slide->cta_secondary_url,
            ];
        }

        return [
            'id' => $slide->id,
            'type' => 'live_spotlight',
            'slide_type' => 'live_spotlight',
            'title' => $officerName,
            'subtitle' => $topStream->title ?: 'Siaran Patroli Langsung',
            'badge_label' => $slide->badge_label ?: 'LIVE SPOTLIGHT',
            'accent' => $slide->accent === 'danger' ? 'destructive' : ($slide->accent ?: 'live'),
            'image_desktop_url' => $topStream->thumbnail_url ?: $slide->image_desktop_url,
            'image_mobile_url' => $topStream->thumbnail_url ?: ($slide->image_mobile_url ?: $slide->image_desktop_url),
            'image_alt' => 'Siaran langsung ' . $officerName,
            'cta_primary' => [
                'label' => $slide->cta_primary_label ?: 'Tonton di Multiview',
                'url' => $slide->cta_primary_url ?: '/multiview',
            ],
            'cta_secondary' => $ctaSecondary,
            'live' => [
                'officer_id' => $officer->id,
                'officer_name' => $officerName,
                'callsign' => $officer->callsign,
                'department' => $officer->department,
                'rank' => $officer->rank,
                'avatar_url' => $officer->public_avatar_url,
                'viewers_count' => (int) ($topStream->viewers_count ?? 0),
                'video_id' => $topStream->video_id,
            ],
        ];
    }
}

