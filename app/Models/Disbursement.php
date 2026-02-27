<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'voucher_no',
        'payment_date',
        'payment_method',
        'bank_account_id',
        'total_amount',
        'status',
        'notes',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'journal_entry_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_id');
    }

    public function items()
    {
        return $this->hasMany(DisbursementItem::class);
    }

    public function pdcDetail()
    {
        return $this->hasOne(PdcVault::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
