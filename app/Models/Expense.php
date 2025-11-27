<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'amount',
        'category',
        'store',
        'note',
        'receipt_url',
        'date',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
}
