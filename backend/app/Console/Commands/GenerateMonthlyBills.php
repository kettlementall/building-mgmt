<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\FeeRule;
use App\Models\Unit;
use App\Notifications\BillNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyBills extends Command
{
    protected $signature   = 'bills:generate {--year=} {--month=} {--notify}';
    protected $description = '每月自動產生管理費帳單，--notify 同時發送 Email 通知';

    public function handle(): void
    {
        $year  = $this->option('year')  ?? now()->year;
        $month = $this->option('month') ?? now()->month;

        $ruleDate = Carbon::create($year, $month, 1)->format('Y-m-d');
        $rule     = FeeRule::activeAt($ruleDate);

        if (!$rule) {
            $this->error("找不到 {$year}/{$month} 的有效管理費規則，請先設定。");
            return;
        }

        $dueDate = Carbon::create($year, $month, 15)->format('Y-m-d');
        $units   = Unit::where('status', 'occupied')->get();
        $created = 0;

        foreach ($units as $unit) {
            $exists = Bill::where('unit_id', $unit->id)
                ->where('year', $year)->where('month', $month)->exists();

            if ($exists) continue;

            $amount = $rule->type === 'per_area'
                ? round($rule->amount * $unit->area, 0)
                : $rule->amount;

            $bill = Bill::create([
                'unit_id'     => $unit->id,
                'fee_rule_id' => $rule->id,
                'year'        => $year,
                'month'       => $month,
                'amount'      => $amount,
                'status'      => 'unpaid',
                'due_date'    => $dueDate,
            ]);

            if ($this->option('notify')) {
                $resident = $unit->activeResident;
                if ($resident && $resident->email) {
                    $resident->notify(new BillNotification($bill));
                }
            }

            $created++;
        }

        $this->info("帳單產生完成：{$year}/{$month}，共建立 {$created} 筆。");
    }
}
