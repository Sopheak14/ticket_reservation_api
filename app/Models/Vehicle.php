<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'vehicle_id';

    protected $fillable = [
        'vehicle_number',
        'vehicle_type',
        'seat_capacity',
        'status',
        'description',
    ];

    protected $casts = [
        'seat_capacity' => 'integer',
    ];

    public function seats()
    {
        return $this->hasMany(Seat::class, 'vehicle_id', 'vehicle_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'vehicle_id', 'vehicle_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vehicle_type', $type);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('vehicle_number', 'like', "%{$term}%")
                     ->orWhere('vehicle_type', 'like', "%{$term}%");
    }
}