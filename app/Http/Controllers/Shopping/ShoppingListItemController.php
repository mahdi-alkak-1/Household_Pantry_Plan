<?php

namespace App\Http\Controllers\Shopping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class ShoppingListItemController extends Controller
{
    use ResponseTrait;

    /**
     * GET: List all items for a shopping list
     */
    public function index($shopping_list_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $list = ShoppingList::find($shopping_list_id);
        if (!$list) return self::responseJSON(null, "Shopping list not found", 404);

        $items = ShoppingListItem::where('shopping_list_id', $shopping_list_id)->get();

        return self::responseJSON($items, "Items retrieved successfully", 200);
    }


    /**
     * POST: Create a new shopping list item
     */
    public function store(Request $request)
    {
        $request->validate([
            'shopping_list_id' => 'required|integer',
            'name'             => 'required|string|max:255',
            'quantity'         => 'nullable|numeric',
            'unit'             => 'nullable|string|max:50'
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $list = ShoppingList::find($request->shopping_list_id);
        if (!$list) return self::responseJSON(null, "Shopping list not found", 404);

        $item = new ShoppingListItem;
        $item->shopping_list_id = $request->shopping_list_id;
        $item->name = $request->name;
        $item->quantity = $request->quantity;
        $item->unit = $request->unit;
        $item->bought = false;

        if ($item->save()) {
            return self::responseJSON($item, "Item added successfully", 201);
        }

        return self::responseJSON(null, "Failed to add item", 500);
    }


    /**
     * GET: Show single item
     */
    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $item = ShoppingListItem::find($id);
        if (!$item) return self::responseJSON(null, "Item not found", 404);

        return self::responseJSON($item, "Item retrieved successfully", 200);
    }


    /**
     * POST: Update an item
     */
    public function update(Request $request, $id)
    {
        $item = ShoppingListItem::find($id);
        if (!$item) return self::responseJSON(null, "Item not found", 404);

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'quantity' => 'sometimes|numeric|nullable',
            'unit'     => 'sometimes|string|max:50|nullable',
            'bought'   => 'sometimes|boolean'
        ]);

        if ($request->has('name'))     $item->name = $request->name;
        if ($request->has('quantity')) $item->quantity = $request->quantity;
        if ($request->has('unit'))     $item->unit = $request->unit;
        if ($request->has('bought'))   $item->bought = $request->bought;

        if ($item->save()) {
            return self::responseJSON($item, "Item updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update item", 500);
    }


    /**
     * POST: Toggle bought/not bought
     */
    public function toggleBought($id)
    {
        $item = ShoppingListItem::find($id);
        if (!$item) return self::responseJSON(null, "Item not found", 404);

        $item->bought = !$item->bought;

        if ($item->save()) {
            return self::responseJSON($item, "Item status updated", 200);
        }

        return self::responseJSON(null, "Failed to update status", 500);
    }


    /**
     * DELETE: Remove item
     */
    public function destroy($id)
    {
        $item = ShoppingListItem::find($id);
        if (!$item) return self::responseJSON(null, "Item not found", 404);

        if ($item->delete()) {
            return self::responseJSON(null, "Item deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete item", 500);
    }
}
