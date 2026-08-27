<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->active) {
            $path = $request->path();
            if (! str_contains($path, 'auth/me') && ! str_contains($path, 'user-profile') && ! str_contains($path, 'users/me')) {
                abort(403, 'Учётная запись неактивна.');
            }
        }

        return $next($request);
    }
}
