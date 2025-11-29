<?php

namespace App\Http\Controllers\MealPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MealPlan;
use App\Models\Household;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class MealPlanController extends Controller
{
    use ResponseTrait;


    /**
     * GET: List all meal plans for a household
     */
    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $mealPlans = MealPlan::where('household_id', $household_id)->get();

        return self::responseJSON($mealPlans, "Meal plans retrieved successfully", 200);
    }


    /**
     * POST: Create a new meal plan (1 per week)
     */
    public function store(Request $request)
    {
        $request->validate([
            'household_id'    => 'required|integer',
            'week_start_date' => 'required|date',   // e.g. "2025-02-10"
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        // Check household exists
        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        // OPTIONAL: prevent duplicate week plans for same household
        $exists = MealPlan::where('household_id', $request->household_id)
                           ->where('week_start_date', $request->week_start_date)
                           ->exists();

        if ($exists) {
            return self::responseJSON(null, "Meal plan for this week already exists", 409);
        }

        $mealPlan = new MealPlan;
        $mealPlan->household_id    = $request->household_id;
        $mealPlan->week_start_date = $request->week_start_date;

        if ($mealPlan->save()) {
            return self::responseJSON($mealPlan, "Meal plan created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create meal plan", 500);
    }


    /**
     * GET: Show a meal plan WITH items
     */
    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $mealPlan = MealPlan::with('items')->find($id);

        if (!$mealPlan) {
            return self::responseJSON(null, "Meal plan not found", 404);
        }

        return self::responseJSON($mealPlan, "Meal plan retrieved successfully", 200);
    }


    /**
     * POST: Update meal plan (usually only the week_start_date)
     */
    public function update(Request $request, $id)
    {
        $mealPlan = MealPlan::find($id);
        if (!$mealPlan) return self::responseJSON(null, "Meal plan not found", 404);

        $request->validate([
            'week_start_date' => 'sometimes|date',
        ]);

        if ($request->has('week_start_date')) {
            $mealPlan->week_start_date = $request->week_start_date;
        }

        if ($mealPlan->save()) {
            return self::responseJSON($mealPlan, "Meal plan updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update meal plan", 500);
    }


    /**
     * DELETE: Delete a meal plan
     */
    public function destroy($id)
    {
        $mealPlan = MealPlan::find($id);
        if (!$mealPlan) return self::responseJSON(null, "Meal plan not found", 404);

        if ($mealPlan->delete()) {
            return self::responseJSON(null, "Meal plan deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete meal plan", 500);
    }
}
