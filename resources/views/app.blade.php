<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $meta = $page['props']['meta'] ?? [];
            $metaTitle = $meta['title'] ?? 'IME RP — SASP Police Duty | Live Officer Bodycam & Multiview';
            $metaDesc = $meta['description'] ?? 'Pusat pemantauan taktis & live streaming bodycam perwira kepolisian IME Roleplay (LSPD, BCSO, SASP, SAPR). Pantau seluruh siaran langsung perwira patroli secara multiview interaktif.';
            $metaImage = $meta['image'] ?? asset('images/icons/icon-512x512.png');
            $metaUrl = $meta['url'] ?? url()->current();
            $metaType = $meta['type'] ?? 'website';
            $canonicalUrl = $meta['canonical'] ?? url()->current();
            $isNoIndex = !empty($meta['noindex']);
            $googleVerification = config('services.google.site_verification', env('GOOGLE_SITE_VERIFICATION'));

            $siteGraph = [
                '@type' => 'WebSite',
                '@id' => url('/') . '/#website',
                'url' => url('/'),
                'name' => 'IME Police Duty Multiview',
                'description' => 'Pusat pemantauan taktis & live streaming bodycam perwira kepolisian IME Roleplay',
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'SASP Tactical Command Center',
                    'url' => url('/'),
                    'logo' => asset('images/icons/icon-512x512.png'),
                ],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/officers') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];

            $graph = [$siteGraph];
            if (!empty($meta['jsonld'])) {
                $graph[] = $meta['jsonld'];
            }

            $structuredData = [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ];
        @endphp

        <title inertia>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDesc }}">
        <meta name="keywords" content="IME RP, Police Duty, SASP, LSPD, BCSO, SAPR, Multiview, FiveM, GTA V Roleplay, Bodycam Polisi">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta name="robots" content="{{ $isNoIndex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }}">

        @if (!empty($googleVerification))
            <meta name="google-site-verification" content="{{ $googleVerification }}">
        @endif

        <!-- OpenGraph Social Media Meta Tags -->
        <meta property="og:site_name" content="IME Police Duty Multiview">
        <meta property="og:locale" content="id_ID">
        <meta property="og:type" content="{{ $metaType }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDesc }}">
        <meta property="og:image" content="{{ $metaImage }}">
        <meta property="og:url" content="{{ $metaUrl }}">

        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDesc }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        <!-- Structured Data (JSON-LD) for Search Engines -->
        <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>

        <!-- Favicon / Browser Icon (With Cache Buster) -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">

        <!-- PWA Web App Manifest & Mobile Integration -->
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/images/icons/apple-touch-icon.png">
        <meta name="theme-color" content="#0b1320">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="IME Police">

        <!-- Preconnect Fast Network Origins (Fonts & YouTube Assets) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://i.ytimg.com">
        <link rel="preconnect" href="https://www.youtube.com">

        <!-- Fonts: Rajdhani, Plus Jakarta Sans, JetBrains Mono (With display=swap for high FCP/LCP performance) -->
        <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Rajdhani:wght@500;600;700&display=swap" />
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        @php
            $gaId = config('services.google_analytics.id');
        @endphp
        @if (!empty($gaId))
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ $gaId }}', {
                    send_page_view: false
                });
            </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!-- Register PWA Service Worker -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').then(function(reg) {
                        console.log('PWA ServiceWorker registered with scope: ', reg.scope);
                    }).catch(function(err) {
                        console.log('PWA ServiceWorker registration failed: ', err);
                    });
                });
            }
        </script>
    </body>
</html>
