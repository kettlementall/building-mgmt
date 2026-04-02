<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_belongs_to_category(): void
    {
        $category = ExpenseCategory::factory()->create();
        $expense  = Expense::factory()->create(['category_id' => $category->id]);

        $this->assertNotNull($expense->category);
        $this->assertEquals($category->id, $expense->category->id);
    }

    public function test_expense_belongs_to_recorder(): void
    {
        $user    = User::factory()->create();
        $expense = Expense::factory()->create(['recorded_by' => $user->id]);

        $this->assertNotNull($expense->recorder);
        $this->assertEquals($user->id, $expense->recorder->id);
    }

    public function test_expense_has_many_attachments(): void
    {
        $expense = Expense::factory()->create();
        ExpenseAttachment::factory()->count(3)->create(['expense_id' => $expense->id]);

        $this->assertCount(3, $expense->fresh()->attachments);
    }

    public function test_expense_amount_is_cast_to_decimal(): void
    {
        $expense = Expense::factory()->create(['amount' => 1500]);

        $this->assertEquals('1500.00', $expense->amount);
    }

    public function test_expense_date_is_cast_to_date(): void
    {
        $expense = Expense::factory()->create(['expense_date' => '2025-03-15']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $expense->expense_date);
        $this->assertEquals('2025-03-15', $expense->expense_date->format('Y-m-d'));
    }

    public function test_expense_soft_delete_hides_from_default_query(): void
    {
        $expense = Expense::factory()->create();
        $id      = $expense->id;

        $expense->delete();

        $this->assertNull(Expense::find($id));
        $this->assertNotNull(Expense::withTrashed()->find($id));
    }
}
