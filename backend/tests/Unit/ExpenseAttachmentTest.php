<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_belongs_to_expense(): void
    {
        $expense    = Expense::factory()->create();
        $attachment = ExpenseAttachment::factory()->create(['expense_id' => $expense->id]);

        $this->assertNotNull($attachment->expense);
        $this->assertEquals($expense->id, $attachment->expense->id);
    }

    public function test_attachment_url_attribute_returns_storage_url(): void
    {
        Storage::fake('public');

        $attachment = ExpenseAttachment::factory()->create([
            'path' => 'expenses/1/receipt.pdf',
        ]);

        $this->assertStringContainsString('expenses/1/receipt.pdf', $attachment->url);
    }
}
