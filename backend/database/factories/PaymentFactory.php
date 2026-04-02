<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bill_id'     => Bill::factory(),
            'amount'      => 2000,
            'method'      => 'cash',
            'paid_at'     => now()->format('Y-m-d'),
            'reference'   => null,
            'recorded_by' => User::factory(),
            'note'        => null,
        ];
    }

    public function transfer(): static
    {
        return $this->state([
            'method'    => 'transfer',
            'reference' => 'TXN' . $this->faker->numerify('#######'),
        ]);
    }
}
