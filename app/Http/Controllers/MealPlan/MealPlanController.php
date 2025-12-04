<?php

namespace App\Http\Controllers\MealPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Household;
use App\Models\Recipe;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MealPlanController extends Controller
{

    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $mealPlans = MealPlan::where('household_id', $household_id)->get();

        return self::responseJSON($mealPlans, "Meal plans retrieved successfully", 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'household_id'    => 'required|integer',
            'week_start_date' => 'required|date',   // e.g. "2025-02-10"
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        // prevent duplicate week plans for same household
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

    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $mealPlan = MealPlan::find($id);
        if (!$mealPlan) {
            return self::responseJSON(null, "Meal plan not found", 404);
        }

        //create slots for this week
        $start = Carbon::parse($mealPlan->week_start_date)->startOfDay();
        $slots = ['breakfast', 'lunch', 'dinner'];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();

            foreach ($slots as $slot) {
                MealPlanItem::firstOrCreate([
                    'meal_plan_id' => $mealPlan->id,
                    'date'         => $date,
                    'slot'         => $slot,
                ]);
            }
        }

        // load items + recipes
        $mealPlan->load('items.recipe');

        return self::responseJSON($mealPlan, "Meal plan retrieved successfully", 200);
    }

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
