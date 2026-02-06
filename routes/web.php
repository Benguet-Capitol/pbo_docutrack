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

// API Routes
Route::middleware('auth')->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    Route::get('/api/employees', [EmployeeController::class, 'index']);
    Route::get('/api/offices', [OfficeController::class, 'index']);
});

require __DIR__.'/auth.php';
