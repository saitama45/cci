<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'unit_id',
        'broker_id',
        'reservation_date',
        'expiry_date',
        'fee',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'expiry_date' => 'date',
        'fee' => 'decimal:4',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }
}
