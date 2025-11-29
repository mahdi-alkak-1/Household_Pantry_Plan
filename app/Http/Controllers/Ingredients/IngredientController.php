<?php

namespace App\Http\Controllers\Ingredients;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IngredientController extends Controller
{

    /**
     * GET: list ingredients of a household
     */
    public function index($household_id)
    {
        $user_id = Auth::id();

        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $household = Household::find($household_id);

        if (!$household) {
            return self::responseJSON(null, "Household not found", 404);
        }

        $ingredients = Ingredient::where('household_id', $household_id)->get();

        return self::responseJSON($ingredients, "Ingredients retrieved successfully", 200);
    }


    /**
     * POST: create ingredient
     */
    public function store(Request $request)
    {
        $request->validate([
            'household_id' => 'required|integer',
            'name'         => 'required|string|max:255',
            'category'     => 'nullable|string|max:255',
        ]);

        $user_id = Auth::id();

        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        // Check household exists
        $household = Household::find($request->household_id);
        if (!$household) {
            return self::responseJSON(null, "Household not found", 404);
        }

        $ingredient = new Ingredient;
        $ingredient->name         = $request->name;
        $ingredient->category     = $request->category ?? null;
        $ingredient->household_id = $request->household_id;

        if ($ingredient->save()) {
            return self::responseJSON($ingredient, "Ingredient created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create ingredient", 500);
    }


    /**
     * PUT: update ingredient
     */
    public function update(Request $request, $id)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return self::responseJSON(null, "Ingredient not found", 404);
        }

        if ($request->name)     $ingredient->name     = $request->name;
        if ($request->category) $ingredient->category = $request->category;

        if ($ingredient->save()) {
            return self::responseJSON($ingredient, "Ingredient updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update ingredient", 500);
    }


    /**
     * DELETE: delete ingredient
     */
    public function destroy($id)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return self::responseJSON(null, "Ingredient not found", 404);
        }

        if ($ingredient->delete()) {
            return self::responseJSON(null, "Ingredient deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete ingredient", 500);
    }
}
