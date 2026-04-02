<?php

namespace Tests\Feature;

use App\Models\FeeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeRuleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_fee_rules(): void
    {
        FeeRule::factory()->count(2)->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/fee-rules');

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_can_create_fixed_fee_rule(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/fee-rules', [
            'type'           => 'fixed',
            'amount'         => 2500,
            'effective_from' => '2025-01-01',
        ]);

        $response->assertCreated()
                 ->assertJsonPath('type', 'fixed')
                 ->assertJsonPath('amount', '2500.00');
    }

    public function test_can_create_per_area_fee_rule(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/fee-rules', [
            'type'           => 'per_area',
            'amount'         => 55,
            'effective_from' => '2025-01-01',
        ]);

        $response->assertCreated()->assertJsonPath('type', 'per_area');
    }

    public function test_create_fee_rule_requires_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/fee-rules', [
            'amount'         => 2000,
            'effective_from' => '2025-01-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_create_fee_rule_rejects_invalid_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/fee-rules', [
            'type'           => 'invalid_type',
            'amount'         => 2000,
            'effective_from' => '2025-01-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_create_fee_rule_requires_amount(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/fee-rules', [
            'type'           => 'fixed',
            'effective_from' => '2025-01-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['amount']);
    }

    public function test_effective_to_must_be_after_effective_from(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/fee-rules', [
            'type'           => 'fixed',
            'amount'         => 2000,
            'effective_from' => '2025-06-01',
            'effective_to'   => '2025-01-01', // before effective_from
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['effective_to']);
    }

    public function test_can_update_fee_rule(): void
    {
        $rule = FeeRule::factory()->create(['amount' => 2000]);
        $this->actingAsAdmin();

        $response = $this->putJson("/api/fee-rules/{$rule->id}", ['amount' => 2500]);

        $response->assertOk()->assertJsonPath('amount', '2500.00');
    }

    public function test_can_delete_fee_rule(): void
    {
        $rule = FeeRule::factory()->create();
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/fee-rules/{$rule->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('fee_rules', ['id' => $rule->id]);
    }
}
