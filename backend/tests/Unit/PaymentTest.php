<?php

namespace Tests\Unit;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_payment_marks_bill_as_paid(): void
    {
        $bill = Bill::factory()->create(['status' => 'unpaid']);

        Payment::factory()->create([
            'bill_id'     => $bill->id,
            'recorded_by' => User::factory()->create()->id,
        ]);

        $this->assertEquals('paid', $bill->fresh()->status);
    }

    public function test_deleting_payment_reverts_bill_to_unpaid(): void
    {
        $bill    = Bill::factory()->paid()->create();
        $payment = Payment::factory()->create([
            'bill_id'     => $bill->id,
            'recorded_by' => User::factory()->create()->id,
        ]);

        // 先確認帳單是 paid
        $bill->update(['status' => 'paid']);

        $payment->delete();

        $this->assertEquals('unpaid', $bill->fresh()->status);
    }

    public function test_payment_belongs_to_bill(): void
    {
        $payment = Payment::factory()->create([
            'recorded_by' => User::factory()->create()->id,
        ]);

        $this->assertNotNull($payment->bill);
    }

    public function test_payment_amount_is_cast_to_decimal(): void
    {
        $payment = Payment::factory()->create([
            'amount'      => 2000,
            'recorded_by' => User::factory()->create()->id,
        ]);

        $this->assertEquals('2000.00', $payment->amount);
    }

    public function test_payment_paid_at_is_cast_to_date(): void
    {
        $payment = Payment::factory()->create([
            'paid_at'     => '2025-06-10',
            'recorded_by' => User::factory()->create()->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $payment->paid_at);
    }
}
