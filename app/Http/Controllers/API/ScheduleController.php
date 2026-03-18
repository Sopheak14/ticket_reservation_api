<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // GET /api/schedules/search (Public)
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'departure' => 'required|string',
            'arrival'   => 'required|string',
            'date'      => 'required|date|after_or_equal:today',
        ]);

        $schedules = Schedule::with(['route', 'vehicle'])
            ->active()
            ->byDate($request->date)
            ->whereHas('route', fn($q) =>
                $q->where('departure_location', 'like', "%{$request->departure}%")
                  ->where('destination_location', 'like', "%{$request->arrival}%")
            )
            ->get()
            ->map(fn($s) => [
                'schedule_id'        => $s->schedule_id,
                'route'              => $s->route->full_route,
                'departure_datetime' => $s->departure_datetime,
                'arrival_datetime'   => $s->arrival_datetime,
                'vehicle_type'       => $s->vehicle->vehicle_type,
                'vehicle_number'     => $s->vehicle->vehicle_number,
                'base_price'         => $s->base_price,
                'available_seats'    => $s->remaining_seats,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $schedules,
        ]);
    }

    // GET /api/schedules
    public function index(Request $request): JsonResponse
    {
        $schedules = Schedule::with(['route', 'vehicle'])
            ->when($request->date,     fn($q) => $q->byDate($request->date))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->route_id, fn($q) => $q->byRoute($request->route_id))
            ->when($request->upcoming, fn($q) => $q->upcoming())
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $schedules,
        ]);
    }

    // POST /api/schedules
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id'         => 'required|exists:vehicles,vehicle_id',
            'route_id'           => 'required|exists:routes,route_id',
            'departure_datetime' => 'required|date',
            'arrival_datetime'   => 'required|date|after:departure_datetime',
            'travel_date'        => 'required|date',
            'base_price'         => 'required|numeric|min:0',
            'available_seats'    => 'required|integer|min:1',
            'status'             => 'in:active,cancelled,completed,maintenance',
        ]);

        $schedule = Schedule::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data'    => $schedule->load('route', 'vehicle'),
        ], 201);
    }

    // GET /api/schedules/{id}
    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with(['route', 'vehicle'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => array_merge($schedule->toArray(), [
                'remaining_seats' => $schedule->remaining_seats,
                'seat_map'        => $schedule->getSeatMap(),
            ]),
        ]);
    }

    // PUT /api/schedules/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        $data = $request->validate([
            'departure_datetime' => 'date',
            'arrival_datetime'   => 'date|after:departure_datetime',
            'travel_date'        => 'date',
            'base_price'         => 'numeric|min:0',
            'available_seats'    => 'integer|min:0',
            'status'             => 'in:active,cancelled,completed,maintenance',
        ]);

        $schedule->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data'    => $schedule->fresh()->load('route', 'vehicle'),
        ]);
    }

    // DELETE /api/schedules/{id}
    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        if ($schedule->bookingDetails()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete schedule — it has active bookings',
            ], 422);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }
}