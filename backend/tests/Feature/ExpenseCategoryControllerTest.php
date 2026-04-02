<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_expense_categories(): void
    {
        ExpenseCategory::factory()->count(3)->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/expense-categories');

        $response->assertOk()->assertJsonCount(3);
    }

    public function test_list_includes_expenses_count(): void
    {
        $cat = ExpenseCategory::factory()->create();
        Expense::factory()->count(2)->create([
            'category_id' => $cat->id,
            'recorded_by' => User::factory()->create()->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/expense-categories');

        $response->assertOk();
        $this->assertEquals(2, $response->json('0.expenses_count'));
    }

    public function test_can_create_expense_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/expense-categories', [
            'name'  => '清潔費',
            'color' => '#409eff',
        ]);

        $response->assertCreated()->assertJsonPath('name', '清潔費');
        $this->assertDatabaseHas('expense_categories', ['name' => '清潔費']);
    }

    public function test_create_category_requires_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/expense-categories', ['color' => '#409eff']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_create_category_name_must_be_unique(): void
    {
        ExpenseCategory::factory()->create(['name' => '清潔費']);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/expense-categories', ['name' => '清潔費']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_expense_category(): void
    {
        $cat = ExpenseCategory::factory()->create(['name' => '舊分類']);
        $this->actingAsAdmin();

        $response = $this->putJson("/api/expense-categories/{$cat->id}", ['name' => '新分類']);

        $response->assertOk()->assertJsonPath('name', '新分類');
    }

    public function test_can_delete_empty_category(): void
    {
        $cat = ExpenseCategory::factory()->create();
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/expense-categories/{$cat->id}");

        $response->assertNoContent();
    }

    public function test_cannot_delete_category_with_expenses(): void
    {
        $cat = ExpenseCategory::factory()->create();
        Expense::factory()->create([
            'category_id' => $cat->id,
            'recorded_by' => User::factory()->create()->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/expense-categories/{$cat->id}");

        $response->assertUnprocessable();
        $this->assertDatabaseHas('expense_categories', ['id' => $cat->id]);
    }
}
