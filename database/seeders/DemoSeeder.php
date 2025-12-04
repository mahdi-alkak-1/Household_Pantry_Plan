<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\PantryItem;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Expense;

class DemoSeeder extends Seeder
{
    /**
     * Seed a full demo household with user + pantry + recipes + meal plan + shopping.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 1) USER
        // ---------------------------------------------------------------------
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('password'),   // login: demo@example.com / password
            ]
        );

        // ---------------------------------------------------------------------
        // 2) HOUSEHOLD
        // ---------------------------------------------------------------------
        $household = Household::firstOrCreate(
            ['name' => 'Demo Household'],
            ['invite_code' => 'DEMO1234']
        );

        // attach user to household as OWNER (pivot table household_user)
        $household->users()->syncWithoutDetaching([
            $user->id => ['role' => 'admin'],
        ]);

        // ---------------------------------------------------------------------
        // 3) INGREDIENTS
        // ---------------------------------------------------------------------
        $ingredientsData = [
            ['name' => 'Eggs',         'category' => 'Fridge'],
            ['name' => 'Milk',         'category' => 'Fridge'],
            ['name' => 'Bread',        'category' => 'Bakery'],
            ['name' => 'Chicken Breast','category' => 'Meat'],
            ['name' => 'Rice',         'category' => 'Dry'],
            ['name' => 'Tomato',       'category' => 'Vegetable'],
            ['name' => 'Lettuce',      'category' => 'Vegetable'],
            ['name' => 'Cheddar Cheese','category' => 'Fridge'],
            ['name' => 'Olive Oil',    'category' => 'Condiment'],
        ];

        $ingredients = [];

        foreach ($ingredientsData as $row) {
            $ingredients[$row['name']] = Ingredient::firstOrCreate(
                [
                    'household_id' => $household->id,
                    'name'         => $row['name'],
                ],
                [
                    'category'     => $row['category'],
                ]
            );
        }

        // ---------------------------------------------------------------------
        // 4) RECIPES (+ pivot ingredients)
        // ---------------------------------------------------------------------
        $omelette = Recipe::firstOrCreate(
            [
                'household_id' => $household->id,
                'title'        => 'Cheese Omelette',
            ],
            [
                'instructions' => 'Beat eggs, add cheese, cook in pan with oil.',
                'tags'         => ['breakfast', 'quick'],
            ]
        );

        $salad = Recipe::firstOrCreate(
            [
                'household_id' => $household->id,
                'title'        => 'Simple Salad',
            ],
            [
                'instructions' => 'Chop tomato and lettuce, mix with olive oil.',
                'tags'         => ['lunch', 'healthy'],
            ]
        );

        $chickenRice = Recipe::firstOrCreate(
            [
                'household_id' => $household->id,
                'title'        => 'Chicken & Rice',
            ],
            [
                'instructions' => 'Cook rice, grill chicken, combine with spices.',
                'tags'         => ['dinner'],
            ]
        );

        // Attach ingredients to recipes (pivot: quantity + unit)
        $omelette->ingredients()->sync([
            $ingredients['Eggs']->id          => ['quantity' => 2,  'unit' => 'pcs'],
            $ingredients['Cheddar Cheese']->id => ['quantity' => 30, 'unit' => 'g'],
            $ingredients['Olive Oil']->id     => ['quantity' => 1,  'unit' => 'tbsp'],
        ]);

        $salad->ingredients()->sync([
            $ingredients['Tomato']->id   => ['quantity' => 1,  'unit' => 'pc'],
            $ingredients['Lettuce']->id  => ['quantity' => 0.5,'unit' => 'head'],
            $ingredients['Olive Oil']->id=> ['quantity' => 1,  'unit' => 'tbsp'],
        ]);

        $chickenRice->ingredients()->sync([
            $ingredients['Chicken Breast']->id => ['quantity' => 1,  'unit' => 'piece'],
            $ingredients['Rice']->id           => ['quantity' => 100,'unit' => 'g'],
        ]);

        // ---------------------------------------------------------------------
        // 5) PANTRY ITEMS
        // ---------------------------------------------------------------------
        $today = Carbon::today();
        $soon  = $today->copy()->addDays(5);
        $later = $today->copy()->addDays(20);

        PantryItem::firstOrCreate(
            [
                'household_id'  => $household->id,
                'ingredient_id' => $ingredients['Eggs']->id,
            ],
            [
                'quantity'    => 6,
                'unit'        => 'pcs',
                'expiry_date' => $soon->toDateString(),
                'location'    => 'Fridge',
            ]
        );

        PantryItem::firstOrCreate(
            [
                'household_id'  => $household->id,
                'ingredient_id' => $ingredients['Milk']->id,
            ],
            [
                'quantity'    => 1,
                'unit'        => 'L',
                'expiry_date' => $soon->toDateString(),
                'location'    => 'Fridge',
            ]
        );

        PantryItem::firstOrCreate(
            [
                'household_id'  => $household->id,
                'ingredient_id' => $ingredients['Bread']->id,
            ],
            [
                'quantity'    => 4,
                'unit'        => 'slices',
                'expiry_date' => $soon->toDateString(),
                'location'    => 'Counter',
            ]
        );

        PantryItem::firstOrCreate(
            [
                'household_id'  => $household->id,
                'ingredient_id' => $ingredients['Rice']->id,
            ],
            [
                'quantity'    => 500,
                'unit'        => 'g',
                'expiry_date' => $later->toDateString(),
                'location'    => 'Pantry',
            ]
        );

        PantryItem::firstOrCreate(
            [
                'household_id'  => $household->id,
                'ingredient_id' => $ingredients['Chicken Breast']->id,
            ],
            [
                'quantity'    => 2,
                'unit'        => 'pieces',
                'expiry_date' => $soon->toDateString(),
                'location'    => 'Freezer',
            ]
        );

        // ---------------------------------------------------------------------
        // 6) SHOPPING LIST + ITEMS
        // ---------------------------------------------------------------------
        $shoppingList = ShoppingList::firstOrCreate(
            [
                'household_id' => $household->id,
                'name'         => 'Weekly Groceries',
            ]
        );

        ShoppingListItem::firstOrCreate(
            [
                'shopping_list_id' => $shoppingList->id,
                'name'             => 'Eggs',
            ],
            [
                'quantity' => 12,
                'unit'     => 'pcs',
                'bought'   => false,
            ]
        );

        ShoppingListItem::firstOrCreate(
            [
                'shopping_list_id' => $shoppingList->id,
                'name'             => 'Tomato',
            ],
            [
                'quantity' => 4,
                'unit'     => 'pcs',
                'bought'   => false,
            ]
        );

        ShoppingListItem::firstOrCreate(
            [
                'shopping_list_id' => $shoppingList->id,
                'name'             => 'Olive Oil',
            ],
            [
                'quantity' => 1,
                'unit'     => 'bottle',
                'bought'   => true,
            ]
        );

        // ---------------------------------------------------------------------
        // 7) MEAL PLAN + ITEMS
        // ---------------------------------------------------------------------
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $mealPlan = MealPlan::firstOrCreate(
            [
                'household_id'    => $household->id,
                'week_start_date' => $weekStart,
            ]
        );

        // a few example slots (your controller will autogenerate all 7x3 later)
        $monday = Carbon::parse($weekStart);
        $tuesday = $monday->copy()->addDay();
        $wednesday = $monday->copy()->addDays(2);

        MealPlanItem::firstOrCreate(
            [
                'meal_plan_id' => $mealPlan->id,
                'date'         => $monday->toDateString(),
                'slot'         => 'breakfast',
            ],
            [
                'recipe_id' => $omelette->id,
            ]
        );

        MealPlanItem::firstOrCreate(
            [
                'meal_plan_id' => $mealPlan->id,
                'date'         => $tuesday->toDateString(),
                'slot'         => 'lunch',
            ],
            [
                'recipe_id' => $salad->id,
            ]
        );

        MealPlanItem::firstOrCreate(
            [
                'meal_plan_id' => $mealPlan->id,
                'date'         => $wednesday->toDateString(),
                'slot'         => 'dinner',
            ],
            [
                'recipe_id' => $chickenRice->id,
            ]
        );

        // ---------------------------------------------------------------------
        // 8) EXPENSES
        // ---------------------------------------------------------------------
        Expense::firstOrCreate(
            [
                'household_id' => $household->id,
                'amount'       => 75.50,
                'category'     => 'Groceries',
                'store'        => 'Supermarket A',
                'date'         => $today->toDateString(),
            ],
            [
                'note'        => 'Weekly grocery shopping',
                'receipt_url' => null,
            ]
        );

        Expense::firstOrCreate(
            [
                'household_id' => $household->id,
                'amount'       => 15.00,
                'category'     => 'Bakery',
                'store'        => 'Local Bakery',
                'date'         => $today->copy()->subDay()->toDateString(),
            ],
            [
                'note'        => 'Bread & pastries',
                'receipt_url' => null,
            ]
        );
    }
}
