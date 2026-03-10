<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. check if authenticated
        if (! Auth::check()) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        // 2. check if user's role is in the $roles array
        $user = $request->user();
        if (! $user->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
