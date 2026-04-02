<?php

namespace Tests\Unit;

use App\Models\Resident;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_belongs_to_unit(): void
    {
        $unit     = Unit::factory()->create();
        $resident = Resident::factory()->create(['unit_id' => $unit->id]);

        $this->assertNotNull($resident->unit);
        $this->assertEquals($unit->id, $resident->unit->id);
    }

    public function test_resident_move_in_date_is_cast_to_date(): void
    {
        $resident = Resident::factory()->create(['move_in_date' => '2024-03-01']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $resident->move_in_date);
        $this->assertEquals('2024-03-01', $resident->move_in_date->format('Y-m-d'));
    }

    public function test_resident_move_out_date_is_cast_to_date(): void
    {
        $resident = Resident::factory()->create(['move_out_date' => '2025-01-31']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $resident->move_out_date);
        $this->assertEquals('2025-01-31', $resident->move_out_date->format('Y-m-d'));
    }

    public function test_resident_move_out_date_can_be_null(): void
    {
        $resident = Resident::factory()->create(['move_out_date' => null]);

        $this->assertNull($resident->move_out_date);
    }

    public function test_resident_is_active_is_cast_to_boolean(): void
    {
        $resident = Resident::factory()->create(['is_active' => true]);

        $this->assertIsBool($resident->is_active);
        $this->assertTrue($resident->is_active);
    }

    public function test_inactive_factory_state_sets_is_active_false(): void
    {
        $resident = Resident::factory()->inactive()->create();

        $this->assertFalse($resident->is_active);
        $this->assertNotNull($resident->move_out_date);
    }

    public function test_tenant_factory_state_sets_type_to_tenant(): void
    {
        $resident = Resident::factory()->tenant()->create();

        $this->assertEquals('tenant', $resident->type);
    }

    public function test_resident_soft_delete_hides_from_default_query(): void
    {
        $resident = Resident::factory()->create();
        $id       = $resident->id;

        $resident->delete();

        $this->assertNull(Resident::find($id));
        $this->assertNotNull(Resident::withTrashed()->find($id));
    }
}
