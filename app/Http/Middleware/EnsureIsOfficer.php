<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsOfficer
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isOfficer()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Akses ditolak. Fitur ini hanya untuk Perwira Kepolisian SASP yang terverifikasi.'
                ], 403);
            }

            abort(403, 'Akses terbatas untuk Perwira Kepolisian SASP.');
        }

        return $next($request);
    }
}
