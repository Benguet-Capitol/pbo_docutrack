<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registry\RegistryEmployee;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees from pbo-registry.
     */
    public function index(): JsonResponse
    {
        $employees = RegistryEmployee::all();
        return response()->json($employees);
    }
}
