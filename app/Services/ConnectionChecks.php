<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The Connections page (as in pbo-registryV2): the systems DocuTrack talks to. HRIS is called from here and tested live;
 * the shared APIs are called by pbo-registryV2, so they show whether keys are set and when a call was last accepted or refused.
 * Keys are never shown.
 */
class ConnectionChecks
{
    /** The shared APIs: the key setting and what pbo-registryV2 uses each for */
    public const APIS = [
        'records' => ['name' => 'Records API', 'settings' => 'RECORDS_API_KEYS', 'used_for' => "pbo-registryV2's Archives → Records (DOCUTRACK_RECORDS_KEY there)."],
        'leaves' => ['name' => 'Leaves API', 'settings' => 'LEAVES_API_KEYS', 'used_for' => "pbo-registryV2's Profile → My Leaves (DOCUTRACK_LEAVES_KEY there)."],
        'locator' => ['name' => 'Locator API', 'settings' => 'LOCATOR_API_KEYS', 'used_for' => "pbo-registryV2's Locator Chart (DOCUTRACK_LOCATOR_KEY there)."],
        'filing' => ['name' => 'Filing API', 'settings' => 'FILING_API_KEYS', 'used_for' => "pbo-registryV2's Profile → My Filings: PBO staff file their own leaves and slips (DOCUTRACK_FILING_KEY there)."],
    ];

    /** A call is noted at most once a minute per API and outcome, so busy APIs don't write on every request */
    private const NOTE_EVERY_SECONDS = 60;

    public static function all(): array
    {
        return [self::hris(), ...array_map(fn (string $api) => self::sharedApi($api), array_keys(self::APIS))];
    }

    /** Called by EnsureApiKey: when an API last accepted or refused a call (and why it refused) */
    public static function noteApiCall(string $api, bool $accepted, ?string $reason = null): void
    {
        $key = 'connections.api.' . $api . '.' . ($accepted ? 'accepted' : 'refused');
        try {
            $last = Cache::get($key);
            if (!$last || now()->getTimestamp() - ($last['at'] ?? 0) >= self::NOTE_EVERY_SECONDS) {
                Cache::forever($key, ['at' => now()->getTimestamp(), 'reason' => $reason]);
            }
        } catch (Throwable) {
            // Only a status note: it must never fail the call itself
        }
    }

    public static function hris(): array
    {
        $config = config('services.hris');

        return self::probe('HRIS', 'Employees: the monthly sync and "Sync from HRIS" on the Employees page.', $config['url'] ?? null, (bool) ($config['key'] ?? null), 'HRIS_URL / HRIS_KEY',
            function () use ($config) {
                $response = Http::timeout(min(20, (int) ($config['timeout'] ?? 15)))->connectTimeout(5)->acceptJson()->withHeaders(['X-API-KEY' => $config['key']])
                    ->get(rtrim($config['url'], '/') . '/' . ltrim($config['employees_endpoint'], '/'));
                if (in_array($response->status(), [401, 403], true)) {
                    return [false, "HRIS refused the key (HTTP {$response->status()})."];
                }
                if (!$response->successful()) {
                    return [false, "HRIS answered with an error (HTTP {$response->status()})."];
                }
                $json = $response->json();
                $rows = is_array($json['data'] ?? null) ? $json['data'] : $json;

                return is_array($rows) ? [true, number_format(count($rows)) . ' employees returned.'] : [false, 'HRIS did not answer with JSON; check HRIS_URL.'];
            });
    }

    public static function sharedApi(string $api): array
    {
        ['name' => $name, 'settings' => $settings, 'used_for' => $usedFor] = self::APIS[$api];
        $keys = count(config("services.{$api}_api.keys", []));
        $base = ['name' => "{$name} (for pbo-registryV2)", 'used_for' => $usedFor, 'url' => url("/api/v1/{$api}"), 'settings' => $settings, 'ms' => null];

        if (!$keys) {
            return $base + ['status' => 'not_configured', 'message' => "Off: no keys, so every call is refused. Add {$settings} to .env and the same key to pbo-registryV2."];
        }

        $accepted = Cache::get("connections.api.{$api}.accepted");
        $refused = Cache::get("connections.api.{$api}.refused");
        $keysText = $keys === 1 ? '1 key set' : "{$keys} keys set";
        $acceptedText = $accepted ? 'last call accepted ' . self::ago($accepted['at']) : 'no call received yet';

        // Refused more recently than accepted: pbo-registryV2's key probably doesn't match
        if ($refused && (!$accepted || $refused['at'] > $accepted['at'])) {
            return $base + ['status' => 'warning', 'message' => "{$keysText}; a call was refused " . self::ago($refused['at']) . " ({$refused['reason']}), {$acceptedText}. Check that pbo-registryV2 has one of these keys."];
        }

        return $base + ['status' => $accepted ? 'ok' : 'warning', 'message' => ucfirst("{$keysText}, {$acceptedText}.") . ($accepted ? '' : ' Open the page in pbo-registryV2 that uses it to test.')];
    }

    private static function ago(int $timestamp): string
    {
        return now()->setTimestamp($timestamp)->diffForHumans() . ' (' . date('M j, g:i A', $timestamp) . ')';
    }

    private static function probe(string $name, string $usedFor, ?string $url, bool $hasKey, string $settings, callable $check): array
    {
        $base = ['name' => $name, 'used_for' => $usedFor, 'url' => $url, 'settings' => $settings, 'ms' => null];
        if (!$url || !$hasKey) {
            return $base + ['status' => 'not_configured', 'message' => "Not set up: add {$settings} to .env."];
        }

        $start = microtime(true);
        try {
            [$ok, $message] = $check();
        } catch (ConnectionException) {
            [$ok, $message] = [false, "Can't be reached at {$url} (server down, wrong address, or a firewall)."];
        } catch (Throwable $e) {
            report($e);
            [$ok, $message] = [false, 'The check failed unexpectedly; see the Laravel log.'];
        }

        return ['status' => $ok ? 'ok' : 'error', 'message' => $message, 'ms' => (int) round((microtime(true) - $start) * 1000)] + $base;
    }
}
