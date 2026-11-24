<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Services\OfficerAvatarStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves officer avatars stored in object storage (the bucket stays private).
 * File names are random and change on every replacement, so responses are
 * cached as immutable. Hidden avatars are only served to admins and the owner.
 */
class OfficerAvatarController extends Controller
{
    public function show(Request $request, string $file): Response
    {
        $path = trim((string) config('avatars.directory', 'officer-avatars'), '/') . '/' . $file;

        $officer = Officer::where('avatar_path', $path)->first(['id', 'user_id', 'avatar_disk', 'avatar_path', 'avatar_hidden']);
        abort_unless($officer && $officer->avatar_disk, 404);

        $private = false;
        if ($officer->avatar_hidden) {
            $user = $request->user();
            abort_unless($user && ($user->isAdmin() || (int) $user->id === (int) $officer->user_id), 404);
            $private = true;
        }

        $disk = Storage::disk($officer->avatar_disk);

        try {
            abort_unless($disk->exists($path), 404);

            return $disk->response($path, null, [
                'Cache-Control' => $private ? 'private, no-store' : 'public, max-age=31536000, immutable',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'",
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            abort(404);
        }
    }
}
