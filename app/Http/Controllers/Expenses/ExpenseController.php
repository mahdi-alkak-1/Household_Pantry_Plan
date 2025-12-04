<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Household;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{

    //list all exp
    public function index($household_id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $expenses = Expense::where('household_id', $household_id)->orderBy('date', 'desc')->get();

        return self::responseJSON($expenses, "Expenses retrieved successfully", 200);
    }


    //Create expense
    public function store(Request $request)
    {
        $request->validate([
            'household_id' => 'required|integer',
            'amount'       => 'required|numeric',
            'category'     => 'required|string|max:255',
            'store'        => 'nullable|string|max:255',
            'note'         => 'nullable|string',
            'receipt_url'  => 'nullable|string',
            'date'         => 'required|date'
        ]);

        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $household = Household::find($request->household_id);
        if (!$household) return self::responseJSON(null, "Household not found", 404);

        $expense = new Expense;
        $expense->household_id = $request->household_id;
        $expense->amount       = $request->amount;
        $expense->category     = $request->category;
        $expense->store        = $request->store ?? null;
        $expense->note         = $request->note ?? null;
        $expense->receipt_url  = $request->receipt_url ?? null;
        $expense->date         = $request->date;

        if ($expense->save()) {
            return self::responseJSON($expense, "Expense created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create expense", 500);
    }


    //Show specific expense
    public function show($id)
    {
        $user_id = Auth::id();
        if (!$user_id) return self::responseJSON(null, "unauthorized", 401);

        $expense = Expense::find($id);
        if (!$expense) return self::responseJSON(null, "Expense not found", 404);

        return self::responseJSON($expense, "Expense retrieved successfully", 200);
    }


    //Update expense
    public function update(Request $request, $id)
    {
        $expense = Expense::find($id);
        if (!$expense) return self::responseJSON(null, "Expense not found", 404);

        $request->validate([
            'amount'      => 'sometimes|numeric',
            'category'    => 'sometimes|string|max:255',
            'store'       => 'sometimes|string|nullable|max:255',
            'note'        => 'sometimes|string|nullable',
            'receipt_url' => 'sometimes|string|nullable',
            'date'        => 'sometimes|date',
        ]);

        if ($request->has('amount'))      $expense->amount = $request->amount;
        if ($request->has('category'))    $expense->category = $request->category;
        if ($request->has('store'))       $expense->store = $request->store;
        if ($request->has('note'))        $expense->note = $request->note;
        if ($request->has('receipt_url')) $expense->receipt_url = $request->receipt_url;
        if ($request->has('date'))        $expense->date = $request->date;

        if ($expense->save()) {
            return self::responseJSON($expense, "Expense updated successfully", 200);
        }

        return self::responseJSON(null, "Failed to update expense", 500);
    }


    public function destroy($id)
    {
        $expense = Expense::find($id);
        if (!$expense) return self::responseJSON(null, "Expense not found", 404);

        if ($expense->delete()) {
            return self::responseJSON(null, "Expense deleted successfully", 200);
        }

        return self::responseJSON(null, "Failed to delete expense", 500);
    }
}
