<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'week_start_date',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    public function items()
    {
        return $this->hasMany(MealPlanItem::class);
    }
}
