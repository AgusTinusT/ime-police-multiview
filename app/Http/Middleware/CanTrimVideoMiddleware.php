<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanTrimVideoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->canTrimVideo()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki hak akses untuk fitur potong video (Role Admin atau Clipper diperlukan).'
                ], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Fitur potong video terbatas.');
        }

        return $next($request);
    }
}
