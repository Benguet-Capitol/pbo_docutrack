<?php

namespace App\Http\Middleware;

use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users whose role has at least one of the given RoleService permissions.
 * Usage: ->middleware('permission:pass_slips.edit') or 'permission:documents.create,documents.edit'
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        $allowed = $user && collect($permissions)->contains(
            fn (string $permission) => RoleService::hasPermission($user, $permission)
        );

        if (!$allowed) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'You do not have permission to perform this action.'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
