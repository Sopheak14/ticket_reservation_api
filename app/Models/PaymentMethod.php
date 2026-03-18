<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $primaryKey = 'payment_method_id';

    protected $fillable = [
        'method_name',
        'is_active',
        'configuration_json',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'configuration_json' => 'array',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class, 'payment_method_id', 'payment_method_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}