<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_id'       => Unit::factory(),
            'name'          => $this->faker->name(),
            'phone'         => $this->faker->phoneNumber(),
            'email'         => $this->faker->safeEmail(),
            'move_in_date'  => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'move_out_date' => null,
            'type'          => 'owner',
            'is_active'     => true,
            'note'          => null,
        ];
    }

    public function tenant(): static
    {
        return $this->state(['type' => 'tenant']);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active'     => false,
            'move_out_date' => now()->format('Y-m-d'),
        ]);
    }
}
