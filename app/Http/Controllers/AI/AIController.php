<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Household;
use App\Models\PantryItem;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Expense;
use App\Services\OpenAIService;

class AIController extends Controller
{
    /**
     * POST /api/ai/ask
     * body: { "household_id": 1, "question": "..." }
     */
    public function householdAssistant(Request $request)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $data = $request->validate([
            'household_id' => 'required|integer',
            'question'     => 'required|string',
        ]);

        $household = Household::find($data['household_id']);
        if (!$household) {
            return self::responseJSON(null, "Household not found", 404);
        }

        // ---------------- PANTRY: ALL ITEMS ----------------
        // We intentionally DO NOT filter by date here.
        // We send every pantry row so the model can detect the earliest expiry itself.
        $pantryItems = PantryItem::with('ingredient')
            ->where('household_id', $household->id)
            // Put items with a real expiry_date first, sorted by soonest date
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->get()
            ->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'ingredient_id' => $p->ingredient_id,
                    'name'          => optional($p->ingredient)->name,
                    'quantity'      => $p->quantity,
                    'unit'          => $p->unit,
                    'location'      => $p->location,
                    'expiry_date'   => $p->expiry_date, // 'YYYY-MM-DD' or null
                ];
            })
            ->values()
            ->all();

        // ---------------- SHOPPING LISTS + ITEMS -----------
        $shoppingLists = ShoppingList::with('items')
            ->where('household_id', $household->id)
            ->get()
            ->map(function ($list) {
                return [
                    'id'    => $list->id,
                    'name'  => $list->name,
                    'items' => $list->items->map(function ($it) {
                        return [
                            'id'       => $it->id,
                            'name'     => $it->name,
                            'quantity' => $it->quantity,
                            'unit'     => $it->unit,
                            'bought'   => (bool)$it->bought,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        // ---------------- EXPENSES (all; you can limit by date if needed) ----
        $expenses = Expense::where('household_id', $household->id)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($e) {
                return [
                    'id'       => $e->id,
                    'amount'   => $e->amount,
                    'category' => $e->category,
                    'store'    => $e->store,
                    'note'     => $e->note,
                    'date'     => $e->date, // Y-m-d
                ];
            })
            ->values()
            ->all();

        // ---------------- BUILD CONTEXT OBJECT -------------
        $context = [
            'pantry_items'   => $pantryItems,
            'shopping_lists' => $shoppingLists,
            'expenses'       => $expenses,
        ];

        // ---------------- CALL OPENAI ----------------------
        $answer = OpenAIService::householdAssistant($data['question'], $context);

        if ($answer === null) {
            return self::responseJSON(
                ['answer' => "Sorry, I couldn't generate an answer right now."],
                "AI error",
                500
            );
        }

        return self::responseJSON(
            ['answer' => $answer],
            "AI answer",
            200
        );
    }
}
