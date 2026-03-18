<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    // GET /api/payment-methods
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::withCount('payments')->get();

        if ($methods->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No payment methods found',
                'data'    => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment methods retrieved successfully',
            'total'   => $methods->count(),
            'data'    => $methods,
        ]);
    }

    // POST /api/payment-methods
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method_name'        => 'required|string|max:100|unique:payment_methods,method_name',
            'is_active'          => 'boolean',
            'configuration_json' => 'nullable|array',
        ]);

        $method = PaymentMethod::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method created successfully',
            'data'    => $method,
        ], 201);
    }

    // GET /api/payment-methods/{id}
    public function show(int $id): JsonResponse
    {
        $method = PaymentMethod::withCount('payments')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $method,
        ]);
    }

    // PUT /api/payment-methods/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);

        $data = $request->validate([
            'method_name'        => "string|max:100|unique:payment_methods,method_name,{$id},payment_method_id",
            'is_active'          => 'boolean',
            'configuration_json' => 'nullable|array',
        ]);

        $method->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully',
            'data'    => $method->fresh(),
        ]);
    }

    // DELETE /api/payment-methods/{id}
    public function destroy(int $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);

        if ($method->payments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete — this method has payment history',
            ], 422);
        }

        $method->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully',
        ]);
    }

    // POST /api/payment-methods/{id}/toggle
    public function toggle(int $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);

        $method->update(['is_active' => !$method->is_active]);

        return response()->json([
            'success' => true,
            'message' => $method->is_active ? 'Payment method enabled' : 'Payment method disabled',
            'data'    => $method->fresh(),
        ]);
    }
}