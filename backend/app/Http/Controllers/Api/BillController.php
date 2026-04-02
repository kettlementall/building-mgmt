<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\FeeRule;
use App\Models\Unit;
use App\Notifications\BillNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $bills = Bill::with(['unit', 'payment'])
            ->when($request->year,   fn($q) => $q->where('year', $request->year))
            ->when($request->month,  fn($q) => $q->where('month', $request->month))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(20);

        return response()->json($bills);
    }

    public function byUnit(Unit $unit)
    {
        $bills = $unit->bills()->with('payment')->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return response()->json($bills);
    }

    public function show(Bill $bill)
    {
        return response()->json($bill->load(['unit.activeResident', 'feeRule', 'payment']));
    }

    // 手動批量產帳單（指定年月，預設當月）
    public function generate(Request $request)
    {
        $request->validate([
            'year'     => 'sometimes|integer|min:2000',
            'month'    => 'sometimes|integer|min:1|max:12',
            'due_days' => 'sometimes|integer|min:1', // 帳單截止日（幾日）
        ]);

        $year     = $request->year     ?? now()->year;
        $month    = $request->month    ?? now()->month;
        $dueDays  = $request->due_days ?? 15;
        $dueDate  = Carbon::create($year, $month, $dueDays)->format('Y-m-d');
        $ruleDate = Carbon::create($year, $month, 1)->format('Y-m-d');

        $rule = FeeRule::activeAt($ruleDate);

        if (!$rule) {
            return response()->json(['message' => '該月份無有效管理費規則'], 422);
        }

        $units   = Unit::where('status', 'occupied')->get();
        $created = 0;
        $skipped = 0;

        foreach ($units as $unit) {
            // 已存在的帳單不重複建立
            $exists = Bill::where('unit_id', $unit->id)
                ->where('year', $year)
                ->where('month', $month)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $amount = $rule->type === 'per_area'
                ? round($rule->amount * $unit->area, 0)
                : $rule->amount;

            Bill::create([
                'unit_id'     => $unit->id,
                'fee_rule_id' => $rule->id,
                'year'        => $year,
                'month'       => $month,
                'amount'      => $amount,
                'status'      => 'unpaid',
                'due_date'    => $dueDate,
            ]);

            $created++;
        }

        return response()->json([
            'message' => "帳單產生完成：建立 {$created} 筆，略過 {$skipped} 筆（已存在）",
            'year'    => $year,
            'month'   => $month,
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function update(Request $request, Bill $bill)
    {
        $data = $request->validate([
            'amount'   => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'status'   => 'sometimes|in:unpaid,paid,overdue',
            'note'     => 'nullable|string',
        ]);

        $bill->update($data);

        return response()->json($bill);
    }

    public function destroy(Bill $bill)
    {
        $bill->delete();

        return response()->json(null, 204);
    }

    // 寄送繳費通知 Email
    public function sendNotice(Bill $bill)
    {
        $resident = $bill->unit->activeResident;

        if (!$resident || !$resident->email) {
            return response()->json(['message' => '該住戶無 Email，無法發送通知'], 422);
        }

        $resident->notify(new BillNotification($bill));

        return response()->json(['message' => '通知已發送']);
    }
}
