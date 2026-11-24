<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\UriInterface;

/**
 * Copies a custom officer avatar from a link into object storage, so the
 * photo keeps showing after the source link expires, moves or blocks
 * hotlinking. The stored file is served by OfficerAvatarController at
 * /media/avatars/{file}.
 */
class OfficerAvatarStore
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public const ROUTE_PREFIX = '/media/avatars/';

    public static function enabled(): bool
    {
        return (bool) config('avatars.enabled', true);
    }

    /**
     * Whether a submitted avatar link should be downloaded and stored:
     * an http(s) link that is not a generated placeholder nor already ours.
     */
    public static function shouldStore(?string $url): bool
    {
        if (! self::enabled() || blank($url)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return ! str_contains($url, 'dicebear.com') && ! self::isStoredUrl($url);
    }

    /**
     * True if the URL already points at a stored avatar of this app.
     */
    public static function isStoredUrl(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return str_starts_with($path, self::ROUTE_PREFIX);
    }

    /**
     * True if $url is the served URL of the stored file at $path (host-agnostic).
     */
    public static function urlMatchesPath(?string $url, string $path): bool
    {
        return self::isStoredUrl($url)
            && basename((string) parse_url($url, PHP_URL_PATH)) === basename($path);
    }

    /**
     * Public URL for a stored avatar path.
     */
    public static function urlFor(string $path): string
    {
        return url(self::ROUTE_PREFIX . basename($path));
    }

    /**
     * Download the image behind $sourceUrl, validate it and store it.
     * Returns the officer columns to save. Throws a ValidationException on
     * $field with a readable reason when the link cannot be used.
     *
     * @return array{avatar_url: string, avatar_disk: string, avatar_path: string, avatar_source_url: string}
     */
    public function store(string $sourceUrl, string $field = 'avatar_url'): array
    {
        $bytes = $this->download($sourceUrl, $field);

        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        if (! $mime || ! isset(self::MIME_EXTENSIONS[$mime])) {
            throw ValidationException::withMessages([
                $field => 'Tautan tidak berisi gambar JPG, PNG, GIF, atau WebP yang valid.',
            ]);
        }

        $disk = (string) config('avatars.disk', 's3');
        $path = trim((string) config('avatars.directory', 'officer-avatars'), '/')
            . '/' . Str::lower(Str::random(24)) . '.' . self::MIME_EXTENSIONS[$mime];

        try {
            $ok = Storage::disk($disk)->put($path, $bytes, ['ContentType' => $mime]);
        } catch (\Throwable $e) {
            Log::warning("Avatar upload to disk '{$disk}' failed: " . $e->getMessage());
            $ok = false;
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                $field => 'Foto profil gagal disimpan ke penyimpanan. Coba lagi beberapa saat lagi.',
            ]);
        }

        return [
            'avatar_url' => self::urlFor($path),
            'avatar_disk' => $disk,
            'avatar_path' => $path,
            'avatar_source_url' => Str::limit($sourceUrl, 500, ''),
        ];
    }

    /**
     * Remove a stored avatar file, ignoring storage errors.
     */
    public static function delete(?string $disk, ?string $path): void
    {
        if (! $disk || ! $path) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            Log::warning("Avatar delete on disk '{$disk}' failed for {$path}: " . $e->getMessage());
        }
    }

    private function download(string $url, string $field): string
    {
        $this->assertPublicHost($url, $field);

        $maxBytes = max(1, (int) config('avatars.max_kb', 5120)) * 1024;

        try {
            $response = Http::timeout((int) config('avatars.timeout', 15))
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; AvatarFetcher/1.0)',
                    'Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/*;q=0.8',
                ])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'protocols' => ['http', 'https'],
                        'on_redirect' => function ($request, $response, UriInterface $uri) use ($field) {
                            $this->assertPublicHost((string) $uri, $field);
                        },
                    ],
                ])
                ->get($url);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof ValidationException) {
                throw $previous;
            }
            throw ValidationException::withMessages([
                $field => 'Gambar dari tautan gagal diunduh (tidak dapat terhubung). Periksa kembali tautannya.',
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                $field => "Gambar dari tautan gagal diunduh (HTTP {$response->status()}). Pastikan tautan bisa dibuka publik.",
            ]);
        }

        $bytes = $response->body();
        if ($bytes === '') {
            throw ValidationException::withMessages([
                $field => 'Tautan tidak mengembalikan gambar (isi kosong).',
            ]);
        }

        if (strlen($bytes) > $maxBytes) {
            throw ValidationException::withMessages([
                $field => 'Ukuran gambar melebihi batas ' . round($maxBytes / 1048576, 1) . ' MB.',
            ]);
        }

        return $bytes;
    }

    /**
     * Refuse links that resolve to private, loopback or reserved addresses so
     * the server cannot be pointed at its own network.
     */
    private function assertPublicHost(string $url, string $field): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            throw ValidationException::withMessages([$field => 'Tautan foto profil tidak valid.']);
        }

        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (@gethostbynamel($host) ?: []);

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw ValidationException::withMessages([
                    $field => 'Tautan foto profil harus mengarah ke alamat publik.',
                ]);
            }
        }
    }
}
