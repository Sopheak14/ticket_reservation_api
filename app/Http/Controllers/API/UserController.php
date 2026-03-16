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
        $data = $request->validate([
            'role_id'  => 'required|exists:roles,role_id',
            'name'     => 'required|string|max:150',
            'phone'    => 'required|string|max:20|unique:users,phone',
            'email'    => 'nullable|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'status'   => 'in:active,inactive,disabled',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data'    => $user->load('role'),
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

        $data = $request->validate([
            'role_id'  => 'exists:roles,role_id',
            'name'     => 'string|max:150',
            'phone'    => "string|max:20|unique:users,phone,{$id}",
            'email'    => "nullable|email|max:150|unique:users,email,{$id}",
            'password' => 'nullable|string|min:6',
            'status'   => 'in:active,inactive,disabled',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

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