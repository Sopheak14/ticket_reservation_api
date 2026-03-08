<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
    ];

    // // Scope for search
    // public function scopeSearch($query, $term)
    // {
    //     return $query->where('name', 'like', "%{$term}%")
    //                  ->orWhere('phone', 'like', "%{$term}%")
    //                  ->orWhere('email', 'like', "%{$term}%");
    // }
}
