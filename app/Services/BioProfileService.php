<?php

namespace App\Services;

use App\Models\BioSlugRedirect;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use Illuminate\Support\Str;

class BioProfileService
{
    /**
     * Ensure an OfficerBioProfile exists for an officer, generating a slug if new.
     */
    public static function ensureBioProfile(Officer $officer): OfficerBioProfile
    {
        if ($officer->bioProfile) {
            return $officer->bioProfile;
        }

        $slug = self::generateUniqueSlug($officer);

        return OfficerBioProfile::create([
            'officer_id' => $officer->id,
            'slug' => $slug,
            'display_title' => $officer->officer_name,
            'bio_tagline' => "Official SASP Tactical Channel of {$officer->streamer_name} ({$officer->callsign})",
            'theme_preset' => self::detectDefaultTheme($officer),
            'button_style' => 'glass_tactical',
            'accent_color' => self::detectDefaultAccent($officer),
            'show_live_stream_banner' => true,
            'is_active' => true,
            'is_suspended' => false,
        ]);
    }

    /**
     * Generate an initial unique, clean slug for an officer based on handle.
     */
    public static function generateUniqueSlug(Officer $officer): string
    {
        $raw = ltrim($officer->handle ?: $officer->streamer_name, '@');
        $baseSlug = Str::slug($raw, '-');
        $baseSlug = preg_replace('/[^a-z0-9._-]/', '', strtolower($baseSlug));
        
        if (empty($baseSlug) || strlen($baseSlug) < 3) {
            $baseSlug = 'officer-' . strtolower($officer->callsign ? Str::slug($officer->callsign) : $officer->id);
        }

        $slug = substr($baseSlug, 0, 35);
        $candidate = $slug;
        $counter = 1;

        while (self::isSlugTakenOrReserved($candidate)) {
            $suffix = '-' . ($officer->badge_number ? str_replace('#', '', $officer->badge_number) : $counter);
            $candidate = substr($slug, 0, 35 - strlen($suffix)) . $suffix;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Check if a slug is taken in officer_bio_profiles, in redirects, or is reserved.
     */
    public static function isSlugTakenOrReserved(string $slug, ?int $ignoreProfileId = null): bool
    {
        $slug = strtolower(trim($slug));

        // 1. Reserved list
        $reserved = config('biolinks.reserved_slugs', []);
        if (in_array($slug, $reserved, true)) {
            return true;
        }

        // 2. Active profiles
        $query = OfficerBioProfile::where('slug', $slug);
        if ($ignoreProfileId) {
            $query->where('id', '!=', $ignoreProfileId);
        }
        if ($query->exists()) {
            return true;
        }

        // 3. Redirects within 90 days
        if (BioSlugRedirect::where('old_slug', $slug)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Validate a user-requested or admin-requested slug format and availability.
     */
    public static function validateSlug(string $slug, ?int $ignoreProfileId = null): ?string
    {
        $slug = strtolower(trim($slug));

        if (!preg_match('/^[a-z0-9](?:[a-z0-9._-]{1,38}[a-z0-9])$/', $slug)) {
            return 'Format slug tidak valid. Slug hanya boleh memuat huruf kecil, angka, titik, strip, atau underscore (3-40 karakter) dan tidak boleh berawal/berakhir dengan simbol.';
        }

        if (self::isSlugTakenOrReserved($slug, $ignoreProfileId)) {
            return 'Slug tersebut telah dipakai atau merupakan reserved URL resmi.';
        }

        return null;
    }

    /**
     * Detect default theme preset based on officer agency/department.
     */
    protected static function detectDefaultTheme(Officer $officer): string
    {
        $dept = strtoupper($officer->department ?? '');
        return match ($dept) {
            'SASP' => 'sasp_trooper',
            'LSPD' => 'lspd_metro',
            'BCSO' => 'bcso_sheriff',
            'SAPR' => 'sapr_ranger',
            default => 'default_glass',
        };
    }

    /**
     * Detect default accent color.
     */
    protected static function detectDefaultAccent(Officer $officer): string
    {
        $dept = strtoupper($officer->department ?? '');
        return match ($dept) {
            'SASP' => '#f59e0b',
            'LSPD' => '#3b82f6',
            'BCSO' => '#84cc16',
            'SAPR' => '#10b981',
            default => '#06b6d4',
        };
    }
}
