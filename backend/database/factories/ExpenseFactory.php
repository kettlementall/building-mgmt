<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id'  => ExpenseCategory::factory(),
            'title'        => $this->faker->sentence(3),
            'amount'       => $this->faker->randomFloat(2, 100, 50000),
            'expense_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'vendor'       => $this->faker->company(),
            'description'  => $this->faker->sentence(),
            'recorded_by'  => User::factory(),
        ];
    }
}
