<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Route extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'route_id';

    protected $fillable =[
        'departure_location',
        'destination_location',
        'distance',
        'status',
    ];
    protected $casts =[
        'distance' => 'decimal:2',
    ];
    public function scopeActive($query){
        return $query->where(
            'status', 'active'
        );
    }

    public function schedules()
    {return $this->hasMany(Schedule::class, 'route_id', 'route_id');}


    public function scopeSearch($query, $term){
        return $query -> where('departure_location','like',"%{$term}%")
                    ->orWhere('destination_location','like',"%{$term}%");   
    }
    public function getFullRouteAttribute(): string
    {
        return "{$this->departure_location} → {$this->destination_location}";
    }

}
