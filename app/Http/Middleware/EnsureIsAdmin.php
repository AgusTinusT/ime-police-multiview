<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Halaman atau fitur ini hanya dapat diakses oleh Admin Dispatcher.',
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk membuka halaman Admin.');
        }

        return $next($request);
    }
}
