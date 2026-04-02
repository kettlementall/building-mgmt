<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_payments(): void
    {
        Payment::factory()->count(2)->create([
            'recorded_by' => User::factory()->create()->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/payments');

        $response->assertOk()->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_payment_for_unpaid_bill(): void
    {
        $bill = Bill::factory()->create(['status' => 'unpaid', 'amount' => 2000]);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/payments', [
            'bill_id' => $bill->id,
            'amount'  => 2000,
            'method'  => 'cash',
            'paid_at' => '2025-06-10',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'paid']);
    }

    public function test_cannot_create_payment_for_already_paid_bill(): void
    {
        $bill = Bill::factory()->paid()->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/payments', [
            'bill_id' => $bill->id,
            'amount'  => 2000,
            'method'  => 'cash',
            'paid_at' => '2025-06-10',
        ]);

        $response->assertUnprocessable()->assertJsonPath('message', '此帳單已繳費');
    }

    public function test_create_payment_requires_bill_id(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/payments', [
            'amount'  => 2000,
            'method'  => 'cash',
            'paid_at' => '2025-06-10',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['bill_id']);
    }

    public function test_create_payment_requires_valid_method(): void
    {
        $bill = Bill::factory()->create(['status' => 'unpaid']);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/payments', [
            'bill_id' => $bill->id,
            'amount'  => 2000,
            'method'  => 'invalid',
            'paid_at' => '2025-06-10',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['method']);
    }

    public function test_payment_recorded_by_is_set_to_current_user(): void
    {
        $user = $this->actingAsAdmin();
        $bill = Bill::factory()->create(['status' => 'unpaid']);

        $this->postJson('/api/payments', [
            'bill_id' => $bill->id,
            'amount'  => 2000,
            'method'  => 'cash',
            'paid_at' => '2025-06-10',
        ]);

        $this->assertDatabaseHas('payments', [
            'bill_id'     => $bill->id,
            'recorded_by' => $user->id,
        ]);
    }

    public function test_can_delete_payment_and_bill_reverts_to_unpaid(): void
    {
        $bill    = Bill::factory()->paid()->create();
        $payment = Payment::factory()->create([
            'bill_id'     => $bill->id,
            'recorded_by' => User::factory()->create()->id,
        ]);
        $bill->update(['status' => 'paid']);
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/payments/{$payment->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'unpaid']);
    }

    public function test_can_get_payment_by_bill(): void
    {
        $bill    = Bill::factory()->paid()->create();
        $payment = Payment::factory()->create([
            'bill_id'     => $bill->id,
            'recorded_by' => User::factory()->create()->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson("/api/bills/{$bill->id}/payment");

        $response->assertOk()->assertJsonPath('id', $payment->id);
    }
}
