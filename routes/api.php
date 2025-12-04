<?php

use App\Http\Controllers\AI\AIController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Household\HouseholdController;
use App\Http\Controllers\Ingredients\IngredientController;
use App\Http\Controllers\MealPlan\MealPlanController;
use App\Http\Controllers\MealPlan\MealPlanItemController;
use App\Http\Controllers\Pantry\PantryController;
use App\Http\Controllers\Recipes\RecipeController;
use App\Http\Controllers\Recipes\RecipeIngredientController;
use App\Http\Controllers\Shopping\ShoppingListController;
use App\Http\Controllers\Shopping\ShoppingListItemController;




Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});


Route::middleware(['auth:api'])->group(function () {

    // AUTH
    Route::get('/auth/user',   [AuthController::class, 'user']);
    Route::post('/auth/logout',[AuthController::class, 'logout']);


    // HOUSEHOLDS
    Route::prefix('households')->group(function () {
        Route::get('/',[HouseholdController::class, 'index']);
        Route::post('/create',[HouseholdController::class, 'store']);
        Route::post('/join',[HouseholdController::class, 'join']);
        Route::get('/show/{id}',[HouseholdController::class, 'show']);
    });


    // PANTRY ITEMS
    Route::prefix('pantry')->group(function () {
        Route::get('/{household_id}',          [PantryController::class, 'index']);
        Route::post('/create',                 [PantryController::class, 'store']);
        Route::get('/item/{id}',               [PantryController::class, 'show']);
        Route::post('/update/{id}',            [PantryController::class, 'update']);
        Route::post('/delete/{id}',            [PantryController::class, 'destroy']);
    });


    // RECIPES
    Route::prefix('recipes')->group(function () {
        Route::get('/{household_id}',          [RecipeController::class, 'index']);
        Route::post('/create',                 [RecipeController::class, 'store']);
        Route::get('/show/{id}',               [RecipeController::class, 'show']);
        Route::post('/update/{id}',            [RecipeController::class, 'update']);
        Route::post('/delete/{id}',            [RecipeController::class, 'destroy']);
    });

    // RECIPE INGREDIENTS (pivot)
    Route::prefix('recipe-ingredients')->group(function () {
        Route::post('/attach/{recipeId}',      [RecipeIngredientController::class, 'store']);
        Route::post('/update/{recipeId}/{ingredientId}', [RecipeIngredientController::class, 'update']);
        Route::post('/detach/{recipeId}/{ingredientId}', [RecipeIngredientController::class, 'destroy']);
    });


    // INGREDIENTS
    Route::prefix('ingredients')->group(function () {
        Route::get('/{household_id}',          [IngredientController::class, 'index']);
        Route::post('/create',                 [IngredientController::class, 'store']);
        Route::post('/update/{id}',            [IngredientController::class, 'update']);
        Route::post('/delete/{id}',            [IngredientController::class, 'destroy']);
    });


    // MEAL PLANS
    Route::prefix('meal-plans')->group(function () {
        Route::get('/{household_id}',          [MealPlanController::class, 'index']);
        Route::post('/create',                 [MealPlanController::class, 'store']);
        Route::get('/show/{id}',               [MealPlanController::class, 'show']);
        Route::post('/update/{id}',            [MealPlanController::class, 'update']);   // if you’ll use it
        Route::post('/delete/{id}',            [MealPlanController::class, 'destroy']); 
    });

    // MEAL PLAN ITEMS
    Route::prefix('meal-plan-items')->group(function () {
        Route::get('/{meal_plan_id}',          [MealPlanItemController::class, 'index']);
        Route::post('/create',                 [MealPlanItemController::class, 'store']);
        Route::get('/show/{id}',               [MealPlanItemController::class, 'show']);
        Route::post('/update/{id}',            [MealPlanItemController::class, 'update']);
        Route::post('/delete/{id}',            [MealPlanItemController::class, 'destroy']);
    });


    // SHOPPING LISTS
    Route::prefix('shopping-lists')->group(function () {
        Route::get('/{household_id}',          [ShoppingListController::class, 'index']);
        Route::post('/create',                 [ShoppingListController::class, 'store']);
        Route::get('/show/{id}',               [ShoppingListController::class, 'show']);
        Route::post('/update/{id}',               [ShoppingListController::class, 'update']);   
        Route::post('/checkout-bought/{id}',               [ShoppingListController::class, 'checkoutBought']);   
    
        Route::post('/from-meal-plan', [ShoppingListController::class,'autoFromMealPlan']);
    });

    // SHOPPING LIST ITEMS
    Route::prefix('shopping-list-items')->group(function () {
        Route::post('/create',                 [ShoppingListItemController::class, 'store']);
        Route::post('/update/{id}',            [ShoppingListItemController::class, 'update']);
        Route::post('/delete/{id}',            [ShoppingListItemController::class, 'destroy']);
        Route::post('/toggle/{id}',            [ShoppingListItemController::class, 'toggleBought']);
    });


    // EXPENSES
    Route::prefix('expenses')->group(function () {
        Route::get('/{household_id}',          [ExpenseController::class, 'index']);
        Route::post('/create',                 [ExpenseController::class, 'store']);
        Route::post('/update/{id}',            [ExpenseController::class, 'update']);
        Route::post('/delete/{id}',            [ExpenseController::class, 'destroy']);
    });

    Route::post('/ai/assistant', [AIController::class, 'householdAssistant']);
});
