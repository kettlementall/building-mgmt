<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_residents(): void
    {
        Resident::factory()->count(3)->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/residents');

        $response->assertOk()->assertJsonStructure(['data', 'total']);
    }

    public function test_can_create_resident(): void
    {
        $unit = Unit::factory()->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/residents', [
            'unit_id'      => $unit->id,
            'name'         => '王小明',
            'email'        => 'ming@example.com',
            'type'         => 'owner',
            'move_in_date' => '2024-01-01',
        ]);

        $response->assertCreated()->assertJsonPath('name', '王小明');
        $this->assertDatabaseHas('residents', ['name' => '王小明']);
    }

    public function test_creating_resident_sets_unit_as_occupied(): void
    {
        $unit = Unit::factory()->create(['status' => 'vacant']);
        $this->actingAsAdmin();

        $this->postJson('/api/residents', [
            'unit_id'      => $unit->id,
            'name'         => '王小明',
            'type'         => 'owner',
            'move_in_date' => '2024-01-01',
        ]);

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'status' => 'occupied']);
    }

    public function test_create_resident_requires_name(): void
    {
        $unit = Unit::factory()->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/residents', [
            'unit_id'      => $unit->id,
            'type'         => 'owner',
            'move_in_date' => '2024-01-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_create_resident_requires_valid_unit(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/residents', [
            'unit_id'      => 999999,
            'name'         => '王小明',
            'type'         => 'owner',
            'move_in_date' => '2024-01-01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['unit_id']);
    }

    public function test_can_update_resident(): void
    {
        $resident = Resident::factory()->create(['name' => '舊名字']);
        $this->actingAsAdmin();

        $response = $this->putJson("/api/residents/{$resident->id}", ['name' => '新名字']);

        $response->assertOk()->assertJsonPath('name', '新名字');
    }

    public function test_updating_resident_to_inactive_sets_unit_vacant(): void
    {
        $unit     = Unit::factory()->occupied()->create();
        $resident = Resident::factory()->create(['unit_id' => $unit->id, 'is_active' => true]);
        $this->actingAsAdmin();

        $this->putJson("/api/residents/{$resident->id}", ['is_active' => false]);

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'status' => 'vacant']);
    }

    public function test_can_get_residents_by_unit(): void
    {
        $unit = Unit::factory()->create();
        Resident::factory()->count(2)->create(['unit_id' => $unit->id]);
        $this->actingAsAdmin();

        $response = $this->getJson("/api/units/{$unit->id}/residents");

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_can_delete_resident(): void
    {
        $resident = Resident::factory()->create();
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/residents/{$resident->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('residents', ['id' => $resident->id]);
    }
}
