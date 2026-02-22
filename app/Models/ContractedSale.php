<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractedSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_no',
        'company_id',
        'reservation_id',
        'customer_id',
        'unit_id',
        'tcp',
        'downpayment_paid',
        'loanable_amount',
        'interest_rate',
        'terms_month',
        'monthly_amortization',
        'repricing_config',
        'start_date',
        'status',
    ];

    protected $casts = [
        'tcp' => 'decimal:4',
        'downpayment_paid' => 'decimal:4',
        'loanable_amount' => 'decimal:4',
        'interest_rate' => 'decimal:4',
        'monthly_amortization' => 'decimal:4',
        'repricing_config' => 'array',
        'start_date' => 'date',
    ];

    protected $appends = ['total_paid', 'total_dp_paid', 'principal_paid', 'current_balance'];

    public function getTotalPaidAttribute()
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getTotalDpPaidAttribute()
    {
        return (float) $this->payments()
            ->whereIn('payment_type', ['Reservation Fee', 'Downpayment'])
            ->sum('amount');
    }

    public function getPrincipalPaidAttribute()
    {
        return (float) $this->paymentSchedules()
            ->where('type', 'Amortization')
            ->where('status', 'Paid')
            ->sum('principal');
    }

    public function getCurrentBalanceAttribute()
    {
        // Principal Balance = Original Loanable Amount - Principal portion of PAID installments
        $loanable = (float) $this->loanable_amount;
        $paidPrincipal = $this->getPrincipalPaidAttribute();
            
        return max(0, $loanable - $paidPrincipal);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

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

    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'reservation_id', 'reservation_id');
    }
}
