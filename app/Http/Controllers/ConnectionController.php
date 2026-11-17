<?php

namespace App\Http\Controllers;

use App\Services\ConnectionChecks;
use App\Services\ServerChecks;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System → Connections (as in pbo-registryV2): HRIS, the shared APIs for pbo-registryV2 and this server's setup,
 * checked each time the page opens or on "Check again"
 */
class ConnectionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Connections/Index', [
            // Deferred: the page opens at once while HRIS answers (up to its timeout)
            'checks' => Inertia::defer(fn () => ConnectionChecks::all()),
            'serverChecks' => Inertia::defer(fn () => ServerChecks::all()),
            'checkedAt' => now()->format('M j, Y g:i:s A'),
        ]);
    }
}
