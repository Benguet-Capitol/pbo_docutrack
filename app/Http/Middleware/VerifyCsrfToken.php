<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * API routes using Bearer token authentication don't need CSRF tokens
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'docutrack/api/*',
    ];
}
