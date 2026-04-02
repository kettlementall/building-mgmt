<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function yearExpr(string $col): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', {$col}) AS INTEGER)"
            : "YEAR({$col})";
    }

    private function monthExpr(string $col): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$col}) AS INTEGER)"
            : "MONTH({$col})";
    }

    // 收入統計（依年月）
    public function income(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $query = Payment::query()
            ->join('bills', 'payments.bill_id', '=', 'bills.id')
            ->join('units', 'bills.unit_id', '=', 'units.id')
            ->whereYear('paid_at', $request->year)
            ->when($request->month, fn($q) => $q->whereMonth('paid_at', $request->month))
            ->select(
                DB::raw($this->yearExpr('paid_at') . ' as year'),
                DB::raw($this->monthExpr('paid_at') . ' as month'),
                DB::raw('SUM(payments.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');

        return response()->json($query->get());
    }

    // 支出統計（依年月、分類）
    public function expense(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $byCategory = Expense::query()
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->whereYear('expense_date', $request->year)
            ->when($request->month, fn($q) => $q->whereMonth('expense_date', $request->month))
            ->select(
                'expense_categories.name as category',
                'expense_categories.color',
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->orderBy('total', 'desc')
            ->get();

        $grandTotal = $byCategory->sum('total');

        return response()->json([
            'by_category' => $byCategory,
            'total'       => $grandTotal,
        ]);
    }

    // 結餘報表（收入 - 支出，按月份）
    public function balance(Request $request)
    {
        $request->validate(['year' => 'required|integer']);

        $year = $request->year;

        // 各月收入
        $incomeRows = Payment::whereYear('paid_at', $year)
            ->select(DB::raw($this->monthExpr('paid_at') . ' as month'), DB::raw('SUM(amount) as income'))
            ->groupBy('month')
            ->pluck('income', 'month');

        // 各月支出
        $expenseRows = Expense::whereYear('expense_date', $year)
            ->select(DB::raw($this->monthExpr('expense_date') . ' as month'), DB::raw('SUM(amount) as expense'))
            ->groupBy('month')
            ->pluck('expense', 'month');

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $income  = (float) ($incomeRows[$m]  ?? 0);
            $expense = (float) ($expenseRows[$m] ?? 0);
            $result[] = [
                'month'   => $m,
                'income'  => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        }

        return response()->json([
            'year'         => $year,
            'months'       => $result,
            'total_income'  => array_sum(array_column($result, 'income')),
            'total_expense' => array_sum(array_column($result, 'expense')),
            'total_balance' => array_sum(array_column($result, 'balance')),
        ]);
    }

    // 欠繳清單
    public function overdue(Request $request)
    {
        $bills = Bill::with(['unit.activeResident'])
            ->whereIn('status', ['unpaid', 'overdue'])
            ->when($request->year,  fn($q) => $q->where('year', $request->year))
            ->when($request->month, fn($q) => $q->where('month', $request->month))
            ->orderBy('due_date')
            ->get();

        $total = $bills->sum('amount');

        return response()->json([
            'bills' => $bills,
            'total' => $total,
            'count' => $bills->count(),
        ]);
    }
}
