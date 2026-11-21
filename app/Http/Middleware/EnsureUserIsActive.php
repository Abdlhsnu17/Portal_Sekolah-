<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup sesi milik akun yang sudah dinonaktifkan.
 *
 * Pemeriksaan saat login saja tidak cukup: pegawai yang aksesnya dicabut tetap
 * bisa bekerja sampai sesinya kedaluwarsa — dan dengan "ingat saya" itu berarti
 * berminggu-minggu. Karena itu status diperiksa pada setiap permintaan.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        if ($request->user() && $request->user()->password_changed_at?->lt(now()->subYears(50))
            && ! $request->routeIs('security.index', 'security.password', 'logout')) {
            return redirect()->route('security.index')->with('warning', 'Ganti kata sandi sementara sebelum menggunakan aplikasi.');
        }

        return $next($request);
    }
}
