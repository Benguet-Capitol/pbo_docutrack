<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SyncController;

// Users API
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::middleware(['web', 'auth'])->get('/user/current', [UserController::class, 'getCurrentUser']);

// Employees API
Route::get('/employees', [EmployeeController::class, 'index']);
Route::post('/employees', [EmployeeController::class, 'store']);
Route::put('/employees/{id}', [EmployeeController::class, 'update']);
Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

// Offices API
Route::get('/offices', [OfficeController::class, 'index']);
Route::post('/offices', [OfficeController::class, 'store']);
Route::put('/offices/{id}', [OfficeController::class, 'update']);
Route::delete('/offices/{id}', [OfficeController::class, 'destroy']);

// Municipalities API
Route::get('/municipalities', [MunicipalityController::class, 'index']);
Route::post('/municipalities', [MunicipalityController::class, 'store']);
Route::put('/municipalities/{id}', [MunicipalityController::class, 'update']);
Route::delete('/municipalities/{id}', [MunicipalityController::class, 'destroy']);

// Documents API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);
    Route::get('/documents/{id}/transactions', [DocumentController::class, 'getTransactions']);
    Route::post('/documents/{id}/forward', [DocumentController::class, 'forward']);
    Route::post('/documents/{id}/receive', [DocumentController::class, 'receive']);
    Route::post('/documents/{id}/finalize', [DocumentController::class, 'finalize']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
});

// Sync API - Fetch and update data from pbo-registry
Route::prefix('sync')->group(function () {
    Route::post('/offices', [SyncController::class, 'syncOffices']);
    Route::post('/employees', [SyncController::class, 'syncEmployees']);
    Route::post('/users', [SyncController::class, 'syncUsers']);
    Route::post('/all', [SyncController::class, 'syncAll']);
});

