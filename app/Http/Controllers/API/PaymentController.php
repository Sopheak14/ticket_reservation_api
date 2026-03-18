<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    // GET /api/payments/methods
    public function methods(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => PaymentMethod::active()->get()]);
    }

    // POST /api/payments/booking/{id}/initiate
    public function initiate(Request $request, int $bookingId): JsonResponse
    {
        $request->validate(['payment_method_id' => 'required|exists:payment_methods,payment_method_id']);

        $booking = Booking::findOrFail($bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Already paid'], 422);
        }

        $payment = Payment::create([
            'booking_id'        => $bookingId,
            'payment_method_id' => $request->payment_method_id,
            'payment_reference' => Payment::generateReference(),
            'amount'            => $booking->total_amount,
            'payment_status'    => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Payment initiated', 'data' => $payment->load('paymentMethod')], 201);
    }

    // POST /api/payments/{id}/process
    public function process(Request $request, int $paymentId): JsonResponse
    {
        $request->validate([
            'status'           => 'required|in:success,failed',
            'gateway_response' => 'nullable|array',
            'notes'            => 'nullable|string',
        ]);

        $payment = Payment::with('booking')->findOrFail($paymentId);

        DB::beginTransaction();
        try {
            $payment->update([
                'payment_status'   => $request->status,
                'payment_datetime' => now(),
                'gateway_response' => $request->gateway_response,
                'notes'            => $request->notes,
            ]);

            if ($request->status === 'success') {
                $payment->booking->update(['payment_status' => 'paid']);
                $this->notificationService->sendPaymentSuccess($payment->booking);
            } else {
                $this->notificationService->sendPaymentFailed($payment->booking);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->status === 'success' ? 'Payment successful ✅' : 'Payment failed ❌',
                'data'    => $payment->fresh()->load('booking'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/payments/{id}/refund
    public function refund(Request $request, int $paymentId): JsonResponse
    {
        $request->validate(['notes' => 'nullable|string']);

        $payment = Payment::with('booking')->findOrFail($paymentId);

        if ($payment->payment_status !== 'success') {
            return response()->json(['success' => false, 'message' => 'Only successful payments can be refunded'], 422);
        }

        DB::beginTransaction();
        try {
            $payment->update(['payment_status' => 'refunded', 'notes' => $request->notes]);
            $payment->booking->update(['payment_status' => 'refunded', 'booking_status' => 'cancelled']);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Refunded successfully', 'data' => $payment->fresh()]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/payments/booking/{id}/history
    public function history(int $bookingId): JsonResponse
    {
        $payments = Payment::with('paymentMethod')->where('booking_id', $bookingId)->latest()->get();
        return response()->json(['success' => true, 'data' => $payments]);
    }

    // POST /api/payments/aba-webhook (Public)
    public function abaWebhook(Request $request): JsonResponse
    {
        $reference = $request->input('tran_id') ?? $request->input('payment_reference');
        $payment   = Payment::with('booking')->where('payment_reference', $reference)->first();

        if (!$payment) return response()->json(['status' => 'not_found'], 404);

        if ($request->input('status') === '0' || $request->input('status') === 'success') {
            $payment->update(['payment_status' => 'success', 'payment_datetime' => now(), 'gateway_response' => $request->all()]);
            $payment->booking->update(['payment_status' => 'paid']);
        } else {
            $payment->update(['payment_status' => 'failed', 'gateway_response' => $request->all()]);
        }

        return response()->json(['status' => 'ok']);
    }
}