<?php

namespace App\Http\Controllers\Pantry;

use App\Http\Controllers\Controller;
use App\Models\PantryItem;
use App\Models\Ingredient;
use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PantryController extends Controller
{

    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $items = PantryItem::join('ingredients', 'ingredients.id', '=', 'pantry_items.ingredient_id')
            ->where('pantry_items.household_id', $household_id)
            ->orderBy('ingredients.name')
            ->get([
                'pantry_items.id',
                'pantry_items.household_id',
                'pantry_items.ingredient_id',
                'ingredients.name',       // <-- this is what ShoppingList.tsx expects as p.name
                'pantry_items.quantity',
                'pantry_items.unit',
                'pantry_items.expiry_date',
                'pantry_items.location',
                'pantry_items.created_at',
                'pantry_items.updated_at',
            ]);

        return self::responseJSON($items, "Pantry items retrieved successfully", 200);
    }



    public function store(Request $request)
    {
      
        $request->validate([
            'household_id'  => 'required|integer',
            'ingredient_id' => 'required|integer',
            'quantity'      => 'required|integer|min:1',
            'unit'          => 'nullable|string',
            'expiry_date'   => 'nullable|date',
            'location'      => 'nullable|string'
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        // Check household
        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        // Check ingredient
        $ingredient = Ingredient::find($request->ingredient_id);
        if (!$ingredient) return self::responseJSON(null, "Ingredient not found", 404);

        $item = new PantryItem;
        $item->household_id  = $request->household_id;
        $item->ingredient_id = $request->ingredient_id;
        $item->quantity      = $request->quantity;
        $item->unit          = $request->unit ?? null;
        $item->expiry_date   = $request->expiry_date ?? null;
        $item->location      = $request->location ?? null;

        if ($item->save()) {
            return self::responseJSON($item, "Pantry item added successfully", 201);
        }

        return self::responseJSON(null, "Failed to add pantry item", 500);
    }


    public function update(Request $request, $id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $item = PantryItem::find($id);
        if (!$item) return self::responseJSON(null, "Pantry item not found", 404);

        if ($request->quantity)     $item->quantity = $request->quantity;
        if ($request->unit)         $item->unit = $request->unit;
        if ($request->expiry_date)  $item->expiry_date = $request->expiry_date;
        if ($request->location)     $item->location = $request->location;

        if ($item->save()) {
            return self::responseJSON($item, "Pantry item updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update pantry item", 500);
    }


    public function destroy($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $item = PantryItem::find($id);
        if (!$item) return self::responseJSON(null, "Pantry item not found", 404);

        if ($item->delete()) {
            return self::responseJSON(null, "Pantry item deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete pantry item", 500);
    }
}
