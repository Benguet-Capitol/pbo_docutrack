<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared APIs for other PBO systems (api.key:records, api.key:leaves): the client app sends one of the keys in
 * config("services.{api}_api.keys") as the X-API-Key header (or "Authorization: Bearer <key>"). No session, no user.
 * Each API has its own keys, so a Records key can't read leaves.
 */
class EnsureApiKey
{
    public function handle(Request $request, Closure $next, string $api = 'records'): Response
    {
        $sent = (string) ($request->header('X-API-Key') ?: $request->bearerToken());
        $keys = config("services.{$api}_api.keys", []);

        $valid = $sent !== '' && collect($keys)->contains(fn ($key) => is_string($key) && $key !== '' && hash_equals($key, $sent));

        if (!$valid) {
            $name = ucfirst($api);

            return response()->json(['message' => $keys ? 'Invalid or missing API key.' : "The {$name} API is not enabled."], $keys ? 401 : 503);
        }

        return $next($request);
    }
}
