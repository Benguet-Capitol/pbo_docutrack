<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registry\RegistryUser;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Display a listing of users from pbo-registry.
     */
    public function index(): JsonResponse
    {
        $users = RegistryUser::all();
        return response()->json($users);
    }
}
