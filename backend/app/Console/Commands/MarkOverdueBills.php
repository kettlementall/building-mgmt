<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Resident;
use App\Notifications\OverdueNotification;
use Illuminate\Console\Command;

class MarkOverdueBills extends Command
{
    protected $signature   = 'bills:mark-overdue {--notify}';
    protected $description = '將超過截止日的未繳帳單標記為逾期，--notify 同時發送催繳通知';

    public function handle(): void
    {
        $bills = Bill::where('status', 'unpaid')
            ->where('due_date', '<', now()->toDateString())
            ->get();

        foreach ($bills as $bill) {
            $bill->update(['status' => 'overdue']);

            if ($this->option('notify')) {
                $resident = $bill->unit->activeResident;
                if ($resident && $resident->email) {
                    $resident->notify(new OverdueNotification($bill));
                }
            }
        }

        $this->info("已將 {$bills->count()} 筆帳單標記為逾期。");
    }
}
