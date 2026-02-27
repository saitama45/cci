<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'chart_of_account_id',
        'allocated_amount',
        'spent_amount',
        'committed_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:4',
        'spent_amount' => 'decimal:4',
        'committed_amount' => 'decimal:4',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
