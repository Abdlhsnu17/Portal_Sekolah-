<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->user()?->role, ['admin', 'staf'], true)) {
            abort(403, 'Halaman ini hanya untuk administrator atau staf sekolah.');
        }

        return $next($request);
    }
}
