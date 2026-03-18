<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'booking_id',
        'payment_method_id',
        'payment_reference',
        'amount',
        'payment_status',
        'payment_datetime',
        'gateway_response',
        'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'payment_datetime' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function scopeSuccess($query)
    {
        return $query->where('payment_status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public static function generateReference(): string
    {
        return 'PAY-' . strtoupper(uniqid()) . '-' . now()->format('Ymd');
    }
}