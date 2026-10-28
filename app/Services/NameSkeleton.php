<?php

namespace App\Services;

class NameSkeleton
{
    /**
     * Map of common visual homoglyphs and leetspeak to base characters.
     */
    protected static array $homoglyphs = [
        'rn' => 'm',
        'vv' => 'w',
        '1' => 'l',
        'i' => 'l',
        '|' => 'l',
        '!' => 'l',
        '0' => 'o',
        '5' => 's',
        '$' => 's',
        '3' => 'e',
        '4' => 'a',
        '@' => 'a',
        '8' => 'b',
    ];

    /**
     * Confusables map (Cyrillic & Greek homoglyphs to Latin).
     */
    protected static array $confusables = [
        // Cyrillic
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l',
        'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'p', 'с' => 'c',
        'т' => 't', 'у' => 'y', 'ф' => 'f', 'х' => 'x', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
        'ю' => 'yu', 'я' => 'ya',
        // Greek
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z',
        'η' => 'e', 'θ' => 'th', 'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm',
        'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's',
        'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'o',
    ];

    /**
     * Normalize a name to its canonical skeleton form.
     */
    public static function make(?string $name): string
    {
        if (empty($name)) {
            return '';
        }

        // 1. Unicode NFKC normalization if extension available
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($name, \Normalizer::FORM_KC);
            if ($normalized !== false) {
                $name = $normalized;
            }
        }

        // 2. Strip zero-width and control characters (\p{Cf}, \p{Cc})
        $name = preg_replace('/[\p{Cf}\p{Cc}]/u', '', $name);

        // 3. Lowercase
        $name = mb_strtolower($name, 'UTF-8');

        // 4. Transliterate known Cyrillic/Greek confusables
        $name = strtr($name, self::$confusables);

        // 5. Homoglyph and leetspeak substitution (handle multi-char 'rn' first)
        $name = str_replace('rn', 'm', $name);
        $name = str_replace('vv', 'w', $name);
        $name = strtr($name, [
            '1' => 'l',
            'i' => 'l',
            '|' => 'l',
            '!' => 'l',
            '0' => 'o',
            '5' => 's',
            '$' => 's',
            '3' => 'e',
            '4' => 'a',
            '@' => 'a',
            '8' => 'b',
        ]);

        // 6. Remove all spaces, punctuation, dots, hyphens, and non-alphanumeric chars
        $name = preg_replace('/[^a-z0-9]/', '', $name);

        return $name ?? '';
    }

    /**
     * Check if a candidate name conflicts with any reserved keywords or existing officers.
     * Returns an array with ['blocked' => bool, 'reason' => ?string, 'flagged' => bool].
     */
    public static function checkName(string $name, ?int $ignoreUserId = null): array
    {
        $skeleton = self::make($name);

        if (empty($skeleton)) {
            return ['blocked' => false, 'reason' => null, 'flagged' => false];
        }

        // 1. Check against reserved keywords
        $reserved = config('biolinks.reserved_keywords', [
            'admin', 'sasp', 'lspd', 'bcso', 'sapr', 'command', 'moderator', 'official', 'dispatch',
        ]);

        foreach ($reserved as $keyword) {
            $keywordSkeleton = self::make($keyword);
            if ($skeleton === $keywordSkeleton) {
                return [
                    'blocked' => true,
                    'reason' => 'Nama mengandung kata kunci otoritas kepolisian atau reserved role yang dilarang.',
                    'flagged' => false,
                ];
            }
        }

        // 2. Query all active officers
        $officers = \App\Models\Officer::where('is_active', true)->get([
            'id', 'user_id', 'officer_name', 'streamer_name', 'handle',
        ]);

        foreach ($officers as $officer) {
            // If the user being checked is already bound to this officer, allow it
            if ($ignoreUserId && $officer->user_id === $ignoreUserId) {
                continue;
            }

            $candidates = [
                self::make($officer->officer_name),
                self::make($officer->streamer_name),
                self::make(ltrim($officer->handle, '@')),
            ];

            foreach ($candidates as $targetSkeleton) {
                if (empty($targetSkeleton)) {
                    continue;
                }

                // Exact skeleton match -> BLOCK
                if ($skeleton === $targetSkeleton) {
                    return [
                        'blocked' => true,
                        'reason' => 'Nama yang Anda gunakan menyerupai nama perwira kepolisian terdaftar. Silakan gunakan nama lain atau hubungi Admin jika Anda adalah perwira bersangkutan.',
                        'flagged' => false,
                    ];
                }

                // Fuzzy distance check for longer names (length >= 6) -> FLAG
                if (strlen($skeleton) >= 6 && strlen($targetSkeleton) >= 6) {
                    $dist = levenshtein($skeleton, $targetSkeleton);
                    if ($dist <= 1) {
                        return [
                            'blocked' => false,
                            'reason' => "Nama mirip dengan perwira ({$officer->officer_name}). Ditandai untuk peninjauan moderasi.",
                            'flagged' => true,
                        ];
                    }
                }
            }
        }

        return ['blocked' => false, 'reason' => null, 'flagged' => false];
    }
}
