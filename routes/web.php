<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\NoticeOfMeetingController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard/Index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Module pages: each requires its module's view permission (the side menu hides what the role can't open)
    $page = fn (string $uri, string $component, string $name, string $permission) =>
        Route::get($uri, fn () => Inertia::render($component))->middleware("permission:{$permission}")->name($name);

    $page('/documents', 'Documents', 'documents.index', 'documents.view');
    $page('/records', 'Records/Index', 'records.index', 'records.view');
    $page('/locator-chart', 'LocatorChart/Index', 'locator-chart.index', 'locator_chart.view');
    $page('/leaves', 'Leaves/Index', 'leaves.index', 'leaves.view');
    $page('/travel-orders', 'TravelOrders/Index', 'travel-orders.index', 'travel_orders.view');
    $page('/pass-slips', 'PassSlips/Index', 'pass-slips.index', 'pass_slips.view');
    $page('/time-slips', 'TimeSlips/Index', 'time-slips.index', 'time_slips.view');
    $page('/tardiness', 'Tardiness/Index', 'tardiness.index', 'tardiness.view');
    $page('/certificates-of-appearance', 'CertificateOfAppearance/Index', 'certificates-of-appearance.index', 'certificate_of_appearances.view');

    // Auxiliary
    $page('/users', 'Users/Index', 'users.index', 'users.manage');
    $page('/roles-permissions', 'RolesPermissions/Index', 'roles-permissions.index', 'roles.manage');
    $page('/employees', 'Employees/Index', 'employees.index', 'employees.view');
    $page('/offices', 'Offices/Index', 'offices.index', 'offices.view');
    $page('/municipalities', 'Municipalities/Index', 'municipalities.index', 'municipalities.view');

    // System
    $page('/activity-logs', 'ActivityLogs/Index', 'activity-logs.index', 'activity_logs.view');
    $page('/trash', 'Trash/Index', 'trash.index', 'trash.manage');
});

// Notice of Meetings
Route::middleware(['web', 'auth'])->prefix('api')->group(function () {
    Route::get('/notice-of-meetings', [NoticeOfMeetingController::class, 'api']);
    Route::middleware('permission:notice_of_meetings.manage')->group(function () {
        Route::post('/notice-of-meetings', [NoticeOfMeetingController::class, 'store']);
        Route::post('/notice-of-meetings/bulk', [NoticeOfMeetingController::class, 'storeBulk']);
        Route::put('/notice-of-meetings/{noticeOfMeeting}', [NoticeOfMeetingController::class, 'update']);
        Route::delete('/notice-of-meetings/{noticeOfMeeting}', [NoticeOfMeetingController::class, 'destroy']);
    });
});

// API Routes - session-authenticated (skip CSRF).
// Reads are open to any signed-in user (other pages use them for lists and dropdowns);
// writes require the matching RoleService permission.
Route::middleware('auth')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    // Creating, editing (including password resets) and deleting users
    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/api/users', [UserController::class, 'store']);
        Route::put('/api/users/{id}', [UserController::class, 'update']);
        Route::delete('/api/users/{id}', [UserController::class, 'destroy']);
    });

    // Roles & Permissions
    Route::get('/api/roles/options', [RoleController::class, 'options'])->middleware('permission:users.manage,roles.manage');
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/api/roles', [RoleController::class, 'index']);
        Route::post('/api/roles', [RoleController::class, 'store']);
        Route::put('/api/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/api/roles/{role}', [RoleController::class, 'destroy']);
    });

    Route::get('/api/employees', [EmployeeController::class, 'index']);
    Route::post('/api/employees', [EmployeeController::class, 'store'])->middleware('permission:employees.create');
    Route::put('/api/employees/{id}', [EmployeeController::class, 'update'])->middleware('permission:employees.edit');
    Route::delete('/api/employees/{id}', [EmployeeController::class, 'destroy'])->middleware('permission:employees.delete');

    Route::get('/api/municipalities', [MunicipalityController::class, 'index']);
    Route::post('/api/municipalities', [MunicipalityController::class, 'store'])->middleware('permission:municipalities.create');
    Route::put('/api/municipalities/{id}', [MunicipalityController::class, 'update'])->middleware('permission:municipalities.edit');
    Route::delete('/api/municipalities/{id}', [MunicipalityController::class, 'destroy'])->middleware('permission:municipalities.delete');

    Route::get('/api/offices', [OfficeController::class, 'index']);
    Route::post('/api/offices', [OfficeController::class, 'store'])->middleware('permission:offices.create');
    Route::put('/api/offices/{id}', [OfficeController::class, 'update'])->middleware('permission:offices.edit');
    Route::delete('/api/offices/{id}', [OfficeController::class, 'destroy'])->middleware('permission:offices.delete');

    Route::get('/api/documents', [DocumentController::class, 'index']);
    Route::post('/api/documents', [DocumentController::class, 'store'])->middleware('permission:documents.create');
    Route::put('/api/documents/{id}', [DocumentController::class, 'update'])->middleware('permission:documents.edit');
    Route::delete('/api/documents/{id}', [DocumentController::class, 'destroy'])->middleware('permission:documents.delete');

    Route::get('/api/records', [RecordController::class, 'index']);
    Route::post('/api/records', [RecordController::class, 'store'])->middleware('permission:records.create');
    Route::put('/api/records/{id}', [RecordController::class, 'update'])->middleware('permission:records.edit');
    Route::delete('/api/records/{id}', [RecordController::class, 'destroy'])->middleware('permission:records.delete');
    Route::get('/api/records/{id}/view', [RecordController::class, 'viewFile']);
    Route::get('/api/records/{id}/download', [RecordController::class, 'downloadFile']);
});

require __DIR__.'/auth.php';

// Fallback route for 404 errors - must be after all other routes
Route::fallback(function () {
    return Inertia::render('Errors/Error404');
});