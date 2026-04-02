<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id', 'amount', 'method', 'paid_at', 'reference', 'recorded_by', 'note',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount'  => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // 登錄繳費後自動將帳單狀態改為 paid
    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            $payment->bill->update(['status' => 'paid']);
        });

        static::deleted(function (Payment $payment) {
            $payment->bill->update(['status' => 'unpaid']);
        });
    }
}
