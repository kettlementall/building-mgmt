<?php

namespace Tests\Feature;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_units(): void
    {
        Unit::factory()->count(3)->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/units');

        $response->assertOk()->assertJsonCount(3);
    }

    public function test_list_units_requires_authentication(): void
    {
        $response = $this->getJson('/api/units');

        $response->assertUnauthorized();
    }

    public function test_can_create_unit(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/units', [
            'floor'  => '5',
            'number' => '03',
            'area'   => 45.50,
        ]);

        $response->assertCreated()
                 ->assertJsonPath('floor', '5')
                 ->assertJsonPath('number', '03');

        $this->assertDatabaseHas('units', ['floor' => '5', 'number' => '03']);
    }

    public function test_create_unit_requires_floor(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/units', ['number' => '01', 'area' => 30]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['floor']);
    }

    public function test_create_unit_requires_number(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/units', ['floor' => '3', 'area' => 30]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['number']);
    }

    public function test_create_unit_requires_area(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/units', ['floor' => '3', 'number' => '01']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['area']);
    }

    public function test_create_unit_floor_number_must_be_unique(): void
    {
        $this->actingAsAdmin();
        Unit::factory()->create(['floor' => '3', 'number' => '01']);

        $response = $this->postJson('/api/units', ['floor' => '3', 'number' => '01', 'area' => 30]);

        $response->assertStatus(500); // DB unique constraint
    }

    public function test_can_show_unit(): void
    {
        $unit = Unit::factory()->create();
        $this->actingAsAdmin();

        $response = $this->getJson("/api/units/{$unit->id}");

        $response->assertOk()->assertJsonPath('id', $unit->id);
    }

    public function test_show_unit_returns_404_for_nonexistent(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/units/999999');

        $response->assertNotFound();
    }

    public function test_can_update_unit(): void
    {
        $unit = Unit::factory()->create(['area' => 30]);
        $this->actingAsAdmin();

        $response = $this->putJson("/api/units/{$unit->id}", ['area' => 50.00]);

        $response->assertOk();
        $this->assertEquals(50.00, (float) $response->json('area'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'area' => 50.00]);
    }

    public function test_can_delete_unit(): void
    {
        $unit = Unit::factory()->create();
        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/units/{$unit->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('units', ['id' => $unit->id]);
    }
}
