<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'price_per_sqm',
        'tcp',
        'vat_amount',
        'downpayment_amount',
        'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'price_per_sqm' => 'decimal:4',
        'tcp' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'downpayment_amount' => 'decimal:4',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}