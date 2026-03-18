<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'vehicle_id',
        'route_id',
        'departure_datetime',
        'arrival_datetime',
        'travel_date',
        'available_seats',
        'base_price',
        'status',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'arrival_datetime'   => 'datetime',
        'travel_date'        => 'date',
        'base_price'         => 'decimal:2',
        'available_seats'    => 'integer',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class, 'schedule_id', 'schedule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('travel_date', $date);
    }

    public function scopeByRoute($query, $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('travel_date', '>=', now()->toDateString());
    }

    public function getRemainingSeatsAttribute(): int
    {
        $booked = $this->bookingDetails()
                       ->whereIn('status', ['pending', 'confirmed'])
                       ->count();
        return max(0, $this->available_seats - $booked);
    }

    public function getSeatMap(): array
    {
        $allSeats  = $this->vehicle->seats()->orderBy('seat_number')->get();
        $bookedIds = $this->bookingDetails()
                          ->whereIn('status', ['pending', 'confirmed'])
                          ->pluck('seat_id')
                          ->toArray();

        return $allSeats->map(fn($seat) => [
            'seat_id'     => $seat->seat_id,
            'seat_number' => $seat->seat_number,
            'status'      => in_array($seat->seat_id, $bookedIds) ? 'booked' : 'available',
        ])->toArray();
    }
}