<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        if ($request->user() && ! $request->routeIs('bio.show', 'bio.go')) {
            \Illuminate\Support\Facades\Vite::prefetch(concurrency: 3);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'is_admin' => $request->user()->isAdmin(),
                    'is_officer' => $request->user()->isOfficer(),
                    'can_delegate_clipper' => $request->user()->canDelegateClipper(),
                    'can_trim_video' => $request->user()->canTrimVideo(),
                    'clipper_tier' => $request->user()->getClipperTier(),
                    'clipper_tier_name' => $request->user()->getClipperTierName(),
                    'daily_clips_count' => $request->user()->getClipsQuotaCount(),
                    'daily_clips_limit' => $request->user()->getDailyClipsLimit(),
                    'quota_period_label' => $request->user()->getQuotaPeriodLabel(),
                    'can_use_1080p' => $request->user()->canUse1080p(),
                    'max_clip_duration_sec' => $request->user()->getMaxClipDurationSec(),
                    'officer_profile' => $request->user()->officer ? [
                        'id' => $request->user()->officer->id,
                        'officer_name' => $request->user()->officer->officer_name,
                        'streamer_name' => $request->user()->officer->streamer_name,
                        'callsign' => $request->user()->officer->callsign,
                        'badge_number' => $request->user()->officer->badge_number,
                        'slug' => $request->user()->officer->bioProfile?->slug,
                    ] : null,
                ]) : null,
            ],
        ];
    }
}
