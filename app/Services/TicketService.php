<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    // Generate QR Code for booking
    public function generateQRCode(Booking $booking): string
    {
        // QR Code directory
        $directory = 'qrcodes';
        Storage::disk('public')->makeDirectory($directory);

        $filename = "qr_{$booking->booking_code}.svg";
        $path     = "{$directory}/{$filename}";

        // Generate simple SVG QR placeholder
        // In production: use simplesoftwareio/simple-qrcode package
        $qrContent = $this->generateSimpleQR($booking->booking_code);

        Storage::disk('public')->put($path, $qrContent);

        return $path;
    }

    // Generate PDF Ticket
    public function generateTicketPDF(Booking $booking): string
    {
        // PDF directory
        $directory = 'tickets';
        Storage::disk('public')->makeDirectory($directory);

        $filename = "ticket_{$booking->booking_code}.html";
        $path     = "{$directory}/{$filename}";

        // Load booking details
        $booking->load([
            'customer',
            'bookingDetails.schedule.route',
            'bookingDetails.schedule.vehicle',
            'bookingDetails.seat',
        ]);

        // Generate HTML ticket
        $html = $this->generateTicketHTML($booking);

        Storage::disk('public')->put($path, $html);

        // In production: use barryvdh/laravel-dompdf
        // $pdf = PDF::loadView('tickets.pdf', compact('booking'));
        // Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    // ── Private Methods ───────────────────────────────────────────────────────

    private function generateSimpleQR(string $text): string
    {
        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
            <rect width="200" height="200" fill="white"/>
            <rect x="10" y="10" width="60" height="60" fill="none" stroke="black" stroke-width="5"/>
            <rect x="20" y="20" width="40" height="40" fill="black"/>
            <rect x="130" y="10" width="60" height="60" fill="none" stroke="black" stroke-width="5"/>
            <rect x="140" y="20" width="40" height="40" fill="black"/>
            <rect x="10" y="130" width="60" height="60" fill="none" stroke="black" stroke-width="5"/>
            <rect x="20" y="140" width="40" height="40" fill="black"/>
            <text x="100" y="110" text-anchor="middle" font-size="12" fill="black">{$text}</text>
        </svg>
        SVG;
    }

    private function generateTicketHTML(Booking $booking): string
    {
        $detail    = $booking->bookingDetails->first();
        $route     = $detail?->schedule?->route;
        $schedule  = $detail?->schedule;
        $vehicle   = $schedule?->vehicle;

        $seats = $booking->bookingDetails->map(fn($d) => $d->seat?->seat_number)->join(', ');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Ticket {$booking->booking_code}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .ticket { border: 2px solid #333; padding: 20px; max-width: 600px; margin: 0 auto; }
                .header { text-align: center; background: #1a56db; color: white; padding: 15px; }
                .info { margin: 15px 0; }
                .info table { width: 100%; border-collapse: collapse; }
                .info td { padding: 8px; border-bottom: 1px solid #ddd; }
                .info td:first-child { font-weight: bold; color: #555; width: 40%; }
                .footer { text-align: center; margin-top: 20px; color: #888; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="header">
                    <h2>🎫 SUS SUPER Travels</h2>
                    <h3>Booking #{$booking->booking_code}</h3>
                </div>
                <div class="info">
                    <table>
                        <tr>
                            <td>Passenger</td>
                            <td>{$booking->customer?->name}</td>
                        </tr>
                        <tr>
                            <td>Phone</td>
                            <td>{$booking->customer?->phone}</td>
                        </tr>
                        <tr>
                            <td>Route</td>
                            <td>{$route?->departure_location} → {$route?->destination_location}</td>
                        </tr>
                        <tr>
                            <td>Departure</td>
                            <td>{$schedule?->departure_datetime?->format('d/m/Y H:i')}</td>
                        </tr>
                        <tr>
                            <td>Arrival</td>
                            <td>{$schedule?->arrival_datetime?->format('d/m/Y H:i')}</td>
                        </tr>
                        <tr>
                            <td>Vehicle</td>
                            <td>{$vehicle?->vehicle_number} ({$vehicle?->vehicle_type})</td>
                        </tr>
                        <tr>
                            <td>Seats</td>
                            <td>{$seats}</td>
                        </tr>
                        <tr>
                            <td>Total Amount</td>
                            <td>\${$booking->total_amount}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>{$booking->booking_status}</td>
                        </tr>
                    </table>
                </div>
                <div class="footer">
                    <p>Thank you for traveling with SUS SUPER Travels!</p>
                    <p>Generated: {$booking->updated_at?->format('d/m/Y H:i')}</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}