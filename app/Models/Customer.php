<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'account_no',
        'first_name',
        'middle_name',
        'last_name',
        'tin',
        'email',
        'contact_no',
        'maceda_status',
        'home_no_street',
        'barangay',
        'city',
        'region',
        'zip_code',
        'gender',
        'civil_status',
        'profile_photo',
    ];

    protected static function booted()
    {
        static::creating(function ($customer) {
            $year = date('y');
            $month = date('m');
            
            // Get the last account_no to extract the sequence
            $lastCustomer = static::orderBy('id', 'desc')->first();
            
            $sequence = 1;
            if ($lastCustomer && $lastCustomer->account_no) {
                $parts = explode('-', $lastCustomer->account_no);
                $lastSequence = (int) end($parts);
                $sequence = $lastSequence + 1;
            }
            
            $customer->account_no = sprintf('%s-%s-%04d', $year, $month, $sequence);
        });
    }

    protected $casts = [
        'maceda_status' => 'boolean',
    ];

    protected $appends = ['full_name'];

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function getFullNameAttribute()
    {
        $middle = $this->middle_name ? " {$this->middle_name} " : " ";
        return "{$this->first_name}{$middle}{$this->last_name}";
    }

    // Mutators for Uppercase
    protected function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = strtoupper($value);
    }

    protected function setMiddleNameAttribute($value)
    {
        $this->attributes['middle_name'] = $value ? strtoupper($value) : null;
    }

    protected function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = strtoupper($value);
    }
}
