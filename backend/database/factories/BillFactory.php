<?php

namespace Database\Factories;

use App\Models\FeeRule;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    public function definition(): array
    {
        static $month = 1;
        $m = $month > 12 ? ($month = 1) : $month++;

        return [
            'unit_id'     => Unit::factory()->occupied(),
            'fee_rule_id' => FeeRule::factory(),
            'year'        => 2025,
            'month'       => $m,
            'amount'      => 2000,
            'status'      => 'unpaid',
            'due_date'    => "2025-{$m}-15",
            'note'        => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => 'overdue',
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ]);
    }
}
