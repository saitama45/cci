<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'chart_of_account_id',
        'statement_date',
        'statement_ending_balance',
        'cleared_balance',
        'difference',
        'status',
        'prepared_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_ending_balance' => 'decimal:4',
        'cleared_balance' => 'decimal:4',
        'difference' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function lines()
    {
        return $this->hasMany(BankReconciliationLine::class);
    }
}
