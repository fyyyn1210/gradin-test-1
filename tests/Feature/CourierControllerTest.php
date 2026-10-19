<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_couriers(): void
    {
        Courier::factory()->count(20)->create();

        $this->getJson('/api/couriers')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.has_prev', false)
            ->assertJsonPath('meta.has_next', true)
            ->assertJsonMissingPath('links')
            ->assertJsonMissingPath('meta.links')
            ->assertJsonMissingPath('meta.path');
    }

    public function test_index_respects_per_page(): void
    {
        Courier::factory()->count(5)->create();

        $this->getJson('/api/couriers?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.has_prev', true)
            ->assertJsonPath('meta.has_next', true);

        $this->getJson('/api/couriers?per_page=2&page=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.has_prev', true)
            ->assertJsonPath('meta.has_next', false);
    }

    public function test_index_returns_empty_page_without_navigation(): void
    {
        $this->getJson('/api/couriers')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.has_prev', false)
            ->assertJsonPath('meta.has_next', false);
    }

    public function test_index_sorts_by_name_by_default(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $this->getJson('/api/couriers')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Bravo', 'Charlie']);
    }

    public function test_index_sorts_by_registered_at(): void
    {
        Courier::factory()->create(['name' => 'Alpha', 'registered_at' => '2026-03-01']);
        Courier::factory()->create(['name' => 'Bravo', 'registered_at' => '2026-01-01']);
        Courier::factory()->create(['name' => 'Charlie', 'registered_at' => '2026-02-01']);

        $this->getJson('/api/couriers?sort_by=registered_at')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Bravo', 'Charlie', 'Alpha']);

        $this->getJson('/api/couriers?sort_by=registered_at&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Charlie', 'Bravo']);
    }

    public function test_index_searches_every_word_in_name(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Agung Wijaya']);

        $this->getJson('/api/couriers?search=budi+agung')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budiono Hadi Agung');
    }

    public function test_index_ignores_blank_search(): void
    {
        Courier::factory()->count(3)->create();

        $this->getJson('/api/couriers?search=%20%20')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_uses_defaults_for_empty_query_params(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $this->getJson('/api/couriers?search=&level=&sort_by=&sort_dir=&per_page=&page=')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Bravo', 'Charlie'])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_index_filters_by_levels(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->create(['level' => $level]);
        }

        $this->getJson('/api/couriers?level=2,3')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.*.level', fn (array $levels) => collect($levels)->sort()->values()->all() === [2, 3]);
    }

    public function test_index_combines_search_level_and_sort(): void
    {
        Courier::factory()->create(['name' => 'Budi Agung', 'level' => 2, 'registered_at' => '2026-02-01']);
        Courier::factory()->create(['name' => 'Agung Budiman', 'level' => 3, 'registered_at' => '2026-01-01']);
        Courier::factory()->create(['name' => 'Budi Agus', 'level' => 2]);
        Courier::factory()->create(['name' => 'Budi Agung Jr', 'level' => 5]);

        $this->getJson('/api/couriers?search=budi+agung&level=2,3&sort_by=registered_at')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Agung Budiman', 'Budi Agung']);
    }

    #[DataProvider('invalidIndexQueries')]
    public function test_index_rejects_invalid_query(string $query, string $field): void
    {
        $this->getJson("/api/couriers?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidIndexQueries(): array
    {
        return [
            'level out of range' => ['level=0,6', 'level'],
            'level not numeric' => ['level=abc', 'level'],
            'level trailing comma' => ['level=2,', 'level'],
            'unknown sort_by' => ['sort_by=phone', 'sort_by'],
            'unknown sort_dir' => ['sort_dir=up', 'sort_dir'],
            'per_page too large' => ['per_page=101', 'per_page'],
            'per_page zero' => ['per_page=0', 'per_page'],
            'page zero' => ['page=0', 'page'],
        ];
    }

    public function test_show_returns_courier(): void
    {
        $courier = Courier::factory()->create();

        $this->getJson("/api/couriers/{$courier->id}")
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $courier->id,
                'name' => $courier->name,
                'phone' => $courier->phone,
                'email' => $courier->email,
                'address' => $courier->address,
                'vehicleType' => $courier->vehicle_type->value,
                'vehiclePlate' => $courier->vehicle_plate,
                'level' => $courier->level,
                'isActive' => $courier->is_active,
            ]])
            ->assertJsonStructure(['data' => ['registeredAt', 'createdAt', 'updatedAt']]);
    }

    public function test_show_returns_not_found_for_missing_courier(): void
    {
        $this->getJson('/api/couriers/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }

    public function test_store_creates_courier(): void
    {
        $payload = [
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'address' => 'Jl. Merdeka No. 1',
            'vehicleType' => 'motorcycle',
            'vehiclePlate' => 'B 1234 XYZ',
            'level' => 3,
            'isActive' => false,
            'registeredAt' => '2026-01-15 08:00:00',
        ];

        $this->postJson('/api/couriers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.vehicleType', 'motorcycle')
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('couriers', [
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'address' => 'Jl. Merdeka No. 1',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => 'B 1234 XYZ',
            'level' => 3,
            'is_active' => false,
            'registered_at' => '2026-01-15 08:00:00',
        ]);
    }

    public function test_store_applies_default_values(): void
    {
        $this->postJson('/api/couriers', [
            'name' => 'Budi',
            'phone' => '081234567890',
            'vehicleType' => 'car',
        ])
            ->assertCreated()
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.isActive', true)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.registeredAt', fn (?string $value) => $value !== null);

        $this->assertDatabaseHas('couriers', [
            'name' => 'Budi',
            'level' => 1,
            'is_active' => true,
            'email' => null,
            'address' => null,
            'vehicle_plate' => null,
        ]);
    }

    public function test_store_requires_mandatory_fields(): void
    {
        $this->postJson('/api/couriers', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'vehicleType']);

        $this->assertDatabaseCount('couriers', 0);
    }

    #[DataProvider('invalidPayloads')]
    public function test_store_rejects_invalid_payload(array $override, string $field): void
    {
        $payload = array_merge([
            'name' => 'Budi',
            'phone' => '081234567890',
            'vehicleType' => 'car',
        ], $override);

        $this->postJson('/api/couriers', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('couriers', 0);
    }

    public static function invalidPayloads(): array
    {
        return [
            'name too long' => [['name' => str_repeat('a', 256)], 'name'],
            'name not string' => [['name' => ['budi']], 'name'],
            'phone with letters' => [['phone' => '08123abc'], 'phone'],
            'phone too short' => [['phone' => '0812'], 'phone'],
            'email invalid' => [['email' => 'not-an-email'], 'email'],
            'address too long' => [['address' => str_repeat('a', 1001)], 'address'],
            'vehicle type unknown' => [['vehicleType' => 'truck'], 'vehicleType'],
            'vehicle plate too long' => [['vehiclePlate' => str_repeat('a', 21)], 'vehiclePlate'],
            'level below range' => [['level' => 0], 'level'],
            'level above range' => [['level' => 6], 'level'],
            'level not integer' => [['level' => 2.5], 'level'],
            'is active not boolean' => [['isActive' => 'yes'], 'isActive'],
            'registered at invalid' => [['registeredAt' => 'not-a-date'], 'registeredAt'],
        ];
    }

    public function test_store_rejects_duplicate_phone_and_email(): void
    {
        Courier::factory()->create(['phone' => '081234567890', 'email' => 'budi@example.com']);

        $this->postJson('/api/couriers', [
            'name' => 'Budi',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'vehicleType' => 'car',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'email']);

        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_update_changes_courier(): void
    {
        $courier = Courier::factory()->create(['level' => 1, 'vehicle_type' => 'motorcycle']);

        $this->putJson("/api/couriers/{$courier->id}", [
            'name' => 'Budiono Hadi Agung',
            'level' => 4,
            'vehicleType' => 'van',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.level', 4)
            ->assertJsonPath('data.vehicleType', 'van');

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Budiono Hadi Agung',
            'phone' => $courier->phone,
            'level' => 4,
            'vehicle_type' => 'van',
        ]);
    }

    public function test_update_allows_clearing_nullable_fields(): void
    {
        $courier = Courier::factory()->create();

        $this->patchJson("/api/couriers/{$courier->id}", [
            'email' => null,
            'address' => null,
            'vehiclePlate' => null,
        ])->assertOk();

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'email' => null,
            'address' => null,
            'vehicle_plate' => null,
        ]);
    }

    public function test_update_keeps_own_phone_and_email(): void
    {
        $courier = Courier::factory()->create();

        $this->putJson("/api/couriers/{$courier->id}", [
            'phone' => $courier->phone,
            'email' => $courier->email,
        ])->assertOk();
    }

    public function test_update_rejects_phone_and_email_of_other_courier(): void
    {
        $other = Courier::factory()->create();
        $courier = Courier::factory()->create();

        $this->putJson("/api/couriers/{$courier->id}", [
            'phone' => $other->phone,
            'email' => $other->email,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'email']);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'phone' => $courier->phone,
            'email' => $courier->email,
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_update_rejects_invalid_payload(array $payload, string $field): void
    {
        $courier = Courier::factory()->create();

        $this->putJson("/api/couriers/{$courier->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => $courier->name,
            'level' => $courier->level,
        ]);
    }

    public function test_update_rejects_null_required_fields(): void
    {
        $courier = Courier::factory()->create();

        $this->putJson("/api/couriers/{$courier->id}", [
            'name' => null,
            'phone' => null,
            'vehicleType' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'vehicleType']);
    }

    public function test_update_returns_not_found_for_missing_courier(): void
    {
        $this->putJson('/api/couriers/999', ['name' => 'Budi'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }

    public function test_destroy_deletes_courier(): void
    {
        $courier = Courier::factory()->create();

        $this->deleteJson("/api/couriers/{$courier->id}")->assertNoContent();

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
    }

    public function test_destroy_returns_not_found_for_missing_courier(): void
    {
        $this->deleteJson('/api/couriers/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }
}
