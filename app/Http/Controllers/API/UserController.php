<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /api/users
    public function index(Request $request): JsonResponse
    {
        $users = User::with('role')
            ->when($request->search,  fn($q) => $q->search($request->search))
            ->when($request->role_id, fn($q) => $q->where('role_id', $request->role_id))
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }

    // POST /api/users
    public function store(Request $request): JsonResponse
    {
        // Validation (without role_id for first user)
        $data = $request->validate([
            'role_id'  => 'nullable|exists:roles,role_id', // optional if first user
            'name'     => 'required|string|max:150',
            'phone'    => 'required|string|max:20|unique:users,phone',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'status'   => 'nullable|in:active,inactive,disabled',
        ]);

        // Default status
        $data['status'] = $data['status'] ?? 'active';

        // Hash password
        $data['password'] = Hash::make($data['password']);

        //First user? assign Admin role automatically
        if (User::count() === 0) {
            $role = Role::where('role_name', 'Admin')->first();
            $data['role_id'] = $role->role_id;
        } else {
            // For other users, role_id must come from frontend
            if (empty($data['role_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'role_id is required for non-admin users'
                ], 422);
            }
        }

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data'    => $user->load('role'), // include role info
        ], 201);
    }

    // GET /api/users/{id}
    public function show(int $id): JsonResponse
    {
        $user = User::with('role', 'customer')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    // PUT /api/users/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // ✅ Validate
        $data = $request->validate([
            'role_id'  => 'nullable|exists:roles,role_id',
            'name'     => 'required|string|max:150',
            'phone'    => 'required|string|max:20|unique:users,phone,' . $user->id,
            'email'    => 'nullable|email|max:150|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'status'   => 'required|in:active,inactive,disabled',
        ]);

        // ✅ Password (optional)
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // ✅ Update user
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data'    => $user->fresh()->load('role'),
        ]);
    }


    // DELETE /api/users/{id}
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
