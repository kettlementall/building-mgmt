<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\FeeRule;
use App\Models\Resident;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_bills(): void
    {
        Bill::factory()->count(3)->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/bills');

        $response->assertOk()->assertJsonStructure(['data', 'total']);
    }

    public function test_can_filter_bills_by_year_and_month(): void
    {
        Bill::factory()->create(['year' => 2025, 'month' => 1]);
        Bill::factory()->create(['year' => 2025, 'month' => 6]);
        Bill::factory()->create(['year' => 2024, 'month' => 1]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/bills?year=2025&month=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_bills_by_status(): void
    {
        Bill::factory()->create(['status' => 'paid']);
        Bill::factory()->create(['status' => 'unpaid']);
        Bill::factory()->create(['status' => 'overdue']);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/bills?status=unpaid');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_show_bill(): void
    {
        $bill = Bill::factory()->create();
        $this->actingAsAdmin();

        $response = $this->getJson("/api/bills/{$bill->id}");

        $response->assertOk()->assertJsonPath('id', $bill->id);
    }

    public function test_can_generate_bills_with_fixed_fee_rule(): void
    {
        FeeRule::factory()->create([
            'type'           => 'fixed',
            'amount'         => 2000,
            'effective_from' => '2024-01-01',
            'effective_to'   => null,
        ]);
        Unit::factory()->occupied()->count(3)->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/bills/generate', [
            'year'  => 2025,
            'month' => 6,
        ]);

        $response->assertOk()->assertJsonPath('created', 3);
        $this->assertDatabaseCount('bills', 3);
    }

    public function test_generate_bills_calculates_per_area_amount(): void
    {
        FeeRule::factory()->create([
            'type'           => 'per_area',
            'amount'         => 50,
            'effective_from' => '2024-01-01',
        ]);
        Unit::factory()->occupied()->create(['area' => 40]);
        $this->actingAsAdmin();

        $this->postJson('/api/bills/generate', ['year' => 2025, 'month' => 6]);

        // 50 * 40 = 2000
        $this->assertDatabaseHas('bills', ['amount' => 2000]);
    }

    public function test_generate_bills_skips_existing_bills(): void
    {
        $unit = Unit::factory()->occupied()->create();
        $rule = FeeRule::factory()->create(['effective_from' => '2024-01-01']);
        Bill::factory()->create(['unit_id' => $unit->id, 'year' => 2025, 'month' => 6, 'fee_rule_id' => $rule->id]);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/bills/generate', ['year' => 2025, 'month' => 6]);

        $response->assertOk()
                 ->assertJsonPath('created', 0)
                 ->assertJsonPath('skipped', 1);
    }

    public function test_generate_bills_fails_without_active_rule(): void
    {
        Unit::factory()->occupied()->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/bills/generate', ['year' => 2025, 'month' => 6]);

        $response->assertUnprocessable();
    }

    public function test_generate_bills_skips_vacant_units(): void
    {
        FeeRule::factory()->create(['effective_from' => '2024-01-01']);
        Unit::factory()->create(['status' => 'vacant']);
        $this->actingAsAdmin();

        $response = $this->postJson('/api/bills/generate', ['year' => 2025, 'month' => 6]);

        $response->assertOk()->assertJsonPath('created', 0);
    }

    public function test_can_update_bill_note(): void
    {
        $bill = Bill::factory()->create();
        $this->actingAsAdmin();

        $response = $this->putJson("/api/bills/{$bill->id}", ['note' => '測試備註']);

        $response->assertOk()->assertJsonPath('note', '測試備註');
    }

    public function test_can_delete_bill(): void
    {
        $bill = Bill::factory()->create();
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/bills/{$bill->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
    }

    public function test_get_bills_by_unit(): void
    {
        $unit = Unit::factory()->occupied()->create();
        $rule = FeeRule::factory()->create();
        foreach ([1, 2] as $month) {
            Bill::factory()->create([
                'unit_id'     => $unit->id,
                'fee_rule_id' => $rule->id,
                'year'        => 2025,
                'month'       => $month,
            ]);
        }
        Bill::factory()->create(); // 其他戶的帳單
        $this->actingAsAdmin();

        $response = $this->getJson("/api/units/{$unit->id}/bills");

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_send_notice_fails_when_resident_has_no_email(): void
    {
        $unit     = Unit::factory()->occupied()->create();
        $resident = Resident::factory()->create(['unit_id' => $unit->id, 'email' => null]);
        $bill     = Bill::factory()->create(['unit_id' => $unit->id]);
        $this->actingAsAdmin();

        $response = $this->postJson("/api/bills/{$bill->id}/send-notice");

        $response->assertUnprocessable();
    }
}
