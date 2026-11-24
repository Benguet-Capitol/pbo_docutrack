<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\EmployeeController;
use App\Models\Leave;
use App\Models\PassSlip;
use App\Models\Tardiness;
use App\Models\TimeSlip;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The print preview of one leave / slip for another PBO system (pbo-registryV2's My Filings), with the same preview
 * the module's page shows. No sign-in: the link is signed and short-lived, made by the Filing API for the record's own
 * employee (SharedFilingController::printUrl), and it shows only that record and the names its form prints.
 */
class FilingPrintController extends Controller
{
    public function show(string $module, int $id): Response
    {
        $record = match ($module) {
            'leaves' => Leave::where('control_no', Leave::findOrFail($id)->control_no)->orderBy('id')->get(),
            'pass-slips' => PassSlip::with('employees')->findOrFail($id),
            'time-slips' => TimeSlip::findOrFail($id),
            'tardiness' => Tardiness::findOrFail($id),
            'travel-orders' => TravelOrder::with('employees')->findOrFail($id),
        };
        // The same employee list the module's page loads, for the names and titles its preview prints
        $employees = app(EmployeeController::class)->index(Request::create('/', 'GET', in_array($module, ['leaves', 'travel-orders'], true) ? [] : ['scope' => 'pbo']))->getData(true);

        return Inertia::render('FilingPrint', ['module' => $module, 'record' => $record, 'employees' => $employees]);
    }
}
