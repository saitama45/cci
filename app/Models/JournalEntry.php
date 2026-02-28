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
            return route('journal-entries.show', $this->id);
        }

        switch ($this->referenceable_type) {
            case 'App\Models\Payment':
                $payment = \App\Models\Payment::find($this->referenceable_id);
                if ($payment && $payment->reservation_id) {
                    $sale = \App\Models\ContractedSale::where('reservation_id', $payment->reservation_id)->first();
                    if ($sale) {
                        return route('payments.show', ['payment' => $sale->id, 'search' => $this->reference_no]);
                    }
                }
                return route('payments.index', ['search' => $this->reference_no]);

            case 'App\Models\Reservation':
                return route('reservations.index', ['search' => $this->referenceable_id]);

            case 'App\Models\Bill':
                return route('accounting.bills.show', $this->referenceable_id);

            case 'App\Models\Disbursement':
                return route('accounting.disbursements.show', $this->referenceable_id);

            default:
                return route('journal-entries.show', $this->id);
        }
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
