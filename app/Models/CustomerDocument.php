<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    protected $fillable = [
        'customer_id',
        'document_requirement_id',
        'file_path',
        'file_name',
        'status',
        'remarks',
        'approved_at',
        'approved_by'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function requirement()
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_requirement_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
