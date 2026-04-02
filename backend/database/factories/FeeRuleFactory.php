<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeeRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'           => 'fixed',
            'amount'         => 2000,
            'effective_from' => '2024-01-01',
            'effective_to'   => null,
            'note'           => null,
        ];
    }

    public function perArea(float $pricePerArea = 50): static
    {
        return $this->state([
            'type'   => 'per_area',
            'amount' => $pricePerArea,
        ]);
    }

    public function effectiveBetween(string $from, string $to): static
    {
        return $this->state([
            'effective_from' => $from,
            'effective_to'   => $to,
        ]);
    }
}
