<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeRule extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'amount', 'effective_from', 'effective_to', 'note'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'amount'         => 'decimal:2',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    // 取得指定日期當下有效的規則
    public static function activeAt(string $date): ?self
    {
        return self::whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->latest('effective_from')
            ->first();
    }
}
