<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'filename'   => $this->faker->word() . '.pdf',
            'path'       => 'expenses/1/' . $this->faker->word() . '.pdf',
            'mime_type'  => 'application/pdf',
        ];
    }
}
