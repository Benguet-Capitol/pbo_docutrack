<?php

namespace App\Providers;

use App\Services\ActivityLogService;
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
    }
}
