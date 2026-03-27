<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'booking_code',
        'customer_id',
        'booking_date',
        'booking_status',
        'total_amount',
        'payment_status',
        'qr_code_path',
        'ticket_pdf_path',
        'cancel_reason',
        'created_by',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class, 'booking_id', 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'booking_id', 'booking_id');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('booking_code', 'like', "%{$term}%")
                     ->orWhereHas('customer', fn($q) =>
                         $q->where('name', 'like', "%{$term}%")
                           ->orWhere('phone', 'like', "%{$term}%")
                     );
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('booking_status', $status);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('booking_date', $date);
    }

    public function scopeByRoute($query, $routeId)
    {
        return $query->whereHas('bookingDetails.schedule',
            fn($q) => $q->where('route_id', $routeId)
        );
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public static function generateBookingCode(): string
    {
        $last   = static::withTrashed()->latest('booking_id')->first();
        $number = $last ? ((int) ltrim(substr($last->booking_code, 3), '0') + 1) : 1;
        return 'TKT' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->booking_status === 'confirmed';
    }
}