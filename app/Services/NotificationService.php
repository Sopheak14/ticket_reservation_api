<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;

class NotificationService
{
    // ── Booking Notifications ─────────────────────────────────────────────────

    public function sendBookingConfirmation(Booking $booking): void
    {
        $this->create(
            booking: $booking,
            type:    'booking_confirmation',
            title:   'Booking Confirmed! ✅',
            message: "Your booking {$booking->booking_code} has been confirmed successfully. Thank you for choosing us!"
        );
    }

    public function sendPaymentSuccess(Booking $booking): void
    {
        $this->create(
            booking: $booking,
            type:    'payment_success',
            title:   'Payment Successful! 💰',
            message: "Payment for booking {$booking->booking_code} was successful. Amount: \${$booking->total_amount}"
        );
    }

    public function sendPaymentFailed(Booking $booking): void
    {
        $this->create(
            booking: $booking,
            type:    'payment_failed',
            title:   'Payment Failed ❌',
            message: "Payment for booking {$booking->booking_code} failed. Please try again or contact support."
        );
    }

    public function sendCancellation(Booking $booking): void
    {
        $this->create(
            booking: $booking,
            type:    'cancellation',
            title:   'Booking Cancelled',
            message: "Your booking {$booking->booking_code} has been cancelled. We hope to see you again soon."
        );
    }

    public function sendTripReminder(Booking $booking): void
    {
        $schedule = $booking->bookingDetails->first()?->schedule;
        $departure = $schedule?->departure_datetime?->format('d/m/Y H:i') ?? 'N/A';

        $this->create(
            booking: $booking,
            type:    'trip_reminder',
            title:   'Trip Reminder 🚌',
            message: "Reminder: Your trip for booking {$booking->booking_code} departs at {$departure}. Please be on time!"
        );
    }

    public function sendScheduleUpdate(Booking $booking, string $updateMessage): void
    {
        $this->create(
            booking: $booking,
            type:    'schedule_update',
            title:   'Schedule Update ⚠️',
            message: "Update for booking {$booking->booking_code}: {$updateMessage}"
        );
    }

    // ── Private Helper ────────────────────────────────────────────────────────

    private function create(
        Booking $booking,
        string  $type,
        string  $title,
        string  $message,
        string  $channel = 'in_app'
    ): void {
        Notification::create([
            'booking_id' => $booking->booking_id,
            'user_id'    => $booking->customer?->user_id,
            'channel'    => $channel,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'status'     => 'sent',
            'sent_at'    => now(),
        ]);
    }
}