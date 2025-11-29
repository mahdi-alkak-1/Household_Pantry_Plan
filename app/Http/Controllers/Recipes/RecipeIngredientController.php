<?php

namespace App\Http\Controllers\Recipes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Auth;

class RecipeIngredientController extends Controller
{
    /**
     * GET: List all ingredients for a recipe (with pivot fields)
     */
    public function index($recipe_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $recipe = Recipe::with('ingredients')->find($recipe_id);
        if (!$recipe) return self::responseJSON(null, "Recipe not found", 404);

        return self::responseJSON($recipe->ingredients, "Ingredients retrieved successfully", 200);
    }


    /**
     * POST: Add ingredient to recipe
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipe_id'     => 'required|integer',
            'ingredient_id' => 'required|integer',
            'quantity'      => 'nullable|numeric',
            'unit'          => 'nullable|string|max:50',
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $recipe = Recipe::find($request->recipe_id);
        if (!$recipe) return self::responseJSON(null, "Recipe not found", 404);

        $ingredient = Ingredient::find($request->ingredient_id);
        if (!$ingredient) return self::responseJSON(null, "Ingredient not found", 404);

        // Check if ingredient already attached
        if ($recipe->ingredients()->where('ingredient_id', $request->ingredient_id)->exists()) {
            return self::responseJSON(null, "Ingredient already added to recipe", 409);
        }

        // Attach with pivot data
        $recipe->ingredients()->attach($request->ingredient_id, [
            'quantity' => $request->quantity,
            'unit'     => $request->unit,
        ]);

        return self::responseJSON(null, "Ingredient added successfully", 201);
    }


    /**
     * POST: Update quantity/unit for an ingredient in a recipe
     */
    public function update(Request $request, $recipe_id, $ingredient_id)
    {
        $request->validate([
            'quantity' => 'nullable|numeric',
            'unit'     => 'nullable|string|max:50',
        ]);

        $recipe = Recipe::find($recipe_id);
        if (!$recipe) return self::responseJSON(null, "Recipe not found", 404);

        if (!$recipe->ingredients()->where('ingredient_id', $ingredient_id)->exists()) {
            return self::responseJSON(null, "Ingredient not found in recipe", 404);
        }

        // Update pivot table values
        $recipe->ingredients()->updateExistingPivot($ingredient_id, [
            'quantity' => $request->quantity,
            'unit'     => $request->unit,
        ]);

        return self::responseJSON(null, "Ingredient updated successfully", 200);
    }


    /**
     * DELETE / POST: Remove ingredient from recipe
     */
    public function destroy($recipe_id, $ingredient_id)
    {
        $recipe = Recipe::find($recipe_id);
        if (!$recipe) return self::responseJSON(null, "Recipe not found", 404);

        if (!$recipe->ingredients()->where('ingredient_id', $ingredient_id)->exists()) {
            return self::responseJSON(null, "Ingredient not found in recipe", 404);
        }

        $recipe->ingredients()->detach($ingredient_id);

        return self::responseJSON(null, "Ingredient removed successfully", 200);
    }
}
