<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'title',
        'instructions',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',     // JSON to PHP array
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    // MANY-TO-MANY with extra pivot data
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class)
                    ->withPivot(['quantity', 'unit'])
                    ->withTimestamps();
    }
}
