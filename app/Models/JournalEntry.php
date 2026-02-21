<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'transaction_date',
        'reference_no',
        'referenceable_type',
        'referenceable_id',
        'description',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    protected $appends = ['reference_url'];

    public function getReferenceUrlAttribute()
    {
        if (!$this->referenceable_type || !$this->referenceable_id) {
            return null;
        }

        return match ($this->referenceable_type) {
            'App\Models\Payment' => route('payments.index', ['search' => $this->reference_no]),
            'App\Models\Reservation' => route('reservations.index', ['search' => $this->referenceable_id]),
            default => null,
        };
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function referenceable()
    {
        return $this->morphTo();
    }
}
