<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'invite_code',
    ];

    public function users(){
        return $this->belongsToMany(User::class)
                    ->withPivote('role')
                    ->withTimestamps();
    }

    public function pantryItems(){
        return $this->hasMany(PantryItem::class);
    }

     public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }


    public function mealPlans()
    {
        return $this->hasMany(MealPlan::class);
    }


    public function shoppingLists()
    {
        return $this->hasMany(ShoppingList::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
