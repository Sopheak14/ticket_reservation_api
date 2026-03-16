<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/auth/login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user  = User::where($field, $request->email)
                     ->where('status', 'active')
                     ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'       => $user->load('role'),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    // POST /api/auth/register
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'phone'    => 'required|string|max:20|unique:users,phone',
            'email'    => 'nullable|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $customerRole = Role::where('role_name', 'Customer')->firstOrCreate(
            ['role_name' => 'Customer'],
            ['description' => 'Regular customer']
        );

        $user = User::create([
            'role_id'  => $customerRole->role_id,
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status'   => 'active',
        ]);

        Customer::create([
            'user_id' => $user->id,
            'name'    => $request->name,
            'phone'   => $request->phone,
            'email'   => $request->email,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data'    => [
                'user'       => $user->load('role'),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    // GET /api/auth/me
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user()->load('role', 'customer'),
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    // POST /api/auth/logout-all
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    // POST /api/auth/change-password
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}