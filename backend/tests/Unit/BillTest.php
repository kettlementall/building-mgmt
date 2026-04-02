<?php

namespace Tests\Unit;

use App\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_paid_returns_true_when_status_is_paid(): void
    {
        $bill = Bill::factory()->paid()->create();

        $this->assertTrue($bill->isPaid());
    }

    public function test_is_paid_returns_false_when_status_is_unpaid(): void
    {
        $bill = Bill::factory()->create(['status' => 'unpaid']);

        $this->assertFalse($bill->isPaid());
    }

    public function test_is_paid_returns_false_when_status_is_overdue(): void
    {
        $bill = Bill::factory()->overdue()->create();

        $this->assertFalse($bill->isPaid());
    }

    public function test_bill_belongs_to_unit(): void
    {
        $bill = Bill::factory()->create();

        $this->assertNotNull($bill->unit);
    }

    public function test_bill_belongs_to_fee_rule(): void
    {
        $bill = Bill::factory()->create();

        $this->assertNotNull($bill->feeRule);
    }

    public function test_bill_amount_is_cast_to_decimal(): void
    {
        $bill = Bill::factory()->create(['amount' => 2000]);

        $this->assertEquals('2000.00', $bill->amount);
    }

    public function test_bill_due_date_is_cast_to_date(): void
    {
        $bill = Bill::factory()->create(['due_date' => '2025-06-15']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $bill->due_date);
        $this->assertEquals('2025-06-15', $bill->due_date->format('Y-m-d'));
    }
}
