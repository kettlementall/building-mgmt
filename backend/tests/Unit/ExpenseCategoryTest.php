<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_category_has_many_expenses(): void
    {
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->fresh()->expenses);
    }

    public function test_expense_category_expenses_is_empty_when_none_created(): void
    {
        $category = ExpenseCategory::factory()->create();

        $this->assertCount(0, $category->expenses);
    }
}
