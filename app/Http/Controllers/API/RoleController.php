<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // GET /api/roles
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->get();

        return response()->json([
            'success' => true,
            'data'    => $roles,
        ]);
    }

    // POST /api/roles
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role_name'   => 'required|string|max:100|unique:roles,role_name',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data'    => $role,
        ], 201);
    }

    // GET /api/roles/{id}
    public function show(int $id): JsonResponse
    {
        $role = Role::withCount('users')->find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role with ID {$id} not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $role,
        ]);
    }


    // PUT /api/roles/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role with ID {$id} not found",
            ], 404);
        }

        $data = $request->validate([
            'role_name'   => "string|max:100|unique:roles,role_name,{$id},role_id",
            'description' => 'nullable|string',
        ]);

        $role->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data'    => $role,
        ]);
    }


    // DELETE /api/roles/{id}
    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role with ID {$id} not found",
            ], 404);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role — users are assigned to it',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

}