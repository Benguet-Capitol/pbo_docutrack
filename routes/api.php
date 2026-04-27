<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\PassSlipController;
use App\Http\Controllers\Api\TimeSlipController;
use App\Http\Controllers\Api\TravelOrderController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\TardinessController;
use App\Http\Controllers\Api\CertificateOfAppearanceController;
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
    // More specific routes first
    Route::get('/documents/generate/tracking-no', [DocumentController::class, 'generateTrackingNo']);
    
    // General routes
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);
    Route::get('/documents/{id}/transactions', [DocumentController::class, 'getTransactions']);
    Route::post('/documents/{id}/forward', [DocumentController::class, 'forward']);
    Route::post('/documents/{id}/receive', [DocumentController::class, 'receive']);
    Route::post('/documents/{id}/finalize', [DocumentController::class, 'finalize']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
    
    // Document Checklist API
    Route::get('/documents/checklist/template/{documentType}', [DocumentController::class, 'getChecklistTemplate']);
    Route::post('/documents/{id}/checklist/save', [DocumentController::class, 'saveChecklist']);
    Route::get('/documents/{id}/checklist/records', [DocumentController::class, 'getChecklistRecords']);
});

// Records API
Route::middleware(['web', 'auth'])->group(function () {
    // More specific routes first
    Route::get('/records/generate/record-no', [RecordController::class, 'generateRecordNo']);
    
    // General routes
    Route::get('/records', [RecordController::class, 'index']);
    Route::post('/records', [RecordController::class, 'store']);
    Route::put('/records/{id}', [RecordController::class, 'update']);
    Route::delete('/records/{id}', [RecordController::class, 'destroy']);
});

// Pass Slips API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/pass-slips', [PassSlipController::class, 'index']);
    Route::post('/pass-slips', [PassSlipController::class, 'store']);
    Route::put('/pass-slips/{passSlip}', [PassSlipController::class, 'update']);
    Route::delete('/pass-slips/{passSlip}', [PassSlipController::class, 'destroy']);
});

// Time Slips API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/time-slips', [TimeSlipController::class, 'index']);
    Route::post('/time-slips', [TimeSlipController::class, 'store']);
    Route::put('/time-slips/{timeSlip}', [TimeSlipController::class, 'update']);
    Route::delete('/time-slips/{timeSlip}', [TimeSlipController::class, 'destroy']);
});

// Certificate of Appearance API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/certificate-of-appearances', [CertificateOfAppearanceController::class, 'index']);
    Route::post('/certificate-of-appearances', [CertificateOfAppearanceController::class, 'store']);
    Route::put('/certificate-of-appearances/{certificateOfAppearance}', [CertificateOfAppearanceController::class, 'update']);
    Route::delete('/certificate-of-appearances/{certificateOfAppearance}', [CertificateOfAppearanceController::class, 'destroy']);
});

// Travel Orders API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/travel-orders', [TravelOrderController::class, 'index']);
    Route::post('/travel-orders', [TravelOrderController::class, 'store']);
    Route::put('/travel-orders/{travelOrder}', [TravelOrderController::class, 'update']);
    Route::delete('/travel-orders/{travelOrder}', [TravelOrderController::class, 'destroy']);
});

// Leaves API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::put('/leaves/{leave}', [LeaveController::class, 'update']);
    Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy']);
});

// Tardiness/Undertime API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/tardiness', [TardinessController::class, 'index']);
    Route::post('/tardiness', [TardinessController::class, 'store']);
    Route::put('/tardiness/{tardiness}', [TardinessController::class, 'update']);
    Route::delete('/tardiness/{tardiness}', [TardinessController::class, 'destroy']);
});

// Sync API - Fetch and update data from pbo-registry
Route::prefix('sync')->group(function () {
    Route::post('/offices', [SyncController::class, 'syncOffices']);
    Route::post('/employees', [SyncController::class, 'syncEmployees']);
    Route::post('/users', [SyncController::class, 'syncUsers']);
    Route::post('/all', [SyncController::class, 'syncAll']);
});

