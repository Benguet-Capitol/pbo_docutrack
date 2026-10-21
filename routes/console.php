<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Employees: sync from HRIS on the 1st of every month, 2:00 AM (needs `php artisan schedule:run` every minute)
Schedule::command('employees:sync-hris')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping();
