<?php

namespace App\Http\Controllers;

use App\Models\BioDailyStat;
use App\Models\BioLinkClick;
use App\Models\BioSlugRedirect;
use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Services\BioProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicBioLinkController extends Controller
{
    /**
     * Show public tactical bio-link page (/@{slug}).
     */
    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $slug = strtolower(trim($slug));

        // 1. Check if slug belongs to bio_slug_redirects (301 Permanent Redirect)
        $redirect = BioSlugRedirect::where('old_slug', $slug)->first();
        if ($redirect && $redirect->profile) {
            return redirect()->to('/@' . $redirect->profile->slug, 301);
        }

        // 2. Find profile by slug
        $profile = OfficerBioProfile::with([
            'officer.agency',
            'officer.rankRelation',
            'officer.activeStream',
            'links.recommendedOfficer.activeStream',
            'links.recommendedOfficer.agency',
            'links.recommendedOfficer.rankRelation',
            'links.recommendedOfficer.bioProfile',
        ])->where('slug', $slug)->first();

        // 3. Fallback: check if slug matches an officer handle -> 301 Permanent Redirect to official slug
        if (!$profile) {
            $officer = Officer::where('is_active', true)
                ->where(function ($q) use ($slug) {
                    $q->where('handle', '@' . $slug)
                        ->orWhere('handle', $slug);
                })
                ->whereHas('bioProfile')
                ->with('bioProfile')
                ->first();

            if ($officer && $officer->bioProfile) {
                return redirect()->to('/@' . $officer->bioProfile->slug, 301);
            }
        }

        if (!$profile) {
            abort(404, 'Halaman bio-link perwira tidak ditemukan.');
        }

        $officer = $profile->officer;

        // Precedence 1: Officer inactive in roster -> 404
        if (!$officer || !$officer->is_active) {
            abort(404, 'Perwira tidak aktif.');
        }

        // Precedence 2: Suspended by admin kill-switch -> show notice card with 200 noindex
        if ($profile->is_suspended) {
            return Inertia::render('Public/BioLink', [
                'officer' => [
                    'officer_name' => $officer->officer_name,
                    'callsign' => $officer->callsign,
                    'department' => $officer->department,
                    'avatar_url' => $officer->public_avatar_url,
                ],
                'profile' => [
                    'slug' => $profile->slug,
                    'is_suspended' => true,
                    'suspension_reason' => $profile->suspension_reason ?: 'Halaman ini telah dinonaktifkan sementara oleh Komando Kepolisian SASP.',
                ],
                'isSuspended' => true,
                'links' => [],
                'meta' => [
                    'title' => "{$officer->officer_name} [DINONAKTIFKAN] — Police Tactical Command",
                    'description' => 'Halaman bio-link dinonaktifkan sementara.',
                    'image' => $officer->public_avatar_url,
                    'url' => url('/@' . $profile->slug),
                    'noindex' => true,
                ],
            ]);
        }

        // Precedence 3: Officer hid bio profile -> 404
        if (!$profile->is_active) {
            abort(404, 'Halaman bio-link sedang dinonaktifkan.');
        }

        // Precedence 4: Unclaimed officer -> basic roster page without links
        $isUnclaimed = $officer->user_id === null;

        // Increment daily view stats in cache (T-08)
        $this->recordPageView($profile);

        // Cache the rendered page data for 60 seconds per slug (T-08)
        $pageProps = Cache::remember("biolink_data:{$slug}", 60, function () use ($profile, $officer, $isUnclaimed) {
            // Check if officer is actively live right now
            $activeStream = $officer->activeStream;

            // Filter links: only active & approved
            // B-07: For officer_recommendation, hide if recommendedOfficer.is_active = false or recommendedOfficer.bioProfile.is_suspended = true
            $links = $isUnclaimed ? collect([]) : $profile->links
                ->filter(function ($l) {
                    if (!$l->is_active || $l->moderation_status !== 'approved') {
                        return false;
                    }
                    if ($l->link_type === 'officer_recommendation') {
                        $ro = $l->recommendedOfficer;
                        if (!$ro || !$ro->is_active) {
                            return false;
                        }
                        if ($ro->bioProfile && $ro->bioProfile->is_suspended) {
                            return false;
                        }
                    }
                    return true;
                })
                ->values();

            // SEO and Social Media Meta
            $metaTitle = "{$officer->officer_name} ({$officer->callsign}) — Official SASP Bio Hub";
            $metaDescription = $profile->bio_tagline ?: "Saluran resmi perwira kepolisian {$officer->streamer_name} di Police Tactical Command Center SASP.";
            $metaImage = $officer->public_avatar_url ?: asset('images/icons/icon-512x512.png');

            return [
                'officer' => [
                    'id' => $officer->id,
                    'officer_name' => $officer->officer_name,
                    'streamer_name' => $officer->streamer_name,
                    'callsign' => $officer->callsign,
                    'badge_number' => $officer->badge_number,
                    'department' => $officer->agency?->agency_code ?: $officer->department,
                    'rank' => $officer->rankRelation?->rank_title ?: $officer->rank,
                    'agency' => $officer->agency?->agency_name ?: $officer->department,
                    'agency_badge_url' => $officer->agency?->badge_logo_url,
                    'avatar_url' => $officer->public_avatar_url,
                    'duty_status' => $officer->duty_status,
                    'is_live' => $activeStream !== null,
                    'live_stream' => $activeStream ? [
                        'video_id' => $activeStream->video_id,
                        'title' => $activeStream->title,
                        'viewer_count' => $activeStream->viewers_count,
                    ] : null,
                ],
                'profile' => [
                    'id' => $profile->id,
                    'slug' => $profile->slug,
                    'display_title' => $profile->display_title ?: $officer->officer_name,
                    'bio_tagline' => $profile->bio_tagline,
                    'theme_preset' => $profile->theme_preset,
                    'button_style' => $profile->button_style,
                    'accent_color' => $profile->accent_color,
                    'show_live_stream_banner' => $profile->show_live_stream_banner,
                    'is_suspended' => false,
                    'is_unclaimed' => $isUnclaimed,
                    'is_verified' => !$isUnclaimed,
                ],
                'links' => $links->map(function ($link) {
                    return [
                        'id' => $link->id,
                        'title' => $link->title,
                        'subtitle' => $link->subtitle,
                        'link_type' => $link->link_type,
                        'platform' => $link->platform,
                        'icon' => $link->icon,
                        'badge_label' => $link->badge_label,
                        'is_highlighted' => $link->is_highlighted,
                        'redirect_url' => $link->link_type === 'officer_recommendation' ? null : url('/go/' . $link->id),
                        'recommended_officer' => $link->recommendedOfficer ? [
                            'id' => $link->recommendedOfficer->id,
                            'officer_name' => $link->recommendedOfficer->officer_name,
                            'streamer_name' => $link->recommendedOfficer->streamer_name,
                            'callsign' => $link->recommendedOfficer->callsign,
                            'department' => $link->recommendedOfficer->agency?->agency_code ?: $link->recommendedOfficer->department,
                            'rank' => $link->recommendedOfficer->rankRelation?->rank_title ?: $link->recommendedOfficer->rank,
                            'agency' => $link->recommendedOfficer->agency?->agency_name ?: $link->recommendedOfficer->department,
                            'agency_badge_url' => $link->recommendedOfficer->agency?->badge_logo_url,
                            'avatar_url' => $link->recommendedOfficer->public_avatar_url,
                            'is_live' => $link->recommendedOfficer->activeStream !== null,
                            'active_stream' => $link->recommendedOfficer->activeStream ? [
                                'video_id' => $link->recommendedOfficer->activeStream->video_id,
                                'title' => $link->recommendedOfficer->activeStream->title,
                            ] : null,
                            'bio_slug' => $link->recommendedOfficer->bioProfile?->slug,
                            'youtube_url' => $link->recommendedOfficer->youtube_url,
                        ] : null,
                    ];
                })->values()->all(),
                'isSuspended' => false,
                'isUnclaimed' => $isUnclaimed,
                'meta' => [
                    'title' => $metaTitle,
                    'description' => $metaDescription,
                    'image' => $metaImage,
                    'url' => url('/@' . $profile->slug),
                ],
            ];
        });

        // Theme contract stays outside the page cache so config changes apply immediately.
        $user = $request->user();
        $isOfficer = $user ? $user->isOfficer() : false;
        $promoCta = ! $isOfficer ? [
            'text' => 'Suka tampilan ini? Kamu juga bisa punya bio-link resmi di /@kamu.',
            'action_label' => 'Pelajari →',
            'action_url' => '/bio-link',
        ] : null;

        return Inertia::render('Public/BioLink', $pageProps + [
            'themes' => config('biolinks.theme_presets', []),
            'promoCta' => $promoCta,
        ]);
    }

    /**
     * Polling endpoint for real-time live telemetry without full page reload (T-09).
     */
    public function liveStatus(string $slug): JsonResponse
    {
        $slug = strtolower(trim($slug));
        $profile = OfficerBioProfile::where('slug', $slug)
            ->with(['officer.activeStream', 'links.recommendedOfficer.activeStream'])
            ->first();

        if (!$profile || !$profile->officer) {
            return response()->json([
                'is_live' => false,
                'live_stream' => null,
                'recommended_streams' => (object) [],
            ]);
        }

        $officer = $profile->officer;
        $activeStream = $officer->activeStream;

        $recommended = [];
        foreach ($profile->links as $link) {
            if ($link->link_type === 'officer_recommendation' && $link->recommendedOfficer) {
                $ro = $link->recommendedOfficer;
                $recommended[$link->id] = [
                    'is_live' => $ro->activeStream !== null,
                    'active_stream' => $ro->activeStream ? [
                        'video_id' => $ro->activeStream->video_id,
                        'title' => $ro->activeStream->title,
                    ] : null,
                ];
            }
        }

        return response()->json([
            'is_live' => $activeStream !== null,
            'live_stream' => $activeStream ? [
                'video_id' => $activeStream->video_id,
                'title' => $activeStream->title,
                'viewer_count' => $activeStream->viewers_count,
            ] : null,
            'recommended_streams' => $recommended,
        ]);
    }

    /**
     * Handle safe redirect with telemetry and anti-open-redirect (/go/{link}).
     */
    public function handleClick(Request $request, OfficerBioLink $link): RedirectResponse
    {
        $profile = $link->profile;
        $officer = $profile?->officer;

        // Safety guards
        if (
            !$link->is_active ||
            $link->moderation_status !== 'approved' ||
            empty($link->url) ||
            !$profile ||
            $profile->is_suspended ||
            !$officer ||
            !$officer->is_active
        ) {
            abort(404, 'Tautan tidak valid atau telah dinonaktifkan.');
        }

        // Privacy-preserving visitor hash (HMAC-SHA256 with daily rotating salt)
        $ip = $request->ip() ?: '0.0.0.0';
        $ua = $request->userAgent() ?: 'unknown';
        $dailySalt = date('Ymd') . config('app.key');
        $visitorHash = hash_hmac('sha256', "{$ip}|{$ua}", $dailySalt);

        // Host of referer only
        $referer = $request->headers->get('referer');
        $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;

        // Non-blocking telemetry click recording
        try {
            BioLinkClick::create([
                'officer_bio_link_id' => $link->id,
                'visitor_hash' => $visitorHash,
                'referer_host' => $refererHost,
            ]);

            // Increment daily stats
            $today = now()->toDateString();
            $stat = BioDailyStat::firstOrCreate(
                [
                    'officer_bio_profile_id' => $profile->id,
                    'officer_bio_link_id' => $link->id,
                    'stat_date' => $today,
                ],
                [
                    'clicks' => 0,
                    'views' => 0,
                    'unique_visitors' => 0,
                ]
            );
            $stat->increment('clicks');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Bio link click recording failed: " . $e->getMessage());
        }

        // Safe redirect to target URL
        return redirect()->away($link->url, 302, [
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ]);
    }

    /**
     * Record page view in cache and aggregate via command (T-08, B-12).
     */
    protected function recordPageView(OfficerBioProfile $profile): void
    {
        // Ignore known crawler bots
        $ua = request()->userAgent() ?: '';
        if (preg_match('/bot|crawl|slurp|spider|mediapartners|discordapp|whatsapp/i', $ua)) {
            return;
        }

        try {
            $today = now()->toDateString();
            $cacheKey = "bio_views:{$profile->id}:{$today}";

            // B-12: DatabaseStore requires key to exist before incrementing
            Cache::add($cacheKey, 0, now()->addDays(2));
            Cache::increment($cacheKey);
        } catch (\Throwable) {
            // Silently continue if telemetry fails
        }
    }
}
