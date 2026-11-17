<?php

namespace App\Support;

/**
 * Lookup helpers for the link platforms in config('biolinks.platforms').
 */
class BioLinkPlatforms
{
    public static function all(): array
    {
        return config('biolinks.platforms', []);
    }

    public static function get(?string $key): ?array
    {
        return $key === null ? null : (self::all()[$key] ?? null);
    }

    /**
     * Whether $host (lowercase, without "www.") belongs to one of the domains.
     */
    public static function hostMatches(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The platform key a URL host belongs to, or 'custom' when none matches.
     */
    public static function detect(?string $host): string
    {
        $host = preg_replace('/^www\./', '', strtolower((string) $host));

        foreach (self::all() as $key => $platform) {
            if ($platform['domains'] && self::hostMatches($host, $platform['domains'])) {
                return $key;
            }
        }

        return 'custom';
    }
}
