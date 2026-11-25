<?php

namespace App\Http\Controllers;

use App\Models\OfficerBioProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BioLinkPromoController extends Controller
{
    /**
     * Display the active officer bio-links directory & promotional story page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        $activeBioLinks = OfficerBioProfile::query()
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->whereHas('officer', function ($query) {
                $query->where('is_active', true)
                    ->whereNotNull('user_id');
            })
            ->with([
                'officer.agency:id,agency_code,agency_name',
                'officer.rankRelation:id,rank_title',
                'officer.activeStream:id,officer_id,video_id,status',
            ])
            ->withCount('activeLinks')
            ->get()
            ->map(function (OfficerBioProfile $profile) {
                $officer = $profile->officer;
                $department = $officer->agency?->agency_code ?? $officer->department;

                return [
                    'id' => $profile->id,
                    'officer_id' => $officer->id,
                    'slug' => $profile->slug,
                    'officer_name' => $officer->officer_name,
                    'streamer_name' => $officer->streamer_name,
                    'callsign' => $officer->callsign,
                    'department' => $department,
                    'rank' => $officer->rankRelation?->rank_title ?? $officer->rank,
                    'avatar_url' => $officer->public_avatar_url,
                    'tagline' => $profile->bio_tagline,
                    'links_count' => (int) $profile->active_links_count,
                    'is_live' => $officer->activeStream !== null,
                    'live_video_id' => $officer->activeStream?->video_id,
                ];
            })
            ->sort(function (array $a, array $b) {
                return ($b['is_live'] <=> $a['is_live'])
                    ?: ($b['links_count'] <=> $a['links_count'])
                    ?: strcasecmp($a['officer_name'] ?? '', $b['officer_name'] ?? '');
            })
            ->values();

        $exampleSlug = $activeBioLinks->first()['slug'] ?? null;

        return Inertia::render('BioLinkPromo', [
            'themes' => config('biolinks.theme_presets', []),
            'exampleSlug' => $exampleSlug,
            'activeBioLinks' => $activeBioLinks,
            'isOfficer' => $user ? $user->isOfficer() : false,
            'isAuthenticated' => $user !== null,
            'meta' => [
                'title' => 'Officer Tactical Bio-Link Hub — Daftar Bio-Link Resmi Perwira',
                'description' => 'Jelajahi daftar bio-link taktis resmi para perwira kepolisian SASP MotionLife RP beralamat /@slug.',
                'image' => asset('images/cars-police-twopov.jpg'),
                'url' => url('/bio-link'),
                'canonical' => url('/bio-link'),
                'type' => 'website',
            ],
        ]);
    }
}
