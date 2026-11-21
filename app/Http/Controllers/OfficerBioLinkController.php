<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LinkDomainDenylist;
use App\Models\Officer;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Services\AuditLogger;
use App\Services\BioProfileService;
use App\Support\BioLinkPlatforms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OfficerBioLinkController extends Controller
{
    /**
     * Render the Tactical Bio Links Management Dashboard for the authenticated officer.
     */
    /**
     * Legacy dashboard URL — the editor now lives in the unified Settings page.
     */
    public function dashboard(Request $request): RedirectResponse
    {
        $query = $request->query('officer_id') ? ['tab' => 'biolink', 'officer_id' => $request->query('officer_id')] : ['tab' => 'biolink'];
        return redirect()->route('settings', $query);
    }

    /**
     * Props for the bio-link editor, shared by SettingsController.
     * Only the user's own officer: no admin preview of other officers here —
     * that leaks someone else's bio into the editor UI. The old admin preview
     * still works through resolveOfficer() with ?officer_id= in write actions.
     */
    public static function pageProps(Request $request): array
    {
        $user = Auth::user();
        $officer = $user?->officer;

        if (!$officer) {
            return [];
        }

        $profile = BioProfileService::ensureBioProfile($officer);
        $profile->load(['links.recommendedOfficer']);

        $otherOfficers = Officer::roster()
            ->where('id', '!=', $officer->id)
            ->orderBy('officer_name', 'asc')
            ->get(['id', 'officer_name', 'streamer_name', 'callsign', 'department', 'avatar_url']);

        $stats = [
            'total_views' => (int) $profile->dailyStats()->sum('views'),
            'total_clicks' => (int) $profile->dailyStats()->sum('clicks'),
            'links_count' => $profile->links()->count(),
        ];

        return [
            'bioOfficer' => $officer->load('agency', 'rankRelation'),
            'bioProfile' => $profile,
            'bioOtherOfficers' => $otherOfficers,
            'bioThemes' => config('biolinks.theme_presets', []),
            'bioButtonStyles' => config('biolinks.button_styles', []),
            'bioAllowedDomains' => config('biolinks.allowed_domains', []),
            'bioPlatforms' => BioLinkPlatforms::all(),
            'bioStats' => $stats,
        ];
    }

    /**
     * Store a new bio link.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $officer = $this->resolveOfficer($user, $request);
        $profile = $officer->bioProfile;

        if (!$profile) {
            $profile = BioProfileService::ensureBioProfile($officer);
        }

        if ($profile->is_suspended) {
            return response()->json([
                'message' => 'Halaman bio-link Anda sedang dinonaktifkan sementara (Suspended) oleh Komando Kepolisian. Alasan: ' . ($profile->suspension_reason ?: 'Penyelidikan moderasi.'),
            ], 423);
        }

        $maxLinks = config('biolinks.limits.max_links_per_profile', 30);
        if ($profile->links()->count() >= $maxLinks) {
            return response()->json([
                'message' => "Batas maksimal link tercapai ({$maxLinks} link per profil).",
            ], 422);
        }

        if ($this->requestedLinkType($request) === 'donation') {
            if ($err = $this->checkDonationPasswordConfirmation($request)) {
                return $err;
            }
        }

        $validated = $this->validateLinkData($request, $officer);

        $maxOrder = $profile->links()->max('sort_order') ?? 0;
        $validated['officer_bio_profile_id'] = $profile->id;
        $validated['sort_order'] = $maxOrder + 1;
        $validated['moderation_status'] = 'approved';

        // refresh() picks up DB defaults (is_active) so the dashboard shows the new link as visible
        $link = OfficerBioLink::create($validated)->refresh();

        BioProfileService::invalidatePublicCache($profile->slug);

        if ($link->link_type === 'donation') {
            AuditLogger::log(
                'bio_link.donation_added',
                'officer_bio_link',
                $link->id,
                null,
                ['title' => $link->title, 'url' => $link->url],
                "Added donation link: {$link->title}"
            );
        }

        return response()->json([
            'message' => 'Tautan berhasil ditambahkan.',
            'link' => $link->load('recommendedOfficer'),
        ], 201);
    }

    /**
     * Update an existing bio link.
     */
    public function update(Request $request, OfficerBioLink $link): JsonResponse
    {
        Gate::authorize('update', $link);

        $profile = $link->profile;
        if ($profile->is_suspended) {
            return response()->json([
                'message' => 'Halaman bio-link Anda sedang dinonaktifkan sementara (Suspended) oleh Komando Kepolisian.',
            ], 423);
        }

        if ($this->requestedLinkType($request) === 'donation' || $link->link_type === 'donation') {
            if ($err = $this->checkDonationPasswordConfirmation($request)) {
                return $err;
            }
        }

        $officer = $profile->officer;
        $validated = $this->validateLinkData($request, $officer, $link->id);

        $beforeState = [
            'title' => $link->title,
            'subtitle' => $link->subtitle,
            'url' => $link->url,
            'link_type' => $link->link_type,
            'is_highlighted' => $link->is_highlighted,
        ];

        $link->update($validated);

        BioProfileService::invalidatePublicCache($profile->slug);

        if ($link->link_type === 'donation' || $beforeState['link_type'] === 'donation') {
            AuditLogger::log(
                'bio_link.donation_changed',
                'officer_bio_link',
                $link->id,
                $beforeState,
                ['title' => $link->title, 'url' => $link->url, 'link_type' => $link->link_type],
                "Updated donation link details"
            );
        }

        return response()->json([
            'message' => 'Tautan berhasil diperbarui.',
            'link' => $link->load('recommendedOfficer'),
        ]);
    }

    /**
     * Toggle link active / inactive state.
     */
    public function toggle(OfficerBioLink $link): JsonResponse
    {
        Gate::authorize('toggle', $link);

        if ($link->profile->is_suspended) {
            return response()->json(['message' => 'Profil sedang disuspend.'], 423);
        }

        if ($link->moderation_status === 'banned') {
            return response()->json(['message' => 'Tautan ini telah di-banned oleh Admin dan tidak dapat diaktifkan kembali.'], 403);
        }

        $link->is_active = !$link->is_active;
        $link->save();

        BioProfileService::invalidatePublicCache($link->profile->slug);

        return response()->json([
            'message' => $link->is_active ? 'Tautan diaktifkan.' : 'Tautan disembunyikan.',
            'is_active' => $link->is_active,
        ]);
    }

    /**
     * Reorder links via drag-and-drop.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer|distinct',
        ]);

        $user = Auth::user();
        $officer = $this->resolveOfficer($user, $request);
        $profile = $officer->bioProfile;

        if (!$profile || $profile->is_suspended) {
            return response()->json(['message' => 'Aksi ditolak atau profil suspended.'], 423);
        }

        $existingIds = $profile->links()->pluck('id')->all();
        $submittedIds = $request->ordered_ids;

        // Anti-tampering check: submitted list must match existing link IDs
        if (count($submittedIds) !== count($existingIds) || array_diff($submittedIds, $existingIds)) {
            return response()->json([
                'message' => 'Daftar ID tautan tidak valid atau milik profil lain.',
            ], 422);
        }

        DB::transaction(function () use ($submittedIds, $profile) {
            foreach ($submittedIds as $index => $id) {
                OfficerBioLink::where('officer_bio_profile_id', $profile->id)
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        BioProfileService::invalidatePublicCache($profile->slug);

        return response()->json(['message' => 'Urutan tautan berhasil disimpan.']);
    }

    /**
     * Delete a bio link.
     */
    public function destroy(OfficerBioLink $link): JsonResponse
    {
        Gate::authorize('delete', $link);

        if ($link->profile->is_suspended) {
            return response()->json(['message' => 'Profil sedang disuspend.'], 423);
        }

        $isDonation = $link->link_type === 'donation';
        $title = $link->title;

        $link->delete();

        BioProfileService::invalidatePublicCache($link->profile->slug);

        if ($isDonation) {
            AuditLogger::log(
                'bio_link.donation_deleted',
                'officer_bio_link',
                $link->id,
                ['title' => $title],
                null,
                "Deleted donation link: {$title}"
            );
        }

        return response()->json(['message' => 'Tautan telah dihapus.']);
    }

    /**
     * Update profile settings (tagline, theme, accent, etc).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        $officer = $this->resolveOfficer($user, $request);
        $profile = $officer->bioProfile;

        if (!$profile || $profile->is_suspended) {
            return response()->json(['message' => 'Aksi ditolak atau profil suspended.'], 423);
        }

        $request->validate([
            'display_title' => 'nullable|string|max:120',
            'bio_tagline' => 'nullable|string|max:255',
            'theme_preset' => ['required', Rule::in(array_keys(config('biolinks.theme_presets', [])))],
            'button_style' => ['required', Rule::in(config('biolinks.button_styles', []))],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_live_stream_banner' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $profile->update([
            'display_title' => $request->display_title,
            'bio_tagline' => $request->bio_tagline,
            'theme_preset' => $request->theme_preset,
            'button_style' => $request->button_style,
            'accent_color' => $request->accent_color,
            'show_live_stream_banner' => $request->boolean('show_live_stream_banner', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        BioProfileService::invalidatePublicCache($profile->slug);

        return response()->json([
            'message' => 'Pengaturan tampilan profil bio berhasil disimpan.',
            'profile' => $profile,
        ]);
    }

    /**
     * View audit history of officer's own bio links.
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        $officer = $this->resolveOfficer($user, $request);
        $profile = $officer->bioProfile;

        if (!$profile) {
            return response()->json(['logs' => []]);
        }

        $linkIds = $profile->links()->pluck('id')->all();

        $logs = AuditLog::with('actor')
            ->where(function ($query) use ($profile, $linkIds) {
                $query->where(function ($q) use ($linkIds) {
                    $q->where('subject_type', 'officer_bio_link')
                        ->whereIn('subject_id', $linkIds);
                })->orWhere(function ($q) use ($profile) {
                    $q->where('subject_type', 'officer_bio_profile')
                        ->where('subject_id', $profile->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['logs' => $logs]);
    }

    /**
     * Helper to resolve the operating officer.
     */
    protected function resolveOfficer($user, Request $request): Officer
    {
        if ($user->officer) {
            return $user->officer;
        }

        if ($user->isAdmin() && $request->filled('officer_id')) {
            return Officer::findOrFail($request->officer_id);
        }

        abort(403, 'Anda tidak terhubung dengan profil perwira.');
    }

    /**
     * The link_type a store/update request will save, from its platform or link_type.
     */
    protected function requestedLinkType(Request $request): ?string
    {
        if ($request->filled('platform')) {
            return BioLinkPlatforms::get($request->input('platform'))['type'] ?? null;
        }

        return $request->input('link_type');
    }

    /**
     * Validate link payload and allowlist rules.
     */
    protected function validateLinkData(Request $request, Officer $officer, ?int $ignoreLinkId = null): array
    {
        $platformKey = $request->filled('platform') ? $request->input('platform') : null;
        $platform = null;

        if ($platformKey !== null) {
            // Simple form (S-01): platform + URL; name and type come from the platform.
            $request->validate([
                'platform' => ['required', 'string', Rule::in(array_keys(BioLinkPlatforms::all()))],
                'title' => [Rule::requiredIf($platformKey === 'custom'), 'nullable', 'string', 'max:120'],
            ], [
                'platform.in' => 'Platform tidak dikenal.',
                'title.required' => 'Nama tautan wajib diisi untuk platform Lainnya.',
            ]);

            $platform = BioLinkPlatforms::get($platformKey);
            $linkType = $platform['type'];
            $title = $platformKey === 'custom' ? trim($request->title) : $platform['name'];
        } else {
            $request->validate([
                'title' => 'required|string|max:120',
                'subtitle' => 'nullable|string|max:180',
                'link_type' => ['required', Rule::in(['donation', 'discord', 'officer_recommendation', 'social', 'custom'])],
                'badge_label' => 'nullable|string|max:30',
                'is_highlighted' => 'boolean',
                'icon' => 'nullable|string|max:60',
            ]);

            $linkType = $request->link_type;
            $title = $request->title;
        }

        $url = trim($request->input('url', ''));
        $urlHost = null;
        $recommendedOfficerId = null;

        if ($linkType === 'officer_recommendation') {
            $request->validate([
                'recommended_officer_id' => [
                    'required',
                    'exists:officers,id',
                    Rule::notIn([$officer->id]),
                ],
            ], [
                'recommended_officer_id.not_in' => 'Anda tidak dapat merekomendasikan profil Anda sendiri.',
            ]);

            // Max 4 recommendations check
            $currentRecommendationsCount = $officer->bioProfile->links()
                ->where('link_type', 'officer_recommendation')
                ->when($ignoreLinkId, fn($q) => $q->where('id', '!=', $ignoreLinkId))
                ->count();

            if ($currentRecommendationsCount >= config('biolinks.limits.max_officer_recommendations', 4)) {
                throw ValidationException::withMessages([
                    'recommended_officer_id' => 'Maksimal 4 rekan patroli yang dapat direkomendasikan.',
                ]);
            }

            $recommendedOfficerId = $request->recommended_officer_id;
            $url = null;
        } else {
            // URL validation
            if (empty($url)) {
                throw ValidationException::withMessages(['url' => 'URL tautan wajib diisi.']);
            }

            // Must use HTTPS only
            if (!str_starts_with(strtolower($url), 'https://')) {
                throw ValidationException::withMessages(['url' => 'Protokol keamanan wajib HTTPS (awali dengan https://).']);
            }

            // Disallow credentials or dangerous characters
            $parsed = parse_url($url);
            if (!$parsed || empty($parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])) {
                throw ValidationException::withMessages(['url' => 'Format URL tidak valid atau memuat kredensial ilegal.']);
            }

            $urlHost = strtolower($parsed['host']);
            // Remove 'www.' prefix for uniform matching
            $cleanHost = preg_replace('/^www\./', '', $urlHost);

            // Allowlist check: per platform, or per link_type for links without one
            $allowlists = config('biolinks.allowed_domains', []);
            if ($platform) {
                if ($platform['domains'] && !BioLinkPlatforms::hostMatches($cleanHost, $platform['domains'])) {
                    $domainList = implode(', ', $platform['domains']);
                    throw ValidationException::withMessages([
                        'url' => "Link {$platform['name']} harus berasal dari {$domainList}.",
                    ]);
                }
            } elseif (isset($allowlists[$linkType])) {
                if (!BioLinkPlatforms::hostMatches($cleanHost, $allowlists[$linkType])) {
                    $domainList = implode(', ', $allowlists[$linkType]);
                    throw ValidationException::withMessages([
                        'url' => "Domain '{$cleanHost}' tidak diizinkan untuk tipe {$linkType}. Domain yang diterima: {$domainList}",
                    ]);
                }
            }

            // Denylist check: check exact host and parent domains (B-06)
            $hostParts = explode('.', $cleanHost);
            $candidateHosts = [];
            while (count($hostParts) >= 2) {
                $candidateHosts[] = implode('.', $hostParts);
                array_shift($hostParts);
            }

            if (LinkDomainDenylist::whereIn('host', $candidateHosts)->exists()) {
                throw ValidationException::withMessages([
                    'url' => "Domain '{$cleanHost}' telah diblokir secara permanen oleh Admin Kepolisian.",
                ]);
            }
        }

        return [
            'title' => $title,
            'subtitle' => $request->subtitle,
            'url' => $url,
            'url_host' => $cleanHost ?? null,
            'icon' => $request->icon ?: 'external-link',
            'link_type' => $linkType,
            'platform' => $linkType === 'officer_recommendation'
                ? null
                : ($platformKey ?? BioLinkPlatforms::detect($cleanHost ?? null)),
            'recommended_officer_id' => $recommendedOfficerId,
            'badge_label' => $request->badge_label,
            'is_highlighted' => $request->boolean('is_highlighted', false),
        ];
    }

    /**
     * Verify password confirmation when modifying financial donation links (T-03).
     */
    protected function checkDonationPasswordConfirmation(Request $request): ?JsonResponse
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at', 0);
        $timeout = config('auth.password_timeout', 900);

        if (time() - $confirmedAt <= $timeout) {
            return null;
        }

        if ($request->filled('password_confirmation_inline')) {
            if (Hash::check($request->password_confirmation_inline, $request->user()->password)) {
                $request->session()->put('auth.password_confirmed_at', time());
                return null;
            }

            return response()->json([
                'message' => 'Password konfirmasi salah.',
                'error' => 'password_mismatch',
            ], 422);
        }

        return response()->json([
            'message' => 'Konfirmasi password diperlukan untuk mengamankan perubahan tautan donasi finansial.',
            'error' => 'password_confirmation_required',
        ], 423);
    }
}
