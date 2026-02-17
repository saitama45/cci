<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequirement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_required',
        'category',
        'status',
        'sort_order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function customerDocuments()
    {
        return $this->hasMany(CustomerDocument::class);
    }
}
