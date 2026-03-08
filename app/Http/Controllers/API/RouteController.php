<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    // GET /api/routes/list (Public)
    public function index(Request $request): JsonResponse
    {
        $routes = Route::active()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->get(['route_id', 'departure_location', 'destination_location', 'distance']);

        if ($routes->isEmpty() && $request->search) {
            return response()->json([
                'success' => false,
                'message' => "No routes found for '{$request->search}'",
                'data'    => [],
            ], 404);
        }

        if ($routes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No routes available',
                'data'    => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Routes retrieved successfully',
            'total'   => $routes->count(),
            'data'    => $routes,
        ]);
    }

    // POST /api/routes
   public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'departure_location'   => 'required|string|max:200',
                'destination_location' => 'required|string|max:200',
                'distance'             => 'nullable|numeric|min:0',
                'status'               => 'required|in:active,inactive',
            ]);

            if (!isset($data['status'])) {
                $data['status'] = 'active';
            }

            $route = Route::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully',
                'data'    => $route,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create route',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // GET /api/routes/{id}
    public function show(int $id): JsonResponse
    {
        try {
            $route = Route::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Route retrieved successfully.',
                'data'    => $route,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Route with ID {$id} not found.",
                'data'    => null,
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
    // PUT /api/routes/{id}
    public function update(Request $request, int $id): JsonResponse
    {
       try {
            $route = Route::findOrFail($id);

            $data = $request->validate([
                'departure_location'   => 'sometimes|string|max:200',
                'destination_location' => 'sometimes|string|max:200',
                'distance'             => 'sometimes|nullable|numeric|min:0',
                'status'               => 'sometimes|in:active,inactive',
            ]);

            $route->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully.',
                'data'    => $route->fresh(),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Route with ID {$id} not found.",
                'data'    => null,
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check your input.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    // DELETE /api/routes/{id}
   public function destroy(int $id): JsonResponse
    {
        try {
            $route = Route::findOrFail($id);

            $route->delete();

            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully.',
                'data'    => null,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Route with ID {$id} not found.",
                'data'    => null,
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
