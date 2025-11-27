<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PantryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'name',
        'quantity',
        'unit',
        'expiry_date',
        'location',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
}

