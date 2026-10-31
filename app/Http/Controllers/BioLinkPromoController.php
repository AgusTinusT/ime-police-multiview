<?php

namespace App\Http\Controllers;

use App\Models\OfficerBioProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BioLinkPromoController extends Controller
{
    /**
     * Display the bio-link promotional story page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        $exampleSlug = OfficerBioProfile::whereHas('officer', function ($query) {
            $query->whereNotNull('user_id');
        })->value('slug');

        return Inertia::render('BioLinkPromo', [
            'themes' => config('biolinks.theme_presets', []),
            'exampleSlug' => $exampleSlug,
            'isOfficer' => $user ? $user->isOfficer() : false,
            'isAuthenticated' => $user !== null,
        ]);
    }
}

