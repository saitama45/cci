<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'tin',
        'address',
        'contact_person',
        'email',
        'phone',
        'bank_name',
        'bank_account_no',
        'bank_branch',
        'category',
        'verification_status',
        'payment_terms',
        'default_expense_account_id',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function defaultExpenseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_expense_account_id');
    }

    public function documents()
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'Verified');
    }
}
