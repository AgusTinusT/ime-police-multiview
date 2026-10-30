<?php

namespace App\Http\Controllers;

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

        $props = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'currentTab' => $request->query('tab', 'account'),
        ];

        if ($user && $user->isOfficer()) {
            $officerProps = OfficerSelfProfileController::pageProps($request);
            $props = array_merge($props, $officerProps);
        }

        return Inertia::render('Settings/Index', $props);
    }
}

