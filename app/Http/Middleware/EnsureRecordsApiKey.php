<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared Records API: the client app sends one of the keys in config('services.records_api.keys')
 * as the X-API-Key header (or "Authorization: Bearer <key>"). No session, no user.
 */
class EnsureRecordsApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $sent = (string) ($request->header('X-API-Key') ?: $request->bearerToken());
        $keys = config('services.records_api.keys', []);

        $valid = $sent !== '' && collect($keys)->contains(fn ($key) => is_string($key) && $key !== '' && hash_equals($key, $sent));

        if (!$valid) {
            return response()->json(['message' => $keys ? 'Invalid or missing API key.' : 'The Records API is not enabled.'], $keys ? 401 : 503);
        }

        return $next($request);
    }
}
