<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Seat;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(
        private BookingService      $bookingService,
        private NotificationService $notificationService,
        private TicketService       $ticketService
    ) {}

    // GET /api/bookings
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['customer', 'bookingDetails.schedule.route', 'payments'])
            ->when($request->search,         fn($q) => $q->search($request->search))
            ->when($request->booking_status, fn($q) => $q->byStatus($request->booking_status))
            ->when($request->payment_status, fn($q) => $q->byPaymentStatus($request->payment_status))
            ->when($request->date,           fn($q) => $q->byDate($request->date))
            ->when($request->route_id,       fn($q) => $q->byRoute($request->route_id))
            ->when($request->customer_id,    fn($q) => $q->byCustomer($request->customer_id))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    // GET /api/my/bookings (Customer)
    public function myBookings(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer profile not found'], 404);
        }

        $bookings = Booking::with(['bookingDetails.schedule.route', 'bookingDetails.seat', 'payments'])
            ->byCustomer($customer->customer_id)
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    // GET /api/bookings/{id}
    public function show(int $id): JsonResponse
    {
        $booking = Booking::with([
            'customer',
            'createdBy',
            'bookingDetails.schedule.route',
            'bookingDetails.schedule.vehicle',
            'bookingDetails.seat',
            'payments.paymentMethod',
            'notifications',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $booking]);
    }

    // GET /api/bookings/find/{code} (Public)
    public function findByCode(string $code): JsonResponse
    {
        $booking = Booking::with([
            'customer',
            'bookingDetails.schedule.route',
            'bookingDetails.seat',
            'payments',
        ])->where('booking_code', $code)->firstOrFail();

        return response()->json(['success' => true, 'data' => $booking]);
    }

    // POST /api/bookings/validate-qr (Public)
    public function validateQR(Request $request): JsonResponse
    {
        $request->validate(['booking_code' => 'required|string']);

        $booking = Booking::with(['customer', 'bookingDetails.schedule.route'])
            ->where('booking_code', $request->booking_code)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'valid' => false, 'message' => 'Booking not found'], 404);
        }

        $isValid = $booking->booking_status === 'confirmed' && $booking->payment_status === 'paid';

        return response()->json([
            'success' => true,
            'valid'   => $isValid,
            'message' => $isValid ? 'Valid ticket ✅' : 'Invalid ticket ❌',
            'data'    => [
                'booking_code'   => $booking->booking_code,
                'customer_name'  => $booking->customer->name,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
            ],
        ]);
    }

    // POST /api/bookings/initiate — STEP 1
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_id'    => 'required|integer|exists:schedules,schedule_id',
            'customer_name'  => 'required|string|max:150',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:150',
        ]);

        $schedule = Schedule::with(['route', 'vehicle'])->findOrFail($request->schedule_id);

        if ($schedule->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Schedule not available'], 422);
        }

        if ($schedule->remaining_seats <= 0) {
            return response()->json(['success' => false, 'message' => 'No seats available'], 422);
        }

        $customer = Customer::firstOrCreate(
            ['phone' => $request->customer_phone],
            ['name' => $request->customer_name, 'email' => $request->customer_email]
        );

        $booking = Booking::create([
            'booking_code'   => Booking::generateBookingCode(),
            'customer_id'    => $customer->customer_id,
            'booking_date'   => now()->toDateString(),
            'booking_status' => 'pending',
            'total_amount'   => 0,
            'payment_status' => 'unpaid',
            'created_by'     => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking initiated — select seats next',
            'data'    => [
                'booking'  => $booking,
                'customer' => $customer,
                'schedule' => [
                    'schedule_id'        => $schedule->schedule_id,
                    'route'              => $schedule->route->full_route,
                    'departure_datetime' => $schedule->departure_datetime,
                    'arrival_datetime'   => $schedule->arrival_datetime,
                    'vehicle_number'     => $schedule->vehicle->vehicle_number,
                    'vehicle_type'       => $schedule->vehicle->vehicle_type,
                    'base_price'         => $schedule->base_price,
                    'remaining_seats'    => $schedule->remaining_seats,
                ],
            ],
        ], 201);
    }

    // POST /api/bookings/{id}/select-seats — STEP 2
    public function selectSeats(Request $request, int $bookingId): JsonResponse
    {
        $request->validate([
            'schedule_id' => 'required|integer|exists:schedules,schedule_id',
            'seat_ids'    => 'required|array|min:1',
            'seat_ids.*'  => 'integer|exists:seats,seat_id',
        ]);

        $booking = Booking::findOrFail($bookingId);

        if ($booking->booking_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Booking not in pending status'], 422);
        }

        $schedule = Schedule::findOrFail($request->schedule_id);
        $seats    = Seat::whereIn('seat_id', $request->seat_ids)->get();

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            BookingDetail::where('booking_id', $bookingId)->delete();

            foreach ($seats as $seat) {
                if ($seat->isBookedForSchedule($request->schedule_id)) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Seat {$seat->seat_number} is already booked"], 422);
                }

                $price        = $schedule->base_price;
                $totalAmount += $price;

                BookingDetail::create([
                    'booking_id'  => $bookingId,
                    'schedule_id' => $request->schedule_id,
                    'seat_id'     => $seat->seat_id,
                    'price'       => $price,
                    'status'      => 'pending',
                ]);
            }

            $booking->update(['total_amount' => $totalAmount]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Seats selected — proceed to payment',
                'data'    => [
                    'booking'      => $booking->fresh(),
                    'seats'        => $seats->map(fn($s) => [
                        'seat_id'     => $s->seat_id,
                        'seat_number' => $s->seat_number,
                        'price'       => $schedule->base_price,
                    ]),
                    'total_amount' => $totalAmount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/bookings/{id}/confirm — STEP 3
    public function confirm(int $bookingId): JsonResponse
    {
        $booking = Booking::with(['customer', 'bookingDetails'])->findOrFail($bookingId);

        if ($booking->payment_status !== 'paid') {
            return response()->json(['success' => false, 'message' => 'Payment must be completed first'], 422);
        }

        DB::beginTransaction();
        try {
            $booking->bookingDetails()->update(['status' => 'confirmed']);
            $booking->update(['booking_status' => 'confirmed']);

            foreach ($booking->bookingDetails as $detail) {
                Schedule::where('schedule_id', $detail->schedule_id)->decrement('available_seats');
            }

            $qrPath  = $this->ticketService->generateQRCode($booking);
            $pdfPath = $this->ticketService->generateTicketPDF($booking);
            $booking->update(['qr_code_path' => $qrPath, 'ticket_pdf_path' => $pdfPath]);

            $this->notificationService->sendBookingConfirmation($booking);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed! 🎉',
                'data'    => $booking->fresh()->load(['customer', 'bookingDetails.schedule.route', 'bookingDetails.seat']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/bookings/{id}/cancel
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $booking = Booking::with('bookingDetails')->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Already cancelled'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($booking->bookingDetails as $detail) {
                Schedule::where('schedule_id', $detail->schedule_id)->increment('available_seats');
            }

            $booking->bookingDetails()->update(['status' => 'cancelled']);
            $booking->update(['booking_status' => 'cancelled', 'cancel_reason' => $request->reason]);

            $this->notificationService->sendCancellation($booking);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Booking cancelled', 'data' => $booking->fresh()]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/bookings/search
    public function search(Request $request): JsonResponse
    {
        $request->validate(['query' => 'required|string|min:2']);

        $bookings = Booking::with(['customer', 'bookingDetails.schedule.route', 'bookingDetails.seat'])
            ->search($request->query)
            ->latest()->limit(20)->get();

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    // GET /api/bookings/{id}/download-ticket
    public function downloadTicket(int $id)
    {
        $booking = Booking::findOrFail($id);
        $path    = storage_path('app/public/' . $booking->ticket_pdf_path);

        if (!$booking->ticket_pdf_path || !file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        return response()->download($path);
    }
}