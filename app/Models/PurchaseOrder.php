<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'project_id',
        'po_number',
        'po_date',
        'expected_delivery_date',
        'total_amount',
        'tax_type',
        'vat_amount',
        'ewt_rate',
        'ewt_amount',
        'net_amount',
        'status',
        'notes',
        'prepared_by',
        'approved_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'ewt_rate' => 'decimal:2',
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

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
