<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'chart_of_account_id',
        'description',
        'amount',
        'project_id',
        'unit_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
