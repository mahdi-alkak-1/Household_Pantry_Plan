<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'household_id'
    ];

    
    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class)
                    ->withPivot(['quantity', 'unit'])
                    ->withTimestamps();
    }
}
