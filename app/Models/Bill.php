<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_order_id',
        'type',
        'bill_number',
        'bill_date',
        'due_date',
        'total_amount',
        'vat_amount',
        'ewt_amount',
        'net_amount',
        'status',
        'notes',
        'project_id',
        'journal_entry_id',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'ewt_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(BillItem::class);
    }
}
