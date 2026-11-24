<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && in_array(auth()->user()->role, ['super_admin', 'admin_bidang', 'admin'])) {
            return $next($request);
        }

        abort(403, 'Akses tidak diizinkan. Halaman ini khusus untuk Super Admin dan Admin Bidang.');
    }
}
