<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'customer_id',
        'unit_id',
        'type',
        'installment_no',
        'due_date',
        'amount_due',
        'amount_paid',
        'status',
        'remarks',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount_due' => 'decimal:4',
        'amount_paid' => 'decimal:4',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
