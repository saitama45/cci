<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdcVault extends Model
{
    use HasFactory;

    protected $table = 'pdc_vault';

    protected $fillable = [
        'company_id',
        'type',
        'disbursement_id',
        'payment_id',
        'customer_id',
        'vendor_id',
        'bank_id',
        'check_no',
        'check_date',
        'bank_name',
        'bank_branch',
        'amount',
        'status',
        'cleared_date',
        'remarks',
    ];

    protected $casts = [
        'check_date' => 'date',
        'cleared_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function disbursement()
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function scopeInward($query)
    {
        return $query->where('type', 'Inward');
    }

    public function scopeOutward($query)
    {
        return $query->where('type', 'Outward');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeCleared($query)
    {
        return $query->where('status', 'Cleared');
    }
}
