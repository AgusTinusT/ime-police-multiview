<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Link Domain Allowlists by Link Type
    |--------------------------------------------------------------------------
    | Enforced at validation time to ensure links cannot be pointed to arbitrary
    | phishing or scam domains.
    */
    'allowed_domains' => [
        'donation' => [
            'saweria.co',
            'bagibagi.co',
            'trakteer.id',
            'sociabuzz.com',
        ],
        'discord' => [
            'discord.gg',
            'discord.com',
        ],
        'social' => [
            'youtube.com',
            'youtu.be',
            'tiktok.com',
            'instagram.com',
            'x.com',
            'twitter.com',
            'twitch.tv',
            'facebook.com',
        ],
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
        'chief',
        'captain',
        'lieutenant',
        'sergeant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Department Theme Presets
    |--------------------------------------------------------------------------
    */
    'theme_presets' => [
        'default_glass' => [
            'name' => 'Default Tactical Glass',
            'description' => 'Dark slate command center with cyan glow',
            'accent' => '#06b6d4',
            'agency' => null,
        ],
        'sasp_trooper' => [
            'name' => 'SASP State Trooper',
            'description' => 'Tactical black with amber gold accent',
            'accent' => '#f59e0b',
            'agency' => 'SASP',
        ],
        'lspd_metro' => [
            'name' => 'LSPD Metropolitan',
            'description' => 'Navy blue with reflective white accent',
            'accent' => '#3b82f6',
            'agency' => 'LSPD',
        ],
        'bcso_sheriff' => [
            'name' => 'BCSO Sheriff County',
            'description' => 'Olive green with khaki gold accent',
            'accent' => '#84cc16',
            'agency' => 'BCSO',
        ],
        'sapr_ranger' => [
            'name' => 'SAPR San Andreas Park Ranger',
            'description' => 'Forest green with timber bronze accent',
            'accent' => '#10b981',
            'agency' => 'SAPR',
        ],
        'stealth_interceptor' => [
            'name' => 'Stealth Interceptor',
            'description' => 'Carbon graphite with neon cyan accent',
            'accent' => '#06b6d4',
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
