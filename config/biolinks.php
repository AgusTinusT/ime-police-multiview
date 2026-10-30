<?php

/*
|--------------------------------------------------------------------------
| Link Platforms
|--------------------------------------------------------------------------
| An officer adds a link by picking a platform and pasting its URL; the name
| and icon come from here. `type` is stored as the link_type (donation links
| need password confirmation). The URL host must match one of `domains`
| (subdomains allowed); `custom` accepts any HTTPS host not on the denylist.
| Keys are stored in officer_bio_links.platform — never rename one.
*/
$platforms = [
    'saweria' => ['name' => 'Saweria', 'type' => 'donation', 'domains' => ['saweria.co']],
    'bagibagi' => ['name' => 'Bagibagi', 'type' => 'donation', 'domains' => ['bagibagi.co']],
    'trakteer' => ['name' => 'Trakteer', 'type' => 'donation', 'domains' => ['trakteer.id']],
    'tako' => ['name' => 'Tako', 'type' => 'donation', 'domains' => ['tako.id']],
    'sociabuzz' => ['name' => 'Sociabuzz', 'type' => 'donation', 'domains' => ['sociabuzz.com']],
    'instagram' => ['name' => 'Instagram', 'type' => 'social', 'domains' => ['instagram.com']],
    'tiktok' => ['name' => 'TikTok', 'type' => 'social', 'domains' => ['tiktok.com']],
    'youtube' => ['name' => 'YouTube', 'type' => 'social', 'domains' => ['youtube.com', 'youtu.be']],
    'discord' => ['name' => 'Discord', 'type' => 'discord', 'domains' => ['discord.gg', 'discord.com']],
    'twitch' => ['name' => 'Twitch', 'type' => 'social', 'domains' => ['twitch.tv']],
    'x' => ['name' => 'X', 'type' => 'social', 'domains' => ['x.com', 'twitter.com']],
    'facebook' => ['name' => 'Facebook', 'type' => 'social', 'domains' => ['facebook.com']],
    'custom' => ['name' => 'Lainnya', 'type' => 'custom', 'domains' => []],
];

$allowedDomains = [];
foreach ($platforms as $platform) {
    if ($platform['domains']) {
        $allowedDomains[$platform['type']] = [...($allowedDomains[$platform['type']] ?? []), ...$platform['domains']];
    }
}

return [
    'platforms' => $platforms,

    /*
    |--------------------------------------------------------------------------
    | Link Domain Allowlists by Link Type
    |--------------------------------------------------------------------------
    | Derived from the platforms above. Used for links saved without a
    | platform (older clients) so they cannot point at phishing domains.
    */
    'allowed_domains' => $allowedDomains,

    /*
    |--------------------------------------------------------------------------
    | Avatar Image Host Allowlist
    |--------------------------------------------------------------------------
    | Only trusted YouTube/Google image CDN hosts are allowed for officer avatars.
    */
    'avatar_hosts' => [
        'yt3.ggpht.com',
        'yt3.googleusercontent.com',
        'i.ytimg.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved Public Bio-Link Slugs
    |--------------------------------------------------------------------------
    | Public bio-link slugs matching these values cannot be registered.
    */
    'reserved_slugs' => [
        'admin',
        'api',
        'dashboard',
        'login',
        'register',
        'logout',
        'officers',
        'multiview',
        'clipper',
        'police-clipper',
        'pricing',
        'links',
        'go',
        'profile',
        'settings',
        'sasp',
        'lspd',
        'bcso',
        'sapr',
        'dispatch',
        'command',
        'moderator',
        'official',
        'tac',
        'clips',
        'video',
        'stream',
        'streams',
        'watchlist',
        'announcements',
        'chat',
        'verify-email',
        'confirm-password',
        'forgot-password',
        'reset-password',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved Impersonation Keywords
    |--------------------------------------------------------------------------
    | Used by NameSkeleton detector to prevent regular users from impersonating
    | command staff or tactical authorities.
    */
    'reserved_keywords' => [
        'admin',
        'sasp',
        'lspd',
        'bcso',
        'sapr',
        'command',
        'moderator',
        'official',
        'dispatch',
        'trooper',
        'sheriff',
        'captain',
        'lieutenant',
        'sergeant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bio-Link Theme Presets (theme contract)
    |--------------------------------------------------------------------------
    | Every visual decision a theme may make lives here; link components only
    | read the resulting tokens, so a new theme never needs per-button changes.
    |
    | accent        Main colour: borders, stripes, hover, section icons, CTAs.
    | accent_2      Highlight colour for support/donation links. Must differ
    |               clearly from `accent`; never close to the LIVE red.
    | surface       Optional card colour (hex). null = the default card.
    | backdrop      Background layer: 'grid' (default) or a key registered in
    |               BioLinkView.vue (e.g. 'srt_pursuit').
    | button_style  Optional forced button style; null = the officer's choice.
    | heading       'default', 'pursuit' (italic caps) or 'stencil' (wide caps).
    |
    | Fixed meanings are NOT themeable: LIVE red, department badges, errors.
    | Preview every theme with all link types at /dev/bio-themes.
    */
    'theme_presets' => [
        'default_glass' => [
            'name' => 'Default Tactical Glass',
            'description' => 'Dark slate command center with cyan glow',
            'accent' => '#06b6d4',
            'accent_2' => '#f59e0b',
            'surface' => null,
            'backdrop' => 'glass_radar',
            'button_style' => null,
            'heading' => 'default',
            'agency' => null,
        ],
        'sasp_trooper' => [
            'name' => 'SASP State Trooper',
            'description' => 'Tactical black with amber gold accent',
            'accent' => '#f59e0b',
            'accent_2' => '#e2e8f0',
            'surface' => null,
            'backdrop' => 'trooper_highway',
            'button_style' => null,
            'heading' => 'default',
            'agency' => 'SASP',
        ],
        'lspd_metro' => [
            'name' => 'LSPD Metropolitan',
            'description' => 'Navy blue with reflective white accent',
            'accent' => '#3b82f6',
            'accent_2' => '#f59e0b',
            'surface' => null,
            'backdrop' => 'metro_skyline',
            'button_style' => null,
            'heading' => 'default',
            'agency' => 'LSPD',
        ],
        'bcso_sheriff' => [
            'name' => 'BCSO Sheriff County',
            'description' => 'Olive green with khaki gold accent',
            'accent' => '#84cc16',
            'accent_2' => '#fbbf24',
            'surface' => null,
            'backdrop' => 'county_star',
            'button_style' => null,
            'heading' => 'default',
            'agency' => 'BCSO',
        ],
        'sapr_ranger' => [
            'name' => 'SAPR San Andreas Park Ranger',
            'description' => 'Forest green with timber bronze accent',
            'accent' => '#10b981',
            'accent_2' => '#f59e0b',
            'surface' => null,
            'backdrop' => 'ranger_forest',
            'button_style' => null,
            'heading' => 'default',
            'agency' => 'SAPR',
        ],
        'stealth_interceptor' => [
            'name' => 'Stealth Interceptor',
            'description' => 'Carbon graphite with neon cyan accent',
            'accent' => '#06b6d4',
            'accent_2' => '#e2e8f0',
            'surface' => '#0b0f19',
            'backdrop' => 'stealth_carbon',
            'button_style' => null,
            'heading' => 'default',
            'agency' => null,
        ],
        // Pursuit look inspired by the SRT stream thumbnails: oversized SRT
        // wordmark, red slash, police-blue siren glow and speed lines.
        'srt_pursuit' => [
            'name' => 'SRT Pursuit',
            'description' => 'Wordmark SRT, coretan merah & biru sirene pengejaran',
            'accent' => '#c026d3',
            'accent_2' => '#38bdf8',
            'surface' => null,
            'backdrop' => 'srt_pursuit',
            'button_style' => 'pursuit_stripe',
            'heading' => 'pursuit',
            'agency' => null,
        ],
        // Night operations look of the SWAT kit: all-black gear, night-vision
        // green glow from the goggles, white vest lettering.
        'swat_nightops' => [
            'name' => 'SWAT Night Ops',
            'description' => 'Hitam taktis, glow hijau night-vision & tulisan rompi putih',
            'accent' => '#4ade80',
            'accent_2' => '#f1f5f9',
            'surface' => '#0b0d10',
            'backdrop' => 'swat_nightops',
            'button_style' => 'plate_carrier',
            'heading' => 'stencil',
            'agency' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Button Styles
    |--------------------------------------------------------------------------
    */
    'button_styles' => [
        'glass_tactical',
        'rounded_edge',
        'sharp_tactical',
        'solid_bold',
        'outlined_glow',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits & Quotas
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_links_per_profile' => 30,
        'max_officer_recommendations' => 4,
        'max_tagline_length' => 255,
        'max_display_title_length' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Discord Tactical Alert Webhook
    |--------------------------------------------------------------------------
    */
    'discord_webhook_url' => env('DISCORD_TACTICAL_WEBHOOK_URL', null),
];
