<?php

namespace Tests\Unit;

use App\Models\FeeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_at_returns_rule_with_no_end_date(): void
    {
        FeeRule::factory()->create([
            'effective_from' => '2024-01-01',
            'effective_to'   => null,
        ]);

        $rule = FeeRule::activeAt('2025-06-01');

        $this->assertNotNull($rule);
    }

    public function test_active_at_returns_rule_within_date_range(): void
    {
        FeeRule::factory()->create([
            'effective_from' => '2024-01-01',
            'effective_to'   => '2024-12-31',
        ]);

        $rule = FeeRule::activeAt('2024-06-01');

        $this->assertNotNull($rule);
    }

    public function test_active_at_returns_null_before_effective_from(): void
    {
        FeeRule::factory()->create([
            'effective_from' => '2024-06-01',
            'effective_to'   => null,
        ]);

        $rule = FeeRule::activeAt('2024-01-01');

        $this->assertNull($rule);
    }

    public function test_active_at_returns_null_after_effective_to(): void
    {
        FeeRule::factory()->create([
            'effective_from' => '2024-01-01',
            'effective_to'   => '2024-06-30',
        ]);

        $rule = FeeRule::activeAt('2024-12-01');

        $this->assertNull($rule);
    }

    public function test_active_at_returns_latest_rule_when_multiple_match(): void
    {
        FeeRule::factory()->create([
            'amount'         => 2000,
            'effective_from' => '2024-01-01',
            'effective_to'   => null,
        ]);

        FeeRule::factory()->create([
            'amount'         => 2500,
            'effective_from' => '2025-01-01',
            'effective_to'   => null,
        ]);

        $rule = FeeRule::activeAt('2025-06-01');

        $this->assertEquals(2500, $rule->amount);
    }

    public function test_active_at_on_exact_effective_from_date(): void
    {
        FeeRule::factory()->create([
            'effective_from' => '2025-01-01',
            'effective_to'   => null,
        ]);

        $rule = FeeRule::activeAt('2025-01-01');

        $this->assertNotNull($rule);
    }

    public function test_active_at_on_exact_effective_to_date(): void
    {
        FeeRule::factory()->create([
            'effective_from' => '2024-01-01',
            'effective_to'   => '2024-12-31',
        ]);

        $rule = FeeRule::activeAt('2024-12-31');

        $this->assertNotNull($rule);
    }

    public function test_fee_rule_has_many_bills(): void
    {
        $rule = FeeRule::factory()->create();
        $unit = \App\Models\Unit::factory()->occupied()->create();

        \App\Models\Bill::factory()->count(2)->create([
            'fee_rule_id' => $rule->id,
            'unit_id'     => $unit->id,
        ]);

        $this->assertCount(2, $rule->fresh()->bills);
    }

    public function test_fee_rule_amount_is_cast_to_decimal(): void
    {
        $rule = FeeRule::factory()->create(['amount' => 3000]);

        $this->assertEquals('3000.00', $rule->amount);
    }

    public function test_fee_rule_dates_are_cast_to_date(): void
    {
        $rule = FeeRule::factory()->create([
            'effective_from' => '2024-01-01',
            'effective_to'   => '2024-12-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $rule->effective_from);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $rule->effective_to);
        $this->assertEquals('2024-01-01', $rule->effective_from->format('Y-m-d'));
        $this->assertEquals('2024-12-31', $rule->effective_to->format('Y-m-d'));
    }

    public function test_fee_rule_effective_to_null_is_allowed(): void
    {
        $rule = FeeRule::factory()->create(['effective_to' => null]);

        $this->assertNull($rule->effective_to);
    }
}
