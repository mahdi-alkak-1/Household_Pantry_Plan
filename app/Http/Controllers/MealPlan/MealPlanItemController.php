<?php

namespace App\Http\Controllers\MealPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class MealPlanItemController extends Controller
{
    use ResponseTrait;


    /**
     * GET: List all items for a meal plan
     */
    public function index($meal_plan_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $mealPlan = MealPlan::find($meal_plan_id);
        if (!$mealPlan) return self::responseJSON(null, "Meal plan not found", 404);

        $items = MealPlanItem::where('meal_plan_id', $meal_plan_id)->get();

        return self::responseJSON($items, "Meal plan items retrieved successfully", 200);
    }


    /**
     * POST: Create a new meal plan item
     */
    public function store(Request $request)
    {
        $request->validate([
            'meal_plan_id' => 'required|integer',
            'date'         => 'required|date',
            'slot'         => 'required|in:breakfast,lunch,dinner',
            'recipe_id'    => 'nullable|integer'
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        // Check meal plan exists
        $mealPlan = MealPlan::find($request->meal_plan_id);
        if (!$mealPlan) return self::responseJSON(null, "Meal plan not found", 404);

        // If recipe provided, validate it
        if ($request->recipe_id) {
            $recipe = Recipe::find($request->recipe_id);
            if (!$recipe) {
                return self::responseJSON(null, "Recipe not found", 404);
            }
        }

        $item = new MealPlanItem;
        $item->meal_plan_id = $request->meal_plan_id;
        $item->date         = $request->date;
        $item->slot         = $request->slot;
        $item->recipe_id    = $request->recipe_id ?? null;

        if ($item->save()) {
            return self::responseJSON($item, "Meal plan item created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create meal plan item", 500);
    }


    /**
     * GET: Show specific meal plan item
     */
    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $item = MealPlanItem::with('recipe')->find($id);

        if (!$item) return self::responseJSON(null, "Meal plan item not found", 404);

        return self::responseJSON($item, "Meal plan item retrieved successfully", 200);
    }


    /**
     * POST: Update meal plan item (date, slot, recipe)
     */
    public function update(Request $request, $id)
    {
        $item = MealPlanItem::find($id);
        if (!$item) return self::responseJSON(null, "Meal plan item not found", 404);

        $request->validate([
            'date'      => 'sometimes|date',
            'slot'      => 'sometimes|in:breakfast,lunch,dinner',
            'recipe_id' => 'nullable|integer'
        ]);

        if ($request->has('date')) $item->date = $request->date;
        if ($request->has('slot')) $item->slot = $request->slot;

        // Validate recipe if provided
        if ($request->has('recipe_id')) {
            if ($request->recipe_id === null) {
                $item->recipe_id = null;
            } else {
                $recipe = Recipe::find($request->recipe_id);
                if (!$recipe) {
                    return self::responseJSON(null, "Recipe not found", 404);
                }
                $item->recipe_id = $request->recipe_id;
            }
        }

        if ($item->save()) {
            return self::responseJSON($item, "Meal plan item updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update meal plan item", 500);
    }


    /**
     * DELETE: Remove item
     */
    public function destroy($id)
    {
        $item = MealPlanItem::find($id);
        if (!$item) return self::responseJSON(null, "Meal plan item not found", 404);

        if ($item->delete()) {
            return self::responseJSON(null, "Meal plan item deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete meal plan item", 500);
    }
}
