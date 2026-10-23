<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\PassSlipController;
use App\Http\Controllers\Api\TimeSlipController;
use App\Http\Controllers\Api\TravelOrderController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\TardinessController;
use App\Http\Controllers\Api\CertificateOfAppearanceController;
use App\Http\Controllers\Api\TrashController;
use App\Http\Controllers\Api\ActivityLogController;

// Users, Employees, Offices, Municipalities and Records CRUD live in routes/web.php.
// Every route here requires a signed-in user. Reads are open to any signed-in user;
// writes require the matching RoleService permission (see App\Http\Middleware\EnsurePermission).

Route::middleware(['web', 'auth'])->get('/user/current', [UserController::class, 'getCurrentUser']);

// Shared Records API (read-only) for other PBO systems, e.g. pbo-registryV2's Archives: no session, an API key instead
// (X-API-Key, keys in RECORDS_API_KEYS). Only the shared record categories; see docs/records-api.md.
Route::prefix('v1/records')->middleware(['records.api', 'throttle:120,1'])->group(function () {
    Route::get('/categories', [\App\Http\Controllers\Api\V1\SharedRecordController::class, 'categories']);
    Route::get('/deleted', [\App\Http\Controllers\Api\V1\SharedRecordController::class, 'deleted']);
    Route::get('/', [\App\Http\Controllers\Api\V1\SharedRecordController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\V1\SharedRecordController::class, 'show'])->whereNumber('id');
    Route::get('/{id}/file', [\App\Http\Controllers\Api\V1\SharedRecordController::class, 'file'])->whereNumber('id');
});

// Documents API
Route::middleware(['web', 'auth'])->group(function () {
    // More specific routes first
    Route::get('/documents/generate/tracking-no', [DocumentController::class, 'generateTrackingNo'])->middleware('permission:documents.create');

    // General routes
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store'])->middleware('permission:documents.create');
    Route::put('/documents/{id}', [DocumentController::class, 'update'])->middleware('permission:documents.edit');
    Route::get('/documents/{id}/transactions', [DocumentController::class, 'getTransactions']);
    Route::post('/documents/{id}/forward', [DocumentController::class, 'forward'])->middleware('permission:documents.forward');
    Route::post('/documents/{id}/receive', [DocumentController::class, 'receive'])->middleware('permission:documents.receive');
    Route::post('/documents/{id}/finalize', [DocumentController::class, 'finalize'])->middleware('permission:documents.finalize');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->middleware('permission:documents.delete');

    // Document Checklist API (saved when a document is created and when it is edited)
    Route::get('/documents/checklist/template/{documentType}', [DocumentController::class, 'getChecklistTemplate']);
    Route::post('/documents/{id}/checklist/save', [DocumentController::class, 'saveChecklist'])->middleware('permission:documents.create,documents.edit');
    Route::get('/documents/{id}/checklist/records', [DocumentController::class, 'getChecklistRecords']);
});

// Records API (CRUD is in routes/web.php)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/records/generate/record-no', [RecordController::class, 'generateRecordNo'])->middleware('permission:records.create');
});

// Pass Slips API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/pass-slips', [PassSlipController::class, 'index']);
    Route::post('/pass-slips', [PassSlipController::class, 'store'])->middleware('permission:pass_slips.create');
    Route::put('/pass-slips/{passSlip}', [PassSlipController::class, 'update'])->middleware('permission:pass_slips.edit');
    Route::delete('/pass-slips/{passSlip}', [PassSlipController::class, 'destroy'])->middleware('permission:pass_slips.delete');
});

// Time Slips API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/time-slips', [TimeSlipController::class, 'index']);
    Route::post('/time-slips', [TimeSlipController::class, 'store'])->middleware('permission:time_slips.create');
    Route::put('/time-slips/{timeSlip}', [TimeSlipController::class, 'update'])->middleware('permission:time_slips.edit');
    Route::delete('/time-slips/{timeSlip}', [TimeSlipController::class, 'destroy'])->middleware('permission:time_slips.delete');
});

// Certificate of Appearance API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/certificate-of-appearances', [CertificateOfAppearanceController::class, 'index']);
    Route::post('/certificate-of-appearances', [CertificateOfAppearanceController::class, 'store'])->middleware('permission:certificate_of_appearances.create');
    Route::put('/certificate-of-appearances/{certificateOfAppearance}', [CertificateOfAppearanceController::class, 'update'])->middleware('permission:certificate_of_appearances.edit');
    Route::delete('/certificate-of-appearances/{certificateOfAppearance}', [CertificateOfAppearanceController::class, 'destroy'])->middleware('permission:certificate_of_appearances.delete');
});

// Travel Orders API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/travel-orders', [TravelOrderController::class, 'index']);
    Route::post('/travel-orders', [TravelOrderController::class, 'store'])->middleware('permission:travel_orders.create');
    Route::put('/travel-orders/{travelOrder}', [TravelOrderController::class, 'update'])->middleware('permission:travel_orders.edit');
    Route::delete('/travel-orders/{travelOrder}', [TravelOrderController::class, 'destroy'])->middleware('permission:travel_orders.delete');
});

// Leaves API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::get('/leaves/{leave}/form', [LeaveController::class, 'exportForm'])->middleware('permission:leaves.view');
    Route::get('/leaves/employee-salary/{employee}', [LeaveController::class, 'employeeSalary'])->middleware('permission:leaves.create,leaves.edit');
    Route::post('/leaves', [LeaveController::class, 'store'])->middleware('permission:leaves.create');
    Route::put('/leaves/{leave}', [LeaveController::class, 'update'])->middleware('permission:leaves.edit');
    Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])->middleware('permission:leaves.delete');
});

// Tardiness/Undertime API
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/tardiness', [TardinessController::class, 'index']);
    Route::post('/tardiness', [TardinessController::class, 'store'])->middleware('permission:tardiness.create');
    Route::put('/tardiness/{tardiness}', [TardinessController::class, 'update'])->middleware('permission:tardiness.edit');
    Route::delete('/tardiness/{tardiness}', [TardinessController::class, 'destroy'])->middleware('permission:tardiness.delete');
});

// Trash API
Route::middleware(['web', 'auth'])->group(function () {
    // Used by every create form to skip control numbers reserved by trashed records
    Route::get('/trash/control-numbers/{module}', [TrashController::class, 'controlNumbers']);

    Route::middleware('permission:trash.manage')->group(function () {
        Route::get('/trash', [TrashController::class, 'index']);
        Route::get('/trash/modules', [TrashController::class, 'modules']);
        Route::post('/trash/restore', [TrashController::class, 'restore']);
        Route::post('/trash/force-delete', [TrashController::class, 'forceDelete'])->middleware('throttle:10,1');
    });
});

// Activity Logs API
Route::middleware(['web', 'auth', 'permission:activity_logs.view'])->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/filters', [ActivityLogController::class, 'filters']);
});
