<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_reconciliation_id',
        'transaction_type',
        'transaction_id',
        'transaction_date',
        'type',
        'reference_no',
        'description',
        'amount',
        'is_cleared',
        'cleared_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'cleared_date' => 'date',
        'amount' => 'decimal:4',
        'is_cleared' => 'boolean',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function transaction()
    {
        return $this->morphTo();
    }
}
