<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Schedule;

class BookingService
{
    // Expire pending bookings older than 15 minutes
    public function expirePendingBookings(): int
    {
        $bookings = Booking::where('booking_status', 'pending')
                           ->where('payment_status', 'unpaid')
                           ->where('created_at', '<', now()->subMinutes(15))
                           ->get();

        foreach ($bookings as $booking) {
            // Restore available seats
            foreach ($booking->bookingDetails as $detail) {
                Schedule::where('schedule_id', $detail->schedule_id)
                        ->increment('available_seats');
            }

            // Cancel booking details
            $booking->bookingDetails()->update(['status' => 'cancelled']);

            // Cancel booking
            $booking->update([
                'booking_status' => 'expired',
                'cancel_reason'  => 'Booking expired due to no payment',
            ]);
        }

        return $bookings->count();
    }

    // Check if booking belongs to customer
    public function belongsToCustomer(Booking $booking, int $customerId): bool
    {
        return $booking->customer_id === $customerId;
    }

    // Calculate total amount
    public function calculateTotal(array $seatIds, float $basePrice): float
    {
        return count($seatIds) * $basePrice;
    }

    // Get booking summary
    public function getSummary(Booking $booking): array
    {
        return [
            'booking_code'    => $booking->booking_code,
            'customer_name'   => $booking->customer?->name,
            'customer_phone'  => $booking->customer?->phone,
            'total_seats'     => $booking->bookingDetails->count(),
            'total_amount'    => $booking->total_amount,
            'booking_status'  => $booking->booking_status,
            'payment_status'  => $booking->payment_status,
            'booking_date'    => $booking->booking_date,
        ];
    }
}