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
                    'daily_clips_count' => $request->user()->getDailyClipsCount(),
                    'daily_clips_limit' => $request->user()->getDailyClipsLimit(),
                    'max_clip_duration_sec' => $request->user()->getMaxClipDurationSec(),
                ]) : null,
            ],
        ];
    }
}
