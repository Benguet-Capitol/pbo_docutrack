<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Exclude API routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'docutrack/api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Request $request, \Throwable $e) {
            // Determine status code from exception
            $statusCode = 500;
            
            if ($e instanceof AuthorizationException) {
                $statusCode = 403;
            } elseif ($e instanceof AuthenticationException) {
                $statusCode = 401;
            } elseif ($e instanceof TokenMismatchException) {
                $statusCode = 419;
            } elseif ($e instanceof HttpException) {
                $statusCode = $e->getStatusCode();
            } elseif ($e instanceof NotFoundHttpException) {
                $statusCode = 404;
            } elseif (method_exists($e, 'getStatusCode')) {
                $statusCode = $e->getStatusCode();
            }
            
            // List of error page codes we have
            $errorPageCodes = [401, 403, 404, 419, 429, 500, 503];
            
            // If we have an error page for this code, render it as Inertia component
            if (in_array($statusCode, $errorPageCodes)) {
                return Inertia::render('Errors/Error' . $statusCode)
                    ->toResponse($request)
                    ->setStatusCode($statusCode);
            }
        });
    })->create();
