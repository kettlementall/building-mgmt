<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        return response()->json(ExpenseCategory::withCount('expenses')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|unique:expense_categories,name',
            'color' => 'nullable|string',
        ]);

        return response()->json(ExpenseCategory::create($data), 201);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validate([
            'name'  => 'sometimes|string|unique:expense_categories,name,' . $expenseCategory->id,
            'color' => 'nullable|string',
        ]);

        $expenseCategory->update($data);

        return response()->json($expenseCategory);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return response()->json(['message' => '此分類下有支出紀錄，無法刪除'], 422);
        }

        $expenseCategory->delete();

        return response()->json(null, 204);
    }
}
