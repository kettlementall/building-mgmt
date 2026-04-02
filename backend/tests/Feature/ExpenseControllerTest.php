<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_expenses(): void
    {
        Expense::factory()->count(3)->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/expenses');

        $response->assertOk()->assertJsonStructure(['data', 'total']);
    }

    public function test_can_filter_expenses_by_category(): void
    {
        $cat1 = ExpenseCategory::factory()->create();
        $cat2 = ExpenseCategory::factory()->create();
        Expense::factory()->count(2)->create(['category_id' => $cat1->id, 'recorded_by' => User::factory()->create()->id]);
        Expense::factory()->create(['category_id' => $cat2->id, 'recorded_by' => User::factory()->create()->id]);
        $this->actingAsAdmin();

        $response = $this->getJson("/api/expenses?category_id={$cat1->id}");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_filter_expenses_by_year_and_month(): void
    {
        $user = User::factory()->create();
        $cat  = ExpenseCategory::factory()->create();
        Expense::factory()->create(['expense_date' => '2025-03-15', 'category_id' => $cat->id, 'recorded_by' => $user->id]);
        Expense::factory()->create(['expense_date' => '2025-06-20', 'category_id' => $cat->id, 'recorded_by' => $user->id]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/expenses?year=2025&month=3');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_create_expense(): void
    {
        $cat  = ExpenseCategory::factory()->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/expenses', [
            'category_id'  => $cat->id,
            'title'        => '4月清潔費',
            'amount'       => 5000,
            'expense_date' => '2025-04-01',
            'vendor'       => '清潔公司',
        ]);

        $response->assertCreated()->assertJsonPath('title', '4月清潔費');
        $this->assertDatabaseHas('expenses', ['title' => '4月清潔費']);
    }

    public function test_create_expense_requires_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/expenses', [
            'title'        => '4月清潔費',
            'amount'       => 5000,
            'expense_date' => '2025-04-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
    }

    public function test_create_expense_requires_valid_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/expenses', [
            'category_id'  => 999999,
            'title'        => '4月清潔費',
            'amount'       => 5000,
            'expense_date' => '2025-04-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
    }

    public function test_recorded_by_is_set_to_current_user(): void
    {
        $user = $this->actingAsAdmin();
        $cat  = ExpenseCategory::factory()->create();

        $this->postJson('/api/expenses', [
            'category_id'  => $cat->id,
            'title'        => '測試支出',
            'amount'       => 1000,
            'expense_date' => '2025-04-01',
        ]);

        $this->assertDatabaseHas('expenses', ['recorded_by' => $user->id]);
    }

    public function test_can_update_expense(): void
    {
        $expense = Expense::factory()->create(['amount' => 1000]);
        $this->actingAsAdmin();

        $response = $this->putJson("/api/expenses/{$expense->id}", ['amount' => 2000]);

        $response->assertOk()->assertJsonPath('amount', '2000.00');
    }

    public function test_can_delete_expense(): void
    {
        $expense = Expense::factory()->create();
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/expenses/{$expense->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_can_upload_attachment(): void
    {
        Storage::fake('public');
        $expense = Expense::factory()->create();
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/expenses/{$expense->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertCreated()->assertJsonPath('filename', 'receipt.pdf');
        $this->assertDatabaseCount('expense_attachments', 1);
    }

    public function test_upload_attachment_rejects_invalid_type(): void
    {
        Storage::fake('public');
        $expense = Expense::factory()->create();
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $response = $this->postJson("/api/expenses/{$expense->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['file']);
    }

    public function test_upload_attachment_rejects_file_over_5mb(): void
    {
        Storage::fake('public');
        $expense = Expense::factory()->create();
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'); // 6MB

        $response = $this->postJson("/api/expenses/{$expense->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['file']);
    }

    public function test_can_delete_attachment(): void
    {
        Storage::fake('public');
        $expense    = Expense::factory()->create();
        $attachment = ExpenseAttachment::factory()->create([
            'expense_id' => $expense->id,
            'path'       => 'expenses/1/receipt.pdf',
        ]);
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/expenses/{$expense->id}/attachments/{$attachment->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('expense_attachments', ['id' => $attachment->id]);
    }

    public function test_cannot_delete_attachment_belonging_to_different_expense(): void
    {
        $expense1   = Expense::factory()->create();
        $expense2   = Expense::factory()->create();
        $attachment = ExpenseAttachment::factory()->create(['expense_id' => $expense2->id]);
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/expenses/{$expense1->id}/attachments/{$attachment->id}");

        $response->assertNotFound();
    }
}
