<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CekBidangKosong
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // JIKA USER ADALAH SUPER ADMIN: Langsung loloskan (bypass), tidak perlu pilih bidang!
        if ($user && $user->role === 'super_admin') {
            return $next($request);
        }

        // Jika user biasa/admin bidang, TAPI kolom bidang di databasenya masih kosong (null)
        if ($user && is_null($user->bidang)) {
            
            // Biar gak kena error "too many redirects", izinkan akses form pilih bidang & prosesnya.
            if (!$request->routeIs('pilih.bidang') && !$request->routeIs('simpan.bidang')) {
                return redirect()->route('pilih.bidang');
            }
        }

        return $next($request);
    }
}