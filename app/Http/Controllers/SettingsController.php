<?php

namespace App\Http\Controllers;

use App\Http\Controllers\OfficerBioLinkController;
use App\Http\Controllers\OfficerSelfProfileController;
use App\Models\Officer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the unified settings page (SET-01).
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        $rosterOfficers = Officer::roster()
            ->orderBy('department')
            ->orderBy('officer_name')
            ->get(['id', 'officer_name', 'streamer_name', 'callsign', 'department', 'rank', 'avatar_url', 'avatar_hidden'])
            ->map(fn (Officer $o) => [
                'id' => $o->id,
                'officer_name' => $o->officer_name,
                'streamer_name' => $o->streamer_name,
                'callsign' => $o->callsign,
                'department' => $o->department,
                'rank' => $o->rank,
                'avatar_url' => $o->public_avatar_url,
            ])
            ->values();

        $props = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'currentTab' => $request->query('tab', 'account'),
            'biolinksAdminEmail' => config('biolinks.admin_email'),
            'biolinksAdminWhatsapp' => config('biolinks.admin_whatsapp', '6281234567890'),
            'bioThemes' => config('biolinks.theme_presets', []),
            'rosterOfficers' => $rosterOfficers,
        ];

        if ($user && $user->isOfficer()) {
            $props = array_merge(
                $props,
                OfficerSelfProfileController::pageProps($request),
                OfficerBioLinkController::pageProps($request),
            );
        }

        return Inertia::render('Settings/Index', $props);
    }
}

