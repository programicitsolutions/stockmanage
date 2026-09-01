<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'This account cannot access stock management.');
        }

        if ($roles === []) {
            return $next($request);
        }

        $allowed = collect($roles)->map(fn (string $role) => RoleSlug::from($role));

        if (! $allowed->contains($user->role?->slug)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
