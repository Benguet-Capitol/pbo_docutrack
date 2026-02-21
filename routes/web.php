<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RecordController;
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

    Route::get('/municipalities', function () {
        return Inertia::render('Municipalities');
    })->name('municipalities.index');

    Route::get('/documents', function () {
        return Inertia::render('Documents');
    })->name('documents.index');

    Route::get('/records', function () {
        return Inertia::render('Records');
    })->name('records.index');

    Route::get('/pass-slips', function () {
        return Inertia::render('PassSlips');
    })->name('pass-slips.index');

    Route::get('/travel-orders', function () {
        return Inertia::render('TravelOrders');
    })->name('travel-orders.index');

    Route::get('/leaves', function () {
        return Inertia::render('Leaves');
    })->name('leaves.index');
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


    Route::get('/api/municipalities', [MunicipalityController::class, 'index']);
    Route::post('/api/municipalities', [MunicipalityController::class, 'store']);
    Route::put('/api/municipalities/{id}', [MunicipalityController::class, 'update']);
    Route::delete('/api/municipalities/{id}', [MunicipalityController::class, 'destroy']);

    Route::get('/api/offices', [OfficeController::class, 'index']);
    Route::post('/api/offices', [OfficeController::class, 'store']);
    Route::put('/api/offices/{id}', [OfficeController::class, 'update']);
    Route::delete('/api/offices/{id}', [OfficeController::class, 'destroy']);

    Route::get('/api/documents', [DocumentController::class, 'index']);
    Route::post('/api/documents', [DocumentController::class, 'store']);
    Route::put('/api/documents/{id}', [DocumentController::class, 'update']);
    Route::delete('/api/documents/{id}', [DocumentController::class, 'destroy']);

    Route::get('/api/records', [RecordController::class, 'index']);
    Route::post('/api/records', [RecordController::class, 'store']);
    Route::put('/api/records/{id}', [RecordController::class, 'update']);
    Route::delete('/api/records/{id}', [RecordController::class, 'destroy']);
});

require __DIR__.'/auth.php';

// Fallback route for 404 errors - must be after all other routes
Route::fallback(function () {
    return Inertia::render('Errors/Error404');
});
