<?php

namespace App\Http\Controllers;

use App\Models\OfficerBioProfile;
use App\Models\Squad;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate dynamic sitemap.xml for search engines.
     */
    public function index(): Response
    {
        $xml = Cache::remember('seo_sitemap_xml', 7200, function () {
            return $this->buildSitemapXml();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Build standard XML sitemap string.
     */
    private function buildSitemapXml(): string
    {
        $baseUrl = rtrim(config('app.url') ?: url('/'), '/');
        $now = now()->toAtomString();

        // 1. Static public core routes
        $urls = [
            [
                'loc' => "{$baseUrl}/",
                'lastmod' => $now,
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ],
            [
                'loc' => "{$baseUrl}/multiview",
                'lastmod' => $now,
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ],
            [
                'loc' => "{$baseUrl}/officers",
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'loc' => "{$baseUrl}/ranking",
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            [
                'loc' => "{$baseUrl}/action-clips",
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            [
                'loc' => "{$baseUrl}/clipper",
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            [
                'loc' => "{$baseUrl}/bio-link",
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            [
                'loc' => "{$baseUrl}/faq",
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ],
            [
                'loc' => "{$baseUrl}/about",
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => "{$baseUrl}/updates",
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ],
            [
                'loc' => "{$baseUrl}/feedback",
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
        ];

        // 2. Dynamic Officer Bio-Links (/@{slug})
        $bioProfiles = OfficerBioProfile::where('is_suspended', false)
            ->whereNotNull('slug')
            ->whereHas('officer', fn ($q) => $q->where('is_active', true))
            ->get();

        foreach ($bioProfiles as $profile) {
            $lastmod = $profile->updated_at ? $profile->updated_at->toAtomString() : $now;
            $urls[] = [
                'loc' => "{$baseUrl}/@{$profile->slug}",
                'lastmod' => $lastmod,
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        // 3. Dynamic Public Squads (/multiview?squad={slug})
        $publicSquads = Squad::where('visibility', 'public')
            ->where('is_hidden', false)
            ->get();

        foreach ($publicSquads as $squad) {
            $lastmod = $squad->updated_at ? $squad->updated_at->toAtomString() : $now;
            $urls[] = [
                'loc' => "{$baseUrl}/multiview?squad={$squad->slug}",
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        // Generate XML Output
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $item) {
            $loc = htmlspecialchars($item['loc'], ENT_XML1, 'UTF-8');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>{$item['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$item['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$item['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}

