<?php

namespace App\Http\Controllers\Recipes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Household;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{


    /**
     * GET: List recipes for a household
     */
    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $recipes = Recipe::where('household_id', $household_id)->get();

        return self::responseJSON($recipes, "Recipes retrieved successfully", 200);
    }


    /**
     * POST: Create a new recipe
     */
    public function store(Request $request)
    {
        $request->validate([
            'household_id' => 'required|integer',
            'title'        => 'required|string|max:255',
            'instructions' => 'required|string',
            'tags'         => 'nullable|array'
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $recipe = new Recipe;
        $recipe->household_id = $request->household_id;
        $recipe->title        = $request->title;
        $recipe->instructions = $request->instructions;
        $recipe->tags         = $request->tags;

        if ($recipe->save()) {
            return self::responseJSON($recipe, "Recipe created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create recipe", 500);
    }


    /**
     * GET: Show a single recipe WITH its ingredients + pivot (q, unit)
     */
    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $recipe = Recipe::with(['ingredients'])->find($id);

        if (!$recipe) {
            return self::responseJSON(null, "Recipe not found", 404);
        }

        return self::responseJSON($recipe, "Recipe retrieved successfully", 200);
    }


    /**
     * POST: Update a recipe
     */
    public function update(Request $request, $id)
    {
        $recipe = Recipe::find($id);
        if (!$recipe) return self::responseJSON(null, "Recipe not found", 404);

        $request->validate([
            'title'        => 'sometimes|string|max:255',
            'instructions' => 'sometimes|string',
            'tags'         => 'sometimes|array'
        ]);

        if ($request->has('title'))        $recipe->title = $request->title;
        if ($request->has('instructions')) $recipe->instructions = $request->instructions;
        if ($request->has('tags'))         $recipe->tags = $request->tags;

        if ($recipe->save()) {
            return self::responseJSON($recipe, "Recipe updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update recipe", 500);
    }


    /**
     * DELETE: Remove a recipe
     */
    public function destroy($id)
    {
        $recipe = Recipe::find($id);
        if (!$recipe) return self::responseJSON(null, "Recipe not found", 404);

        if ($recipe->delete()) {
            return self::responseJSON(null, "Recipe deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete recipe", 500);
    }
}
