<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'chart_of_account_id',
        'description',
        'quantity',
        'quantity_billed',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_billed' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
