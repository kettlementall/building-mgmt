<?php

namespace Tests\Unit;

use App\Models\Resident;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_has_many_residents(): void
    {
        $unit = Unit::factory()->create();
        Resident::factory()->count(2)->create(['unit_id' => $unit->id]);

        $this->assertCount(2, $unit->residents);
    }

    public function test_active_resident_returns_only_active_one(): void
    {
        $unit = Unit::factory()->create();

        // 已退租住戶
        Resident::factory()->inactive()->create(['unit_id' => $unit->id]);

        // 現居住戶
        $active = Resident::factory()->create(['unit_id' => $unit->id, 'is_active' => true]);

        $this->assertEquals($active->id, $unit->activeResident->id);
    }

    public function test_active_resident_returns_null_when_vacant(): void
    {
        $unit = Unit::factory()->create();

        $this->assertNull($unit->activeResident);
    }

    public function test_unit_has_many_bills(): void
    {
        $unit = Unit::factory()->occupied()->create();
        $rule = \App\Models\FeeRule::factory()->create();

        foreach ([1, 2, 3] as $month) {
            \App\Models\Bill::factory()->create([
                'unit_id'     => $unit->id,
                'fee_rule_id' => $rule->id,
                'year'        => 2025,
                'month'       => $month,
            ]);
        }

        $this->assertCount(3, $unit->fresh()->bills);
    }
}
