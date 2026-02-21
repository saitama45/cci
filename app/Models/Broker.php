<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'commission_rate',
        'prc_license',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
