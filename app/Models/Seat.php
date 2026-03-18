<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $primaryKey = 'seat_id';

    protected $fillable = [
        'vehicle_id',
        'seat_number',
        'status',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class, 'seat_id', 'seat_id');
    }

    public function isBookedForSchedule(int $scheduleId): bool
    {
        return $this->bookingDetails()
                    ->where('schedule_id', $scheduleId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}