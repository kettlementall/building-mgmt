<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueNotification extends Notification implements ShouldQueue
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
            ->subject("【催繳通知】{$bill->year} 年 {$bill->month} 月管理費逾期未繳")
            ->greeting("您好，{$notifiable->name}")
            ->line("{$unit->floor} 樓 {$unit->number} 室 {$bill->year} 年 {$bill->month} 月管理費尚未繳納。")
            ->line("應繳金額：**NT$ " . number_format($bill->amount) . "**")
            ->line("繳費截止日：{$bill->due_date->format('Y-m-d')}（已逾期）")
            ->line('請盡快完成繳費，如有疑問請聯絡管理室，謝謝。');
    }
}
