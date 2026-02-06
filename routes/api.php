<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;

// Users API
Route::get('/users', [UserController::class, 'index']);

// Employees API
Route::get('/employees', [EmployeeController::class, 'index']);

// Offices API
Route::get('/offices', [OfficeController::class, 'index']);
