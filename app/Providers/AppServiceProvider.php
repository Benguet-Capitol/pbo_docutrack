<?php

namespace App\Providers;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(Login::class, function (Login $event) {
            ActivityLogService::log('login', 'Authentication', "{$event->user->name} logged in", user: $event->user);
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                ActivityLogService::log('logout', 'Authentication', "{$event->user->name} logged out", user: $event->user);
            }
        });

        // Failed sign-ins and lockouts (password guessing): the username tried, never the password
        Event::listen(Failed::class, function (Failed $event) {
            $username = mb_substr((string) ($event->credentials['username'] ?? ''), 0, 100);
            ActivityLogService::log(
                'login_failed',
                'Authentication',
                $event->user ? "Failed sign-in to {$event->user->name}'s account (wrong password)" : "Failed sign-in with unknown username \"{$username}\"",
                subjectLabel: $username,
                properties: ['username' => $username],
                user: $event->user,
                actor: 'Guest',
            );
        });

        Event::listen(Lockout::class, function (Lockout $event) {
            $username = mb_substr((string) $event->request->input('username'), 0, 100);
            ActivityLogService::log(
                'login_locked',
                'Authentication',
                "Sign-in locked for \"{$username}\" after too many failed attempts",
                subjectLabel: $username,
                properties: ['username' => $username],
                actor: 'Guest',
            );
        });
    }
}
