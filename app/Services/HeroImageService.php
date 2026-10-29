<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HeroImageService
{
    /**
     * Validate, re-encode to WebP (quality 82) via GD, and store in public disk under hero/.
     */
    public static function store(UploadedFile $file, string $variant = 'desktop'): string
    {
        $field = $variant === 'mobile' ? 'image_mobile' : 'image_desktop';
        $minWidth = $variant === 'mobile' ? 1080 : 1600;
        $minHeight = $variant === 'mobile' ? 1080 : 600;

        $validator = Validator::make(
            [$field => $file],
            [
                $field => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                    "dimensions:min_width={$minWidth},min_height={$minHeight}",
                ],
            ],
            [
                "{$field}.mimes" => 'Format gambar harus JPG, PNG, atau WebP.',
                "{$field}.max" => 'Ukuran gambar maksimal 2 MB.',
                "{$field}.dimensions" => "Dimensi gambar {$variant} minimal {$minWidth}×{$minHeight} piksel.",
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $rawBytes = file_get_contents($file->getRealPath());
        $image = $rawBytes !== false ? @imagecreatefromstring($rawBytes) : false;

        if ($image === false) {
            throw ValidationException::withMessages([
                $field => ['File gambar tidak valid atau gagal diproses.'],
            ]);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagewebp($image, null, 82);
        $webpData = ob_get_clean();
        imagedestroy($image);

        if (!$ok || empty($webpData)) {
            throw ValidationException::withMessages([
                $field => ['Gagal mengonversi gambar ke format WebP.'],
            ]);
        }

        $path = 'hero/' . Str::uuid()->toString() . '.webp';
        Storage::disk('public')->put($path, $webpData);

        return $path;
    }

    /**
     * Delete an existing hero image from public disk if present.
     */
    public static function delete(?string $path): void
    {
        if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
