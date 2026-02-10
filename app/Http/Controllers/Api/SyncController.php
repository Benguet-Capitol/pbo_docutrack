<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use App\Models\Registry\RegistryEmployee;
use App\Models\Registry\RegistryOffice;
use App\Models\Registry\RegistryUser;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    /**
     * Sync offices from pbo-registry to local database
     */
    public function syncOffices(): JsonResponse
    {
        try {
            $registryOffices = RegistryOffice::all();
            $synced = 0;
            $skipped = 0;

            foreach ($registryOffices as $registryOffice) {
                $localOffice = Office::firstOrCreate(
                    ['office_abbreviation' => $registryOffice->office_abbreviation],
                    [
                        'office_name' => $registryOffice->office_name,
                        'sub_office' => $registryOffice->sub_office,
                        'fund' => $registryOffice->fund,
                        'fpp_code' => $registryOffice->fpp_code,
                        'responsibility_code' => $registryOffice->responsibility_code,
                        'branch' => $registryOffice->branch,
                    ]
                );

                if ($localOffice->wasRecentlyCreated) {
                    $synced++;
                } else {
                    // Update if office exists
                    $localOffice->update([
                        'office_name' => $registryOffice->office_name,
                        'sub_office' => $registryOffice->sub_office,
                        'fund' => $registryOffice->fund,
                        'fpp_code' => $registryOffice->fpp_code,
                        'responsibility_code' => $registryOffice->responsibility_code,
                        'branch' => $registryOffice->branch,
                    ]);
                    $synced++;
                }
            }

            return response()->json([
                'message' => 'Offices synced successfully',
                'synced' => $synced,
                'total' => $registryOffices->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync employees from pbo-registry to local database
     */
    public function syncEmployees(): JsonResponse
    {
        try {
            $registryEmployees = RegistryEmployee::all();
            $synced = 0;

            foreach ($registryEmployees as $registryEmployee) {
                // Find the office by name in local database
                $localOffice = Office::where('office_abbreviation', $registryEmployee->office)
                    ->orWhere('office_name', $registryEmployee->office)
                    ->first();

                Employee::updateOrCreate(
                    ['employee_id' => $registryEmployee->employee_id],
                    [
                        'name' => $registryEmployee->name,
                        'office_id' => $localOffice?->id,
                        'designation' => $registryEmployee->designation,
                    ]
                );
                $synced++;
            }

            return response()->json([
                'message' => 'Employees synced successfully',
                'synced' => $synced,
                'total' => $registryEmployees->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync users from pbo-registry to local database
     */
    public function syncUsers(): JsonResponse
    {
        try {
            $registryUsers = RegistryUser::all();
            $synced = 0;

            foreach ($registryUsers as $registryUser) {
                // Find the office by name in local database
                $localOffice = Office::where('office_abbreviation', $registryUser->office)
                    ->orWhere('office_name', $registryUser->office)
                    ->first();

                User::updateOrCreate(
                    ['username' => $registryUser->username],
                    [
                        'name' => $registryUser->name,
                        'password' => $registryUser->password,
                        'usertype' => $registryUser->usertype,
                        'fk_office_id' => $localOffice?->id,
                    ]
                );
                $synced++;
            }

            return response()->json([
                'message' => 'Users synced successfully',
                'synced' => $synced,
                'total' => $registryUsers->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync all data (offices, employees, users)
     */
    public function syncAll(): JsonResponse
    {
        try {
            $results = [
                'offices' => $this->syncOffices()->getData(),
                'employees' => $this->syncEmployees()->getData(),
                'users' => $this->syncUsers()->getData(),
            ];

            return response()->json([
                'message' => 'All data synced successfully',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
