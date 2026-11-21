<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.mfa_enabled')) {
            return $next($request);
        }

        if ($request->user()?->isAdmin() && ! $request->user()->hasTwoFactorAuthentication()) {
            return redirect()->route('security.index')->withErrors([
                'mfa' => 'Administrator wajib mengaktifkan autentikasi dua faktor sebelum melanjutkan.',
            ]);
        }

        return $next($request);
    }
}
