<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Server-side client for the HRIS API (config/services.php 'hris'): the URL and key stay in .env
 * instead of the built JavaScript. Ported from pbo-registryV2's HrisClient (employee list only).
 */
class HrisClient
{
    /** HRIS lists elected officials (ID numbers starting "91") without a position title: their title from their office */
    private const ELECTED_TITLES = [
        'office of the governor' => 'Provincial Governor',
        'office of the vice governor' => 'Provincial Vice Governor',
        'office of the sangguniang panlalawigan members' => 'Sangguniang Panlalawigan Member',
    ];

    /**
     * All HRIS employees (permanent, casual, COS, ...).
     *
     * @throws RuntimeException when HRIS is not configured or can't be reached
     */
    public function employees(): Collection
    {
        return collect($this->get(config('services.hris.employees_endpoint')))
            ->map(fn ($e) => is_array($e) ? self::withElectedTitle($e) : $e);
    }

    private function get(string $endpoint): array
    {
        $config = config('services.hris');
        if (!$config['key']) {
            throw new RuntimeException('HRIS is not configured: set HRIS_URL and HRIS_KEY in .env.');
        }

        try {
            $response = Http::timeout($config['timeout'])->acceptJson()
                ->withHeaders(['X-API-KEY' => $config['key']])
                ->get(rtrim($config['url'], '/') . '/' . ltrim($endpoint, '/'));
        } catch (ConnectionException $e) {
            throw new RuntimeException("Couldn't reach HRIS ({$config['url']}). Check the connection and try again.");
        }

        if (!$response->successful()) {
            throw new RuntimeException("HRIS answered with an error ({$response->status()}).");
        }

        $json = $response->json();

        // Accept a bare list or one wrapped in "data"
        return is_array($json['data'] ?? null) ? $json['data'] : (is_array($json) ? $json : []);
    }

    private static function withElectedTitle(array $e): array
    {
        $isElected = str_starts_with(trim((string) ($e['employee_id_number'] ?? '')), '91');
        if (trim((string) ($e['position_title'] ?? '')) === '' && $isElected) {
            $e['position_title'] = self::ELECTED_TITLES[mb_strtolower(trim((string) ($e['office_desc'] ?? '')))] ?? null;
        }

        return $e;
    }

    /**
     * An employee's name as "Juan B. Dela Cruz" (first name, middle initial, last name, suffix). The middle initial is
     * left out when the middle name is blank or N/A; without a first and last name, HRIS's fullname is used as it is.
     */
    public static function displayName(array $e): ?string
    {
        $part = function ($value) {
            $value = trim(preg_replace('/\s+/', ' ', (string) $value));

            return in_array(mb_strtolower(str_replace([' ', '.'], '', $value)), ['', 'na', 'n/a', 'none', '-', '--'], true) ? '' : $value;
        };
        [$first, $middle, $last, $suffix] = [$part($e['fname'] ?? null), $part($e['mname'] ?? null), $part($e['lname'] ?? null), $part($e['suffix'] ?? null)];
        if ($first === '' || $last === '') {
            return trim((string) ($e['fullname'] ?? '')) ?: null;
        }

        return implode(' ', array_filter([$first, $middle !== '' ? mb_strtoupper(mb_substr($middle, 0, 1)) . '.' : '', $last, $suffix]));
    }
}
