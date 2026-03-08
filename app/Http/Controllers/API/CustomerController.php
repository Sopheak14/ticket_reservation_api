<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // List customers
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->latest()
            ->paginate($request->get('per_page', 15));

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer is not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $customers,
        ]);
    }


    // Create customer
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
        ]);

        // Create new customer
        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data'    => $customer,
        ], 201);
    }

    // Show customer
    public function show($id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => "Customer with ID {$id} is not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $customer,
        ]);
    }


    // Update customer
    public function update(Request $request, $id): JsonResponse
    {
        // Find customer by id
        $customer = Customer::find($id);

        // If not found
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => "Customer with ID {$id} is not found",
            ], 404);
        }

        // Validate input
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
        ]);

        // Update customer
        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data'    => $customer,
        ]);
    }

    //delete customer
    public function destroy($id):JsonResponse{
        $customer =Customer::find($id);

        if(!$customer){
            return response()->json([
                'success' => false,
                'message' => "Customer with ID {$id} not found",
            ], 404);
        }
        $customer ->delete();
        return response()->json([
            'success' => true,
            'message' => "Customer with ID {$id} deleted successfully",
        ]);
    }
}
