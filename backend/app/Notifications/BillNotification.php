<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Bill $bill) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bill = $this->bill;
        $unit = $bill->unit;

        return (new MailMessage)
            ->subject("{$bill->year} 年 {$bill->month} 月管理費繳費通知")
            ->greeting("您好，{$notifiable->name}")
            ->line("{$unit->floor} 樓 {$unit->number} 室 {$bill->year} 年 {$bill->month} 月管理費帳單已開立。")
            ->line("應繳金額：**NT$ " . number_format($bill->amount) . "**")
            ->line("繳費截止日：{$bill->due_date->format('Y-m-d')}")
            ->line('請於截止日前完成繳費，謝謝。');
    }
}
