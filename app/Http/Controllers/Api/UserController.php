<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registry\RegistryUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users from pbo-registry.
     */
    public function index(): JsonResponse
    {
        try {
            $users = RegistryUser::with('office')->get();
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'username' => 'required|string|unique:' . env('DB_DATABASE_REGISTRY', 'pbo_registry') . '.users,username',
                'password' => 'required|string|min:6',
                'usertype' => 'required|string',
                'office' => 'required|integer',
            ]);

            // Hash the password before storing
            $validated['password'] = Hash::make($validated['password']);

            $user = RegistryUser::create($validated);
            
            // Don't return the password
            $user = $user->load('office');
            $user->makeHidden('password');
            
            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a user.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = RegistryUser::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|string',
                'username' => 'sometimes|string|unique:' . env('DB_DATABASE_REGISTRY', 'pbo_registry') . '.users,username,' . $id,
                'password' => 'sometimes|string|min:6',
                'usertype' => 'sometimes|string',
                'office' => 'sometimes|integer',
            ]);

            // Hash the password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);
            return response()->json($user->load('office'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a user.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = RegistryUser::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
