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
        'status',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'expiry_date' => 'date',
        'fee' => 'decimal:4',
    ];

    protected $appends = ['total_paid', 'is_dp_fully_paid'];

    public function getTotalPaidAttribute()
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getIsDpFullyPaidAttribute()
    {
        // Load latest price list for the unit
        $priceList = $this->unit->priceList()->latest('effective_date')->first();
        if (!$priceList) return false;
        
        return $this->total_paid >= (float) $priceList->downpayment_amount;
    }

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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }
}
