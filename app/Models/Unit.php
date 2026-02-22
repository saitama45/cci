<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'block_num',
        'lot_num',
        'name',
        'sqm_area',
        'status',
        'svg_path',
    ];

    protected $casts = [
        'sqm_area' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function priceList()
    {
        return $this->hasOne(PriceList::class)->latestOfMany('effective_date');
    }
}