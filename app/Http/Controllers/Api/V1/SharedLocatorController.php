<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LocatorChart;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared Locator API (read-only) for other PBO systems, e.g. pbo-registryV2's Locator Chart page: where PBO staff are,
 * per day. Its own keys (LOCATOR_API_KEYS). Only computed statuses leave here, never the leave, pass slip or
 * travel order records themselves. See docs/locator-api.md.
 */
class SharedLocatorController extends Controller
{
    /** A month at most: the widest view the chart has */
    private const MAX_DAYS = 31;

    /** GET /api/v1/locator?from=2026-11-01&to=2026-11-30 */
    public function index(Request $request, LocatorChart $chart): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ]);
        $from = CarbonImmutable::parse($validated['from']);
        $to = CarbonImmutable::parse($validated['to']);

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            return response()->json(['message' => 'The range can be at most '.self::MAX_DAYS.' days.'], 422);
        }

        return response()->json(['data' => $chart->build($from, $to)]);
    }
}
