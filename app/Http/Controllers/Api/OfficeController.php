<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registry\RegistryOffice;
use Illuminate\Http\JsonResponse;

class OfficeController extends Controller
{
    /**
     * Display a listing of offices from pbo-registry.
     */
    public function index(): JsonResponse
    {
        $offices = RegistryOffice::all();
        return response()->json($offices);
    }
}
