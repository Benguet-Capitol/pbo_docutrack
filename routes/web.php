<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Management Routes
    Route::get('/users', function () {
        return Inertia::render('Users');
    })->name('users.index');

    Route::get('/employees', function () {
        return Inertia::render('Employees');
    })->name('employees.index');

    Route::get('/offices', function () {
        return Inertia::render('Offices');
    })->name('offices.index');
});

// API Routes - Stateless with Bearer token authentication (skip CSRF)
Route::middleware('auth')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
    Route::put('/api/users/{id}', [UserController::class, 'update']);
    Route::delete('/api/users/{id}', [UserController::class, 'destroy']);

    Route::get('/api/employees', [EmployeeController::class, 'index']);
    Route::post('/api/employees', [EmployeeController::class, 'store']);
    Route::put('/api/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/api/employees/{id}', [EmployeeController::class, 'destroy']);

    Route::get('/api/offices', [OfficeController::class, 'index']);
    Route::post('/api/offices', [OfficeController::class, 'store']);
    Route::put('/api/offices/{id}', [OfficeController::class, 'update']);
    Route::delete('/api/offices/{id}', [OfficeController::class, 'destroy']);
});

require __DIR__.'/auth.php';
