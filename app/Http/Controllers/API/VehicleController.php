<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    // GET /api/vehicles
    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::withCount('seats')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type,   fn($q) => $q->byType($request->type))
            ->when($request->search, fn($q) => $q->search($request->search))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $vehicles,
        ]);
    }

    // POST /api/vehicles
   public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_number' => 'required|string|max:8|unique:vehicles,vehicle_number',
            'vehicle_type'   => 'required|in:big_bus,mini_bus,family_car,minivan,train,flight',
            'seat_capacity'  => 'required|integer|min:1|max:40',
            'status'         => 'in:active,maintenance,inactive',
            'description'    => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::create($data);

            if (!$vehicle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not created',
                ], 500);
            }

            // Auto-generate Seats (dynamic numbering)
            $total   = $data['seat_capacity'];
            $created = 0;

            for ($i = 1; $i <= $total; $i++) {
                Seat::create([
                    'vehicle_id'  => $vehicle->vehicle_id,
                    'seat_number' => "S{$i}", // seat numbering S1, S2, ...
                    'status'      => 'available',
                ]);
                $created++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Vehicle created with {$created} seats",
                'data'    => $vehicle->load('seats'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage()); // log error for debugging
            return response()->json([
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    // GET /api/vehicles/{id}
    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::with('seats')->withCount('seats')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $vehicle,
        ]);
    }

    // PUT /api/vehicles/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $data = $request->validate([
            'vehicle_number' => "string|max:50|unique:vehicles,vehicle_number,{$id},vehicle_id",
            'vehicle_type'   => 'in:big_bus,mini_bus,family_car,minivan,train,flight',
            'seat_capacity'  => 'integer|min:1|max:500',
            'status'         => 'in:active,maintenance,inactive',
            'description'    => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $vehicle->update($data);

            // Regenerate seats if seat_capacity changed
            if (isset($data['seat_capacity'])) {
                // Clear old seats
                $vehicle->seats()->delete();
                // Generate new seats
                $total   = $data['seat_capacity'];
                $created = 0;

                for ($i = 1; $i <= $total; $i++) {
                    Seat::create([
                        'vehicle_id'  => $vehicle->vehicle_id,
                        'seat_number' => "S{$i}", // seat numbering S1, S2, ...
                        'status'      => 'available',
                    ]);
                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data'    => $vehicle->load('seats'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    // DELETE /api/vehicles/{id}
    public function destroy(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        if ($vehicle->schedules()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete vehicle — it has active schedules',
            ], 422);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }

    // GET /api/vehicles/{id}/seat-map
    public function seatMap(int $id): JsonResponse
    {
        $vehicle = Vehicle::with(['seats' => fn($q) => $q->orderBy('seat_number')])
                    ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
            'vehicle_id'     => $vehicle->vehicle_id,
            'vehicle_number' => $vehicle->vehicle_number,
            'vehicle_type'   => $vehicle->vehicle_type,
            'seat_capacity'  => $vehicle->seat_capacity,
            'total_seats'    => $vehicle->seats->count(),
            'seat_map'       => $vehicle->seats->map(fn($s) => [
            'seat_id'     => $s->seat_id,
                'seat_number' => $s->seat_number,
                'status'      => $s->status,
            ]),
            ],
        ]);
    }
}