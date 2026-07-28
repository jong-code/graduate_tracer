<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Usage in routes: Route::middleware('role:admin')->group(...)
     * or 'role:admin,faculty' to allow either.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this area of the system.');
        }

        if (! $user->is_active) {
            abort(403, 'Your account has been deactivated. Please contact the administrator.');
        }

        return $next($request);
    }
}
