<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'disbursement_id',
        'bill_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function disbursement()
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
