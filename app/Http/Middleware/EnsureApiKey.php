<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared APIs for other PBO systems (api.key:records, api.key:leaves): the client app sends one of the keys in
 * config("services.{api}_api.keys") as the X-API-Key header (or "Authorization: Bearer <key>"). No session, no user.
 * Each API has its own keys, so a Records key can't read leaves.
 *
 * Audit trail (Activity Logs, module "Shared API"): every Leaves API call (personal data: who asked for whose leaves),
 * Records file downloads, and every refused call. A key is logged as a fingerprint, never the key itself.
 */
class EnsureApiKey
{
    public function handle(Request $request, Closure $next, string $api = 'records'): Response
    {
        $sent = (string) ($request->header('X-API-Key') ?: $request->bearerToken());
        $keys = array_values(config("services.{$api}_api.keys", []));

        $index = $sent === '' ? false : collect($keys)->search(fn ($key) => is_string($key) && $key !== '' && hash_equals($key, $sent));

        if ($index === false) {
            $name = ucfirst($api);
            $this->log($request, $api, 'api_denied', $sent === '' ? 'no API key' : 'wrong API key', $keys ? 401 : 503);

            return response()->json(['message' => $keys ? 'Invalid or missing API key.' : "The {$name} API is not enabled."], $keys ? 401 : 503);
        }

        $response = $next($request);

        // Leaves: every call; Records: file downloads only (listing is frequent and holds no personal data)
        if ($api === 'leaves' || str_ends_with($request->path(), '/file')) {
            $this->log($request, $api, 'api_access', 'key #' . ($index + 1) . ' (' . substr(hash('sha256', $sent), 0, 8) . ')', $response->getStatusCode());
        }

        return $response;
    }

    private function log(Request $request, string $api, string $action, string $client, int $status): void
    {
        $name = ucfirst($api);
        $target = $api === 'leaves'
            ? 'leaves of employee ' . ($request->route('employeeId') ?? '?') . ($request->query('year') ? " ({$request->query('year')})" : '')
            : $request->path();

        ActivityLogService::log(
            $action,
            'Shared API',
            $action === 'api_denied'
                ? "{$name} API refused a call ({$client}): {$request->method()} /{$request->path()}"
                : "{$name} API: {$target} read by {$client}",
            subjectLabel: $api === 'leaves' ? (string) ($request->route('employeeId') ?? '') : null,
            properties: array_filter([
                'api' => $api,
                'client' => $client,
                'path' => '/' . $request->path(),
                'query' => $request->query() ?: null,
                'status' => $status,
            ]),
            actor: 'API client',
        );
    }
}
