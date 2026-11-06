<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  role values accepted by the route
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $allowed = array_filter(array_map(fn (string $role) => UserRole::tryFrom($role), $roles));

        // Admin is a superset of moderator so it always satisfies a moderator-gated route.
        if (! in_array($user->role, $allowed, true) && ! $user->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
