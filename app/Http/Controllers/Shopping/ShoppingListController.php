<?php

namespace App\Http\Controllers\Shopping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class ShoppingListController extends Controller
{
    use ResponseTrait;

    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $lists = ShoppingList::where('household_id', $household_id)->get();

        return self::responseJSON($lists, "Shopping lists retrieved successfully", 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'household_id' => 'required|integer',
            'name'         => 'required|string',
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $list = new ShoppingList;
        $list->name         = $request->name;
        $list->household_id = $request->household_id;

        if ($list->save()) {
            return self::responseJSON($list, "Shopping list created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create shopping list", 500);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'household_id' => 'required|integer',
            'name'         => 'required|string',
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $list = ShoppingList::find($id);
        if (!$list) {
            return self::responseJSON(null, "shopping list not found", 404);
        }

        $list->name = $request->name;

        if ($list->save()) {
            return self::responseJSON($list, "Shopping list updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update shopping list", 500);
    }

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

    /**
     * Auto-generate / update a shopping list based on the latest MealPlan
     * for the given household.
     *
     * Request JSON:
     *  - household_id     (required)
     *  - shopping_list_id (optional, if not given we create a new list)
     */
    public function autoFromMealPlan(Request $request)
    {
        $request->validate([
            'household_id'     => 'required|integer',
            'shopping_list_id' => 'nullable|integer',
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) {
            return self::responseJSON(null, "Household not found", 404);
        }

        // 1) Latest meal plan with recipes + ingredients
        $mealPlan = MealPlan::with(['items.recipe.ingredients'])
            ->where('household_id', $request->household_id)
            ->orderBy('week_start_date', 'desc')
            ->first();

        if (!$mealPlan) {
            return self::responseJSON(null, "No meal plan found for this household", 404);
        }

        // 2) Total required per ingredient
        $required = []; // [ingredient_id => ['ingredient' => Ingredient, 'qty' => float, 'unit' => string|null]]

        foreach ($mealPlan->items as $item) {
            if (!$item->recipe) continue;

            foreach ($item->recipe->ingredients as $ingredient) {
                $id   = $ingredient->id;
                $qty  = $ingredient->pivot->quantity ?? 1;
                $unit = $ingredient->pivot->unit ?? null;

                if (!isset($required[$id])) {
                    $required[$id] = [
                        'ingredient' => $ingredient,
                        'qty'        => 0.0,
                        'unit'       => $unit,
                    ];
                }

                $required[$id]['qty'] += (float) $qty;
            }
        }

        if (empty($required)) {
            return self::responseJSON(null, "Meal plan has no recipes/ingredients", 200);
        }

        // 3) Pantry stock for this household
        $pantry   = PantryItem::where('household_id', $request->household_id)->get();
        $inPantry = []; // [ingredient_id => totalQty]

        foreach ($pantry as $p) {
            $id  = $p->ingredient_id;
            $qty = $p->quantity ?? 0;

            if (!isset($inPantry[$id])) {
                $inPantry[$id] = 0.0;
            }
            $inPantry[$id] += (float) $qty;
        }

        // 4) Deficits (missing > 0)
        $deficits = []; // list of ['ingredient' => Ingredient, 'needed' => float, 'unit' => ?]

        foreach ($required as $id => $data) {
            $need    = $data['qty'];
            $have    = $inPantry[$id] ?? 0.0;
            $missing = $need - $have;

            // small tolerance
            if ($missing > 0.01) {
                $deficits[] = [
                    'ingredient' => $data['ingredient'],
                    'needed'     => $missing,
                    'unit'       => $data['unit'],
                ];
            }
        }

        if (empty($deficits)) {
            return self::responseJSON(null, "Pantry already covers this meal plan", 200);
        }

        // 5) Target shopping list
        if ($request->shopping_list_id) {
            $list = ShoppingList::where('household_id', $request->household_id)
                ->find($request->shopping_list_id);

            if (!$list) {
                return self::responseJSON(null, "Target shopping list not found", 404);
            }
        } else {
            // Create a new list named with the meal-plan week
            $list = new ShoppingList;
            $list->household_id = $request->household_id;
            $list->name         = "Meal plan " . $mealPlan->week_start_date;
            $list->save();
        }

        // 6) Insert / UPDATE items (idempotent: sets quantity to needed, doesn't keep adding)
        $affected = [];

        foreach ($deficits as $row) {
            $ingredient = $row['ingredient'];
            $needed     = $row['needed'];
            $unit       = $row['unit'];

            $item = ShoppingListItem::where('shopping_list_id', $list->id)
                ->where('name', $ingredient->name)
                ->where('unit', $unit)
                ->first();

            if ($item) {
                // IMPORTANT FIX: overwrite quantity with current deficit
                $item->quantity = $needed;
                $item->bought   = false;
                $item->source   = 'mealplan';
                $item->save();
            } else {
                $item = ShoppingListItem::create([
                    'shopping_list_id' => $list->id,
                    'name'             => $ingredient->name,
                    'quantity'         => $needed,
                    'unit'             => $unit,
                    'bought'           => false,
                ]);
            }

            $affected[] = $item;
        }

        return self::responseJSON(
            [
                'shopping_list' => $list,
                'items'         => $affected,
            ],
            "Shopping list updated from meal plan",
            200
        );
    }

    public function checkoutBought(Request $request, $id)
    {
        // Validate optional expiry/location for this checkout batch
        $request->validate([
            'expiry_date' => 'nullable|date',
            'location'    => 'nullable|string|max:255',
        ]);

        $user_id = Auth::id();
        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $list = ShoppingList::with('items')->find($id);
        if (!$list) {
            return self::responseJSON(null, "Shopping list not found", 404);
        }

        $household_id = $list->household_id;

        // All items marked as bought
        $boughtItems = $list->items()->where('bought', true)->get();
        if ($boughtItems->isEmpty()) {
            return self::responseJSON(null, "No bought items to move", 200);
        }

        // Read expiry + location from request (same for all items in this checkout)
        $expiryDate = $request->input('expiry_date'); // can be null
        $location   = $request->input('location');    // can be null

        // Ingredients of this household, keyed by lowercase name
        $ingredients = Ingredient::where('household_id', $household_id)->get();
        $ingredientByName = [];
        foreach ($ingredients as $ing) {
            $ingredientByName[mb_strtolower(trim($ing->name))] = $ing;
        }

        $added   = 0;
        $skipped = [];

        foreach ($boughtItems as $item) {
            $nameKey = mb_strtolower(trim($item->name ?? ''));
            if ($nameKey === '') {
                $skipped[] = $item->name;
                $item->delete();
                continue;
            }

            $ingredient = $ingredientByName[$nameKey] ?? null;
            if (!$ingredient) {
                // no matching ingredient – skip adding to pantry
                $skipped[] = $item->name;
                $item->delete();
                continue;
            }

            $qty  = $item->quantity ?? 1;
            $unit = $item->unit;

            // ✅ ALWAYS CREATE A NEW PANTRY ROW
            // so different shopping trips / expiry dates don't get merged
            PantryItem::create([
                'household_id'  => $household_id,
                'ingredient_id' => $ingredient->id,
                'quantity'      => $qty,
                'unit'          => $unit,
                'expiry_date'   => $expiryDate,
                'location'      => $location,
            ]);

            $added++;
            // Remove from shopping list
            $item->delete();
        }

        return self::responseJSON(
            [
                'moved_to_pantry' => $added,
                'skipped_names'   => $skipped,
            ],
            "Bought items moved to pantry and removed from shopping list",
            200
        );
    }
}
