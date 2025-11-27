<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    public function items()
    {
        return $this->hasMany(ShoppingListItem::class);
    }
}

