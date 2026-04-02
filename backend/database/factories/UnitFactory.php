<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'floor'  => $this->faker->numberBetween(1, 20),
            'number' => str_pad($this->faker->numberBetween(1, 10), 2, '0', STR_PAD_LEFT),
            'area'   => $this->faker->randomFloat(2, 20, 100),
            'status' => 'vacant',
            'note'   => null,
        ];
    }

    public function occupied(): static
    {
        return $this->state(['status' => 'occupied']);
    }
}
