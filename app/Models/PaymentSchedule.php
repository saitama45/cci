<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'contracted_sale_id',
        'customer_id',
        'unit_id',
        'type',
        'installment_no',
        'due_date',
        'amount_due',
        'principal',
        'interest',
        'remaining_balance',
        'amount_paid',
        'status',
        'remarks',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount_due' => 'decimal:4',
        'principal' => 'decimal:4',
        'interest' => 'decimal:4',
        'remaining_balance' => 'decimal:4',
        'amount_paid' => 'decimal:4',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function contractedSale()
    {
        return $this->belongsTo(ContractedSale::class);
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
