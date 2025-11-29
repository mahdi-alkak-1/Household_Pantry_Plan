<?php

namespace App\Http\Controllers\Shopping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShoppingList;
use App\Models\Household;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class ShoppingListController extends Controller
{
    use ResponseTrait;

    /**
     * GET: List all shopping lists for a household
     */
    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $lists = ShoppingList::where('household_id', $household_id)->get();

        return self::responseJSON($lists, "Shopping lists retrieved successfully", 200);
    }


    /**
     * POST: Create a shopping list
     * (No need for: name, description, date)
     */
    public function store(Request $request)
    {
        $request->validate([
            'household_id' => 'required|integer',
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $list = new ShoppingList;
        $list->household_id = $request->household_id;

        if ($list->save()) {
            return self::responseJSON($list, "Shopping list created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create shopping list", 500);
    }


    /**
     * GET: Show a shopping list WITH items
     */
    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $list = ShoppingList::with('items')->find($id);

        if (!$list) {
            return self::responseJSON(null, "Shopping list not found", 404);
        }

        return self::responseJSON($list, "Shopping list retrieved successfully", 200);
    }


    /**
     * DELETE: Remove shopping list
     */
    public function destroy($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $list = ShoppingList::find($id);
        if (!$list) return self::responseJSON(null, "Shopping list not found", 404);

        if ($list->delete()) {
            return self::responseJSON(null, "Shopping list deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete shopping list", 500);
    }
}
