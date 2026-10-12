<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users with the Administrator or Developer role.
 */
class EnsureAdminOrDeveloper
{
    public const ROLES = ['Administrator', 'Developer'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->usertype, self::ROLES, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Only Administrators and Developers can access this resource.'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
